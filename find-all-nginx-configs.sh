#!/bin/bash
# 🔍 Find ALL Nginx configs with earningsstable.com to identify duplicates

echo "🔍 Finding ALL Nginx configurations with earningsstable.com..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== Configs in sites-enabled ===${NC}"
ls -la /etc/nginx/sites-enabled/ | grep -v "^total" | grep -v "^d"

echo ""
echo -e "${BLUE}=== Configs in sites-available ===${NC}"
ls -la /etc/nginx/sites-available/ | grep -v "^total" | grep -v "^d"

echo ""
echo -e "${BLUE}=== Searching for 'earningsstable' in all configs ===${NC}"
echo "sites-enabled:"
grep -l "earningsstable\|earningstable" /etc/nginx/sites-enabled/* 2>/dev/null || echo "  None found"

echo ""
echo "sites-available:"
grep -l "earningsstable\|earningstable" /etc/nginx/sites-available/* 2>/dev/null || echo "  None found"

echo ""
echo -e "${BLUE}=== All server blocks with earningsstable ===${NC}"
for file in /etc/nginx/sites-enabled/* /etc/nginx/sites-available/*; do
    if [ -f "$file" ] && grep -q "earningsstable\|earningstable" "$file" 2>/dev/null; then
        echo ""
        echo "File: $file"
        echo "---"
        grep -n "server_name\|listen" "$file" | grep -i earnings
        echo ""
    fi
done

echo ""
echo -e "${BLUE}=== Active compiled config (what Nginx actually uses) ===${NC}"
echo "Searching for server_name with earningsstable in compiled config..."
nginx -T 2>/dev/null | grep -B 2 -A 40 "server_name.*earn.*stable" | head -80

echo ""
echo -e "${BLUE}=== Recommendation ===${NC}"
echo "1. List all config files above"
echo "2. Keep ONLY ONE config file in sites-enabled/"
echo "3. Disable/remove all others:"
echo "   unlink /etc/nginx/sites-enabled/other-config-file"
echo "4. Ensure the active config has location blocks for robots.txt/sitemap.xml"

