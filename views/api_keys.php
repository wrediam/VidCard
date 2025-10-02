<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Keys - VidCard</title>
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
        <header class="bg-white border-b border-slate-200 px-6 py-4">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="/images/icon.png" alt="VidCard" class="w-8 h-8">
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">VidCard API</h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/dashboard" class="text-sm text-slate-600 hover:text-slate-900">Dashboard</a>
                    <span class="text-sm text-slate-600"><?php echo htmlspecialchars($currentUser['email']); ?></span>
                    <button onclick="logout()" class="text-sm text-slate-600 hover:text-slate-900">Logout</button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-6 py-8">
            <!-- Header Section -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-slate-900 mb-2">API Keys</h2>
                <p class="text-slate-600">Manage your API keys for programmatic access to VidCard</p>
            </div>

            <!-- Create New Key Section -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Create New API Key</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Key Name</label>
                        <input 
                            type="text" 
                            id="keyName" 
                            placeholder="Production API Key"
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent"
                        />
                    </div>
                    <div class="flex items-end">
                        <button 
                            onclick="createApiKey()"
                            id="createBtn"
                            class="w-full bg-slate-900 text-white py-2 px-4 rounded-md font-medium hover:bg-slate-800 transition"
                        >
                            Create API Key
                        </button>
                    </div>
                    <div class="flex items-end">
                        <button 
                            onclick="openDocumentation()"
                            type="button"
                            class="w-full bg-blue-600 text-white py-2 px-4 rounded-md font-medium hover:bg-blue-700 transition"
                        >
                            📚 View Documentation
                        </button>
                    </div>
                </div>
                <div id="createError" class="text-sm text-red-600 mt-2 hidden"></div>
            </div>

            <!-- API Keys List -->
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-lg font-semibold">Your API Keys</h3>
                    <p class="text-sm text-slate-600 mt-1">Keep your API keys secure and never share them publicly</p>
                </div>
                <div id="apiKeysList" class="divide-y divide-slate-200">
                    <div class="p-6 text-center text-slate-500">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-slate-900 mx-auto"></div>
                        <p class="mt-2 text-sm">Loading API keys...</p>
                    </div>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="mt-8 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6 border border-blue-200">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">📚 Quick Start</h3>
                <div class="space-y-2 text-sm text-blue-800">
                    <p><strong>Base URL:</strong> <code class="bg-white px-2 py-1 rounded"><?php echo APP_URL; ?>/api/v1</code></p>
                    <p><strong>Authentication:</strong> Include <code class="bg-white px-2 py-1 rounded">X-API-Key</code> header with your API key</p>
                    <p><strong>Rate Limit:</strong> 100 requests per hour per API key</p>
                </div>
                <div class="mt-4">
                    <button onclick="openDocumentation()" class="text-blue-700 hover:text-blue-900 font-medium underline cursor-pointer">
                        View Full Documentation →
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- New Key Modal -->
    <div id="newKeyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
            <div class="p-6 border-b border-slate-200">
                <h3 class="text-lg font-semibold">🎉 API Key Created Successfully!</h3>
            </div>
            <div class="p-6">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-yellow-800 font-semibold">⚠️ Important: Copy your API key now!</p>
                    <p class="text-xs text-yellow-700 mt-1">For security reasons, you won't be able to see this key again.</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Your API Key</label>
                    <div class="flex items-center gap-2">
                        <input 
                            type="text" 
                            id="newApiKey" 
                            readonly
                            class="flex-1 px-3 py-2 bg-white border border-slate-300 rounded-md font-mono text-sm"
                        />
                        <button 
                            onclick="copyNewKey()"
                            class="px-4 py-2 bg-slate-900 text-white rounded-md hover:bg-slate-800 transition whitespace-nowrap"
                        >
                            Copy
                        </button>
                    </div>
                    <div id="copySuccess" class="text-sm text-green-600 mt-2 hidden">✓ Copied to clipboard!</div>
                </div>
            </div>
            <div class="p-6 border-t border-slate-200 flex justify-end">
                <button 
                    onclick="closeNewKeyModal()"
                    class="px-4 py-2 bg-slate-900 text-white rounded-md hover:bg-slate-800 transition"
                >
                    Done
                </button>
            </div>
        </div>
    </div>

    <!-- Documentation Modal -->
    <div id="documentationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center overflow-y-auto p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full my-8">
            <div class="sticky top-0 bg-white border-b border-slate-200 p-6 flex items-center justify-between rounded-t-lg z-10">
                <h3 class="text-2xl font-bold text-slate-900">📚 VidCard API Documentation</h3>
                <button onclick="closeDocumentation()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6 max-h-[70vh] overflow-y-auto">
                <!-- Overview -->
                <section class="mb-8">
                    <h4 class="text-xl font-semibold text-slate-900 mb-3">Overview</h4>
                    <p class="text-slate-700 mb-2">The VidCard API provides programmatic access to process YouTube videos, manage your video library, and retrieve analytics.</p>
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mt-3">
                        <p class="text-sm"><strong>Base URL:</strong> <code class="bg-white px-2 py-1 rounded text-blue-600"><?php echo APP_URL; ?>/api/v1</code></p>
                        <p class="text-sm mt-2"><strong>Rate Limit:</strong> 100 requests per hour per API key</p>
                    </div>
                </section>

                <!-- Authentication -->
                <section class="mb-8">
                    <h4 class="text-xl font-semibold text-slate-900 mb-3">Authentication</h4>
                    <p class="text-slate-700 mb-3">All API requests must include your API key in the request headers:</p>
                    <div class="bg-slate-900 text-white rounded-lg p-4 font-mono text-sm overflow-x-auto">
                        <span class="text-green-400">X-API-Key</span>: vk_your_api_key_here
                    </div>
                </section>

                <!-- Rate Limiting -->
                <section class="mb-8">
                    <h4 class="text-xl font-semibold text-slate-900 mb-3">Rate Limiting</h4>
                    <p class="text-slate-700 mb-3">Each API key is limited to <strong>100 requests per hour</strong>. Rate limit information is included in response headers:</p>
                    <div class="bg-slate-900 text-white rounded-lg p-4 font-mono text-sm overflow-x-auto">
                        <div><span class="text-blue-400">X-RateLimit-Limit</span>: 100</div>
                        <div><span class="text-blue-400">X-RateLimit-Remaining</span>: 95</div>
                        <div><span class="text-blue-400">X-RateLimit-Reset</span>: 1234567890</div>
                    </div>
                    <p class="text-sm text-slate-600 mt-2">When you exceed your rate limit, you'll receive a <code class="bg-slate-100 px-2 py-1 rounded">429 Too Many Requests</code> response.</p>
                </section>

                <!-- Endpoints -->
                <section class="mb-8">
                    <h4 class="text-xl font-semibold text-slate-900 mb-4">API Endpoints</h4>
                    
                    <!-- GET /me -->
                    <div class="border border-slate-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded font-mono text-xs font-semibold">GET</span>
                            <code class="text-sm font-mono">/api/v1/me</code>
                        </div>
                        <p class="text-sm text-slate-600">Get information about the current API key.</p>
                    </div>

                    <!-- GET /videos -->
                    <div class="border border-slate-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded font-mono text-xs font-semibold">GET</span>
                            <code class="text-sm font-mono">/api/v1/videos</code>
                        </div>
                        <p class="text-sm text-slate-600 mb-3">Retrieve all videos in your library.</p>
                        <details class="text-sm">
                            <summary class="cursor-pointer text-blue-600 hover:text-blue-800 font-medium">View Example Response</summary>
                            <div class="bg-slate-900 text-white rounded-lg p-3 mt-2 font-mono text-xs overflow-x-auto">
{
  <span class="text-blue-400">"success"</span>: <span class="text-green-400">true</span>,
  <span class="text-blue-400">"data"</span>: [
    {
      <span class="text-blue-400">"id"</span>: 1,
      <span class="text-blue-400">"video_id"</span>: <span class="text-yellow-400">"dQw4w9WgXcQ"</span>,
      <span class="text-blue-400">"title"</span>: <span class="text-yellow-400">"Amazing Video"</span>,
      <span class="text-blue-400">"visit_count"</span>: 42
    }
  ],
  <span class="text-blue-400">"count"</span>: 1
}
                            </div>
                        </details>
                    </div>

                    <!-- GET /videos/{id} -->
                    <div class="border border-slate-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded font-mono text-xs font-semibold">GET</span>
                            <code class="text-sm font-mono">/api/v1/videos/{video_id}</code>
                        </div>
                        <p class="text-sm text-slate-600">Get details for a specific video.</p>
                    </div>

                    <!-- POST /videos -->
                    <div class="border border-slate-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded font-mono text-xs font-semibold">POST</span>
                            <code class="text-sm font-mono">/api/v1/videos</code>
                        </div>
                        <p class="text-sm text-slate-600 mb-3">Process a new YouTube video and add it to your library.</p>
                        <details class="text-sm">
                            <summary class="cursor-pointer text-blue-600 hover:text-blue-800 font-medium">View Example Request</summary>
                            <div class="bg-slate-900 text-white rounded-lg p-3 mt-2 font-mono text-xs overflow-x-auto">
curl -X POST <?php echo APP_URL; ?>/api/v1/videos \
  -H <span class="text-yellow-400">"X-API-Key: vk_your_key"</span> \
  -H <span class="text-yellow-400">"Content-Type: application/json"</span> \
  -d <span class="text-yellow-400">'{"url": "https://youtube.com/watch?v=..."}'</span>
                            </div>
                        </details>
                    </div>

                    <!-- DELETE /videos/{id} -->
                    <div class="border border-slate-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded font-mono text-xs font-semibold">DELETE</span>
                            <code class="text-sm font-mono">/api/v1/videos/{video_id}</code>
                        </div>
                        <p class="text-sm text-slate-600">Delete a video from your library.</p>
                    </div>

                    <!-- GET /videos/{id}/stats -->
                    <div class="border border-slate-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded font-mono text-xs font-semibold">GET</span>
                            <code class="text-sm font-mono">/api/v1/videos/{video_id}/stats</code>
                        </div>
                        <p class="text-sm text-slate-600">Retrieve analytics and statistics for a specific video.</p>
                    </div>

                    <!-- GET /channels -->
                    <div class="border border-slate-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded font-mono text-xs font-semibold">GET</span>
                            <code class="text-sm font-mono">/api/v1/channels</code>
                        </div>
                        <p class="text-sm text-slate-600">Get videos grouped by YouTube channel.</p>
                    </div>

                    <!-- GET /search -->
                    <div class="border border-slate-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded font-mono text-xs font-semibold">GET</span>
                            <code class="text-sm font-mono">/api/v1/search?q={query}</code>
                        </div>
                        <p class="text-sm text-slate-600">Search your video library by title, channel, or description.</p>
                    </div>
                </section>

                <!-- Code Examples -->
                <section class="mb-8">
                    <h4 class="text-xl font-semibold text-slate-900 mb-4">Code Examples</h4>
                    
                    <!-- cURL -->
                    <div class="mb-4">
                        <h5 class="font-semibold text-slate-800 mb-2">cURL</h5>
                        <div class="bg-slate-900 text-white rounded-lg p-4 font-mono text-xs overflow-x-auto">
<span class="text-gray-400"># Get all videos</span>
curl -X GET <?php echo APP_URL; ?>/api/v1/videos \
  -H <span class="text-yellow-400">"X-API-Key: vk_your_key"</span>

<span class="text-gray-400"># Process a video</span>
curl -X POST <?php echo APP_URL; ?>/api/v1/videos \
  -H <span class="text-yellow-400">"X-API-Key: vk_your_key"</span> \
  -H <span class="text-yellow-400">"Content-Type: application/json"</span> \
  -d <span class="text-yellow-400">'{"url": "https://youtube.com/watch?v=..."}'</span>
                        </div>
                    </div>

                    <!-- JavaScript -->
                    <div class="mb-4">
                        <h5 class="font-semibold text-slate-800 mb-2">JavaScript (fetch)</h5>
                        <div class="bg-slate-900 text-white rounded-lg p-4 font-mono text-xs overflow-x-auto">
<span class="text-purple-400">const</span> API_KEY = <span class="text-yellow-400">'vk_your_key'</span>;

<span class="text-purple-400">async function</span> <span class="text-blue-400">getVideos</span>() {
  <span class="text-purple-400">const</span> response = <span class="text-purple-400">await</span> <span class="text-blue-400">fetch</span>(<span class="text-yellow-400">'<?php echo APP_URL; ?>/api/v1/videos'</span>, {
    headers: { <span class="text-yellow-400">'X-API-Key'</span>: API_KEY }
  });
  <span class="text-purple-400">return await</span> response.<span class="text-blue-400">json</span>();
}
                        </div>
                    </div>

                    <!-- Python -->
                    <div class="mb-4">
                        <h5 class="font-semibold text-slate-800 mb-2">Python (requests)</h5>
                        <div class="bg-slate-900 text-white rounded-lg p-4 font-mono text-xs overflow-x-auto">
<span class="text-purple-400">import</span> requests

API_KEY = <span class="text-yellow-400">'vk_your_key'</span>
headers = {<span class="text-yellow-400">'X-API-Key'</span>: API_KEY}

<span class="text-gray-400"># Get all videos</span>
response = requests.<span class="text-blue-400">get</span>(
    <span class="text-yellow-400">'<?php echo APP_URL; ?>/api/v1/videos'</span>,
    headers=headers
)
videos = response.<span class="text-blue-400">json</span>()
                        </div>
                    </div>
                </section>

                <!-- Error Responses -->
                <section class="mb-8">
                    <h4 class="text-xl font-semibold text-slate-900 mb-3">Common Error Responses</h4>
                    <div class="space-y-3">
                        <div class="border-l-4 border-red-500 bg-red-50 p-3">
                            <p class="font-semibold text-red-900">401 Unauthorized</p>
                            <p class="text-sm text-red-700">Missing or invalid API key</p>
                        </div>
                        <div class="border-l-4 border-orange-500 bg-orange-50 p-3">
                            <p class="font-semibold text-orange-900">429 Too Many Requests</p>
                            <p class="text-sm text-orange-700">Rate limit exceeded (100 requests/hour)</p>
                        </div>
                        <div class="border-l-4 border-yellow-500 bg-yellow-50 p-3">
                            <p class="font-semibold text-yellow-900">404 Not Found</p>
                            <p class="text-sm text-yellow-700">Resource not found</p>
                        </div>
                    </div>
                </section>

                <!-- Best Practices -->
                <section>
                    <h4 class="text-xl font-semibold text-slate-900 mb-3">Best Practices</h4>
                    <ul class="space-y-2 text-sm text-slate-700">
                        <li class="flex items-start gap-2">
                            <span class="text-green-600 mt-1">✓</span>
                            <span><strong>Secure your keys:</strong> Never commit API keys to version control</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-600 mt-1">✓</span>
                            <span><strong>Handle rate limits:</strong> Implement exponential backoff for 429 responses</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-600 mt-1">✓</span>
                            <span><strong>Cache responses:</strong> Store video data locally to reduce API calls</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-600 mt-1">✓</span>
                            <span><strong>Use HTTPS:</strong> All API requests must use HTTPS</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-green-600 mt-1">✓</span>
                            <span><strong>Monitor usage:</strong> Check your API key statistics regularly</span>
                        </li>
                    </ul>
                </section>
            </div>
            <div class="sticky bottom-0 bg-slate-50 border-t border-slate-200 p-4 rounded-b-lg">
                <button 
                    onclick="closeDocumentation()"
                    class="w-full bg-slate-900 text-white py-2 px-4 rounded-md font-medium hover:bg-slate-800 transition"
                >
                    Close Documentation
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
                <h3 class="text-lg font-semibold text-center mb-2">Delete API Key?</h3>
                <p class="text-sm text-slate-600 text-center mb-6">
                    This will permanently delete this API key. Any applications using this key will stop working immediately. This action cannot be undone.
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
                        Delete Key
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let keyToDelete = null;

        // Load API keys on page load
        loadApiKeys();

        function openDocumentation() {
            document.getElementById('documentationModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeDocumentation() {
            document.getElementById('documentationModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        async function loadApiKeys() {
            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_api_keys' })
                });

                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Failed to load API keys');
                }

                renderApiKeys(data.keys);
            } catch (error) {
                console.error('Error loading API keys:', error);
                document.getElementById('apiKeysList').innerHTML = `
                    <div class="p-6 text-center text-red-600">
                        <p>Error loading API keys</p>
                        <p class="text-sm mt-1">${error.message}</p>
                    </div>
                `;
            }
        }

        function renderApiKeys(keys) {
            const container = document.getElementById('apiKeysList');
            
            if (keys.length === 0) {
                container.innerHTML = `
                    <div class="p-6 text-center text-slate-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        <p>No API keys yet</p>
                        <p class="text-sm mt-1">Create your first API key to get started</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = keys.map(key => `
                <div class="p-6 hover:bg-slate-50 transition">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h4 class="font-semibold text-slate-900">${key.key_name}</h4>
                                ${key.is_active ? 
                                    '<span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Active</span>' : 
                                    '<span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full font-medium">Inactive</span>'
                                }
                            </div>
                            <div class="space-y-1 text-sm text-slate-600">
                                <p><span class="font-medium">Key:</span> <code class="bg-slate-100 px-2 py-1 rounded text-xs">${key.api_key.substring(0, 20)}...${key.api_key.substring(key.api_key.length - 8)}</code></p>
                                <p><span class="font-medium">Rate Limit:</span> ${key.rate_limit_per_hour} requests/hour</p>
                                <p><span class="font-medium">Created:</span> ${formatDate(key.created_at)}</p>
                                ${key.last_used_at ? `<p><span class="font-medium">Last Used:</span> ${formatDate(key.last_used_at)}</p>` : ''}
                            </div>
                            <div class="mt-3 flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                                        ${key.total_requests || 0} total requests
                                    </span>
                                    <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                                        ${key.requests_last_hour || 0} last hour
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="ml-4">
                            <button 
                                onclick="deleteApiKey(${key.id})"
                                class="text-red-600 hover:text-red-800 text-sm font-medium"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        async function createApiKey() {
            const name = document.getElementById('keyName').value.trim();
            const btn = document.getElementById('createBtn');
            const error = document.getElementById('createError');
            
            error.classList.add('hidden');
            
            if (!name) {
                error.textContent = 'Please enter a key name';
                error.classList.remove('hidden');
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Creating...';
            
            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'create_api_key',
                        name: name,
                        rate_limit: 100
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('newApiKey').value = data.key.api_key;
                    document.getElementById('newKeyModal').classList.remove('hidden');
                    document.getElementById('keyName').value = '';
                    loadApiKeys();
                } else {
                    error.textContent = data.error || 'Failed to create API key';
                    error.classList.remove('hidden');
                }
            } catch (err) {
                error.textContent = 'Network error. Please try again.';
                error.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Create API Key';
            }
        }

        function copyNewKey() {
            const input = document.getElementById('newApiKey');
            input.select();
            navigator.clipboard.writeText(input.value);
            const success = document.getElementById('copySuccess');
            success.classList.remove('hidden');
            setTimeout(() => success.classList.add('hidden'), 2000);
        }

        function closeNewKeyModal() {
            document.getElementById('newKeyModal').classList.add('hidden');
        }

        function deleteApiKey(keyId) {
            keyToDelete = keyId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            keyToDelete = null;
            document.getElementById('deleteModal').classList.add('hidden');
        }

        async function confirmDelete() {
            if (!keyToDelete) return;

            const btn = document.getElementById('confirmDeleteBtn');
            btn.disabled = true;
            btn.textContent = 'Deleting...';

            try {
                const response = await fetch('/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'delete_api_key',
                        key_id: keyToDelete 
                    })
                });

                const data = await response.json();

                if (data.success) {
                    closeDeleteModal();
                    loadApiKeys();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete API key'));
                    btn.disabled = false;
                    btn.textContent = 'Delete Key';
                }
            } catch (error) {
                console.error('Delete error:', error);
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Delete Key';
            }
        }

        function formatDate(dateString) {
            if (!dateString) return 'Never';
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / (1000 * 60));
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
            if (diffHours < 24) return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
            if (diffDays === 1) return 'Yesterday';
            if (diffDays < 7) return `${diffDays} days ago`;
            if (diffDays < 30) return `${Math.floor(diffDays / 7)} week${Math.floor(diffDays / 7) !== 1 ? 's' : ''} ago`;
            return date.toLocaleDateString();
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

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
                closeNewKeyModal();
                closeDocumentation();
            }
        });
    </script>
</body>
</html>
