# VidCard Deployment Guide

## Coolify Deployment (Recommended)

### Prerequisites

- Coolify instance running
- Domain name (vidcard.io) pointed to your server
- SMTP2GO account configured
- YouTube API key

### Step-by-Step Deployment

#### 1. Prepare Your Repository

Ensure your repository is pushed to GitHub/GitLab:

```bash
git add .
git commit -m "Ready for deployment"
git push origin main
```

#### 2. Create Project in Coolify

1. Log into your Coolify dashboard
2. Click **"New Resource"** → **"Application"**
3. Select **"Docker Compose"** as deployment type
4. Connect your Git repository

#### 3. Configure Environment Variables

In Coolify, navigate to **Environment Variables** and add:

```env
# Database (Coolify will auto-generate these)
DB_HOST=postgres
DB_PORT=5432
DB_NAME=vidcard
DB_USER=vidcard
DB_PASSWORD=<generate-secure-password>

# SMTP2GO Configuration (API Key)
SMTP2GO_API_KEY=api-47E6C617D8B64C7C8C3220B2C32A0326
SMTP_FROM_EMAIL=noreply@vidcard.io
SMTP_FROM_NAME=VidCard

# YouTube API
YOUTUBE_API_KEY=<your-youtube-api-key>

# Application
APP_URL=https://vidcard.io
```

#### 4. Configure Domain

1. In Coolify, go to **Domains**
2. Add your domain: `vidcard.io`
3. Enable **HTTPS** (Let's Encrypt)
4. Coolify will automatically configure SSL

#### 5. Deploy

1. Click **"Deploy"**
2. Coolify will:
   - Build the Docker images
   - Create PostgreSQL database
   - Initialize schema (non-destructive)
   - Start the application
   - Configure reverse proxy with SSL

#### 6. Verify Deployment

1. Visit `https://vidcard.io`
2. Enter your email to test authentication
3. Check email for verification code
4. Process a test YouTube video

### Post-Deployment

#### Database Backup

Coolify automatically backs up PostgreSQL. To manually backup:

```bash
# SSH into your Coolify server
docker exec -t vidcard-postgres pg_dump -U vidcard vidcard > backup.sql
```

#### Monitoring

- **Logs**: View in Coolify dashboard under "Logs"
- **Metrics**: Monitor CPU/Memory usage in Coolify
- **Uptime**: Configure health checks in Coolify

#### Scaling

For high traffic:

1. Increase container resources in Coolify
2. Consider adding Redis for session storage
3. Use CDN for static assets

---

## Manual Docker Deployment

If not using Coolify:

### 1. Clone Repository

```bash
git clone https://github.com/yourusername/VidCard.git
cd VidCard
```

### 2. Configure Environment

```bash
cp .env.example .env
nano .env  # Edit with your credentials
```

### 3. Deploy with Docker Compose

```bash
docker-compose up -d
```

### 4. Configure Reverse Proxy (Nginx)

```nginx
server {
    listen 80;
    server_name vidcard.io;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name vidcard.io;

    ssl_certificate /etc/letsencrypt/live/vidcard.io/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/vidcard.io/privkey.pem;

    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### 5. SSL Certificate

```bash
certbot --nginx -d vidcard.io
```

---

## Troubleshooting

### Database Connection Issues

```bash
# Check PostgreSQL is running
docker ps | grep postgres

# View PostgreSQL logs
docker logs vidcard-postgres

# Test connection
docker exec -it vidcard-postgres psql -U vidcard -d vidcard
```

### Email Not Sending

1. Verify SMTP2GO API key is correct
2. Check SMTP2GO dashboard for API usage/errors
3. Ensure API key has sending permissions
4. Optional: Verify domain in SMTP2GO for better deliverability
5. View application logs:

```bash
docker logs vidcard-app
```

### Application Errors

```bash
# View PHP error logs
docker exec -it vidcard-app tail -f /var/log/apache2/error.log

# Restart application
docker-compose restart app
```

### Database Migration

If you need to reset the database:

```bash
# Backup first!
docker exec -t vidcard-postgres pg_dump -U vidcard vidcard > backup.sql

# Drop and recreate (DESTRUCTIVE)
docker exec -it vidcard-postgres psql -U vidcard -c "DROP DATABASE vidcard;"
docker exec -it vidcard-postgres psql -U vidcard -c "CREATE DATABASE vidcard;"

# Schema will auto-initialize on next request
```

---

## Performance Optimization

### Enable PHP OPcache

Add to Dockerfile:

```dockerfile
RUN docker-php-ext-install opcache
```

### PostgreSQL Tuning

Edit `docker-compose.yml`:

```yaml
postgres:
  command: postgres -c shared_buffers=256MB -c max_connections=200
```

### CDN Integration

For thumbnails and static assets, consider:
- Cloudflare CDN
- AWS CloudFront
- BunnyCDN

---

## Security Checklist

- [ ] HTTPS enabled with valid SSL certificate
- [ ] Environment variables secured (not in Git)
- [ ] Database password is strong and unique
- [ ] SMTP credentials are secure
- [ ] YouTube API key is restricted to your domain
- [ ] PostgreSQL is not exposed publicly
- [ ] Regular backups configured
- [ ] Firewall rules configured (only 80/443 open)

---

## Maintenance

### Update Application

```bash
git pull origin main
docker-compose down
docker-compose up -d --build
```

### Database Cleanup

Run periodically to clean expired sessions and auth codes:

```sql
-- Clean expired auth codes (older than 24 hours)
DELETE FROM auth_codes WHERE expires_at < NOW() - INTERVAL '24 hours';

-- Clean expired sessions
DELETE FROM sessions WHERE expires_at < NOW();
```

### Monitor Disk Space

```bash
# Check Docker disk usage
docker system df

# Clean up unused images
docker system prune -a
```

---

## Support

For deployment issues:
- Check Coolify documentation: https://coolify.io/docs
- Open an issue on GitHub
- Contact support@vidcard.io
