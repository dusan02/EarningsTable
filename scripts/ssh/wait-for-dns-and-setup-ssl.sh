#!/bin/bash
# 🔄 Wait for DNS propagation and automatically setup SSL

set -e

echo "🔄 Waiting for DNS propagation and SSL setup..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

DOMAIN_MAIN="earningsstable.com"
DOMAIN_WWW="www.earningsstable.com"
MAX_ATTEMPTS=60  # 60 attempts = 30 minutes (30 second intervals)
ATTEMPT=0

echo "Checking DNS for:"
echo "  - $DOMAIN_MAIN"
echo "  - $DOMAIN_WWW"
echo ""
echo "Will check every 30 seconds (max 30 minutes)..."
echo ""

while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    ((ATTEMPT++))
    
    DNS_MAIN=$(dig +short "$DOMAIN_MAIN" 2>/dev/null || echo "")
    DNS_WWW=$(dig +short "$DOMAIN_WWW" 2>/dev/null || echo "")
    
    echo -n "[$ATTEMPT/$MAX_ATTEMPTS] "
    
    if [ -n "$DNS_MAIN" ] && [ -n "$DNS_WWW" ]; then
        echo -e "${GREEN}✅ DNS records found!${NC}"
        echo "   $DOMAIN_MAIN → $DNS_MAIN"
        echo "   $DOMAIN_WWW → $DNS_WWW"
        echo ""
        break
    else
        if [ -z "$DNS_MAIN" ]; then
            echo -n "❌ $DOMAIN_MAIN "
        else
            echo -n "✅ $DOMAIN_MAIN "
        fi
        
        if [ -z "$DNS_WWW" ]; then
            echo "❌ $DOMAIN_WWW"
        else
            echo "✅ $DOMAIN_WWW"
        fi
        
        if [ $ATTEMPT -lt $MAX_ATTEMPTS ]; then
            sleep 30
        fi
    fi
done

if [ -z "$DNS_MAIN" ] || [ -z "$DNS_WWW" ]; then
    echo ""
    echo -e "${RED}❌ DNS records not found after $MAX_ATTEMPTS attempts${NC}"
    echo ""
    echo "Please check:"
    echo "  1. DNS A records are added in your DNS provider"
    echo "  2. DNS propagation may take longer (up to 48 hours)"
    echo "  3. Try running this script again later"
    exit 1
fi

# Wait a bit more for full propagation
echo "Waiting 60 seconds for full DNS propagation..."
sleep 60

# Test HTTP connectivity
echo ""
echo "Testing HTTP connectivity..."
HTTP_TEST=$(curl -I http://$DOMAIN_MAIN/ 2>&1 | head -3)
if echo "$HTTP_TEST" | grep -q "HTTP"; then
    echo -e "${GREEN}✅ Server is reachable via HTTP${NC}"
else
    echo -e "${YELLOW}⚠️  HTTP test inconclusive (may need firewall rules)${NC}"
fi

# Run certbot
echo ""
echo -e "${BLUE}Running certbot...${NC}"
echo ""

if certbot --nginx -d "$DOMAIN_MAIN" -d "$DOMAIN_WWW" --non-interactive --agree-tos --email admin@$DOMAIN_MAIN; then
    echo ""
    echo -e "${GREEN}✅ SSL certificates installed!${NC}"
    echo ""
    
    # Test HTTPS endpoints
    echo "Testing HTTPS endpoints..."
    echo ""
    
    # Homepage
    if curl -I https://$DOMAIN_MAIN/ 2>&1 | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
        echo -e "${GREEN}✅ Homepage (200)${NC}"
    else
        echo -e "${YELLOW}⚠️  Homepage test inconclusive${NC}"
    fi
    
    # robots.txt
    if curl -I https://$DOMAIN_MAIN/robots.txt 2>&1 | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
        echo -e "${GREEN}✅ robots.txt (200)${NC}"
    else
        echo -e "${YELLOW}⚠️  robots.txt test inconclusive${NC}"
    fi
    
    # sitemap.xml
    if curl -I https://$DOMAIN_MAIN/sitemap.xml 2>&1 | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
        echo -e "${GREEN}✅ sitemap.xml (200)${NC}"
    else
        echo -e "${YELLOW}⚠️  sitemap.xml test inconclusive${NC}"
    fi
    
    echo ""
    echo -e "${GREEN}🎉 SSL setup complete!${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Add to Google Search Console: https://search.google.com/search-console"
    echo "  2. Submit sitemap: https://$DOMAIN_MAIN/sitemap.xml"
    echo "  3. Request indexing for homepage"
    echo ""
    echo "Run final status report:"
    echo "  ./final-seo-status-report.sh"
    echo ""
else
    echo ""
    echo -e "${RED}❌ Certbot failed${NC}"
    echo ""
    echo "Check:"
    echo "  1. DNS records are correct"
    echo "  2. Ports 80/443 are open: ufw allow 80; ufw allow 443"
    echo "  3. Nginx is running: systemctl status nginx"
    echo ""
    exit 1
fi

