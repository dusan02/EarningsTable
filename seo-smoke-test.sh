#!/bin/bash
# 🔍 SEO Smoke Test Script
# Comprehensive SEO verification after deployment
# Usage: ./seo-smoke-test.sh [URL]
# Default URL: https://earningsstable.com

set -e

SITE_URL="${1:-https://earningsstable.com}"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔍 SEO Smoke Test for ${SITE_URL}${NC}"
echo "=========================================="
echo ""

ERRORS=0
WARNINGS=0

# Helper function to check HTTP status
check_status() {
    local URL=$1
    local EXPECTED=$2
    local NAME=$3
    
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$URL" 2>/dev/null || echo "000")
    
    if [ "$HTTP_CODE" = "$EXPECTED" ]; then
        echo -e "${GREEN}✅ ${NAME}: HTTP ${HTTP_CODE}${NC}"
        return 0
    else
        echo -e "${RED}❌ ${NAME}: HTTP ${HTTP_CODE} (expected ${EXPECTED})${NC}"
        ((ERRORS++))
        return 1
    fi
}

# Helper function to check header
check_header() {
    local URL=$1
    local HEADER=$2
    local EXPECTED=$3
    local NAME=$4
    
    HEADER_VALUE=$(curl -s -I "$URL" 2>/dev/null | grep -i "^${HEADER}:" | cut -d: -f2 | tr -d ' \r\n' || echo "")
    
    if echo "$HEADER_VALUE" | grep -qi "$EXPECTED"; then
        echo -e "${GREEN}✅ ${NAME}: ${HEADER_VALUE}${NC}"
        return 0
    else
        echo -e "${RED}❌ ${NAME}: Expected '${EXPECTED}' in ${HEADER}, got '${HEADER_VALUE}'${NC}"
        ((ERRORS++))
        return 1
    fi
}

# Helper function to check content
check_content() {
    local URL=$1
    local PATTERN=$2
    local NAME=$3
    local SHOULD_EXIST=${4:-true}
    
    CONTENT=$(curl -s "$URL" 2>/dev/null || echo "")
    
    if [ "$SHOULD_EXIST" = "true" ]; then
        if echo "$CONTENT" | grep -qi "$PATTERN"; then
            echo -e "${GREEN}✅ ${NAME}: Found '${PATTERN}'${NC}"
            return 0
        else
            echo -e "${RED}❌ ${NAME}: '${PATTERN}' not found${NC}"
            ((ERRORS++))
            return 1
        fi
    else
        if echo "$CONTENT" | grep -qi "$PATTERN"; then
            echo -e "${RED}❌ ${NAME}: '${PATTERN}' should NOT exist but was found${NC}"
            ((ERRORS++))
            return 1
        else
            echo -e "${GREEN}✅ ${NAME}: '${PATTERN}' correctly absent${NC}"
            return 0
        fi
    fi
}

echo -e "${BLUE}1. Homepage Status & Headers${NC}"
echo "-----------------------------------"
check_status "${SITE_URL}/" "200" "Homepage"
check_header "${SITE_URL}/" "X-Robots-Tag" "index, follow" "X-Robots-Tag header"
check_header "${SITE_URL}/" "Content-Type" "text/html" "Content-Type"
echo ""

echo -e "${BLUE}2. Robots.txt${NC}"
echo "----------------"
check_status "${SITE_URL}/robots.txt" "200" "robots.txt"
check_header "${SITE_URL}/robots.txt" "Content-Type" "text/plain" "robots.txt Content-Type"
check_content "${SITE_URL}/robots.txt" "User-agent" "robots.txt content"
check_content "${SITE_URL}/robots.txt" "sitemap.xml" "robots.txt sitemap reference"
echo ""

echo -e "${BLUE}3. Sitemap.xml${NC}"
echo "-----------------"
check_status "${SITE_URL}/sitemap.xml" "200" "sitemap.xml"
check_header "${SITE_URL}/sitemap.xml" "Content-Type" "application/xml" "sitemap.xml Content-Type"
check_content "${SITE_URL}/sitemap.xml" "urlset" "sitemap.xml structure"
check_content "${SITE_URL}/sitemap.xml" "earningsstable.com" "sitemap.xml URL"
echo ""

echo -e "${BLUE}4. Homepage Content Check${NC}"
echo "---------------------------"
check_content "${SITE_URL}/" "earningsstable.com" "Homepage contains new domain" true
check_content "${SITE_URL}/" "earnings-table.com" "Homepage does NOT contain old domain" false
check_content "${SITE_URL}/" "canonical" "Homepage has canonical link" true
check_content "${SITE_URL}/" "og:url" "Homepage has Open Graph URL" true
check_content "${SITE_URL}/" "twitter:url" "Homepage has Twitter URL" true
echo ""

echo -e "${BLUE}5. API Endpoints${NC}"
echo "----------------"
check_status "${SITE_URL}/api/health" "200" "Health endpoint"
check_status "${SITE_URL}/api/final-report" "200" "Final Report endpoint"
check_header "${SITE_URL}/api/final-report" "X-Robots-Tag" "index, follow" "API X-Robots-Tag"
echo ""

echo -e "${BLUE}6. Static Assets${NC}"
echo "-----------------"
check_status "${SITE_URL}/site.webmanifest" "200" "Web Manifest"
check_status "${SITE_URL}/favicon.svg" "200" "Favicon SVG"
echo ""

echo -e "${BLUE}7. Response Time Check${NC}"
echo "----------------------"
RESPONSE_TIME=$(curl -s -o /dev/null -w "%{time_total}" "${SITE_URL}/" 2>/dev/null || echo "999")
RESPONSE_TIME_MS=$(echo "$RESPONSE_TIME * 1000" | bc | cut -d. -f1)

if [ "$RESPONSE_TIME_MS" -lt 3000 ]; then
    echo -e "${GREEN}✅ Homepage response time: ${RESPONSE_TIME_MS}ms (< 3s)${NC}"
else
    echo -e "${YELLOW}⚠️  Homepage response time: ${RESPONSE_TIME_MS}ms (>= 3s)${NC}"
    ((WARNINGS++))
fi

API_TIME=$(curl -s -o /dev/null -w "%{time_total}" "${SITE_URL}/api/final-report" 2>/dev/null || echo "999")
API_TIME_MS=$(echo "$API_TIME * 1000" | bc | cut -d. -f1)

if [ "$API_TIME_MS" -lt 3000 ]; then
    echo -e "${GREEN}✅ API response time: ${API_TIME_MS}ms (< 3s)${NC}"
else
    echo -e "${YELLOW}⚠️  API response time: ${API_TIME_MS}ms (>= 3s)${NC}"
    ((WARNINGS++))
fi
echo ""

echo -e "${BLUE}8. SSL/HTTPS Check${NC}"
echo "------------------"
if echo "$SITE_URL" | grep -q "^https://"; then
    SSL_ISSUER=$(echo | openssl s_client -connect "${SITE_URL#https://}" -servername "${SITE_URL#https://}" 2>/dev/null | openssl x509 -noout -issuer 2>/dev/null | cut -d= -f2- || echo "Unknown")
    if [ "$SSL_ISSUER" != "Unknown" ]; then
        echo -e "${GREEN}✅ SSL Certificate valid (Issuer: ${SSL_ISSUER})${NC}"
    else
        echo -e "${YELLOW}⚠️  Could not verify SSL certificate${NC}"
        ((WARNINGS++))
    fi
else
    echo -e "${YELLOW}⚠️  Not using HTTPS${NC}"
    ((WARNINGS++))
fi
echo ""

# Summary
echo "=========================================="
if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✅ All SEO checks passed!${NC}"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠️  SEO checks passed with ${WARNINGS} warning(s)${NC}"
    exit 0
else
    echo -e "${RED}❌ SEO checks failed: ${ERRORS} error(s), ${WARNINGS} warning(s)${NC}"
    exit 1
fi

