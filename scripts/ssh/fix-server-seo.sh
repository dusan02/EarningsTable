#!/bin/bash
# 🔧 Fix Server SEO Issues - Step by Step
# Resolves git conflicts, updates scripts, and tests SEO

set -e

echo "🔧 Fixing Server SEO Issues..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    SUDO=""
else
    SUDO="sudo"
fi

cd /var/www/earnings-table

# Step 1: Fix git conflict
echo -e "${BLUE}Step 1: Fixing git conflict...${NC}"
if [ -f "post-deployment-seo-check.sh" ]; then
    # Backup local changes
    cp post-deployment-seo-check.sh post-deployment-seo-check.sh.local-backup
    echo -e "${YELLOW}  Backed up local changes${NC}"
    
    # Stash or discard local changes
    git checkout -- post-deployment-seo-check.sh 2>/dev/null || true
    echo -e "${GREEN}  ✅ Reset local changes${NC}"
fi

# Pull latest changes
echo -e "${YELLOW}  Pulling latest changes...${NC}"
git pull origin feat/skeleton-loading-etag
echo -e "${GREEN}  ✅ Git pull successful${NC}"
echo ""

# Step 2: Make scripts executable
echo -e "${BLUE}Step 2: Making scripts executable...${NC}"
chmod +x post-deployment-seo-check.sh update-nginx-seo.sh 2>/dev/null || true
echo -e "${GREEN}  ✅ Scripts are executable${NC}"
echo ""

# Step 3: Test HTTPS with insecure flag (for self-signed certs)
echo -e "${BLUE}Step 3: Testing HTTPS endpoints (ignoring SSL cert)...${NC}"
echo ""

echo -n "  Homepage: "
HOME_STATUS=$(curl -k -s -o /dev/null -w "%{http_code}" https://earningsstable.com/ 2>/dev/null || echo "000")
if [ "$HOME_STATUS" = "200" ]; then
    echo -e "${GREEN}✅ HTTP ${HOME_STATUS}${NC}"
else
    echo -e "${RED}❌ HTTP ${HOME_STATUS}${NC}"
fi

echo -n "  robots.txt: "
ROBOTS_STATUS=$(curl -k -s -o /dev/null -w "%{http_code}" https://earningsstable.com/robots.txt 2>/dev/null || echo "000")
if [ "$ROBOTS_STATUS" = "200" ]; then
    echo -e "${GREEN}✅ HTTP ${ROBOTS_STATUS}${NC}"
else
    echo -e "${RED}❌ HTTP ${ROBOTS_STATUS}${NC}"
fi

echo -n "  sitemap.xml: "
SITEMAP_STATUS=$(curl -k -s -o /dev/null -w "%{http_code}" https://earningsstable.com/sitemap.xml 2>/dev/null || echo "000")
if [ "$SITEMAP_STATUS" = "200" ]; then
    echo -e "${GREEN}✅ HTTP ${SITEMAP_STATUS}${NC}"
else
    echo -e "${RED}❌ HTTP ${SITEMAP_STATUS}${NC}"
fi

echo -n "  X-Robots-Tag: "
ROBOTS_TAG=$(curl -k -I -s https://earningsstable.com/ 2>/dev/null | grep -i "x-robots-tag" | tr -d '\r\n' || echo "")
if echo "$ROBOTS_TAG" | grep -qi "index, follow"; then
    echo -e "${GREEN}✅ ${ROBOTS_TAG}${NC}"
else
    echo -e "${RED}❌ Missing or incorrect: ${ROBOTS_TAG}${NC}"
fi

echo -n "  API /api/final-report: "
API_STATUS=$(curl -k -s -o /dev/null -w "%{http_code}" https://earningsstable.com/api/final-report 2>/dev/null || echo "000")
if [ "$API_STATUS" = "200" ]; then
    echo -e "${GREEN}✅ HTTP ${API_STATUS}${NC}"
else
    echo -e "${RED}❌ HTTP ${API_STATUS}${NC}"
fi

echo ""

# Step 4: Check homepage content
echo -e "${BLUE}Step 4: Checking homepage content...${NC}"
HOMEPAGE=$(curl -k -s https://earningsstable.com/ 2>/dev/null || echo "")

if echo "$HOMEPAGE" | grep -qi "earningsstable.com"; then
    echo -e "${GREEN}  ✅ Contains earningsstable.com${NC}"
else
    echo -e "${RED}  ❌ Does not contain earningsstable.com${NC}"
fi

if echo "$HOMEPAGE" | grep -qi "earnings-table.com"; then
    echo -e "${RED}  ❌ Still contains earnings-table.com (BAD)${NC}"
else
    echo -e "${GREEN}  ✅ Does not contain earnings-table.com (GOOD)${NC}"
fi

CANONICAL=$(echo "$HOMEPAGE" | grep -i 'rel="canonical"' | grep -o 'href="[^"]*"' | cut -d'"' -f2 || echo "")
if [ "$CANONICAL" = "https://earningsstable.com/" ]; then
    echo -e "${GREEN}  ✅ Canonical correct: ${CANONICAL}${NC}"
else
    echo -e "${RED}  ❌ Canonical incorrect: ${CANONICAL}${NC}"
fi
echo ""

# Step 5: Check PM2 status
echo -e "${BLUE}Step 5: Checking PM2 services...${NC}"
pm2 list
echo ""

# Step 6: Summary and recommendations
echo -e "${BLUE}Step 6: Summary${NC}"
echo "=========================================="
echo ""
echo "Issues found:"
echo "  1. HTTP returns 404 - Need HTTP→HTTPS redirect in Nginx"
echo "  2. Self-signed SSL certificate - Consider Let's Encrypt"
echo ""
echo "Next steps:"
echo "  1. Update Nginx config (run: ./update-nginx-seo.sh as root)"
echo "  2. Or manually add HTTP→HTTPS redirect in Nginx"
echo "  3. Get proper SSL cert: certbot --nginx -d earningsstable.com"
echo "  4. Run full SEO check: ./post-deployment-seo-check.sh https://earningsstable.com"
echo ""

