<?php
require_once 'config.php';

class ApiKey {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Generate a new API key for a user
     */
    public function generateKey($userId, $name, $rateLimit = 100) {
        // Generate secure API key (32 bytes = 64 hex chars)
        $key = 'vk_' . bin2hex(random_bytes(32));
        
        $stmt = $this->db->prepare(
            'INSERT INTO api_keys (user_id, key_name, api_key, rate_limit_per_hour, is_active) 
             VALUES (:user_id, :key_name, :api_key, :rate_limit, true) 
             RETURNING *'
        );
        
        $stmt->execute([
            'user_id' => $userId,
            'key_name' => $name,
            'api_key' => $key,
            'rate_limit' => $rateLimit
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * Validate API key and check if it's active
     */
    public function validateKey($apiKey) {
        $stmt = $this->db->prepare(
            'SELECT ak.*, u.email, u.is_active as user_active 
             FROM api_keys ak 
             JOIN users u ON ak.user_id = u.id 
             WHERE ak.api_key = :api_key 
             AND ak.is_active = true 
             AND u.is_active = true'
        );
        
        $stmt->execute(['api_key' => $apiKey]);
        $key = $stmt->fetch();
        
        if (!$key) {
            return null;
        }
        
        // Update last used timestamp
        $this->updateLastUsed($key['id']);
        
        return $key;
    }
    
    /**
     * Check rate limit for API key
     */
    public function checkRateLimit($apiKeyId, $rateLimit) {
        // Count requests in the last hour
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as request_count 
             FROM api_requests 
             WHERE api_key_id = :api_key_id 
             AND created_at > NOW() - INTERVAL \'1 hour\''
        );
        
        $stmt->execute(['api_key_id' => $apiKeyId]);
        $result = $stmt->fetch();
        
        $currentCount = $result['request_count'] ?? 0;
        
        return [
            'allowed' => $currentCount < $rateLimit,
            'current' => $currentCount,
            'limit' => $rateLimit,
            'remaining' => max(0, $rateLimit - $currentCount),
            'reset_at' => date('Y-m-d H:i:s', strtotime('+1 hour', strtotime('-1 hour')))
        ];
    }
    
    /**
     * Log API request
     */
    public function logRequest($apiKeyId, $endpoint, $method, $ipAddress, $statusCode, $responseTime = null) {
        $stmt = $this->db->prepare(
            'INSERT INTO api_requests (api_key_id, endpoint, method, ip_address, status_code, response_time) 
             VALUES (:api_key_id, :endpoint, :method, :ip_address, :status_code, :response_time)'
        );
        
        return $stmt->execute([
            'api_key_id' => $apiKeyId,
            'endpoint' => $endpoint,
            'method' => $method,
            'ip_address' => $ipAddress,
            'status_code' => $statusCode,
            'response_time' => $responseTime
        ]);
    }
    
    /**
     * Get all API keys for a user
     */
    public function getUserKeys($userId) {
        $stmt = $this->db->prepare(
            'SELECT ak.*, 
                    COUNT(DISTINCT ar.id) as total_requests,
                    COUNT(DISTINCT CASE WHEN ar.created_at > NOW() - INTERVAL \'1 hour\' THEN ar.id END) as requests_last_hour
             FROM api_keys ak
             LEFT JOIN api_requests ar ON ak.id = ar.api_key_id
             WHERE ak.user_id = :user_id
             GROUP BY ak.id
             ORDER BY ak.created_at DESC'
        );
        
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Revoke (deactivate) an API key
     */
    public function revokeKey($apiKeyId, $userId) {
        $stmt = $this->db->prepare(
            'UPDATE api_keys 
             SET is_active = false 
             WHERE id = :id AND user_id = :user_id'
        );
        
        return $stmt->execute([
            'id' => $apiKeyId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Delete an API key permanently
     */
    public function deleteKey($apiKeyId, $userId) {
        $stmt = $this->db->prepare(
            'DELETE FROM api_keys 
             WHERE id = :id AND user_id = :user_id'
        );
        
        return $stmt->execute([
            'id' => $apiKeyId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Update last used timestamp
     */
    private function updateLastUsed($apiKeyId) {
        $stmt = $this->db->prepare(
            'UPDATE api_keys SET last_used_at = NOW() WHERE id = :id'
        );
        
        $stmt->execute(['id' => $apiKeyId]);
    }
    
    /**
     * Get API key statistics
     */
    public function getKeyStats($apiKeyId, $userId) {
        // Verify ownership
        $stmt = $this->db->prepare(
            'SELECT * FROM api_keys WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute(['id' => $apiKeyId, 'user_id' => $userId]);
        
        if (!$stmt->fetch()) {
            return null;
        }
        
        // Get request statistics
        $stmt = $this->db->prepare(
            'SELECT 
                COUNT(*) as total_requests,
                COUNT(DISTINCT DATE(created_at)) as active_days,
                AVG(response_time) as avg_response_time,
                COUNT(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 END) as successful_requests,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as failed_requests,
                MAX(created_at) as last_request_at
             FROM api_requests 
             WHERE api_key_id = :api_key_id'
        );
        
        $stmt->execute(['api_key_id' => $apiKeyId]);
        $stats = $stmt->fetch();
        
        // Get requests by endpoint
        $stmt = $this->db->prepare(
            'SELECT endpoint, COUNT(*) as count 
             FROM api_requests 
             WHERE api_key_id = :api_key_id 
             GROUP BY endpoint 
             ORDER BY count DESC 
             LIMIT 10'
        );
        
        $stmt->execute(['api_key_id' => $apiKeyId]);
        $stats['top_endpoints'] = $stmt->fetchAll();
        
        // Get recent requests
        $stmt = $this->db->prepare(
            'SELECT * FROM api_requests 
             WHERE api_key_id = :api_key_id 
             ORDER BY created_at DESC 
             LIMIT 20'
        );
        
        $stmt->execute(['api_key_id' => $apiKeyId]);
        $stats['recent_requests'] = $stmt->fetchAll();
        
        return $stats;
    }
    
    /**
     * Update API key settings
     */
    public function updateKey($apiKeyId, $userId, $name = null, $rateLimit = null) {
        $updates = [];
        $params = ['id' => $apiKeyId, 'user_id' => $userId];
        
        if ($name !== null) {
            $updates[] = 'key_name = :name';
            $params['name'] = $name;
        }
        
        if ($rateLimit !== null) {
            $updates[] = 'rate_limit_per_hour = :rate_limit';
            $params['rate_limit'] = $rateLimit;
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = 'UPDATE api_keys SET ' . implode(', ', $updates) . ' WHERE id = :id AND user_id = :user_id';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
}
