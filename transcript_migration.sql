-- Transcript Feature Migration
-- Adds transcript storage to videos table

-- Add transcript columns to videos table
ALTER TABLE videos 
ADD COLUMN IF NOT EXISTS transcript_raw JSONB,
ADD COLUMN IF NOT EXISTS transcript_text TEXT,
ADD COLUMN IF NOT EXISTS transcript_fetched_at TIMESTAMP;

-- Create index for faster transcript queries
CREATE INDEX IF NOT EXISTS idx_videos_transcript_text ON videos USING gin(to_tsvector('english', transcript_text));
CREATE INDEX IF NOT EXISTS idx_videos_has_transcript ON videos((transcript_text IS NOT NULL));

-- Add comments for documentation
COMMENT ON COLUMN videos.transcript_raw IS 'Raw YouTube caption data in JSON format';
COMMENT ON COLUMN videos.transcript_text IS 'Extracted plain text transcript without timestamps';
COMMENT ON COLUMN videos.transcript_fetched_at IS 'Timestamp when transcript was last fetched';
