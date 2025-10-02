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
                'X-API-Key: WrediaAPI_2025_9f8e7d6c5b4a3210fedcba0987654321abcdef1234567890bcda1ef2a3b4c5d6e7f8g9h0i1j2k3l4m5n6o7p8q9r0s1t2u3v4w5x6y7z8'
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
                     transcript_fetched_at = NOW()
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
     * Get transcript for a video
     */
    public function getTranscript($videoId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT transcript_raw, transcript_text, transcript_fetched_at 
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
            return false;
        }
        
        $cleanText = $this->extractCleanText($captionData);
        
        if (empty($cleanText)) {
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
