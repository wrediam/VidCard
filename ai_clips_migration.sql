-- AI Clip Suggestions Migration
-- Adds table for storing AI-generated social media clip suggestions

-- Create table for AI-generated clip suggestions
CREATE TABLE IF NOT EXISTS ai_clip_suggestions (
    id SERIAL PRIMARY KEY,
    video_id VARCHAR(20) NOT NULL,
    user_id INTEGER NOT NULL,
    clip_suggestions JSONB NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_clip_video_id FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE
);

-- Create indexes for faster queries
CREATE INDEX IF NOT EXISTS idx_ai_clips_video_id ON ai_clip_suggestions(video_id);
CREATE INDEX IF NOT EXISTS idx_ai_clips_user_id ON ai_clip_suggestions(user_id);
CREATE INDEX IF NOT EXISTS idx_ai_clips_generated_at ON ai_clip_suggestions(generated_at);

-- Add unique constraint to ensure one set of suggestions per video
CREATE UNIQUE INDEX IF NOT EXISTS idx_ai_clips_unique_video ON ai_clip_suggestions(video_id);

-- Add comments for documentation
COMMENT ON TABLE ai_clip_suggestions IS 'Stores AI-generated social media clip suggestions for videos';
COMMENT ON COLUMN ai_clip_suggestions.clip_suggestions IS 'Array of clip suggestion objects with start_time_ms, end_time_ms, suggested_title, and reason fields';
COMMENT ON COLUMN ai_clip_suggestions.generated_at IS 'Timestamp when suggestions were generated';
