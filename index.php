<?php
session_start();

// Parse the URL
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$query = parse_url($request_uri, PHP_URL_QUERY);
parse_str($query ?? '', $params);

// Track visit if video parameter is present
if (isset($params['v']) && !isset($params['action'])) {
    $videoId = $params['v'];
    $db = loadDatabase();
    if (isset($db[$videoId])) {
        // Record visit
        if (!isset($db[$videoId]['visits'])) {
            $db[$videoId]['visits'] = [];
        }
        $db[$videoId]['visits'][] = [
            'timestamp' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'referrer' => $_SERVER['HTTP_REFERER'] ?? 'direct'
        ];
        saveToDatabase($db);
    }
}

// Check for login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['passcode'])) {
    if ($_POST['passcode'] === '5455') {
        $_SESSION['authenticated'] = true;
        header('Location: /');
        exit;
    }
}

// Database functions
function loadDatabase()
{
    $dbFile = __DIR__ . '/db.json';
    return file_exists($dbFile) ? json_decode(file_get_contents($dbFile), true) : [];
}

function saveToDatabase($data)
{
    $dbFile = __DIR__ . '/db.json';
    file_put_contents($dbFile, json_encode($data, JSON_PRETTY_PRINT));
}

function extractYoutubeId($url)
{
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    if (preg_match($pattern, $url, $match)) {
        return $match[1];
    }
    return false;
}

function parseDuration($duration)
{
    // Convert YouTube's PT#M#S format to a readable duration
    preg_match('/PT(\d+H)?(\d+M)?(\d+S)?/', $duration, $matches);
    $hours = isset($matches[1]) ? intval($matches[1]) : 0;
    $minutes = isset($matches[2]) ? intval($matches[2]) : 0;
    $seconds = isset($matches[3]) ? intval($matches[3]) : 0;

    if ($hours > 0) {
        return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
    }
    return sprintf("%d:%02d", $minutes, $seconds);
}

function fetchVideoData($videoId)
{
    // Use YouTube Data API v3
    $apiKey = getenv('YOUTUBE_API_KEY'); // Make sure to set this environment variable
    if (!$apiKey) {
        throw new Exception('YouTube API key not configured. Please set YOUTUBE_API_KEY environment variable.');
    }

    $apiUrl = "https://www.googleapis.com/youtube/v3/videos?id=" . $videoId . "&key=" . $apiKey . "&part=snippet";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Referer: https://meta.wredia.co/',
        'Origin: https://meta.wredia.co'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && !empty($data['items'])) {
            $videoData = $data['items'][0]['snippet'];

            // Fetch channel details to get profile image
            $channelId = $videoData['channelId'];
            $channelUrl = "https://www.googleapis.com/youtube/v3/channels?id=" . $channelId . "&key=" . $apiKey . "&part=snippet";

            curl_setopt($ch, CURLOPT_URL, $channelUrl);
            $channelResponse = curl_exec($ch);
            $channelData = json_decode($channelResponse, true);

            curl_close($ch);

            $channelThumbnail = $channelData['items'][0]['snippet']['thumbnails']['default']['url'] ?? '';

            error_log("YouTube API data for video $videoId: " . print_r($videoData, true));

            return [
                'id' => $videoId,
                'title' => $videoData['title'],
                'description' => $videoData['description'],
                'author_name' => $videoData['channelTitle'],
                'author_url' => "https://www.youtube.com/channel/" . $channelId,
                'channel_thumbnail' => $channelThumbnail,
                'thumbnail' => $videoData['thumbnails']['maxres']['url'] ?? $videoData['thumbnails']['high']['url'] ?? $videoData['thumbnails']['default']['url'],
                'url' => "https://www.youtube.com/watch?v=" . $videoId,
                'timestamp' => time()
            ];
        }
    }

    curl_close($ch);
    error_log("Failed to fetch metadata for video $videoId. HTTP code: $httpCode, Response: " . substr($response, 0, 1000));
    throw new Exception('Could not fetch video metadata. Please check if the YouTube API key is configured correctly.');
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Handle stats request
    if (isset($_POST['action']) && $_POST['action'] === 'get_stats' && isset($_POST['videoId'])) {
        $videoId = $_POST['videoId'];
        $db = loadDatabase();
        if (isset($db[$videoId])) {
            $visits = $db[$videoId]['visits'] ?? [];
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_visits' => count($visits),
                    'visits' => array_slice($visits, -10) // Return last 10 visits
                ]
            ]);
            exit;
        }
        echo json_encode(['success' => false, 'error' => 'Video not found']);
        exit;
    }

    // Handle database restore action
    if (isset($_FILES['db_file']) && isset($_POST['action']) && $_POST['action'] === 'restore_db') {
        $uploadedFile = $_FILES['db_file'];
        if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
            $content = file_get_contents($uploadedFile['tmp_name']);
            if (json_decode($content) !== null) {
                file_put_contents('db.json', $content);
                echo json_encode(['success' => true]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Invalid JSON file']);
        exit;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['url'])) {
            throw new Exception('URL is required');
        }

        $url = $data['url'] ?? '';

        // Get video ID from URL
        $videoId = extractYoutubeId($url);

        if (!$videoId) {
            echo json_encode(['success' => false, 'error' => 'Invalid YouTube URL']);
            exit;
        }

        // Check if video already exists in db.json
        $dbContent = file_get_contents('db.json');
        $db = json_decode($dbContent, true) ?? [];

        if (isset($db[$videoId])) {
            // Return existing data if video is already processed
            echo json_encode([
                'success' => true,
                'exists' => true,
                'title' => $db[$videoId]['title'],
                'description' => $db[$videoId]['description'],
                'thumbnail' => $db[$videoId]['thumbnail'],
                'redirect' => "https://meta.wredia.co/?v=" . $videoId
            ]);
            exit;
        }

        $videoData = fetchVideoData($videoId);

        // Save to database
        $db[$videoId] = $videoData;
        saveToDatabase($db);

        echo json_encode([
            'success' => true,
            'redirect' => "https://meta.wredia.co/?v=" . $videoId,
            'title' => $videoData['title'],
            'thumbnail' => $videoData['thumbnail'],
            'description' => $videoData['description']
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

function getVideoIdFromUrl($url)
{
    $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return null;
}

// Get the database for client-side search
$dbContent = file_get_contents('db.json');
$db = json_decode($dbContent, true) ?? [];
$dbJson = json_encode($db);

// Skip authentication for video preview endpoint
$isVideoPreview = isset($params['v']);

// If not authenticated and not a video preview, show login form
if (!$isVideoPreview && (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated'])) {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - VidCard</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
            
            body {
                font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);
            }

            .login-form {
                background: white;
                padding: 3rem;
                border-radius: 16px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 320px;
                position: relative;
                overflow: hidden;
            }

            .login-form::before,
            .login-form::after,
            .login-form span::before {
                content: '';
                position: absolute;
                border-radius: 6px;
                animation: float 3s infinite;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .login-form::before {
                right: -16px;
                top: -16px;
                width: 32px;
                height: 32px;
                background: linear-gradient(135deg, #4158D0, #C850C0);
                animation-delay: 0s;
            }

            .login-form::after {
                left: -10px;
                bottom: -10px;
                width: 20px;
                height: 20px;
                background: linear-gradient(135deg, #00B4DB, #0083B0);
                animation-delay: -1s;
                opacity: 0.8;
            }

            .login-form span::before {
                right: 40px;
                bottom: -8px;
                width: 16px;
                height: 16px;
                background: linear-gradient(135deg, #FF416C, #FF4B2B);
                animation-delay: -2s;
                opacity: 0.6;
            }

            .login-form h1 {
                margin: 0 0 1.5rem;
                font-size: 2rem;
                font-weight: 700;
                text-align: center;
                background: linear-gradient(135deg, #FF416C, #FF4B2B);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
                position: relative;
            }

            .login-form input {
                width: 100%;
                padding: 0.75rem;
                margin-bottom: 1.5rem;
                border: 2px solid #e1e4e8;
                border-radius: 8px;
                box-sizing: border-box;
                font-family: 'Poppins', sans-serif;
                font-size: 1rem;
                transition: all 0.3s ease;
            }

            .login-form input:focus {
                outline: none;
                border-color: #FF416C;
                box-shadow: 0 0 0 3px rgba(255, 65, 108, 0.1);
            }

            .login-form button {
                width: 100%;
                padding: 0.75rem;
                background: linear-gradient(135deg, #FF416C, #FF4B2B);
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-family: 'Poppins', sans-serif;
                font-weight: 600;
                font-size: 1rem;
                transition: all 0.3s ease;
            }

            .login-form button:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(255, 65, 108, 0.3);
            }

            @keyframes float {
                0% {
                    transform: translate(0, 0) rotate(0deg);
                }
                20% {
                    transform: translate(-4px, -4px) rotate(5deg);
                }
                40% {
                    transform: translate(4px, -2px) rotate(-5deg);
                }
                60% {
                    transform: translate(-2px, 4px) rotate(5deg);
                }
                80% {
                    transform: translate(4px, 2px) rotate(-5deg);
                }
                100% {
                    transform: translate(0, 0) rotate(0deg);
                }
            }
        </style>
    </head>

    <body>
        <form class="login-form" method="POST">
            <span></span>
            <h1>VidCard</h1>
            <input type="password" name="passcode" placeholder="Enter passcode" required>
            <button type="submit">Login</button>
        </form>
    </body>

    </html>
<?php
    exit;
}
?>
<?php
// Handle video requests
if (isset($params['v'])) {
    $videoId = $params['v'];
    $db = loadDatabase();

    if (!isset($db[$videoId])) {
        http_response_code(404);
        echo "Video not found";
        exit;
    }
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($db[$videoId]['title']); ?></title>

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="video">
        <meta property="og:title" content="<?php echo htmlspecialchars($db[$videoId]['title']); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars($db[$videoId]['description']); ?>">
        <meta property="og:image" content="<?php echo htmlspecialchars($db[$videoId]['thumbnail']); ?>">
        <meta property="og:url" content="<?php echo htmlspecialchars($db[$videoId]['url']); ?>">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo htmlspecialchars($db[$videoId]['title']); ?>">
        <meta name="twitter:description" content="<?php echo htmlspecialchars($db[$videoId]['description']); ?>">
        <meta name="twitter:image" content="<?php echo htmlspecialchars($db[$videoId]['thumbnail']); ?>">

        <script>
            window.location.href = "<?php echo htmlspecialchars($db[$videoId]['url']); ?>";
        </script>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background-color: #f5f5f5;
            }

            .redirect-message {
                text-align: center;
                padding: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
        </style>
    </head>

    <body>
        <div class="redirect-message">
            <h1>Redirecting to YouTube...</h1>
            <p>If you are not redirected automatically, <a href="<?php echo htmlspecialchars($db[$videoId]['url']); ?>">click here</a>.</p>
        </div>
    </body>

    </html>
<?php
    exit;
} else {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>VidCard</title>
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="styles.css">
    </head>

    <body>
        <div class="sidebar" id="sidebar">
            <div class="sidebar-title">Channels</div>
            <div class="channel-list" id="channelList"></div>
        </div>

        <button class="sidebar-toggle material-icons" id="sidebarToggle" onclick="toggleSidebar()">
            chevron_left
        </button>

        <button class="settings-button material-icons" onclick="toggleSettings()">settings</button>

        <div class="overlay-container" id="settingsOverlay">
            <div class="settings-content">
                <button class="close-button material-icons" onclick="toggleSettings()">close</button>
                <h2>Settings</h2>
                <div class="settings-actions">
                    <div class="settings-button-group">
                        <label>Backup Database</label>
                        <button class="action-button" onclick="downloadDatabase()">
                            <span class="material-icons">download</span>
                            Download db.json
                        </button>
                    </div>
                    <div class="settings-button-group">
                        <label>Restore Database</label>
                        <div class="file-input-wrapper">
                            <button class="action-button">
                                <span class="material-icons">upload</span>
                                Upload db.json
                            </button>
                            <input type="file" accept=".json" onchange="restoreDatabase(this)">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-wrapper" id="mainWrapper">
            <button class="search-icon material-icons" onclick="toggleSearch()">search</button>

            <div class="overlay-container" id="searchContainer">
                <div class="search-box">
                    <button class="close-button material-icons" onclick="toggleSearch()">close</button>
                    <input type="text" id="searchInput" placeholder="Search channel or video" onkeyup="debounceSearch()">
                    <div class="search-results" id="searchResults"></div>
                </div>
            </div>

            <div class="overlay-container" id="channelOverlay">
                <div class="overlay-content">
                    <button class="close-button material-icons" onclick="closeChannelOverlay()">close</button>
                    <div class="overlay-header" id="channelOverlayHeader"></div>
                    <div class="search-results" id="channelVideos"></div>
                </div>
            </div>

            <div class="overlay-container" id="statsModal">
                <div class="stats-content">
                    <div class="stats-header">
                        <h2>Link Statistics</h2>
                        <span class="stats-close" onclick="closeStatsModal()">&times;</span>
                    </div>
                    <div class="stats-total"></div>
                    <ul class="stats-list"></ul>
                </div>
            </div>

            <div class="container">
                <div class="main-content">
                    <h1><span>VidCard</span></h1>
                    <p>Extract YouTube video metadata and create a clean card for sharing on social media to boost engagement.</p>
                    <br>
                    <div class="input-group">
                        <input type="text" id="urlInput" placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                    <button class="submit-button" onclick="extractMetadata()">Process Link</button>
                    <div id="error"></div>
                </div>

                <div class="preview-container" id="previewContainer">
                    <div class="twitter-card">
                        <img id="previewImage" src="" alt="Preview">
                        <div class="twitter-card-content">
                            <h2 id="previewTitle"></h2>
                            <div class="domain">From meta.wredia.co</div>
                        </div>
                    </div>
                    <div class="copy-section">
                        <button class="copy-button" onclick="copyToClipboard()">
                            <span class="material-icons">content_copy</span>
                            Copy URL
                        </button>
                        <div class="success-message" id="copySuccess">URL copied to clipboard!</div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            let videoDB = <?php echo $dbJson; ?> || {};
            let searchTimeout;
            let sidebarCollapsed = false;

            // Initialize channel list and click handlers on page load
            document.addEventListener('DOMContentLoaded', () => {
                updateChannelList();

                // Add click handlers for overlays
                document.getElementById('channelOverlay').addEventListener('click', (e) => {
                    // Only close if clicking the overlay background itself
                    if (e.target.id === 'channelOverlay') {
                        closeChannelOverlay();
                    }
                });

                // Prevent clicks inside overlay content from closing
                document.querySelector('#channelOverlay .overlay-content').addEventListener('click', (e) => {
                    e.stopPropagation();
                });

                document.getElementById('searchContainer').addEventListener('click', (e) => {
                    if (e.target.id === 'searchContainer') {
                        toggleSearch();
                    }
                });

                // Prevent clicks inside search content from closing
                document.querySelector('#searchContainer .search-box').addEventListener('click', (e) => {
                    e.stopPropagation();
                });
                toggleSidebar();
            });

            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                const mainWrapper = document.getElementById('mainWrapper');
                const toggle = document.getElementById('sidebarToggle');

                sidebarCollapsed = !sidebarCollapsed;

                sidebar.classList.toggle('collapsed');
                mainWrapper.classList.toggle('collapsed');
                toggle.classList.toggle('collapsed');

                // Change icon based on state
                toggle.textContent = sidebarCollapsed ? 'chevron_right' : 'chevron_left';
            }

            function updateChannelList() {
                const channelList = document.getElementById('channelList');
                const channels = {};

                // Group videos by channel
                if (Object.keys(videoDB).length > 0) {
                    Object.values(videoDB).forEach(video => {
                        if (!channels[video.author_name]) {
                            channels[video.author_name] = {
                                name: video.author_name,
                                thumbnail: video.channel_thumbnail,
                                videos: []
                            };
                        }
                        channels[video.author_name].videos.push(video);
                    });

                    channelList.innerHTML = Object.values(channels).map(channel => `
                    <div class="sidebar-channel-item" onclick="showChannelOverlay('${channel.name.replace(/'/g, "\\'")}')">
                        <div class="channel-thumbnail">
                            <img src="${channel.thumbnail}" alt="${channel.name}">
                        </div>
                        <div class="channel-info">
                            <div class="channel-name">${channel.name}</div>
                            <div class="video-count">${channel.videos.length} video${channel.videos.length === 1 ? '' : 's'}</div>
                        </div>
                    </div>
                `).join('');
                } else {
                    channelList.innerHTML = '<div class="sidebar-empty">No channels processed yet</div>';
                }
            }

            function showChannelOverlay(channelName) {
                const overlay = document.getElementById('channelOverlay');
                const header = document.getElementById('channelOverlayHeader');
                const videosList = document.getElementById('channelVideos');

                const channelVideos = Object.values(videoDB).filter(video => video.author_name === channelName);
                const channel = channelVideos[0]; // Use first video to get channel info

                header.innerHTML = `
                <div class="channel-thumbnail">
                    <img src="${channel.channel_thumbnail}" alt="${channel.author_name}">
                </div>
                <div class="overlay-header-info">
                    <h2>${channel.author_name}</h2>
                    <p>${channelVideos.length} video${channelVideos.length === 1 ? '' : 's'}</p>
                </div>
            `;

                videosList.innerHTML = channelVideos.map(video => `
                <div class="video-item">
                    <img class="video-thumbnail" src="${video.thumbnail}" alt="${video.title}">
                    <div class="video-info">
                        <div class="video-title">
                            <a href="${video.url}" target="_blank">${video.title}</a>
                        </div>
                        <div class="video-description">${video.description}</div>
                        <div class="video-actions">
                            <button class="video-copy-button" onclick="copyLink('${video.id}', this)">
                                <span class="material-icons">content_copy</span>
                                Copy URL
                            </button>
                            <button class="stats-button" onclick="showStats('${video.id}')">
                                <span class="material-icons">analytics</span>
                                View Stats
                            </button>
                            <span class="video-success-message" data-video-id="${video.id}">URL copied!</span>
                        </div>
                    </div>
                </div>
            `).join('');

                overlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            function closeChannelOverlay() {
                const overlay = document.getElementById('channelOverlay');
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            }

            async function extractMetadata() {
                const urlInput = document.getElementById('urlInput');
                const errorDiv = document.getElementById('error');
                const previewContainer = document.getElementById('previewContainer');

                try {
                    const response = await fetch('/', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            url: urlInput.value
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Update preview
                        document.getElementById('previewImage').src = data.thumbnail;
                        document.getElementById('previewTitle').textContent = data.title;

                        // Show preview
                        previewContainer.style.display = 'block';

                        // Store URL for copying
                        previewContainer.dataset.url = data.redirect;

                        // Show message if video already exists
                        if (data.exists) {
                            errorDiv.style.color = '#1da1f2'; // Use Twitter blue for non-error message
                            errorDiv.textContent = 'This video has already been processed.';
                        } else {
                            errorDiv.textContent = '';
                            // Only refresh database if it's a new video
                            const dbResponse = await fetch('/db.json');
                            videoDB = await dbResponse.json();
                            updateChannelList();
                        }

                        // Scroll to preview
                        previewContainer.scrollIntoView({
                            behavior: 'smooth'
                        });
                    } else {
                        errorDiv.style.color = 'red';
                        errorDiv.textContent = data.error;
                    }
                } catch (error) {
                    errorDiv.style.color = 'red';
                    errorDiv.textContent = 'Error extracting metadata';
                }
            }

            function copyLink(videoId, button) {
                const url = `https://meta.wredia.co/?v=${videoId}`;
                navigator.clipboard.writeText(url)
                    .then(() => {
                        // Find all success messages for this video ID
                        const successMessages = document.querySelectorAll(`.video-success-message[data-video-id="${videoId}"]`);
                        successMessages.forEach(message => {
                            message.style.display = 'inline';
                            setTimeout(() => {
                                message.style.display = 'none';
                            }, 2000);
                        });
                    })
                    .catch(err => {
                        console.error('Failed to copy:', err);
                    });
            }

            function debounceSearch() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(searchChannels, 300);
            }

            function searchChannels() {
                const searchInput = document.getElementById('searchInput');
                const searchResults = document.getElementById('searchResults');
                const query = searchInput.value.trim().toLowerCase();

                // Clear results if search is empty
                if (!query) {
                    searchResults.innerHTML = '';
                    return;
                }

                // Create maps for channels and videos
                const channelResults = new Map();
                const videoResults = [];

                // Search through videos
                Object.values(videoDB).forEach(video => {
                    const channelName = video.author_name.toLowerCase();
                    const videoTitle = video.title.toLowerCase();
                    
                    // Check for channel match
                    if (channelName.includes(query)) {
                        if (!channelResults.has(video.author_name)) {
                            channelResults.set(video.author_name, {
                                name: video.author_name,
                                thumbnail: video.channel_thumbnail,
                                url: video.author_url,
                                videos: []
                            });
                        }
                        channelResults.get(video.author_name).videos.push(video);
                    }
                    // Check for video title match (only if not already matched by channel)
                    else if (videoTitle.includes(query)) {
                        videoResults.push(video);
                    }
                });

                // Build results HTML
                let resultsHTML = '';

                // Add channel results
                if (channelResults.size > 0) {
                    resultsHTML += '<div class="search-section"><h3>Channels</h3>';
                    channelResults.forEach(channel => {
                        resultsHTML += `
                            <div class="channel-item" onclick="showChannelVideos('${channel.name.replace(/'/g, "\\'")}')">
                                <img src="${channel.thumbnail}" alt="${channel.name}" class="channel-thumbnail">
                                <div class="channel-info">
                                    <div class="channel-name">${channel.name}</div>
                                    <div class="video-count">${channel.videos.length} videos</div>
                                </div>
                            </div>
                        `;
                    });
                    resultsHTML += '</div>';
                }

                // Add video results
                if (videoResults.length > 0) {
                    resultsHTML += '<div class="search-section"><h3>Videos</h3>';
                    videoResults.forEach(video => {
                        resultsHTML += `
                            <div class="video-item">
                                <img class="video-thumbnail" src="${video.thumbnail}" alt="${video.title}">
                                <div class="video-info">
                                    <div class="video-title">${video.title}</div>
                                    <div class="video-channel">${video.author_name}</div>
                                    <div class="button-group">
                                        <button class="video-copy-button" onclick="copyLink('${video.id}')">
                                            <span class="material-icons">content_copy</span>
                                            Copy URL
                                        </button>
                                        <button class="stats-button" onclick="showStats('${video.id}')">
                                            <span class="material-icons">analytics</span>
                                            View Stats
                                        </button>
                                        <span class="video-success-message" data-video-id="${video.id}">URL copied!</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    resultsHTML += '</div>';
                }

                // Show no results message if nothing found
                if (!resultsHTML) {
                    resultsHTML = '<div class="no-results">No matches found</div>';
                }

                searchResults.innerHTML = resultsHTML;
            }

            function toggleChannelVideos(channelName) {
                const videosDiv = document.getElementById(`channel-${channelName.replace(/[^a-zA-Z0-9]/g, '-')}`);
                const currentDisplay = videosDiv.style.display;
                videosDiv.style.display = currentDisplay === 'block' ? 'none' : 'block';
            }

            function toggleSearch() {
                const searchContainer = document.getElementById('searchContainer');
                const searchInput = document.getElementById('searchInput');
                const currentDisplay = searchContainer.style.display;

                searchContainer.style.display = currentDisplay === 'flex' ? 'none' : 'flex';

                if (searchContainer.style.display === 'flex') {
                    searchInput.focus();
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }

            async function copyToClipboard() {
                const url = document.getElementById('previewContainer').dataset.url;
                const successMessage = document.getElementById('copySuccess');

                try {
                    await navigator.clipboard.writeText(url);
                    successMessage.style.display = 'block';
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                    }, 2000);
                } catch (error) {
                    console.error('Failed to copy:', error);
                }
            }

            // Handle Enter key in URL input
            document.getElementById('urlInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    extractMetadata();
                }
            });

            // Settings functionality
            function toggleSettings() {
                const overlay = document.getElementById('settingsOverlay');
                const currentDisplay = overlay.style.display;
                overlay.style.display = currentDisplay === 'flex' ? 'none' : 'flex';
                
                if (overlay.style.display === 'flex') {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }

            // Close settings when clicking outside
            document.getElementById('settingsOverlay').addEventListener('click', function(e) {
                if (e.target === this) {
                    toggleSettings();
                }
            });

            // Prevent clicks inside settings content from closing
            document.querySelector('#settingsOverlay .settings-content').addEventListener('click', function(e) {
                e.stopPropagation();
            });

            function downloadDatabase() {
                const dbContent = JSON.stringify(videoDB, null, 2);
                const blob = new Blob([dbContent], {
                    type: 'application/json'
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'db.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }

            async function restoreDatabase(input) {
                if (!input.files || !input.files[0]) return;

                const formData = new FormData();
                formData.append('action', 'restore_db');
                formData.append('db_file', input.files[0]);

                try {
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error restoring database: ' + (data.error || 'Unknown error'));
                    }
                } catch (error) {
                    alert('Error uploading file: ' + error.message);
                }
            }

            async function showStats(videoId) {
                const modal = document.getElementById('statsModal');
                const totalElement = modal.querySelector('.stats-total');
                const listElement = modal.querySelector('.stats-list');

                // Clear previous content
                totalElement.textContent = 'Loading...';
                listElement.innerHTML = '';
                modal.style.display = 'flex';

                // Add base styles for the modal
                modal.querySelector('.stats-content').style.cssText = `
                    background: white;
                    padding: 24px;
                    border-radius: 16px;
                    width: 90%;
                    max-width: 480px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                `;

                // Style the header
                modal.querySelector('.stats-header').style.cssText = `
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 8px;
                `;

                modal.querySelector('.stats-header h2').style.cssText = `
                    font-size: 20px;
                    font-weight: 600;
                    margin: 0;
                `;

                // Style the close button
                modal.querySelector('.stats-close').style.cssText = `
                    cursor: pointer;
                    font-size: 24px;
                    width: 32px;
                    height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    margin: -8px;
                `;

                const video = videoDB[videoId];
                if (video) {
                    const visits = video.visits || [];
                    totalElement.textContent = visits.length === 1 
                        ? '1 Visit' 
                        : `${visits.length} Visits`;
                    
                    // Style the total element
                    totalElement.style.cssText = `
                        font-size: 15px;
                        font-weight: 500;
                        color: #666;
                        margin-bottom: 16px;
                        padding-bottom: 16px;
                        border-bottom: 1px solid #e1e4e8;
                    `;
                    
                    // Function to get platform info from referrer
                    function getPlatformInfo(referrer) {
                        if (!referrer || referrer === 'direct') {
                            return {
                                name: 'Direct Visit',
                                icon: 'data:image/svg+xml,' + encodeURIComponent(`
                                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16 4C9.37 4 4 9.37 4 16C4 22.63 9.37 28 16 28C22.63 28 28 22.63 28 16C28 9.37 22.63 4 16 4ZM16 6C21.54 6 26 10.46 26 16C26 21.54 21.54 26 16 26C10.46 26 6 21.54 6 16C6 10.46 10.46 6 16 6ZM15 8V17L21 20L22 18.5L17 16V8H15Z" fill="#666666"/>
                                    </svg>
                                `)
                            };
                        }
                        
                        const url = new URL(referrer);
                        const domain = url.hostname.toLowerCase();
                        
                        if (domain.includes('twitter') || domain.includes('x.com') || domain.includes('t.co')) {
                            return {
                                name: 'X',
                                icon: 'https://abs.twimg.com/responsive-web/client-web/icon-svg.ea5ff4aa.svg'
                            };
                        } else if (domain.includes('facebook') || domain.includes('fb.com')) {
                            return {
                                name: 'Facebook',
                                icon: 'https://static.xx.fbcdn.net/rsrc.php/yD/r/d4ZIVX-5C-b.ico'
                            };
                        } else if (domain.includes('instagram') || domain.includes('ig.com')) {
                            return {
                                name: 'Instagram',
                                icon: 'https://www.instagram.com/static/images/ico/favicon.ico/36b3ee2d91ed.ico'
                            };
                        } else if (domain.includes('linkedin')) {
                            return {
                                name: 'LinkedIn',
                                icon: 'https://static.licdn.com/sc/h/al2o9zrvru7aqj8e1x2rzsrca'
                            };
                        } else if (domain.includes('reddit')) {
                            return {
                                name: 'Reddit',
                                icon: 'https://www.redditstatic.com/desktop2x/img/favicon/favicon-32x32.png'
                            };
                        } else {
                            return {
                                name: domain || 'Unknown',
                                icon: 'data:image/svg+xml,' + encodeURIComponent(`
                                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16 4C9.37 4 4 9.37 4 16C4 22.63 9.37 28 16 28C22.63 28 28 22.63 28 16C28 9.37 22.63 4 16 4ZM16 6C21.54 6 26 10.46 26 16C26 21.54 21.54 26 16 26C10.46 26 6 21.54 6 16C6 10.46 10.46 6 16 6ZM14 10V22L22 16L14 10Z" fill="#666666"/>
                                    </svg>
                                `)
                            };
                        }
                    }
                    
                    // Style the list
                    listElement.style.cssText = `
                        list-style: none;
                        padding: 0;
                        margin: 0;
                        min-height: 100px;
                    `;
                    
                    // Show last 10 visits in reverse chronological order
                    listElement.innerHTML = visits.slice(-10).reverse().map(visit => {
                        const platform = getPlatformInfo(visit.referrer);
                        const visitDate = new Date(visit.timestamp * 1000);
                        const timeString = visitDate.toLocaleString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric',
                            hour: 'numeric',
                            minute: 'numeric',
                            hour12: true
                        });
                        
                        return `
                            <li style="
                                padding: 12px;
                                transition: background-color 0.2s ease;
                                cursor: default;
                                border-radius: 8px;
                                margin-bottom: 4px;
                            "
                            onmouseover="this.style.backgroundColor='#f8f9fa'"
                            onmouseout="this.style.backgroundColor='transparent'"
                            >
                                <div style="
                                    display: flex;
                                    align-items: center;
                                    gap: 12px;
                                ">
                                    <div style="
                                        width: 32px;
                                        height: 32px;
                                        border-radius: 50%;
                                        background: #f8f9fa;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        flex-shrink: 0;
                                        padding: 6px;
                                        border: 1px solid #e1e4e8;
                                    ">
                                        <img src="${platform.icon}" 
                                             alt="${platform.name}"
                                             style="width: 100%; height: 100%; object-fit: contain;">
                                    </div>
                                    <span style="
                                        font-size: 13px;
                                        color: #666;
                                        font-weight: 400;
                                        white-space: nowrap;
                                    ">${timeString}</span>
                                </div>
                            </li>
                        `;
                    }).join('');
                } else {
                    totalElement.textContent = 'No stats available';
                }
            }

            function closeStatsModal() {
                document.getElementById('statsModal').style.display = 'none';
            }

            // Close modal when clicking outside
            document.getElementById('statsModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeStatsModal();
                }
            });
        </script>
    </body>

    </html>
<?php
}
?>