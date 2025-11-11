#!/bin/bash
# 🔧 Fix Duplicate Server Blocks - Move location blocks to active server block

set -e

echo "🔧 Fixing duplicate server blocks..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"

if [ ! -f "$NGINX_CONFIG" ]; then
    echo -e "${RED}❌ Config not found: $NGINX_CONFIG${NC}"
    exit 1
fi

# Backup
BACKUP_FILE="${NGINX_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"
cp "$NGINX_CONFIG" "$BACKUP_FILE"
echo -e "${GREEN}✅ Backup: $BACKUP_FILE${NC}"
echo ""

# Check for duplicate server blocks
echo -e "${BLUE}Checking for duplicate server blocks...${NC}"
SERVER_COUNT=$(grep -c "^server {" "$NGINX_CONFIG" || echo "0")
HTTPS_COUNT=$(grep -c "listen 443" "$NGINX_CONFIG" || echo "0")

echo "  Total server blocks: $SERVER_COUNT"
echo "  HTTPS server blocks: $HTTPS_COUNT"
echo ""

if [ "$HTTPS_COUNT" -gt 1 ]; then
    echo -e "${YELLOW}⚠️  Multiple HTTPS server blocks found!${NC}"
    echo "  This is likely causing the 'conflicting server name' warnings"
    echo ""
    
    # Show all HTTPS server blocks
    echo "HTTPS server blocks:"
    grep -n "listen 443" "$NGINX_CONFIG"
    echo ""
    
    # Find which one has location blocks
    echo "Checking which server block has location blocks..."
    awk '/listen 443/,/^}/ { 
        if (/listen 443/) { block_start=NR; in_block=1 }
        if (in_block && /location = \/robots.txt/) { 
            print "  Location blocks found in server block starting at line", block_start
            found=1
        }
        if (/^}/ && in_block) { in_block=0 }
    }' "$NGINX_CONFIG"
    echo ""
    
    echo -e "${YELLOW}Recommendation:${NC}"
    echo "  1. Keep only ONE HTTPS server block"
    echo "  2. Ensure location blocks are in that block"
    echo "  3. Remove or comment out duplicate blocks"
    echo ""
    echo "Would you like to see the full config structure? (y/n)"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        echo ""
        echo "=== Full Config Structure ==="
        awk '/^server {/,/^}/ { print NR": "$0 }' "$NGINX_CONFIG" | head -100
    fi
else
    echo -e "${GREEN}✅ Only one HTTPS server block found${NC}"
    echo ""
    echo "Checking if location blocks are in the correct place..."
    
    # Check if location blocks are before location /
    ROBOTS_LINE=$(grep -n "location = /robots.txt" "$NGINX_CONFIG" | cut -d: -f1)
    LOCATION_SLASH_LINE=$(grep -n "location / {" "$NGINX_CONFIG" | head -1 | cut -d: -f1)
    
    if [ -n "$ROBOTS_LINE" ] && [ -n "$LOCATION_SLASH_LINE" ]; then
        if [ "$ROBOTS_LINE" -lt "$LOCATION_SLASH_LINE" ]; then
            echo -e "${GREEN}✅ Location blocks are correctly placed before location /${NC}"
            echo "  robots.txt at line: $ROBOTS_LINE"
            echo "  location / at line: $LOCATION_SLASH_LINE"
        else
            echo -e "${RED}❌ Location blocks are AFTER location /${NC}"
            echo "  This is the problem! Location blocks must be before location /"
        fi
    fi
fi

echo ""
echo -e "${BLUE}Checking active Nginx configuration...${NC}"
echo "Running: nginx -T (shows compiled config)..."
echo ""

# Show what Nginx actually uses
ACTIVE_CONFIG=$(nginx -T 2>/dev/null | grep -A 30 "server_name.*earningstable.*443" | head -40 || echo "Could not get active config")

if echo "$ACTIVE_CONFIG" | grep -q "location = /robots.txt"; then
    echo -e "${GREEN}✅ Location blocks ARE in active config${NC}"
else
    echo -e "${RED}❌ Location blocks are NOT in active config${NC}"
    echo "  This confirms the problem - location blocks are in an ignored server block"
fi

echo ""
echo "=== Active Server Block (first 40 lines) ==="
echo "$ACTIVE_CONFIG"
echo ""

echo -e "${BLUE}Next Steps:${NC}"
echo "  1. If location blocks are missing from active config, move them to the first server block"
echo "  2. Remove duplicate server blocks"
echo "  3. Test: curl -k -I https://earningsstable.com/robots.txt"
echo ""

