<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VidCard</title>
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
        <div id="sidebar" class="w-64 bg-white border-r border-slate-200 flex flex-col transition-all duration-300">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-xl font-bold">Channels</h2>
            </div>
            <div id="channelList" class="flex-1 overflow-y-auto p-4 space-y-2"></div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="text-slate-600 hover:text-slate-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 class="text-2xl font-bold">VidCard</h1>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="toggleSearch()" class="text-slate-600 hover:text-slate-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
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

                    <!-- Videos List -->
                    <div id="videosSection" class="space-y-4">
                        <h2 class="text-xl font-semibold">Your Videos</h2>
                        <div id="videosList"></div>
                    </div>
                </div>
            </main>
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

    <script>
        let channels = {};
        let sidebarCollapsed = false;

        // Load videos on page load
        loadVideos();

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebarCollapsed = !sidebarCollapsed;
            if (sidebarCollapsed) {
                sidebar.classList.add('sidebar-collapsed');
            } else {
                sidebar.classList.remove('sidebar-collapsed');
            }
        }

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
                div.className = 'flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 cursor-pointer transition';
                div.onclick = () => filterByChannel(channel.name);
                div.innerHTML = `
                    ${channel.thumbnail ? `<img src="${channel.thumbnail}" class="w-8 h-8 rounded-full" />` : ''}
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm truncate">${channel.name}</div>
                        <div class="text-xs text-slate-500">${channel.videos.length} videos</div>
                    </div>
                `;
                list.appendChild(div);
            });
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
            
            videos.forEach(video => {
                const div = document.createElement('div');
                div.className = 'bg-white border border-slate-200 rounded-lg p-4 hover:shadow-md transition';
                div.innerHTML = `
                    <div class="flex gap-4">
                        <img src="${video.thumbnail_url}" class="w-40 h-24 object-cover rounded" />
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold mb-1 truncate">${video.title}</h3>
                            <p class="text-sm text-slate-600 mb-2">${video.channel_name}</p>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <span>${video.visit_count || 0} visits</span>
                                <span>•</span>
                                <button onclick="showStats('${video.video_id}')" class="text-slate-700 hover:text-slate-900">View Stats</button>
                                <span>•</span>
                                <button onclick="copyVideoUrl('${video.video_id}')" class="text-slate-700 hover:text-slate-900">Copy Link</button>
                            </div>
                        </div>
                    </div>
                `;
                list.appendChild(div);
            });
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
            btn.textContent = 'Processing...';
            
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
                btn.textContent = 'Process Video';
            });
        }

        function copyUrl() {
            const url = document.getElementById('shareUrl').textContent;
            navigator.clipboard.writeText(url);
            const success = document.getElementById('copySuccess');
            success.classList.remove('hidden');
            setTimeout(() => success.classList.add('hidden'), 2000);
        }

        function copyVideoUrl(videoId) {
            const url = `<?php echo APP_URL; ?>/?v=${videoId}`;
            navigator.clipboard.writeText(url);
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
                    content.innerHTML = `
                        <div class="space-y-6">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="bg-slate-50 p-4 rounded-lg">
                                    <div class="text-2xl font-bold">${data.stats.total_visits || 0}</div>
                                    <div class="text-sm text-slate-600">Total Visits</div>
                                </div>
                            </div>
                            ${data.stats.recent_visits && data.stats.recent_visits.length > 0 ? `
                                <div>
                                    <h3 class="font-semibold mb-3">Recent Visits</h3>
                                    <div class="space-y-2">
                                        ${data.stats.recent_visits.map(visit => `
                                            <div class="text-sm border-b border-slate-100 pb-2">
                                                <div class="flex justify-between">
                                                    <span class="text-slate-600">${new Date(visit.visited_at).toLocaleString()}</span>
                                                    <span class="text-slate-500">${visit.referrer}</span>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
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
                                <div class="flex gap-3 p-3 hover:bg-slate-50 rounded-lg cursor-pointer" onclick="copyVideoUrl('${video.video_id}')">
                                    <img src="${video.thumbnail_url}" class="w-24 h-16 object-cover rounded" />
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-sm truncate">${video.title}</div>
                                        <div class="text-xs text-slate-500">${video.channel_name}</div>
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

        // Close modals on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                toggleSearch();
                closeStats();
            }
        });
    </script>
</body>
</html>
