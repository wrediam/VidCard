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
            // Send clean transcript text to n8n webhook (not timestamped version)
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $transcriptText);
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
                    $timestamps = $this->locateQuotationInTranscript($quotation, $rawData, $index);
                    
                    if ($timestamps) {
                        $processedSuggestions[] = [
                            'quotation' => $quotation,
                            'start_time_ms' => $timestamps['start_time_ms'],
                            'end_time_ms' => $timestamps['end_time_ms'],
                            'suggested_title' => $suggestion['suggested_title'] ?? 'Clip ' . (count($processedSuggestions) + 1),
                            'reason' => $suggestion['reason'] ?? 'AI-selected clip',
                            'match_confidence' => $timestamps['match_confidence'] ?? 1.0
                        ];
                        error_log("Successfully located clip #$index: {$timestamps['start_time_ms']}ms - {$timestamps['end_time_ms']}ms (confidence: " . round($timestamps['match_confidence'] * 100) . "%)");
                    } else {
                        error_log("Could not locate quotation #$index in transcript. Quotation: " . substr($quotation, 0, 200));
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
                
                if ($confidence > $bestConfidence) {
                    error_log("Clip #$clipIndex: Found match at segment $i - confidence: " . round($confidence * 100) . "%, duration: {$durationSec}s, matched {$matchedWords}/" . count($quotationWords) . " words");
                    
                    $bestMatch = [
                        'start_time_ms' => $startMs,
                        'end_time_ms' => $endMs,
                        'match_confidence' => $confidence
                    ];
                    $bestConfidence = $confidence;
                    
                    // If we have a perfect or near-perfect match, use it
                    if ($confidence >= 0.95) {
                        error_log("Clip #$clipIndex: Found excellent match at segment $i with confidence " . round($confidence * 100) . "%");
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
            error_log("Clip #$clipIndex: Found best match with confidence " . round($bestConfidence * 100) . "% - Start: {$bestMatch['start_time_ms']}ms ({$startSec}s), End: {$bestMatch['end_time_ms']}ms ({$endSec}s), Duration: {$durationMs}ms ({$durationSec}s)");
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
