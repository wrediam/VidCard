# Clip Download Feature - Testing Guide

## Pre-Testing Setup

### 1. Environment Setup
```bash
# Copy environment variables
cp .env.example .env

# Edit .env and ensure these are set:
WREDIA_CLIP_API_KEY=WrediaAPI_2025_9f8e7d6c5b4a3210fedcba0987654321abcdef1234567890bcda1ef2a3b4c5d6e7f8g9h0i1j2k3l4m5n6o7p8q9r0s1t2u3v4w5x6y7z8
WREDIA_CLIP_API_URL=https://vid.wredia.com/download/clip
```

### 2. Database Migration
```bash
# Run the migration
psql -U vidcard -d vidcard -f clip_edits_migration.sql

# Verify table was created
psql -U vidcard -d vidcard -c "\d ai_clip_edits"
```

### 3. Restart Server
```bash
# If using Docker
docker-compose restart

# Or restart your PHP server
```

## Test Cases

### Test 1: Timeline UI Display ✓

**Steps:**
1. Log in to VidCard
2. Process a YouTube video (or use existing one)
3. Click "AI Tools" button on a video
4. Click "Generate Clips" or "View Clip Suggestions"
5. Navigate through clip suggestions

**Expected Results:**
- Timeline bar appears below YouTube embed
- Clip segment is highlighted in orange-red gradient
- Start and end handles are visible
- Time labels show correct start/end times
- Duration label shows clip length

**Pass Criteria:**
- [ ] Timeline renders correctly
- [ ] Clip segment positioned accurately
- [ ] Time labels display properly
- [ ] Visual design matches mockup

---

### Test 2: Drag Start Handle ✓

**Steps:**
1. Open a clip suggestion with timeline
2. Hover over the orange start handle (left side)
3. Click and drag the start handle to the left
4. Release mouse button

**Expected Results:**
- Cursor changes to `ew-resize` during drag
- Clip segment updates in real-time
- Start time label updates
- Duration label updates
- YouTube embed reloads with new start time
- Cannot drag past end handle (1s minimum)

**Pass Criteria:**
- [ ] Handle drags smoothly
- [ ] UI updates in real-time
- [ ] Embed refreshes with new time
- [ ] Constraints enforced

---

### Test 3: Drag End Handle ✓

**Steps:**
1. Open a clip suggestion with timeline
2. Hover over the red end handle (right side)
3. Click and drag the end handle to the right
4. Release mouse button

**Expected Results:**
- Cursor changes to `ew-resize` during drag
- Clip segment expands/contracts
- End time label updates
- Duration label updates
- YouTube embed reloads with new end time
- Cannot drag before start handle (1s minimum)

**Pass Criteria:**
- [ ] Handle drags smoothly
- [ ] UI updates in real-time
- [ ] Embed refreshes with new time
- [ ] Constraints enforced

---

### Test 4: Save Clip Edit ✓

**Steps:**
1. Adjust clip timing using handles
2. Open browser DevTools → Network tab
3. Release the handle after dragging
4. Check network requests

**Expected Results:**
- POST request to `/` with action `save_clip_edit`
- Request includes video_id, clip_index, start_time, end_time
- Response returns `{"success": true}`
- Database record created/updated in `ai_clip_edits` table

**Verification:**
```sql
SELECT * FROM ai_clip_edits 
WHERE video_id = 'YOUR_VIDEO_ID' 
ORDER BY updated_at DESC 
LIMIT 1;
```

**Pass Criteria:**
- [ ] API request sent successfully
- [ ] Database record exists
- [ ] Edited times match UI values
- [ ] No console errors

---

### Test 5: Download Clip (Success) ✓

**Steps:**
1. Open a clip suggestion
2. Optionally adjust timing
3. Click "Download Clip" button
4. Wait for processing

**Expected Results:**
- Button shows loading state ("Processing...")
- POST request to `/download_clip.php`
- New tab opens with download link
- Success toast notification appears
- Button returns to normal state

**Verification:**
- Check browser downloads folder for MP4 file
- Verify clip duration matches selected range
- Play clip to confirm content

**Pass Criteria:**
- [ ] Download initiates successfully
- [ ] File downloads to device
- [ ] Clip duration is correct
- [ ] Video quality is good (1080p)
- [ ] No errors in console

---

### Test 6: Download Clip (Error Handling) ✓

**Steps:**
1. Temporarily set invalid API key in `.env`
2. Restart server
3. Try to download a clip

**Expected Results:**
- Error toast notification appears
- Button returns to normal state
- Error logged in browser console
- Helpful error message displayed

**Pass Criteria:**
- [ ] Error handled gracefully
- [ ] User sees clear error message
- [ ] UI doesn't break
- [ ] Can retry after fixing

---

### Test 7: Multiple Clips Navigation ✓

**Steps:**
1. Generate clip suggestions (should have 3-4 clips)
2. Use arrow buttons to navigate between clips
3. Adjust timing on clip #1
4. Navigate to clip #2
5. Navigate back to clip #1

**Expected Results:**
- Timeline resets for each clip
- Edited clip #1 shows saved changes
- Each clip has independent timeline state
- Navigation buttons enable/disable correctly

**Pass Criteria:**
- [ ] Timeline updates per clip
- [ ] Edits persist across navigation
- [ ] No state leakage between clips
- [ ] Smooth transitions

---

### Test 8: Edge Cases ✓

#### 8a. Very Short Clip (< 5 seconds)
**Steps:**
1. Create a clip with 3-second duration
2. Try to drag handles closer together

**Expected:**
- Minimum 1-second duration enforced
- Cannot make clip shorter than 1 second

#### 8b. Very Long Clip (> 5 minutes)
**Steps:**
1. Drag handles to create 6-minute clip
2. Attempt download

**Expected:**
- Download works normally
- May take longer to process

#### 8c. Start at Video Beginning (0:00)
**Steps:**
1. Drag start handle to far left (0:00)
2. Download clip

**Expected:**
- Clip starts at 0:00
- Download succeeds

#### 8d. End at Video End
**Steps:**
1. Drag end handle to far right
2. Download clip

**Expected:**
- Clip ends at video duration
- Download succeeds

**Pass Criteria:**
- [ ] All edge cases handled
- [ ] No crashes or errors
- [ ] Reasonable constraints enforced

---

### Test 9: Concurrent Users ✓

**Steps:**
1. Log in as User A
2. Edit clip timing for Video X
3. Log in as User B (different browser/incognito)
4. Edit same Video X clip

**Expected Results:**
- Each user has independent edits
- User A's edits don't affect User B
- Database stores separate records per user

**Verification:**
```sql
SELECT user_id, edited_start_time, edited_end_time 
FROM ai_clip_edits 
WHERE video_id = 'VIDEO_X';
```

**Pass Criteria:**
- [ ] Edits are user-specific
- [ ] No data conflicts
- [ ] Proper isolation

---

### Test 10: Performance ✓

**Steps:**
1. Open clip with timeline
2. Rapidly drag handles back and forth
3. Monitor browser performance

**Expected Results:**
- UI remains responsive
- No lag or stuttering
- Smooth animations
- No memory leaks

**Monitoring:**
- Open DevTools → Performance tab
- Record while dragging
- Check for frame drops

**Pass Criteria:**
- [ ] 60 FPS maintained
- [ ] No excessive repaints
- [ ] Memory stable
- [ ] CPU usage reasonable

---

## Integration Tests

### Test 11: Full User Journey ✓

**Complete Flow:**
1. ✓ Log in to VidCard
2. ✓ Process a new YouTube video
3. ✓ Wait for transcript generation
4. ✓ Open AI Tools modal
5. ✓ Generate clip suggestions
6. ✓ View first clip suggestion
7. ✓ Verify timeline appears
8. ✓ Drag start handle -5 seconds
9. ✓ Drag end handle +3 seconds
10. ✓ Verify embed updates
11. ✓ Click "Download Clip"
12. ✓ Verify download succeeds
13. ✓ Play downloaded clip
14. ✓ Confirm timing is correct

**Pass Criteria:**
- [ ] All steps complete without errors
- [ ] Downloaded clip matches expectations
- [ ] User experience is smooth

---

## Browser Compatibility

Test on multiple browsers:

- [ ] **Chrome** (latest)
- [ ] **Firefox** (latest)
- [ ] **Safari** (latest)
- [ ] **Edge** (latest)

**Test:**
- Timeline rendering
- Drag interactions
- Download functionality
- Toast notifications

---

## Mobile Responsiveness

### Test 12: Mobile/Tablet ✓

**Devices to Test:**
- [ ] iPhone (Safari)
- [ ] Android (Chrome)
- [ ] iPad (Safari)

**Steps:**
1. Open clip suggestions on mobile
2. Try to interact with timeline

**Expected:**
- Timeline may not be draggable (touch not implemented)
- Download button still works
- UI is responsive and readable

**Note:** Touch drag support is a future enhancement.

---

## API Testing

### Test 13: Direct API Call ✓

**Using cURL:**
```bash
curl -X POST https://vid.wredia.com/download/clip \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
    "start_time": 30,
    "end_time": 60,
    "resolution": "1080p",
    "link": true
  }'
```

**Expected Response:**
```json
{
  "download_url": "https://...",
  "filename": "clip_dQw4w9WgXcQ_30-60.mp4"
}
```

**Pass Criteria:**
- [ ] API responds successfully
- [ ] Download URL is valid
- [ ] File downloads correctly

---

## Security Testing

### Test 14: Authentication ✓

**Steps:**
1. Log out of VidCard
2. Try to access `/download_clip.php` directly

**Expected:**
- 401 Unauthorized response
- No clip download

### Test 15: Authorization ✓

**Steps:**
1. Log in as User A
2. Get video_id from User B's video
3. Try to download User B's clip

**Expected:**
- 403 Forbidden or 404 Not Found
- Access denied message

**Pass Criteria:**
- [ ] Proper authentication required
- [ ] User can only access own videos
- [ ] No data leakage

---

## Regression Testing

After any code changes, verify:

- [ ] Existing clip suggestions still work
- [ ] Post suggestions unaffected
- [ ] Video processing unchanged
- [ ] Transcript feature works
- [ ] Analytics still accurate
- [ ] No broken links or 404s

---

## Known Limitations

1. **Video Duration**: Currently estimated, not fetched from YouTube API
2. **Touch Support**: Drag not supported on mobile (future enhancement)
3. **Batch Download**: Can only download one clip at a time
4. **Format Options**: Only MP4 supported currently

---

## Reporting Issues

When reporting bugs, include:

1. **Browser & Version**: e.g., Chrome 120.0
2. **Steps to Reproduce**: Detailed sequence
3. **Expected vs Actual**: What should happen vs what happened
4. **Screenshots/Video**: Visual evidence
5. **Console Errors**: Any JavaScript errors
6. **Network Logs**: Failed API requests
7. **Environment**: Development, staging, or production

---

## Success Criteria

All tests must pass before considering feature complete:

- [ ] All UI tests pass (Tests 1-3)
- [ ] Data persistence works (Test 4)
- [ ] Download succeeds (Test 5)
- [ ] Errors handled gracefully (Test 6)
- [ ] Navigation works (Test 7)
- [ ] Edge cases covered (Test 8)
- [ ] Multi-user support (Test 9)
- [ ] Performance acceptable (Test 10)
- [ ] Full journey works (Test 11)
- [ ] Browser compatible (Tests 12)
- [ ] API functional (Test 13)
- [ ] Security verified (Tests 14-15)
- [ ] No regressions

---

## Post-Deployment Checklist

After deploying to production:

- [ ] Verify `.env` has correct API key
- [ ] Run database migration
- [ ] Test with real YouTube videos
- [ ] Monitor error logs for 24 hours
- [ ] Check download success rate
- [ ] Gather user feedback
- [ ] Update documentation if needed

---

## Automated Testing (Future)

Consider implementing:

1. **Unit Tests**: Test individual functions
2. **Integration Tests**: Test API endpoints
3. **E2E Tests**: Playwright/Cypress for UI
4. **Load Tests**: Concurrent download requests
5. **CI/CD**: Automated testing on commits
