#!/bin/bash

# Clip Download Feature Setup Script
# This script sets up the clip download feature for VidCard

set -e  # Exit on error

echo "🎬 VidCard Clip Download Feature Setup"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env file exists
if [ ! -f .env ]; then
    echo -e "${RED}❌ Error: .env file not found${NC}"
    echo "Please create .env file from .env.example first:"
    echo "  cp .env.example .env"
    exit 1
fi

echo "✓ Found .env file"

# Check if API key is set
if ! grep -q "WREDIA_CLIP_API_KEY=" .env; then
    echo -e "${YELLOW}⚠️  Warning: WREDIA_CLIP_API_KEY not found in .env${NC}"
    echo "Adding WREDIA_CLIP_API_KEY to .env..."
    echo "" >> .env
    echo "# Wredia Clip Download API" >> .env
    echo "WREDIA_CLIP_API_KEY=WrediaAPI_2025_9f8e7d6c5b4a3210fedcba0987654321abcdef1234567890bcda1ef2a3b4c5d6e7f8g9h0i1j2k3l4m5n6o7p8q9r0s1t2u3v4w5x6y7z8" >> .env
    echo "WREDIA_CLIP_API_URL=https://vid.wredia.com/download/clip" >> .env
    echo -e "${GREEN}✓ Added API configuration to .env${NC}"
else
    echo "✓ WREDIA_CLIP_API_KEY already configured"
fi

# Check database connection
echo ""
echo "Checking database connection..."

DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_PORT=$(grep DB_PORT .env | cut -d '=' -f2)
DB_NAME=$(grep DB_NAME .env | cut -d '=' -f2)
DB_USER=$(grep DB_USER .env | cut -d '=' -f2)

if [ -z "$DB_HOST" ] || [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]; then
    echo -e "${RED}❌ Error: Database configuration incomplete in .env${NC}"
    exit 1
fi

echo "✓ Database configuration found"

# Run database migration
echo ""
echo "Running database migration..."

if command -v psql &> /dev/null; then
    # Check if migration file exists
    if [ ! -f clip_edits_migration.sql ]; then
        echo -e "${RED}❌ Error: clip_edits_migration.sql not found${NC}"
        exit 1
    fi
    
    # Run migration
    PGPASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2) psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f clip_edits_migration.sql
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Database migration completed successfully${NC}"
    else
        echo -e "${RED}❌ Error: Database migration failed${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠️  Warning: psql not found. Please run migration manually:${NC}"
    echo "  psql -U $DB_USER -d $DB_NAME -f clip_edits_migration.sql"
fi

# Verify table creation
echo ""
echo "Verifying database table..."

if command -v psql &> /dev/null; then
    TABLE_EXISTS=$(PGPASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2) psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -tAc "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'ai_clip_edits');")
    
    if [ "$TABLE_EXISTS" = "t" ]; then
        echo -e "${GREEN}✓ Table 'ai_clip_edits' exists${NC}"
    else
        echo -e "${RED}❌ Error: Table 'ai_clip_edits' not found${NC}"
        exit 1
    fi
fi

# Check required files
echo ""
echo "Checking required files..."

FILES=(
    "download_clip.php"
    "views/dashboard.php"
    "config.php"
    "index.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "✓ $file"
    else
        echo -e "${RED}❌ Missing: $file${NC}"
        exit 1
    fi
done

# Test API endpoint (optional)
echo ""
echo "Testing Wredia API endpoint..."

API_KEY=$(grep WREDIA_CLIP_API_KEY .env | cut -d '=' -f2)
API_URL=$(grep WREDIA_CLIP_API_URL .env | cut -d '=' -f2)

if command -v curl &> /dev/null; then
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$API_URL" \
        -H "Content-Type: application/json" \
        -H "X-API-Key: $API_KEY" \
        -d '{"url":"https://www.youtube.com/watch?v=test","start_time":0,"end_time":10}')
    
    if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "400" ]; then
        echo -e "${GREEN}✓ API endpoint is reachable${NC}"
    else
        echo -e "${YELLOW}⚠️  Warning: API returned HTTP $HTTP_CODE${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Warning: curl not found, skipping API test${NC}"
fi

# Final summary
echo ""
echo "======================================"
echo -e "${GREEN}✅ Setup Complete!${NC}"
echo ""
echo "Next steps:"
echo "1. Restart your web server/Docker containers"
echo "2. Log in to VidCard"
echo "3. Process a video and generate clip suggestions"
echo "4. Test the timeline and download functionality"
echo ""
echo "Documentation:"
echo "  - Integration Guide: CLIP_DOWNLOAD_INTEGRATION.md"
echo "  - Testing Guide: CLIP_DOWNLOAD_TESTING.md"
echo ""
echo "Troubleshooting:"
echo "  - Check browser console for JavaScript errors"
echo "  - Review server logs for API errors"
echo "  - Verify .env variables are loaded"
echo ""
echo "Happy clipping! 🎬✂️"
