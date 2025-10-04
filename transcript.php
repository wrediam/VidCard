<?php
require_once 'config.php';

class Transcript {
    private $db;
    private $captionApiUrl = 'https://vid.wredia.com/captions/en';
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Fetch transcript from caption API
     */
    public function fetchTranscript($youtubeUrl, $videoId) {
        try {
            error_log("=== TRANSCRIPT FETCH START for video: $videoId ===");
            error_log("YouTube URL: $youtubeUrl");
            
            // Check if API key is set
            if (!defined('CAPTION_API_KEY') || empty(CAPTION_API_KEY)) {
                error_log("ERROR: CAPTION_API_KEY is not defined or empty!");
                error_log("Please set CAPTION_API_KEY in your .env file");
                return null;
            }
            
            error_log("API Key is set (length: " . strlen(CAPTION_API_KEY) . ")");
            
            $url = $this->captionApiUrl . '?url=' . urlencode($youtubeUrl) . '&format=raw';
            error_log("Caption API URL: $url");
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'X-API-Key: ' . CAPTION_API_KEY
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            error_log("HTTP Code: $httpCode");
            if ($curlError) {
                error_log("CURL Error: $curlError");
            }
            
            if ($httpCode !== 200 || !$response) {
                error_log("Failed: HTTP code $httpCode or empty response");
                error_log("Response preview: " . substr($response, 0, 200));
                return null;
            }
            
            error_log("Response received, length: " . strlen($response));
            error_log("Response preview: " . substr($response, 0, 200));
            
            $data = json_decode($response, true);
            
            if (!$data) {
                error_log("Failed to decode JSON response");
                error_log("JSON error: " . json_last_error_msg());
                return null;
            }
            
            if (!isset($data['data'])) {
                error_log("No 'data' field in response");
                error_log("Response keys: " . implode(', ', array_keys($data)));
                return null;
            }
            
            error_log("Data field found, attempting to parse nested JSON");
            
            // Parse the nested JSON in data field
            $captionData = json_decode($data['data'], true);
            
            if (!$captionData) {
                error_log("Failed to decode nested JSON in data field");
                error_log("Nested JSON error: " . json_last_error_msg());
                error_log("Data field preview: " . substr($data['data'], 0, 200));
                return null;
            }
            
            if (!isset($captionData['events'])) {
                error_log("No 'events' field in caption data");
                error_log("Caption data keys: " . implode(', ', array_keys($captionData)));
                return null;
            }
            
            $eventCount = count($captionData['events']);
            error_log("SUCCESS: Found $eventCount caption events");
            error_log("=== TRANSCRIPT FETCH END ===");
            
            return $captionData;
            
        } catch (Exception $e) {
            error_log('Transcript fetch error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }
    
    /**
     * Extract clean text from raw caption data
     */
    public function extractCleanText($captionData) {
        if (!$captionData || !isset($captionData['events'])) {
            return '';
        }
        
        $textParts = [];
        $currentLine = '';
        
        foreach ($captionData['events'] as $event) {
            if (!isset($event['segs'])) {
                continue;
            }
            
            foreach ($event['segs'] as $segment) {
                if (isset($segment['utf8'])) {
                    $text = $segment['utf8'];
                    
                    // Handle newlines
                    if ($text === "\n") {
                        if (!empty($currentLine)) {
                            $textParts[] = trim($currentLine);
                            $currentLine = '';
                        }
                    } else {
                        $currentLine .= $text;
                    }
                }
            }
        }
        
        // Add any remaining text
        if (!empty($currentLine)) {
            $textParts[] = trim($currentLine);
        }
        
        // Join with newlines and clean up
        $fullText = implode("\n", $textParts);
        
        // Remove duplicate newlines
        $fullText = preg_replace("/\n{3,}/", "\n\n", $fullText);
        
        return trim($fullText);
    }
    
    /**
     * Extract timestamped text from raw caption data
     */
    public function extractTimestampedText($captionData) {
        if (!$captionData || !isset($captionData['events'])) {
            return '';
        }
        
        $lines = [];
        
        foreach ($captionData['events'] as $event) {
            if (!isset($event['segs']) || !isset($event['tStartMs'])) {
                continue;
            }
            
            // Convert milliseconds to readable timestamp (MM:SS)
            $totalSeconds = floor($event['tStartMs'] / 1000);
            $minutes = floor($totalSeconds / 60);
            $seconds = $totalSeconds % 60;
            $timestamp = sprintf('[%02d:%02d]', $minutes, $seconds);
            
            // Collect text from segments
            $lineText = '';
            foreach ($event['segs'] as $segment) {
                if (isset($segment['utf8']) && $segment['utf8'] !== "\n") {
                    $lineText .= $segment['utf8'];
                }
            }
            
            // Add line with timestamp if there's text
            $lineText = trim($lineText);
            if (!empty($lineText)) {
                $lines[] = $timestamp . ' ' . $lineText;
            }
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Save transcript to database
     */
    public function saveTranscript($videoId, $captionData, $cleanText) {
        try {
            $stmt = $this->db->prepare(
                'UPDATE videos 
                 SET transcript_raw = :raw, 
                     transcript_text = :text,
                     transcript_fetched_at = NOW(),
                     transcript_unavailable = FALSE
                 WHERE video_id = :video_id'
            );
            
            return $stmt->execute([
                'raw' => json_encode($captionData),
                'text' => $cleanText,
                'video_id' => $videoId
            ]);
            
        } catch (Exception $e) {
            error_log('Transcript save error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark transcript as unavailable
     */
    public function markUnavailable($videoId) {
        try {
            $stmt = $this->db->prepare(
                'UPDATE videos 
                 SET transcript_unavailable = TRUE,
                     transcript_fetched_at = NOW()
                 WHERE video_id = :video_id'
            );
            
            return $stmt->execute(['video_id' => $videoId]);
            
        } catch (Exception $e) {
            error_log('Transcript mark unavailable error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get transcript for a video
     */
    public function getTranscript($videoId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT transcript_raw, transcript_text, transcript_fetched_at, transcript_unavailable 
                 FROM videos 
                 WHERE video_id = :video_id'
            );
            
            $stmt->execute(['video_id' => $videoId]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log('Transcript get error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Process and save transcript for a video
     */
    public function processTranscript($youtubeUrl, $videoId) {
        error_log("=== PROCESS TRANSCRIPT START for video: $videoId ===");
        error_log("YouTube URL: $youtubeUrl");
        
        $captionData = $this->fetchTranscript($youtubeUrl, $videoId);
        
        if (!$captionData) {
            error_log("Caption data is null, marking transcript as unavailable");
            // Mark as unavailable so we don't keep trying
            $this->markUnavailable($videoId);
            error_log("=== PROCESS TRANSCRIPT END (unavailable) ===");
            return false;
        }
        
        error_log("Caption data received, extracting clean text");
        $cleanText = $this->extractCleanText($captionData);
        
        if (empty($cleanText)) {
            error_log("Clean text is empty, marking transcript as unavailable");
            // Mark as unavailable if extraction failed
            $this->markUnavailable($videoId);
            error_log("=== PROCESS TRANSCRIPT END (empty text) ===");
            return false;
        }
        
        error_log("Clean text extracted, length: " . strlen($cleanText));
        error_log("Text preview: " . substr($cleanText, 0, 100));
        
        $result = $this->saveTranscript($videoId, $captionData, $cleanText);
        error_log("Save result: " . ($result ? 'SUCCESS' : 'FAILED'));
        error_log("=== PROCESS TRANSCRIPT END ===");
        
        return $result;
    }
    
    /**
     * Check if video has transcript
     */
    public function hasTranscript($videoId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT transcript_text IS NOT NULL as has_transcript 
                 FROM videos 
                 WHERE video_id = :video_id'
            );
            
            $stmt->execute(['video_id' => $videoId]);
            $result = $stmt->fetch();
            
            return $result ? (bool)$result['has_transcript'] : false;
            
        } catch (Exception $e) {
            return false;
        }
    }
}
