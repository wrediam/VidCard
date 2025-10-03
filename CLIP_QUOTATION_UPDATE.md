# Clip Suggestions - Quotation-Based Timestamp Extraction

## Overview
Updated the AI clip suggestions feature to use text quotations instead of direct timestamps. The AI agent now receives clean text and returns exact quotations, which are then programmatically matched to the timestamped transcript data to extract precise start/end milliseconds.

## Changes Made

### 1. Modified Data Flow
**Before:**
- Sent `transcript_raw` (timestamped JSON) to n8n webhook
- AI agent tried to pick timestamps directly from complex structure
- Poor timestamp selection accuracy

**After:**
- Send `transcript_text` (clean text) to n8n webhook
- AI agent returns 4 exact quotations from the script
- Backend programmatically locates quotations in `transcript_raw` to extract timestamps
- More accurate and reliable timestamp extraction

### 2. Updated Files

#### `ai_clips.php`
- **Modified `generateClipSuggestions()` method:**
  - Now accepts both `$transcriptText` and `$transcriptRaw` parameters
  - Sends clean text to n8n webhook (not timestamped version)
  - Processes AI response to extract quotations
  - Locates each quotation in timestamped data to get milliseconds

- **Added `locateQuotationInTranscript()` method:**
  - Searches for text quotation in timestamped transcript data
  - Returns start and end timestamps in milliseconds
  - Handles complex event/segment structure from YouTube captions

- **Added `normalizeText()` method:**
  - Normalizes text for matching (lowercase, trim, remove punctuation)
  - Ensures flexible matching between quotation and transcript

- **Added `findQuotationMatch()` method:**
  - Implements fuzzy matching algorithm
  - 80% word match threshold for reliability
  - Handles variations in text formatting

- **Added `wordsMatch()` method:**
  - Compares individual words with similarity threshold
  - Supports exact matches, partial matches, and 85% similarity

#### `index.php`
- Updated `generate_clip_suggestions` route to:
  - Validate both `transcript_text` and `transcript_raw` exist
  - Pass both parameters to `generateClipSuggestions()`
  - Provide clear error messages if raw data is missing

### 3. Expected n8n Response Format

The n8n webhook should now return quotations instead of timestamps:

```json
[{
  "output": {
    "clip_suggestions": [
      {
        "quotation": "That's what I do. Lord, I do it all for you.",
        "suggested_title": "Powerful Declaration",
        "reason": "This segment features a powerful moment of dedication..."
      },
      {
        "quotation": "The rose blooms in the garden. The fragrance of victory still fills the air.",
        "suggested_title": "Victory Celebration",
        "reason": "Beautiful imagery and uplifting message..."
      }
    ]
  }
}]
```

### 4. Processed Output Format

The backend processes quotations and returns:

```json
{
  "success": true,
  "suggestions": [
    {
      "quotation": "That's what I do. Lord, I do it all for you.",
      "start_time_ms": 16080,
      "end_time_ms": 20640,
      "suggested_title": "Powerful Declaration",
      "reason": "This segment features a powerful moment of dedication..."
    }
  ]
}
```

## Database Schema

No changes to database schema required. Uses existing columns:
- `videos.transcript_text` - Clean text sent to AI
- `videos.transcript_raw` - Timestamped JSON for extraction
- `ai_clip_suggestions.clip_suggestions` - Stores processed suggestions with timestamps

## Error Handling

### Guardrails Implemented:

1. **Missing Quotation Field:**
   - Logs error and skips suggestion if quotation field is missing
   - Continues processing other suggestions

2. **Quotation Not Found:**
   - Logs warning with first 100 chars of quotation
   - Skips suggestion if match confidence is below threshold
   - Continues processing other suggestions

3. **No Matches Found:**
   - Throws exception if no quotations could be located
   - Prevents saving empty suggestion set

4. **Invalid Raw Data:**
   - Validates JSON parsing of transcript_raw
   - Throws clear error if format is invalid

5. **Fuzzy Matching:**
   - 80% word match threshold (configurable)
   - 85% word similarity for partial matches
   - Handles punctuation and case differences

## Matching Algorithm

### Text Normalization:
- Convert to lowercase
- Trim whitespace
- Remove punctuation (.,!?;:")
- Collapse multiple spaces

### Segment Processing:
- Extracts text from event/segment structure
- Calculates absolute timestamps (event start + segment offset)
- Skips newlines and music markers
- Preserves event end time for duration

### Quotation Matching:
1. Split quotation into words
2. Iterate through text segments
3. Match words sequentially with flexibility
4. Track start time at first match
5. Track end time at last match
6. Validate match confidence meets threshold

## Testing Recommendations

1. **Test with exact quotations:**
   - Verify precise timestamp extraction
   - Confirm start/end milliseconds are correct

2. **Test with slight variations:**
   - Quotations with different punctuation
   - Quotations with case differences
   - Verify fuzzy matching works

3. **Test edge cases:**
   - Very short quotations (< 5 words)
   - Very long quotations (> 50 words)
   - Quotations spanning multiple events
   - Quotations with [Music] or newlines

4. **Test error scenarios:**
   - Quotation not found in transcript
   - Malformed n8n response
   - Missing transcript_raw data

## n8n Webhook Updates Required

The n8n workflow needs to be updated to:

1. **Accept clean text input** (already implemented - sends text/plain)

2. **Return quotations instead of timestamps:**
   - AI should read the full transcript text
   - Select 4 compelling excerpts
   - Return the EXACT text as quotations
   - Include suggested_title and reason for each

3. **Example prompt for AI:**
   ```
   Analyze this video transcript and select 4 compelling excerpts that would make great social media clips.
   
   For each excerpt:
   - Copy the EXACT text from the transcript (word-for-word)
   - Suggest a catchy title
   - Explain why this would be engaging
   
   Return in JSON format with fields: quotation, suggested_title, reason
   ```

## Benefits

1. **Better AI Performance:**
   - AI works with clean, readable text
   - No need to parse complex timestamp structures
   - Can focus on content quality

2. **More Accurate Timestamps:**
   - Programmatic matching is deterministic
   - Handles edge cases consistently
   - Fuzzy matching handles variations

3. **Easier Debugging:**
   - Clear separation of concerns
   - AI returns human-readable quotations
   - Backend handles technical timestamp extraction

4. **Flexible Matching:**
   - Tolerates minor text differences
   - Handles punctuation variations
   - Works with partial matches

## Future Enhancements

1. **Improve matching algorithm:**
   - Use Levenshtein distance for better similarity
   - Implement phrase-based matching
   - Add context-aware matching

2. **Add confidence scores:**
   - Return match confidence to frontend
   - Allow user to see match quality
   - Provide manual timestamp override

3. **Support multiple matches:**
   - If quotation appears multiple times
   - Let user choose which instance
   - Show all matches with timestamps

4. **Optimize performance:**
   - Cache normalized text segments
   - Use more efficient search algorithms
   - Parallel processing for multiple quotations
