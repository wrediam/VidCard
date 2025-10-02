-- API Keys and Rate Limiting Migration
-- Run this to add API functionality to VidCard

-- Create API keys table
CREATE TABLE IF NOT EXISTS api_keys (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    key_name VARCHAR(255) NOT NULL,
    api_key VARCHAR(128) UNIQUE NOT NULL,
    rate_limit_per_hour INTEGER DEFAULT 100,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_api_keys_user_id ON api_keys(user_id);
CREATE INDEX IF NOT EXISTS idx_api_keys_api_key ON api_keys(api_key);
CREATE INDEX IF NOT EXISTS idx_api_keys_active ON api_keys(is_active);

-- Create API requests log table
CREATE TABLE IF NOT EXISTS api_requests (
    id SERIAL PRIMARY KEY,
    api_key_id INTEGER REFERENCES api_keys(id) ON DELETE CASCADE,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45),
    status_code INTEGER,
    response_time DECIMAL(10, 2), -- in milliseconds
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_api_requests_api_key_id ON api_requests(api_key_id);
CREATE INDEX IF NOT EXISTS idx_api_requests_created_at ON api_requests(created_at);
CREATE INDEX IF NOT EXISTS idx_api_requests_endpoint ON api_requests(endpoint);

-- Create function to clean up old API request logs (keep last 30 days)
CREATE OR REPLACE FUNCTION cleanup_old_api_requests()
RETURNS void AS $$
BEGIN
    DELETE FROM api_requests WHERE created_at < NOW() - INTERVAL '30 days';
END;
$$ LANGUAGE plpgsql;

-- Add comment for documentation
COMMENT ON TABLE api_keys IS 'API keys for programmatic access to VidCard';
COMMENT ON TABLE api_requests IS 'Log of all API requests for rate limiting and analytics';
COMMENT ON COLUMN api_keys.rate_limit_per_hour IS 'Maximum number of requests allowed per hour';
COMMENT ON COLUMN api_requests.response_time IS 'Response time in milliseconds';
