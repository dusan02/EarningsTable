#!/bin/bash
# 🔍 Quick Check All - DNS, HTTP, HTTPS, SSL, Monitor

set -e

echo "🔍 Quick Check All"
echo "=================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

EXPECTED_IP="89.185.250.213"

# 1. Check DNS propagation
echo -e "${BLUE}1️⃣ DNS Propagation Check${NC}"
echo "----------------------"

DNS_MAIN=$(dig +short earningsstable.com 2>/dev/null || echo "")
DNS_WWW=$(dig +short www.earningsstable.com 2>/dev/null || echo "")

echo "earningsstable.com:"
if [ -n "$DNS_MAIN" ]; then
    if [ "$DNS_MAIN" = "$EXPECTED_IP" ]; then
        echo -e "  ${GREEN}✅ $DNS_MAIN (correct)${NC}"
    else
        echo -e "  ${YELLOW}⚠️  $DNS_MAIN (expected $EXPECTED_IP)${NC}"
    fi
else
    echo -e "  ${RED}❌ No DNS record (not propagated yet)${NC}"
fi

echo ""
echo "www.earningsstable.com:"
if [ -n "$DNS_WWW" ]; then
    if [ "$DNS_WWW" = "$EXPECTED_IP" ]; then
        echo -e "  ${GREEN}✅ $DNS_WWW (correct)${NC}"
    else
        echo -e "  ${YELLOW}⚠️  $DNS_WWW (expected $EXPECTED_IP)${NC}"
    fi
else
    echo -e "  ${RED}❌ No DNS record (not propagated yet)${NC}"
fi

echo ""

# 2. Check HTTP response
echo -e "${BLUE}2️⃣ HTTP Response Check${NC}"
echo "----------------------"

HTTP_RESPONSE=$(curl -I http://earningsstable.com/ 2>&1 | head -5)
echo "$HTTP_RESPONSE"

if echo "$HTTP_RESPONSE" | grep -q "301\|Location: https://earningsstable.com"; then
    echo -e "${GREEN}✅ HTTP redirects to HTTPS${NC}"
elif echo "$HTTP_RESPONSE" | grep -q "404"; then
    echo -e "${YELLOW}⚠️  HTTP returns 404 (DNS may not be propagated yet)${NC}"
else
    echo -e "${YELLOW}⚠️  Unexpected HTTP response${NC}"
fi

echo ""

# 3. Check HTTPS / SSL
echo -e "${BLUE}3️⃣ HTTPS / SSL Check${NC}"
echo "----------------------"

echo "Testing robots.txt:"
ROBOTS_RESPONSE=$(curl -k -I https://earningsstable.com/robots.txt 2>&1 | head -5)
if echo "$ROBOTS_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ robots.txt returns 200${NC}"
    echo "$ROBOTS_RESPONSE" | head -3
else
    if echo "$ROBOTS_RESPONSE" | grep -q "SSL certificate problem"; then
        echo -e "${YELLOW}⚠️  SSL certificate problem (self-signed or DNS not ready)${NC}"
    else
        echo -e "${RED}❌ robots.txt failed${NC}"
        echo "$ROBOTS_RESPONSE" | head -3
    fi
fi

echo ""
echo "Testing sitemap.xml:"
SITEMAP_RESPONSE=$(curl -k -I https://earningsstable.com/sitemap.xml 2>&1 | head -5)
if echo "$SITEMAP_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ sitemap.xml returns 200${NC}"
    echo "$SITEMAP_RESPONSE" | head -3
else
    if echo "$SITEMAP_RESPONSE" | grep -q "SSL certificate problem"; then
        echo -e "${YELLOW}⚠️  SSL certificate problem (self-signed or DNS not ready)${NC}"
    else
        echo -e "${RED}❌ sitemap.xml failed${NC}"
        echo "$SITEMAP_RESPONSE" | head -3
    fi
fi

echo ""

# 4. Check if certbot/monitor is running
echo -e "${BLUE}4️⃣ Certbot / Monitor Check${NC}"
echo "----------------------"

CERTBOT_PID=$(pgrep -f certbot || echo "")
MONITOR_PID=$(pgrep -f monitor-dns-and-auto-certbot.sh || echo "")

if [ -n "$CERTBOT_PID" ]; then
    echo -e "${GREEN}✅ Certbot is running (PID: $CERTBOT_PID)${NC}"
    pgrep -a certbot | head -2
else
    echo -e "${YELLOW}⚠️  Certbot is not running${NC}"
fi

echo ""

if [ -n "$MONITOR_PID" ]; then
    echo -e "${GREEN}✅ Monitor script is running (PID: $MONITOR_PID)${NC}"
    pgrep -a monitor-dns-and-auto-certbot.sh | head -2
else
    echo -e "${YELLOW}⚠️  Monitor script is not running${NC}"
    echo "  Run: ./monitor-dns-and-auto-certbot.sh"
fi

echo ""

# 5. Quick SEO status
echo -e "${BLUE}5️⃣ Quick SEO Status${NC}"
echo "----------------------"

if [ -f "check-status.sh" ]; then
    ./check-status.sh
else
    echo -e "${YELLOW}⚠️  check-status.sh not found${NC}"
fi

echo ""

# Summary
echo -e "${BLUE}📊 Summary${NC}"
echo "-------"

if [ -n "$DNS_MAIN" ] && [ "$DNS_MAIN" = "$EXPECTED_IP" ] && [ -n "$DNS_WWW" ] && [ "$DNS_WWW" = "$EXPECTED_IP" ]; then
    echo -e "${GREEN}✅ DNS is ready!${NC}"
    echo ""
    echo "Next steps:"
    if [ -z "$MONITOR_PID" ]; then
        echo "  1. Run: ./monitor-dns-and-auto-certbot.sh"
    else
        echo "  1. Monitor script is running - it will auto-run certbot"
    fi
    echo "  2. Wait for certbot to complete"
    echo "  3. Test: curl -I https://earningsstable.com/robots.txt"
else
    echo -e "${YELLOW}⏳ DNS not ready yet${NC}"
    echo ""
    echo "Action needed:"
    echo "  1. Add DNS A records in your DNS provider:"
    echo "     - A @ → $EXPECTED_IP"
    echo "     - A www → $EXPECTED_IP"
    echo "  2. Wait 5-30 minutes for propagation"
    if [ -z "$MONITOR_PID" ]; then
        echo "  3. Run: ./monitor-dns-and-auto-certbot.sh"
    else
        echo "  3. Monitor script is running - it will auto-detect DNS"
    fi
fi

echo ""

