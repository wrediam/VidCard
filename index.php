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
    
    // Add channel_handle column if it doesn't exist (migration for existing databases)
    try {
        $db->exec("
            DO $$ 
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_name = 'videos' AND column_name = 'channel_handle'
                ) THEN
                    ALTER TABLE videos ADD COLUMN channel_handle VARCHAR(255);
                END IF;
            END $$;
        ");
    } catch (Exception $e) {
        error_log('Column migration check: ' . $e->getMessage());
    }
    
    // Auto-backfill channel handles for existing videos (runs once per session)
    if (!isset($_SESSION['handles_backfilled'])) {
        $stmt = $db->query("SELECT COUNT(*) FROM videos WHERE channel_handle IS NULL OR channel_handle = ''");
        $missingHandles = $stmt->fetchColumn();
        
        if ($missingHandles > 0 && $missingHandles <= 10) {
            // Only auto-backfill if 10 or fewer videos (to avoid long delays)
            error_log("Auto-backfilling {$missingHandles} channel handles...");
            require_once 'video.php';
            
            $stmt = $db->query("SELECT * FROM videos WHERE channel_handle IS NULL OR channel_handle = '' LIMIT 10");
            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($videos as $video) {
                try {
                    $apiKey = YOUTUBE_API_KEY;
                    if (!$apiKey || $apiKey === 'your_youtube_api_key_here') continue;
                    
                    $apiUrl = "https://www.googleapis.com/youtube/v3/videos?id=" . $video['video_id'] . "&key=" . $apiKey . "&part=snippet";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $apiUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    
                    $response = curl_exec($ch);
                    $data = json_decode($response, true);
                    
                    if (!empty($data['items'])) {
                        $channelId = $data['items'][0]['snippet']['channelId'];
                        $channelUrl = "https://www.googleapis.com/youtube/v3/channels?id=" . $channelId . "&key=" . $apiKey . "&part=snippet";
                        
                        curl_setopt($ch, CURLOPT_URL, $channelUrl);
                        $channelResponse = curl_exec($ch);
                        $channelData = json_decode($channelResponse, true);
                        
                        $channelHandle = $channelData['items'][0]['snippet']['customUrl'] ?? '';
                        if ($channelHandle && strpos($channelHandle, '@') !== 0) {
                            $channelHandle = '@' . $channelHandle;
                        }
                        if (empty($channelHandle)) {
                            $channelHandle = '@' . str_replace(' ', '', $video['channel_name']);
                        }
                        
                        $updateStmt = $db->prepare('UPDATE videos SET channel_handle = :channel_handle WHERE id = :id');
                        $updateStmt->execute(['channel_handle' => $channelHandle, 'id' => $video['id']]);
                        error_log("Backfilled handle for video: {$video['video_id']} -> {$channelHandle}");
                    }
                    curl_close($ch);
                } catch (Exception $e) {
                    error_log("Failed to backfill handle for video {$video['video_id']}: " . $e->getMessage());
                }
            }
        }
        $_SESSION['handles_backfilled'] = true;
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
        
        // Route: Get all users (admin only)
        if (isset($input['action']) && $input['action'] === 'get_all_users') {
            if (!$currentUser || $currentUser['email'] !== 'will@wredia.com') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Forbidden']);
                exit;
            }
            
            $db = getDB();
            $stmt = $db->query(
                'SELECT u.id, u.email, u.created_at, u.last_login, u.is_active,
                        COUNT(v.id) as video_count
                 FROM users u
                 LEFT JOIN videos v ON u.id = v.user_id
                 GROUP BY u.id, u.email, u.created_at, u.last_login, u.is_active
                 ORDER BY u.created_at DESC'
            );
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'users' => $users]);
            exit;
        }
        
        // Route: Get user videos (admin only)
        if (isset($input['action']) && $input['action'] === 'get_user_videos') {
            if (!$currentUser || $currentUser['email'] !== 'will@wredia.com') {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Forbidden']);
                exit;
            }
            
            $userId = $input['user_id'] ?? '';
            if (!$userId) {
                throw new Exception('User ID required');
            }
            
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT v.*, 
                        COUNT(DISTINCT vv.id) as view_count,
                        MAX(vv.visited_at) as last_viewed
                 FROM videos v
                 LEFT JOIN video_visits vv ON v.id = vv.video_id
                 WHERE v.user_id = :user_id
                 GROUP BY v.id
                 ORDER BY v.created_at DESC'
            );
            $stmt->execute(['user_id' => $userId]);
            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'videos' => $videos]);
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

// Route: Admin panel (requires auth and admin email)
if ($path === '/admin' || $path === '/admin/') {
    if (!$currentUser) {
        header('Location: /');
        exit;
    }
    if ($currentUser['email'] !== 'will@wredia.com') {
        header('Location: /dashboard');
        exit;
    }
    include 'views/admin.php';
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
// Redirect to dashboard if already logged in
if ($currentUser) {
    header('Location: /dashboard');
    exit;
}

include 'views/home.php';
