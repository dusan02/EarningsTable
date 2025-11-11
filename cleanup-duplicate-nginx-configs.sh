#!/bin/bash
# 🧹 Cleanup Duplicate Nginx Configs - Keep only one, disable others

set -e

echo "🧹 Cleaning up duplicate Nginx configurations..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

MAIN_CONFIG="/etc/nginx/sites-enabled/earningstable.com"

if [ ! -f "$MAIN_CONFIG" ]; then
    echo -e "${RED}❌ Main config not found: $MAIN_CONFIG${NC}"
    exit 1
fi

echo -e "${BLUE}Main config: $MAIN_CONFIG${NC}"
echo ""

# Find all configs with earningsstable
echo -e "${BLUE}Finding all configs with earningsstable...${NC}"
ALL_CONFIGS=$(grep -l "earningsstable\|earningstable" /etc/nginx/sites-enabled/* /etc/nginx/sites-available/* 2>/dev/null | sort -u || echo "")

if [ -z "$ALL_CONFIGS" ]; then
    echo "No other configs found"
    exit 0
fi

echo "Found configs:"
echo "$ALL_CONFIGS" | while read -r config; do
    if [ "$config" != "$MAIN_CONFIG" ]; then
        echo "  - $config"
    fi
done

echo ""
echo -e "${YELLOW}⚠️  These configs may be causing conflicts${NC}"
echo ""
echo "Options:"
echo "  1. Disable configs in sites-enabled/ (keep in sites-available/)"
echo "  2. Remove configs completely"
echo "  3. Show me what's in each config first"
echo ""
read -p "Choose option (1/2/3): " choice

case $choice in
    1)
        echo ""
        echo -e "${BLUE}Disabling duplicate configs in sites-enabled/...${NC}"
        echo "$ALL_CONFIGS" | while read -r config; do
            if [ "$config" != "$MAIN_CONFIG" ] && [[ "$config" == *"sites-enabled"* ]]; then
                echo "  Disabling: $config"
                mv "$config" "${config}.disabled" 2>/dev/null || unlink "$config" 2>/dev/null || echo "    Could not disable"
            fi
        done
        ;;
    2)
        echo ""
        echo -e "${RED}⚠️  This will DELETE config files!${NC}"
        read -p "Are you sure? Type 'yes' to confirm: " confirm
        if [ "$confirm" = "yes" ]; then
            echo "$ALL_CONFIGS" | while read -r config; do
                if [ "$config" != "$MAIN_CONFIG" ]; then
                    echo "  Removing: $config"
                    rm -f "$config"
                fi
            done
        else
            echo "Cancelled"
            exit 0
        fi
        ;;
    3)
        echo ""
        echo -e "${BLUE}Showing contents of each config...${NC}"
        echo "$ALL_CONFIGS" | while read -r config; do
            echo ""
            echo "=== $config ==="
            grep -E "server_name|listen|location" "$config" | head -20
        done
        exit 0
        ;;
    *)
        echo "Invalid choice"
        exit 1
        ;;
esac

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
        echo "There may be configs in sites-available/ that are still linked"
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
echo -e "${GREEN}🎉 Cleanup complete!${NC}"
echo ""
echo "Test:"
echo "  curl -k -I https://earningsstable.com/robots.txt"
echo ""

