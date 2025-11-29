#!/bin/bash
# 🔧 Remove ALL duplicates - Final fix

set -e

echo "🔧 Removing ALL duplicate configs..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Step 1: Remove ALL backup files from sites-enabled/
echo -e "${BLUE}Step 1: Removing ALL backup files from sites-enabled/...${NC}"
rm -f /etc/nginx/sites-enabled/*.backup*
rm -f /etc/nginx/sites-enabled/*.bak*
echo -e "${GREEN}✅ All backup files removed${NC}"

# Step 2: Check for symlinks
echo ""
echo -e "${BLUE}Step 2: Checking for symlinks...${NC}"
ls -la /etc/nginx/sites-enabled/ | grep -E "earnings"

# Check if earnings-table is symlinked
if [ -L "/etc/nginx/sites-enabled/earnings-table" ]; then
    echo -e "${YELLOW}⚠️  earnings-table is symlinked${NC}"
    unlink /etc/nginx/sites-enabled/earnings-table
    echo -e "${GREEN}✅ Removed earnings-table symlink${NC}"
fi

# Check if earningstable.conf is symlinked
if [ -L "/etc/nginx/sites-enabled/earningstable.conf" ]; then
    echo -e "${YELLOW}⚠️  earningstable.conf is symlinked${NC}"
    unlink /etc/nginx/sites-enabled/earningstable.conf
    echo -e "${GREEN}✅ Removed earningstable.conf symlink${NC}"
fi

# Check if sites-available/earningstable.com is symlinked (should be direct file)
if [ -L "/etc/nginx/sites-enabled/earningstable.com" ]; then
    echo -e "${YELLOW}⚠️  earningstable.com is symlink, converting to direct file...${NC}"
    TARGET=$(readlink /etc/nginx/sites-enabled/earningstable.com)
    cp "$TARGET" /etc/nginx/sites-enabled/earningstable.com
    unlink /etc/nginx/sites-enabled/earningstable.com
    echo -e "${GREEN}✅ Converted to direct file${NC}"
fi

# Step 3: Verify only ONE file in sites-enabled/
echo ""
echo -e "${BLUE}Step 3: Verifying files in sites-enabled/...${NC}"
FILES=$(ls -1 /etc/nginx/sites-enabled/ | grep -v "^default$" | grep -i earnings || echo "")
FILE_COUNT=$(echo "$FILES" | grep -c . || echo "0")

if [ "$FILE_COUNT" -eq 1 ]; then
    echo -e "${GREEN}✅ Only ONE earnings config file${NC}"
    echo "  File: $(echo "$FILES" | head -1)"
elif [ "$FILE_COUNT" -gt 1 ]; then
    echo -e "${RED}❌ Multiple earnings config files found:${NC}"
    echo "$FILES" | while read -r file; do
        echo "  - $file"
    done
    echo ""
    echo "Removing duplicates..."
    echo "$FILES" | tail -n +2 | while read -r file; do
        rm -f "/etc/nginx/sites-enabled/$file"
        echo "  Removed: $file"
    done
fi

# Step 4: Check for duplicate server blocks in the main file
echo ""
echo -e "${BLUE}Step 4: Checking for duplicate server blocks in main config...${NC}"
MAIN_CONFIG="/etc/nginx/sites-enabled/earningstable.com"

if [ -f "$MAIN_CONFIG" ]; then
    HTTPS_BLOCKS=$(grep -c "listen 443" "$MAIN_CONFIG" || echo "0")
    if [ "$HTTPS_BLOCKS" -gt 1 ]; then
        echo -e "${YELLOW}⚠️  Multiple HTTPS server blocks in main config ($HTTPS_BLOCKS)${NC}"
        echo "This needs manual editing"
    else
        echo -e "${GREEN}✅ Only ONE HTTPS server block${NC}"
    fi
fi

# Step 5: Test and reload
echo ""
echo -e "${BLUE}Step 5: Testing and reloading Nginx...${NC}"

if nginx -t 2>&1 | grep -q "successful"; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    
    CONFLICTS=$(nginx -t 2>&1 | grep -c "conflicting server name" || echo "0")
    if [ "$CONFLICTS" -eq 0 ]; then
        echo -e "${GREEN}✅ No conflicting server names!${NC}"
    else
        echo -e "${YELLOW}⚠️  Still have $CONFLICTS conflicting server names${NC}"
        echo "There may be server blocks in other files"
    fi
    
    systemctl restart nginx
    echo -e "${GREEN}✅ Nginx restarted${NC}"
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    nginx -t
    exit 1
fi

# Step 6: Final verification
echo ""
echo -e "${BLUE}Step 6: Final verification...${NC}"
echo "Files in sites-enabled/:"
ls -la /etc/nginx/sites-enabled/ | grep -v "^total" | grep -v "^d"

echo ""
echo "Testing endpoints:"
echo "  curl -k -I https://earningsstable.com/robots.txt"
echo "  curl -k -I https://earningsstable.com/sitemap.xml"
echo ""

echo -e "${GREEN}🎉 All duplicates removed!${NC}"

