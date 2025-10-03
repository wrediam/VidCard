# Clip Quotation Matching Improvements

## Overview
Enhanced the quotation matching algorithm with comprehensive logging and improved fuzzy matching to handle real-world transcript data and AI-generated quotations.

## Issues Fixed

### 1. Nested Data Structure
**Problem:** The `transcript_raw` data is wrapped in `{"data": "..."}` structure where the actual JSON is a string.

**Solution:**
```php
// Check if data is wrapped in {"data": "..."} structure
if (isset($rawData['data']) && is_string($rawData['data'])) {
    error_log("Unwrapping nested transcript_raw data structure");
    $rawData = json_decode($rawData['data'], true);
}
```

### 2. Field Name Variations
**Problem:** AI returns `verbatim_quote` instead of `quotation` field.

**Solution:**
```php
// Accept both 'quotation' and 'verbatim_quote'
if (isset($suggestion['quotation'])) {
    $quotation = $suggestion['quotation'];
} elseif (isset($suggestion['verbatim_quote'])) {
    $quotation = $suggestion['verbatim_quote'];
}
```

### 3. Strict Matching Threshold
**Problem:** 80% match threshold was too strict for real transcripts with variations.

**Solution:**
- Lowered threshold to 70% (from 80%)
- Implemented best-match algorithm instead of first-match
- Allow skipping up to 20% of words for flexibility

### 4. No Diagnostic Logging
**Problem:** When matches failed, no information about why.

**Solution:** Added comprehensive logging at every step.

## Logging Added

### Parsing Stage
```
✓ Unwrapping nested transcript_raw data structure
✓ Successfully parsed transcript_raw with 1234 events
```

### Processing Stage
```
✓ Processing clip #0: This morning the rose blooms in the garden. The fragrance of victory...
✓ Clip suggestion #1 using 'verbatim_quote' field instead of 'quotation'
✗ Clip suggestion #2 missing quotation/verbatim_quote field. Available fields: title, reason, text
```

### Matching Stage
```
✓ Clip #0: Looking for 45 words. First 5: this morning the rose blooms
✓ Clip #0: Built 2847 searchable segments from 3521 total segments
✓ Clip #0: Need to match at least 32 out of 45 words
✓ Clip #0: Found excellent match at segment 234 with confidence 98%
✓ Successfully located clip #0: 123456ms - 145678ms (confidence: 98%)
```

### Failure Diagnostics
```
✗ Clip #1: No match found meeting threshold. Best confidence was 62%
✗ Clip #1: No match found. First 10 segment texts: full | sun | [Music] | That's | what | I | do | Lord | I | do
✗ Could not locate quotation #1 in transcript. Quotation: Lend only what you're willing to lose without bitterness.
```

## Matching Algorithm Improvements

### Before
- First-match algorithm (stops at first match above threshold)
- 80% word match required
- No word skipping allowed
- Breaks on first mismatch after starting

### After
- Best-match algorithm (finds highest confidence match)
- 70% word match required
- Allows skipping 20% of words
- More flexible with mismatches
- Returns match with highest confidence

### Matching Logic
```php
// For each starting position in transcript
for ($i = 0; $i < count($textSegments); $i++) {
    // Try to match quotation words sequentially
    // Allow skipping small words (≤2 chars)
    // Allow skipping up to 20% of words
    // Track best match across all positions
    // Return highest confidence match
}
```

## Data Structure Compatibility

### Expected Database Format
```json
{
  "data": "{\"events\": [{\"tStartMs\": 80, \"segs\": [{\"utf8\": \"text\"}]}]}"
}
```

### Parsed Structure
```json
{
  "events": [
    {
      "tStartMs": 80,
      "dDurationMs": 10249,
      "segs": [
        {"utf8": "text", "tOffsetMs": 0}
      ]
    }
  ]
}
```

### Segment Processing
- Reads `events` array
- For each event, reads `segs` array
- Calculates absolute timestamp: `tStartMs + tOffsetMs`
- Skips newlines and `[Music]` markers
- Normalizes text (lowercase, remove punctuation)

## Error Messages

### Clear Error Messages
```
✗ Invalid transcript raw data format - missing events structure
✗ Could not locate any quotations in the transcript
✗ Clip suggestion #0 missing quotation/verbatim_quote field
```

### Diagnostic Information
- Shows which field names are available
- Shows first words of quotation being searched
- Shows sample of transcript segments
- Shows confidence scores for partial matches

## Testing Recommendations

### Test with Real Data
1. **Perfect Match:** Quotation exactly matches transcript
2. **Minor Variations:** Quotation has punctuation differences
3. **Word Skips:** Quotation missing small words like "the", "a"
4. **Long Quotations:** 50+ word quotations
5. **Short Quotations:** 5-10 word quotations
6. **Multiple Matches:** Same text appears multiple times

### Check Logs
```bash
# View clip matching logs
tail -f /var/log/apache2/error_log | grep "Clip #"

# Or in Docker
docker logs vidcard-php | grep "Clip #"
```

### Expected Log Flow
```
1. Unwrapping nested transcript_raw data structure
2. Successfully parsed transcript_raw with N events
3. Processing clip #0: [quotation preview]
4. Clip #0: Looking for N words. First 5: [words]
5. Clip #0: Built N searchable segments
6. Clip #0: Need to match at least N words
7. Clip #0: Found excellent match at segment N
8. Successfully located clip #0: [timestamps]
```

## Performance Considerations

### Optimizations
- Early exit on excellent matches (≥95% confidence)
- Skip processing newlines and music markers
- Filter empty strings from word arrays
- Use best-match to avoid reprocessing

### Complexity
- Time: O(n * m) where n = transcript segments, m = quotation words
- Space: O(n) for storing text segments
- Typical: ~3000 segments, ~50 words = ~150k comparisons per clip

## Future Enhancements

1. **Levenshtein Distance:** Better similarity scoring
2. **Phrase Matching:** Match multi-word phrases as units
3. **Context Windows:** Consider surrounding text
4. **Caching:** Cache normalized segments per video
5. **Parallel Processing:** Process multiple clips simultaneously
6. **Confidence Threshold:** Make threshold configurable
7. **Alternative Matches:** Return top 3 matches for user selection

## Summary

The matching algorithm now:
- ✅ Handles nested JSON structure from database
- ✅ Accepts both `quotation` and `verbatim_quote` fields
- ✅ Provides comprehensive diagnostic logging
- ✅ Uses flexible fuzzy matching (70% threshold)
- ✅ Finds best match instead of first match
- ✅ Allows word skipping for natural variations
- ✅ Reports confidence scores
- ✅ Gives clear error messages with context

This makes the system much more robust for real-world usage where transcripts and AI quotations may have minor variations.
