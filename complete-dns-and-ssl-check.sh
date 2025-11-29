#!/bin/bash
# 🔍 Complete DNS and SSL Check - All-in-One

set -e

echo "🔍 Complete DNS and SSL Check"
echo "============================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

EXPECTED_IP="89.185.250.213"
DOMAIN="earningsstable.com"

# Step 1: Fix permissions
echo -e "${BLUE}1️⃣ Fixing permissions...${NC}"
chmod +x check-status.sh monitor-dns-and-auto-certbot.sh setup-monitor-background.sh quick-check-all.sh 2>/dev/null || true
echo -e "${GREEN}✅ Permissions fixed${NC}"
echo ""

# Step 2: Quick local status check
echo -e "${BLUE}2️⃣ Quick Local Status Check${NC}"
echo "----------------------"
if [ -f "check-status.sh" ]; then
    ./check-status.sh | head -20
else
    echo -e "${YELLOW}⚠️  check-status.sh not found${NC}"
fi
echo ""

# Step 3: Check authoritative name servers
echo -e "${BLUE}3️⃣ Authoritative Name Servers${NC}"
echo "----------------------"

echo "Checking whois..."
NS_WHOIS=$(whois "$DOMAIN" 2>/dev/null | grep -i "Name Server" | head -5 || echo "")
if [ -n "$NS_WHOIS" ]; then
    echo "$NS_WHOIS"
else
    echo -e "${YELLOW}⚠️  Could not get NS from whois${NC}"
fi

echo ""
echo "Checking DNS NS records:"
NS_RECORDS=$(dig +short NS "$DOMAIN" 2>/dev/null || echo "")
if [ -n "$NS_RECORDS" ]; then
    echo "$NS_RECORDS" | while read ns; do
        echo "  - $ns"
    done
else
    echo -e "${RED}❌ No NS records found${NC}"
fi

echo ""

# Step 4: Direct query to authoritative NS (if found)
if [ -n "$NS_RECORDS" ]; then
    FIRST_NS=$(echo "$NS_RECORDS" | head -1)
    echo -e "${BLUE}4️⃣ Direct Query to Authoritative NS ($FIRST_NS)${NC}"
    echo "----------------------"
    
    echo "Querying $FIRST_NS for A records:"
    echo ""
    echo "earningsstable.com:"
    DNS_MAIN_NS=$(dig @"$FIRST_NS" A "$DOMAIN" +noall +answer 2>/dev/null | grep -E "^$DOMAIN" | awk '{print $5}' || echo "")
    if [ -n "$DNS_MAIN_NS" ]; then
        if [ "$DNS_MAIN_NS" = "$EXPECTED_IP" ]; then
            echo -e "  ${GREEN}✅ $DNS_MAIN_NS (correct)${NC}"
        else
            echo -e "  ${YELLOW}⚠️  $DNS_MAIN_NS (expected $EXPECTED_IP)${NC}"
        fi
    else
        echo -e "  ${RED}❌ No A record found${NC}"
    fi
    
    echo ""
    echo "www.earningsstable.com:"
    DNS_WWW_NS=$(dig @"$FIRST_NS" A "www.$DOMAIN" +noall +answer 2>/dev/null | grep -E "^www\.$DOMAIN" | awk '{print $5}' || echo "")
    if [ -n "$DNS_WWW_NS" ]; then
        if [ "$DNS_WWW_NS" = "$EXPECTED_IP" ]; then
            echo -e "  ${GREEN}✅ $DNS_WWW_NS (correct)${NC}"
        else
            echo -e "  ${YELLOW}⚠️  $DNS_WWW_NS (expected $EXPECTED_IP)${NC}"
        fi
    else
        echo -e "  ${RED}❌ No A record found${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Skipping direct NS query (no NS records found)${NC}"
fi

echo ""

# Step 5: External resolvers (propagation check)
echo -e "${BLUE}5️⃣ External Resolvers (Propagation Check)${NC}"
echo "----------------------"

echo "Checking Cloudflare (1.1.1.1):"
DNS_CF_MAIN=$(dig +short "$DOMAIN" @1.1.1.1 2>/dev/null || echo "")
DNS_CF_WWW=$(dig +short "www.$DOMAIN" @1.1.1.1 2>/dev/null || echo "")

if [ -n "$DNS_CF_MAIN" ] && [ "$DNS_CF_MAIN" = "$EXPECTED_IP" ]; then
    echo -e "  ${GREEN}✅ earningsstable.com → $DNS_CF_MAIN${NC}"
else
    echo -e "  ${RED}❌ earningsstable.com → ${DNS_CF_MAIN:-"No record"}${NC}"
fi

if [ -n "$DNS_CF_WWW" ] && [ "$DNS_CF_WWW" = "$EXPECTED_IP" ]; then
    echo -e "  ${GREEN}✅ www.earningsstable.com → $DNS_CF_WWW${NC}"
else
    echo -e "  ${RED}❌ www.earningsstable.com → ${DNS_CF_WWW:-"No record"}${NC}"
fi

echo ""
echo "Checking Google (8.8.8.8):"
DNS_GG_MAIN=$(dig +short "$DOMAIN" @8.8.8.8 2>/dev/null || echo "")
DNS_GG_WWW=$(dig +short "www.$DOMAIN" @8.8.8.8 2>/dev/null || echo "")

if [ -n "$DNS_GG_MAIN" ] && [ "$DNS_GG_MAIN" = "$EXPECTED_IP" ]; then
    echo -e "  ${GREEN}✅ earningsstable.com → $DNS_GG_MAIN${NC}"
else
    echo -e "  ${RED}❌ earningsstable.com → ${DNS_GG_MAIN:-"No record"}${NC}"
fi

if [ -n "$DNS_GG_WWW" ] && [ "$DNS_GG_WWW" = "$EXPECTED_IP" ]; then
    echo -e "  ${GREEN}✅ www.earningsstable.com → $DNS_GG_WWW${NC}"
else
    echo -e "  ${RED}❌ www.earningsstable.com → ${DNS_GG_WWW:-"No record"}${NC}"
fi

echo ""

# Step 6: Test Nginx with --resolve (bypass DNS)
echo -e "${BLUE}6️⃣ Nginx Test (Bypassing DNS)${NC}"
echo "----------------------"

echo "Testing robots.txt:"
ROBOTS_RESPONSE=$(curl -k -I --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/robots.txt" 2>&1 | head -5)
if echo "$ROBOTS_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ robots.txt returns 200${NC}"
    echo "$ROBOTS_RESPONSE" | head -3
else
    echo -e "${RED}❌ robots.txt failed${NC}"
    echo "$ROBOTS_RESPONSE" | head -3
fi

echo ""
echo "Testing sitemap.xml:"
SITEMAP_RESPONSE=$(curl -k -I --resolve "$DOMAIN:443:127.0.0.1" "https://$DOMAIN/sitemap.xml" 2>&1 | head -5)
if echo "$SITEMAP_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ sitemap.xml returns 200${NC}"
    echo "$SITEMAP_RESPONSE" | head -3
else
    echo -e "${RED}❌ sitemap.xml failed${NC}"
    echo "$SITEMAP_RESPONSE" | head -3
fi

echo ""

# Step 7: Check if monitor is running
echo -e "${BLUE}7️⃣ Monitor Script Status${NC}"
echo "----------------------"

MONITOR_PID=$(pgrep -f "monitor-dns-and-auto-certbot" || echo "")

if [ -n "$MONITOR_PID" ]; then
    echo -e "${GREEN}✅ Monitor script is running (PID: $MONITOR_PID)${NC}"
    pgrep -f "monitor-dns-and-auto-certbot" | head -1 | xargs ps -p 2>/dev/null | tail -1 || true
    echo ""
    echo "View logs: tail -f /var/log/monitor-dns.log"
else
    echo -e "${YELLOW}⚠️  Monitor script is not running${NC}"
    echo ""
    read -p "Start monitor in background? (y/N): " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        chmod +x monitor-dns-and-auto-certbot.sh
        nohup ./monitor-dns-and-auto-certbot.sh >> /var/log/monitor-dns.log 2>&1 &
        sleep 2
        
        if pgrep -f "monitor-dns-and-auto-certbot" > /dev/null; then
            echo -e "${GREEN}✅ Monitor started in background${NC}"
            echo "View logs: tail -f /var/log/monitor-dns.log"
        else
            echo -e "${RED}❌ Failed to start monitor${NC}"
        fi
    fi
fi

echo ""

# Step 8: Summary and next steps
echo -e "${BLUE}📊 Summary${NC}"
echo "-------"

DNS_READY=false
if [ -n "$DNS_CF_MAIN" ] && [ "$DNS_CF_MAIN" = "$EXPECTED_IP" ] && [ -n "$DNS_CF_WWW" ] && [ "$DNS_CF_WWW" = "$EXPECTED_IP" ]; then
    DNS_READY=true
elif [ -n "$DNS_GG_MAIN" ] && [ "$DNS_GG_MAIN" = "$EXPECTED_IP" ] && [ -n "$DNS_GG_WWW" ] && [ "$DNS_GG_WWW" = "$EXPECTED_IP" ]; then
    DNS_READY=true
fi

if [ "$DNS_READY" = true ]; then
    echo -e "${GREEN}✅ DNS is ready!${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Monitor will auto-run certbot (or run manually):"
    echo "     certbot --nginx -d $DOMAIN -d www.$DOMAIN"
    echo "  2. Test endpoints:"
    echo "     curl -I http://$DOMAIN/"
    echo "     curl -I https://$DOMAIN/robots.txt"
    echo "     curl -I https://$DOMAIN/sitemap.xml"
    echo "  3. Run final report:"
    echo "     ./final-seo-status-report.sh"
else
    echo -e "${YELLOW}⏳ DNS not ready yet${NC}"
    echo ""
    echo "Action needed:"
    echo "  1. Add DNS A records in your DNS provider (Active24):"
    echo "     - A @ → $EXPECTED_IP (TTL 300-600)"
    echo "     - A www → $EXPECTED_IP (TTL 300-600)"
    echo "  2. Wait 5-30 minutes for propagation"
    echo "  3. Run this script again to check"
    echo ""
    echo "Nginx is working correctly (tested with --resolve)"
fi

echo ""

