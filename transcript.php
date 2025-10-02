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
            $url = $this->captionApiUrl . '?url=' . urlencode($youtubeUrl) . '&format=raw';
            
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
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['data'])) {
                return null;
            }
            
            // Parse the nested JSON in data field
            $captionData = json_decode($data['data'], true);
            
            if (!$captionData || !isset($captionData['events'])) {
                return null;
            }
            
            return $captionData;
            
        } catch (Exception $e) {
            error_log('Transcript fetch error: ' . $e->getMessage());
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
        $captionData = $this->fetchTranscript($youtubeUrl, $videoId);
        
        if (!$captionData) {
            // Mark as unavailable so we don't keep trying
            $this->markUnavailable($videoId);
            return false;
        }
        
        $cleanText = $this->extractCleanText($captionData);
        
        if (empty($cleanText)) {
            // Mark as unavailable if extraction failed
            $this->markUnavailable($videoId);
            return false;
        }
        
        return $this->saveTranscript($videoId, $captionData, $cleanText);
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
