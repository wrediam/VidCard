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
    public function generateClipSuggestions($transcriptText, $videoId, $userId) {
        if (empty($this->webhookUrl)) {
            throw new Exception('n8n clip webhook URL not configured');
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
                error_log("n8n clip webhook error: HTTP $httpCode - Response: " . substr($response, 0, 500));
                throw new Exception('Failed to generate clip suggestions from n8n webhook');
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
            
            // Save to database (will overwrite existing suggestions for this video)
            $this->saveClipSuggestions($videoId, $userId, $clipSuggestions);
            
            return $clipSuggestions;
            
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
}
