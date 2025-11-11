#!/bin/bash
# 📊 Quick Status Check

echo "📊 Quick Status Check"
echo "===================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

EXPECTED_IP="89.185.250.213"

# Check DNS
echo -e "${BLUE}DNS Status:${NC}"
DNS_MAIN=$(dig +short earningsstable.com 2>/dev/null || echo "")
DNS_WWW=$(dig +short www.earningsstable.com 2>/dev/null || echo "")

if [ -n "$DNS_MAIN" ] && [ "$DNS_MAIN" = "$EXPECTED_IP" ]; then
    echo -e "  ${GREEN}✅ earningsstable.com → $DNS_MAIN${NC}"
else
    if [ -z "$DNS_MAIN" ]; then
        echo -e "  ${RED}❌ earningsstable.com → No DNS record${NC}"
    else
        echo -e "  ${YELLOW}⚠️  earningsstable.com → $DNS_MAIN (expected $EXPECTED_IP)${NC}"
    fi
fi

if [ -n "$DNS_WWW" ] && [ "$DNS_WWW" = "$EXPECTED_IP" ]; then
    echo -e "  ${GREEN}✅ www.earningsstable.com → $DNS_WWW${NC}"
else
    if [ -z "$DNS_WWW" ]; then
        echo -e "  ${RED}❌ www.earningsstable.com → No DNS record${NC}"
    else
        echo -e "  ${YELLOW}⚠️  www.earningsstable.com → $DNS_WWW (expected $EXPECTED_IP)${NC}"
    fi
fi

echo ""

# Check Nginx
echo -e "${BLUE}Nginx Status:${NC}"
if nginx -t 2>&1 | grep -q "successful"; then
    echo -e "  ${GREEN}✅ Config valid${NC}"
    
    CONFLICTS=$(nginx -t 2>&1 | grep -c "conflicting server name" 2>/dev/null || echo "0")
    CONFLICTS=${CONFLICTS//[^0-9]/}
    if [ -z "$CONFLICTS" ] || [ "$CONFLICTS" -eq 0 ]; then
        echo -e "  ${GREEN}✅ No conflicts${NC}"
    else
        echo -e "  ${RED}❌ $CONFLICTS conflicts${NC}"
    fi
else
    echo -e "  ${RED}❌ Config invalid${NC}"
fi

echo ""

# Check SSL
echo -e "${BLUE}SSL Status:${NC}"
SSL_CERT="/etc/letsencrypt/live/earningsstable.com/fullchain.pem"
if [ -f "$SSL_CERT" ]; then
    echo -e "  ${GREEN}✅ SSL certificate exists${NC}"
else
    echo -e "  ${YELLOW}⚠️  SSL certificate not found (waiting for DNS)${NC}"
fi

echo ""

# Check if monitor is running
echo -e "${BLUE}Monitor Script:${NC}"
if pgrep -f "monitor-dns-and-auto-certbot.sh" > /dev/null; then
    echo -e "  ${GREEN}✅ Monitor script is running${NC}"
    echo "  (Checking DNS every 60 seconds, will auto-run certbot when ready)"
else
    echo -e "  ${YELLOW}⚠️  Monitor script not running${NC}"
    echo "  Run: ./monitor-dns-and-auto-certbot.sh"
fi

echo ""

# Summary
if [ -n "$DNS_MAIN" ] && [ "$DNS_MAIN" = "$EXPECTED_IP" ] && [ -n "$DNS_WWW" ] && [ "$DNS_WWW" = "$EXPECTED_IP" ]; then
    echo -e "${GREEN}🎉 DNS is ready!${NC}"
    echo ""
    echo "Next: Monitor script should auto-run certbot, or run manually:"
    echo "  certbot --nginx -d earningsstable.com -d www.earningsstable.com"
else
    echo -e "${YELLOW}⏳ Waiting for DNS propagation...${NC}"
    echo ""
    echo "Action needed:"
    echo "  1. Add DNS A records in your DNS provider:"
    echo "     - A @ → $EXPECTED_IP"
    echo "     - A www → $EXPECTED_IP"
    echo "  2. Wait 5-30 minutes for propagation"
    echo "  3. Monitor script will auto-detect and run certbot"
fi

echo ""

