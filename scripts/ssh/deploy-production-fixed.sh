#!/bin/bash

# 🚀 Production Deployment Script - EarningsTable
# This script deploys the application with all error prevention measures

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/var/www/earnings-table"
SERVICE_NAME="earnings-table"
CRON_NAME="earnings-cron"
PORT="5555"

echo -e "${BLUE}🚀 Starting Production Deployment...${NC}"

# 1. Pre-deployment checks
echo -e "${YELLOW}🔍 Running pre-deployment checks...${NC}"

# Check if project directory exists
if [ ! -d "$PROJECT_DIR" ]; then
    echo -e "${RED}❌ Project directory $PROJECT_DIR does not exist${NC}"
    exit 1
fi

# Check if .env file exists
if [ ! -f "$PROJECT_DIR/.env" ]; then
    echo -e "${RED}❌ .env file not found in $PROJECT_DIR${NC}"
    exit 1
fi

# Check if ecosystem.config.js exists
if [ ! -f "$PROJECT_DIR/ecosystem.config.js" ]; then
    echo -e "${RED}❌ ecosystem.config.js not found${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Pre-deployment checks passed${NC}"

# 2. Stop existing services
echo -e "${YELLOW}🛑 Stopping existing services...${NC}"
pm2 delete "$SERVICE_NAME" 2>/dev/null || echo "No existing $SERVICE_NAME service"
pm2 delete "$CRON_NAME" 2>/dev/null || echo "No existing $CRON_NAME service"
echo -e "${GREEN}✅ Existing services stopped${NC}"

# 3. Install dependencies
echo -e "${YELLOW}📦 Installing dependencies...${NC}"
cd "$PROJECT_DIR"
npm install --production --legacy-peer-deps

# Install module dependencies
cd "$PROJECT_DIR/modules/database"
npm install --production --legacy-peer-deps
npx prisma generate

cd "$PROJECT_DIR/modules/cron"
npm install --production --legacy-peer-deps

cd "$PROJECT_DIR/modules/shared"
npm install --production --legacy-peer-deps

cd "$PROJECT_DIR/modules/web"
npm install --production --legacy-peer-deps

cd "$PROJECT_DIR"
echo -e "${GREEN}✅ Dependencies installed${NC}"

# 4. Database setup
echo -e "${YELLOW}🗄️ Setting up database...${NC}"
cd "$PROJECT_DIR/modules/database"
npx prisma migrate deploy
cd "$PROJECT_DIR"
echo -e "${GREEN}✅ Database setup complete${NC}"

# 5. Environment validation
echo -e "${YELLOW}🔑 Validating environment variables...${NC}"
cd "$PROJECT_DIR/modules/cron"
npm run status > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Environment variables validated${NC}"
else
    echo -e "${RED}❌ Environment validation failed${NC}"
    exit 1
fi
cd "$PROJECT_DIR"

# 6. Start services with PM2
echo -e "${YELLOW}🚀 Starting services with PM2...${NC}"
pm2 start ecosystem.config.js --env production
pm2 save
pm2 startup
echo -e "${GREEN}✅ Services started with PM2${NC}"

# 7. Wait for services to start
echo -e "${YELLOW}⏳ Waiting for services to start...${NC}"
sleep 10

# 8. Health checks
echo -e "${YELLOW}🧪 Running health checks...${NC}"

# Check PM2 status
pm2 status

# Check API health
if curl -s "http://localhost:$PORT/api/health" > /dev/null; then
    echo -e "${GREEN}✅ API health check passed${NC}"
else
    echo -e "${RED}❌ API health check failed${NC}"
    echo -e "${YELLOW}📋 PM2 logs for $SERVICE_NAME:${NC}"
    pm2 logs "$SERVICE_NAME" --lines 20
    exit 1
fi

# Check cron status
cd "$PROJECT_DIR/modules/cron"
if npm run status > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Cron status check passed${NC}"
else
    echo -e "${RED}❌ Cron status check failed${NC}"
    echo -e "${YELLOW}📋 PM2 logs for $CRON_NAME:${NC}"
    pm2 logs "$CRON_NAME" --lines 20
    exit 1
fi
cd "$PROJECT_DIR"

# Check database connectivity
if sqlite3 "$PROJECT_DIR/modules/database/prisma/prod.db" "SELECT COUNT(*) FROM final_report;" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Database connectivity check passed${NC}"
else
    echo -e "${RED}❌ Database connectivity check failed${NC}"
    exit 1
fi

# Check logo serving
if curl -s "http://localhost:$PORT/logos/AAPL.webp" > /dev/null; then
    echo -e "${GREEN}✅ Logo serving check passed${NC}"
else
    echo -e "${YELLOW}⚠️ Logo serving check failed (may be normal if no logos yet)${NC}"
fi

# 9. Final status
echo -e "${GREEN}🎉 Deployment completed successfully!${NC}"
echo -e "${BLUE}📊 Final Status:${NC}"
pm2 status
echo -e "${BLUE}🌐 Application URL: https://www.earningstable.com${NC}"
echo -e "${BLUE}📋 API Health: http://localhost:$PORT/api/health${NC}"
echo -e "${BLUE}📊 Cron Status: cd $PROJECT_DIR/modules/cron && npm run status${NC}"

# 10. Optional: Run one-time cron job
read -p "Do you want to run a one-time cron job to populate data? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}🔄 Running one-time cron job...${NC}"
    cd "$PROJECT_DIR/modules/cron"
    npm run start:once
    cd "$PROJECT_DIR"
    echo -e "${GREEN}✅ One-time cron job completed${NC}"
fi

echo -e "${GREEN}🚀 Production deployment completed successfully!${NC}"
