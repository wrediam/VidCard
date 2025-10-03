# YouTube Clip Download Integration

## Overview

This integration adds the ability to download AI-suggested clips with an interactive timeline UI that allows users to fine-tune clip start/end times before downloading.

## Features

### 1. **Interactive Timeline UI**
- **Visual Timeline**: Horizontal bar showing the full video duration
- **Highlighted Segment**: Color-coded section representing the current clip
- **Draggable Handles**: Orange (start) and red (end) handles for precise time adjustment
- **Real-time Updates**: YouTube embed updates instantly as you drag
- **Time Display**: Shows start time, end time, and duration

### 2. **Clip Download**
- **API Integration**: Uses Wredia Clip Download API
- **Format Support**: Downloads clips in MP4 format
- **Resolution Options**: Supports 1080p (configurable)
- **Optional Features**: HDR and subtitle support available

### 3. **Data Persistence**
- **Edit Tracking**: Saves user-edited clip times to database
- **Original Preservation**: Keeps original AI suggestions for reference
- **Conflict Resolution**: Automatically updates existing edits

## Architecture

### Frontend Components

#### Timeline UI (`dashboard.php`)
```
┌─────────────────────────────────────┐
│  Adjust Clip Timing                │
│  0:30 → 1:45 | 75s                 │
│                                     │
│  [────────▓▓▓▓▓▓▓▓────────────]    │
│           ↑      ↑                  │
│        start    end                 │
│                                     │
│  [    Download Clip    ]            │
└─────────────────────────────────────┘
```

#### JavaScript Functions
- `initializeTimeline()` - Sets up timeline with current clip data
- `updateTimelineUI()` - Updates visual position of clip segment
- `setupTimelineDragHandlers()` - Handles mouse drag events
- `updateClipEmbed()` - Refreshes YouTube iframe with new times
- `saveClipEdit()` - Persists changes to backend
- `downloadClip()` - Triggers clip download via API

### Backend Components

#### API Endpoints

**1. Save Clip Edit** (`index.php`)
```php
POST /
{
  "action": "save_clip_edit",
  "video_id": "VIDEO_ID",
  "clip_index": 0,
  "start_time": 30,
  "end_time": 105
}
```

**2. Download Clip** (`download_clip.php`)
```php
POST /download_clip.php
{
  "video_id": "VIDEO_ID",
  "start_time": 30,
  "end_time": 105,
  "clip_index": 0,
  "resolution": "1080p"
}
```

#### Database Schema

**Table: `ai_clip_edits`**
```sql
CREATE TABLE ai_clip_edits (
    id SERIAL PRIMARY KEY,
    video_id VARCHAR(20) NOT NULL,
    user_id INTEGER NOT NULL,
    clip_index INTEGER NOT NULL,
    original_start_time INTEGER NOT NULL,
    original_end_time INTEGER NOT NULL,
    edited_start_time INTEGER NOT NULL,
    edited_end_time INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Installation & Setup

### 1. Environment Configuration

Add to your `.env` file:
```bash
WREDIA_CLIP_API_KEY=WrediaAPI_2025_9f8e7d6c5b4a3210fedcba0987654321abcdef1234567890bcda1ef2a3b4c5d6e7f8g9h0i1j2k3l4m5n6o7p8q9r0s1t2u3v4w5x6y7z8
WREDIA_CLIP_API_URL=https://vid.wredia.com/download/clip
```

### 2. Database Migration

Run the migration to create the clip edits table:
```bash
psql -U vidcard -d vidcard -f clip_edits_migration.sql
```

### 3. Verify Configuration

Check that `config.php` has the constants defined:
```php
define('WREDIA_CLIP_API_KEY', getenv('WREDIA_CLIP_API_KEY') ?: '');
define('WREDIA_CLIP_API_URL', getenv('WREDIA_CLIP_API_URL') ?: 'https://vid.wredia.com/download/clip');
```

## User Flow

1. **View AI Suggestions**
   - User opens AI Tools modal
   - Clicks "Generate Clips" or "View Clip Suggestions"
   - AI-suggested clips are displayed

2. **Adjust Clip Timing**
   - Timeline shows the suggested clip segment
   - User drags start/end handles to fine-tune
   - YouTube embed updates in real-time
   - Changes are auto-saved to database

3. **Download Clip**
   - User clicks "Download Clip" button
   - Backend calls Wredia API with current times
   - Download link opens in new tab
   - User receives the clip file

## API Integration Details

### Wredia Clip Download API

**Endpoint**: `POST https://vid.wredia.com/download/clip`

**Headers**:
```
Content-Type: application/json
X-API-Key: YOUR_API_KEY
```

**Request Body**:
```json
{
  "url": "https://www.youtube.com/watch?v=VIDEO_ID",
  "start_time": 30,
  "end_time": 90,
  "resolution": "1080p",
  "hdr": true,
  "subtitle": {
    "lang": "en",
    "burn": true,
    "translate": false
  },
  "link": true
}
```

**Time Format Options**:
- Seconds: `30`, `90.5`
- MM:SS: `"01:30"`, `"00:45"`
- HH:MM:SS: `"00:01:30"`, `"01:23:45"`

**Response**:
```json
{
  "download_url": "https://...",
  "filename": "clip_VIDEO_ID_30-90.mp4"
}
```

## UI/UX Design

### Color Scheme
- **Timeline Bar**: Slate gray (`bg-slate-200`)
- **Clip Segment**: Orange to red gradient (`from-orange-400 to-red-500`)
- **Start Handle**: Orange (`bg-orange-600`)
- **End Handle**: Red (`bg-red-600`)
- **Download Button**: Green gradient (`from-green-600 to-emerald-600`)

### Interactions
- **Hover**: Handles darken on hover for better visibility
- **Drag**: Cursor changes to `ew-resize` during drag
- **Constraints**: Minimum 1-second clip duration enforced
- **Smooth**: 150ms transition for visual updates

## Error Handling

### Frontend
- Network errors show toast notification
- Invalid time ranges prevented by constraints
- Loading states on buttons during API calls

### Backend
- Authentication required for all endpoints
- Video ownership verification
- API key validation
- Comprehensive error logging

## Testing Guide

See `CLIP_DOWNLOAD_TESTING.md` for detailed testing instructions.

## Future Enhancements

1. **Video Duration Detection**: Fetch actual video duration from YouTube API
2. **Waveform Visualization**: Show audio waveform on timeline
3. **Keyboard Shortcuts**: Arrow keys for fine-tuning (±1 second)
4. **Preset Durations**: Quick buttons for 15s, 30s, 60s clips
5. **Batch Download**: Download multiple clips at once
6. **Format Options**: Support for different video formats and codecs
7. **Preview Mode**: Preview clip before downloading

## Troubleshooting

### Timeline not appearing
- Check browser console for JavaScript errors
- Verify timeline HTML elements exist in DOM
- Ensure `showClip()` function is called

### Download fails
- Verify `WREDIA_CLIP_API_KEY` is set in `.env`
- Check API endpoint is accessible
- Review backend logs for API errors
- Confirm video URL is valid

### Edits not saving
- Check database migration ran successfully
- Verify user authentication
- Review browser network tab for failed requests

## Files Modified/Created

### Created
- `/download_clip.php` - Clip download API endpoint
- `/clip_edits_migration.sql` - Database migration
- `/CLIP_DOWNLOAD_INTEGRATION.md` - This documentation
- `/CLIP_DOWNLOAD_TESTING.md` - Testing guide

### Modified
- `/views/dashboard.php` - Added timeline UI and JavaScript
- `/index.php` - Added save_clip_edit action handler
- `/config.php` - Added API configuration constants
- `/.env.example` - Added API key examples

## Support

For issues or questions:
1. Check error logs in browser console and server logs
2. Verify all environment variables are set
3. Ensure database migration completed successfully
4. Review API documentation at https://vid.wredia.com/docs
