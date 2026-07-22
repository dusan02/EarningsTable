#!/bin/bash
# 🔧 Fix robots.txt and sitemap.xml routes
# Checks file locations and fixes server routes

set -e

echo "🔧 Fixing robots.txt and sitemap.xml routes..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

cd /var/www/earnings-table

# 1. Check if files exist
echo -e "${BLUE}1. Checking file locations${NC}"
echo "------------------------"

if [ -f "public/robots.txt" ]; then
    echo -e "${GREEN}✅ public/robots.txt exists${NC}"
    cat public/robots.txt
else
    echo -e "${RED}❌ public/robots.txt NOT found${NC}"
fi

echo ""

if [ -f "public/sitemap.xml" ]; then
    echo -e "${GREEN}✅ public/sitemap.xml exists${NC}"
    head -5 public/sitemap.xml
else
    echo -e "${RED}❌ public/sitemap.xml NOT found${NC}"
fi

echo ""

# 2. Check server.ts routes
echo -e "${BLUE}2. Checking server.ts routes${NC}"
echo "---------------------------"
if [ -f "server.ts" ]; then
    echo "Routes in server.ts:"
    grep -A 3 "robots.txt\|sitemap.xml" server.ts || echo "  No robots.txt/sitemap.xml routes found"
    
    # Check path
    ROBOTS_PATH=$(grep "robots.txt" server.ts | grep -o "path.join([^)]*)" || echo "")
    if [ -n "$ROBOTS_PATH" ]; then
        echo "  robots.txt path: $ROBOTS_PATH"
    fi
else
    echo -e "${RED}❌ server.ts not found${NC}"
fi

echo ""

# 3. Check modules/web/src/web.ts routes
echo -e "${BLUE}3. Checking modules/web/src/web.ts routes${NC}"
echo "-----------------------------------"
if [ -f "modules/web/src/web.ts" ]; then
    echo "Routes in web.ts:"
    grep -A 3 "robots.txt\|sitemap.xml" modules/web/src/web.ts || echo "  No robots.txt/sitemap.xml routes found"
else
    echo -e "${RED}❌ modules/web/src/web.ts not found${NC}"
fi

echo ""

# 4. Test current routes
echo -e "${BLUE}4. Testing current routes${NC}"
echo "---------------------"
echo -n "  /robots.txt: "
ROBOTS_TEST=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:5555/robots.txt 2>/dev/null || echo "000")
if [ "$ROBOTS_TEST" = "200" ]; then
    echo -e "${GREEN}✅ HTTP ${ROBOTS_TEST}${NC}"
else
    echo -e "${RED}❌ HTTP ${ROBOTS_TEST}${NC}"
fi

echo -n "  /sitemap.xml: "
SITEMAP_TEST=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:5555/sitemap.xml 2>/dev/null || echo "000")
if [ "$SITEMAP_TEST" = "200" ]; then
    echo -e "${GREEN}✅ HTTP ${SITEMAP_TEST}${NC}"
else
    echo -e "${RED}❌ HTTP ${SITEMAP_TEST}${NC}"
fi

echo ""

# 5. Check which server is running
echo -e "${BLUE}5. Checking which server is running${NC}"
echo "--------------------------------"
PM2_LIST=$(pm2 list | grep "earnings-table" || echo "")
echo "$PM2_LIST"

# Check PM2 script path
PM2_SCRIPT=$(pm2 jlist | grep -o '"script":"[^"]*"' | head -1 | cut -d'"' -f4 || echo "")
if [ -n "$PM2_SCRIPT" ]; then
    echo "  PM2 script: $PM2_SCRIPT"
fi

echo ""

# 6. Summary
echo -e "${BLUE}6. Summary${NC}"
echo "=========="
echo ""
if [ "$ROBOTS_TEST" = "200" ] && [ "$SITEMAP_TEST" = "200" ]; then
    echo -e "${GREEN}✅ Routes are working${NC}"
    echo "  → Problem is in Nginx configuration"
else
    echo -e "${RED}❌ Routes are NOT working${NC}"
    echo "  → Need to fix server routes"
    echo ""
    echo "Likely issue:"
    echo "  - server.ts uses path.join(__dirname, 'public', 'robots.txt')"
    echo "  - But __dirname might be different in production"
    echo "  - Or files are in wrong location"
fi
echo ""

