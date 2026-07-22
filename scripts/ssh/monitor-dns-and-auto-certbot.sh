#!/bin/bash
# 🔄 Monitor DNS and Auto-Run Certbot When Ready

# Don't exit on error when running in background
# set -e

echo "🔄 DNS Monitor and Auto-Certbot"
echo "==============================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

EXPECTED_IP="89.185.250.213"
CHECK_INTERVAL=60  # Check every 60 seconds
MAX_CHECKS=30       # Max 30 minutes

echo "Monitoring DNS for:"
echo "  - earningsstable.com → $EXPECTED_IP"
echo "  - www.earningsstable.com → $EXPECTED_IP"
echo ""
echo "Checking every $CHECK_INTERVAL seconds (max $MAX_CHECKS checks = ~$((MAX_CHECKS * CHECK_INTERVAL / 60)) minutes)"
echo "Press Ctrl+C to cancel"
echo ""

CHECK_COUNT=0

while [ $CHECK_COUNT -lt $MAX_CHECKS ]; do
    ((CHECK_COUNT++))
    
    DNS_MAIN=$(dig +short earningsstable.com 2>/dev/null || echo "")
    DNS_WWW=$(dig +short www.earningsstable.com 2>/dev/null || echo "")
    
    TIMESTAMP=$(date +%H:%M:%S 2>/dev/null || echo "N/A")
    echo -n "[$CHECK_COUNT/$MAX_CHECKS] $TIMESTAMP - "
    
    if [ -n "$DNS_MAIN" ] && [ "$DNS_MAIN" = "$EXPECTED_IP" ] && [ -n "$DNS_WWW" ] && [ "$DNS_WWW" = "$EXPECTED_IP" ]; then
        echo -e "${GREEN}✅ DNS records found!${NC}"
        echo "   earningsstable.com → $DNS_MAIN"
        echo "   www.earningsstable.com → $DNS_WWW"
        echo ""
        break
    else
        if [ -z "$DNS_MAIN" ]; then
            echo -n "❌ earningsstable.com "
        elif [ "$DNS_MAIN" != "$EXPECTED_IP" ]; then
            echo -n "⚠️  earningsstable.com → $DNS_MAIN "
        else
            echo -n "✅ earningsstable.com "
        fi
        
        if [ -z "$DNS_WWW" ]; then
            echo "❌ www.earningsstable.com"
        elif [ "$DNS_WWW" != "$EXPECTED_IP" ]; then
            echo "⚠️  www.earningsstable.com → $DNS_WWW"
        else
            echo "✅ www.earningsstable.com"
        fi
        
        if [ $CHECK_COUNT -lt $MAX_CHECKS ]; then
            sleep $CHECK_INTERVAL
        fi
    fi
done

if [ -z "$DNS_MAIN" ] || [ "$DNS_MAIN" != "$EXPECTED_IP" ] || [ -z "$DNS_WWW" ] || [ "$DNS_WWW" != "$EXPECTED_IP" ]; then
    echo ""
    echo -e "${YELLOW}⚠️  DNS records not ready after $MAX_CHECKS checks${NC}"
    echo ""
    echo "Please check:"
    echo "  1. DNS A records are added in your DNS provider"
    echo "  2. DNS propagation may take longer (up to 48 hours)"
    echo "  3. Run this script again later"
    # Don't exit with error code when running in background
    exit 0
fi

# Wait a bit more for full propagation
echo "Waiting 60 seconds for full DNS propagation..."
sleep 60

# Test HTTP connectivity
echo ""
echo -e "${BLUE}Testing HTTP connectivity...${NC}"
HTTP_TEST=$(curl -I http://earningsstable.com/ 2>&1 | head -5)
if echo "$HTTP_TEST" | grep -q "HTTP"; then
    echo -e "${GREEN}✅ Server is reachable via HTTP${NC}"
else
    echo -e "${YELLOW}⚠️  HTTP test inconclusive (check firewall: ufw allow 80; ufw allow 443)${NC}"
fi

# Run certbot
echo ""
echo -e "${BLUE}Running certbot...${NC}"
echo ""

if certbot --nginx -d earningsstable.com -d www.earningsstable.com --non-interactive --agree-tos --email admin@earningsstable.com; then
    echo ""
    echo -e "${GREEN}✅ SSL certificates installed!${NC}"
    echo ""
    
    # Test HTTPS endpoints
    echo "Testing HTTPS endpoints..."
    echo ""
    
    # HTTP redirect
    echo "HTTP redirect:"
    HTTP_REDIRECT=$(curl -I http://earningsstable.com/ 2>&1 | head -5)
    if echo "$HTTP_REDIRECT" | grep -q "301\|Location: https://earningsstable.com"; then
        echo -e "${GREEN}✅ HTTP redirects to HTTPS${NC}"
    else
        echo -e "${YELLOW}⚠️  HTTP redirect test inconclusive${NC}"
    fi
    
    # Homepage
    echo ""
    echo "Homepage:"
    if curl -I https://earningsstable.com/ 2>&1 | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
        echo -e "${GREEN}✅ Homepage (200)${NC}"
    else
        echo -e "${YELLOW}⚠️  Homepage test inconclusive${NC}"
    fi
    
    # robots.txt
    echo ""
    echo "robots.txt:"
    if curl -I https://earningsstable.com/robots.txt 2>&1 | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
        echo -e "${GREEN}✅ robots.txt (200)${NC}"
    else
        echo -e "${YELLOW}⚠️  robots.txt test inconclusive${NC}"
    fi
    
    # sitemap.xml
    echo ""
    echo "sitemap.xml:"
    if curl -I https://earningsstable.com/sitemap.xml 2>&1 | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
        echo -e "${GREEN}✅ sitemap.xml (200)${NC}"
    else
        echo -e "${YELLOW}⚠️  sitemap.xml test inconclusive${NC}"
    fi
    
    echo ""
    echo -e "${GREEN}🎉 SSL setup complete!${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Run: ./final-seo-status-report.sh"
    echo "  2. Add to Google Search Console: https://search.google.com/search-console"
    echo "  3. Submit sitemap: https://earningsstable.com/sitemap.xml"
    echo "  4. Request indexing for homepage"
    echo ""
else
    echo ""
    echo -e "${RED}❌ Certbot failed${NC}"
    echo ""
    echo "Check:"
    echo "  1. DNS records are correct and propagated"
    echo "  2. Ports 80/443 are open: ufw allow 80; ufw allow 443"
    echo "  3. Nginx is running: systemctl status nginx"
    echo ""
    exit 1
fi

