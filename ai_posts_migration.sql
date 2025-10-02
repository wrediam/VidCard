-- AI Post Suggestions Migration
-- Adds table for storing AI-generated social media post suggestions

-- Create table for AI-generated post suggestions
CREATE TABLE IF NOT EXISTS ai_post_suggestions (
    id SERIAL PRIMARY KEY,
    video_id VARCHAR(20) NOT NULL,
    user_id INTEGER NOT NULL,
    post_suggestions JSONB NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_video_id FOREIGN KEY (video_id) REFERENCES videos(video_id) ON DELETE CASCADE
);

-- Create indexes for faster queries
CREATE INDEX IF NOT EXISTS idx_ai_posts_video_id ON ai_post_suggestions(video_id);
CREATE INDEX IF NOT EXISTS idx_ai_posts_user_id ON ai_post_suggestions(user_id);
CREATE INDEX IF NOT EXISTS idx_ai_posts_generated_at ON ai_post_suggestions(generated_at);

-- Add unique constraint to ensure one set of suggestions per video
CREATE UNIQUE INDEX IF NOT EXISTS idx_ai_posts_unique_video ON ai_post_suggestions(video_id);

-- Add comments for documentation
COMMENT ON TABLE ai_post_suggestions IS 'Stores AI-generated social media post suggestions for videos';
COMMENT ON COLUMN ai_post_suggestions.post_suggestions IS 'Array of 5 post suggestion objects with post_text field';
COMMENT ON COLUMN ai_post_suggestions.generated_at IS 'Timestamp when suggestions were generated';
