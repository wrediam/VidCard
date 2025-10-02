<?php
require_once 'config.php';
require_once 'api_key.php';
require_once 'video.php';

/**
 * VidCard API v1
 * RESTful API with API key authentication and rate limiting
 */

header('Content-Type: application/json');

// CORS headers (adjust origins as needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$apiKeyService = new ApiKey();
$videoService = new Video();

// Get API key from header
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;

if (!$apiKey) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Unauthorized',
        'message' => 'API key is required. Include X-API-Key header.'
    ]);
    exit;
}

// Validate API key
$startTime = microtime(true);
$keyData = $apiKeyService->validateKey($apiKey);

if (!$keyData) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Unauthorized',
        'message' => 'Invalid or inactive API key'
    ]);
    exit;
}

// Check rate limit
$rateLimitCheck = $apiKeyService->checkRateLimit($keyData['id'], $keyData['rate_limit_per_hour']);

// Add rate limit headers
header('X-RateLimit-Limit: ' . $rateLimitCheck['limit']);
header('X-RateLimit-Remaining: ' . $rateLimitCheck['remaining']);
header('X-RateLimit-Reset: ' . strtotime($rateLimitCheck['reset_at']));

if (!$rateLimitCheck['allowed']) {
    http_response_code(429);
    echo json_encode([
        'error' => 'Rate Limit Exceeded',
        'message' => 'You have exceeded your rate limit',
        'rate_limit' => $rateLimitCheck
    ]);
    
    // Log the request
    $apiKeyService->logRequest(
        $keyData['id'],
        $_SERVER['REQUEST_URI'],
        $_SERVER['REQUEST_METHOD'],
        $_SERVER['REMOTE_ADDR'],
        429
    );
    
    exit;
}

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/v1', '', $path); // Remove base path
$pathParts = array_filter(explode('/', $path));
$pathParts = array_values($pathParts); // Re-index

$input = null;
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    $input = json_decode(file_get_contents('php://input'), true);
}

$statusCode = 200;
$response = null;

try {
    // Route: GET /videos - List user's videos
    if ($method === 'GET' && count($pathParts) === 1 && $pathParts[0] === 'videos') {
        $videos = $videoService->getUserVideos($keyData['user_id']);
        $response = [
            'success' => true,
            'data' => $videos,
            'count' => count($videos)
        ];
    }
    
    // Route: GET /videos/{video_id} - Get specific video
    elseif ($method === 'GET' && count($pathParts) === 2 && $pathParts[0] === 'videos') {
        $videoId = $pathParts[1];
        $video = $videoService->getVideoByVideoId($videoId);
        
        if (!$video) {
            $statusCode = 404;
            $response = [
                'error' => 'Not Found',
                'message' => 'Video not found'
            ];
        } elseif ($video['user_id'] != $keyData['user_id']) {
            $statusCode = 403;
            $response = [
                'error' => 'Forbidden',
                'message' => 'You do not have access to this video'
            ];
        } else {
            $response = [
                'success' => true,
                'data' => $video
            ];
        }
    }
    
    // Route: POST /videos - Process a new video
    elseif ($method === 'POST' && count($pathParts) === 1 && $pathParts[0] === 'videos') {
        if (!isset($input['url'])) {
            $statusCode = 400;
            $response = [
                'error' => 'Bad Request',
                'message' => 'URL is required'
            ];
        } else {
            $videoId = $videoService->extractYoutubeId($input['url']);
            
            if (!$videoId) {
                $statusCode = 400;
                $response = [
                    'error' => 'Bad Request',
                    'message' => 'Invalid YouTube URL'
                ];
            } else {
                $videoData = $videoService->fetchVideoData($videoId);
                $videoService->saveVideo($keyData['user_id'], $videoData);
                
                $statusCode = 201;
                $response = [
                    'success' => true,
                    'data' => $videoData,
                    'share_url' => APP_URL . '/?v=' . $videoId
                ];
            }
        }
    }
    
    // Route: DELETE /videos/{video_id} - Delete a video
    elseif ($method === 'DELETE' && count($pathParts) === 2 && $pathParts[0] === 'videos') {
        $videoId = $pathParts[1];
        $video = $videoService->getVideoByVideoId($videoId);
        
        if (!$video) {
            $statusCode = 404;
            $response = [
                'error' => 'Not Found',
                'message' => 'Video not found'
            ];
        } elseif ($video['user_id'] != $keyData['user_id']) {
            $statusCode = 403;
            $response = [
                'error' => 'Forbidden',
                'message' => 'You do not have access to this video'
            ];
        } else {
            $db = getDB();
            $stmt = $db->prepare('DELETE FROM videos WHERE video_id = :video_id');
            $stmt->execute(['video_id' => $videoId]);
            
            $response = [
                'success' => true,
                'message' => 'Video deleted successfully'
            ];
        }
    }
    
    // Route: GET /videos/{video_id}/stats - Get video statistics
    elseif ($method === 'GET' && count($pathParts) === 3 && $pathParts[0] === 'videos' && $pathParts[2] === 'stats') {
        $videoId = $pathParts[1];
        $video = $videoService->getVideoByVideoId($videoId);
        
        if (!$video) {
            $statusCode = 404;
            $response = [
                'error' => 'Not Found',
                'message' => 'Video not found'
            ];
        } elseif ($video['user_id'] != $keyData['user_id']) {
            $statusCode = 403;
            $response = [
                'error' => 'Forbidden',
                'message' => 'You do not have access to this video'
            ];
        } else {
            $stats = $videoService->getVideoStats($videoId);
            $response = [
                'success' => true,
                'data' => $stats
            ];
        }
    }
    
    // Route: GET /channels - Get videos grouped by channel
    elseif ($method === 'GET' && count($pathParts) === 1 && $pathParts[0] === 'channels') {
        $channels = $videoService->getUserVideosByChannel($keyData['user_id']);
        $response = [
            'success' => true,
            'data' => $channels,
            'count' => count($channels)
        ];
    }
    
    // Route: GET /search - Search videos
    elseif ($method === 'GET' && count($pathParts) === 1 && $pathParts[0] === 'search') {
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            $statusCode = 400;
            $response = [
                'error' => 'Bad Request',
                'message' => 'Search query (q) is required'
            ];
        } else {
            $results = $videoService->searchUserVideos($keyData['user_id'], $query);
            $response = [
                'success' => true,
                'data' => $results,
                'count' => count($results),
                'query' => $query
            ];
        }
    }
    
    // Route: GET /videos/{video_id}/transcript - Get video transcript
    elseif ($method === 'GET' && count($pathParts) === 3 && $pathParts[0] === 'videos' && $pathParts[2] === 'transcript') {
        $videoId = $pathParts[1];
        $video = $videoService->getVideoByVideoId($videoId);
        
        if (!$video) {
            $statusCode = 404;
            $response = [
                'error' => 'Not Found',
                'message' => 'Video not found'
            ];
        } elseif ($video['user_id'] != $keyData['user_id']) {
            $statusCode = 403;
            $response = [
                'error' => 'Forbidden',
                'message' => 'You do not have access to this video'
            ];
        } else {
            require_once 'transcript.php';
            $transcriptService = new Transcript();
            $transcript = $transcriptService->getTranscript($videoId);
            
            $response = [
                'success' => true,
                'has_transcript' => !empty($transcript['transcript_text']),
                'transcript' => $transcript['transcript_text'] ?? null,
                'fetched_at' => $transcript['transcript_fetched_at'] ?? null
            ];
        }
    }
    
    // Route: POST /videos/{video_id}/transcript - Fetch video transcript
    elseif ($method === 'POST' && count($pathParts) === 3 && $pathParts[0] === 'videos' && $pathParts[2] === 'transcript') {
        $videoId = $pathParts[1];
        $video = $videoService->getVideoByVideoId($videoId);
        
        if (!$video) {
            $statusCode = 404;
            $response = [
                'error' => 'Not Found',
                'message' => 'Video not found'
            ];
        } elseif ($video['user_id'] != $keyData['user_id']) {
            $statusCode = 403;
            $response = [
                'error' => 'Forbidden',
                'message' => 'You do not have access to this video'
            ];
        } else {
            require_once 'transcript.php';
            $transcriptService = new Transcript();
            $result = $transcriptService->processTranscript($video['youtube_url'], $videoId);
            
            if (!$result) {
                $statusCode = 400;
                $response = [
                    'error' => 'Bad Request',
                    'message' => 'Failed to fetch transcript. Transcript may not be available for this video.'
                ];
            } else {
                $transcript = $transcriptService->getTranscript($videoId);
                $statusCode = 201;
                $response = [
                    'success' => true,
                    'transcript' => $transcript['transcript_text'] ?? null,
                    'fetched_at' => $transcript['transcript_fetched_at'] ?? null
                ];
            }
        }
    }
    
    // Route: GET /me - Get current API key info
    elseif ($method === 'GET' && count($pathParts) === 1 && $pathParts[0] === 'me') {
        $response = [
            'success' => true,
            'data' => [
                'user_id' => $keyData['user_id'],
                'email' => $keyData['email'],
                'key_name' => $keyData['key_name'],
                'rate_limit' => $keyData['rate_limit_per_hour'],
                'created_at' => $keyData['created_at']
            ]
        ];
    }
    
    // Route not found
    else {
        $statusCode = 404;
        $response = [
            'error' => 'Not Found',
            'message' => 'API endpoint not found',
            'available_endpoints' => [
                'GET /api/v1/me' => 'Get API key information',
                'GET /api/v1/videos' => 'List all videos',
                'GET /api/v1/videos/{id}' => 'Get specific video',
                'POST /api/v1/videos' => 'Process new video (body: {url})',
                'DELETE /api/v1/videos/{id}' => 'Delete video',
                'GET /api/v1/videos/{id}/stats' => 'Get video statistics',
                'GET /api/v1/channels' => 'Get videos grouped by channel',
                'GET /api/v1/search?q={query}' => 'Search videos'
            ]
        ];
    }
    
} catch (Exception $e) {
    $statusCode = 500;
    $response = [
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ];
}

// Calculate response time
$responseTime = round((microtime(true) - $startTime) * 1000, 2); // in milliseconds

// Log the request
$apiKeyService->logRequest(
    $keyData['id'],
    $_SERVER['REQUEST_URI'],
    $method,
    $_SERVER['REMOTE_ADDR'],
    $statusCode,
    $responseTime
);

// Send response
http_response_code($statusCode);
echo json_encode($response, JSON_PRETTY_PRINT);
