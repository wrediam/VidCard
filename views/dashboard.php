<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VidCard</title>
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="icon" type="image/png" href="/images/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-collapsed {
            width: 0;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-slate-50">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="w-20 bg-white border-r border-slate-200 flex flex-col transition-all duration-300 overflow-hidden">
            <div class="p-4 border-b border-slate-200 flex items-center justify-center">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div id="channelList" class="flex-1 overflow-y-auto p-2"></div>
        </div>

        <!-- Channel Detail Panel -->
        <div id="channelPanel" class="hidden fixed inset-y-0 left-20 w-[480px] bg-white border-r border-slate-200 shadow-xl z-40 overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-slate-200 p-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold" id="channelPanelTitle">Channel Videos</h2>
                <button onclick="closeChannelPanel()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="channelPanelContent" class="p-4"></div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="/images/icon.png" alt="VidCard" class="w-8 h-8">
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">VidCard</h1>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="toggleSearch()" class="text-slate-600 hover:text-slate-900" title="Search">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                    <a href="/api-keys" class="text-slate-600 hover:text-slate-900" title="API Keys">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </a>
                    <?php if ($currentUser['email'] === 'will@wredia.com'): ?>
                    <a href="/admin" class="text-slate-600 hover:text-slate-900" title="Admin Panel">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <span class="text-sm text-slate-600"><?php echo htmlspecialchars($currentUser['email']); ?></span>
                    <button onclick="logout()" class="text-sm text-slate-600 hover:text-slate-900">Logout</button>
                </div>
            </header>

            <!-- Main Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <div class="max-w-4xl mx-auto space-y-8">
                    <!-- Process Video Section -->
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8">
                        <h2 class="text-xl font-semibold mb-4">Process YouTube Video</h2>
                        <p class="text-slate-600 mb-6">Paste a YouTube URL to create a shareable link with rich previews</p>
                        
                        <div class="space-y-4">
                            <input 
                                type="text" 
                                id="videoUrl" 
                                placeholder="https://www.youtube.com/watch?v=..."
                                class="w-full px-4 py-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent"
                            />
                            <button 
                                onclick="processVideo()"
                                id="processBtn"
                                class="w-full bg-slate-900 text-white py-3 rounded-md font-medium hover:bg-slate-800 transition disabled:opacity-50"
                            >
                                Process Video
                            </button>
                            <div id="processError" class="text-sm text-red-600 hidden"></div>
                        </div>
                    </div>

                    <!-- Result Preview -->
                    <div id="resultSection" class="bg-white rounded-lg shadow-sm border border-slate-200 p-8 hidden">
                        <h3 class="text-lg font-semibold mb-4">Your Shareable Link</h3>
                        <div class="space-y-4">
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <div class="flex items-center justify-between gap-4">
                                    <code id="shareUrl" class="text-sm text-slate-700 flex-1 break-all"></code>
                                    <button 
                                        onclick="copyUrl()"
                                        class="px-4 py-2 bg-slate-900 text-white text-sm rounded-md hover:bg-slate-800 transition whitespace-nowrap"
                                    >
                                        Copy
                                    </button>
                                </div>
                            </div>
                            <div id="videoPreview" class="border border-slate-200 rounded-lg overflow-hidden"></div>
                            <div id="copySuccess" class="text-sm text-green-600 hidden">✓ Copied to clipboard!</div>
                        </div>
                    </div>

                    <!-- Analytics Overview -->
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-8">
                        <h2 class="text-xl font-semibold mb-6">Analytics Overview</h2>
                        <div id="analyticsOverview" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                                <div class="text-sm text-blue-600 font-medium">Total Videos</div>
                                <div class="text-3xl font-bold text-blue-900" id="totalVideos">0</div>
                            </div>
                            <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
                                <div class="text-sm text-green-600 font-medium">Total Clicks</div>
                                <div class="text-3xl font-bold text-green-900" id="totalClicks">0</div>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg">
                                <div class="text-sm text-purple-600 font-medium">Avg Clicks/Video</div>
                                <div class="text-3xl font-bold text-purple-900" id="avgClicks">0</div>
                            </div>
                            <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-4 rounded-lg">
                                <div class="text-sm text-orange-600 font-medium">Top Performer</div>
                                <div class="text-sm font-bold text-orange-900 truncate" id="topVideo">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- Videos List -->
                    <div id="videosSection" class="space-y-4">
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-semibold">Your Videos</h2>
                            <span class="px-2 py-1 bg-slate-200 text-slate-600 text-xs rounded-full font-medium">Most Recent</span>
                        </div>
                        <div id="videosList"></div>
                    </div>
                </div>
            </main>
            
            <!-- Footer -->
            <footer class="border-t border-slate-200 py-3 px-6">
                <div class="flex items-center justify-center gap-4 text-xs text-slate-500">
                    <a href="/terms" class="hover:text-slate-700 transition">Terms</a>
                    <span>•</span>
                    <a href="/privacy" class="hover:text-slate-700 transition">Privacy</a>
                    <span>•</span>
                    <span>© <?php echo date('Y'); ?> VidCard</span>
                </div>
            </footer>
        </div>
    </div>

    <!-- Search Modal -->
    <div id="searchModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-start justify-center pt-20">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
            <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="Search videos or channels..."
                    class="flex-1 text-lg focus:outline-none"
                />
                <button onclick="toggleSearch()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="searchResults" class="max-h-96 overflow-y-auto p-4"></div>
        </div>
    </div>

    <!-- Stats Modal -->
    <div id="statsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200 flex items-center justify-between sticky top-0 bg-white">
                <h2 class="text-xl font-semibold">Video Statistics</h2>
                <button onclick="closeStats()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="statsContent" class="p-6"></div>
        </div>
    </div>

    <!-- Transcript Modal -->
    <div id="transcriptModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center overflow-y-auto p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full my-8">
            <div class="sticky top-0 bg-white border-b border-slate-200 p-6 flex items-center justify-between rounded-t-lg z-10">
                <h3 class="text-2xl font-bold text-slate-900">📝 Video Transcript</h3>
                <button onclick="closeTranscriptModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <div id="transcriptContent" class="prose max-w-none">
                    <div class="flex items-center justify-center py-8">
                        <svg class="animate-spin h-8 w-8 text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="ml-3 text-slate-600">Loading transcript...</p>
                    </div>
                </div>
            </div>
            <div class="sticky bottom-0 bg-slate-50 border-t border-slate-200 p-4 rounded-b-lg flex justify-between">
                <div class="flex gap-2">
                    <button 
                        onclick="copyTranscript()"
                        id="copyTranscriptBtn"
                        class="px-4 py-2 bg-slate-900 text-white rounded-md font-medium hover:bg-slate-800 transition"
                    >
                        Copy Transcript
                    </button>
                    <button 
                        onclick="openAITools()"
                        id="aiToolsBtn"
                        class="px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-md font-medium hover:from-purple-700 hover:to-blue-700 transition flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        AI Tools
                    </button>
                </div>
                <button 
                    onclick="closeTranscriptModal()"
                    class="px-4 py-2 border border-slate-300 text-slate-700 rounded-md font-medium hover:bg-slate-50 transition"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- AI Tools Modal -->
    <div id="aiToolsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center overflow-y-auto p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full my-8">
            <div class="sticky top-0 bg-gradient-to-r from-purple-600 to-blue-600 border-b border-purple-700 p-6 flex items-center justify-between rounded-t-lg z-10">
                <div class="flex items-center gap-3">
                    <button onclick="backToTranscript()" class="text-white hover:text-purple-100 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        AI Tools
                    </h3>
                </div>
                <button onclick="closeAIToolsModal()" class="text-white hover:text-purple-100 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <div id="aiToolsContent">
                    <div class="text-center py-8">
                        <div class="mb-6">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-100 to-blue-100 rounded-full mb-4">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                            </div>
                            <h4 class="text-xl font-semibold text-slate-900 mb-2">Generate Social Media Posts</h4>
                            <p class="text-slate-600 max-w-md mx-auto">Use AI to create 5 engaging post suggestions based on your video transcript. Perfect for promoting your content!</p>
                        </div>
                        <button 
                            onclick="generatePostSuggestions()"
                            id="generatePostsBtn"
                            class="px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg font-medium hover:from-purple-700 hover:to-blue-700 transition shadow-lg hover:shadow-xl"
                        >
                            Generate Post Suggestions
                        </button>
                    </div>
                    <div id="postSuggestionsContainer" class="hidden">
                        <div class="mb-4 flex items-center justify-between">
                            <h4 class="text-lg font-semibold text-slate-900">Your Post Suggestions</h4>
                            <button 
                                onclick="generatePostSuggestions()"
                                id="regeneratePostsBtn"
                                class="px-4 py-2 text-sm bg-slate-100 text-slate-700 rounded-md font-medium hover:bg-slate-200 transition flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Regenerate
                            </button>
                        </div>
                        <div id="postSuggestionsList" class="space-y-3"></div>
                    </div>
                </div>
            </div>
            <div class="sticky bottom-0 bg-slate-50 border-t border-slate-200 p-4 rounded-b-lg flex justify-end">
                <button 
                    onclick="closeAIToolsModal()"
                    class="px-4 py-2 border border-slate-300 text-slate-700 rounded-md font-medium hover:bg-slate-50 transition"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-center mb-2">Delete Video?</h3>
                <p class="text-sm text-slate-600 text-center mb-6">
                    This will permanently delete this video. <strong class="text-red-600">Any shared links will stop working immediately.</strong> This action cannot be undone.
                </p>
                <div class="flex gap-3">
                    <button 
                        onclick="closeDeleteModal()" 
                        class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition font-medium"
                    >
                        Cancel
                    </button>
                    <button 
                        onclick="confirmDelete()" 
                        id="confirmDeleteBtn"
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium"
                    >
                        Delete Video
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let channels = {};
        let currentChannelName = null;

        // Load videos on page load
        loadVideos();

        function toggleSearch() {
            const modal = document.getElementById('searchModal');
            modal.classList.toggle('hidden');
            if (!modal.classList.contains('hidden')) {
                document.getElementById('searchInput').focus();
            }
        }

        function loadVideos() {
            fetch('/', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_videos' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    channels = data.channels;
                    renderChannels();
                    renderVideos();
                }
            });
        }

        function renderChannels() {
            const list = document.getElementById('channelList');
            list.innerHTML = '';
            
            Object.values(channels).forEach(channel => {
                const div = document.createElement('div');
                div.className = 'relative group mb-3';
                div.onclick = () => openChannelPanel(channel);
                
                const videoCount = channel.videos.length;
                
                div.innerHTML = `
                    <div class="relative cursor-pointer">
                        ${channel.thumbnail ? 
                            `<img src="${channel.thumbnail}" class="w-14 h-14 rounded-full mx-auto border-2 border-slate-200 hover:border-slate-400 transition" title="${channel.name}" />` : 
                            `<div class="w-14 h-14 rounded-full mx-auto bg-slate-200 flex items-center justify-center text-slate-600 font-bold text-xl">${channel.name.charAt(0)}</div>`
                        }
                        ${videoCount > 0 ? `
                            <div class="absolute -top-1 -right-1 bg-blue-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-semibold">
                                ${videoCount > 99 ? '99+' : videoCount}
                            </div>
                        ` : ''}
                    </div>
                    <div class="text-xs text-center mt-1 text-slate-600 truncate px-1" title="${channel.name}">
                        ${channel.name.length > 12 ? channel.name.substring(0, 12) + '...' : channel.name}
                    </div>
                `;
                list.appendChild(div);
            });
        }

        function openChannelPanel(channel) {
            currentChannelName = channel.name;
            const panel = document.getElementById('channelPanel');
            const title = document.getElementById('channelPanelTitle');
            const content = document.getElementById('channelPanelContent');
            
            title.textContent = channel.name;
            
            content.innerHTML = `
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200">
                    ${channel.thumbnail ? 
                        `<img src="${channel.thumbnail}" class="w-16 h-16 rounded-full" />` : 
                        `<div class="w-16 h-16 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-bold text-2xl">${channel.name.charAt(0)}</div>`
                    }
                    <div>
                        <h3 class="font-semibold text-lg">${channel.name}</h3>
                        <p class="text-sm text-slate-600">${channel.videos.length} videos</p>
                    </div>
                </div>
                <div class="space-y-3">
                    ${channel.videos.map(video => `
                        <div class="bg-slate-50 rounded-lg p-4 hover:bg-slate-100 transition">
                            <div class="flex gap-3">
                                <img src="${video.thumbnail_url}" class="w-32 h-20 object-cover rounded flex-shrink-0" />
                                <div class="flex-1 min-w-0 flex flex-col">
                                    <h4 class="font-medium text-sm mb-2 line-clamp-2">${video.title}</h4>
                                    <div class="mt-auto">
                                        <div class="mb-2">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full font-medium text-xs">
                                                ${video.visit_count || 0} clicks
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs flex-wrap">
                                            <button onclick="event.stopPropagation(); showStats('${video.video_id}')" class="text-slate-600 hover:text-slate-900 underline">
                                                Stats
                                            </button>
                                            <span class="text-slate-300">•</span>
                                            <button onclick="event.stopPropagation(); copyVideoUrl('${video.video_id}', event)" class="text-slate-600 hover:text-slate-900 underline">
                                                Copy
                                            </button>
                                            <span class="text-slate-300">•</span>
                                            ${video.transcript_unavailable ? 
                                                '<span class="text-slate-400 cursor-not-allowed">Transcript Unavailable</span>' : 
                                                `<button onclick="event.stopPropagation(); viewTranscript('${video.video_id}', ${video.transcript_text ? 'true' : 'false'})" class="text-blue-600 hover:text-blue-800 underline">
                                                    ${video.transcript_text ? 'View Transcript' : 'Retrieve Transcript'}
                                                </button>`
                                            }
                                            <span class="text-slate-300">•</span>
                                            <button onclick="event.stopPropagation(); showDeleteModal('${video.video_id}')" class="text-red-600 hover:text-red-800 underline">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
            
            panel.classList.remove('hidden');
        }

        function closeChannelPanel() {
            document.getElementById('channelPanel').classList.add('hidden');
            currentChannelName = null;
        }

        function renderVideos(filter = null) {
            const list = document.getElementById('videosList');
            list.innerHTML = '';
            
            let videos = [];
            Object.values(channels).forEach(channel => {
                if (!filter || channel.name === filter) {
                    videos.push(...channel.videos);
                }
            });
            
            if (videos.length === 0) {
                list.innerHTML = '<p class="text-slate-500 text-center py-8">No videos yet. Process your first YouTube video above!</p>';
                return;
            }
            
            // Update analytics overview with all videos
            updateAnalytics(videos);
            
            // Sort by created date (most recent first) and limit to 3
            videos.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            const recentVideos = videos.slice(0, 3);
            
            recentVideos.forEach(video => {
                const div = document.createElement('div');
                div.className = 'bg-white border border-slate-200 rounded-lg p-5 hover:shadow-md transition';
                div.innerHTML = `
                    <div class="flex gap-4">
                        <img src="${video.thumbnail_url}" class="w-48 h-28 object-cover rounded flex-shrink-0" />
                        <div class="flex-1 min-w-0 flex flex-col">
                            <h3 class="font-semibold mb-1 line-clamp-2 text-base">${video.title}</h3>
                            <p class="text-sm text-slate-600 mb-3">${video.channel_name}</p>
                            <div class="mt-auto">
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full font-medium text-sm">
                                        ${video.visit_count || 0} clicks
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-sm flex-wrap">
                                    <button onclick="showStats('${video.video_id}')" class="text-slate-600 hover:text-slate-900 underline">
                                        Stats
                                    </button>
                                    <span class="text-slate-300">•</span>
                                    <button onclick="copyVideoUrl('${video.video_id}', event)" class="text-slate-600 hover:text-slate-900 underline transition-colors">
                                        Copy
                                    </button>
                                    <span class="text-slate-300">•</span>
                                    ${video.transcript_unavailable ? 
                                        '<span class="text-slate-400 cursor-not-allowed">Transcript Unavailable</span>' : 
                                        `<button onclick="viewTranscript('${video.video_id}', ${video.transcript_text ? 'true' : 'false'})" class="text-blue-600 hover:text-blue-800 underline transition-colors">
                                            ${video.transcript_text ? 'View Transcript' : 'Retrieve Transcript'}
                                        </button>`
                                    }
                                    <span class="text-slate-300">•</span>
                                    <button onclick="showDeleteModal('${video.video_id}')" class="text-red-600 hover:text-red-800 underline transition-colors">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                list.appendChild(div);
            });
        }

        function updateAnalytics(videos) {
            const totalVideos = videos.length;
            const totalClicks = videos.reduce((sum, v) => sum + (parseInt(v.visit_count) || 0), 0);
            const avgClicks = totalVideos > 0 ? Math.round(totalClicks / totalVideos) : 0;
            const topVideo = videos.reduce((top, v) => 
                (parseInt(v.visit_count) || 0) > (parseInt(top.visit_count) || 0) ? v : top
            , videos[0] || {});

            document.getElementById('totalVideos').textContent = totalVideos;
            document.getElementById('totalClicks').textContent = totalClicks;
            document.getElementById('avgClicks').textContent = avgClicks;
            document.getElementById('topVideo').textContent = topVideo.title || '-';
            document.getElementById('topVideo').title = topVideo.title || '';
        }

        function filterByChannel(channelName) {
            renderVideos(channelName);
        }

        function processVideo() {
            const url = document.getElementById('videoUrl').value;
            const btn = document.getElementById('processBtn');
            const error = document.getElementById('processError');
            
            error.classList.add('hidden');
            
            if (!url) {
                error.textContent = 'Please enter a YouTube URL';
                error.classList.remove('hidden');
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = `
                <div class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Processing...</span>
                </div>
            `;
            
            fetch('/', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'process_video', url })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('shareUrl').textContent = data.share_url;
                    document.getElementById('videoPreview').innerHTML = `
                        <img src="${data.video.thumbnail_url}" class="w-full" />
                        <div class="p-4">
                            <h4 class="font-semibold">${data.video.title}</h4>
                            <p class="text-sm text-slate-600">${data.video.channel_name}</p>
                        </div>
                    `;
                    document.getElementById('resultSection').classList.remove('hidden');
                    document.getElementById('videoUrl').value = '';
                    loadVideos();
                } else {
                    error.textContent = data.error || 'Failed to process video';
                    error.classList.remove('hidden');
                }
            })
            .catch(err => {
                error.textContent = 'Network error. Please try again.';
                error.classList.remove('hidden');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = 'Process Video';
            });
        }

        function copyUrl() {
            const url = document.getElementById('shareUrl').textContent;
            navigator.clipboard.writeText(url);
            const success = document.getElementById('copySuccess');
            success.classList.remove('hidden');
            setTimeout(() => success.classList.add('hidden'), 2000);
        }

        function copyVideoUrl(videoId, event) {
            const url = `<?php echo APP_URL; ?>/?v=${videoId}`;
            navigator.clipboard.writeText(url).then(() => {
                // Get the button that was clicked
                const button = event ? event.target : null;
                if (button) {
                    const originalText = button.textContent;
                    button.textContent = '✓ Copied!';
                    button.classList.add('text-green-600', 'font-semibold');
                    
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.classList.remove('text-green-600', 'font-semibold');
                    }, 2000);
                }
            });
        }

        function showStats(videoId) {
            fetch('/', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_stats', video_id: videoId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.stats) {
                    const content = document.getElementById('statsContent');
                    
                    // Helper function to format dates in local timezone
                    const formatLocalDate = (dateString) => {
                        if (!dateString) return '-';
                        const date = new Date(dateString + 'Z'); // Add Z to treat as UTC
                        return date.toLocaleDateString();
                    };
                    
                    const formatLocalDateTime = (dateString) => {
                        if (!dateString) return '-';
                        const date = new Date(dateString + 'Z'); // Add Z to treat as UTC
                        return date.toLocaleString('en-US', {
                            month: 'numeric',
                            day: 'numeric',
                            year: 'numeric',
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                    };
                    
                    const firstVisit = formatLocalDate(data.stats.first_visit);
                    const lastVisit = formatLocalDateTime(data.stats.last_visit);
                    
                    content.innerHTML = `
                        <div class="space-y-6">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                                    <div class="text-3xl font-bold text-blue-900">${data.stats.total_visits || 0}</div>
                                    <div class="text-sm text-blue-600 font-medium">Total Clicks</div>
                                </div>
                                <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
                                    <div class="text-lg font-bold text-green-900">${firstVisit}</div>
                                    <div class="text-sm text-green-600 font-medium">First Click</div>
                                </div>
                                <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg">
                                    <div class="text-lg font-bold text-purple-900">${lastVisit}</div>
                                    <div class="text-sm text-purple-600 font-medium">Last Click</div>
                                </div>
                            </div>
                            ${data.stats.recent_visits && data.stats.recent_visits.length > 0 ? `
                                <div>
                                    <h3 class="font-semibold mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        Recent Click Activity
                                    </h3>
                                    <div class="space-y-2 max-h-96 overflow-y-auto">
                                        ${data.stats.recent_visits.map(visit => `
                                            <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                                                <div class="flex justify-between items-start gap-4">
                                                    <div class="flex-1">
                                                        <div class="text-sm font-medium text-slate-900">
                                                            ${formatLocalDateTime(visit.visited_at)}
                                                        </div>
                                                        <div class="text-xs text-slate-500 mt-1">
                                                            Referrer: ${visit.referrer === 'direct' ? 'Direct visit' : visit.referrer}
                                                        </div>
                                                    </div>
                                                    <div class="text-xs text-slate-400">
                                                        ${visit.ip_address || 'Unknown IP'}
                                                    </div>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : '<p class="text-slate-500 text-center py-8">No clicks yet</p>'}
                        </div>
                    `;
                    document.getElementById('statsModal').classList.remove('hidden');
                }
            });
        }

        function closeStats() {
            document.getElementById('statsModal').classList.add('hidden');
        }

        function logout() {
            fetch('/', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' })
            })
            .then(() => {
                window.location.href = '/';
            });
        }

        // Search functionality
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const query = e.target.value;
                if (query.length < 2) {
                    document.getElementById('searchResults').innerHTML = '';
                    return;
                }
                
                fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'search', query })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const results = document.getElementById('searchResults');
                        if (data.results.length === 0) {
                            results.innerHTML = '<p class="text-slate-500 text-center py-4">No results found</p>';
                        } else {
                            results.innerHTML = data.results.map(video => `
                                <div class="flex gap-3 p-3 hover:bg-slate-50 rounded-lg">
                                    <img src="${video.thumbnail_url}" class="w-24 h-16 object-cover rounded" />
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-sm truncate">${video.title}</div>
                                        <div class="text-xs text-slate-500 mb-2">${video.channel_name}</div>
                                        <button onclick="copyVideoUrl('${video.video_id}', event)" class="text-xs text-slate-600 hover:text-slate-900 underline transition-colors">
                                            Copy Link
                                        </button>
                                    </div>
                                </div>
                            `).join('');
                        }
                    }
                });
            }, 300);
        });

        // Enter key handler
        document.getElementById('videoUrl').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') processVideo();
        });

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                toggleSearch();
                closeStats();
                closeDeleteModal();
                closeTranscriptModal();
            }
        });

        // Transcript functionality
        let currentTranscript = '';
        let currentVideoId = '';

        async function viewTranscript(videoId, hasTranscript) {
            currentVideoId = videoId;
            document.getElementById('transcriptModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            const content = document.getElementById('transcriptContent');
            content.innerHTML = `
                <div class="flex items-center justify-center py-8">
                    <svg class="animate-spin h-8 w-8 text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="ml-3 text-slate-600">${hasTranscript ? 'Loading transcript...' : 'Fetching transcript...'}</p>
                </div>
            `;

            try {
                const action = hasTranscript ? 'get_transcript' : 'fetch_transcript';
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: action,
                        video_id: videoId 
                    })
                });

                const data = await response.json();

                if (data.success && data.unavailable) {
                    // Transcript is unavailable - close modal and reload videos to update UI
                    closeTranscriptModal();
                    loadVideos();
                    return;
                }

                if (data.success && data.transcript) {
                    currentTranscript = data.transcript;
                    content.innerHTML = `
                        <div class="whitespace-pre-wrap text-slate-700 leading-relaxed">
                            ${escapeHtml(data.transcript)}
                        </div>
                        ${data.fetched_at ? `<p class="text-xs text-slate-500 mt-4">Fetched: ${new Date(data.fetched_at).toLocaleString()}</p>` : ''}
                    `;
                    
                    if (!hasTranscript) {
                        loadVideos();
                    }
                } else {
                    content.innerHTML = `
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-slate-600 font-medium">Transcript not available</p>
                            <p class="text-sm text-slate-500 mt-2">This video may not have captions enabled.</p>
                        </div>
                    `;
                    currentTranscript = '';
                }
            } catch (error) {
                console.error('Transcript error:', error);
                content.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-red-600 font-medium">Error loading transcript</p>
                        <p class="text-sm text-slate-500 mt-2">${error.message}</p>
                    </div>
                `;
                currentTranscript = '';
            }
        }

        function closeTranscriptModal() {
            document.getElementById('transcriptModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentTranscript = '';
        }

        function copyTranscript() {
            if (!currentTranscript) return;
            
            navigator.clipboard.writeText(currentTranscript).then(() => {
                const btn = document.getElementById('copyTranscriptBtn');
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                btn.classList.add('bg-green-600');
                btn.classList.remove('bg-slate-900');
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('bg-green-600');
                    btn.classList.add('bg-slate-900');
                }, 2000);
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // AI Tools functionality
        async function openAITools() {
            if (!currentTranscript) {
                alert('Please load the transcript first');
                return;
            }
            
            // Hide transcript modal and show AI tools modal
            document.getElementById('transcriptModal').classList.add('hidden');
            document.getElementById('aiToolsModal').classList.remove('hidden');
            
            // Try to load existing suggestions
            await loadExistingPostSuggestions();
        }

        async function loadExistingPostSuggestions() {
            if (!currentVideoId) return;

            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'get_post_suggestions',
                        video_id: currentVideoId 
                    })
                });

                const data = await response.json();

                const initialView = document.getElementById('aiToolsContent').querySelector('.text-center');
                const suggestionsContainer = document.getElementById('postSuggestionsContainer');

                if (data.success && data.has_suggestions && data.suggestions) {
                    // Hide initial view and show existing suggestions
                    if (initialView) initialView.classList.add('hidden');
                    suggestionsContainer.classList.remove('hidden');
                    renderPostSuggestions(data.suggestions);
                } else {
                    // Show initial view (no suggestions yet)
                    if (initialView) initialView.classList.remove('hidden');
                    suggestionsContainer.classList.add('hidden');
                }
            } catch (error) {
                console.error('Load suggestions error:', error);
                // Show initial view on error
                const initialView = document.getElementById('aiToolsContent').querySelector('.text-center');
                const suggestionsContainer = document.getElementById('postSuggestionsContainer');
                if (initialView) initialView.classList.remove('hidden');
                suggestionsContainer.classList.add('hidden');
            }
        }

        function closeAIToolsModal() {
            document.getElementById('aiToolsModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function backToTranscript() {
            document.getElementById('aiToolsModal').classList.add('hidden');
            document.getElementById('transcriptModal').classList.remove('hidden');
        }

        async function generatePostSuggestions() {
            if (!currentVideoId) {
                alert('Video ID not found');
                return;
            }

            const btn = document.getElementById('generatePostsBtn');
            const regenerateBtn = document.getElementById('regeneratePostsBtn');
            const initialView = document.getElementById('aiToolsContent').querySelector('.text-center');
            const suggestionsContainer = document.getElementById('postSuggestionsContainer');
            
            // Disable buttons and show loading state
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `
                    <div class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Generating...</span>
                    </div>
                `;
            }
            if (regenerateBtn) {
                regenerateBtn.disabled = true;
            }

            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'generate_post_suggestions',
                        video_id: currentVideoId 
                    })
                });

                const data = await response.json();

                if (data.success && data.suggestions) {
                    // Hide initial view and show suggestions
                    if (initialView) initialView.classList.add('hidden');
                    suggestionsContainer.classList.remove('hidden');
                    
                    // Render suggestions
                    renderPostSuggestions(data.suggestions);
                } else {
                    alert('Error: ' + (data.error || 'Failed to generate post suggestions'));
                }
            } catch (error) {
                console.error('Generate error:', error);
                alert('Network error. Please try again.');
            } finally {
                // Re-enable buttons
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Generate Post Suggestions';
                }
                if (regenerateBtn) {
                    regenerateBtn.disabled = false;
                }
            }
        }

        function renderPostSuggestions(suggestions) {
            const list = document.getElementById('postSuggestionsList');
            
            list.innerHTML = suggestions.map((suggestion, index) => `
                <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-lg p-4 hover:shadow-md transition">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-500 to-blue-500 text-white rounded-full font-bold text-sm">
                                ${index + 1}
                            </div>
                            <span class="text-xs font-medium text-slate-500">Post Suggestion</span>
                        </div>
                        <button 
                            onclick="copyPostSuggestion(${index}, '${escapeForAttribute(suggestion.post_text)}')"
                            id="copyBtn${index}"
                            class="px-3 py-1.5 bg-white border border-slate-300 text-slate-700 rounded-md text-sm font-medium hover:bg-slate-50 transition flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Copy
                        </button>
                    </div>
                    <p class="text-slate-700 leading-relaxed">${escapeHtml(suggestion.post_text)}</p>
                </div>
            `).join('');
        }

        function copyPostSuggestion(index, text) {
            // Decode the escaped text
            const textarea = document.createElement('textarea');
            textarea.innerHTML = text;
            const decodedText = textarea.value;
            
            navigator.clipboard.writeText(decodedText).then(() => {
                const btn = document.getElementById(`copyBtn${index}`);
                const originalHTML = btn.innerHTML;
                
                btn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Copied!
                `;
                btn.classList.add('bg-green-50', 'border-green-500', 'text-green-700');
                btn.classList.remove('bg-white', 'border-slate-300', 'text-slate-700');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('bg-green-50', 'border-green-500', 'text-green-700');
                    btn.classList.add('bg-white', 'border-slate-300', 'text-slate-700');
                }, 2000);
            });
        }

        function escapeForAttribute(text) {
            return text
                .replace(/&/g, '&amp;')
                .replace(/'/g, '&#39;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, ' ')
                .replace(/\r/g, '');
        }

        // Delete video functionality
        let videoToDelete = null;

        function showDeleteModal(videoId) {
            videoToDelete = videoId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            videoToDelete = null;
            document.getElementById('deleteModal').classList.add('hidden');
        }

        async function confirmDelete() {
            if (!videoToDelete) return;

            const btn = document.getElementById('confirmDeleteBtn');
            btn.disabled = true;
            btn.innerHTML = `
                <div class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Deleting...</span>
                </div>
            `;

            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'delete_video',
                        video_id: videoToDelete 
                    })
                });

                const data = await response.json();

                if (data.success) {
                    closeDeleteModal();
                    loadVideos(); // Reload the video list
                    closeChannelPanel(); // Close channel panel if open
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete video'));
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('Network error. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Delete Video';
            }
        }
    </script>
</body>
</html>
