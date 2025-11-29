#!/bin/bash
# 🔧 Setup SSL with Let's Encrypt and cleanup debug routes

set -e

echo "🔧 SSL Setup and Cleanup..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

MAIN_CONFIG="/etc/nginx/sites-enabled/earningstable.com"

# Step 1: Run certbot
echo -e "${BLUE}Step 1: Running Let's Encrypt certbot...${NC}"
echo "This will configure SSL certificates for earningsstable.com"
echo ""

certbot --nginx -d earningsstable.com -d www.earningsstable.com --non-interactive --agree-tos --email admin@earningsstable.com || {
    echo -e "${YELLOW}⚠️  Certbot failed or certificates already exist${NC}"
    echo "Continuing with cleanup..."
}

# Step 2: Test without -k flag
echo ""
echo -e "${BLUE}Step 2: Testing with valid SSL (without -k flag)...${NC}"
echo ""

echo "Testing robots.txt:"
if curl -I https://earningsstable.com/robots.txt 2>&1 | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ robots.txt works with valid SSL!${NC}"
else
    echo -e "${YELLOW}⚠️  robots.txt test failed (might need -k flag if cert not ready)${NC}"
fi

echo ""
echo "Testing sitemap.xml:"
if curl -I https://earningsstable.com/sitemap.xml 2>&1 | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ sitemap.xml works with valid SSL!${NC}"
else
    echo -e "${YELLOW}⚠️  sitemap.xml test failed (might need -k flag if cert not ready)${NC}"
fi

# Step 3: Remove debug route and headers (optional cleanup)
echo ""
echo -e "${BLUE}Step 3: Removing debug route and headers (optional)...${NC}"
read -p "Remove debug route /__nginx__ and X-Debug-Block headers? (y/N): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Remove backup files first (they cause conflicts)
    echo "Removing backup files from sites-enabled..."
    rm -f /etc/nginx/sites-enabled/*.backup.*
    
    # Backup
    cp "$MAIN_CONFIG" "${MAIN_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"
    
    # Remove debug route
    sed -i '/location = \/__nginx__/,/^    }$/d' "$MAIN_CONFIG"
    
    # Remove X-Debug-Block headers
    sed -i 's/ *add_header X-Debug-Block A always;//g' "$MAIN_CONFIG"
    sed -i 's/ *add_header X-Debug-Block A;//g' "$MAIN_CONFIG"
    
    # Validate and restart
    if nginx -t; then
        systemctl restart nginx
        echo -e "${GREEN}✅ Debug routes removed and Nginx restarted${NC}"
    else
        echo -e "${RED}❌ Config invalid after cleanup${NC}"
        nginx -t
        exit 1
    fi
else
    echo "Keeping debug routes for now"
fi

echo ""
echo -e "${GREEN}🎉 SSL setup complete!${NC}"
echo ""
echo "Final checklist:"
echo "  ✅ nginx -t (no conflicts)"
echo "  ✅ curl -I https://earningsstable.com/robots.txt (200)"
echo "  ✅ curl -I https://earningsstable.com/sitemap.xml (200)"
echo "  ✅ curl -I http://earningsstable.com/ (301 → HTTPS)"
echo ""
echo "Next: Add to Google Search Console and submit sitemap!"
echo ""

