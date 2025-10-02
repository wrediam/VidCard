<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - VidCard</title>
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <link rel="icon" type="image/png" href="/images/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="/images/icon.png" alt="VidCard" class="w-8 h-8">
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">VidCard Admin</h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">Back to Dashboard</a>
                    <span class="text-sm text-slate-600"><?php echo htmlspecialchars($currentUser['email']); ?></span>
                    <button onclick="logout()" class="text-sm text-slate-600 hover:text-slate-900">Logout</button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-6 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Users List -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                        <div class="p-6 border-b border-slate-200">
                            <h2 class="text-lg font-semibold">Users</h2>
                            <p class="text-sm text-slate-600 mt-1">Click to view videos</p>
                        </div>
                        <div id="usersList" class="divide-y divide-slate-200">
                            <div class="p-6 text-center text-slate-500">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-slate-900 mx-auto"></div>
                                <p class="mt-2 text-sm">Loading users...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Videos -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                        <div class="p-6 border-b border-slate-200">
                            <h2 class="text-lg font-semibold" id="videosTitle">Select a user</h2>
                            <p class="text-sm text-slate-600 mt-1" id="videosSubtitle">Choose a user from the list to view their videos</p>
                        </div>
                        <div id="videosList" class="p-6">
                            <div class="text-center text-slate-400 py-12">
                                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <p>No user selected</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let selectedUserId = null;

        // Load all users on page load
        loadUsers();

        async function loadUsers() {
            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_all_users' })
                });

                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Failed to load users');
                }

                renderUsers(data.users);
            } catch (error) {
                console.error('Error loading users:', error);
                document.getElementById('usersList').innerHTML = `
                    <div class="p-6 text-center text-red-600">
                        <p>Error loading users</p>
                        <p class="text-sm mt-1">${error.message}</p>
                    </div>
                `;
            }
        }

        function renderUsers(users) {
            const container = document.getElementById('usersList');
            
            if (users.length === 0) {
                container.innerHTML = `
                    <div class="p-6 text-center text-slate-500">
                        <p>No users found</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = users.map(user => `
                <div 
                    onclick="selectUser(${user.id}, '${user.email}')"
                    class="p-4 hover:bg-slate-50 cursor-pointer transition ${selectedUserId === user.id ? 'bg-slate-100' : ''}"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 truncate">${user.email}</p>
                            <p class="text-xs text-slate-500 mt-1">
                                ${user.video_count} video${user.video_count !== 1 ? 's' : ''}
                            </p>
                            <p class="text-xs text-slate-400 mt-1">
                                Joined ${formatDate(user.created_at)}
                            </p>
                        </div>
                        <div class="ml-2">
                            ${user.is_active ? 
                                '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>' : 
                                '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>'
                            }
                        </div>
                    </div>
                </div>
            `).join('');
        }

        async function selectUser(userId, email) {
            selectedUserId = userId;
            
            // Update UI
            document.getElementById('videosTitle').textContent = email;
            document.getElementById('videosSubtitle').textContent = 'Loading videos...';
            document.getElementById('videosList').innerHTML = `
                <div class="text-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-slate-900 mx-auto"></div>
                    <p class="mt-2 text-sm text-slate-500">Loading videos...</p>
                </div>
            `;

            // Highlight selected user
            loadUsers();

            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'get_user_videos',
                        user_id: userId 
                    })
                });

                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Failed to load videos');
                }

                renderVideos(data.videos, email);
            } catch (error) {
                console.error('Error loading videos:', error);
                document.getElementById('videosList').innerHTML = `
                    <div class="text-center text-red-600 py-12">
                        <p>Error loading videos</p>
                        <p class="text-sm mt-1">${error.message}</p>
                    </div>
                `;
            }
        }

        function renderVideos(videos, userEmail) {
            const container = document.getElementById('videosList');
            document.getElementById('videosSubtitle').textContent = `${videos.length} video${videos.length !== 1 ? 's' : ''}`;
            
            if (videos.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-slate-400 py-12">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <p>No videos yet</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = `
                <div class="space-y-4">
                    ${videos.map(video => `
                        <div class="border border-slate-200 rounded-lg p-4 hover:border-slate-300 transition">
                            <div class="flex gap-4">
                                <img 
                                    src="${video.thumbnail_url}" 
                                    alt="${video.title}"
                                    class="w-32 h-20 object-cover rounded flex-shrink-0"
                                    onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%2290%22%3E%3Crect fill=%22%23ddd%22 width=%22120%22 height=%2290%22/%3E%3C/svg%3E'"
                                >
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-slate-900 line-clamp-2">${video.title}</h3>
                                    <p class="text-sm text-slate-600 mt-1">${video.channel_name}</p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-500">
                                        <span>${video.view_count || 0} views</span>
                                        <span>Added ${formatDate(video.created_at)}</span>
                                        ${video.last_viewed ? `<span>Last viewed ${formatDate(video.last_viewed)}</span>` : ''}
                                    </div>
                                    <div class="flex items-center gap-3 mt-2">
                                        <a 
                                            href="/?v=${video.video_id}" 
                                            target="_blank"
                                            class="text-sm text-blue-600 hover:text-blue-800"
                                        >
                                            View Share Link →
                                        </a>
                                        <button 
                                            onclick="deleteVideo('${video.video_id}')"
                                            class="text-sm text-red-600 hover:text-red-800 font-medium"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function formatDate(dateString) {
            if (!dateString) return 'Never';
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            
            if (days === 0) return 'Today';
            if (days === 1) return 'Yesterday';
            if (days < 7) return `${days} days ago`;
            if (days < 30) return `${Math.floor(days / 7)} weeks ago`;
            if (days < 365) return `${Math.floor(days / 30)} months ago`;
            return `${Math.floor(days / 365)} years ago`;
        }

        async function logout() {
            try {
                await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                });
            } catch (error) {
                console.error('Logout error:', error);
            }
            window.location.href = '/';
        }

        async function deleteVideo(videoId) {
            if (!confirm('Are you sure you want to delete this video? This will break any shared links and cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'delete_video',
                        video_id: videoId 
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Reload the current user's videos
                    if (selectedUserId) {
                        const userEmail = document.getElementById('videosTitle').textContent;
                        selectUser(selectedUserId, userEmail);
                    }
                    // Reload users list to update video counts
                    loadUsers();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete video'));
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('Network error. Please try again.');
            }
        }
    </script>
</body>
</html>
