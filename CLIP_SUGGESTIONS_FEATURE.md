# AI Clip Suggestions Feature

## Overview
The AI Clip Suggestions feature generates viral-worthy video clip recommendations using AI analysis of video transcripts via n8n webhook integration.

## Features
- **AI-Powered Analysis**: Analyzes video transcripts to identify engaging moments
- **Timestamp Precision**: Provides exact start/end times in milliseconds
- **YouTube Embed Preview**: Live preview of suggested clips with YouTube embed player
- **Regeneration Support**: Users can regenerate suggestions for different options
- **Database Persistence**: Suggestions are saved to avoid redundant API calls

## Architecture

### Backend Components

#### 1. Database (`ai_clips_migration.sql`)
- **Table**: `ai_clip_suggestions`
- **Columns**:
  - `id`: Primary key
  - `video_id`: Foreign key to videos table
  - `user_id`: Foreign key to users table
  - `clip_suggestions`: JSONB array of clip objects
  - `generated_at`: Timestamp of generation

#### 2. Service Class (`ai_clips.php`)
- **Class**: `AIClips`
- **Methods**:
  - `generateClipSuggestions()`: Calls n8n webhook with transcript
  - `saveClipSuggestions()`: Persists suggestions to database
  - `getClipSuggestions()`: Retrieves saved suggestions
  - `hasSuggestions()`: Checks if suggestions exist

#### 3. API Routes (`index.php`)
- **POST** `/` with `action=generate_clip_suggestions`: Generate new suggestions
- **POST** `/` with `action=get_clip_suggestions`: Retrieve saved suggestions

### Frontend Components

#### UI Elements (`dashboard.php`)
1. **AI Tools Selection Grid**: Two-column layout with Posts and Clips options
2. **Clip Suggestions Container**: 
   - Navigation controls (prev/next)
   - Clip title and reason
   - Duration and timestamp display
   - YouTube embed with start/end parameters
3. **Regenerate Button**: Allows users to generate new suggestions

#### JavaScript Functions
- `generateClipSuggestions()`: Fetches suggestions from backend
- `renderClipSuggestions()`: Initializes carousel with suggestions
- `showClip()`: Displays specific clip with YouTube embed
- `previousClip()` / `nextClip()`: Navigation functions

## n8n Webhook Integration

### Webhook URL
```
https://n8n.wredia.com/webhook/generate_clips
```

### Request Format
- **Method**: POST
- **Content-Type**: text/plain
- **Body**: Raw transcript text

### Response Format
```json
[{
  "output": {
    "clip_suggestions": [
      {
        "start_time_ms": 16080,
        "end_time_ms": 20640,
        "suggested_title": "Hilarious Staff Shoutouts!",
        "reason": "This segment features a funny moment..."
      }
    ]
  }
}]
```

## YouTube Embed Format

The feature uses YouTube's embed URL with start and end parameters:
```
https://www.youtube.com/embed/{video_id}?start={start_seconds}&end={end_seconds}&autoplay=0
```

**Note**: YouTube requires seconds (not milliseconds), so the frontend converts:
```javascript
const startSeconds = Math.floor(clip.start_time_ms / 1000);
const endSeconds = Math.floor(clip.end_time_ms / 1000);
```

## Environment Variables

Add to `.env`:
```bash
N8N_CLIP_WEBHOOK_URL=https://n8n.wredia.com/webhook/generate_clips
```

## Database Migration

The migration runs automatically on first load. Manual migration:
```bash
psql -U vidcard -d vidcard -f ai_clips_migration.sql
```

## Usage Flow

1. User clicks "AI Tools" button on a video
2. User selects "Clip Suggestions" from the grid
3. System checks for transcript availability
4. Backend sends transcript to n8n webhook
5. n8n processes with AI and returns clip suggestions
6. Suggestions saved to database
7. Frontend displays first clip with YouTube embed
8. User navigates through suggestions with prev/next buttons
9. User can regenerate for new suggestions

## Error Handling

- **No Transcript**: Shows error if transcript not available
- **Webhook Timeout**: 120-second timeout on n8n request (AI processing can take time)
- **Invalid Response**: Validates response format from n8n
- **Network Errors**: User-friendly error messages with detailed logging
- **Loading Indicator**: Shows "This may take up to 2 minutes" message to users

## Future Enhancements

- Export clips to video editing software
- Share individual clips on social media
- Download clip metadata as JSON
- Batch clip generation for multiple videos
- Custom clip duration preferences
- AI-powered thumbnail suggestions for clips
