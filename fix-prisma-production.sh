#!/bin/bash

# 🔧 CRITICAL FIX: Prisma Schema Output Path on Production
# Fixes the broken Prisma client generation that's causing cron failures

set -e  # Exit on any error

echo "🔧 Starting CRITICAL Prisma fix on production..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/var/www/earnings-table"

echo -e "${BLUE}📋 Fix Configuration:${NC}"
echo "  Project Directory: $PROJECT_DIR"
echo ""

# 0) Stop PM2 to prevent restart loops
echo -e "${YELLOW}🛑 Stopping PM2 to prevent restart loops...${NC}"
pm2 delete earnings-cron || true
echo -e "${GREEN}✅ PM2 stopped${NC}"

# 1) Oprav generator blok na SPRÁVNY output do shared
#   relatívne z modules/database/prisma -> ../../shared/node_modules/@prisma/client
echo -e "${YELLOW}🔧 Fixing schema output path...${NC}"
cd "$PROJECT_DIR"

perl -0777 -pe 's/generator client \{[^}]*\}/generator client {\n  provider = "prisma-client-js"\n  output   = "..\/..\/shared\/node_modules\/@prisma\/client"\n}/s' \
  -i modules/database/prisma/schema.prisma

# 2) Over obsah (musí končiť na .../@prisma/client)
echo -e "${BLUE}📋 Schema generator block after fix:${NC}"
sed -n '/generator client {/,/}/p' modules/database/prisma/schema.prisma
echo ""

# 3) Zbav sa nežiadaného auto-installu, ktorý Prisma vytvorila v ./modules
echo -e "${YELLOW}🧹 Cleaning up unwanted files...${NC}"
rm -rf modules/node_modules
rm -f modules/package.json
rm -f modules/package-lock.json
echo -e "${GREEN}✅ Cleanup completed${NC}"

# 4) Vyčisti staré generáty v OBOCH moduloch
echo -e "${YELLOW}🧹 Cleaning old generated clients...${NC}"
rm -rf modules/shared/node_modules/@prisma modules/shared/node_modules/.prisma
rm -rf modules/database/node_modules/@prisma modules/database/node_modules/.prisma
echo -e "${GREEN}✅ Old clients cleaned${NC}"

# 5) Nainštaluj klient/CLI len v SHARED (konzistentné verzie)
echo -e "${YELLOW}📦 Installing Prisma in shared module...${NC}"
cd modules/shared
npm i @prisma/client@6.18.0
npm i -D prisma@6.18.0
echo -e "${GREEN}✅ Prisma installed in shared${NC}"

# 6) Vygeneruj klienta (schema je v database, output smeruje do shared)
echo -e "${YELLOW}⚙️ Generating Prisma client...${NC}"
npx prisma generate --schema=../database/prisma/schema.prisma
echo -e "${GREEN}✅ Prisma client generated${NC}"

# 7) Kontrola – teraz MUSÍ byť veľký runtime v SHARED (už nie ~2.1K):
echo -e "${YELLOW}🔍 Verifying fix...${NC}"
echo -e "${BLUE}📊 Generated client file sizes:${NC}"
ls -lh node_modules/.prisma/client/default.js
ls -lh node_modules/@prisma/client/index.js

# Check if we have the full runtime (should be much larger than 2.1K)
DEFAULT_SIZE=$(stat -c%s node_modules/.prisma/client/default.js 2>/dev/null || echo "0")
if [ "$DEFAULT_SIZE" -gt 10000 ]; then
    echo -e "${GREEN}✅ Full Prisma runtime detected (${DEFAULT_SIZE} bytes)${NC}"
else
    echo -e "${RED}❌ Still mini runtime detected (${DEFAULT_SIZE} bytes)${NC}"
    exit 1
fi

# 8) Smoke test (správne uzátvorkované a escapované)
echo -e "${YELLOW}🧪 Testing Prisma client...${NC}"
cd "$PROJECT_DIR"

# Set DATABASE_URL for testing
export DATABASE_URL="file:$PROJECT_DIR/modules/database/prisma/prod.db"

# Test if Prisma client works
node --input-type=module -e 'import("@prisma/client").then(async ({PrismaClient})=>{const p=new PrismaClient(); await p.$executeRawUnsafe("SELECT 1"); await p.$disconnect(); console.log("Prisma OK");}).catch(e=>{console.error(e); process.exit(1);})' || {
    echo -e "${RED}❌ Prisma client test failed${NC}"
    exit 1
}

echo -e "${GREEN}✅ Prisma client test passed${NC}"

# 9) Odporúčané trvalé nastavenie - pridaj postinstall script
echo -e "${YELLOW}📝 Updating shared package.json...${NC}"
cd modules/shared

# Add postinstall script if not exists
if ! grep -q "postinstall" package.json; then
    # Add postinstall script
    sed -i '/"scripts": {/a\        "postinstall": "prisma generate",' package.json
    echo -e "${GREEN}✅ Added postinstall script${NC}"
else
    echo -e "${GREEN}✅ postinstall script already exists${NC}"
fi

# 10) Štart
echo -e "${YELLOW}🚀 Restarting PM2 with fixed Prisma...${NC}"
cd "$PROJECT_DIR"
pm2 start ecosystem.config.js --update-env
pm2 save

echo -e "${GREEN}✅ PM2 restarted${NC}"

# 11) Wait and check status
echo -e "${YELLOW}⏳ Waiting for services to start...${NC}"
sleep 10

echo -e "${BLUE}📊 PM2 Status:${NC}"
pm2 status

# 12) Check logs for errors
echo -e "${YELLOW}📋 Checking recent logs...${NC}"
pm2 logs earnings-cron --lines 100

echo ""
echo -e "${GREEN}🎉 CRITICAL Prisma fix completed!${NC}"
echo ""
echo -e "${BLUE}📊 What was fixed:${NC}"
echo "  ✅ Schema output path corrected"
echo "  ✅ Full Prisma runtime generated"
echo "  ✅ Prisma client test passed"
echo "  ✅ PM2 restarted with working Prisma"
echo ""
echo -e "${BLUE}🔍 Monitor with:${NC}"
echo "  pm2 logs earnings-cron --lines 50"
echo "  pm2 status"
echo ""
echo -e "${GREEN}✅ Cron jobs should now work without Prisma errors!${NC}"
