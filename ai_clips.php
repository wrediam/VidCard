<?php
require_once 'config.php';

class AIClips {
    private $db;
    private $webhookUrl;
    
    public function __construct() {
        $this->db = getDB();
        // Use the clip generation webhook URL
        $this->webhookUrl = getenv('N8N_CLIP_WEBHOOK_GEN_URL') ?: 'https://n8n.wredia.com/webhook/generate_clips';
    }
    
    /**
     * Generate clip suggestions via n8n webhook
     */
    public function generateClipSuggestions($transcriptText, $transcriptRaw, $videoId, $userId) {
        if (empty($this->webhookUrl)) {
            throw new Exception('n8n clip webhook URL not configured');
        }
        
        if (empty($transcriptText)) {
            throw new Exception('Transcript text is required');
        }
        
        if (empty($transcriptRaw)) {
            throw new Exception('Transcript raw data is required for timestamp extraction');
        }
        
        try {
            // Intelligently chunk transcript if too long
            $textToSend = $this->prepareTranscriptForAI($transcriptText);
            
            error_log("Sending transcript to n8n: " . strlen($textToSend) . " characters (original: " . strlen($transcriptText) . " chars)");
            
            // Send clean transcript text to n8n webhook (not timestamped version)
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $textToSend);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Increased to 120 seconds for AI processing
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: text/plain'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("n8n clip webhook cURL error: $curlError");
                throw new Exception('Network error connecting to AI service: ' . $curlError);
            }
            
            if ($httpCode !== 200 || !$response) {
                error_log("n8n clip webhook error: HTTP $httpCode - Response: " . substr($response ?: 'empty', 0, 500));
                throw new Exception('Failed to generate clip suggestions from n8n webhook (HTTP ' . $httpCode . ')');
            }
            
            $data = json_decode($response, true);
            
            if (!$data || !isset($data[0]['output']['clip_suggestions'])) {
                error_log("n8n clip webhook invalid response: " . substr($response, 0, 500));
                throw new Exception('Invalid response format from n8n webhook');
            }
            
            $clipSuggestions = $data[0]['output']['clip_suggestions'];
            
            // Validate we have suggestions
            if (empty($clipSuggestions)) {
                error_log("n8n clip webhook returned no suggestions");
                throw new Exception('No clip suggestions generated');
            }
            
            // Parse transcript_raw JSON - handle nested structure
            $rawData = is_string($transcriptRaw) ? json_decode($transcriptRaw, true) : $transcriptRaw;
            
            // Check if data is wrapped in {"data": "..."} structure
            if (isset($rawData['data']) && is_string($rawData['data'])) {
                error_log("Unwrapping nested transcript_raw data structure");
                $rawData = json_decode($rawData['data'], true);
            }
            
            if (!$rawData || !isset($rawData['events'])) {
                error_log("Failed to parse transcript_raw JSON or missing events array. Structure: " . print_r(array_keys($rawData ?: []), true));
                throw new Exception('Invalid transcript raw data format - missing events structure');
            }
            
            error_log("Successfully parsed transcript_raw with " . count($rawData['events']) . " events");
            
            // Process each suggestion to locate timestamps from quotations
            $processedSuggestions = [];
            foreach ($clipSuggestions as $index => $suggestion) {
                try {
                    // Check for quotation field (accept both 'quotation' and 'verbatim_quote')
                    $quotation = null;
                    if (isset($suggestion['quotation']) && !empty($suggestion['quotation'])) {
                        $quotation = $suggestion['quotation'];
                    } elseif (isset($suggestion['verbatim_quote']) && !empty($suggestion['verbatim_quote'])) {
                        $quotation = $suggestion['verbatim_quote'];
                        error_log("Clip suggestion #$index using 'verbatim_quote' field instead of 'quotation'");
                    }
                    
                    if (!$quotation) {
                        error_log("Clip suggestion #$index missing quotation/verbatim_quote field. Available fields: " . implode(', ', array_keys($suggestion)));
                        continue;
                    }
                    
                    error_log("Processing clip #$index: " . substr($quotation, 0, 80) . "...");
                    error_log("Clip #$index: Full AI quote: " . $quotation);
                    
                    // STEP 1: Verify and correct the AI's quotation against the actual transcript
                    $verifiedQuotation = $this->verifyAndCorrectQuotation($quotation, $transcriptText, $index);
                    
                    if (!$verifiedQuotation) {
                        error_log("Could not verify quotation #$index against transcript. Skipping.");
                        continue;
                    }
                    
                    // STEP 2: Locate the verified quotation in the timestamped data
                    $timestamps = $this->locateQuotationInTranscript($verifiedQuotation['corrected_text'], $rawData, $index);
                    
                    if ($timestamps) {
                        $startSec = round($timestamps['start_time_ms'] / 1000);
                        $endSec = round($timestamps['end_time_ms'] / 1000);
                        
                        $processedSuggestions[] = [
                            'quotation' => $verifiedQuotation['corrected_text'], // Use corrected version
                            'ai_original_quote' => $quotation, // Keep original for reference
                            'was_corrected' => $verifiedQuotation['was_corrected'],
                            'similarity_score' => $verifiedQuotation['similarity_score'],
                            'start_time_ms' => $timestamps['start_time_ms'],
                            'end_time_ms' => $timestamps['end_time_ms'],
                            'suggested_title' => $suggestion['suggested_title'] ?? 'Clip ' . (count($processedSuggestions) + 1),
                            'reason' => $suggestion['reason'] ?? 'AI-selected clip',
                            'match_confidence' => $timestamps['match_confidence'] ?? 1.0
                        ];
                        error_log("✅ SUCCESS Clip #$index: {$timestamps['start_time_ms']}ms ({$startSec}s) - {$timestamps['end_time_ms']}ms ({$endSec}s)");
                        error_log("Clip #$index: Match confidence: " . round($timestamps['match_confidence'] * 100) . "%, Corrected: " . ($verifiedQuotation['was_corrected'] ? 'yes' : 'no'));
                        error_log("Clip #$index: Title: " . ($suggestion['suggested_title'] ?? 'Untitled'));
                        error_log("Clip #$index: Corrected text used: " . substr($verifiedQuotation['corrected_text'], 0, 150) . "...");
                    } else {
                        error_log("Could not locate quotation #$index in transcript. Quotation: " . substr($verifiedQuotation['corrected_text'], 0, 200));
                    }
                } catch (Exception $e) {
                    error_log("Error processing clip suggestion #$index: " . $e->getMessage());
                    continue;
                }
            }
            
            if (empty($processedSuggestions)) {
                throw new Exception('Could not locate any quotations in the transcript');
            }
            
            // Save to database (will overwrite existing suggestions for this video)
            $this->saveClipSuggestions($videoId, $userId, $processedSuggestions);
            
            return $processedSuggestions;
            
        } catch (Exception $e) {
            error_log('AI clip generation error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Prepare transcript for AI processing - chunk if too long
     * Returns a single string (either full transcript or a strategic chunk)
     */
    private function prepareTranscriptForAI($transcriptText) {
        $maxChars = 50000; // ~12,500 tokens for most LLMs (safe limit)
        $minChars = 15000; // Minimum chunk size to ensure context
        
        $textLength = strlen($transcriptText);
        
        // If transcript is short enough, send it all
        if ($textLength <= $maxChars) {
            error_log("Transcript is {$textLength} chars, sending full transcript");
            return $transcriptText;
        }
        
        error_log("Transcript is {$textLength} chars, selecting strategic chunk");
        
        // For long transcripts, select a strategic middle chunk
        // This avoids intro/outro and gets the main content
        
        // Calculate chunk boundaries
        $chunkSize = $maxChars;
        
        // Start from 20% into the transcript (skip intro)
        $startOffset = (int)($textLength * 0.2);
        
        // Don't go past 80% (skip outro)
        $maxEndOffset = (int)($textLength * 0.8);
        
        // Try multiple candidate chunks and pick the best one (least music/noise)
        $bestChunk = null;
        $bestScore = -1;
        $attempts = 3; // Try 3 different positions
        
        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            // Vary the start position for each attempt
            $attemptStart = $startOffset + (int)(($maxEndOffset - $startOffset - $chunkSize) * ($attempt / $attempts));
            $attemptEnd = min($attemptStart + $chunkSize, $maxEndOffset);
            
            $candidateChunk = substr($transcriptText, $attemptStart, $attemptEnd - $attemptStart);
            
            // Score this chunk (higher is better)
            $score = $this->scoreChunkQuality($candidateChunk);
            
            error_log("Chunk attempt #$attempt: score={$score}, start=" . round(($attemptStart/$textLength)*100) . "%");
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestChunk = $candidateChunk;
                $startOffset = $attemptStart;
            }
        }
        
        $chunk = $bestChunk;
        
        // Adjust to not cut mid-sentence - find nearest sentence boundary
        // Try to start at a sentence boundary (look for period + space + capital letter)
        if (preg_match('/\.\s+[A-Z]/', $chunk, $matches, PREG_OFFSET_CAPTURE)) {
            $firstSentenceStart = $matches[0][1] + 2; // +2 to skip ". "
            $chunk = substr($chunk, $firstSentenceStart);
            $startOffset += $firstSentenceStart;
        }
        
        // Try to end at a sentence boundary
        if (preg_match('/\.\s+[A-Z]/', strrev($chunk), $matches, PREG_OFFSET_CAPTURE)) {
            $lastSentenceEnd = strlen($chunk) - $matches[0][1];
            $chunk = substr($chunk, 0, $lastSentenceEnd);
        }
        
        // Ensure we still have a decent chunk
        if (strlen($chunk) < $minChars && $textLength > $minChars) {
            // Fallback: just take first $maxChars
            error_log("Chunk too small after boundary adjustment, using first {$maxChars} chars");
            return substr($transcriptText, 0, $maxChars);
        }
        
        $chunkLength = strlen($chunk);
        $startPercent = round(($startOffset / $textLength) * 100);
        $endPercent = round((($startOffset + $chunkLength) / $textLength) * 100);
        
        error_log("Selected chunk: {$chunkLength} chars from {$startPercent}% to {$endPercent}% of transcript (quality score: {$bestScore})");
        
        return $chunk;
    }
    
    /**
     * Score a chunk's quality for AI processing
     * Higher score = better chunk (more speech, less music/noise)
     */
    private function scoreChunkQuality($chunk) {
        $score = 100; // Start with perfect score
        
        // Penalize for music and sound effects markers
        $musicPatterns = [
            '/\[Music\]/i',
            '/\[Applause\]/i',
            '/\[Laughter\]/i',
            '/\[Singing\]/i',
            '/\[Instrumental\]/i',
            '/\[Background music\]/i',
            '/\[Sound effects\]/i',
            '/\[Noise\]/i'
        ];
        
        foreach ($musicPatterns as $pattern) {
            $matches = preg_match_all($pattern, $chunk);
            $score -= ($matches * 5); // -5 points per occurrence
        }
        
        // Penalize for excessive repetition (often indicates music lyrics)
        $words = str_word_count(strtolower($chunk), 1);
        if (count($words) > 0) {
            $uniqueWords = count(array_unique($words));
            $repetitionRatio = $uniqueWords / count($words);
            
            // If less than 40% unique words, likely repetitive content (lyrics)
            if ($repetitionRatio < 0.4) {
                $score -= 20;
            }
        }
        
        // Reward for sentence structure (indicates speech)
        $sentences = preg_match_all('/[.!?]\s+[A-Z]/', $chunk);
        $score += min($sentences * 2, 20); // +2 per sentence, max +20
        
        // Reward for question marks (indicates dialogue/teaching)
        $questions = substr_count($chunk, '?');
        $score += min($questions * 3, 15); // +3 per question, max +15
        
        return $score;
    }
    
    /**
     * Save clip suggestions to database (overwrites existing)
     */
    private function saveClipSuggestions($videoId, $userId, $suggestions) {
        try {
            // Delete existing suggestions for this video
            $stmt = $this->db->prepare(
                'DELETE FROM ai_clip_suggestions WHERE video_id = :video_id'
            );
            $stmt->execute(['video_id' => $videoId]);
            
            // Insert new suggestions
            $stmt = $this->db->prepare(
                'INSERT INTO ai_clip_suggestions (video_id, user_id, clip_suggestions) 
                 VALUES (:video_id, :user_id, :suggestions)'
            );
            
            return $stmt->execute([
                'video_id' => $videoId,
                'user_id' => $userId,
                'suggestions' => json_encode($suggestions)
            ]);
            
        } catch (Exception $e) {
            error_log('AI clip save error: ' . $e->getMessage());
            throw new Exception('Failed to save clip suggestions');
        }
    }
    
    /**
     * Get saved clip suggestions for a video
     */
    public function getClipSuggestions($videoId, $userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT clip_suggestions, generated_at 
                 FROM ai_clip_suggestions 
                 WHERE video_id = :video_id AND user_id = :user_id'
            );
            
            $stmt->execute([
                'video_id' => $videoId,
                'user_id' => $userId
            ]);
            
            $result = $stmt->fetch();
            
            if (!$result) {
                return null;
            }
            
            return [
                'suggestions' => json_decode($result['clip_suggestions'], true),
                'generated_at' => $result['generated_at']
            ];
            
        } catch (Exception $e) {
            error_log('AI clip get error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if video has saved clip suggestions
     */
    public function hasSuggestions($videoId, $userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) as count 
                 FROM ai_clip_suggestions 
                 WHERE video_id = :video_id AND user_id = :user_id'
            );
            
            $stmt->execute([
                'video_id' => $videoId,
                'user_id' => $userId
            ]);
            
            $result = $stmt->fetch();
            return $result && $result['count'] > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Locate a text quotation in the timestamped transcript data
     * Returns start and end timestamps in milliseconds
     */
    private function locateQuotationInTranscript($quotation, $rawData, $clipIndex = 0) {
        if (!isset($rawData['events']) || !is_array($rawData['events'])) {
            error_log("Clip #$clipIndex: No events array in rawData");
            return null;
        }
        
        // Normalize the quotation for matching (remove extra whitespace, case-insensitive)
        $normalizedQuotation = $this->normalizeText($quotation);
        $quotationWords = preg_split('/\s+/', $normalizedQuotation);
        $quotationWords = array_filter($quotationWords); // Remove empty strings
        
        if (empty($quotationWords)) {
            error_log("Clip #$clipIndex: Quotation normalized to empty");
            return null;
        }
        
        error_log("Clip #$clipIndex: Looking for " . count($quotationWords) . " words. First 5: " . implode(' ', array_slice($quotationWords, 0, 5)));
        
        // Build a searchable text array with timestamps
        $textSegments = [];
        $totalSegments = 0;
        foreach ($rawData['events'] as $event) {
            if (!isset($event['segs']) || !is_array($event['segs'])) {
                continue;
            }
            
            $eventStartMs = $event['tStartMs'] ?? 0;
            $eventDurationMs = $event['dDurationMs'] ?? 0;
            
            foreach ($event['segs'] as $segment) {
                if (!isset($segment['utf8'])) {
                    continue;
                }
                
                $text = $segment['utf8'];
                $totalSegments++;
                
                // Skip newlines and music markers for matching
                if ($text === "\n" || $text === "[Music]") {
                    continue;
                }
                
                $segmentOffsetMs = $segment['tOffsetMs'] ?? 0;
                $absoluteStartMs = $eventStartMs + $segmentOffsetMs;
                
                $textSegments[] = [
                    'text' => $text,
                    'normalized' => $this->normalizeText($text),
                    'segment_start_ms' => $absoluteStartMs,
                    'event_start_ms' => $eventStartMs,
                    'event_end_ms' => $eventStartMs + $eventDurationMs
                ];
            }
        }
        
        error_log("Clip #$clipIndex: Built " . count($textSegments) . " searchable segments from $totalSegments total segments");
        
        // Search for the quotation in the text segments
        $result = $this->findQuotationMatch($quotationWords, $textSegments, $clipIndex);
        
        if (!$result) {
            error_log("Clip #$clipIndex: No match found. First 10 segment texts: " . implode(' | ', array_slice(array_column($textSegments, 'text'), 0, 10)));
        }
        
        return $result;
    }
    
    /**
     * Verify AI's quotation against actual transcript and correct if needed
     * Returns the corrected quotation from the actual transcript
     */
    private function verifyAndCorrectQuotation($aiQuotation, $transcriptText, $clipIndex = 0) {
        error_log("Clip #$clipIndex: Verifying AI quotation...");
        
        // Normalize both texts for comparison
        $normalizedAI = $this->normalizeText($aiQuotation);
        $normalizedTranscript = $this->normalizeText($transcriptText);
        
        // Extract key phrases from AI quotation
        $aiWords = preg_split('/\s+/', $normalizedAI);
        $aiWords = array_filter($aiWords);
        
        if (count($aiWords) < 5) {
            error_log("Clip #$clipIndex: AI quotation too short for reliable matching");
            return null;
        }
        
        // Use more distinctive words from the middle of the quote for better matching
        // First 10 words, middle 10 words, and last 10 words
        $wordCount = count($aiWords);
        $startWords = array_slice($aiWords, 0, min(10, $wordCount));
        $middleStart = max(0, (int)floor($wordCount / 2) - 5);
        $middleWords = array_slice($aiWords, $middleStart, min(10, $wordCount - $middleStart));
        $endWords = array_slice($aiWords, -min(10, $wordCount));
        
        // Try to find start position using beginning words
        $startPos = $this->findFlexibleMatch($startWords, $normalizedTranscript);
        
        if ($startPos === false) {
            error_log("Clip #$clipIndex: Could not find start of quotation in transcript");
            return null;
        }
        
        // Verify the match by checking if middle words appear after start
        $middleSearchStart = $startPos + (strlen($normalizedAI) * 0.3); // Look 30% into expected quote
        $middlePos = $this->findFlexibleMatch($middleWords, $normalizedTranscript, (int)$middleSearchStart);
        
        if ($middlePos === false || $middlePos > $startPos + strlen($normalizedAI) * 2) {
            error_log("Clip #$clipIndex: Middle words not found in expected range, likely wrong match");
            return null;
        }
        
        // Look for end pattern after middle position
        $endSearchStart = max($middlePos, $startPos + (strlen($normalizedAI) * 0.5));
        $endPos = $this->findFlexibleMatch($endWords, $normalizedTranscript, (int)$endSearchStart);
        
        if ($endPos === false) {
            // Estimate end based on AI quote length
            $estimatedLength = strlen($normalizedAI) * 1.2; // Allow 20% variance
            $endPos = min($startPos + $estimatedLength, strlen($normalizedTranscript));
            error_log("Clip #$clipIndex: Could not find exact end, using estimated position");
        } else {
            // Add length of end words to get actual end position
            $endWordsLength = strlen(implode(' ', $endWords));
            $endPos += $endWordsLength;
        }
        
        // Extract the actual text from transcript
        $extractedLength = $endPos - $startPos;
        $extractedText = substr($normalizedTranscript, $startPos, $extractedLength);
        
        // Get the original (non-normalized) text from the transcript
        $correctedText = $this->extractOriginalText($transcriptText, $startPos, $extractedLength);
        
        // Calculate similarity score
        similar_text($normalizedAI, $extractedText, $similarityPercent);
        
        $wasCorrected = ($similarityPercent < 98);
        
        error_log("Clip #$clipIndex: Similarity: " . round($similarityPercent, 2) . "%, Corrected: " . ($wasCorrected ? 'yes' : 'no'));
        error_log("Clip #$clipIndex: AI quote (first 100 chars): " . substr($aiQuotation, 0, 100) . "...");
        error_log("Clip #$clipIndex: Extracted from DB (first 100 chars): " . substr($correctedText, 0, 100) . "...");
        
        if ($similarityPercent < 60) {
            error_log("Clip #$clipIndex: Similarity too low (" . round($similarityPercent, 2) . "%), rejecting match");
            return null;
        }
        
        return [
            'corrected_text' => trim($correctedText),
            'was_corrected' => $wasCorrected,
            'similarity_score' => round($similarityPercent, 2),
            'start_pos' => $startPos,
            'end_pos' => $endPos
        ];
    }
    
    /**
     * Find a flexible match for a sequence of words in text
     * Allows for some missing words or variations
     */
    private function findFlexibleMatch($searchWords, $text, $startOffset = 0) {
        $textWords = preg_split('/\s+/', substr($text, $startOffset));
        $textWords = array_filter($textWords);
        
        $minMatchRatio = 0.7; // At least 70% of search words must match
        $requiredMatches = max(3, (int)ceil(count($searchWords) * $minMatchRatio));
        
        // Sliding window search
        for ($i = 0; $i < count($textWords) - count($searchWords) + 1; $i++) {
            $matchCount = 0;
            $windowWords = array_slice($textWords, $i, count($searchWords) * 2); // Look in wider window
            
            foreach ($searchWords as $searchWord) {
                foreach ($windowWords as $windowWord) {
                    if ($this->wordsMatch($searchWord, $windowWord)) {
                        $matchCount++;
                        break; // Found this search word, move to next
                    }
                }
            }
            
            if ($matchCount >= $requiredMatches) {
                // Calculate approximate character position
                $charPos = 0;
                for ($j = 0; $j < $i; $j++) {
                    $charPos += strlen($textWords[$j]) + 1; // +1 for space
                }
                return $startOffset + $charPos;
            }
        }
        
        return false;
    }
    
    /**
     * Extract original text (with proper case/punctuation) from transcript
     */
    private function extractOriginalText($originalTranscript, $normalizedStartPos, $normalizedLength) {
        // This is an approximation since normalized text has different length
        // We need to map normalized position back to original
        
        $normalized = $this->normalizeText($originalTranscript);
        
        // Find the actual start position in original text
        $currentNormPos = 0;
        $currentOrigPos = 0;
        $origLength = strlen($originalTranscript);
        
        // Map normalized start position to original position
        while ($currentNormPos < $normalizedStartPos && $currentOrigPos < $origLength) {
            $origChar = $originalTranscript[$currentOrigPos];
            $normChar = $this->normalizeText($origChar);
            
            if (!empty($normChar)) {
                $currentNormPos++;
            }
            $currentOrigPos++;
        }
        
        $startPos = $currentOrigPos;
        
        // Map normalized length to original length
        $currentNormPos = 0;
        $currentOrigPos = $startPos;
        
        while ($currentNormPos < $normalizedLength && $currentOrigPos < $origLength) {
            $origChar = $originalTranscript[$currentOrigPos];
            $normChar = $this->normalizeText($origChar);
            
            if (!empty($normChar)) {
                $currentNormPos++;
            }
            $currentOrigPos++;
        }
        
        $length = $currentOrigPos - $startPos;
        
        return substr($originalTranscript, $startPos, $length);
    }
    
    /**
     * Normalize text for matching (lowercase, trim, remove extra spaces)
     */
    private function normalizeText($text) {
        $text = strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text);
        // Remove common punctuation for more flexible matching
        $text = preg_replace('/[.,!?;:"]/', '', $text);
        return $text;
    }
    
    /**
     * Find a quotation match in text segments using fuzzy matching
     */
    private function findQuotationMatch($quotationWords, $textSegments, $clipIndex = 0) {
        $minMatchThreshold = 0.7; // 70% of words must match (lowered for flexibility)
        $requiredMatches = max(1, (int)ceil(count($quotationWords) * $minMatchThreshold));
        
        error_log("Clip #$clipIndex: Need to match at least $requiredMatches out of " . count($quotationWords) . " words");
        
        // Try to find a sequence of segments that matches the quotation
        $bestMatch = null;
        $bestConfidence = 0;
        
        for ($i = 0; $i < count($textSegments); $i++) {
            $matchedWords = 0;
            $quotationIndex = 0;
            $startMs = null;
            $endMs = null;
            $firstMatchSegmentIndex = null;
            $skippedWords = 0;
            $maxSkips = (int)ceil(count($quotationWords) * 0.2); // Allow skipping 20% of words
            
            // Try to match starting from this segment
            for ($j = $i; $j < count($textSegments) && $quotationIndex < count($quotationWords); $j++) {
                $segmentWords = preg_split('/\s+/', $textSegments[$j]['normalized']);
                $segmentWords = array_filter($segmentWords); // Remove empty
                
                foreach ($segmentWords as $segmentWord) {
                    if ($quotationIndex >= count($quotationWords)) {
                        break;
                    }
                    
                    // Check if current quotation word matches (exact or partial)
                    if ($this->wordsMatch($quotationWords[$quotationIndex], $segmentWord)) {
                        if ($firstMatchSegmentIndex === null) {
                            // Track the segment where we found the first match
                            $firstMatchSegmentIndex = $j;
                            // Use the event's tStartMs for the clip start
                            $startMs = $textSegments[$j]['event_start_ms'];
                        }
                        // Keep updating end time as we match more words
                        $endMs = $textSegments[$j]['event_end_ms'];
                        $matchedWords++;
                        $quotationIndex++;
                    } else if ($matchedWords > 0) {
                        // Allow some flexibility - skip small words or try to skip ahead
                        if (strlen($segmentWord) <= 2 || $skippedWords < $maxSkips) {
                            $skippedWords++;
                            continue;
                        }
                        // Too many mismatches, might be wrong location
                        break;
                    }
                }
            }
            
            // Calculate confidence
            $confidence = $matchedWords / count($quotationWords);
            
            // Check if this is a good match
            if ($matchedWords >= $requiredMatches && $startMs !== null && $endMs !== null) {
                $durationMs = $endMs - $startMs;
                $durationSec = round($durationMs / 1000);
                
                // Reject matches with unreasonable duration (max 3 minutes for a clip)
                $maxDurationMs = 180000; // 3 minutes in milliseconds
                if ($durationMs > $maxDurationMs) {
                    error_log("Clip #$clipIndex: Rejecting match at segment $i - duration too long: {$durationSec}s (max 180s). Words are too scattered.");
                    continue; // Skip this match and keep searching
                }
                
                if ($confidence > $bestConfidence) {
                    error_log("Clip #$clipIndex: Found match at segment $i - confidence: " . round($confidence * 100) . "%, duration: {$durationSec}s, matched {$matchedWords}/" . count($quotationWords) . " words");
                    
                    // Extract matched text from segments for logging
                    $matchedTextParts = [];
                    for ($k = $firstMatchSegmentIndex; $k <= $j && $k < count($textSegments); $k++) {
                        $matchedTextParts[] = $textSegments[$k]['text'];
                        if (count($matchedTextParts) >= 20) break; // Limit to first 20 segments
                    }
                    $matchedTextPreview = implode('', $matchedTextParts);
                    error_log("Clip #$clipIndex: Matched text from raw: " . substr($matchedTextPreview, 0, 150) . "...");
                    
                    $bestMatch = [
                        'start_time_ms' => $startMs,
                        'end_time_ms' => $endMs,
                        'match_confidence' => $confidence,
                        'matched_text_preview' => substr($matchedTextPreview, 0, 200)
                    ];
                    $bestConfidence = $confidence;
                    
                    // If we have a perfect or near-perfect match with reasonable duration, use it
                    if ($confidence >= 0.95) {
                        error_log("Clip #$clipIndex: ✅ Found excellent match at segment $i with confidence " . round($confidence * 100) . "% and duration {$durationSec}s");
                        return $bestMatch;
                    }
                }
            }
        }
        
        if ($bestMatch) {
            $durationMs = $bestMatch['end_time_ms'] - $bestMatch['start_time_ms'];
            $durationSec = round($durationMs / 1000);
            $startSec = round($bestMatch['start_time_ms'] / 1000);
            $endSec = round($bestMatch['end_time_ms'] / 1000);
            error_log("Clip #$clipIndex: ✅ Found best match with confidence " . round($bestConfidence * 100) . "% - Start: {$bestMatch['start_time_ms']}ms ({$startSec}s), End: {$bestMatch['end_time_ms']}ms ({$endSec}s), Duration: {$durationMs}ms ({$durationSec}s)");
            error_log("Clip #$clipIndex: Best match text from raw: " . ($bestMatch['matched_text_preview'] ?? 'N/A'));
            return $bestMatch;
        }
        
        error_log("Clip #$clipIndex: No match found meeting threshold. Best confidence was " . round($bestConfidence * 100) . "%");
        
        return null;
    }
    
    /**
     * Check if two words match (exact or similar)
     */
    private function wordsMatch($word1, $word2) {
        // Exact match
        if ($word1 === $word2) {
            return true;
        }
        
        // Check if one contains the other (for partial matches)
        if (strlen($word1) >= 4 && strlen($word2) >= 4) {
            if (strpos($word1, $word2) !== false || strpos($word2, $word1) !== false) {
                return true;
            }
        }
        
        // Calculate similarity
        similar_text($word1, $word2, $percent);
        return $percent >= 85; // 85% similarity threshold
    }
}
