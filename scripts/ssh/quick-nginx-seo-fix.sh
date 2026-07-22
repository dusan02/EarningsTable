#!/bin/bash
# 🚀 Quick Nginx SEO Fix - Add robots.txt and sitemap.xml aliases
# This is the FASTEST solution - serves files directly from Nginx

set -e

echo "🚀 Quick Nginx SEO Fix..."
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

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
NGINX_AVAILABLE="/etc/nginx/sites-available/earningstable.com"

# Check if config exists
if [ ! -f "$NGINX_CONFIG" ] && [ ! -f "$NGINX_AVAILABLE" ]; then
    echo -e "${RED}❌ Nginx config not found${NC}"
    echo "Looking for: $NGINX_CONFIG or $NGINX_AVAILABLE"
    echo ""
    echo "Available configs:"
    ls -la /etc/nginx/sites-enabled/ | grep -i earnings || echo "  None found"
    exit 1
fi

# Use available config if enabled doesn't exist
if [ ! -f "$NGINX_CONFIG" ]; then
    NGINX_CONFIG="$NGINX_AVAILABLE"
fi

echo -e "${BLUE}Using config: $NGINX_CONFIG${NC}"
echo ""

# Backup
BACKUP_FILE="${NGINX_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"
$SUDO cp "$NGINX_CONFIG" "$BACKUP_FILE"
echo -e "${GREEN}✅ Backup created: $BACKUP_FILE${NC}"
echo ""

# Check if HTTPS server block exists
if ! grep -q "listen 443" "$NGINX_CONFIG"; then
    echo -e "${RED}❌ No HTTPS server block found (listen 443)${NC}"
    echo "This script expects an HTTPS server block."
    echo "Run update-nginx-seo.sh first to create full config."
    exit 1
fi

# Check if location blocks already exist
if grep -q "location = /robots.txt" "$NGINX_CONFIG"; then
    echo -e "${YELLOW}⚠️  robots.txt location already exists${NC}"
    echo "Skipping addition..."
else
    echo -e "${BLUE}Adding robots.txt and sitemap.xml locations...${NC}"
    
    # Find the HTTPS server block and add locations before the main location /
    # Insert before the first "location /" in HTTPS block
    $SUDO sed -i '/listen 443/,/^}/ {
        /location \// i\
    # SEO: Serve robots.txt and sitemap.xml directly from disk\
    location = /robots.txt {\
        alias /var/www/earnings-table/public/robots.txt;\
        access_log off;\
        default_type text/plain;\
        add_header Content-Type text/plain;\
    }\
\
    location = /sitemap.xml {\
        alias /var/www/earnings-table/public/sitemap.xml;\
        access_log off;\
        default_type application/xml;\
        add_header Content-Type application/xml;\
    }
    }' "$NGINX_CONFIG"
    
    echo -e "${GREEN}✅ Locations added${NC}"
fi

# Test config
echo ""
echo -e "${BLUE}Testing Nginx configuration...${NC}"
if $SUDO nginx -t; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    echo "Restoring backup..."
    $SUDO cp "$BACKUP_FILE" "$NGINX_CONFIG"
    exit 1
fi

# Reload Nginx
echo ""
echo -e "${BLUE}Reloading Nginx...${NC}"
$SUDO systemctl reload nginx
echo -e "${GREEN}✅ Nginx reloaded${NC}"

echo ""
echo -e "${GREEN}🎉 Nginx SEO fix applied!${NC}"
echo ""
echo -e "${BLUE}Testing endpoints:${NC}"
echo "  curl -k -I https://earningsstable.com/robots.txt"
echo "  curl -k -I https://earningsstable.com/sitemap.xml"
echo ""
echo "Expected: HTTP/2 200 OK with correct Content-Type"
echo ""

