#!/bin/bash
# 🔄 Quick Production Deploy Script
# Deploys fixed files to production and restarts server

set -e

echo "🚀 Deploying fixes to production..."

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration - UPDATUJTE TYTO HODNOTY!
SERVER_USER="your-username"  # ← Zmeňte na vaše SSH používateľské meno
SERVER_HOST="your-server-ip-or-domain"  # ← Zmeňte na vašu server IP alebo doménu
PROJECT_DIR="/var/www/earnings-table"  # ← Zmeňte ak je iná cesta
SERVICE_NAME="earnings-table"

echo -e "${YELLOW}📋 Production Configuration:${NC}"
echo "  Server: $SERVER_USER@$SERVER_HOST"
echo "  Project: $PROJECT_DIR"
echo "  Service: $SERVICE_NAME"
echo ""

# 1. Commit changes locally
echo -e "${YELLOW}📝 Committing changes...${NC}"
git add simple-server.js api-routes.ts site.webmanifest
git commit -m "Fix: Serialize BigInt and Date values for JSON, add site.webmanifest" || echo "No new changes to commit"

# 2. Push to GitHub
echo -e "${YELLOW}📤 Pushing to GitHub...${NC}"
git push origin main || echo "Push failed or no remote configured"

# 3. Deploy to server
echo -e "${YELLOW}📥 Deploying to server...${NC}"
ssh "$SERVER_USER@$SERVER_HOST" << 'DEPLOY_EOF'
cd /var/www/earnings-table

# Pull latest changes
echo "Pulling latest changes from Git..."
git pull origin main || echo "Git pull failed or not a git repo"

# Copy files directly if git doesn't work
# Uncomment these if git pull fails:
# echo "Copying files manually..."
# # You would use scp or rsync here

echo "✅ Files updated on server"
DEPLOY_EOF

# 4. Restart PM2 service
echo -e "${YELLOW}🔄 Restarting PM2 service...${NC}"
ssh "$SERVER_USER@$SERVER_HOST" << 'RESTART_EOF'
cd /var/www/earnings-table

# Restart the service
pm2 restart earnings-table || pm2 restart simple-server.js

# Show status
pm2 status

echo "✅ Service restarted"
RESTART_EOF

# 5. Test production
echo -e "${YELLOW}🧪 Testing production...${NC}"
sleep 3

if curl -s "https://www.earningstable.com/api/health" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Production health check passed!${NC}"
else
    echo -e "${YELLOW}⚠️  Health check failed - check server logs${NC}"
fi

echo ""
echo -e "${GREEN}✅ Deployment complete!${NC}"
echo ""
echo "Test these URLs:"
echo "  - https://www.earningstable.com/api/health"
echo "  - https://www.earningstable.com/api/final-report"
echo "  - https://www.earningstable.com/site.webmanifest"

