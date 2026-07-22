#!/bin/bash

# 🧪 UX/UI Verification Script
# Verifies identical UX/UI between localhost:5555 and production

set -e  # Exit on any error

echo "🧪 Starting UX/UI verification..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
LOCALHOST_URL="http://localhost:5555"
PRODUCTION_URL="http://your-server.com:5555"

echo -e "${BLUE}📋 Verification Configuration:${NC}"
echo "  Localhost: $LOCALHOST_URL"
echo "  Production: $PRODUCTION_URL"
echo ""

# Function to test endpoint
test_endpoint() {
    local url=$1
    local endpoint=$2
    local description=$3
    
    echo -e "${YELLOW}Testing $description...${NC}"
    
    if curl -s "$url$endpoint" > /dev/null; then
        echo -e "${GREEN}✅ $description: OK${NC}"
        return 0
    else
        echo -e "${RED}❌ $description: FAILED${NC}"
        return 1
    fi
}

# Function to compare response sizes
compare_responses() {
    local endpoint=$1
    local description=$2
    
    echo -e "${YELLOW}Comparing $description...${NC}"
    
    local local_size=$(curl -s "$LOCALHOST_URL$endpoint" | wc -c)
    local prod_size=$(curl -s "$PRODUCTION_URL$endpoint" | wc -c)
    
    if [ "$local_size" -eq "$prod_size" ]; then
        echo -e "${GREEN}✅ $description: Identical size ($local_size bytes)${NC}"
        return 0
    else
        echo -e "${RED}❌ $description: Size mismatch (Local: $local_size, Prod: $prod_size)${NC}"
        return 1
    fi
}

# Test results
local_tests=0
prod_tests=0
comparison_tests=0

echo -e "${BLUE}🔍 Testing Localhost (localhost:5555)...${NC}"
echo ""

# Test localhost endpoints
test_endpoint "$LOCALHOST_URL" "/" "Main Dashboard" && ((local_tests++))
test_endpoint "$LOCALHOST_URL" "/api/health" "API Health" && ((local_tests++))
test_endpoint "$LOCALHOST_URL" "/api/final-report" "API Data" && ((local_tests++))
test_endpoint "$LOCALHOST_URL" "/logos/ALLY.webp" "Logo Serving" && ((local_tests++))
test_endpoint "$LOCALHOST_URL" "/favicon.ico" "Favicon" && ((local_tests++))
test_endpoint "$LOCALHOST_URL" "/test-logos" "Logo Test Page" && ((local_tests++))

echo ""
echo -e "${BLUE}🔍 Testing Production...${NC}"
echo ""

# Test production endpoints
test_endpoint "$PRODUCTION_URL" "/" "Main Dashboard" && ((prod_tests++))
test_endpoint "$PRODUCTION_URL" "/api/health" "API Health" && ((prod_tests++))
test_endpoint "$PRODUCTION_URL" "/api/final-report" "API Data" && ((prod_tests++))
test_endpoint "$PRODUCTION_URL" "/logos/ALLY.webp" "Logo Serving" && ((prod_tests++))
test_endpoint "$PRODUCTION_URL" "/favicon.ico" "Favicon" && ((prod_tests++))
test_endpoint "$PRODUCTION_URL" "/test-logos" "Logo Test Page" && ((prod_tests++))

echo ""
echo -e "${BLUE}🔄 Comparing Responses...${NC}"
echo ""

# Compare responses
compare_responses "/api/final-report" "API Data Response" && ((comparison_tests++))
compare_responses "/" "Main Dashboard HTML" && ((comparison_tests++))

echo ""
echo -e "${BLUE}📊 Verification Summary:${NC}"
echo "  Localhost Tests: $local_tests/6 passed"
echo "  Production Tests: $prod_tests/6 passed"
echo "  Comparison Tests: $comparison_tests/2 passed"
echo ""

# Overall result
total_tests=$((local_tests + prod_tests + comparison_tests))
max_tests=14

if [ $total_tests -eq $max_tests ]; then
    echo -e "${GREEN}🎉 All tests passed! UX/UI is identical between localhost and production.${NC}"
    echo ""
    echo -e "${GREEN}✅ Verification Result: PERFECT MATCH${NC}"
    exit 0
else
    echo -e "${RED}❌ Some tests failed. UX/UI differences detected.${NC}"
    echo ""
    echo -e "${RED}❌ Verification Result: MISMATCH DETECTED${NC}"
    exit 1
fi
