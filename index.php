<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_once 'video.php';

// Auto-migrate database on first run
try {
    $db = getDB();
    $result = $db->query("SELECT to_regclass('public.users')");
    $tableExists = $result->fetchColumn();
    
    if (!$tableExists) {
        $sql = file_get_contents(__DIR__ . '/init.sql');
        $db->exec($sql);
        error_log('Database schema initialized automatically');
    }
} catch (Exception $e) {
    error_log('Auto-migration check failed: ' . $e->getMessage());
}

$auth = new Auth();
$videoService = new Video();

// Parse the URL
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$query = parse_url($request_uri, PHP_URL_QUERY);
parse_str($query ?? '', $params);

// Get client info
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
$referrer = $_SERVER['HTTP_REFERER'] ?? 'direct';

// Check for session
$currentUser = null;
if (isset($_COOKIE['session_token'])) {
    $session = $auth->validateSession($_COOKIE['session_token']);
    if ($session) {
        $currentUser = [
            'id' => $session['user_id'],
            'email' => $session['email']
        ];
    }
}

// API Routes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Route: Send auth code
        if (isset($input['action']) && $input['action'] === 'send_code') {
            $email = $input['email'] ?? '';
            $auth->sendAuthCode($email, $ipAddress);
            echo json_encode(['success' => true, 'message' => 'Code sent to your email']);
            exit;
        }
        
        // Route: Verify code
        if (isset($input['action']) && $input['action'] === 'verify_code') {
            $email = $input['email'] ?? '';
            $code = $input['code'] ?? '';
            
            $result = $auth->verifyCode($email, $code, $ipAddress, $userAgent);
            
            // Set session cookie
            setcookie('session_token', $result['session_token'], time() + SESSION_LIFETIME, '/', '', true, true);
            
            echo json_encode(['success' => true, 'redirect' => '/dashboard']);
            exit;
        }
        
        // Route: Process video (requires auth)
        if (isset($input['action']) && $input['action'] === 'process_video') {
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            
            $url = $input['url'] ?? '';
            $videoId = $videoService->extractYoutubeId($url);
            
            if (!$videoId) {
                throw new Exception('Invalid YouTube URL');
            }
            
            // Fetch and save video data
            $videoData = $videoService->fetchVideoData($videoId);
            $videoService->saveVideo($currentUser['id'], $videoData);
            
            echo json_encode([
                'success' => true,
                'video' => $videoData,
                'share_url' => APP_URL . '/?v=' . $videoId
            ]);
            exit;
        }
        
        // Route: Get user videos (requires auth)
        if (isset($input['action']) && $input['action'] === 'get_videos') {
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            
            $videos = $videoService->getUserVideosByChannel($currentUser['id']);
            echo json_encode(['success' => true, 'channels' => $videos]);
            exit;
        }
        
        // Route: Get video stats (requires auth)
        if (isset($input['action']) && $input['action'] === 'get_stats') {
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            
            $videoId = $input['video_id'] ?? '';
            $stats = $videoService->getVideoStats($videoId);
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            exit;
        }
        
        // Route: Search videos (requires auth)
        if (isset($input['action']) && $input['action'] === 'search') {
            if (!$currentUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            
            $query = $input['query'] ?? '';
            $results = $videoService->searchUserVideos($currentUser['id'], $query);
            
            echo json_encode(['success' => true, 'results' => $results]);
            exit;
        }
        
        // Route: Logout
        if (isset($input['action']) && $input['action'] === 'logout') {
            if (isset($_COOKIE['session_token'])) {
                $auth->logout($_COOKIE['session_token']);
                setcookie('session_token', '', time() - 3600, '/', '', true, true);
            }
            echo json_encode(['success' => true]);
            exit;
        }
        
        throw new Exception('Invalid action');
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Route: Video redirect with meta tags
if (isset($params['v'])) {
    $videoId = $params['v'];
    
    // Record visit
    $videoService->recordVisit($videoId, $ipAddress, $referrer, $userAgent);
    
    // Get video data
    $video = $videoService->getVideoByVideoId($videoId);
    
    if (!$video) {
        http_response_code(404);
        echo "Video not found";
        exit;
    }
    
    include 'views/redirect.php';
    exit;
}

// Route: Dashboard (requires auth)
if ($path === '/dashboard' || $path === '/dashboard/') {
    if (!$currentUser) {
        header('Location: /');
        exit;
    }
    include 'views/dashboard.php';
    exit;
}

// Route: Terms of Service
if ($path === '/terms' || $path === '/terms/') {
    include 'views/terms.php';
    exit;
}

// Route: Privacy Policy
if ($path === '/privacy' || $path === '/privacy/') {
    include 'views/privacy.php';
    exit;
}

// Route: Homepage
include 'views/home.php';
