#!/bin/bash
# 🧪 Detailed test for www.earningstable.com

echo "🧪 Detailed test for www.earningstable.com..."
echo ""

# Test 1: Check if www redirects correctly
echo "1️⃣  Testing redirect chain:"
echo "---"
echo "HTTP -> HTTPS -> non-www:"
REDIRECT_CHAIN=$(curl -I -L -s http://www.earningstable.com/ | grep -i "location\|http/" | head -3)
echo "$REDIRECT_CHAIN"
echo ""

# Test 2: Check final destination
echo "2️⃣  Final destination after redirects:"
FINAL_URL=$(curl -I -L -s http://www.earningstable.com/ 2>&1 | grep -i "location\|http/" | tail -1)
echo "Final URL: $FINAL_URL"
echo ""

# Test 3: Check SSL certificate validity for www
echo "3️⃣  SSL Certificate check for www.earningstable.com:"
echo "---"
if command -v openssl &> /dev/null; then
    echo | openssl s_client -servername www.earningstable.com -connect www.earningstable.com:443 2>/dev/null | openssl x509 -noout -subject -dates 2>/dev/null || echo "  ⚠️  Cannot check certificate"
else
    echo "  ⚠️  openssl not available"
fi
echo ""

# Test 4: Check if browser would see SSL error
echo "4️⃣  SSL Certificate validation (as browser sees it):"
SSL_CHECK=$(curl -v https://www.earningstable.com/ 2>&1 | grep -i "SSL\|certificate\|verify\|error" | head -5)
if echo "$SSL_CHECK" | grep -qi "error\|fail\|unable"; then
    echo "  ❌ SSL errors detected:"
    echo "$SSL_CHECK"
else
    echo "  ✅ No SSL errors detected"
fi
echo ""

# Test 5: Check actual response
echo "5️⃣  Actual response from www.earningstable.com:"
RESPONSE=$(curl -L -k -s -o /dev/null -w "HTTP Status: %{http_code}\nFinal URL: %{url_effective}\nRedirects: %{num_redirects}\n" https://www.earningstable.com/)
echo "$RESPONSE"
echo ""

# Test 6: Check if www works without redirect (should not)
echo "6️⃣  Testing if www serves content directly (should redirect):"
WWW_CONTENT=$(curl -L -k -s https://www.earningstable.com/ | head -20)
if echo "$WWW_CONTENT" | grep -qi "earningstable.com\|Earnings Table"; then
    if echo "$WWW_CONTENT" | grep -qi "301\|302\|redirect"; then
        echo "  ✅ Correctly redirects (contains redirect info)"
    else
        echo "  ⚠️  Serves content directly (should redirect)"
    fi
else
    echo "  ✅ Redirects correctly (no content served)"
fi
echo ""

echo "✅ Detailed test complete!"
echo ""
echo "📝 Summary:"
echo "   - www.earningstable.com should redirect to earningstable.com"
echo "   - If you see SSL errors in browser, certificate may need renewal"
echo "   - If redirect doesn't work, check Nginx config"
