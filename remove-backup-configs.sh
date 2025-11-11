#!/bin/bash
# 🧹 Remove ALL backup configs from sites-enabled/ - they're causing conflicts!

set -e

echo "🧹 Removing backup configs from sites-enabled/..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Finding backup files in sites-enabled/...${NC}"
BACKUP_FILES=$(ls /etc/nginx/sites-enabled/*.backup* 2>/dev/null || echo "")

if [ -z "$BACKUP_FILES" ]; then
    echo -e "${GREEN}✅ No backup files found${NC}"
    exit 0
fi

echo "Found backup files:"
echo "$BACKUP_FILES" | while read -r file; do
    echo "  - $file"
done

echo ""
echo -e "${YELLOW}⚠️  These backup files are being loaded by Nginx!${NC}"
echo "Nginx loads ALL files in sites-enabled/, including backups."
echo ""

read -p "Remove all backup files from sites-enabled/? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled"
    exit 0
fi

echo ""
echo -e "${BLUE}Removing backup files...${NC}"
echo "$BACKUP_FILES" | while read -r file; do
    if [ -f "$file" ]; then
        echo "  Removing: $file"
        rm -f "$file"
    fi
done

echo ""
echo -e "${BLUE}Checking for earningsstable.com in default config...${NC}"
DEFAULT_CONFIG="/etc/nginx/sites-available/default"
if [ -f "$DEFAULT_CONFIG" ] && grep -q "earningsstable\|earningstable" "$DEFAULT_CONFIG"; then
    echo -e "${YELLOW}⚠️  default config also has earningsstable.com!${NC}"
    echo "This will cause conflicts."
    echo ""
    echo "Options:"
    echo "  1. Comment out earningsstable.com in default config"
    echo "  2. Skip (you can do it manually)"
    read -p "Choose (1/2): " choice
    
    if [ "$choice" = "1" ]; then
        # Comment out server blocks with earningsstable
        sed -i 's/^\([[:space:]]*server_name.*earningsstable\)/#\1/' "$DEFAULT_CONFIG"
        sed -i 's/^\([[:space:]]*server_name.*earningstable\)/#\1/' "$DEFAULT_CONFIG"
        echo -e "${GREEN}✅ Commented out earningsstable.com in default config${NC}"
    fi
fi

# Check for earnings-table config
EARNINGS_TABLE_CONFIG="/etc/nginx/sites-available/earnings-table"
if [ -f "$EARNINGS_TABLE_CONFIG" ] && [ -L "/etc/nginx/sites-enabled/earnings-table" ]; then
    echo ""
    echo -e "${YELLOW}⚠️  earnings-table config is enabled!${NC}"
    echo "This may also cause conflicts."
    read -p "Disable earnings-table config? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        unlink /etc/nginx/sites-enabled/earnings-table 2>/dev/null || rm -f /etc/nginx/sites-enabled/earnings-table
        echo -e "${GREEN}✅ Disabled earnings-table config${NC}"
    fi
fi

# Test config
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
        echo "There may be more configs to clean up"
    fi
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    nginx -t
    exit 1
fi

# Reload
echo ""
echo -e "${BLUE}Reloading Nginx...${NC}"
systemctl reload nginx
echo -e "${GREEN}✅ Nginx reloaded${NC}"

echo ""
echo -e "${GREEN}🎉 Backup configs removed!${NC}"
echo ""
echo "Test:"
echo "  curl -k -I https://earningsstable.com/robots.txt"
echo "  Expected: HTTP/2 200 OK"
echo ""

