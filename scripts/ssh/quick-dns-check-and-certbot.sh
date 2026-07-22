#!/bin/bash
# 🔍 Quick DNS Check and Certbot Runner

set -e

echo "🔍 Quick DNS Check and Certbot"
echo "=============================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

EXPECTED_IP="89.185.250.213"

# Check DNS
echo -e "${BLUE}Checking DNS records...${NC}"
echo ""

DNS_MAIN=$(dig +short earningsstable.com 2>/dev/null || echo "")
DNS_WWW=$(dig +short www.earningsstable.com 2>/dev/null || echo "")

if [ -n "$DNS_MAIN" ] && [ "$DNS_MAIN" = "$EXPECTED_IP" ]; then
    echo -e "${GREEN}✅ earningsstable.com → $DNS_MAIN${NC}"
    DNS_MAIN_OK=true
else
    if [ -z "$DNS_MAIN" ]; then
        echo -e "${RED}❌ earningsstable.com → No DNS record${NC}"
    else
        echo -e "${RED}❌ earningsstable.com → $DNS_MAIN (wrong IP, expected $EXPECTED_IP)${NC}"
    fi
    DNS_MAIN_OK=false
fi

if [ -n "$DNS_WWW" ] && [ "$DNS_WWW" = "$EXPECTED_IP" ]; then
    echo -e "${GREEN}✅ www.earningsstable.com → $DNS_WWW${NC}"
    DNS_WWW_OK=true
else
    if [ -z "$DNS_WWW" ]; then
        echo -e "${RED}❌ www.earningsstable.com → No DNS record${NC}"
    else
        echo -e "${RED}❌ www.earningsstable.com → $DNS_WWW (wrong IP, expected $EXPECTED_IP)${NC}"
    fi
    DNS_WWW_OK=false
fi

echo ""

if [ "$DNS_MAIN_OK" = false ] || [ "$DNS_WWW_OK" = false ]; then
    echo -e "${YELLOW}⚠️  DNS records not ready yet${NC}"
    echo ""
    echo "Please add DNS A records:"
    echo "  - A @ (or earningsstable.com) → $EXPECTED_IP"
    echo "  - A www → $EXPECTED_IP"
    echo ""
    echo "Wait 5-30 minutes for propagation, then run this script again."
    exit 1
fi

# DNS is ready, test HTTP
echo -e "${BLUE}Testing HTTP connectivity...${NC}"
HTTP_TEST=$(curl -I http://earningsstable.com/ 2>&1 | head -5)
if echo "$HTTP_TEST" | grep -q "HTTP"; then
    echo -e "${GREEN}✅ Server is reachable via HTTP${NC}"
else
    echo -e "${YELLOW}⚠️  HTTP test inconclusive (check firewall: ufw allow 80; ufw allow 443)${NC}"
fi

echo ""

# Run certbot
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

