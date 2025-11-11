#!/bin/bash
# 🧪 Test Location Block Precedence - Comment out location / and test

set -e

echo "🧪 Testing location block precedence..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

MAIN_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
BACKUP="${MAIN_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"

# Backup
cp "$MAIN_CONFIG" "$BACKUP"
echo "Backup: $BACKUP"
echo ""

# Comment out location / block
echo -e "${BLUE}Commenting out location / block...${NC}"
sed -i '/^[[:space:]]*location \/ {/,/^[[:space:]]*}$/ {
    s/^/    # /
}' "$MAIN_CONFIG"

echo -e "${GREEN}✅ location / commented out${NC}"
echo ""

# Test config
if nginx -t 2>&1 | grep -q "successful"; then
    echo -e "${GREEN}✅ Config valid${NC}"
    systemctl reload nginx
    echo -e "${GREEN}✅ Nginx reloaded${NC}"
    echo ""
    
    echo -e "${BLUE}Testing robots.txt...${NC}"
    RESPONSE=$(curl -k -I https://earningsstable.com/robots.txt 2>&1 | head -10)
    echo "$RESPONSE"
    
    if echo "$RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
        echo -e "${GREEN}✅ robots.txt returns 200! Location blocks work!${NC}"
        echo ""
        echo -e "${YELLOW}⚠️  This confirms location / was intercepting requests${NC}"
        echo "Next: Uncomment location / and investigate why it takes precedence"
    else
        echo -e "${RED}❌ Still 404. Location blocks have different issue${NC}"
    fi
else
    echo -e "${RED}❌ Config invalid${NC}"
    cp "$BACKUP" "$MAIN_CONFIG"
    exit 1
fi

# Restore backup
echo ""
read -p "Restore location / block? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    cp "$BACKUP" "$MAIN_CONFIG"
    nginx -t && systemctl reload nginx
    echo -e "${GREEN}✅ Restored${NC}"
fi

