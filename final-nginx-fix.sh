#!/bin/bash
# 🔧 Final Nginx Fix - Disable default config, verify location blocks work

set -e

echo "🔧 Final Nginx fix..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 1. Disable default config if it has earningsstable.com
DEFAULT_CONFIG="/etc/nginx/sites-available/default"
if [ -f "$DEFAULT_CONFIG" ] && grep -q "earningsstable\|earningstable" "$DEFAULT_CONFIG"; then
    echo -e "${BLUE}Disabling earningsstable.com in default config...${NC}"
    
    # Check if already disabled
    if grep -q "^[[:space:]]*#.*server_name.*earningsstable" "$DEFAULT_CONFIG"; then
        echo -e "${GREEN}✅ Already commented out${NC}"
    else
        # Comment out server blocks with earningsstable
        sed -i '/server {/,/^}/ {
            /server_name.*earningsstable/ {
                :a
                N
                /^}/!ba
                s/^/#/
            }
        }' "$DEFAULT_CONFIG" 2>/dev/null || {
            # Simpler approach - comment out lines with earningsstable
            sed -i 's/^\([[:space:]]*server_name.*earningsstable\)/#\1/' "$DEFAULT_CONFIG"
            sed -i 's/^\([[:space:]]*server_name.*earningstable\)/#\1/' "$DEFAULT_CONFIG"
        }
        echo -e "${GREEN}✅ Commented out earningsstable.com in default config${NC}"
    fi
fi

# 2. Check if default is enabled
if [ -L "/etc/nginx/sites-enabled/default" ]; then
    echo -e "${YELLOW}⚠️  default config is enabled${NC}"
    read -p "Disable default config? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        unlink /etc/nginx/sites-enabled/default
        echo -e "${GREEN}✅ Disabled default config${NC}"
    fi
fi

# 3. Verify main config has location blocks
MAIN_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
if [ -f "$MAIN_CONFIG" ]; then
    echo ""
    echo -e "${BLUE}Checking main config for location blocks...${NC}"
    
    if grep -q "location = /robots.txt" "$MAIN_CONFIG"; then
        echo -e "${GREEN}✅ robots.txt location found${NC}"
        grep -A 5 "location = /robots.txt" "$MAIN_CONFIG" | head -6
    else
        echo -e "${RED}❌ robots.txt location NOT found${NC}"
    fi
    
    if grep -q "location = /sitemap.xml" "$MAIN_CONFIG"; then
        echo -e "${GREEN}✅ sitemap.xml location found${NC}"
        grep -A 5 "location = /sitemap.xml" "$MAIN_CONFIG" | head -6
    else
        echo -e "${RED}❌ sitemap.xml location NOT found${NC}"
    fi
    
    # Check root directive
    if grep -q "root /var/www/earnings-table/public" "$MAIN_CONFIG"; then
        echo -e "${GREEN}✅ root directive found${NC}"
    else
        echo -e "${RED}❌ root directive NOT found${NC}"
    fi
fi

# 4. Test config
echo ""
echo -e "${BLUE}Testing Nginx configuration...${NC}"
if nginx -t 2>&1 | grep -q "successful"; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    
    # Check conflicts
    CONFLICTS=$(nginx -t 2>&1 | grep -c "conflicting server name" || echo "0")
    if [ "$CONFLICTS" -eq 0 ]; then
        echo -e "${GREEN}✅ No conflicting server names!${NC}"
    else
        echo -e "${YELLOW}⚠️  Still have $CONFLICTS conflicting server names${NC}"
        echo "Run: nginx -t 2>&1 | grep conflicting"
    fi
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    nginx -t
    exit 1
fi

# 5. Show active config
echo ""
echo -e "${BLUE}Active server block (first 50 lines)...${NC}"
nginx -T 2>/dev/null | grep -A 50 "server_name.*earningsstable.*443" | head -60

# 6. Full restart
echo ""
echo -e "${BLUE}Restarting Nginx (full restart, not reload)...${NC}"
systemctl restart nginx
echo -e "${GREEN}✅ Nginx restarted${NC}"

echo ""
echo -e "${GREEN}🎉 Final fix applied!${NC}"
echo ""
echo "Test:"
echo "  curl -k -I https://earningsstable.com/robots.txt"
echo "  curl -k -I https://earningsstable.com/sitemap.xml"
echo ""
echo "Expected: HTTP/2 200 OK (not 404)"
echo ""

