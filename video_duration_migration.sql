-- Video Duration Migration
-- Adds duration column to videos table

-- Add duration column (in seconds)
ALTER TABLE videos ADD COLUMN IF NOT EXISTS duration INTEGER;

-- Add index for duration queries
CREATE INDEX IF NOT EXISTS idx_videos_duration ON videos(duration);

-- Add comment for documentation
COMMENT ON COLUMN videos.duration IS 'Video duration in seconds from YouTube API';
