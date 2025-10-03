<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Check authentication
$auth = new Auth();
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

if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$videoId = $input['video_id'] ?? null;
$startTime = $input['start_time'] ?? null;
$endTime = $input['end_time'] ?? null;
$clipIndex = $input['clip_index'] ?? null;

if (!$videoId || $startTime === null || $endTime === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields: video_id, start_time, end_time']);
    exit;
}

// Validate times
if (!is_numeric($startTime) || !is_numeric($endTime) || $startTime < 0 || $endTime <= $startTime) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid time range']);
    exit;
}

try {
    $db = getDB();
    
    // Get video details
    $stmt = $db->prepare('SELECT * FROM videos WHERE video_id = :video_id AND user_id = :user_id');
    $stmt->execute([
        'video_id' => $videoId,
        'user_id' => $currentUser['id']
    ]);
    $video = $stmt->fetch();
    
    if (!$video) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Video not found']);
        exit;
    }
    
    // Save clip edit to database if clip_index is provided
    if ($clipIndex !== null) {
        // Get original clip suggestion
        $stmt = $db->prepare('SELECT clip_suggestions FROM ai_clip_suggestions WHERE video_id = :video_id AND user_id = :user_id');
        $stmt->execute([
            'video_id' => $videoId,
            'user_id' => $currentUser['id']
        ]);
        $result = $stmt->fetch();
        
        if ($result) {
            $suggestions = json_decode($result['clip_suggestions'], true);
            if (isset($suggestions[$clipIndex])) {
                $originalClip = $suggestions[$clipIndex];
                
                // Save or update clip edit
                $stmt = $db->prepare('
                    INSERT INTO ai_clip_edits 
                    (video_id, user_id, clip_index, original_start_time, original_end_time, edited_start_time, edited_end_time)
                    VALUES (:video_id, :user_id, :clip_index, :original_start, :original_end, :edited_start, :edited_end)
                    ON CONFLICT (video_id, user_id, clip_index) 
                    DO UPDATE SET 
                        edited_start_time = :edited_start,
                        edited_end_time = :edited_end,
                        updated_at = CURRENT_TIMESTAMP
                ');
                $stmt->execute([
                    'video_id' => $videoId,
                    'user_id' => $currentUser['id'],
                    'clip_index' => $clipIndex,
                    'original_start' => $originalClip['start_time'],
                    'original_end' => $originalClip['end_time'],
                    'edited_start' => (int)$startTime,
                    'edited_end' => (int)$endTime
                ]);
            }
        }
    }
    
    // Check if API key is configured
    if (empty(WREDIA_CLIP_API_KEY)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Clip download API not configured']);
        exit;
    }
    
    // Prepare request to Wredia API
    $youtubeUrl = "https://www.youtube.com/watch?v={$videoId}";
    
    $requestData = [
        'url' => $youtubeUrl,
        'start_time' => (int)$startTime,
        'end_time' => (int)$endTime,
        'resolution' => $input['resolution'] ?? '1080p',
        'link' => true // Return download link instead of file
    ];
    
    // Add optional parameters if provided
    if (isset($input['hdr'])) {
        $requestData['hdr'] = (bool)$input['hdr'];
    }
    
    if (isset($input['subtitle']) && is_array($input['subtitle'])) {
        $requestData['subtitle'] = $input['subtitle'];
    }
    
    error_log("Requesting clip download from Wredia API: " . json_encode($requestData));
    
    // Make request to Wredia API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, WREDIA_CLIP_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes for clip processing
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . WREDIA_CLIP_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Wredia API cURL error: $curlError");
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Network error connecting to clip download service']);
        exit;
    }
    
    if ($httpCode !== 200) {
        error_log("Wredia API error: HTTP $httpCode - Response: " . substr($response ?: 'empty', 0, 500));
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'Clip download service error (HTTP ' . $httpCode . ')',
            'details' => $response ? json_decode($response, true) : null
        ]);
        exit;
    }
    
    $apiResponse = json_decode($response, true);
    
    // Log the full API response for debugging
    error_log("Wredia API response: " . substr($response, 0, 1000));
    
    if (!$apiResponse) {
        error_log("Wredia API invalid response: " . substr($response, 0, 500));
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Invalid response from clip download service']);
        exit;
    }
    
    // Try multiple possible response formats from the API
    $downloadUrl = $apiResponse['download_url'] 
                ?? $apiResponse['download_link'] 
                ?? $apiResponse['url'] 
                ?? $apiResponse['file'] 
                ?? null;
    
    if (!$downloadUrl) {
        error_log("Wredia API - no download URL found in response: " . json_encode($apiResponse));
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => 'No download URL returned from clip service',
            'api_response' => $apiResponse
        ]);
        exit;
    }
    
    // Return the download link to the frontend
    echo json_encode([
        'success' => true,
        'download_url' => $downloadUrl,
        'filename' => $apiResponse['filename'] ?? "clip_{$videoId}_{$startTime}-{$endTime}.mp4",
        'message' => 'Clip ready for download'
    ]);
    
} catch (Exception $e) {
    error_log('Clip download error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to process clip download']);
}
