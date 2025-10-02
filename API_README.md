# VidCard API Implementation

## Overview

This implementation adds a complete RESTful API to VidCard with API key authentication and rate limiting functionality.

## Features

✅ **API Key Management**
- Generate unique API keys per user
- Configurable rate limits (10-10,000 requests/hour)
- Track usage statistics
- Revoke/delete keys

✅ **Rate Limiting**
- Per-key hourly limits
- Automatic request logging
- Rate limit headers in responses
- 429 responses when exceeded

✅ **RESTful Endpoints**
- List videos
- Get video details
- Process new videos
- Delete videos
- Get video statistics
- Search videos
- Group by channels

✅ **Security**
- API key authentication via headers
- User isolation (can only access own data)
- Secure key generation (64-char hex)
- HTTPS required in production

✅ **UI Dashboard**
- Modern, clean interface
- Create/manage API keys
- View usage statistics
- Integrated documentation
- Copy keys securely

## Files Added

### Backend
- **`api_key.php`** - API key management class
- **`api.php`** - API request handler and router
- **`api_migration.sql`** - Database schema for API tables

### Frontend
- **`views/api_keys.php`** - API key management UI

### Documentation
- **`API_DOCUMENTATION.md`** - Complete API reference
- **`API_README.md`** - This file

### Modified Files
- **`index.php`** - Added API routes and key management endpoints
- **`views/dashboard.php`** - Added API Keys link in header

## Database Schema

### `api_keys` Table
```sql
- id (SERIAL PRIMARY KEY)
- user_id (INTEGER, FK to users)
- key_name (VARCHAR(255))
- api_key (VARCHAR(128), UNIQUE)
- rate_limit_per_hour (INTEGER, DEFAULT 100)
- is_active (BOOLEAN, DEFAULT true)
- created_at (TIMESTAMP)
- last_used_at (TIMESTAMP)
```

### `api_requests` Table
```sql
- id (SERIAL PRIMARY KEY)
- api_key_id (INTEGER, FK to api_keys)
- endpoint (VARCHAR(255))
- method (VARCHAR(10))
- ip_address (VARCHAR(45))
- status_code (INTEGER)
- response_time (DECIMAL, in milliseconds)
- created_at (TIMESTAMP)
```

## Installation

The API tables are automatically created when you first access the application after deployment. The migration runs automatically via `index.php`.

### Manual Migration (if needed)

## Usage

### 1. **Create an API Key**

1. Log in to VidCard
2. Click the key icon in the dashboard header
3. Navigate to "API Keys"
4. Enter a descriptive name for your key
5. Click "Create API Key"
6. **Copy the key immediately** (you won't see it again!)

All API keys are automatically created with a rate limit of 100 requests per hour.

### 2. Make API Requests

Include your API key in the `X-API-Key` header:

```bash
curl -X GET https://vidcard.io/api/v1/videos \
  -H "X-API-Key: vk_your_api_key_here"
```

### 3. Monitor Usage

- View request counts in the API Keys dashboard
- Check rate limit headers in API responses
- Review detailed statistics per key

## API Endpoints

All endpoints are prefixed with `/api/v1`:

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/me` | Get API key info |
| GET | `/videos` | List all videos |
| GET | `/videos/{id}` | Get specific video |
| POST | `/videos` | Process new video |
| DELETE | `/videos/{id}` | Delete video |
| GET | `/videos/{id}/stats` | Get video statistics |
| GET | `/channels` | Get videos by channel |
| GET | `/search?q={query}` | Search videos |

See `API_DOCUMENTATION.md` for complete details.

## Rate Limiting

### How It Works

1. Each API key has a configurable hourly limit
2. Requests are logged in `api_requests` table
3. System counts requests in the last hour
4. Returns 429 if limit exceeded

### Rate Limit Headers

Every response includes:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1234567890
```

### Fixed Rate Limit

- **All API keys**: 100 requests/hour (hard-coded, non-configurable)

This limit cannot be changed or adjusted. All API keys are created with this fixed rate limit.

## Security Considerations

### API Key Format
- Prefix: `vk_` (VidCard)
- Length: 67 characters total
- Generation: `bin2hex(random_bytes(32))`

### Best Practices

1. **Never expose keys in client-side code**
2. **Use environment variables** for keys in applications
3. **Rotate keys regularly** for production use
4. **Delete unused keys** to minimize attack surface
5. **Monitor usage** for suspicious activity
6. **Use HTTPS** for all API requests

### CORS Configuration

Current settings in `api.php`:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
```

**For production**, restrict origins:
```php
header('Access-Control-Allow-Origin: https://yourdomain.com');
```

## Maintenance

### Cleanup Old Logs

API request logs are kept for 30 days. Run cleanup manually:

```sql
SELECT cleanup_old_api_requests();
```

Or set up a cron job:
```bash
0 0 * * * psql -U vidcard -d vidcard -c "SELECT cleanup_old_api_requests();"
```

### Monitor Performance

Check slow endpoints:
```sql
SELECT 
    endpoint,
    method,
    AVG(response_time) as avg_ms,
    COUNT(*) as requests
FROM api_requests
WHERE created_at > NOW() - INTERVAL '24 hours'
GROUP BY endpoint, method
ORDER BY avg_ms DESC;
```

### Top API Users

```sql
SELECT 
    u.email,
    ak.key_name,
    COUNT(ar.id) as total_requests,
    COUNT(CASE WHEN ar.created_at > NOW() - INTERVAL '1 hour' THEN 1 END) as last_hour
FROM api_keys ak
JOIN users u ON ak.user_id = u.id
LEFT JOIN api_requests ar ON ak.id = ar.api_key_id
GROUP BY u.email, ak.key_name
ORDER BY total_requests DESC
LIMIT 10;
```

## Troubleshooting

### "API key is required" Error

**Cause**: Missing `X-API-Key` header

**Solution**: Include header in all requests:
```bash
-H "X-API-Key: vk_your_key"
```

### "Invalid or inactive API key" Error

**Causes**:
1. Key was deleted
2. Key is inactive
3. User account is inactive
4. Typo in key

**Solution**: Verify key in API Keys dashboard

### "Rate Limit Exceeded" Error

**Cause**: Too many requests in the last hour

**Solutions**:
1. Wait for rate limit to reset (check `X-RateLimit-Reset` header)
2. Implement request caching to reduce API calls
3. Create additional API keys for different applications
4. Optimize your application to make fewer requests

### 404 on API Endpoints

**Cause**: URL rewriting not working

**Solution**: Verify `.htaccess` is enabled:
```apache
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

## Future Enhancements

Potential improvements:

- [ ] Webhook support for video events
- [ ] Batch video processing
- [ ] Export analytics as CSV/JSON
- [ ] Configurable rate limits per user tier
- [ ] API key scopes/permissions
- [ ] IP whitelisting per key
- [ ] OAuth2 authentication
- [ ] GraphQL endpoint
- [ ] WebSocket support for real-time updates
- [ ] SDK libraries (Python, JavaScript, PHP)
- [ ] Swagger/OpenAPI specification

## Testing

### Test API Key Creation

```bash
# Via dashboard or:
curl -X POST https://vidcard.io/ \
  -H "Content-Type: application/json" \
  -H "Cookie: session_token=YOUR_SESSION" \
  -d '{"action":"create_api_key","name":"Test Key"}'
```

Note: Rate limit is automatically set to 100 requests/hour and cannot be changed.

### Test API Endpoints

```bash
# Get videos
curl -X GET https://vidcard.io/api/v1/videos \
  -H "X-API-Key: vk_test_key"

# Process video
curl -X POST https://vidcard.io/api/v1/videos \
  -H "X-API-Key: vk_test_key" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://www.youtube.com/watch?v=dQw4w9WgXcQ"}'

# Search
curl -X GET "https://vidcard.io/api/v1/search?q=test" \
  -H "X-API-Key: vk_test_key"
```

### Test Rate Limiting

```bash
# Rapid requests to trigger rate limit
for i in {1..105}; do
  curl -X GET https://vidcard.io/api/v1/videos \
    -H "X-API-Key: vk_test_key"
done
```

## Support

For issues or questions:
- Check `API_DOCUMENTATION.md` for usage details
- Review error messages and status codes
- Check database logs for errors
- Verify API key is active and valid

## License

Same as VidCard project (MIT License)
