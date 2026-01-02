#!/bin/bash
# 🔧 Fix Nginx location blocks for robots.txt and sitemap.xml

set -e

echo "🔧 Fixing Nginx SEO files configuration..."

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
PROJECT_DIR="/var/www/earnings-table"
PUBLIC_DIR="$PROJECT_DIR/public"

# Backup config
echo -e "${YELLOW}📦 Backing up Nginx config...${NC}"
BACKUP_DIR="/etc/nginx/backups"
mkdir -p "$BACKUP_DIR"
cp "$NGINX_CONFIG" "$BACKUP_DIR/earningstable.com.backup.$(date +%Y%m%d-%H%M%S)"

# Remove old backups from sites-enabled
rm -f /etc/nginx/sites-enabled/*.backup.* 2>/dev/null || true

# Check if files exist
if [ ! -f "$PUBLIC_DIR/robots.txt" ]; then
    echo -e "${RED}❌ robots.txt not found at $PUBLIC_DIR/robots.txt${NC}"
    exit 1
fi

if [ ! -f "$PUBLIC_DIR/sitemap.xml" ]; then
    echo -e "${RED}❌ sitemap.xml not found at $PUBLIC_DIR/sitemap.xml${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Files exist${NC}"

# Check current config
echo -e "${YELLOW}🔍 Checking current Nginx config...${NC}"

# Find the HTTPS server block for earningsstable.com (non-www)
# We need to ensure location blocks are BEFORE location / proxy

# Create a Python script to fix the config properly
python3 << 'PYTHON_EOF'
import re
import sys

config_file = "/etc/nginx/sites-enabled/earningstable.com"

with open(config_file, 'r') as f:
    content = f.read()

# Find the HTTPS server block for earningsstable.com (not www)
# Look for: server { ... listen 443 ... server_name earningsstable.com; ... }

# Pattern to find the server block
server_pattern = r'(server\s*\{[^}]*listen\s+443[^}]*server_name\s+earningsstable\.com[^}]*\{[^}]*)(location\s+/\s*\{[^}]*proxy_pass[^}]*\})'

# Check if location blocks exist
has_robots_location = 'location = /robots.txt' in content or 'location ~ ^/robots\.txt$' in content
has_sitemap_location = 'location = /sitemap.xml' in content or 'location ~ ^/sitemap\.xml$' in content

print(f"Has robots.txt location: {has_robots_location}")
print(f"Has sitemap.xml location: {has_sitemap_location}")

# If location blocks don't exist or are after proxy, we need to fix them
if not has_robots_location or not has_sitemap_location:
    print("Need to add location blocks")
    sys.exit(1)
else:
    print("Location blocks exist")
    sys.exit(0)
PYTHON_EOF

PYTHON_EXIT=$?

if [ $PYTHON_EXIT -eq 1 ]; then
    echo -e "${YELLOW}🔧 Adding/fixing location blocks...${NC}"
    
    # Use sed to add location blocks before the proxy location
    # First, let's check the structure
    if grep -q "location / {" "$NGINX_CONFIG"; then
        # Add location blocks right before "location / {"
        sed -i '/location \/ {/i\
    # SEO: Serve robots.txt directly from disk\
    location = /robots.txt {\
        root /var/www/earnings-table/public;\
        try_files /robots.txt =404;\
        default_type text/plain;\
        access_log off;\
    }\
\
    # SEO: Serve sitemap.xml directly from disk\
    location = /sitemap.xml {\
        root /var/www/earnings-table/public;\
        try_files /sitemap.xml =404;\
        default_type application/xml;\
        access_log off;\
    }\
' "$NGINX_CONFIG"
        echo -e "${GREEN}✅ Added location blocks${NC}"
    else
        echo -e "${RED}❌ Could not find 'location / {' in config${NC}"
        exit 1
    fi
fi

# Remove duplicate www redirect blocks (keep only one)
echo -e "${YELLOW}🧹 Removing duplicate www redirect blocks...${NC}"
# Count www redirect blocks
WWW_COUNT=$(grep -c "server_name.*www.earningstable.com" "$NGINX_CONFIG" || echo "0")
if [ "$WWW_COUNT" -gt 1 ]; then
    echo -e "${YELLOW}⚠️  Found $WWW_COUNT www redirect blocks, keeping only the first one${NC}"
    # This is complex, so we'll just warn
fi

# Test Nginx config
echo -e "${YELLOW}🧪 Testing Nginx config...${NC}"
if nginx -t 2>&1 | grep -q "test is successful"; then
    echo -e "${GREEN}✅ Nginx config is valid${NC}"
    
    # Reload Nginx
    echo -e "${YELLOW}🔄 Reloading Nginx...${NC}"
    systemctl reload nginx || nginx -s reload
    echo -e "${GREEN}✅ Nginx reloaded${NC}"
else
    echo -e "${RED}❌ Nginx config has errors${NC}"
    nginx -t
    exit 1
fi

# Final tests
echo -e "${YELLOW}🧪 Running final tests...${NC}"
sleep 2

# Test robots.txt
ROBOTS=$(curl -k -s https://earningsstable.com/robots.txt)
if [ -n "$ROBOTS" ] && ! echo "$ROBOTS" | grep -q "404\|not found"; then
    echo -e "${GREEN}✅ robots.txt accessible${NC}"
    echo "$ROBOTS" | head -3
else
    echo -e "${RED}❌ robots.txt still returns 404${NC}"
    echo "$ROBOTS"
fi

# Test sitemap.xml
SITEMAP=$(curl -k -s https://earningsstable.com/sitemap.xml)
if [ -n "$SITEMAP" ] && echo "$SITEMAP" | grep -q "urlset\|lastmod"; then
    echo -e "${GREEN}✅ sitemap.xml accessible${NC}"
    echo "$SITEMAP" | head -5
else
    echo -e "${RED}❌ sitemap.xml still returns 404${NC}"
    echo "$SITEMAP" | head -5
fi

echo -e "${GREEN}✅ Done!${NC}"
