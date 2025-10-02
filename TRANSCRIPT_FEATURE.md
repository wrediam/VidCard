# Video Transcript Feature

## Overview

VidCard now automatically fetches and stores YouTube video transcripts when videos are processed. Users can view transcripts in a beautiful modal and retrieve them on-demand if not initially available.

## Setup Requirements

**Environment Variable Required:**
Add the Caption API key to your `.env` file:

```bash
CAPTION_API_KEY=XXXXXXX
```

This key is already included in `.env.example` and configured in `config.php`. Without this key, transcript fetching will fail silently.

## Features Implemented

### ✅ Database Schema
- **`transcript_raw`** (JSONB) - Stores complete YouTube caption data
- **`transcript_text`** (TEXT) - Clean, readable transcript without timestamps
- **`transcript_fetched_at`** (TIMESTAMP) - When transcript was retrieved
- **`transcript_unavailable`** (BOOLEAN) - Flag indicating transcript is not available
- Full-text search index on transcript_text
- Auto-migration on application startup

### ✅ Backend Processing
**`transcript.php`** - Complete transcript service:
- `fetchTranscript()` - Retrieves captions from vid.wredia.com API
- `extractCleanText()` - Parses complex JSON structure to plain text
- `saveTranscript()` - Stores both raw and clean versions
- `markUnavailable()` - Marks video as having no transcript available
- `getTranscript()` - Retrieves stored transcript with unavailable flag
- `processTranscript()` - End-to-end fetch and save (marks unavailable on failure)
- `hasTranscript()` - Check if transcript exists

**`video.php`** - Integrated with video processing:
- Automatically attempts transcript fetch when saving new videos
- Non-blocking (doesn't fail video save if transcript unavailable)
- Marks videos as unavailable if transcript cannot be fetched
- Logs errors for debugging

### ✅ API Endpoints

#### Dashboard Endpoints (index.php)
```
POST / with action=get_transcript
POST / with action=fetch_transcript
```

#### REST API Endpoints (api.php)
```
GET  /api/v1/videos/{video_id}/transcript  - Get existing transcript
POST /api/v1/videos/{video_id}/transcript  - Fetch new transcript
```

All endpoints:
- Require authentication
- Verify video ownership
- Return JSON with transcript text, metadata, and `unavailable` flag
- Mark videos as unavailable when transcripts cannot be fetched
- Prevent repeated fetch attempts for unavailable transcripts

### ✅ Dashboard UI

**Transcript Button:**
- Shows "View Transcript" if transcript exists
- Shows "Retrieve Transcript" if not yet fetched
- Shows "Transcript Unavailable" (grayed out, non-clickable) if transcript cannot be fetched
- Appears on all video cards (grid and list views)
- Blue color for active buttons, gray for unavailable

**Transcript Modal:**
- Full-screen overlay with scrollable content
- Clean typography with proper line breaks
- Copy to clipboard functionality
- Shows fetch timestamp
- Escape key to close
- Graceful error handling

**User Flow:**
1. Video processed → Transcript auto-fetched (if available)
2. User clicks "View Transcript" → Modal opens instantly
3. User clicks "Retrieve Transcript" → Fetches, then displays
4. If transcript unavailable → Button changes to "Transcript Unavailable" (non-clickable)
5. User can copy transcript with one click
6. Button updates to "View Transcript" after successful fetch
7. No repeated fetch attempts for unavailable transcripts

## Technical Details

### Caption API
- **Endpoint:** `https://vid.wredia.com/captions/en`
- **Parameters:** `url` (YouTube URL), `format=raw`
- **Authentication:** X-API-Key header
- **Response:** Nested JSON with events and segments

### Data Extraction
The raw caption data contains:
- `events[]` - Array of caption events with timestamps
- `segs[]` - Text segments within each event
- `utf8` - Actual text content
- `\n` - Newline markers

The extraction function:
1. Iterates through all events and segments
2. Concatenates text, respecting newlines
3. Removes duplicate line breaks
4. Returns clean, readable text

### Error Handling
- **No captions available:** Marks video as unavailable, prevents retry
- **API timeout:** 10-second timeout, marks as unavailable, logs error
- **Network failure:** Marks as unavailable, displays error in modal
- **Invalid response:** Marks as unavailable, handles gracefully
- **Unavailable flag:** Persisted in database to prevent repeated failed attempts

## Database Migration

The migration runs automatically on first page load after deployment. It:
1. Adds four new columns to `videos` table (raw, text, fetched_at, unavailable)
2. Creates full-text search index
3. Creates performance index for has_transcript queries
4. Adds column comments for documentation

Manual migration (if needed):
```bash
psql -U vidcard -d vidcard -f transcript_migration.sql
```

## Usage Examples

### Dashboard
```javascript
// View existing transcript
viewTranscript('dQw4w9WgXcQ', true);

// Fetch new transcript
viewTranscript('dQw4w9WgXcQ', false);

// Copy transcript
copyTranscript();
```

### API (cURL)
```bash
# Get transcript
curl -X GET https://vidcard.io/api/v1/videos/dQw4w9WgXcQ/transcript \
  -H "X-API-Key: vk_your_key"

# Fetch transcript
curl -X POST https://vidcard.io/api/v1/videos/dQw4w9WgXcQ/transcript \
  -H "X-API-Key: vk_your_key"
```

### API (JavaScript)
```javascript
// Get transcript
const response = await fetch('/api/v1/videos/dQw4w9WgXcQ/transcript', {
  headers: { 'X-API-Key': 'vk_your_key' }
});
const data = await response.json();
console.log(data.transcript);

// Fetch transcript
const response = await fetch('/api/v1/videos/dQw4w9WgXcQ/transcript', {
  method: 'POST',
  headers: { 'X-API-Key': 'vk_your_key' }
});
```

## Files Modified/Created

### Created
- `transcript.php` - Transcript service class
- `transcript_migration.sql` - Database schema changes
- `TRANSCRIPT_FEATURE.md` - This documentation

### Modified
- `video.php` - Added transcript fetching to video processing
- `index.php` - Added transcript endpoints and auto-migration
- `api.php` - Added REST API transcript endpoints
- `views/dashboard.php` - Added transcript button and modal UI

## Performance Considerations

1. **Non-blocking fetch:** Transcript retrieval doesn't delay video processing
2. **Indexed queries:** Full-text search index for fast transcript searches
3. **Cached data:** Transcripts stored in database, not fetched repeatedly
4. **Lazy loading:** Transcripts only fetched when needed
5. **10-second timeout:** Prevents hanging on slow API responses

## Future Enhancements

Potential improvements:
- [ ] Search within transcripts
- [ ] Highlight search terms in transcript view
- [ ] Download transcript as .txt or .srt file
- [ ] Multi-language transcript support
- [ ] Timestamp navigation (click transcript to jump to video time)
- [ ] AI-powered transcript summarization
- [ ] Transcript editing/corrections
- [ ] Automatic transcript translation

## Troubleshooting

### Transcript not fetching
- **Check environment variable:** Ensure `CAPTION_API_KEY` is set in your `.env` file
- **Verify API key:** The key should match the one in `.env.example`
- **Check video captions:** Verify the video has captions enabled on YouTube
- **Review error logs:** Check PHP error logs for API failures
- **Test connectivity:** Ensure network access to `vid.wredia.com`
- **Restart server:** After adding the env variable, restart your web server/Docker container

### Database errors
- Run migration manually if auto-migration fails
- Check PostgreSQL permissions
- Verify JSONB support (PostgreSQL 9.4+)

### UI not updating
- Hard refresh browser (Cmd+Shift+R)
- Check browser console for JavaScript errors
- Verify session is active

## Support

For issues or questions about the transcript feature:
- Check error logs in browser console
- Review server logs for API errors
- Verify database migration completed successfully
- Test with a known video that has captions
