# VidCard 🎥

A multi-user web application that creates shareable YouTube video links with rich social media previews. Perfect for content creators, social media managers, and marketers who need professional-looking video cards with engagement tracking.

## Features ✨

- **Email Authentication**: Passwordless login via 6-digit codes sent to your email
- **Rich Social Media Previews**: Automatic Open Graph and Twitter Card meta tags
- **Video Processing**: Extract and store YouTube video metadata instantly
- **Analytics Tracking**: Monitor clicks, referrers, and engagement on shared videos
- **Channel Organization**: Videos grouped by YouTube channel with sidebar navigation
- **Search Functionality**: Find videos across your entire library
- **Multi-User Support**: Each user has their own video library and analytics
- **Modern UI**: Built with shadcn/ui design system and Tailwind CSS

## Tech Stack 🔧

- **Backend**: PHP 8.2 with PostgreSQL database
- **Frontend**: Tailwind CSS with shadcn/ui inspired design
- **Authentication**: Email-based with SMTP2GO
- **Database**: PostgreSQL with non-destructive schema initialization
- **Deployment**: Docker + Coolify ready
- **API**: YouTube Data API v3

## Quick Start 🚀

### Docker Deployment (Recommended for Coolify)

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/VidCard.git
   cd VidCard
   ```

2. **Configure environment variables**
   ```bash
   cp .env.example .env
   # Edit .env with your credentials
   ```

3. **Deploy with Docker Compose**
   ```bash
   docker-compose up -d
   ```

4. **Access the application**
   - Navigate to `https://vidcard.io` (or your configured domain)

### Coolify Deployment

1. Create a new project in Coolify
2. Connect your Git repository
3. Set environment variables in Coolify dashboard:
   - `DB_PASSWORD` - Secure PostgreSQL password
   - `SMTP2GO_API_KEY` - Your SMTP2GO API key (e.g., api-xxxxx)
   - `SMTP_FROM_EMAIL` - noreply@vidcard.io
   - `YOUTUBE_API_KEY` - Your YouTube API key
   - `APP_URL` - https://vidcard.io

4. Deploy!

## Environment Variables 📋

| Variable | Description | Required |
|----------|-------------|----------|
| `DB_HOST` | PostgreSQL host | Yes |
| `DB_PORT` | PostgreSQL port (default: 5432) | Yes |
| `DB_NAME` | Database name | Yes |
| `DB_USER` | Database user | Yes |
| `DB_PASSWORD` | Database password | Yes |
| `SMTP2GO_API_KEY` | SMTP2GO API key | Yes |
| `SMTP_FROM_EMAIL` | From email address | Yes |
| `SMTP_FROM_NAME` | From name (default: VidCard) | No |
| `YOUTUBE_API_KEY` | YouTube Data API v3 key | Yes |
| `APP_URL` | Application URL (https://vidcard.io) | Yes |

## Getting API Keys 🔑

### YouTube API Key
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable YouTube Data API v3
4. Create credentials (API Key)
5. Restrict the key to YouTube Data API v3

### SMTP2GO API Key
1. Sign up at [SMTP2GO](https://www.smtp2go.com/)
2. Go to Settings → API Keys
3. Create a new API key
4. Copy the key (format: `api-xxxxx`)
5. Optional: Verify your sending domain for better deliverability

## Usage 📝

### For Users

1. **Sign Up/Login**
   - Enter your email on the homepage
   - Receive a 6-digit code via email
   - Enter the code to access your dashboard

2. **Process Videos**
   - Paste any YouTube URL
   - Click "Process Video"
   - Get a shareable VidCard link

3. **Share Links**
   - Copy your VidCard link (e.g., `https://vidcard.io/?v=VIDEO_ID`)
   - Share on social media
   - Rich previews appear automatically

4. **Track Analytics**
   - View visit counts for each video
   - See referrer sources
   - Monitor engagement over time

### URL Structure

- **Homepage**: `https://vidcard.io/`
- **Dashboard**: `https://vidcard.io/dashboard`
- **Video Redirect**: `https://vidcard.io/?v=VIDEO_ID`

## Database Schema 🗄️

The application uses PostgreSQL with the following tables:

- `users` - User accounts
- `auth_codes` - Email verification codes
- `sessions` - User sessions
- `videos` - Processed YouTube videos
- `video_visits` - Analytics tracking

All tables use `CREATE TABLE IF NOT EXISTS` for non-destructive initialization.

## Security 🔒

- **Passwordless Authentication**: No password storage, email-based codes
- **Session Management**: Secure session tokens with expiration
- **HTTP-Only Cookies**: Session tokens stored in HTTP-only cookies
- **SQL Injection Protection**: PDO prepared statements
- **HTTPS Required**: Secure cookies require HTTPS in production

## Development 💻

### Local Setup

1. Install PHP 8.2+ and PostgreSQL
2. Copy `.env.example` to `.env`
3. Configure database connection
4. Run `php -S localhost:8000` for development server

### File Structure

```
VidCard/
├── index.php           # Main router and API endpoints
├── config.php          # Database and app configuration
├── auth.php            # Authentication class
├── video.php           # Video processing class
├── views/
│   ├── home.php        # Homepage with email signup
│   ├── dashboard.php   # User dashboard
│   └── redirect.php    # Video redirect with meta tags
├── init.sql            # PostgreSQL schema
├── Dockerfile          # Docker configuration
├── docker-compose.yml  # Docker Compose setup
└── .htaccess          # Apache URL rewriting
```

## Contributing 🤝

Contributions are welcome! Please feel free to submit issues and pull requests.

## License 📄

This project is licensed under the MIT License. See the LICENSE file for details.

## Support 💬

For issues and questions, please open an issue on GitHub.
