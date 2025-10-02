# VidCard API Documentation

## Overview

The VidCard API provides programmatic access to process YouTube videos, manage your video library, and retrieve analytics. All API requests require authentication via API keys.

## Base URL

```
https://vidcard.io/api/v1
```

## Authentication

All API requests must include an API key in the request headers:

```
X-API-Key: vk_your_api_key_here
```

### Getting an API Key

1. Log in to your VidCard account
2. Navigate to **API Keys** from the dashboard
3. Click **Create New API Key**
4. Set a name and rate limit
5. Copy your API key (you won't be able to see it again!)

## Rate Limiting

Each API key has a fixed rate limit of **100 requests per hour**. Rate limit information is included in response headers:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1234567890
```

When you exceed your rate limit, you'll receive a `429 Too Many Requests` response.

## Endpoints

### Get API Key Information

Get information about the current API key.

**Request:**
```http
GET /api/v1/me
X-API-Key: vk_your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "email": "user@example.com",
    "key_name": "Production API Key",
    "rate_limit": 100,
    "created_at": "2025-01-15 10:30:00"
  }
}
```

---

### List All Videos

Retrieve all videos in your library.

**Request:**
```http
GET /api/v1/videos
X-API-Key: vk_your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "video_id": "dQw4w9WgXcQ",
      "title": "Amazing Video Title",
      "description": "Video description...",
      "thumbnail_url": "https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg",
      "channel_name": "Channel Name",
      "channel_url": "https://www.youtube.com/channel/UC...",
      "channel_thumbnail": "https://...",
      "channel_handle": "@channelhandle",
      "youtube_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
      "created_at": "2025-01-15 10:30:00",
      "visit_count": 42
    }
  ],
  "count": 1
}
```

---

### Get Specific Video

Retrieve details for a specific video.

**Request:**
```http
GET /api/v1/videos/{video_id}
X-API-Key: vk_your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "video_id": "dQw4w9WgXcQ",
    "title": "Amazing Video Title",
    "description": "Video description...",
    "thumbnail_url": "https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg",
    "channel_name": "Channel Name",
    "channel_url": "https://www.youtube.com/channel/UC...",
    "channel_thumbnail": "https://...",
    "channel_handle": "@channelhandle",
    "youtube_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
    "created_at": "2025-01-15 10:30:00"
  }
}
```

**Error Response (404):**
```json
{
  "error": "Not Found",
  "message": "Video not found"
}
```

---

### Process New Video

Process a YouTube video and add it to your library.

**Request:**
```http
POST /api/v1/videos
X-API-Key: vk_your_api_key_here
Content-Type: application/json

{
  "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "video_id": "dQw4w9WgXcQ",
    "title": "Amazing Video Title",
    "description": "Video description...",
    "thumbnail_url": "https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg",
    "channel_name": "Channel Name",
    "channel_url": "https://www.youtube.com/channel/UC...",
    "channel_thumbnail": "https://...",
    "channel_handle": "@channelhandle",
    "youtube_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
  },
  "share_url": "https://vidcard.io/?v=dQw4w9WgXcQ"
}
```

**Supported URL Formats:**
- `https://www.youtube.com/watch?v=VIDEO_ID`
- `https://youtu.be/VIDEO_ID`
- `https://www.youtube.com/shorts/VIDEO_ID`
- `https://www.youtube.com/embed/VIDEO_ID`

---

### Delete Video

Delete a video from your library.

**Request:**
```http
DELETE /api/v1/videos/{video_id}
X-API-Key: vk_your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "message": "Video deleted successfully"
}
```

---

### Get Video Statistics

Retrieve analytics for a specific video.

**Request:**
```http
GET /api/v1/videos/{video_id}/stats
X-API-Key: vk_your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_visits": 42,
    "last_visit": "2025-01-15 14:30:00",
    "first_visit": "2025-01-10 09:15:00",
    "recent_visits": [
      {
        "visited_at": "2025-01-15 14:30:00",
        "ip_address": "192.168.1.1",
        "referrer": "https://twitter.com"
      }
    ]
  }
}
```

---

### Get Video Transcript

Retrieve the transcript for a specific video (if available).

**Request:**
```http
GET /api/v1/videos/{video_id}/transcript
X-API-Key: vk_your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "has_transcript": true,
  "transcript": "Full sun\n[Music]\nThat's what I do. Lord,\nI do\n[Music]\nknow.\nI don't know just\ntonight\nevery day...",
  "fetched_at": "2025-01-15 10:45:00",
  "unavailable": false
}
```

**Response (No Transcript):**
```json
{
  "success": true,
  "has_transcript": false,
  "transcript": null,
  "fetched_at": null,
  "unavailable": false
}
```

**Response (Transcript Unavailable):**
```json
{
  "success": true,
  "has_transcript": false,
  "transcript": null,
  "fetched_at": "2025-01-15 10:45:00",
  "unavailable": true
}
```

---

### Fetch Video Transcript

Retrieve and store the transcript for a video. Use this if the transcript wasn't automatically fetched during video processing.

**Request:**
```http
POST /api/v1/videos/{video_id}/transcript
X-API-Key: vk_your_api_key_here
```

**Response (201 Created):**
```json
{
  "success": true,
  "transcript": "Full sun\n[Music]\nThat's what I do. Lord,\nI do\n[Music]\nknow...",
  "fetched_at": "2025-01-15 10:45:00",
  "unavailable": false
}
```

**Response (Transcript Unavailable):**
```json
{
  "success": true,
  "transcript": null,
  "fetched_at": null,
  "unavailable": true
}
```

**Note:** Not all YouTube videos have captions/transcripts available. When a transcript cannot be fetched, the video is marked as `unavailable: true` to prevent repeated fetch attempts. The UI will display "Transcript Unavailable" for these videos.

---

### Get Videos by Channel

Retrieve videos grouped by YouTube channel.

**Request:**
```http
GET /api/v1/channels
X-API-Key: vk_your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "data": {
    "Channel Name": {
      "name": "Channel Name",
      "thumbnail": "https://...",
      "url": "https://www.youtube.com/channel/UC...",
      "videos": [
        {
          "id": 1,
          "video_id": "dQw4w9WgXcQ",
          "title": "Amazing Video Title",
          "visit_count": 42
        }
      ]
    }
  },
  "count": 1
}
```

---

### Search Videos

Search your video library by title, channel name, or description.

**Request:**
```http
GET /api/v1/search?q=amazing
X-API-Key: vk_your_api_key_here
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "video_id": "dQw4w9WgXcQ",
      "title": "Amazing Video Title",
      "description": "Video description...",
      "channel_name": "Channel Name",
      "created_at": "2025-01-15 10:30:00"
    }
  ],
  "count": 1,
  "query": "amazing"
}
```

---

## Error Responses

### 401 Unauthorized

Missing or invalid API key.

```json
{
  "error": "Unauthorized",
  "message": "API key is required. Include X-API-Key header."
}
```

### 403 Forbidden

You don't have access to the requested resource.

```json
{
  "error": "Forbidden",
  "message": "You do not have access to this video"
}
```

### 404 Not Found

Resource not found.

```json
{
  "error": "Not Found",
  "message": "Video not found"
}
```

### 429 Rate Limit Exceeded

You've exceeded your rate limit.

```json
{
  "error": "Rate Limit Exceeded",
  "message": "You have exceeded your rate limit",
  "rate_limit": {
    "allowed": false,
    "current": 100,
    "limit": 100,
    "remaining": 0,
    "reset_at": "2025-01-15 15:00:00"
  }
}
```

### 500 Internal Server Error

Server error occurred.

```json
{
  "error": "Internal Server Error",
  "message": "An unexpected error occurred"
}
```

---

## Code Examples

### cURL

```bash
# Get all videos
curl -X GET https://vidcard.io/api/v1/videos \
  -H "X-API-Key: vk_your_api_key_here"

# Process a new video
curl -X POST https://vidcard.io/api/v1/videos \
  -H "X-API-Key: vk_your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"}'

# Search videos
curl -X GET "https://vidcard.io/api/v1/search?q=amazing" \
  -H "X-API-Key: vk_your_api_key_here"
```

### JavaScript (fetch)

```javascript
const API_KEY = 'vk_your_api_key_here';
const BASE_URL = 'https://vidcard.io/api/v1';

// Get all videos
async function getVideos() {
  const response = await fetch(`${BASE_URL}/videos`, {
    headers: {
      'X-API-Key': API_KEY
    }
  });
  const data = await response.json();
  return data;
}

// Process a new video
async function processVideo(youtubeUrl) {
  const response = await fetch(`${BASE_URL}/videos`, {
    method: 'POST',
    headers: {
      'X-API-Key': API_KEY,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ url: youtubeUrl })
  });
  const data = await response.json();
  return data;
}

// Get video stats
async function getVideoStats(videoId) {
  const response = await fetch(`${BASE_URL}/videos/${videoId}/stats`, {
    headers: {
      'X-API-Key': API_KEY
    }
  });
  const data = await response.json();
  return data;
}
```

### Python (requests)

```python
import requests

API_KEY = 'vk_your_api_key_here'
BASE_URL = 'https://vidcard.io/api/v1'

headers = {
    'X-API-Key': API_KEY
}

# Get all videos
response = requests.get(f'{BASE_URL}/videos', headers=headers)
videos = response.json()

# Process a new video
response = requests.post(
    f'{BASE_URL}/videos',
    headers={**headers, 'Content-Type': 'application/json'},
    json={'url': 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'}
)
result = response.json()

# Search videos
response = requests.get(
    f'{BASE_URL}/search',
    headers=headers,
    params={'q': 'amazing'}
)
results = response.json()
```

### PHP

```php
<?php
$apiKey = 'vk_your_api_key_here';
$baseUrl = 'https://vidcard.io/api/v1';

// Get all videos
$ch = curl_init("$baseUrl/videos");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-Key: $apiKey"
]);
$response = curl_exec($ch);
$videos = json_decode($response, true);
curl_close($ch);

// Process a new video
$ch = curl_init("$baseUrl/videos");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-Key: $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
]));
$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);
?>
```

---

## Best Practices

1. **Secure Your API Keys**: Never commit API keys to version control or expose them in client-side code
2. **Handle Rate Limits**: Implement exponential backoff when you receive 429 responses
3. **Cache Responses**: Cache video data to reduce API calls
4. **Error Handling**: Always check response status codes and handle errors gracefully
5. **Use HTTPS**: All API requests must use HTTPS
6. **Monitor Usage**: Check your API key statistics regularly in the dashboard

---

## Support

For API support or questions:
- Email: support@vidcard.io
- Documentation: https://vidcard.io/api-keys
- GitHub Issues: https://github.com/yourusername/VidCard/issues

---

## Changelog

### Version 1.0.0 (2025-01-15)
- Initial API release
- API key authentication
- Rate limiting per key
- Video processing endpoints
- Analytics endpoints
- Search functionality
