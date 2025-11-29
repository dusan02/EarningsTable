#!/bin/bash
# 📊 Final SEO Status Report - Check everything after DNS setup

set -e

echo "📊 Final SEO Status Report"
echo "=========================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

STATUS_OK=0
STATUS_FAIL=0

# Function to check status
check_status() {
    local name=$1
    local command=$2
    local expected=$3
    
    echo -n "Checking $name... "
    result=$(eval "$command" 2>&1 || echo "FAIL")
    
    if echo "$result" | grep -q "$expected"; then
        echo -e "${GREEN}✅ OK${NC}"
        ((STATUS_OK++))
        return 0
    else
        echo -e "${RED}❌ FAIL${NC}"
        echo "  Output: $result" | head -2
        ((STATUS_FAIL++))
        return 1
    fi
}

# 1. DNS Records
echo -e "${BLUE}1. DNS Records${NC}"
echo "-------------------"

DNS_MAIN=$(dig +short earningsstable.com 2>/dev/null || echo "")
DNS_WWW=$(dig +short www.earningsstable.com 2>/dev/null || echo "")

if [ -n "$DNS_MAIN" ]; then
    echo -e "${GREEN}✅ earningsstable.com → $DNS_MAIN${NC}"
    ((STATUS_OK++))
else
    echo -e "${RED}❌ earningsstable.com → No DNS A record${NC}"
    echo "   Action: Add A record in DNS provider"
    ((STATUS_FAIL++))
fi

if [ -n "$DNS_WWW" ]; then
    echo -e "${GREEN}✅ www.earningsstable.com → $DNS_WWW${NC}"
    ((STATUS_OK++))
else
    echo -e "${RED}❌ www.earningsstable.com → No DNS A record${NC}"
    echo "   Action: Add A record in DNS provider"
    ((STATUS_FAIL++))
fi

echo ""

# 2. Nginx Configuration
echo -e "${BLUE}2. Nginx Configuration${NC}"
echo "----------------------"

if nginx -t 2>&1 | grep -q "successful"; then
    echo -e "${GREEN}✅ Nginx config valid${NC}"
    ((STATUS_OK++))
    
    CONFLICTS=$(nginx -t 2>&1 | grep -c "conflicting server name" 2>/dev/null || echo "0")
    CONFLICTS=${CONFLICTS//[^0-9]/}
    if [ -z "$CONFLICTS" ] || [ "$CONFLICTS" -eq 0 ]; then
        echo -e "${GREEN}✅ No conflicting server names${NC}"
        ((STATUS_OK++))
    else
        echo -e "${RED}❌ $CONFLICTS conflicting server names${NC}"
        ((STATUS_FAIL++))
    fi
else
    echo -e "${RED}❌ Nginx config invalid${NC}"
    nginx -t 2>&1 | head -3
    ((STATUS_FAIL++))
fi

echo ""

# 3. SSL Certificates
echo -e "${BLUE}3. SSL Certificates${NC}"
echo "-------------------"

SSL_CERT="/etc/letsencrypt/live/earningstable.com/fullchain.pem"
if [ -f "$SSL_CERT" ]; then
    echo -e "${GREEN}✅ SSL certificate exists${NC}"
    ((STATUS_OK++))
    
    # Check expiry
    EXPIRY=$(openssl x509 -enddate -noout -in "$SSL_CERT" 2>/dev/null | cut -d= -f2)
    echo "   Expires: $EXPIRY"
else
    echo -e "${YELLOW}⚠️  SSL certificate not found${NC}"
    echo "   Action: Run certbot after DNS is configured"
    ((STATUS_FAIL++))
fi

echo ""

# 4. HTTP Redirect (only if DNS works)
echo -e "${BLUE}4. HTTP to HTTPS Redirect${NC}"
echo "---------------------------"

if [ -n "$DNS_MAIN" ]; then
    HTTP_RESPONSE=$(curl -I http://earningsstable.com/ 2>&1 | head -5)
    if echo "$HTTP_RESPONSE" | grep -q "301\|Location: https://earningsstable.com"; then
        echo -e "${GREEN}✅ HTTP redirects to HTTPS${NC}"
        ((STATUS_OK++))
    else
        echo -e "${RED}❌ HTTP redirect not working${NC}"
        echo "$HTTP_RESPONSE" | head -3
        ((STATUS_FAIL++))
    fi
else
    echo -e "${YELLOW}⚠️  Skipped (DNS not configured)${NC}"
fi

echo ""

# 5. HTTPS Endpoints (only if DNS works)
echo -e "${BLUE}5. HTTPS Endpoints${NC}"
echo "-------------------"

if [ -n "$DNS_MAIN" ]; then
    # Homepage
    HOME_RESPONSE=$(curl -k -I https://earningsstable.com/ 2>&1 | head -5)
    if echo "$HOME_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
        echo -e "${GREEN}✅ Homepage (200)${NC}"
        ((STATUS_OK++))
    else
        echo -e "${RED}❌ Homepage failed${NC}"
        ((STATUS_FAIL++))
    fi
    
    # robots.txt
    ROBOTS_RESPONSE=$(curl -k -I https://earningsstable.com/robots.txt 2>&1 | head -5)
    if echo "$ROBOTS_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
        echo -e "${GREEN}✅ robots.txt (200)${NC}"
        ((STATUS_OK++))
    else
        echo -e "${RED}❌ robots.txt failed${NC}"
        ((STATUS_FAIL++))
    fi
    
    # sitemap.xml
    SITEMAP_RESPONSE=$(curl -k -I https://earningsstable.com/sitemap.xml 2>&1 | head -5)
    if echo "$SITEMAP_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
        echo -e "${GREEN}✅ sitemap.xml (200)${NC}"
        ((STATUS_OK++))
    else
        echo -e "${RED}❌ sitemap.xml failed${NC}"
        ((STATUS_FAIL++))
    fi
else
    echo -e "${YELLOW}⚠️  Skipped (DNS not configured)${NC}"
fi

echo ""

# 6. Local Test (bypasses DNS)
echo -e "${BLUE}6. Local Test (bypasses DNS)${NC}"
echo "---------------------------"

LOCAL_TEST=$(curl -k -I --resolve earningsstable.com:443:127.0.0.1 https://earningsstable.com/robots.txt 2>&1 | head -5)
if echo "$LOCAL_TEST" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ Local robots.txt works${NC}"
    ((STATUS_OK++))
else
    echo -e "${RED}❌ Local robots.txt failed${NC}"
    ((STATUS_FAIL++))
fi

echo ""

# Summary
echo -e "${BLUE}Summary${NC}"
echo "-------"
echo -e "✅ Passed: ${GREEN}$STATUS_OK${NC}"
echo -e "❌ Failed: ${RED}$STATUS_FAIL${NC}"
echo ""

if [ $STATUS_FAIL -eq 0 ]; then
    echo -e "${GREEN}🎉 All checks passed!${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Add to Google Search Console"
    echo "  2. Submit sitemap: https://earningsstable.com/sitemap.xml"
    echo "  3. Request indexing for homepage"
    exit 0
else
    echo -e "${YELLOW}⚠️  Some checks failed${NC}"
    echo ""
    echo "Action items:"
    
    if [ -z "$DNS_MAIN" ] || [ -z "$DNS_WWW" ]; then
        echo "  1. Add DNS A records:"
        echo "     - A earningsstable.com → <your-server-ip>"
        echo "     - A www.earningsstable.com → <your-server-ip>"
        echo "  2. Wait for DNS propagation (5-30 minutes)"
    fi
    
    if [ ! -f "$SSL_CERT" ] && [ -n "$DNS_MAIN" ]; then
        echo "  3. Run: certbot --nginx -d earningsstable.com -d www.earningsstable.com"
    fi
    
    exit 1
fi

