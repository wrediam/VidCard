# Testing AI Clip Suggestions

## Quick Test Guide

### Prerequisites
1. ✅ Environment variable set: `N8N_CLIP_WEBHOOK_URL=https://n8n.wredia.com/webhook/generate_clips`
2. ✅ Database migration applied (auto-runs on first load)
3. ✅ Video with transcript available in the system

### Test Steps

#### 1. Basic Functionality Test
```bash
# Test the n8n webhook directly
curl -X POST \
  https://n8n.wredia.com/webhook/generate_clips \
  -H "Content-Type: text/plain" \
  --data-binary @example_curl_response.txt
```

**Expected Response:**
```json
[{
  "output": {
    "clip_suggestions": [
      {
        "start_time_ms": 16080,
        "end_time_ms": 20640,
        "suggested_title": "...",
        "reason": "..."
      }
    ]
  }
}]
```

#### 2. UI Test Flow
1. Navigate to dashboard
2. Click on a video that has a transcript
3. Click "View Transcript" button
4. Click "AI Tools" button
5. Click "Generate Clips" button
6. Wait for processing (up to 2 minutes)
7. Verify clip suggestions appear
8. Test navigation (prev/next buttons)
9. Verify YouTube embed plays correctly
10. Click "Regenerate" to test regeneration

#### 3. Database Verification
```sql
-- Check if suggestions were saved
SELECT 
    video_id, 
    user_id, 
    jsonb_array_length(clip_suggestions) as clip_count,
    generated_at 
FROM ai_clip_suggestions 
ORDER BY generated_at DESC 
LIMIT 5;

-- View actual suggestions
SELECT 
    video_id,
    jsonb_pretty(clip_suggestions) as suggestions
FROM ai_clip_suggestions 
WHERE video_id = 'YOUR_VIDEO_ID';
```

#### 4. Error Scenarios to Test

**No Transcript:**
- Try generating clips for a video without transcript
- Expected: Error message "Transcript not available for this video"

**Network Timeout:**
- If webhook takes > 120 seconds
- Expected: Timeout error with helpful message

**Invalid Response:**
- Mock invalid JSON from webhook
- Expected: "Invalid response format from n8n webhook"

### Performance Benchmarks

| Transcript Length | Expected Processing Time |
|-------------------|-------------------------|
| < 5 minutes       | 30-60 seconds          |
| 5-15 minutes      | 60-90 seconds          |
| 15-30 minutes     | 90-120 seconds         |
| > 30 minutes      | May timeout (consider optimization) |

### Troubleshooting

#### Issue: "Failed to generate clip suggestions from n8n webhook"
**Solutions:**
1. Check error logs: `tail -f /var/log/apache2/error.log`
2. Verify webhook URL is correct
3. Test webhook directly with curl
4. Check n8n workflow is active
5. Verify timeout is sufficient (120 seconds)

#### Issue: Timeout after 120 seconds
**Solutions:**
1. Check n8n workflow execution time
2. Optimize AI processing in n8n
3. Consider async processing for very long videos
4. Increase timeout in `ai_clips.php` if needed

#### Issue: Clips not displaying
**Solutions:**
1. Check browser console for JavaScript errors
2. Verify video_id is being passed correctly
3. Check YouTube embed URL format
4. Verify start/end times are in seconds (not milliseconds)

#### Issue: Database not saving suggestions
**Solutions:**
1. Check migration ran: `SELECT * FROM ai_clip_suggestions LIMIT 1;`
2. Verify user has permission to write
3. Check error logs for SQL errors
4. Verify JSONB format is correct

### Sample Test Data

**Good Response (from your example):**
```json
[{
  "output": {
    "clip_suggestions": [
      {
        "start_time_ms": 0,
        "end_time_ms": 45000,
        "suggested_title": "From Sorrow to Victory: The Rose Blooms Again",
        "reason": "This poetic segment captures the emotional transformation..."
      },
      {
        "start_time_ms": 120000,
        "end_time_ms": 150000,
        "suggested_title": "6 Souls Saved: Power of Tuesday Night Outreach",
        "reason": "Highlights a real-world testimony of evangelism success..."
      }
    ]
  }
}]
```

### Success Criteria

✅ Webhook responds within 120 seconds  
✅ Suggestions saved to database  
✅ UI displays clips with proper formatting  
✅ YouTube embeds load with correct timestamps  
✅ Navigation works (prev/next)  
✅ Regenerate creates new suggestions  
✅ Error messages are user-friendly  
✅ Loading indicator shows during processing  

### Known Limitations

1. **Processing Time**: AI analysis can take 1-2 minutes for long videos
2. **YouTube Embed**: Requires video to be embeddable (not age-restricted)
3. **Timestamp Accuracy**: Depends on transcript quality and AI analysis
4. **Concurrent Requests**: Multiple users generating clips simultaneously may slow down

### Monitoring

**Key Metrics to Track:**
- Average processing time
- Success rate (successful generations / total attempts)
- Timeout rate
- User engagement (clips viewed, regenerations)

**Log Files to Monitor:**
```bash
# Apache error log
tail -f /var/log/apache2/error.log | grep "clip"

# Check for specific errors
grep "AI clip generation error" /var/log/apache2/error.log

# Check for timeouts
grep "timeout" /var/log/apache2/error.log | grep "clip"
```
