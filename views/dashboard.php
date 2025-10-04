<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VidCard</title>
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="icon" type="image/png" href="/images/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://www.youtube.com/iframe_api"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-collapsed {
            width: 0;
            overflow: hidden;
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
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
            <div class="sticky top-0 bg-white border-b border-slate-200 p-6 rounded-t-lg z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-2xl font-bold text-slate-900">📝 Video Transcript</h3>
                    <button onclick="closeTranscriptModal()" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <!-- View Toggle -->
                <div id="transcriptViewToggle" class="hidden flex gap-2 bg-slate-100 p-1 rounded-lg w-fit">
                    <button 
                        onclick="switchTranscriptView('plain')"
                        id="plainViewBtn"
                        class="px-4 py-2 rounded-md font-medium text-sm transition bg-white text-slate-900 shadow-sm"
                    >
                        Plain Text
                    </button>
                    <button 
                        onclick="switchTranscriptView('timestamped')"
                        id="timestampedViewBtn"
                        class="px-4 py-2 rounded-md font-medium text-sm transition text-slate-600 hover:text-slate-900"
                    >
                        Timestamped
                    </button>
                </div>
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
                <button 
                    onclick="copyTranscript()"
                    id="copyTranscriptBtn"
                    class="px-4 py-2 bg-slate-900 text-white rounded-md font-medium hover:bg-slate-800 transition"
                >
                    Copy Transcript
                </button>
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
                    <button id="aiToolsBackBtn" onclick="backToAIToolsSelection()" class="text-white hover:text-purple-100 transition hidden">
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
            <div class="p-6 overflow-y-auto" style="max-height: calc(100vh - 200px);">
                <div id="aiToolsContent">
                    <!-- AI Tools Selection -->
                    <div id="aiToolsSelection" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <!-- Social Media Posts Tool -->
                        <div class="flex flex-col text-center py-6 px-4 border border-slate-200 rounded-lg hover:border-purple-300 transition">
                            <div class="flex-1">
                                <div class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-100 to-blue-100 rounded-full mb-3">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-slate-900 mb-1">Social Media Posts</h4>
                                <p class="text-sm text-slate-600 max-w-md mx-auto mb-4">Create 5 engaging post suggestions based on your video transcript.</p>
                            </div>
                            <div class="flex justify-center">
                                <button 
                                    onclick="handlePostSuggestions()"
                                    id="generatePostsBtn"
                                    class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg font-medium hover:from-purple-700 hover:to-blue-700 transition shadow-lg hover:shadow-xl"
                                >
                                    Generate Posts
                                </button>
                            </div>
                        </div>
                        
                        <!-- Clip Suggestions Tool -->
                        <div class="flex flex-col text-center py-6 px-4 border border-slate-200 rounded-lg hover:border-orange-300 transition">
                            <div class="flex-1">
                                <div class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-br from-orange-100 to-red-100 rounded-full mb-3">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-slate-900 mb-1">Clip Suggestions</h4>
                                <p class="text-sm text-slate-600 max-w-md mx-auto mb-4">AI-powered clip suggestions.</p>
                            </div>
                            <div class="flex justify-center">
                                <button 
                                    onclick="handleClipSuggestions()"
                                    id="generateClipsBtn"
                                    class="px-6 py-2.5 bg-gradient-to-r from-orange-600 to-red-600 text-white rounded-lg font-medium hover:from-orange-700 hover:to-red-700 transition shadow-lg hover:shadow-xl"
                                >
                                    Generate Clips
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Clip Suggestions Container -->
                    <div id="clipSuggestionsContainer" class="hidden">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h4 class="text-base font-semibold text-slate-900">Your Clip Suggestions</h4>
                                <p class="text-xs text-slate-600 mt-0.5">AI selected clip moments from your video</p>
                            </div>
                            <button 
                                onclick="generateClipSuggestions()"
                                id="regenerateClipsBtn"
                                class="px-3 py-1.5 text-xs bg-slate-100 text-slate-700 rounded-md font-medium hover:bg-slate-200 transition flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Regenerate
                            </button>
                        </div>
                        
                        <!-- Clip Preview -->
                        <div class="max-w-4xl mx-auto">
                            <!-- Navigation Counter -->
                            <div class="flex items-center justify-center gap-3 mb-3">
                                <button 
                                    onclick="previousClip()"
                                    id="prevClipBtn"
                                    class="p-1.5 rounded-full bg-slate-100 hover:bg-slate-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <span class="text-xs font-medium text-slate-600">
                                    <span id="currentClipIndex">1</span> / <span id="totalClips">4</span>
                                </span>
                                <button 
                                    onclick="nextClip()"
                                    id="nextClipBtn"
                                    class="p-1.5 rounded-full bg-slate-100 hover:bg-slate-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Clip Card -->
                            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                                <!-- Clip Title & Info -->
                                <div class="p-4 bg-gradient-to-r from-orange-50 to-red-50 border-b border-slate-200">
                                    <h5 id="clipTitle" class="text-lg font-bold text-slate-900 mb-2"></h5>
                                    <p id="clipReason" class="text-sm text-slate-600 mb-2"></p>
                                    <div class="flex items-center gap-4 text-xs text-slate-500">
                                        <span id="clipDuration" class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span></span>
                                        </span>
                                        <span id="clipTimestamp" class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            <span></span>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- YouTube Embed -->
                                <div id="clipEmbed" class="relative" style="padding-bottom: 56.25%; height: 0;">
                                    <!-- Embed will be inserted here -->
                                </div>
                                
                                <!-- Interactive Timeline -->
                                <div class="p-4 bg-slate-50 border-t border-slate-200">
                                    <div class="mb-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="text-xs font-semibold text-slate-700">Adjust Clip Timing</label>
                                            <div class="flex items-center gap-3 text-xs text-slate-600">
                                                <span id="clipStartLabel" class="font-mono">0:00</span>
                                                <span>→</span>
                                                <span id="clipEndLabel" class="font-mono">0:00</span>
                                                <span class="text-slate-400">|</span>
                                                <span id="clipDurationLabel" class="font-medium">0s</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Timeline Container -->
                                        <div class="relative">
                                            <!-- Zoom Controls -->
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center gap-2">
                                                    <button id="playPauseBtn" onclick="togglePlayPause()" class="p-2 bg-blue-500 hover:bg-blue-600 rounded-full text-white transition shadow-md">
                                                        <svg id="playIcon" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M8 5v14l11-7z"/>
                                                        </svg>
                                                        <svg id="pauseIcon" class="w-4 h-4 hidden" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>
                                                        </svg>
                                                    </button>
                                                    <div class="text-xs text-slate-600 font-medium">Timeline Controls</div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button onclick="zoomOut()" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 rounded text-xs font-medium transition">
                                                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"></path>
                                                        </svg>
                                                        Zoom Out
                                                    </button>
                                                    <button onclick="zoomIn()" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 rounded text-xs font-medium transition">
                                                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                                        </svg>
                                                        Zoom In
                                                    </button>
                                                    <button onclick="resetZoom()" class="px-2 py-1 bg-slate-200 hover:bg-slate-300 rounded text-xs font-medium transition">Reset</button>
                                                </div>
                                            </div>
                                            
                                            <!-- Full video timeline bar -->
                                            <div class="relative h-12 bg-slate-200 rounded-lg overflow-hidden cursor-pointer" id="timelineBar">
                                                <!-- Playhead indicator -->
                                                <div 
                                                    id="playhead" 
                                                    class="absolute top-0 h-full w-0.5 bg-blue-600 z-20 pointer-events-none transition-all duration-100"
                                                    style="left: 0%;"
                                                >
                                                    <div class="absolute -top-1 left-1/2 -translate-x-1/2 w-3 h-3 bg-blue-600 rounded-full shadow-lg"></div>
                                                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-3 h-3 bg-blue-600 rounded-full shadow-lg"></div>
                                                </div>
                                                
                                                <!-- Highlighted clip segment -->
                                                <div 
                                                    id="clipSegment" 
                                                    class="absolute top-0 h-full bg-gradient-to-r from-orange-400 to-red-500 opacity-80 transition-all duration-150 cursor-grab active:cursor-grabbing"
                                                    style="left: 0%; width: 100%;"
                                                >
                                                    <!-- Start handle -->
                                                    <div 
                                                        id="startHandle" 
                                                        class="absolute left-0 top-0 h-full w-3 bg-orange-600 cursor-ew-resize hover:bg-orange-700 transition-colors flex items-center justify-center group"
                                                        draggable="false"
                                                    >
                                                        <div class="w-0.5 h-6 bg-white opacity-70 group-hover:opacity-100"></div>
                                                        <!-- Start tag -->
                                                        <div class="absolute -left-14 top-1/2 -translate-y-1/2 px-2 py-1 bg-orange-600 text-white text-xs font-semibold rounded-l-md whitespace-nowrap shadow-md">
                                                            Start <span class="ml-0.5">▶</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- End handle -->
                                                    <div 
                                                        id="endHandle" 
                                                        class="absolute right-0 top-0 h-full w-3 bg-red-600 cursor-ew-resize hover:bg-red-700 transition-colors flex items-center justify-center group"
                                                        draggable="false"
                                                    >
                                                        <div class="w-0.5 h-6 bg-white opacity-70 group-hover:opacity-100"></div>
                                                        <!-- End tag -->
                                                        <div class="absolute -right-12 top-1/2 -translate-y-1/2 px-2 py-1 bg-red-600 text-white text-xs font-semibold rounded-r-md whitespace-nowrap shadow-md">
                                                            <span class="mr-0.5">◀</span> End
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Time markers -->
                                            <div id="timeMarkers" class="flex justify-between mt-1 text-xs text-slate-500 px-1">
                                                <!-- Markers will be generated dynamically -->
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex gap-2">
                                        <button 
                                            onclick="resetToAISuggestion()"
                                            id="resetClipBtn"
                                            class="px-4 py-2.5 bg-slate-200 text-slate-700 rounded-lg font-medium hover:bg-slate-300 transition flex items-center justify-center gap-2"
                                            title="Reset to AI suggestion"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            Reset
                                        </button>
                                        <button 
                                            onclick="downloadClip()"
                                            id="downloadClipBtn"
                                            class="flex-1 px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-lg font-medium hover:from-green-700 hover:to-emerald-700 transition shadow-md hover:shadow-lg flex items-center justify-center gap-2"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                            Download Clip
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="postSuggestionsContainer" class="hidden">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h4 class="text-base font-semibold text-slate-900">Your Post Suggestions</h4>
                                <p class="text-xs text-slate-600 mt-0.5">Preview how your post will look on Twitter</p>
                            </div>
                            <button 
                                onclick="generatePostSuggestions()"
                                id="regeneratePostsBtn"
                                class="px-3 py-1.5 text-xs bg-slate-100 text-slate-700 rounded-md font-medium hover:bg-slate-200 transition flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Regenerate
                            </button>
                        </div>
                        
                        <!-- Twitter Card Preview -->
                        <div class="max-w-xl mx-auto">
                            <!-- Navigation Counter -->
                            <div class="flex items-center justify-center gap-3 mb-3">
                                <button 
                                    onclick="previousSuggestion()"
                                    id="prevBtn"
                                    class="p-1.5 rounded-full bg-slate-100 hover:bg-slate-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <span class="text-xs font-medium text-slate-600">
                                    <span id="currentIndex">1</span> / <span id="totalSuggestions">5</span>
                                </span>
                                <button 
                                    onclick="nextSuggestion()"
                                    id="nextBtn"
                                    class="p-1.5 rounded-full bg-slate-100 hover:bg-slate-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg class="w-4 h-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Twitter Card -->
                            <div id="twitterCard" class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition">
                                <!-- Post Text -->
                                <div class="p-3">
                                    <div class="flex items-start gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1 mb-1">
                                                <span class="font-bold text-slate-900 text-sm">Social Media Account</span>
                                                <svg class="w-3.5 h-3.5 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="text-slate-500 text-xs">@username · now</span>
                                            </div>
                                            <p id="postText" class="text-slate-900 text-sm leading-relaxed whitespace-pre-wrap"></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Video Card -->
                                <div id="videoCard" class="border-t border-slate-200">
                                    <!-- Video will be inserted here -->
                                </div>
                                
                                <!-- Twitter Actions -->
                                <div class="border-t border-slate-200 px-3 py-2 flex items-center justify-around text-slate-500">
                                    <button class="flex items-center gap-1 hover:text-blue-500 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                    </button>
                                    <button class="flex items-center gap-1 hover:text-green-500 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                    <button class="flex items-center gap-1 hover:text-red-500 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                        </svg>
                                    </button>
                                    <button class="flex items-center gap-1 hover:text-blue-500 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Copy Button -->
                            <div class="mt-3 text-center">
                                <button 
                                    onclick="copyCurrentPost()"
                                    id="copyPostBtn"
                                    class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg text-sm font-medium hover:from-purple-700 hover:to-blue-700 transition shadow-lg hover:shadow-xl flex items-center gap-2 mx-auto"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    Copy Post with Link
                                </button>
                            </div>
                        </div>
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
                                            ${!video.transcript_unavailable && video.transcript_text ? 
                                                `<span class="text-slate-300">•</span>
                                                <button onclick="event.stopPropagation(); openAIToolsFromCard('${video.video_id}')" class="text-purple-600 hover:text-purple-800 underline flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                    </svg>
                                                    AI Tools
                                                </button>` : ''
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
                                    ${!video.transcript_unavailable && video.transcript_text ? 
                                        `<span class="text-slate-300">•</span>
                                        <button onclick="openAIToolsFromCard('${video.video_id}')" class="text-purple-600 hover:text-purple-800 underline transition-colors inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            AI Tools
                                        </button>` : ''
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
        let currentTranscriptTimestamped = '';
        let currentTranscriptView = 'plain';
        let currentVideoId = '';

        async function viewTranscript(videoId, hasTranscript) {
            currentVideoId = videoId;
            currentTranscriptView = 'plain'; // Reset to plain view
            document.getElementById('transcriptModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            const content = document.getElementById('transcriptContent');
            const toggle = document.getElementById('transcriptViewToggle');
            toggle.classList.add('hidden'); // Hide toggle until loaded
            
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
                    currentTranscriptTimestamped = data.transcript_timestamped || '';
                    
                    // Show toggle if we have both versions
                    if (currentTranscriptTimestamped) {
                        toggle.classList.remove('hidden');
                        // Reset toggle buttons
                        updateToggleButtons('plain');
                    }
                    
                    // Display plain text by default
                    displayTranscript('plain', data.fetched_at);
                    
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
                    currentTranscriptTimestamped = '';
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
                currentTranscriptTimestamped = '';
            }
        }

        function switchTranscriptView(view) {
            currentTranscriptView = view;
            updateToggleButtons(view);
            displayTranscript(view);
        }

        function updateToggleButtons(activeView) {
            const plainBtn = document.getElementById('plainViewBtn');
            const timestampedBtn = document.getElementById('timestampedViewBtn');
            
            if (activeView === 'plain') {
                plainBtn.classList.add('bg-white', 'text-slate-900', 'shadow-sm');
                plainBtn.classList.remove('text-slate-600', 'hover:text-slate-900');
                timestampedBtn.classList.remove('bg-white', 'text-slate-900', 'shadow-sm');
                timestampedBtn.classList.add('text-slate-600', 'hover:text-slate-900');
            } else {
                timestampedBtn.classList.add('bg-white', 'text-slate-900', 'shadow-sm');
                timestampedBtn.classList.remove('text-slate-600', 'hover:text-slate-900');
                plainBtn.classList.remove('bg-white', 'text-slate-900', 'shadow-sm');
                plainBtn.classList.add('text-slate-600', 'hover:text-slate-900');
            }
        }

        function displayTranscript(view, fetchedAt) {
            const content = document.getElementById('transcriptContent');
            const text = view === 'plain' ? currentTranscript : currentTranscriptTimestamped;
            
            content.innerHTML = `
                <div class="whitespace-pre-wrap text-slate-700 leading-relaxed ${view === 'timestamped' ? 'font-mono text-sm' : ''}">
                    ${escapeHtml(text)}
                </div>
                ${fetchedAt ? `<p class="text-xs text-slate-500 mt-4">Fetched: ${new Date(fetchedAt).toLocaleString()}</p>` : ''}
            `;
        }

        function closeTranscriptModal() {
            document.getElementById('transcriptModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentTranscript = '';
            currentTranscriptTimestamped = '';
            currentTranscriptView = 'plain';
            document.getElementById('transcriptViewToggle').classList.add('hidden');
        }

        function copyTranscript() {
            const textToCopy = currentTranscriptView === 'plain' ? currentTranscript : currentTranscriptTimestamped;
            if (!textToCopy) return;
            
            navigator.clipboard.writeText(textToCopy).then(() => {
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
        async function openAIToolsFromCard(videoId) {
            // Set the current video ID
            currentVideoId = videoId;
            
            // Reset to selection view
            const aiToolsSelection = document.getElementById('aiToolsSelection');
            const postContainer = document.getElementById('postSuggestionsContainer');
            const clipContainer = document.getElementById('clipSuggestionsContainer');
            const backBtn = document.getElementById('aiToolsBackBtn');
            if (aiToolsSelection) aiToolsSelection.classList.remove('hidden');
            if (postContainer) postContainer.classList.add('hidden');
            if (clipContainer) clipContainer.classList.add('hidden');
            if (backBtn) backBtn.classList.add('hidden');
            
            // Check for existing suggestions and update buttons
            await checkExistingPostSuggestions();
            await checkExistingClipSuggestions();
            
            // Open AI Tools modal directly
            document.getElementById('aiToolsModal').classList.remove('hidden');
        }

        async function checkExistingPostSuggestions() {
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
                const btn = document.getElementById('generatePostsBtn');
                
                if (btn) {
                    if (data.success && data.has_suggestions && data.suggestions) {
                        btn.textContent = 'View Post Suggestions';
                        btn.setAttribute('data-has-suggestions', 'true');
                    } else {
                        btn.textContent = 'Generate Posts';
                        btn.setAttribute('data-has-suggestions', 'false');
                    }
                }
            } catch (error) {
                console.error('Check suggestions error:', error);
            }
        }

        async function handlePostSuggestions() {
            const btn = document.getElementById('generatePostsBtn');
            const hasSuggestions = btn?.getAttribute('data-has-suggestions') === 'true';
            
            if (hasSuggestions) {
                // Load existing suggestions
                await loadAndShowPostSuggestions();
            } else {
                // Generate new suggestions
                await generatePostSuggestions();
            }
        }

        async function loadAndShowPostSuggestions() {
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

                if (data.success && data.has_suggestions && data.suggestions) {
                    // Hide selection and show suggestions
                    const aiToolsSelection = document.getElementById('aiToolsSelection');
                    const suggestionsContainer = document.getElementById('postSuggestionsContainer');
                    const backBtn = document.getElementById('aiToolsBackBtn');
                    
                    if (aiToolsSelection) aiToolsSelection.classList.add('hidden');
                    if (suggestionsContainer) suggestionsContainer.classList.remove('hidden');
                    if (backBtn) backBtn.classList.remove('hidden');
                    
                    renderPostSuggestions(data.suggestions);
                }
            } catch (error) {
                console.error('Load suggestions error:', error);
            }
        }

        function backToAIToolsSelection() {
            const aiToolsSelection = document.getElementById('aiToolsSelection');
            const postContainer = document.getElementById('postSuggestionsContainer');
            const clipContainer = document.getElementById('clipSuggestionsContainer');
            const backBtn = document.getElementById('aiToolsBackBtn');
            
            if (aiToolsSelection) aiToolsSelection.classList.remove('hidden');
            if (postContainer) postContainer.classList.add('hidden');
            if (clipContainer) clipContainer.classList.add('hidden');
            if (backBtn) backBtn.classList.add('hidden');
        }

        async function openAITools() {
            if (!currentTranscript) {
                showToast('Please load the transcript first', 'warning');
                return;
            }
            
            // Reset to selection view
            const aiToolsSelection = document.getElementById('aiToolsSelection');
            const postContainer = document.getElementById('postSuggestionsContainer');
            const clipContainer = document.getElementById('clipSuggestionsContainer');
            if (aiToolsSelection) aiToolsSelection.classList.remove('hidden');
            if (postContainer) postContainer.classList.add('hidden');
            if (clipContainer) clipContainer.classList.add('hidden');
            
            // Check for existing suggestions and update buttons
            await checkExistingPostSuggestions();
            await checkExistingClipSuggestions();
            
            // Hide transcript modal and show AI tools modal
            document.getElementById('transcriptModal').classList.add('hidden');
            document.getElementById('aiToolsModal').classList.remove('hidden');
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
                showToast('Video ID not found', 'error');
                return;
            }

            const btn = document.getElementById('generatePostsBtn');
            const regenerateBtn = document.getElementById('regeneratePostsBtn');
            const aiToolsSelection = document.getElementById('aiToolsSelection');
            const suggestionsContainer = document.getElementById('postSuggestionsContainer');
            const isRegenerating = regenerateBtn && !regenerateBtn.disabled && suggestionsContainer && !suggestionsContainer.classList.contains('hidden');
            
            // Loading HTML
            const loadingHTML = `
                <div class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Analyzing video...</span>
                </div>
            `;
            
            // Disable and show loading on appropriate button
            if (isRegenerating && regenerateBtn) {
                regenerateBtn.disabled = true;
                regenerateBtn.innerHTML = loadingHTML;
            } else if (btn) {
                btn.disabled = true;
                btn.innerHTML = `
                    <div class="flex flex-col items-center justify-center gap-2">
                        ${loadingHTML}
                        <span class="text-xs opacity-75">This may take up to 2 minutes</span>
                    </div>
                `;
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
                    // Hide other views and show post suggestions
                    const backBtn = document.getElementById('aiToolsBackBtn');
                    if (aiToolsSelection) aiToolsSelection.classList.add('hidden');
                    if (backBtn) backBtn.classList.remove('hidden');
                    suggestionsContainer.classList.remove('hidden');
                    
                    // Render suggestions
                    renderPostSuggestions(data.suggestions);
                } else {
                    showToast('Error: ' + (data.error || 'Failed to generate post suggestions'), 'error');
                }
            } catch (error) {
                console.error('Generate error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                // Re-enable buttons and restore text
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Generate Post Suggestions';
                }
                if (regenerateBtn) {
                    regenerateBtn.disabled = false;
                    regenerateBtn.innerHTML = `
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Regenerate
                    `;
                }
            }
        }

        // Post suggestions carousel state
        let allSuggestions = [];
        let currentSuggestionIndex = 0;
        let currentVideoData = null;

        function renderPostSuggestions(suggestions) {
            allSuggestions = suggestions;
            currentSuggestionIndex = 0;
            
            // Get current video data
            getCurrentVideoData();
            
            // Show the first suggestion
            showSuggestion(0);
            
            // Update total count
            document.getElementById('totalSuggestions').textContent = suggestions.length;
        }

        function getCurrentVideoData() {
            // Find the current video in channels
            for (const channel of Object.values(channels)) {
                const video = channel.videos.find(v => v.video_id === currentVideoId);
                if (video) {
                    currentVideoData = video;
                    break;
                }
            }
        }

        function showSuggestion(index) {
            if (!allSuggestions || allSuggestions.length === 0) return;
            
            currentSuggestionIndex = index;
            const suggestion = allSuggestions[index];
            
            // Update post text
            document.getElementById('postText').textContent = suggestion.post_text;
            
            // Update video card
            if (currentVideoData) {
                const videoUrl = `<?php echo APP_URL; ?>/?v=${currentVideoData.video_id}`;
                document.getElementById('videoCard').innerHTML = `
                    <a href="${videoUrl}" target="_blank" class="block hover:bg-slate-50 transition">
                        <img src="${currentVideoData.thumbnail_url}" class="w-full" alt="${escapeHtml(currentVideoData.title)}" />
                        <div class="p-3">
                            <div class="text-sm font-medium text-slate-900 line-clamp-2 mb-1">${escapeHtml(currentVideoData.title)}</div>
                            <div class="text-xs text-slate-500">${escapeHtml(currentVideoData.channel_name)}</div>
                            <div class="text-xs text-slate-400 mt-1">${videoUrl}</div>
                        </div>
                    </a>
                `;
            }
            
            // Update counter
            document.getElementById('currentIndex').textContent = index + 1;
            
            // Update button states
            document.getElementById('prevBtn').disabled = index === 0;
            document.getElementById('nextBtn').disabled = index === allSuggestions.length - 1;
        }

        function previousSuggestion() {
            if (currentSuggestionIndex > 0) {
                showSuggestion(currentSuggestionIndex - 1);
            }
        }

        function nextSuggestion() {
            if (currentSuggestionIndex < allSuggestions.length - 1) {
                showSuggestion(currentSuggestionIndex + 1);
            }
        }

        function copyCurrentPost() {
            if (!allSuggestions || !currentVideoData) return;
            
            const suggestion = allSuggestions[currentSuggestionIndex];
            const videoUrl = `<?php echo APP_URL; ?>/?v=${currentVideoData.video_id}`;
            const textToCopy = `${suggestion.post_text}\n\n${videoUrl}`;
            
            navigator.clipboard.writeText(textToCopy).then(() => {
                const btn = document.getElementById('copyPostBtn');
                const originalHTML = btn.innerHTML;
                
                btn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Copied to Clipboard!
                `;
                btn.classList.add('from-green-600', 'to-green-700');
                btn.classList.remove('from-purple-600', 'to-blue-600');
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('from-green-600', 'to-green-700');
                    btn.classList.add('from-purple-600', 'to-blue-600');
                }, 2000);
            });
        }

        // Keyboard navigation for carousel
        document.addEventListener('keydown', function(e) {
            const aiToolsModal = document.getElementById('aiToolsModal');
            const clipModal = document.getElementById('clipSuggestionsModal');
            
            if (!aiToolsModal.classList.contains('hidden')) {
                if (e.key === 'ArrowLeft') {
                    previousSuggestion();
                } else if (e.key === 'ArrowRight') {
                    nextSuggestion();
                }
            }
            
            if (!clipModal.classList.contains('hidden')) {
                if (e.key === 'ArrowLeft') {
                    previousClip();
                } else if (e.key === 'ArrowRight') {
                    nextClip();
                }
            }
        });

        // Clip suggestions state
        let allClips = [];
        let currentClipIndex = 0;
        let videoDuration = 0; // Total video duration in seconds
        let clipStartTime = 0; // Current clip start in seconds
        let clipEndTime = 0;   // Current clip end in seconds
        let originalClipStart = 0; // Original AI suggestion start
        let originalClipEnd = 0;   // Original AI suggestion end
        let isDragging = false;
        let dragType = null; // 'start', 'end', or 'segment'
        let dragStartX = 0; // Mouse X position when drag started
        let dragStartClipStart = 0; // Clip start time when drag started
        let dragStartClipEnd = 0; // Clip end time when drag started
        
        // Zoom state
        let zoomLevel = 1; // 1 = full video, higher = zoomed in
        let timelineViewStart = 0; // Start time of visible timeline window
        let timelineViewEnd = 0; // End time of visible timeline window

        // Clip Suggestions Modal Functions
        async function handleClipSuggestions() {
            const btn = document.getElementById('generateClipsBtn');
            const hasSuggestions = btn?.getAttribute('data-has-suggestions') === 'true';
            
            if (hasSuggestions) {
                // Load existing suggestions
                await loadAndShowClipSuggestions();
            } else {
                // Generate new suggestions
                await generateClipSuggestions();
            }
        }

        async function checkExistingClipSuggestions() {
            if (!currentVideoId) return;

            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'get_clip_suggestions',
                        video_id: currentVideoId 
                    })
                });

                const data = await response.json();
                const btn = document.getElementById('generateClipsBtn');
                
                if (btn) {
                    if (data.success && data.has_suggestions && data.suggestions) {
                        btn.textContent = 'View Clip Suggestions';
                        btn.setAttribute('data-has-suggestions', 'true');
                    } else {
                        btn.textContent = 'Generate Clip Suggestions';
                        btn.setAttribute('data-has-suggestions', 'false');
                    }
                }
            } catch (error) {
                console.error('Check clip suggestions error:', error);
            }
        }

        async function loadAndShowClipSuggestions() {
            if (!currentVideoId) return;

            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'get_clip_suggestions',
                        video_id: currentVideoId 
                    })
                });

                const data = await response.json();

                if (data.success && data.has_suggestions && data.suggestions) {
                    // Load any saved edits
                    await loadSavedClipEdits(currentVideoId, data.suggestions);
                    
                    // Hide selection and show clip suggestions
                    const aiToolsSelection = document.getElementById('aiToolsSelection');
                    const clipContainer = document.getElementById('clipSuggestionsContainer');
                    const backBtn = document.getElementById('aiToolsBackBtn');
                    
                    if (aiToolsSelection) aiToolsSelection.classList.add('hidden');
                    if (clipContainer) clipContainer.classList.remove('hidden');
                    if (backBtn) backBtn.classList.remove('hidden');
                    
                    renderClipSuggestions(data.suggestions);
                }
            } catch (error) {
                console.error('Load clip suggestions error:', error);
            }
        }

        async function loadSavedClipEdits(videoId, suggestions) {
            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'get_clip_edits',
                        video_id: videoId 
                    })
                });

                const data = await response.json();
                
                if (data.success && data.edits) {
                    // Apply saved edits to suggestions
                    data.edits.forEach(edit => {
                        if (suggestions[edit.clip_index]) {
                            suggestions[edit.clip_index].start_time = edit.edited_start_time;
                            suggestions[edit.clip_index].end_time = edit.edited_end_time;
                            suggestions[edit.clip_index].was_edited = true;
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading saved edits:', error);
            }
        }

        async function generateClipSuggestions() {
            const btn = document.getElementById('generateClipsBtn');
            const regenerateBtn = document.getElementById('regenerateClipsBtn');
            const clipContainer = document.getElementById('clipSuggestionsContainer');
            const aiToolsSelection = document.getElementById('aiToolsSelection');
            const backBtn = document.getElementById('aiToolsBackBtn');
            const isRegenerating = regenerateBtn && !regenerateBtn.disabled && clipContainer && !clipContainer.classList.contains('hidden');

            // Show loading state
            const loadingHTML = `
                <div class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Analyzing video...</span>
                </div>
            `;

            // Disable and show loading on appropriate button
            if (isRegenerating && regenerateBtn) {
                regenerateBtn.disabled = true;
                regenerateBtn.innerHTML = loadingHTML;
            } else if (btn) {
                btn.disabled = true;
                btn.innerHTML = `
                    <div class="flex flex-col items-center justify-center gap-2">
                        ${loadingHTML}
                        <span class="text-xs opacity-75">This may take up to 2 minutes</span>
                    </div>
                `;
            }

            try {
                // Set a 3-minute timeout for clip generation
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 180000); // 3 minutes
                
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'generate_clip_suggestions',
                        video_id: currentVideoId 
                    }),
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                const data = await response.json();

                if (data.success && data.suggestions) {
                    // Check if we got any suggestions
                    if (data.suggestions.length === 0) {
                        showToast('No clips found. The AI couldn\'t find suitable clip moments in this video. Try regenerating or check if the transcript is complete.', 'warning');
                    } else {
                        // Hide selection and show clip suggestions
                        if (aiToolsSelection) aiToolsSelection.classList.add('hidden');
                        if (clipContainer) clipContainer.classList.remove('hidden');
                        if (backBtn) backBtn.classList.remove('hidden');
                        
                        // Render clip suggestions
                        renderClipSuggestions(data.suggestions);
                    }
                } else {
                    showToast('Error: ' + (data.error || 'Failed to generate clip suggestions'), 'error');
                }
            } catch (error) {
                console.error('Generate error:', error);
                if (error.name === 'AbortError') {
                    showToast('Request timed out after 3 minutes. The video may be too long. Please try again or contact support.', 'error');
                } else {
                    showToast('Network error: ' + error.message + '. Please try again.', 'error');
                }
            } finally {
                // Re-enable buttons and restore text
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Generate Clip Suggestions';
                }
                if (regenerateBtn) {
                    regenerateBtn.disabled = false;
                    regenerateBtn.innerHTML = `
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Regenerate
                    `;
                }
            }
        }

        function renderClipSuggestions(clips) {
            allClips = clips;
            currentClipIndex = 0;
            
            // Get current video data
            getCurrentVideoData();
            
            // Use actual video duration from database if available
            if (currentVideoData && currentVideoData.duration) {
                videoDuration = parseInt(currentVideoData.duration);
                console.log(`Using actual video duration from database: ${videoDuration}s (${Math.floor(videoDuration/60)}:${(videoDuration%60).toString().padStart(2,'0')})`);
            } else {
                // Fallback: Estimate from clips (find the latest end time)
                let maxEndTime = 0;
                clips.forEach(clip => {
                    if (clip.end_time > maxEndTime) {
                        maxEndTime = clip.end_time;
                    }
                });
                
                // Use the latest clip end time + buffer, but ensure at least 6 minutes for adjustment
                if (maxEndTime > 0) {
                    videoDuration = Math.max(maxEndTime + 120, 360); // Latest clip + 2 min buffer, min 6 min
                    console.log(`Estimated video duration from clips: ${videoDuration}s (${Math.floor(videoDuration/60)}:${(videoDuration%60).toString().padStart(2,'0')})`);
                }
            }
            
            // Reset zoom for new clip set
            zoomLevel = 1;
            timelineViewStart = 0;
            timelineViewEnd = videoDuration;
            
            // Show the first clip
            showClip(0);
            
            // Update total count
            document.getElementById('totalClips').textContent = clips.length;
        }

        function showClip(index) {
            if (!allClips || allClips.length === 0 || !currentVideoData) return;
            
            currentClipIndex = index;
            const clip = allClips[index];
            
            // Times are already in seconds from backend
            const startSeconds = clip.start_time;
            const endSeconds = clip.end_time;
            const durationSeconds = endSeconds - startSeconds;
            
            // Set global clip times
            clipStartTime = startSeconds;
            clipEndTime = endSeconds;
            originalClipStart = startSeconds;
            originalClipEnd = endSeconds;
            
            // Video duration is already set in renderClipSuggestions()
            // But ensure it's at least long enough for this clip
            if (videoDuration < endSeconds + 60) {
                videoDuration = Math.max(endSeconds + 120, 360);
                console.log(`Adjusted video duration for clip: ${videoDuration}s`);
            }
            
            // Format time display
            const formatTime = (seconds) => {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return `${mins}:${secs.toString().padStart(2, '0')}`;
            };
            
            // Update clip info
            document.getElementById('clipTitle').textContent = clip.suggested_title;
            document.getElementById('clipReason').textContent = clip.reason;
            
            // Update clip info displays (will be updated again by updateClipInfoDisplay)
            updateClipInfoDisplay();
            
            // Update YouTube embed with start and end parameters
            updateClipEmbed();
            
            // Initialize timeline
            initializeTimeline();
            
            // Update counter
            document.getElementById('currentClipIndex').textContent = index + 1;
            
            // Update button states
            document.getElementById('prevClipBtn').disabled = index === 0;
            document.getElementById('nextClipBtn').disabled = index === allClips.length - 1;
        }

        function previousClip() {
            if (currentClipIndex > 0) {
                showClip(currentClipIndex - 1);
            }
        }

        function nextClip() {
            if (currentClipIndex < allClips.length - 1) {
                showClip(currentClipIndex + 1);
            }
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

        // Timeline Functions
        function initializeTimeline() {
            const formatTime = (seconds) => {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return `${mins}:${secs.toString().padStart(2, '0')}`;
            };
            
            // Enforce 6-minute max clip length
            const currentDuration = clipEndTime - clipStartTime;
            if (currentDuration > 360) {
                // Clip is too long, adjust end time
                clipEndTime = clipStartTime + 360;
                console.warn('Clip duration exceeded 6 minutes, adjusted to 6 minutes');
            }
            
            // Initialize zoom view only if not already set (first clip)
            if (timelineViewStart === 0 && timelineViewEnd === 0) {
                timelineViewStart = 0;
                timelineViewEnd = videoDuration;
                zoomLevel = 1;
            } else {
                // Maintain zoom level but update view to center on new clip
                updateZoomView();
            }
            
            // Update timeline UI
            updateTimelineUI();
            
            // Update time labels
            document.getElementById('clipStartLabel').textContent = formatTime(clipStartTime);
            document.getElementById('clipEndLabel').textContent = formatTime(clipEndTime);
            document.getElementById('clipDurationLabel').textContent = `${clipEndTime - clipStartTime}s`;
            
            // Setup drag handlers
            setupTimelineDragHandlers();
        }

        function updateTimelineUI() {
            const viewDuration = timelineViewEnd - timelineViewStart;
            const startPercent = ((clipStartTime - timelineViewStart) / viewDuration) * 100;
            const endPercent = ((clipEndTime - timelineViewStart) / viewDuration) * 100;
            const widthPercent = endPercent - startPercent;
            
            console.log(`Timeline: start=${clipStartTime}s (${startPercent.toFixed(1)}%), end=${clipEndTime}s (${endPercent.toFixed(1)}%), view=${timelineViewStart}-${timelineViewEnd}s, zoom=${zoomLevel}x`);
            
            const clipSegment = document.getElementById('clipSegment');
            clipSegment.style.left = `${Math.max(0, startPercent)}%`;
            clipSegment.style.width = `${Math.min(100, widthPercent)}%`;
            
            // Update reset button visibility
            const resetBtn = document.getElementById('resetClipBtn');
            const hasChanges = clipStartTime !== originalClipStart || clipEndTime !== originalClipEnd;
            if (resetBtn) {
                resetBtn.style.opacity = hasChanges ? '1' : '0.5';
                resetBtn.disabled = !hasChanges;
            }
            
            // Update time markers
            updateTimeMarkers();
        }
        
        function updateTimeMarkers() {
            const markersContainer = document.getElementById('timeMarkers');
            if (!markersContainer) return;
            
            const formatTime = (seconds) => {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return `${mins}:${secs.toString().padStart(2, '0')}`;
            };
            
            const viewDuration = timelineViewEnd - timelineViewStart;
            const markerCount = 5; // Show 5 time markers
            const markers = [];
            
            for (let i = 0; i <= markerCount; i++) {
                const time = timelineViewStart + (viewDuration * i / markerCount);
                markers.push(`<span>${formatTime(Math.round(time))}</span>`);
            }
            
            markersContainer.innerHTML = markers.join('');
        }
        
        function zoomIn() {
            if (zoomLevel >= 8) return; // Max 8x zoom
            
            zoomLevel *= 2;
            updateZoomView();
        }
        
        function zoomOut() {
            if (zoomLevel <= 1) return; // Min 1x zoom (full video)
            
            zoomLevel /= 2;
            updateZoomView();
        }
        
        function resetZoom() {
            zoomLevel = 1;
            updateZoomView();
        }
        
        function updateZoomView() {
            if (zoomLevel === 1) {
                // Full video view
                timelineViewStart = 0;
                timelineViewEnd = videoDuration;
            } else {
                // Zoomed view - center on current clip
                const clipCenter = (clipStartTime + clipEndTime) / 2;
                const viewDuration = videoDuration / zoomLevel;
                
                timelineViewStart = Math.max(0, clipCenter - viewDuration / 2);
                timelineViewEnd = Math.min(videoDuration, timelineViewStart + viewDuration);
                
                // Adjust if we hit the end
                if (timelineViewEnd === videoDuration) {
                    timelineViewStart = Math.max(0, videoDuration - viewDuration);
                }
            }
            
            updateTimelineUI();
            updateTimeLabels();
            
            // Update playhead position after zoom
            if (ytPlayer && ytPlayer.getCurrentTime) {
                const currentTime = ytPlayer.getCurrentTime();
                updatePlayheadPosition(currentTime);
            }
        }

        function setupTimelineDragHandlers() {
            const startHandle = document.getElementById('startHandle');
            const endHandle = document.getElementById('endHandle');
            const clipSegment = document.getElementById('clipSegment');
            const timelineBar = document.getElementById('timelineBar');
            
            // Timeline click to seek
            timelineBar.addEventListener('click', (e) => {
                // Don't seek if clicking on handles or during drag
                if (e.target.closest('#startHandle') || e.target.closest('#endHandle') || isDragging) {
                    return;
                }
                
                const rect = timelineBar.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const percent = Math.max(0, Math.min(1, x / rect.width));
                const viewDuration = timelineViewEnd - timelineViewStart;
                const seekTime = timelineViewStart + (percent * viewDuration);
                
                // Seek the YouTube player
                if (ytPlayer && ytPlayer.seekTo) {
                    ytPlayer.seekTo(seekTime, true);
                    updatePlayheadPosition(seekTime);
                }
            });
            
            // Start handle drag
            startHandle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent segment drag
                isDragging = true;
                dragType = 'start';
                document.body.style.cursor = 'ew-resize';
            });
            
            // End handle drag
            endHandle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent segment drag
                isDragging = true;
                dragType = 'end';
                document.body.style.cursor = 'ew-resize';
            });
            
            // Segment drag (move entire clip)
            clipSegment.addEventListener('mousedown', (e) => {
                // Only if not clicking on handles
                if (e.target.closest('#startHandle') || e.target.closest('#endHandle')) {
                    return;
                }
                
                e.preventDefault();
                isDragging = true;
                dragType = 'segment';
                dragStartX = e.clientX;
                dragStartClipStart = clipStartTime;
                dragStartClipEnd = clipEndTime;
                document.body.style.cursor = 'grab';
            });
            
            // Mouse move
            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                
                const rect = timelineBar.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const percent = Math.max(0, Math.min(1, x / rect.width));
                const viewDuration = timelineViewEnd - timelineViewStart;
                const newTime = Math.round(timelineViewStart + (percent * viewDuration));
                
                if (dragType === 'start') {
                    // Don't allow start to go past end (keep 1 second minimum)
                    // Max clip length is 6 minutes (360 seconds)
                    const minStartTime = Math.max(0, clipEndTime - 360);
                    clipStartTime = Math.max(Math.min(newTime, clipEndTime - 1), minStartTime);
                } else if (dragType === 'end') {
                    // Don't allow end to go before start (keep 1 second minimum)
                    // Max clip length is 6 minutes (360 seconds)
                    const maxEndTime = Math.min(clipStartTime + 360, videoDuration);
                    clipEndTime = Math.max(Math.min(newTime, maxEndTime), clipStartTime + 1);
                } else if (dragType === 'segment') {
                    // Move entire segment
                    const deltaX = e.clientX - dragStartX;
                    const deltaPercent = deltaX / rect.width;
                    const deltaTime = Math.round(deltaPercent * viewDuration);
                    
                    const clipDuration = dragStartClipEnd - dragStartClipStart;
                    let newStart = dragStartClipStart + deltaTime;
                    let newEnd = dragStartClipEnd + deltaTime;
                    
                    // Keep within bounds
                    if (newStart < 0) {
                        newStart = 0;
                        newEnd = clipDuration;
                    } else if (newEnd > videoDuration) {
                        newEnd = videoDuration;
                        newStart = videoDuration - clipDuration;
                    }
                    
                    clipStartTime = newStart;
                    clipEndTime = newEnd;
                }
                
                // Only update UI, don't update embed or save yet
                updateTimelineUI();
                updateTimeLabels();
            });
            
            // Mouse up
            document.addEventListener('mouseup', () => {
                if (isDragging) {
                    const wasDragging = isDragging;
                    isDragging = false;
                    dragType = null;
                    document.body.style.cursor = 'default';
                    
                    // Only update embed and save when drag is complete (performance optimization)
                    if (wasDragging) {
                        console.log('Drag complete, updating embed and saving...');
                        updateClipEmbed();
                        saveClipEdit();
                    }
                }
            });
        }

        function updateTimeLabels() {
            const formatTime = (seconds) => {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return `${mins}:${secs.toString().padStart(2, '0')}`;
            };
            
            document.getElementById('clipStartLabel').textContent = formatTime(clipStartTime);
            document.getElementById('clipEndLabel').textContent = formatTime(clipEndTime);
            document.getElementById('clipDurationLabel').textContent = `${clipEndTime - clipStartTime}s`;
            
            // Also update the clip info display
            updateClipInfoDisplay();
        }

        // YouTube Player instance
        let ytPlayer = null;
        let playheadUpdateInterval = null;

        function updateClipEmbed() {
            if (!currentVideoData) return;
            
            // Clear existing player and interval
            if (ytPlayer) {
                ytPlayer.destroy();
                ytPlayer = null;
            }
            if (playheadUpdateInterval) {
                clearInterval(playheadUpdateInterval);
                playheadUpdateInterval = null;
            }
            
            // Create container for YouTube player with absolute positioning
            document.getElementById('clipEmbed').innerHTML = `<div id="ytPlayerContainer" class="absolute top-0 left-0 w-full h-full"></div>`;
            
            // Initialize YouTube player with IFrame API
            ytPlayer = new YT.Player('ytPlayerContainer', {
                width: '100%',
                height: '100%',
                videoId: currentVideoData.video_id,
                playerVars: {
                    start: clipStartTime,
                    end: clipEndTime,
                    autoplay: 0,
                    rel: 0,
                    modestbranding: 1,
                    controls: 1,
                    iv_load_policy: 3,  // Disable annotations
                    fs: 1,              // Enable fullscreen
                    playsinline: 1      // Play inline on mobile
                },
                events: {
                    onReady: onPlayerReady,
                    onStateChange: onPlayerStateChange
                }
            });
        }
        
        function onPlayerReady(event) {
            console.log('YouTube player ready');
            // Start updating playhead position
            startPlayheadUpdate();
        }
        
        function onPlayerStateChange(event) {
            // YT.PlayerState.PLAYING = 1
            // YT.PlayerState.PAUSED = 2
            // YT.PlayerState.ENDED = 0
            if (event.data === YT.PlayerState.PLAYING) {
                startPlayheadUpdate();
                updatePlayPauseButton(true);
            } else if (event.data === YT.PlayerState.PAUSED || event.data === YT.PlayerState.ENDED) {
                stopPlayheadUpdate();
                updatePlayPauseButton(false);
            }
        }
        
        function togglePlayPause() {
            if (!ytPlayer || !ytPlayer.getPlayerState) return;
            
            const state = ytPlayer.getPlayerState();
            if (state === YT.PlayerState.PLAYING) {
                ytPlayer.pauseVideo();
            } else {
                ytPlayer.playVideo();
            }
        }
        
        function updatePlayPauseButton(isPlaying) {
            const playIcon = document.getElementById('playIcon');
            const pauseIcon = document.getElementById('pauseIcon');
            
            if (isPlaying) {
                playIcon.classList.add('hidden');
                pauseIcon.classList.remove('hidden');
            } else {
                playIcon.classList.remove('hidden');
                pauseIcon.classList.add('hidden');
            }
        }
        
        function startPlayheadUpdate() {
            if (playheadUpdateInterval) return;
            
            playheadUpdateInterval = setInterval(() => {
                if (ytPlayer && ytPlayer.getCurrentTime) {
                    const currentTime = ytPlayer.getCurrentTime();
                    updatePlayheadPosition(currentTime);
                }
            }, 100); // Update every 100ms for smooth animation
        }
        
        function stopPlayheadUpdate() {
            if (playheadUpdateInterval) {
                clearInterval(playheadUpdateInterval);
                playheadUpdateInterval = null;
            }
        }
        
        function updatePlayheadPosition(currentTime) {
            const playhead = document.getElementById('playhead');
            if (!playhead) return;
            
            const viewDuration = timelineViewEnd - timelineViewStart;
            const playheadPercent = ((currentTime - timelineViewStart) / viewDuration) * 100;
            
            // Only show playhead if within visible timeline range
            if (playheadPercent >= 0 && playheadPercent <= 100) {
                playhead.style.left = `${playheadPercent}%`;
                playhead.style.opacity = '1';
            } else {
                playhead.style.opacity = '0';
            }
        }

        function updateClipInfoDisplay() {
            const formatTime = (seconds) => {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return `${mins}:${secs.toString().padStart(2, '0')}`;
            };
            
            const durationSeconds = clipEndTime - clipStartTime;
            document.querySelector('#clipDuration span').textContent = `${durationSeconds}s duration`;
            document.querySelector('#clipTimestamp span').textContent = `${formatTime(clipStartTime)} - ${formatTime(clipEndTime)}`;
        }

        async function saveClipEdit() {
            if (!currentVideoData || currentClipIndex === null) return;
            
            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save_clip_edit',
                        video_id: currentVideoData.video_id,
                        clip_index: currentClipIndex,
                        start_time: clipStartTime,
                        end_time: clipEndTime
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    console.log('✓ Clip edit saved automatically');
                } else {
                    console.error('Failed to save clip edit:', data.error);
                }
            } catch (error) {
                console.error('Error saving clip edit:', error);
            }
        }

        function resetToAISuggestion() {
            // Reset to original AI suggestion
            clipStartTime = originalClipStart;
            clipEndTime = originalClipEnd;
            
            // Update all UI elements
            updateTimelineUI();
            updateTimeLabels();
            updateClipEmbed();
            
            // Save the reset to backend (removes custom edit)
            saveClipEdit();
            
            showToast('Reset to AI suggestion', 'info');
        }

        async function downloadClip() {
            if (!currentVideoData) {
                console.error('No current video data');
                return;
            }
            
            console.log('Downloading clip for video:', currentVideoData.video_id, 'from', clipStartTime, 'to', clipEndTime);
            
            const btn = document.getElementById('downloadClipBtn');
            const originalHTML = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Processing...</span>
            `;
            
            try {
                const response = await fetch('/download_clip.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        video_id: currentVideoData.video_id,
                        start_time: clipStartTime,
                        end_time: clipEndTime,
                        clip_index: currentClipIndex,
                        resolution: '1080p'
                    })
                });
                
                const data = await response.json();
                
                console.log('Download response:', data);
                
                if (data.success && data.download_url) {
                    // Force HTTPS for security (mixed content protection)
                    let downloadUrl = data.download_url.replace('http://', 'https://');
                    
                    // Create invisible iframe to trigger download without navigation
                    const iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = downloadUrl;
                    document.body.appendChild(iframe);
                    
                    // Remove iframe after download starts
                    setTimeout(() => {
                        document.body.removeChild(iframe);
                    }, 5000);
                    
                    showToast('Clip download started! Check your downloads folder.', 'success');
                    
                    // Reset button after short delay
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.disabled = false;
                    }, 2000);
                } else {
                    // Log the full response for debugging
                    console.error('Download failed:', data);
                    
                    const errorMsg = data.error || 'No download URL received';
                    showToast('Error: ' + errorMsg, 'error');
                    
                    // Show API response in console if available
                    if (data.api_response) {
                        console.error('API Response:', data.api_response);
                    }
                    
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error('Download error:', error);
                showToast('Network error. Please try again.', 'error');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
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
                    showToast('Video deleted successfully', 'success');
                } else {
                    showToast('Error: ' + (data.error || 'Failed to delete video'), 'error');
                }
            } catch (error) {
                console.error('Delete error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Delete Video';
            }
        }

        // Toast notification system
        function showToast(message, type = 'info') {
            // Create toast container if it doesn't exist
            let container = document.getElementById('toastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toastContainer';
                container.className = 'fixed top-4 right-4 z-50 space-y-2';
                document.body.appendChild(container);
            }

            // Create toast element
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-green-50 border-green-200 text-green-800',
                error: 'bg-red-50 border-red-200 text-red-800',
                warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
                info: 'bg-blue-50 border-blue-200 text-blue-800'
            };
            
            const icons = {
                success: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
                error: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
                warning: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
                info: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
            };

            toast.className = `flex items-start gap-3 p-4 rounded-lg border shadow-lg max-w-md animate-slide-in ${colors[type] || colors.info}`;
            toast.innerHTML = `
                <div class="flex-shrink-0">
                    ${icons[type] || icons.info}
                </div>
                <div class="flex-1 text-sm">${message}</div>
                <button onclick="this.parentElement.remove()" class="flex-shrink-0 text-current opacity-50 hover:opacity-100">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            `;

            container.appendChild(toast);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
    </script>
    
    <style>
        @keyframes slide-in {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .animate-slide-in {
            animation: slide-in 0.3s ease-out;
            transition: opacity 0.3s, transform 0.3s;
        }
    </style>
</body>
</html>
