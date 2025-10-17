#!/bin/bash

# 🔄 Production Sync Script
# Syncs localhost:5555 changes to production server

set -e  # Exit on any error

echo "🔄 Starting production sync..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SERVER_USER="your-username"
SERVER_HOST="your-server.com"
PROJECT_DIR="/var/www/earnings-table"
SERVICE_NAME="earnings-table"

echo -e "${BLUE}📋 Sync Configuration:${NC}"
echo "  Server: $SERVER_USER@$SERVER_HOST"
echo "  Project Directory: $PROJECT_DIR"
echo "  Service Name: $SERVICE_NAME"
echo ""

# 1. Push local changes to GitHub
echo -e "${YELLOW}📤 Pushing local changes to GitHub...${NC}"
git add .
git commit -m "Sync: Update production with localhost:5555 changes" || echo "No changes to commit"
git push origin main
echo -e "${GREEN}✅ Local changes pushed to GitHub${NC}"

# 2. Connect to server and pull changes
echo -e "${YELLOW}📥 Pulling changes on server...${NC}"
ssh "$SERVER_USER@$SERVER_HOST" << EOF
cd $PROJECT_DIR
git pull origin main
echo "✅ Repository updated on server"
EOF

# 3. Restart service on server
echo -e "${YELLOW}🔄 Restarting service on server...${NC}"
ssh "$SERVER_USER@$SERVER_HOST" << EOF
cd $PROJECT_DIR
pm2 restart $SERVICE_NAME
echo "✅ Service restarted"
EOF

# 4. Test production
echo -e "${YELLOW}🧪 Testing production...${NC}"
sleep 5  # Wait for service to restart

# Test API
if curl -s "http://$SERVER_HOST:5555/api/health" > /dev/null; then
    echo -e "${GREEN}✅ Production API health check passed${NC}"
else
    echo -e "${RED}❌ Production API health check failed${NC}"
    exit 1
fi

# Test logos
if curl -s "http://$SERVER_HOST:5555/logos/ALLY.webp" > /dev/null; then
    echo -e "${GREEN}✅ Production logo serving works${NC}"
else
    echo -e "${RED}❌ Production logo serving failed${NC}"
    exit 1
fi

# Test favicon
if curl -s "http://$SERVER_HOST:5555/favicon.ico" > /dev/null; then
    echo -e "${GREEN}✅ Production favicon serving works${NC}"
else
    echo -e "${RED}❌ Production favicon serving failed${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}🎉 Production sync completed successfully!${NC}"
echo ""
echo -e "${BLUE}🌐 Production URLs:${NC}"
echo "  Main Dashboard: http://$SERVER_HOST:5555/"
echo "  API Health: http://$SERVER_HOST:5555/api/health"
echo "  Test Logos: http://$SERVER_HOST:5555/test-logos"
echo ""
echo -e "${GREEN}✅ Production is now identical to localhost:5555!${NC}"
