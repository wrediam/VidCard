<?php
require_once 'config.php';

class Video {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Extract YouTube video ID from URL
     * Supports: youtube.com/watch, youtu.be, youtube.com/shorts, youtube.com/embed
     */
    public function extractYoutubeId($url) {
        // Pattern to match various YouTube URL formats including Shorts
        $patterns = [
            '/(?:youtube\.com\/watch\?v=)([^"&?\/\s]{11})/i',           // youtube.com/watch?v=ID
            '/(?:youtube\.com\/shorts\/)([^"&?\/\s]{11})/i',            // youtube.com/shorts/ID
            '/(?:youtu\.be\/)([^"&?\/\s]{11})/i',                       // youtu.be/ID
            '/(?:youtube\.com\/embed\/)([^"&?\/\s]{11})/i',             // youtube.com/embed/ID
            '/(?:youtube\.com\/v\/)([^"&?\/\s]{11})/i',                 // youtube.com/v/ID
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $match)) {
                return $match[1];
            }
        }
        
        return false;
    }
    
    /**
     * Fetch video metadata from YouTube API
     */
    public function fetchVideoData($videoId) {
        $apiKey = YOUTUBE_API_KEY;
        if (!$apiKey || $apiKey === 'your_youtube_api_key_here') {
            throw new Exception('YouTube API key not configured. Please set YOUTUBE_API_KEY in environment variables.');
        }
        
        $apiUrl = "https://www.googleapis.com/youtube/v3/videos?id=" . $videoId . "&key=" . $apiKey . "&part=snippet";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; VidCard/1.0)');
        curl_setopt($ch, CURLOPT_REFERER, APP_URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Origin: ' . APP_URL
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 200) {
            error_log("YouTube API Error: HTTP $httpCode - Response: " . substr($response, 0, 500));
            curl_close($ch);
            
            // Parse error message if available
            $errorData = json_decode($response, true);
            if (isset($errorData['error']['message'])) {
                throw new Exception('YouTube API Error: ' . $errorData['error']['message']);
            }
            
            throw new Exception('Failed to fetch video metadata (HTTP ' . $httpCode . ')');
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data['items'])) {
            error_log("YouTube API Invalid Response: " . substr($response, 0, 500));
            curl_close($ch);
            throw new Exception('Invalid video data received from YouTube API');
        }
        
        $videoData = $data['items'][0]['snippet'];
        
        // Fetch channel details
        $channelId = $videoData['channelId'];
        $channelUrl = "https://www.googleapis.com/youtube/v3/channels?id=" . $channelId . "&key=" . $apiKey . "&part=snippet";
        
        curl_setopt($ch, CURLOPT_URL, $channelUrl);
        $channelResponse = curl_exec($ch);
        $channelData = json_decode($channelResponse, true);
        
        curl_close($ch);
        
        $channelThumbnail = $channelData['items'][0]['snippet']['thumbnails']['default']['url'] ?? '';
        
        return [
            'video_id' => $videoId,
            'title' => $videoData['title'],
            'description' => $videoData['description'],
            'thumbnail_url' => $videoData['thumbnails']['maxres']['url'] ?? $videoData['thumbnails']['high']['url'] ?? $videoData['thumbnails']['default']['url'],
            'channel_name' => $videoData['channelTitle'],
            'channel_url' => "https://www.youtube.com/channel/" . $channelId,
            'channel_thumbnail' => $channelThumbnail,
            'youtube_url' => "https://www.youtube.com/watch?v=" . $videoId
        ];
    }
    
    /**
     * Save video to database
     */
    public function saveVideo($userId, $videoData) {
        // Check if video already exists for this user
        $stmt = $this->db->prepare(
            'SELECT id FROM videos WHERE user_id = :user_id AND video_id = :video_id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'video_id' => $videoData['video_id']
        ]);
        
        if ($stmt->fetch()) {
            return true; // Already exists
        }
        
        // Insert new video
        $stmt = $this->db->prepare(
            'INSERT INTO videos (user_id, video_id, title, description, thumbnail_url, channel_name, channel_url, channel_thumbnail, youtube_url) 
             VALUES (:user_id, :video_id, :title, :description, :thumbnail_url, :channel_name, :channel_url, :channel_thumbnail, :youtube_url)'
        );
        
        return $stmt->execute([
            'user_id' => $userId,
            'video_id' => $videoData['video_id'],
            'title' => $videoData['title'],
            'description' => $videoData['description'],
            'thumbnail_url' => $videoData['thumbnail_url'],
            'channel_name' => $videoData['channel_name'],
            'channel_url' => $videoData['channel_url'],
            'channel_thumbnail' => $videoData['channel_thumbnail'],
            'youtube_url' => $videoData['youtube_url']
        ]);
    }
    
    /**
     * Get video by video_id
     */
    public function getVideoByVideoId($videoId) {
        $stmt = $this->db->prepare('SELECT * FROM videos WHERE video_id = :video_id LIMIT 1');
        $stmt->execute(['video_id' => $videoId]);
        return $stmt->fetch();
    }
    
    /**
     * Get all videos for a user
     */
    public function getUserVideos($userId) {
        $stmt = $this->db->prepare(
            'SELECT v.*, COUNT(vv.id) as visit_count 
             FROM videos v 
             LEFT JOIN video_visits vv ON v.id = vv.video_id 
             WHERE v.user_id = :user_id 
             GROUP BY v.id 
             ORDER BY v.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get videos grouped by channel for a user
     */
    public function getUserVideosByChannel($userId) {
        $videos = $this->getUserVideos($userId);
        $channels = [];
        
        foreach ($videos as $video) {
            $channelName = $video['channel_name'];
            if (!isset($channels[$channelName])) {
                $channels[$channelName] = [
                    'name' => $channelName,
                    'thumbnail' => $video['channel_thumbnail'],
                    'url' => $video['channel_url'],
                    'videos' => []
                ];
            }
            $channels[$channelName]['videos'][] = $video;
        }
        
        return $channels;
    }
    
    /**
     * Record video visit
     */
    public function recordVisit($videoId, $ipAddress = null, $referrer = null, $userAgent = null) {
        // Get video database ID
        $video = $this->getVideoByVideoId($videoId);
        if (!$video) {
            return false;
        }
        
        $stmt = $this->db->prepare(
            'INSERT INTO video_visits (video_id, ip_address, referrer, user_agent) 
             VALUES (:video_id, :ip_address, :referrer, :user_agent)'
        );
        
        return $stmt->execute([
            'video_id' => $video['id'],
            'ip_address' => $ipAddress,
            'referrer' => $referrer,
            'user_agent' => $userAgent
        ]);
    }
    
    /**
     * Get video statistics
     */
    public function getVideoStats($videoId) {
        $video = $this->getVideoByVideoId($videoId);
        if (!$video) {
            return null;
        }
        
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as total_visits, 
                    MAX(visited_at) as last_visit,
                    MIN(visited_at) as first_visit
             FROM video_visits 
             WHERE video_id = :video_id'
        );
        $stmt->execute(['video_id' => $video['id']]);
        $stats = $stmt->fetch();
        
        // Get recent visits
        $stmt = $this->db->prepare(
            'SELECT visited_at, ip_address, referrer 
             FROM video_visits 
             WHERE video_id = :video_id 
             ORDER BY visited_at DESC 
             LIMIT 10'
        );
        $stmt->execute(['video_id' => $video['id']]);
        $recentVisits = $stmt->fetchAll();
        
        return [
            'total_visits' => $stats['total_visits'],
            'last_visit' => $stats['last_visit'],
            'first_visit' => $stats['first_visit'],
            'recent_visits' => $recentVisits
        ];
    }
    
    /**
     * Search videos for a user
     */
    public function searchUserVideos($userId, $query) {
        $stmt = $this->db->prepare(
            'SELECT * FROM videos 
             WHERE user_id = :user_id 
             AND (title ILIKE :query OR channel_name ILIKE :query OR description ILIKE :query)
             ORDER BY created_at DESC'
        );
        
        $stmt->execute([
            'user_id' => $userId,
            'query' => '%' . $query . '%'
        ]);
        
        return $stmt->fetchAll();
    }
}
