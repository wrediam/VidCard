<?php
/**
 * Backfill Channel Handles
 * Run this script to fetch and update channel handles for existing videos
 */

require_once 'config.php';
require_once 'video.php';

echo "Starting channel handle backfill...\n\n";

try {
    $db = getDB();
    $videoService = new Video();
    
    // Get all videos without channel handles
    $stmt = $db->query("SELECT * FROM videos WHERE channel_handle IS NULL OR channel_handle = ''");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total = count($videos);
    echo "Found {$total} videos without channel handles.\n\n";
    
    if ($total === 0) {
        echo "✅ All videos already have channel handles!\n";
        exit(0);
    }
    
    $updated = 0;
    $failed = 0;
    $apiKey = YOUTUBE_API_KEY;
    
    if (!$apiKey || $apiKey === 'your_youtube_api_key_here') {
        echo "❌ Error: YouTube API key not configured.\n";
        exit(1);
    }
    
    foreach ($videos as $index => $video) {
        $num = $index + 1;
        echo "[{$num}/{$total}] Processing: {$video['title']}\n";
        
        try {
            // Fetch video data to get channel ID
            $apiUrl = "https://www.googleapis.com/youtube/v3/videos?id=" . $video['video_id'] . "&key=" . $apiKey . "&part=snippet";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; VidCard/1.0)');
            curl_setopt($ch, CURLOPT_REFERER, APP_URL);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($httpCode !== 200) {
                throw new Exception("YouTube API returned HTTP {$httpCode}");
            }
            
            $data = json_decode($response, true);
            if (empty($data['items'])) {
                throw new Exception("Video not found");
            }
            
            $videoData = $data['items'][0]['snippet'];
            $channelId = $videoData['channelId'];
            
            // Fetch channel details including handle
            $channelUrl = "https://www.googleapis.com/youtube/v3/channels?id=" . $channelId . "&key=" . $apiKey . "&part=snippet,brandingSettings";
            
            curl_setopt($ch, CURLOPT_URL, $channelUrl);
            $channelResponse = curl_exec($ch);
            $channelData = json_decode($channelResponse, true);
            
            curl_close($ch);
            
            $channelHandle = $channelData['items'][0]['snippet']['customUrl'] ?? '';
            
            // Ensure handle starts with @
            if ($channelHandle && strpos($channelHandle, '@') !== 0) {
                $channelHandle = '@' . $channelHandle;
            }
            
            // Fallback to channel name if no handle
            if (empty($channelHandle)) {
                $channelHandle = '@' . str_replace(' ', '', $video['channel_name']);
            }
            
            // Update the video with the channel handle
            $updateStmt = $db->prepare('UPDATE videos SET channel_handle = :channel_handle WHERE id = :id');
            $updateStmt->execute([
                'channel_handle' => $channelHandle,
                'id' => $video['id']
            ]);
            
            echo "  ✓ Updated with handle: {$channelHandle}\n";
            $updated++;
            
            // Rate limiting - be nice to YouTube API
            usleep(100000); // 100ms delay between requests
            
        } catch (Exception $e) {
            echo "  ✗ Failed: " . $e->getMessage() . "\n";
            $failed++;
        }
        
        echo "\n";
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Backfill complete!\n";
    echo "✅ Updated: {$updated}\n";
    if ($failed > 0) {
        echo "❌ Failed: {$failed}\n";
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
