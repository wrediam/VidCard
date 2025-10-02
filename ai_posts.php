<?php
require_once 'config.php';

class AIPosts {
    private $db;
    private $webhookUrl;
    
    public function __construct() {
        $this->db = getDB();
        $this->webhookUrl = N8N_WEBHOOK_URL;
    }
    
    /**
     * Generate post suggestions via n8n webhook
     */
    public function generatePostSuggestions($transcriptText, $videoId, $userId) {
        if (empty($this->webhookUrl)) {
            throw new Exception('n8n webhook URL not configured');
        }
        
        if (empty($transcriptText)) {
            throw new Exception('Transcript text is required');
        }
        
        try {
            // Send transcript to n8n webhook
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $transcriptText);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: text/plain'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                error_log("n8n webhook error: HTTP $httpCode - Response: " . substr($response, 0, 500));
                throw new Exception('Failed to generate post suggestions from n8n webhook');
            }
            
            $data = json_decode($response, true);
            
            if (!$data || !isset($data[0]['output']['post_suggestions'])) {
                error_log("n8n webhook invalid response: " . substr($response, 0, 500));
                throw new Exception('Invalid response format from n8n webhook');
            }
            
            $postSuggestions = $data[0]['output']['post_suggestions'];
            
            // Validate we have 5 suggestions
            if (count($postSuggestions) !== 5) {
                error_log("n8n webhook returned " . count($postSuggestions) . " suggestions instead of 5");
            }
            
            // Save to database (will overwrite existing suggestions for this video)
            $this->savePostSuggestions($videoId, $userId, $postSuggestions);
            
            return $postSuggestions;
            
        } catch (Exception $e) {
            error_log('AI post generation error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Save post suggestions to database (overwrites existing)
     */
    private function savePostSuggestions($videoId, $userId, $suggestions) {
        try {
            // Delete existing suggestions for this video
            $stmt = $this->db->prepare(
                'DELETE FROM ai_post_suggestions WHERE video_id = :video_id'
            );
            $stmt->execute(['video_id' => $videoId]);
            
            // Insert new suggestions
            $stmt = $this->db->prepare(
                'INSERT INTO ai_post_suggestions (video_id, user_id, post_suggestions) 
                 VALUES (:video_id, :user_id, :suggestions)'
            );
            
            return $stmt->execute([
                'video_id' => $videoId,
                'user_id' => $userId,
                'suggestions' => json_encode($suggestions)
            ]);
            
        } catch (Exception $e) {
            error_log('AI post save error: ' . $e->getMessage());
            throw new Exception('Failed to save post suggestions');
        }
    }
    
    /**
     * Get saved post suggestions for a video
     */
    public function getPostSuggestions($videoId, $userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT post_suggestions, generated_at 
                 FROM ai_post_suggestions 
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
                'suggestions' => json_decode($result['post_suggestions'], true),
                'generated_at' => $result['generated_at']
            ];
            
        } catch (Exception $e) {
            error_log('AI post get error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if video has saved post suggestions
     */
    public function hasSuggestions($videoId, $userId) {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) as count 
                 FROM ai_post_suggestions 
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
}
