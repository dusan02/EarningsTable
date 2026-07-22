#!/bin/bash
# 🔧 Quick Fix - Remove backup files and check DNS

set -e

echo "🔧 Quick Fix - Backup Files & DNS Check..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Step 1: Remove backup files
echo -e "${BLUE}Step 1: Removing backup files from sites-enabled...${NC}"
BACKUP_FILES=$(find /etc/nginx/sites-enabled -name "*.backup.*" -type f 2>/dev/null || true)

if [ -n "$BACKUP_FILES" ]; then
    echo "Found backup files:"
    echo "$BACKUP_FILES"
    rm -f /etc/nginx/sites-enabled/*.backup.*
    echo -e "${GREEN}✅ Backup files removed${NC}"
else
    echo -e "${GREEN}✅ No backup files found${NC}"
fi

# Step 2: Validate Nginx config
echo ""
echo -e "${BLUE}Step 2: Validating Nginx configuration...${NC}"
if nginx -t 2>&1 | grep -q "successful"; then
    echo -e "${GREEN}✅ Config valid${NC}"
    
    # Check for conflicts
    CONFLICTS=$(nginx -t 2>&1 | grep -c "conflicting server name" 2>/dev/null || echo "0")
    CONFLICTS=${CONFLICTS//[^0-9]/}
    if [ -z "$CONFLICTS" ] || [ "$CONFLICTS" -eq 0 ]; then
        echo -e "${GREEN}✅ No conflicting server names!${NC}"
    else
        echo -e "${YELLOW}⚠️  Still have $CONFLICTS conflicts${NC}"
        nginx -t 2>&1 | grep "conflicting" | head -3
    fi
    
    systemctl restart nginx
    echo -e "${GREEN}✅ Nginx restarted${NC}"
else
    echo -e "${RED}❌ Config invalid${NC}"
    nginx -t
    exit 1
fi

# Step 3: Check DNS
echo ""
echo -e "${BLUE}Step 3: Checking DNS records...${NC}"
echo ""

echo "Checking earningsstable.com:"
DNS_MAIN=$(dig +short earningsstable.com 2>/dev/null || echo "")
if [ -n "$DNS_MAIN" ]; then
    echo -e "${GREEN}✅ DNS A record: $DNS_MAIN${NC}"
else
    echo -e "${RED}❌ No DNS A record found for earningsstable.com${NC}"
    echo "   You need to add: A earningsstable.com -> <your-server-ip>"
fi

echo ""
echo "Checking www.earningsstable.com:"
DNS_WWW=$(dig +short www.earningsstable.com 2>/dev/null || echo "")
if [ -n "$DNS_WWW" ]; then
    echo -e "${GREEN}✅ DNS A record: $DNS_WWW${NC}"
else
    echo -e "${RED}❌ No DNS A record found for www.earningsstable.com${NC}"
    echo "   You need to add: A www.earningsstable.com -> <your-server-ip>"
fi

# Step 4: Check ports
echo ""
echo -e "${BLUE}Step 4: Checking ports 80/443...${NC}"
PORTS=$(ss -tulpn | grep -E ':(80|443)\s' || echo "")
if [ -n "$PORTS" ]; then
    echo "$PORTS"
    if echo "$PORTS" | grep -q nginx; then
        echo -e "${GREEN}✅ Nginx is listening on ports 80/443${NC}"
    else
        echo -e "${YELLOW}⚠️  Something else is listening on ports 80/443${NC}"
    fi
else
    echo -e "${RED}❌ Nothing listening on ports 80/443${NC}"
fi

# Step 5: Test with --resolve (bypasses DNS)
echo ""
echo -e "${BLUE}Step 5: Testing with --resolve (bypasses DNS/CDN)...${NC}"
echo ""

echo "Testing debug route:"
DEBUG_RESPONSE=$(curl -k -i --resolve earningsstable.com:443:127.0.0.1 https://earningsstable.com/__nginx__ 2>&1 | head -10)
if echo "$DEBUG_RESPONSE" | grep -q "I am earningsstable.com 443 block"; then
    echo -e "${GREEN}✅ Debug route works!${NC}"
else
    echo -e "${YELLOW}⚠️  Debug route failed${NC}"
fi

echo ""
echo "Testing robots.txt:"
ROBOTS_RESPONSE=$(curl -k -I --resolve earningsstable.com:443:127.0.0.1 https://earningsstable.com/robots.txt 2>&1 | head -10)
if echo "$ROBOTS_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ robots.txt works!${NC}"
else
    echo -e "${YELLOW}⚠️  robots.txt failed${NC}"
fi

# Summary
echo ""
echo -e "${BLUE}Summary:${NC}"
echo ""

if [ -z "$DNS_MAIN" ] || [ -z "$DNS_WWW" ]; then
    echo -e "${YELLOW}⚠️  DNS records are missing${NC}"
    echo "   Add DNS A records before running certbot"
    echo ""
    echo "Next steps:"
    echo "  1. Add DNS A records in your DNS provider:"
    echo "     - A earningsstable.com -> <your-server-ip>"
    echo "     - A www.earningsstable.com -> <your-server-ip>"
    echo "  2. Wait for DNS propagation (can take a few minutes)"
    echo "  3. Run: certbot --nginx -d earningsstable.com -d www.earningsstable.com"
    echo "  4. Test: curl -I https://earningsstable.com/robots.txt"
else
    echo -e "${GREEN}✅ DNS records are configured${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Run: certbot --nginx -d earningsstable.com -d www.earningsstable.com"
    echo "  2. Test: curl -I https://earningsstable.com/robots.txt"
fi

echo ""

