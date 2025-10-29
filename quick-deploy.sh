#!/bin/bash
# 🚀 Quick Production Deploy Script
# Simple git pull + PM2 restart

set -e

PROJECT_DIR="/var/www/earnings-table"

echo "🚀 Quick Production Deploy"
echo "=========================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# 1. Navigate to project
cd "$PROJECT_DIR" || {
    echo -e "${RED}❌ Project directory not found: $PROJECT_DIR${NC}"
    exit 1
}

# 2. Git pull
echo -e "${YELLOW}📥 Pulling latest changes from GitHub...${NC}"
git pull origin main
echo -e "${GREEN}✅ Git pull completed${NC}"
echo ""

# 3. Show latest commit
echo -e "${YELLOW}📝 Latest commit:${NC}"
git log -1 --oneline
echo ""

# 4. Restart PM2 services
echo -e "${YELLOW}🔄 Restarting PM2 services...${NC}"
pm2 restart all
echo -e "${GREEN}✅ PM2 services restarted${NC}"
echo ""

# 5. Show status
echo -e "${YELLOW}📊 Service Status:${NC}"
pm2 status
echo ""

# 6. Quick health check
echo -e "${YELLOW}🏥 Health Check:${NC}"
sleep 2
if curl -s -f "http://localhost:5555/api/health" > /dev/null; then
    echo -e "${GREEN}✅ API is responding${NC}"
else
    echo -e "${RED}❌ API health check failed${NC}"
    echo "Check logs with: pm2 logs earnings-table"
fi

echo ""
echo -e "${GREEN}✅ Deploy completed!${NC}"
echo ""
echo "View logs: pm2 logs earnings-table --lines 50"

