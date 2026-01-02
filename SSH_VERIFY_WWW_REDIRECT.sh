#!/bin/bash
# ✅ Verify www redirect works correctly

echo "✅ Verifying www.earningstable.com redirect..."
echo ""

echo "Test 1: HTTP redirect (should redirect to HTTPS non-www):"
curl -I -s http://www.earningstable.com/ | grep -i "location\|301" | head -2
echo ""

echo "Test 2: HTTPS redirect (should redirect to non-www):"
curl -I -k -s https://www.earningstable.com/ | grep -i "location\|301" | head -2
echo ""

echo "Test 3: Final destination (should be earningstable.com):"
FINAL=$(curl -L -k -s -o /dev/null -w "%{url_effective}\n" https://www.earningstable.com/)
echo "Final URL: $FINAL"
if echo "$FINAL" | grep -q "earningstable.com" && ! echo "$FINAL" | grep -q "www"; then
    echo "✅ Redirect works correctly - www redirects to non-www"
else
    echo "❌ Redirect may not be working correctly"
fi
echo ""

echo "✅ Verification complete!"
echo ""
echo "📝 Note: www.earningstable.com SHOULD redirect to earningstable.com"
echo "   This is correct behavior for SEO. Both URLs work, but non-www is canonical."
