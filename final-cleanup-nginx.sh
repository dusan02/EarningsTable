#!/bin/bash
# 🔧 Final Cleanup - Remove backup files and verify everything works

set -e

echo "🔧 Final Nginx Cleanup..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Step 1: Remove backup files from sites-enabled
echo -e "${BLUE}Step 1: Removing backup files from sites-enabled...${NC}"
BACKUP_FILES=$(find /etc/nginx/sites-enabled -name "*.backup.*" -type f 2>/dev/null || true)

if [ -n "$BACKUP_FILES" ]; then
    echo "Found backup files:"
    echo "$BACKUP_FILES"
    rm -f /etc/nginx/sites-enabled/*.backup.*
    echo -e "${GREEN}✅ Backup files removed${NC}"
else
    echo -e "${GREEN}✅ No backup files found${NC}"
fi

# Step 2: Validate and restart
echo ""
echo -e "${BLUE}Step 2: Validating configuration...${NC}"

if nginx -t 2>&1 | grep -q "successful"; then
    echo -e "${GREEN}✅ Config valid${NC}"
    
    # Check for conflicts
    CONFLICTS=$(nginx -t 2>&1 | grep -c "conflicting server name" 2>/dev/null || echo "0")
    CONFLICTS=${CONFLICTS//[^0-9]/}  # Remove any non-numeric characters
    if [ -z "$CONFLICTS" ] || [ "$CONFLICTS" -eq 0 ]; then
        echo -e "${GREEN}✅ No conflicting server names!${NC}"
    else
        echo -e "${YELLOW}⚠️  Still have $CONFLICTS conflicts${NC}"
        nginx -t 2>&1 | grep "conflicting" | head -3
    fi
    
    echo ""
    echo "Restarting Nginx..."
    systemctl restart nginx
    sleep 2
    echo -e "${GREEN}✅ Nginx restarted${NC}"
else
    echo -e "${RED}❌ Config invalid${NC}"
    nginx -t
    exit 1
fi

# Step 3: Check what's listening on ports
echo ""
echo -e "${BLUE}Step 3: Checking what's listening on ports 80/443...${NC}"
ss -tulpn | grep -E ':(80|443)\s' || echo "Nothing found"

# Step 4: Test public endpoints (without --resolve)
echo ""
echo -e "${BLUE}Step 4: Testing public endpoints (without --resolve)...${NC}"
echo ""

echo "Testing HTTP redirect:"
HTTP_REDIRECT=$(curl -I http://earningsstable.com/ 2>&1 | head -10)
echo "$HTTP_REDIRECT"

if echo "$HTTP_REDIRECT" | grep -q "301\|Location: https://earningsstable.com"; then
    echo -e "${GREEN}✅ HTTP redirects to HTTPS${NC}"
else
    echo -e "${RED}❌ HTTP redirect not working${NC}"
fi

echo ""
echo "Testing HTTPS robots.txt:"
ROBOTS_RESPONSE=$(curl -k -I https://earningsstable.com/robots.txt 2>&1 | head -15)
echo "$ROBOTS_RESPONSE"

if echo "$ROBOTS_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ robots.txt returns 200!${NC}"
    if echo "$ROBOTS_RESPONSE" | grep -q "X-Debug-Block: A"; then
        echo -e "${GREEN}✅ From correct Nginx block${NC}"
    fi
    if echo "$ROBOTS_RESPONSE" | grep -q "x-content-type-options: nosniff"; then
        echo -e "${YELLOW}⚠️  Still showing Express headers (might be proxied)${NC}"
    else
        echo -e "${GREEN}✅ Nginx headers (not Express)${NC}"
    fi
else
    echo -e "${RED}❌ robots.txt still returns error${NC}"
fi

echo ""
echo "Testing HTTPS sitemap.xml:"
SITEMAP_RESPONSE=$(curl -k -I https://earningsstable.com/sitemap.xml 2>&1 | head -15)
echo "$SITEMAP_RESPONSE"

if echo "$SITEMAP_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ sitemap.xml returns 200!${NC}"
else
    echo -e "${RED}❌ sitemap.xml still returns error${NC}"
fi

echo ""
echo "Testing HTTPS homepage:"
HOME_RESPONSE=$(curl -k -I https://earningsstable.com/ 2>&1 | head -15)
echo "$HOME_RESPONSE"

if echo "$HOME_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ Homepage returns 200!${NC}"
else
    echo -e "${RED}❌ Homepage returns error${NC}"
fi

# Step 5: Summary
echo ""
echo -e "${BLUE}Step 5: Final verification...${NC}"
echo ""
echo "nginx -t output:"
nginx -t 2>&1 | tail -3

echo ""
echo -e "${GREEN}🎉 Cleanup complete!${NC}"
echo ""
echo "Next steps:"
echo "  1. Run: certbot --nginx -d earningsstable.com -d www.earningsstable.com"
echo "  2. Test without -k flag: curl -I https://earningsstable.com/robots.txt"
echo "  3. Add to Google Search Console and submit sitemap"
echo ""

