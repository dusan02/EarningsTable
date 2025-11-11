#!/bin/bash
# 🔍 Debug Active Nginx Config - See what Nginx actually uses

echo "🔍 Debugging active Nginx configuration..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== Active HTTPS server block (compiled config) ===${NC}"
ACTIVE_CONFIG=$(nginx -T 2>/dev/null | awk '/server_name.*earningsstable.com.*443/,/^[[:space:]]*}/ { if (/server_name.*earningsstable.*443/ || p) { p=1; print } if (/^[[:space:]]*}/ && p && !in_block) { in_block=1 } if (/^[[:space:]]*}/ && p && in_block) { print; exit } }' | head -80)

if echo "$ACTIVE_CONFIG" | grep -q "location = /robots.txt"; then
    echo -e "${GREEN}✅ Location blocks ARE in active config${NC}"
    echo ""
    echo "Location blocks:"
    echo "$ACTIVE_CONFIG" | grep -A 5 "location = /robots.txt"
    echo "$ACTIVE_CONFIG" | grep -A 5 "location = /sitemap.xml"
else
    echo -e "${RED}❌ Location blocks are NOT in active config!${NC}"
    echo ""
    echo "Active config shows:"
    echo "$ACTIVE_CONFIG" | head -40
fi

echo ""
echo -e "${BLUE}=== Testing file access with root directive ===${NC}"
ROOT_DIR=$(echo "$ACTIVE_CONFIG" | grep "root " | head -1 | awk '{print $2}' | tr -d ';' || echo "")

if [ -n "$ROOT_DIR" ]; then
    echo "Root directory: $ROOT_DIR"
    
    # Test if files exist relative to root
    if [ -f "${ROOT_DIR}/robots.txt" ]; then
        echo -e "${GREEN}✅ ${ROOT_DIR}/robots.txt exists${NC}"
        ls -la "${ROOT_DIR}/robots.txt"
    else
        echo -e "${RED}❌ ${ROOT_DIR}/robots.txt NOT found${NC}"
        echo "  Expected path: ${ROOT_DIR}/robots.txt"
        echo "  Actual path: /var/www/earnings-table/public/robots.txt"
    fi
    
    if [ -f "${ROOT_DIR}/sitemap.xml" ]; then
        echo -e "${GREEN}✅ ${ROOT_DIR}/sitemap.xml exists${NC}"
        ls -la "${ROOT_DIR}/sitemap.xml"
    else
        echo -e "${RED}❌ ${ROOT_DIR}/sitemap.xml NOT found${NC}"
    fi
else
    echo -e "${RED}❌ No root directive found in active config${NC}"
fi

echo ""
echo -e "${BLUE}=== Testing try_files logic ===${NC}"
echo "When Nginx processes: location = /robots.txt { try_files /robots.txt =404; }"
echo "It looks for: ${ROOT_DIR}/robots.txt"
echo ""

# Test direct file serving
echo -e "${BLUE}=== Testing direct file serving (bypass location) ===${NC}"
if [ -n "$ROOT_DIR" ] && [ -f "${ROOT_DIR}/robots.txt" ]; then
    echo "File content (first 3 lines):"
    head -3 "${ROOT_DIR}/robots.txt"
    echo ""
    echo -e "${GREEN}✅ File is readable${NC}"
else
    echo -e "${RED}❌ Cannot read file${NC}"
fi

echo ""
echo -e "${BLUE}=== Alternative: Use alias instead of root ===${NC}"
echo "Current config uses: root + try_files"
echo "Alternative: alias (absolute path)"
echo ""
echo "Would you like to try alias instead? (y/n)"
read -p "" -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    MAIN_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
    BACKUP="${MAIN_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"
    cp "$MAIN_CONFIG" "$BACKUP"
    
    # Replace root + try_files with alias
    sed -i '/location = \/robots.txt/,/^[[:space:]]*}/ {
        s|try_files /robots.txt =404;|alias /var/www/earnings-table/public/robots.txt;|
    }' "$MAIN_CONFIG"
    
    sed -i '/location = \/sitemap.xml/,/^[[:space:]]*}/ {
        s|try_files /sitemap.xml =404;|alias /var/www/earnings-table/public/sitemap.xml;|
    }' "$MAIN_CONFIG"
    
    # Remove root directive (not needed with alias)
    # Actually, keep root for other files, just use alias for these
    
    if nginx -t; then
        systemctl reload nginx
        echo -e "${GREEN}✅ Switched to alias, reloaded${NC}"
        echo "Test: curl -k -I https://earningsstable.com/robots.txt"
    else
        echo -e "${RED}❌ Config error, restoring backup${NC}"
        cp "$BACKUP" "$MAIN_CONFIG"
    fi
fi

