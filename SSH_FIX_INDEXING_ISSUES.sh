#!/bin/bash
# 🔧 Fix indexing issues: www redirect, sitemap, robots.txt

set -e

echo "🔧 Fixing indexing issues..."

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
PROJECT_DIR="/var/www/earnings-table"

# 1. Check if files exist
echo -e "${YELLOW}📋 Checking files...${NC}"
if [ -f "$PROJECT_DIR/public/robots.txt" ]; then
    echo -e "${GREEN}✅ robots.txt exists${NC}"
else
    echo -e "${RED}❌ robots.txt NOT found${NC}"
    exit 1
fi

if [ -f "$PROJECT_DIR/public/sitemap.xml" ]; then
    echo -e "${GREEN}✅ sitemap.xml exists${NC}"
    # Check if it has lastmod
    if grep -q "lastmod" "$PROJECT_DIR/public/sitemap.xml"; then
        echo -e "${GREEN}✅ sitemap.xml has lastmod tag${NC}"
    else
        echo -e "${YELLOW}⚠️  sitemap.xml missing lastmod tag${NC}"
    fi
else
    echo -e "${RED}❌ sitemap.xml NOT found${NC}"
    exit 1
fi

# 2. Backup Nginx config
echo -e "${YELLOW}📦 Backing up Nginx config...${NC}"
cp "$NGINX_CONFIG" "${NGINX_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"

# 3. Check current Nginx config
echo -e "${YELLOW}🔍 Checking Nginx config...${NC}"
if grep -q "server_name.*www.earningstable.com" "$NGINX_CONFIG"; then
    echo -e "${GREEN}✅ www redirect found in config${NC}"
else
    echo -e "${YELLOW}⚠️  www redirect NOT found - will add${NC}"
fi

# 4. Test Express routes directly
echo -e "${YELLOW}🧪 Testing Express routes...${NC}"
curl -s http://localhost:5555/robots.txt > /tmp/robots_test.txt 2>&1
if [ -s /tmp/robots_test.txt ] && ! grep -q "404" /tmp/robots_test.txt; then
    echo -e "${GREEN}✅ Express robots.txt route works${NC}"
else
    echo -e "${RED}❌ Express robots.txt route returns 404${NC}"
    cat /tmp/robots_test.txt
fi

curl -s http://localhost:5555/sitemap.xml > /tmp/sitemap_test.txt 2>&1
if [ -s /tmp/sitemap_test.txt ] && ! grep -q "404" /tmp/sitemap_test.txt; then
    echo -e "${GREEN}✅ Express sitemap.xml route works${NC}"
    if grep -q "lastmod" /tmp/sitemap_test.txt; then
        echo -e "${GREEN}✅ Sitemap contains lastmod${NC}"
    fi
else
    echo -e "${RED}❌ Express sitemap.xml route returns 404${NC}"
    cat /tmp/sitemap_test.txt
fi

# 5. Check if Nginx needs www redirect block
if ! grep -q "server_name.*www.earningstable.com.*443" "$NGINX_CONFIG"; then
    echo -e "${YELLOW}🔧 Adding www redirect block to Nginx...${NC}"
    
    # Find the HTTPS server block and add www redirect before it
    # This is a simplified approach - you may need to adjust based on your config structure
    cat >> "$NGINX_CONFIG" << 'NGINX_EOF'

# Redirect www to non-www (HTTPS)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.earningstable.com;
    
    ssl_certificate /etc/letsencrypt/live/earningstable.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/earningstable.com/privkey.pem;
    
    return 301 https://earningsstable.com$request_uri;
}
NGINX_EOF
    
    echo -e "${GREEN}✅ Added www redirect block${NC}"
else
    echo -e "${GREEN}✅ www redirect block already exists${NC}"
fi

# 6. Test Nginx config
echo -e "${YELLOW}🧪 Testing Nginx config...${NC}"
if nginx -t; then
    echo -e "${GREEN}✅ Nginx config is valid${NC}"
    
    # Reload Nginx
    echo -e "${YELLOW}🔄 Reloading Nginx...${NC}"
    systemctl reload nginx || nginx -s reload
    echo -e "${GREEN}✅ Nginx reloaded${NC}"
else
    echo -e "${RED}❌ Nginx config has errors${NC}"
    exit 1
fi

# 7. Final tests
echo -e "${YELLOW}🧪 Running final tests...${NC}"
sleep 2

# Test www redirect
echo -e "${YELLOW}Testing www redirect...${NC}"
WWW_REDIRECT=$(curl -I -k -s https://www.earningstable.com/ | head -1)
if echo "$WWW_REDIRECT" | grep -q "301"; then
    echo -e "${GREEN}✅ www redirect works (301)${NC}"
else
    echo -e "${RED}❌ www redirect failed${NC}"
    echo "$WWW_REDIRECT"
fi

# Test sitemap
echo -e "${YELLOW}Testing sitemap...${NC}"
SITEMAP=$(curl -k -s https://earningsstable.com/sitemap.xml)
if echo "$SITEMAP" | grep -q "lastmod"; then
    echo -e "${GREEN}✅ sitemap.xml accessible and has lastmod${NC}"
else
    echo -e "${RED}❌ sitemap.xml issue${NC}"
    echo "$SITEMAP" | head -5
fi

# Test robots.txt
echo -e "${YELLOW}Testing robots.txt...${NC}"
ROBOTS=$(curl -k -s https://earningsstable.com/robots.txt)
if [ -n "$ROBOTS" ] && ! echo "$ROBOTS" | grep -q "404"; then
    echo -e "${GREEN}✅ robots.txt accessible${NC}"
else
    echo -e "${RED}❌ robots.txt issue${NC}"
    echo "$ROBOTS"
fi

echo -e "${GREEN}✅ Done!${NC}"
