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
            
            // Parse transcript_raw JSON
            $rawData = is_string($transcriptRaw) ? json_decode($transcriptRaw, true) : $transcriptRaw;
            if (!$rawData) {
                error_log("Failed to parse transcript_raw JSON");
                throw new Exception('Invalid transcript raw data format');
            }
            
            // Process each suggestion to locate timestamps from quotations
            $processedSuggestions = [];
            foreach ($clipSuggestions as $index => $suggestion) {
                try {
                    // Expected format: suggestion should have 'quotation' field with exact text
                    if (!isset($suggestion['quotation']) || empty($suggestion['quotation'])) {
                        error_log("Clip suggestion #$index missing quotation field");
                        continue;
                    }
                    
                    $quotation = $suggestion['quotation'];
                    $timestamps = $this->locateQuotationInTranscript($quotation, $rawData);
                    
                    if ($timestamps) {
                        $processedSuggestions[] = [
                            'quotation' => $quotation,
                            'start_time_ms' => $timestamps['start_time_ms'],
                            'end_time_ms' => $timestamps['end_time_ms'],
                            'suggested_title' => $suggestion['suggested_title'] ?? 'Clip ' . (count($processedSuggestions) + 1),
                            'reason' => $suggestion['reason'] ?? 'AI-selected clip'
                        ];
                    } else {
                        error_log("Could not locate quotation in transcript: " . substr($quotation, 0, 100));
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
    private function locateQuotationInTranscript($quotation, $rawData) {
        if (!isset($rawData['events']) || !is_array($rawData['events'])) {
            return null;
        }
        
        // Normalize the quotation for matching (remove extra whitespace, case-insensitive)
        $normalizedQuotation = $this->normalizeText($quotation);
        $quotationWords = preg_split('/\s+/', $normalizedQuotation);
        
        if (empty($quotationWords)) {
            return null;
        }
        
        // Build a searchable text array with timestamps
        $textSegments = [];
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
                
                // Skip newlines and music markers for matching
                if ($text === "\n" || $text === "[Music]") {
                    continue;
                }
                
                $segmentOffsetMs = $segment['tOffsetMs'] ?? 0;
                $absoluteStartMs = $eventStartMs + $segmentOffsetMs;
                
                $textSegments[] = [
                    'text' => $text,
                    'normalized' => $this->normalizeText($text),
                    'start_ms' => $absoluteStartMs,
                    'event_end_ms' => $eventStartMs + $eventDurationMs
                ];
            }
        }
        
        // Search for the quotation in the text segments
        $result = $this->findQuotationMatch($quotationWords, $textSegments);
        
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
    private function findQuotationMatch($quotationWords, $textSegments) {
        $minMatchThreshold = 0.8; // 80% of words must match
        $requiredMatches = max(1, (int)ceil(count($quotationWords) * $minMatchThreshold));
        
        // Try to find a sequence of segments that matches the quotation
        for ($i = 0; $i < count($textSegments); $i++) {
            $matchedWords = 0;
            $quotationIndex = 0;
            $startMs = null;
            $endMs = null;
            
            // Try to match starting from this segment
            for ($j = $i; $j < count($textSegments) && $quotationIndex < count($quotationWords); $j++) {
                $segmentWords = preg_split('/\s+/', $textSegments[$j]['normalized']);
                
                foreach ($segmentWords as $segmentWord) {
                    if ($quotationIndex >= count($quotationWords)) {
                        break;
                    }
                    
                    // Check if current quotation word matches (exact or partial)
                    if ($this->wordsMatch($quotationWords[$quotationIndex], $segmentWord)) {
                        if ($startMs === null) {
                            $startMs = $textSegments[$j]['start_ms'];
                        }
                        $endMs = $textSegments[$j]['event_end_ms'];
                        $matchedWords++;
                        $quotationIndex++;
                    } else if ($matchedWords > 0) {
                        // Allow some flexibility - skip small words
                        if (strlen($segmentWord) <= 2) {
                            continue;
                        }
                        // If we've already started matching and hit a mismatch, might be wrong location
                        break;
                    }
                }
            }
            
            // Check if we found a good match
            if ($matchedWords >= $requiredMatches && $startMs !== null && $endMs !== null) {
                return [
                    'start_time_ms' => $startMs,
                    'end_time_ms' => $endMs,
                    'match_confidence' => $matchedWords / count($quotationWords)
                ];
            }
        }
        
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
