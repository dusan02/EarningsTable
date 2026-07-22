#!/bin/bash
# 🔍 Production Health Check Script
# Comprehensive verification of production server status

set -e

PROJECT_DIR="/var/www/earnings-table"
SITE_URL="https://www.earningstable.com"
API_URL="${SITE_URL}/api"

echo "🔍 Production Health Check"
echo "=========================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Check PM2 processes
echo "📊 PM2 Processes:"
echo "-----------------"
pm2 list
echo ""

# 2. Check PM2 logs (last 10 lines)
echo "📋 PM2 Logs (last 10 lines):"
echo "----------------------------"
pm2 logs earnings-table --lines 10 --nostream || echo "⚠️  Could not fetch logs"
echo ""

# 3. Check API Health endpoint
echo "🏥 API Health Check:"
echo "-------------------"
if curl -s -f "${API_URL}/health" > /dev/null; then
    echo -e "${GREEN}✅ Health endpoint responding${NC}"
    curl -s "${API_URL}/health" | head -5
else
    echo -e "${RED}❌ Health endpoint failed${NC}"
fi
echo ""

# 4. Check Final Report endpoint
echo "📊 Final Report API:"
echo "-------------------"
RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" "${API_URL}/final-report")
HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | grep -v "HTTP_CODE")

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✅ Final Report endpoint responding${NC}"
    COUNT=$(echo "$BODY" | grep -o '"count":[0-9]*' | cut -d: -f2 || echo "0")
    echo "  Records: $COUNT"
    echo "$BODY" | head -10
else
    echo -e "${RED}❌ Final Report endpoint failed (HTTP $HTTP_CODE)${NC}"
fi
echo ""

# 5. Check site.webmanifest
echo "📱 Web Manifest:"
echo "----------------"
if curl -s -f "${SITE_URL}/site.webmanifest" > /dev/null; then
    echo -e "${GREEN}✅ site.webmanifest accessible${NC}"
    curl -s "${SITE_URL}/site.webmanifest" | head -5
else
    echo -e "${RED}❌ site.webmanifest not found${NC}"
fi
echo ""

# 6. Check Prisma Client
echo "🔧 Prisma Client:"
echo "-----------------"
cd "$PROJECT_DIR"
if [ -d "modules/shared/node_modules/@prisma/client" ]; then
    echo -e "${GREEN}✅ Prisma client found in modules/shared${NC}"
else
    echo -e "${RED}❌ Prisma client not found${NC}"
fi

if [ -d "modules/shared/node_modules/.prisma/client" ]; then
    echo -e "${GREEN}✅ Prisma runtime found${NC}"
else
    echo -e "${RED}❌ Prisma runtime not found${NC}"
fi

if [ -d "node_modules/.prisma" ]; then
    echo -e "${YELLOW}⚠️  Root Prisma client still exists (should be removed)${NC}"
else
    echo -e "${GREEN}✅ Root Prisma client removed${NC}"
fi
echo ""

# 7. Check Database
echo "💾 Database:"
echo "------------"
DB_PATH="$PROJECT_DIR/modules/database/prisma/prod.db"
if [ -f "$DB_PATH" ]; then
    echo -e "${GREEN}✅ Database file exists${NC}"
    DB_SIZE=$(du -h "$DB_PATH" | cut -f1)
    echo "  Size: $DB_SIZE"
    
    # Check if we can query the database
    if sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM final_report;" 2>/dev/null | grep -q "^[0-9]"; then
        COUNT=$(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM final_report;" 2>/dev/null)
        echo -e "${GREEN}✅ Database accessible${NC}"
        echo "  Records in final_report: $COUNT"
    else
        echo -e "${RED}❌ Cannot query database${NC}"
    fi
else
    echo -e "${RED}❌ Database file not found${NC}"
fi
echo ""

# 8. Check Disk Space
echo "💽 Disk Space:"
echo "-------------"
df -h /var/www | tail -1 | awk '{print "  Used: " $3 " / " $2 " (" $5 ")"}'
echo ""

# 9. Check Memory Usage
echo "🧠 Memory Usage:"
echo "----------------"
pm2 monit --no-interaction 2>/dev/null | head -5 || free -h | head -2
echo ""

# 10. Check Git Status
echo "📦 Git Status:"
echo "-------------"
cd "$PROJECT_DIR"
git status --short || echo "⚠️  Could not check git status"
LATEST_COMMIT=$(git log -1 --oneline 2>/dev/null || echo "Unknown")
echo "  Latest commit: $LATEST_COMMIT"
echo ""

# 11. Check Port Binding
echo "🌐 Port Binding:"
echo "----------------"
if netstat -tuln 2>/dev/null | grep -q ":5555"; then
    echo -e "${GREEN}✅ Port 5555 is listening${NC}"
else
    echo -e "${YELLOW}⚠️  Port 5555 not found (may be using different port)${NC}"
fi
echo ""

# Summary
echo "=========================="
echo "✅ Health Check Complete"
echo "=========================="

