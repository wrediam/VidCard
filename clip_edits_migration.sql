-- Clip Edits Migration
-- Adds table for storing user-edited clip times (when they adjust the AI suggestions)

-- Create table for user-edited clip times
CREATE TABLE IF NOT EXISTS ai_clip_edits (
    id SERIAL PRIMARY KEY,
    video_id VARCHAR(20) NOT NULL,
    user_id INTEGER NOT NULL,
    clip_index INTEGER NOT NULL,
    original_start_time INTEGER NOT NULL,
    original_end_time INTEGER NOT NULL,
    edited_start_time INTEGER NOT NULL,
    edited_end_time INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_clip_edit_video_id FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE
);

-- Create indexes for faster queries
CREATE INDEX IF NOT EXISTS idx_clip_edits_video_id ON ai_clip_edits(video_id);
CREATE INDEX IF NOT EXISTS idx_clip_edits_user_id ON ai_clip_edits(user_id);
CREATE INDEX IF NOT EXISTS idx_clip_edits_created_at ON ai_clip_edits(created_at);

-- Add unique constraint to ensure one edit per clip per user
CREATE UNIQUE INDEX IF NOT EXISTS idx_clip_edits_unique ON ai_clip_edits(video_id, user_id, clip_index);

-- Add trigger to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_clip_edit_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_clip_edit_timestamp
    BEFORE UPDATE ON ai_clip_edits
    FOR EACH ROW
    EXECUTE FUNCTION update_clip_edit_timestamp();

-- Add comments for documentation
COMMENT ON TABLE ai_clip_edits IS 'Stores user-edited clip times when they adjust AI suggestions';
COMMENT ON COLUMN ai_clip_edits.clip_index IS 'Index of the clip in the suggestions array (0-based)';
COMMENT ON COLUMN ai_clip_edits.original_start_time IS 'Original AI-suggested start time in seconds';
COMMENT ON COLUMN ai_clip_edits.original_end_time IS 'Original AI-suggested end time in seconds';
COMMENT ON COLUMN ai_clip_edits.edited_start_time IS 'User-edited start time in seconds';
COMMENT ON COLUMN ai_clip_edits.edited_end_time IS 'User-edited end time in seconds';
