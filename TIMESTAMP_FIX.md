# Timestamp Calculation Fix

## Issue
The clip timestamps were way off because we were using the segment's calculated absolute start time (event start + segment offset) instead of using the event's `tStartMs` directly.

## Root Cause

### Before (Incorrect)
```php
$absoluteStartMs = $eventStartMs + $segmentOffsetMs;
$startMs = $textSegments[$j]['start_ms']; // Used segment offset
```

This would give timestamps like:
- Event starts at 16080ms
- First word has offset 0ms → absolute: 16080ms ✓
- Second word has offset 80ms → absolute: 16160ms ✗ (wrong for clip start)

### After (Correct)
```php
$startMs = $textSegments[$j]['event_start_ms']; // Use event's tStartMs
```

Now we always use the event's `tStartMs` for the clip start, which is the correct timestamp for the beginning of that caption event.

## YouTube Caption Structure

Each event in the transcript has:
```json
{
  "tStartMs": 16080,        // When this caption event starts
  "dDurationMs": 4440,      // How long this event lasts
  "segs": [
    {
      "utf8": "That's",
      "tOffsetMs": 0        // Offset within the event
    },
    {
      "utf8": " what",
      "tOffsetMs": 80       // 80ms after event start
    }
  ]
}
```

## Correct Timestamp Logic

### Start Time
- Use the **event's `tStartMs`** when we find the first matching word
- This is the timestamp when the caption event begins
- Example: If first match is in event starting at 16080ms, start = 16080ms

### End Time
- Use **event's `tStartMs + dDurationMs`** for the last matching event
- This is when the caption event ends
- Example: If last match is in event starting at 20640ms with duration 8880ms, end = 29520ms

## Changes Made

### 1. Store Both Timestamps
```php
$textSegments[] = [
    'text' => $text,
    'normalized' => $this->normalizeText($text),
    'segment_start_ms' => $absoluteStartMs,  // For reference
    'event_start_ms' => $eventStartMs,       // For clip start ✓
    'event_end_ms' => $eventStartMs + $eventDurationMs  // For clip end ✓
];
```

### 2. Use Event Start for Clip Start
```php
if ($startMs === null) {
    // Use the event's tStartMs for the clip start
    $startMs = $textSegments[$j]['event_start_ms'];
}
```

### 3. Use Event End for Clip End
```php
// Keep updating end time as we match more words
$endMs = $textSegments[$j]['event_end_ms'];
```

## Example

### Quotation
"This morning the rose blooms in the garden."

### Matching Events
```json
Event 1: tStartMs: 123000, dDurationMs: 3000
  "This morning the"

Event 2: tStartMs: 126000, dDurationMs: 2500
  "rose blooms in"

Event 3: tStartMs: 128500, dDurationMs: 2000
  "the garden."
```

### Result
```php
start_time_ms: 123000  // Event 1's tStartMs
end_time_ms: 130500    // Event 3's tStartMs + dDurationMs (128500 + 2000)
```

### YouTube URL
```
https://www.youtube.com/embed/VIDEO_ID?start=123&end=130
```

## Logging Added

Now logs show the exact timestamps:
```
Clip #0: Found best match with confidence 98% - Start: 123000ms, End: 130500ms, Duration: 7500ms
```

This helps verify:
- Start timestamp is correct
- End timestamp is correct
- Duration makes sense (typically 5-30 seconds for clips)

## Testing

### Verify Timestamps
1. Generate clip suggestions
2. Check server logs for timestamp output
3. Verify start/end times match the actual video content
4. Test YouTube embed with those timestamps

### Expected Behavior
- Clips should start at the beginning of the first matched caption
- Clips should end at the end of the last matched caption
- Duration should be reasonable (5-60 seconds typically)
- YouTube player should show the correct segment

## Summary

✅ **Fixed:** Now using `event_start_ms` (the event's `tStartMs`) for clip start time  
✅ **Fixed:** Now using `event_end_ms` (event's `tStartMs + dDurationMs`) for clip end time  
✅ **Added:** Logging shows exact timestamps and duration for verification  
✅ **Result:** Clip timestamps now accurately reflect the YouTube caption timing
