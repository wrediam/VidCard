<?php
require_once 'config.php';
require_once 'transcript.php';

class Video {
    private $db;
    private $transcriptService;
    
    public function __construct() {
        $this->db = getDB();
        $this->transcriptService = new Transcript();
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
     * Parse ISO 8601 duration format (e.g., PT4M13S) to seconds
     */
    private function parseISO8601Duration($duration) {
        $interval = new DateInterval($duration);
        return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
    }
    
    /**
     * Fetch video metadata from YouTube API
     */
    public function fetchVideoData($videoId) {
        $apiKey = YOUTUBE_API_KEY;
        if (!$apiKey || $apiKey === 'your_youtube_api_key_here') {
            throw new Exception('YouTube API key not configured. Please set YOUTUBE_API_KEY in environment variables.');
        }
        
        // Request both snippet and contentDetails to get duration
        $apiUrl = "https://www.googleapis.com/youtube/v3/videos?id=" . $videoId . "&key=" . $apiKey . "&part=snippet,contentDetails";
        
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
        $contentDetails = $data['items'][0]['contentDetails'] ?? null;
        
        // Extract duration from contentDetails (ISO 8601 format like PT4M13S)
        $duration = null;
        if ($contentDetails && isset($contentDetails['duration'])) {
            $duration = $this->parseISO8601Duration($contentDetails['duration']);
        }
        
        // Fetch channel details including handle
        $channelId = $videoData['channelId'];
        $channelUrl = "https://www.googleapis.com/youtube/v3/channels?id=" . $channelId . "&key=" . $apiKey . "&part=snippet,brandingSettings";
        
        curl_setopt($ch, CURLOPT_URL, $channelUrl);
        $channelResponse = curl_exec($ch);
        $channelData = json_decode($channelResponse, true);
        
        curl_close($ch);
        
        // Get channel thumbnail (prefer higher quality)
        $channelThumbnails = $channelData['items'][0]['snippet']['thumbnails'] ?? [];
        $channelThumbnail = $channelThumbnails['high']['url'] ?? $channelThumbnails['medium']['url'] ?? $channelThumbnails['default']['url'] ?? '';
        
        $channelHandle = $channelData['items'][0]['snippet']['customUrl'] ?? '';
        
        // Ensure handle starts with @
        if ($channelHandle && strpos($channelHandle, '@') !== 0) {
            $channelHandle = '@' . $channelHandle;
        }
        
        // Fallback to channel name if no handle
        if (empty($channelHandle)) {
            $channelHandle = '@' . str_replace(' ', '', $videoData['channelTitle']);
        }
        
        // Get video thumbnail URL
        $thumbnails = $videoData['thumbnails'];
        $thumbnailUrl = $thumbnails['maxres']['url'] ?? $thumbnails['high']['url'] ?? $thumbnails['medium']['url'] ?? '';
        
        return [
            'video_id' => $videoId,
            'title' => $videoData['title'],
            'description' => $videoData['description'],
            'thumbnail_url' => $thumbnailUrl,
            'channel_name' => $videoData['channelTitle'],
            'channel_url' => 'https://www.youtube.com/channel/' . $channelId,
            'channel_thumbnail' => $channelThumbnail,
            'channel_handle' => $channelHandle,
            'youtube_url' => 'https://www.youtube.com/watch?v=' . $videoId,
            'duration' => $duration
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
            'INSERT INTO videos (user_id, video_id, title, description, thumbnail_url, channel_name, channel_url, channel_thumbnail, channel_handle, youtube_url, duration) 
             VALUES (:user_id, :video_id, :title, :description, :thumbnail_url, :channel_name, :channel_url, :channel_thumbnail, :channel_handle, :youtube_url, :duration)'
        );
        
        $result = $stmt->execute([
            'user_id' => $userId,
            'video_id' => $videoData['video_id'],
            'title' => $videoData['title'],
            'description' => $videoData['description'],
            'thumbnail_url' => $videoData['thumbnail_url'],
            'channel_name' => $videoData['channel_name'],
            'channel_url' => $videoData['channel_url'],
            'channel_thumbnail' => $videoData['channel_thumbnail'],
            'channel_handle' => $videoData['channel_handle'] ?? '',
            'youtube_url' => $videoData['youtube_url'],
            'duration' => $videoData['duration'] ?? null
        ]);
        
        // Only fetch transcript and cache video if this is the FIRST time ANY user adds this video
        // (Transcripts and caching are video-specific, not user-specific)
        if ($result) {
            $isFirstTimeVideo = $this->isFirstTimeVideo($videoData['video_id'], $userId);
            
            if ($isFirstTimeVideo) {
                error_log("First time video {$videoData['video_id']} added - fetching transcript and caching");
                
                // Fetch transcript
                try {
                    $this->transcriptService->processTranscript(
                        $videoData['youtube_url'], 
                        $videoData['video_id']
                    );
                } catch (Exception $e) {
                    error_log('Transcript fetch failed for ' . $videoData['video_id'] . ': ' . $e->getMessage());
                }
                
                // Pre-cache video on vid.wredia.com
                try {
                    $this->cacheVideoOnServer($videoData['youtube_url']);
                } catch (Exception $e) {
                    error_log('Video cache request failed for ' . $videoData['video_id'] . ': ' . $e->getMessage());
                }
            } else {
                error_log("Video {$videoData['video_id']} already exists for another user - skipping transcript/cache");
            }
        }
        
        return $result;
    }
    
    /**
     * Check if this is the first time ANY user is adding this video
     * Used to avoid duplicate transcript fetching and caching
     */
    private function isFirstTimeVideo($videoId, $currentUserId) {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM videos WHERE video_id = :video_id AND user_id != :user_id'
        );
        $stmt->execute([
            'video_id' => $videoId,
            'user_id' => $currentUserId
        ]);
        
        $result = $stmt->fetch();
        // If count is 0, this is the first time (no other users have this video)
        return $result['count'] == 0;
    }
    
    /**
     * Request vid.wredia.com to pre-cache the video on their server
     * This is a fire-and-forget request - we don't wait for the download to complete
     */
    private function cacheVideoOnServer($youtubeUrl, $resolution = '1080p') {
        try {
            error_log("=== VIDEO CACHE REQUEST (async) ===");
            error_log("YouTube URL: $youtubeUrl");
            
            $cacheApiUrl = 'https://vid.wredia.com/download/cache';
            
            $payload = json_encode([
                'url' => $youtubeUrl,
                'resolution' => $resolution
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $cacheApiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Short timeout - just initiate the request
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Quick connection timeout
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-API-Key: ' . CAPTION_API_KEY
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // We only care that the request was initiated successfully
            if ($httpCode === 200) {
                error_log("Cache request initiated successfully");
                
                $data = json_decode($response, true);
                if ($data && isset($data['cached']) && $data['cached'] === true) {
                    error_log("Video already cached on server");
                } else {
                    error_log("Video caching started in background");
                }
            } else {
                error_log("Cache request failed: HTTP $httpCode" . ($curlError ? " - $curlError" : ""));
            }
            
            // Always return true - we don't want to fail video save if caching fails
            return true;
            
        } catch (Exception $e) {
            error_log('Video cache exception: ' . $e->getMessage());
            // Don't fail - caching is optional
            return true;
        }
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
