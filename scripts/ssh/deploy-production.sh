#!/bin/bash

# 🚀 Production Deployment Script
# Ensures identical UX/UI between localhost:5555 and production

set -e  # Exit on any error

echo "🚀 Starting production deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/var/www/earnings-table"
BACKUP_DIR="/var/backups/earnings-table"
SERVICE_NAME="earnings-table"
PORT=5555

echo -e "${BLUE}📋 Deployment Configuration:${NC}"
echo "  Project Directory: $PROJECT_DIR"
echo "  Backup Directory: $BACKUP_DIR"
echo "  Service Name: $SERVICE_NAME"
echo "  Port: $PORT"
echo ""

# 1. Create backup
echo -e "${YELLOW}🔄 Creating backup...${NC}"
if [ -d "$PROJECT_DIR" ]; then
    sudo mkdir -p "$BACKUP_DIR"
    sudo cp -r "$PROJECT_DIR" "$BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S)"
    echo -e "${GREEN}✅ Backup created${NC}"
else
    echo -e "${YELLOW}⚠️  No existing project to backup${NC}"
fi

# 2. Create project directory
echo -e "${YELLOW}📁 Creating project directory...${NC}"
sudo mkdir -p "$PROJECT_DIR"
sudo chown -R $USER:$USER "$PROJECT_DIR"
echo -e "${GREEN}✅ Project directory created${NC}"

# 3. Clone/Update repository
echo -e "${YELLOW}📥 Updating repository...${NC}"
if [ -d "$PROJECT_DIR/.git" ]; then
    cd "$PROJECT_DIR"
    git pull origin main
else
    git clone https://github.com/dusan02/EarningsTable.git "$PROJECT_DIR"
    cd "$PROJECT_DIR"
fi
echo -e "${GREEN}✅ Repository updated${NC}"

# 4. Install dependencies
echo -e "${YELLOW}📦 Installing dependencies...${NC}"

# Main project
npm install --production

# Database module
cd modules/database
npm install --production
npx prisma generate
cd ../..

# Cron module
cd modules/cron
npm install --production
cd ../..

# Shared module
cd modules/shared
npm install --production
cd ../..

# Web module
cd modules/web
npm install --production
cd ../..

echo -e "${GREEN}✅ Dependencies installed${NC}"

# 5. Setup environment
echo -e "${YELLOW}⚙️  Setting up environment...${NC}"
cat > .env << EOF
# Database
DATABASE_URL="file:$PROJECT_DIR/modules/database/prisma/dev.db"

# API Keys
FINNHUB_TOKEN="<FINNHUB_TOKEN_REDACTED>"
POLYGON_API_KEY="Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"

# Server
PORT=$PORT
NODE_ENV=production

# Cron
CRON_TZ=America/New_York
CRON_EXPR=0 7 * * *
EOF
echo -e "${GREEN}✅ Environment configured${NC}"

# 6. Setup database
echo -e "${YELLOW}🗄️  Setting up database...${NC}"
cd modules/database
npx prisma migrate deploy
cd ../..
echo -e "${GREEN}✅ Database setup complete${NC}"

# 7. Install PM2 if not exists
echo -e "${YELLOW}🔧 Installing PM2...${NC}"
if ! command -v pm2 &> /dev/null; then
    npm install -g pm2
    echo -e "${GREEN}✅ PM2 installed${NC}"
else
    echo -e "${GREEN}✅ PM2 already installed${NC}"
fi

# 8. Stop existing service
echo -e "${YELLOW}🛑 Stopping existing service...${NC}"
pm2 stop "$SERVICE_NAME" 2>/dev/null || echo "No existing service to stop"
pm2 delete "$SERVICE_NAME" 2>/dev/null || echo "No existing service to delete"

# 9. Start service with PM2
echo -e "${YELLOW}🚀 Starting service...${NC}"
pm2 start simple-server.js --name "$SERVICE_NAME" -- --port $PORT
pm2 save
pm2 startup

echo -e "${GREEN}✅ Service started with PM2${NC}"

# 10. Setup cron job
echo -e "${YELLOW}⏰ Setting up cron job...${NC}"
(crontab -l 2>/dev/null; echo "0 7 * * * cd $PROJECT_DIR/modules/cron && npm run run-all >> /var/log/earnings-cron.log 2>&1") | crontab -
echo -e "${GREEN}✅ Cron job configured${NC}"

# 11. Test deployment
echo -e "${YELLOW}🧪 Testing deployment...${NC}"
sleep 5  # Wait for service to start

# Test API
if curl -s "http://localhost:$PORT/api/health" > /dev/null; then
    echo -e "${GREEN}✅ API health check passed${NC}"
else
    echo -e "${RED}❌ API health check failed${NC}"
    exit 1
fi

# Test logos
if curl -s "http://localhost:$PORT/logos/ALLY.webp" > /dev/null; then
    echo -e "${GREEN}✅ Logo serving works${NC}"
else
    echo -e "${RED}❌ Logo serving failed${NC}"
    exit 1
fi

# Test favicon
if curl -s "http://localhost:$PORT/favicon.ico" > /dev/null; then
    echo -e "${GREEN}✅ Favicon serving works${NC}"
else
    echo -e "${RED}❌ Favicon serving failed${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}🎉 Deployment completed successfully!${NC}"
echo ""
echo -e "${BLUE}📊 Service Status:${NC}"
pm2 status "$SERVICE_NAME"
echo ""
echo -e "${BLUE}🌐 URLs:${NC}"
echo "  Main Dashboard: http://localhost:$PORT/"
echo "  API Health: http://localhost:$PORT/api/health"
echo "  Test Logos: http://localhost:$PORT/test-logos"
echo ""
echo -e "${BLUE}📝 Useful Commands:${NC}"
echo "  View logs: pm2 logs $SERVICE_NAME"
echo "  Restart: pm2 restart $SERVICE_NAME"
echo "  Stop: pm2 stop $SERVICE_NAME"
echo "  Status: pm2 status"
echo ""
echo -e "${GREEN}✅ Production deployment ready!${NC}"
