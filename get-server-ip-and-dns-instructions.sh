#!/bin/bash
# 🔍 Get Server IP and Show DNS Instructions

set -e

echo "🔍 Server IP and DNS Setup Instructions"
echo "========================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Get public IP
echo -e "${BLUE}Step 1: Getting your server's public IP...${NC}"
echo ""

PUBLIC_IP=$(curl -4 -s ifconfig.me 2>/dev/null || curl -4 -s icanhazip.com 2>/dev/null || dig +short myip.opendns.com @resolver1.opendns.com 2>/dev/null || echo "")

if [ -z "$PUBLIC_IP" ]; then
    echo -e "${RED}❌ Could not determine public IP${NC}"
    echo "Please check manually:"
    echo "  curl -4 ifconfig.me"
    exit 1
fi

echo -e "${GREEN}✅ Your server's public IP: ${PUBLIC_IP}${NC}"
echo ""

# Get IPv6 if available
echo -e "${BLUE}Step 2: Checking for IPv6...${NC}"
IPV6=$(curl -6 -s ifconfig.me 2>/dev/null || echo "")
if [ -n "$IPV6" ]; then
    echo -e "${GREEN}✅ IPv6 found: ${IPV6}${NC}"
    echo ""
    HAS_IPV6=true
else
    echo -e "${YELLOW}⚠️  No IPv6 detected (optional)${NC}"
    echo ""
    HAS_IPV6=false
fi

# DNS Instructions
echo -e "${BLUE}Step 3: DNS Configuration Instructions${NC}"
echo "=============================================="
echo ""
echo "Go to your DNS provider (registrar/Cloudflare/etc.) and add:"
echo ""
echo -e "${GREEN}Type: A${NC}"
echo "Name: @ (or earningsstable.com)"
echo "Value: $PUBLIC_IP"
echo "TTL: 300 (5 minutes)"
echo ""
echo -e "${GREEN}Type: A${NC}"
echo "Name: www"
echo "Value: $PUBLIC_IP"
echo "TTL: 300 (5 minutes)"
echo ""

if [ "$HAS_IPV6" = true ]; then
    echo -e "${YELLOW}(Optional) Type: AAAA${NC}"
    echo "Name: @ (or earningsstable.com)"
    echo "Value: $IPV6"
    echo "TTL: 300"
    echo ""
    echo -e "${YELLOW}(Optional) Type: AAAA${NC}"
    echo "Name: www"
    echo "Value: $IPV6"
    echo "TTL: 300"
    echo ""
fi

echo "⚠️  IMPORTANT: Domain name is ${GREEN}earningsstable.com${NC} (with TWO 's' letters)"
echo "   NOT: earningstable.com (one 's')"
echo "   NOT: earnings-table.com (with hyphen)"
echo ""

# Wait for DNS
echo -e "${BLUE}Step 4: Waiting for DNS propagation...${NC}"
echo "======================================"
echo ""
echo "After adding DNS records, this script will check every 30 seconds"
echo "Press Ctrl+C to cancel and run manually later"
echo ""

read -p "Press Enter when you've added DNS records, or Ctrl+C to exit..."
echo ""

MAX_ATTEMPTS=60
ATTEMPT=0

while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    ((ATTEMPT++))
    
    DNS_MAIN=$(dig +short earningsstable.com 2>/dev/null || echo "")
    DNS_WWW=$(dig +short www.earningsstable.com 2>/dev/null || echo "")
    
    echo -n "[$ATTEMPT/$MAX_ATTEMPTS] "
    
    if [ -n "$DNS_MAIN" ] && [ "$DNS_MAIN" = "$PUBLIC_IP" ] && [ -n "$DNS_WWW" ] && [ "$DNS_WWW" = "$PUBLIC_IP" ]; then
        echo -e "${GREEN}✅ DNS records found and pointing to correct IP!${NC}"
        echo "   earningsstable.com → $DNS_MAIN"
        echo "   www.earningsstable.com → $DNS_WWW"
        echo ""
        break
    else
        if [ -z "$DNS_MAIN" ]; then
            echo -n "❌ earningsstable.com "
        elif [ "$DNS_MAIN" != "$PUBLIC_IP" ]; then
            echo -n "⚠️  earningsstable.com → $DNS_MAIN (wrong IP) "
        else
            echo -n "✅ earningsstable.com "
        fi
        
        if [ -z "$DNS_WWW" ]; then
            echo "❌ www.earningsstable.com"
        elif [ "$DNS_WWW" != "$PUBLIC_IP" ]; then
            echo "⚠️  www.earningsstable.com → $DNS_WWW (wrong IP)"
        else
            echo "✅ www.earningsstable.com"
        fi
        
        if [ $ATTEMPT -lt $MAX_ATTEMPTS ]; then
            sleep 30
        fi
    fi
done

if [ -z "$DNS_MAIN" ] || [ "$DNS_MAIN" != "$PUBLIC_IP" ] || [ -z "$DNS_WWW" ] || [ "$DNS_WWW" != "$PUBLIC_IP" ]; then
    echo ""
    echo -e "${RED}❌ DNS records not found or pointing to wrong IP after $MAX_ATTEMPTS attempts${NC}"
    echo ""
    echo "Please check:"
    echo "  1. DNS records are correctly added"
    echo "  2. DNS propagation may take longer (up to 48 hours)"
    echo "  3. Run this script again later"
    exit 1
fi

# Wait a bit more for full propagation
echo "Waiting 60 seconds for full DNS propagation..."
sleep 60

# Test HTTP connectivity
echo ""
echo "Testing HTTP connectivity..."
HTTP_TEST=$(curl -I http://earningsstable.com/ 2>&1 | head -3)
if echo "$HTTP_TEST" | grep -q "HTTP"; then
    echo -e "${GREEN}✅ Server is reachable via HTTP${NC}"
else
    echo -e "${YELLOW}⚠️  HTTP test inconclusive (check firewall: ufw allow 80; ufw allow 443)${NC}"
fi

# Run certbot
echo ""
echo -e "${BLUE}Step 5: Setting up SSL certificates...${NC}"
echo "======================================"
echo ""

read -p "Run certbot now? (y/N): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    if certbot --nginx -d earningsstable.com -d www.earningsstable.com --non-interactive --agree-tos --email admin@earningsstable.com; then
        echo ""
        echo -e "${GREEN}✅ SSL certificates installed!${NC}"
        echo ""
        
        # Test HTTPS endpoints
        echo "Testing HTTPS endpoints..."
        echo ""
        
        # Homepage
        if curl -I https://earningsstable.com/ 2>&1 | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
            echo -e "${GREEN}✅ Homepage (200)${NC}"
        else
            echo -e "${YELLOW}⚠️  Homepage test inconclusive${NC}"
        fi
        
        # robots.txt
        if curl -I https://earningsstable.com/robots.txt 2>&1 | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
            echo -e "${GREEN}✅ robots.txt (200)${NC}"
        else
            echo -e "${YELLOW}⚠️  robots.txt test inconclusive${NC}"
        fi
        
        # sitemap.xml
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
else
    echo "Skipping certbot. Run manually when ready:"
    echo "  certbot --nginx -d earningsstable.com -d www.earningsstable.com"
    echo ""
fi

