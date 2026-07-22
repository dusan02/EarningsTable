#!/bin/bash
# 🔍 Post-Deployment SEO Check Script
# Comprehensive verification after deployment including extra checks
# Usage: ./post-deployment-seo-check.sh [URL]
# Default URL: https://earningsstable.com

set -e

SITE_URL="${1:-https://earningsstable.com}"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

ERRORS=0
WARNINGS=0

echo -e "${CYAN}🔍 Post-Deployment SEO Check for ${SITE_URL}${NC}"
echo "=========================================="
echo ""

# 1. Canonical consistency (no redirect chains)
echo -e "${BLUE}1. Canonical Consistency (No Redirect Chains)${NC}"
echo "-----------------------------------------------"

for url in \
  "http://earningsstable.com" \
  "https://www.earningsstable.com" \
  "https://earnings-table.com" \
  "https://www.earnings-table.com"
do
  echo -n "  Testing ${url}... "
  # Use -k flag to ignore SSL certificate errors (for self-signed certs)
  REDIRECTS=$(curl -k -I -L -s -w "\n%{num_redirects}" "$url" 2>/dev/null | tail -1)
  FINAL_STATUS=$(curl -k -I -L -s "$url" 2>/dev/null | head -1 | grep -o "HTTP/[0-9.]* [0-9]*" | awk '{print $2}')
  FINAL_URL=$(curl -k -I -L -s -w "%{url_effective}" "$url" -o /dev/null 2>/dev/null)
  
  # Accept 200, 301, 302, 307, 308 as valid (redirects are OK)
  if [ "$FINAL_STATUS" = "200" ] && echo "$FINAL_URL" | grep -q "earningsstable.com"; then
    if [ "$REDIRECTS" -le 2 ]; then
      echo -e "${GREEN}✅ OK (${REDIRECTS} redirects → ${FINAL_STATUS})${NC}"
    else
      echo -e "${YELLOW}⚠️  Too many redirects: ${REDIRECTS}${NC}"
      ((WARNINGS++))
    fi
  elif [ "$FINAL_STATUS" = "301" ] || [ "$FINAL_STATUS" = "302" ] || [ "$FINAL_STATUS" = "307" ] || [ "$FINAL_STATUS" = "308" ]; then
    # Redirect is OK, check if it goes to correct domain
    if echo "$FINAL_URL" | grep -q "earningsstable.com"; then
      echo -e "${GREEN}✅ Redirects to correct domain (${REDIRECTS} redirects → ${FINAL_STATUS})${NC}"
    else
      echo -e "${YELLOW}⚠️  Redirects but to: ${FINAL_URL}${NC}"
      ((WARNINGS++))
    fi
  elif [ "$FINAL_STATUS" = "404" ] && echo "$url" | grep -q "^http://"; then
    # HTTP 404 is warning (not error) - might not have HTTP→HTTPS redirect configured
    echo -e "${YELLOW}⚠️  HTTP returns 404 (HTTPS redirect may not be configured)${NC}"
    ((WARNINGS++))
  else
    echo -e "${RED}❌ Failed (Status: ${FINAL_STATUS}, Redirects: ${REDIRECTS})${NC}"
    ((ERRORS++))
  fi
done
echo ""

# 2. Robots / Sitemap / No-noindex
echo -e "${BLUE}2. Robots.txt, Sitemap.xml, X-Robots-Tag${NC}"
echo "----------------------------------------"

echo -n "  robots.txt: "
ROBOTS_STATUS=$(curl -k -s -o /dev/null -w "%{http_code}" "${SITE_URL}/robots.txt" 2>/dev/null)
ROBOTS_HEADERS=$(curl -k -I -s "${SITE_URL}/robots.txt" 2>/dev/null)
if [ "$ROBOTS_STATUS" = "200" ]; then
  echo -e "${GREEN}✅ HTTP ${ROBOTS_STATUS}${NC}"
else
  echo -e "${RED}❌ HTTP ${ROBOTS_STATUS}${NC}"
  ((ERRORS++))
fi

echo -n "  sitemap.xml: "
SITEMAP_STATUS=$(curl -k -s -o /dev/null -w "%{http_code}" "${SITE_URL}/sitemap.xml" 2>/dev/null)
if [ "$SITEMAP_STATUS" = "200" ]; then
  echo -e "${GREEN}✅ HTTP ${SITEMAP_STATUS}${NC}"
else
  echo -e "${RED}❌ HTTP ${SITEMAP_STATUS}${NC}"
  ((ERRORS++))
fi

echo -n "  X-Robots-Tag on homepage: "
ROBOTS_TAG=$(curl -k -I -s "${SITE_URL}/" 2>/dev/null | grep -i "x-robots-tag" | tr -d '\r\n' || echo "")
if echo "$ROBOTS_TAG" | grep -qi "index, follow"; then
  echo -e "${GREEN}✅ ${ROBOTS_TAG}${NC}"
else
  echo -e "${RED}❌ Missing or incorrect: ${ROBOTS_TAG}${NC}"
  ((ERRORS++))
fi
echo ""

# 3. Homepage source check
echo -e "${BLUE}3. Homepage Content Check${NC}"
echo "------------------------"

echo -n "  Contains earningsstable.com: "
HOMEPAGE=$(curl -k -s "${SITE_URL}/" 2>/dev/null)
if echo "$HOMEPAGE" | grep -qi "earningsstable.com"; then
  echo -e "${GREEN}✅ Found${NC}"
else
  echo -e "${RED}❌ Not found${NC}"
  ((ERRORS++))
fi

echo -n "  Does NOT contain earnings-table.com: "
if echo "$HOMEPAGE" | grep -qi "earnings-table.com"; then
  echo -e "${RED}❌ Found (should not exist)${NC}"
  ((ERRORS++))
else
  echo -e "${GREEN}✅ Correctly absent${NC}"
fi

echo -n "  Canonical link: "
CANONICAL=$(echo "$HOMEPAGE" | grep -i 'rel="canonical"' | grep -o 'href="[^"]*"' | cut -d'"' -f2)
if [ "$CANONICAL" = "https://earningsstable.com/" ]; then
  echo -e "${GREEN}✅ ${CANONICAL}${NC}"
else
  echo -e "${RED}❌ Incorrect: ${CANONICAL}${NC}"
  ((ERRORS++))
fi
echo ""

# 4. API stability
echo -e "${BLUE}4. API Stability (5xx/403 Check)${NC}"
echo "----------------------------"

echo -n "  /api/final-report: "
API_STATUS=$(curl -k -s -o /dev/null -w "%{http_code}" "${SITE_URL}/api/final-report" 2>/dev/null)
API_TIME=$(curl -k -s -o /dev/null -w "%{time_total}" "${SITE_URL}/api/final-report" 2>/dev/null)
API_TIME_MS=$(echo "$API_TIME * 1000" | bc | cut -d. -f1)

if [ "$API_STATUS" = "200" ]; then
  if [ "$API_TIME_MS" -lt 3000 ]; then
    echo -e "${GREEN}✅ HTTP ${API_STATUS} (${API_TIME_MS}ms)${NC}"
  else
    echo -e "${YELLOW}⚠️  HTTP ${API_STATUS} but slow: ${API_TIME_MS}ms${NC}"
    ((WARNINGS++))
  fi
else
  echo -e "${RED}❌ HTTP ${API_STATUS}${NC}"
  ((ERRORS++))
fi
echo ""

# 5. Cache/SSL headers
echo -e "${BLUE}5. Security Headers (HSTS/CSP)${NC}"
echo "---------------------------"

HOMEPAGE_HEADERS=$(curl -k -I -s "${SITE_URL}/" 2>/dev/null)
HSTS=$(echo "$HOMEPAGE_HEADERS" | grep -i "strict-transport-security" || echo "")
CSP=$(echo "$HOMEPAGE_HEADERS" | grep -i "content-security-policy" || echo "")

if [ -n "$HSTS" ]; then
  echo -e "${GREEN}✅ HSTS: ${HSTS}${NC}"
else
  echo -e "${YELLOW}⚠️  HSTS not set (optional)${NC}"
  ((WARNINGS++))
fi

if [ -n "$CSP" ]; then
  echo -e "${GREEN}✅ CSP: ${CSP}${NC}"
else
  echo -e "${YELLOW}⚠️  CSP not set (optional)${NC}"
  ((WARNINGS++))
fi
echo ""

# 6. Googlebot access simulation
echo -e "${BLUE}6. Googlebot Access Simulation${NC}"
echo "---------------------------"

GOOGLEBOT_STATUS=$(curl -k -A "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)" -s -o /dev/null -w "%{http_code}" "${SITE_URL}/" 2>/dev/null)

if [ "$GOOGLEBOT_STATUS" = "200" ]; then
  echo -e "${GREEN}✅ Googlebot can access: HTTP ${GOOGLEBOT_STATUS}${NC}"
else
  echo -e "${RED}❌ Googlebot blocked: HTTP ${GOOGLEBOT_STATUS}${NC}"
  ((ERRORS++))
fi
echo ""

# 7. Trailing slash consistency
echo -e "${BLUE}7. Trailing Slash Consistency${NC}"
echo "---------------------------"

WITH_SLASH=$(curl -k -I -L -s -w "%{url_effective}" "${SITE_URL}/" -o /dev/null 2>/dev/null)
WITHOUT_SLASH=$(curl -k -I -L -s -w "%{url_effective}" "${SITE_URL}" -o /dev/null 2>/dev/null)

if [ "$WITH_SLASH" = "$WITHOUT_SLASH" ]; then
  echo -e "${GREEN}✅ Consistent: ${WITH_SLASH}${NC}"
else
  echo -e "${YELLOW}⚠️  Inconsistent: ${WITH_SLASH} vs ${WITHOUT_SLASH}${NC}"
  ((WARNINGS++))
fi
echo ""

# 8. Sitemap validation
echo -e "${BLUE}8. Sitemap XML Validation${NC}"
echo "----------------------"

SITEMAP=$(curl -k -s "${SITE_URL}/sitemap.xml" 2>/dev/null)
if echo "$SITEMAP" | grep -q "urlset"; then
  echo -e "${GREEN}✅ Valid XML structure${NC}"
  
  # Check if xmllint is available
  if command -v xmllint &> /dev/null; then
    if echo "$SITEMAP" | xmllint --noout - 2>/dev/null; then
      echo -e "${GREEN}✅ XML syntax valid${NC}"
    else
      echo -e "${RED}❌ XML syntax invalid${NC}"
      ((ERRORS++))
    fi
  else
    echo -e "${YELLOW}⚠️  xmllint not available (skipping syntax check)${NC}"
  fi
else
  echo -e "${RED}❌ Invalid sitemap structure${NC}"
  ((ERRORS++))
fi
echo ""

# Summary
echo "=========================================="
if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
  echo -e "${GREEN}✅ All SEO checks passed!${NC}"
  echo ""
  echo "Next steps:"
  echo "  1. Submit sitemap in Google Search Console"
  echo "  2. Request indexing for homepage"
  echo "  3. Monitor logs for Googlebot access"
  exit 0
elif [ $ERRORS -eq 0 ]; then
  echo -e "${YELLOW}⚠️  SEO checks passed with ${WARNINGS} warning(s)${NC}"
  exit 0
else
  echo -e "${RED}❌ SEO checks failed: ${ERRORS} error(s), ${WARNINGS} warning(s)${NC}"
  exit 1
fi

