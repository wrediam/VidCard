# VidCard Quick Start Guide

## 🚀 Deploy to Coolify in 5 Minutes

### Step 1: Get Your API Keys (5 min)

#### YouTube API Key
1. Visit [Google Cloud Console](https://console.cloud.google.com/)
2. Create new project → Enable "YouTube Data API v3"
3. Create API Key → Copy it

#### SMTP2GO API Key
1. Sign up at [smtp2go.com](https://www.smtp2go.com/)
2. Go to Settings → API Keys → Create API Key
3. Copy the API key (format: `api-xxxxx`)

### Step 2: Deploy to Coolify (2 min)

1. **In Coolify Dashboard:**
   - Click "New Resource" → "Application"
   - Select "Docker Compose"
   - Connect this Git repository

2. **Add Environment Variables:**
   ```env
   DB_PASSWORD=<generate-strong-password>
   SMTP2GO_API_KEY=api-47E6C617D8B64C7C8C3220B2C32A0326
   SMTP_FROM_EMAIL=noreply@vidcard.io
   YOUTUBE_API_KEY=<your-youtube-api-key>
   APP_URL=https://vidcard.io
   ```

3. **Configure Domain:**
   - Add domain: `vidcard.io`
   - Enable HTTPS (automatic)

4. **Click Deploy!**

### Step 3: Test It Out (1 min)

1. Visit `https://vidcard.io`
2. Enter your email
3. Check email for 6-digit code
4. Paste a YouTube URL
5. Share your VidCard link!

---

## 💻 Local Development Setup

### Prerequisites
- PHP 8.2+
- PostgreSQL 15+
- Composer (optional)

### Quick Start

1. **Clone and Configure**
   ```bash
   git clone https://github.com/yourusername/VidCard.git
   cd VidCard
   cp .env.example .env
   ```

2. **Edit `.env` with your credentials**
   ```bash
   nano .env
   ```

3. **Start PostgreSQL**
   ```bash
   # macOS
   brew services start postgresql@15
   
   # Linux
   sudo systemctl start postgresql
   ```

4. **Create Database**
   ```bash
   createdb vidcard
   psql vidcard < init.sql
   ```

5. **Start PHP Server**
   ```bash
   php -S localhost:8000
   ```

6. **Visit** `http://localhost:8000`

---

## 🐳 Docker Development

```bash
# Start everything
docker-compose up -d

# View logs
docker-compose logs -f

# Stop
docker-compose down

# Rebuild after changes
docker-compose up -d --build
```

---

## 📝 First Steps After Deployment

### 1. Test Email Authentication
- Enter your email on homepage
- Verify you receive the code
- If not, check SMTP2GO dashboard

### 2. Process Your First Video
- Login to dashboard
- Paste any YouTube URL
- Click "Process Video"
- Copy the VidCard link

### 3. Test Social Media Preview
- Share the VidCard link on Twitter/Facebook
- Verify rich preview appears
- Check analytics in dashboard

### 4. Monitor Analytics
- Click on any video in dashboard
- View "Stats" to see visits
- Track referrers and engagement

---

## 🔧 Common Issues

### "Database connection failed"
- Check PostgreSQL is running
- Verify DB credentials in `.env`
- Ensure database exists

### "Failed to send email"
- Verify SMTP2GO API key is correct
- Check API key has sending permissions
- Optional: Verify your domain in SMTP2GO for better deliverability

### "Invalid YouTube URL"
- Ensure URL format: `https://youtube.com/watch?v=...`
- Check YouTube API key is valid
- Verify API quota not exceeded

### "Video not found" on redirect
- Video must be processed first
- Check database has video entry
- Verify video_id in URL is correct

---

## 🎯 Next Steps

1. **Customize Branding**
   - Edit `views/home.php` for homepage styling
   - Modify email template in `auth.php`
   - Update logo and colors

2. **Add Custom Domain**
   - Point DNS to your server
   - Configure in Coolify
   - SSL auto-configured

3. **Set Up Monitoring**
   - Enable Coolify health checks
   - Configure uptime monitoring
   - Set up error alerts

4. **Optimize Performance**
   - Enable PHP OPcache
   - Add Redis for sessions
   - Configure CDN for assets

---

## 📚 Documentation

- **Full README**: [README.md](README.md)
- **Deployment Guide**: [DEPLOYMENT.md](DEPLOYMENT.md)
- **API Documentation**: See `index.php` for endpoints

---

## 🆘 Get Help

- **Issues**: Open on GitHub
- **Discussions**: GitHub Discussions
- **Email**: support@vidcard.io

---

## ✅ Deployment Checklist

- [ ] YouTube API key configured
- [ ] SMTP2GO account set up
- [ ] Environment variables added to Coolify
- [ ] Domain configured with HTTPS
- [ ] Application deployed successfully
- [ ] Email authentication tested
- [ ] First video processed
- [ ] Social media preview verified
- [ ] Analytics tracking confirmed
- [ ] Backups configured

**You're ready to go! 🎉**
