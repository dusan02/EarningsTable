#!/bin/bash
# 🧪 Test site functionality

echo "🧪 Testing earningstable.com..."
echo ""

# Test HTTP redirect
echo "1️⃣  HTTP -> HTTPS redirect:"
HTTP_REDIRECT=$(curl -I -s http://earningstable.com/ | grep -i "location\|301")
if echo "$HTTP_REDIRECT" | grep -q "https://earningstable.com"; then
    echo "   ✅ HTTP redirects to HTTPS"
else
    echo "   ❌ HTTP redirect failed"
    echo "   $HTTP_REDIRECT"
fi
echo ""

# Test HTTPS homepage
echo "2️⃣  HTTPS homepage:"
HTTPS_HOME=$(curl -k -s -o /dev/null -w "%{http_code}" https://earningstable.com/)
if [ "$HTTPS_HOME" = "200" ]; then
    echo "   ✅ Homepage returns 200"
else
    echo "   ❌ Homepage returns $HTTPS_HOME"
fi
echo ""

# Test robots.txt
echo "3️⃣  robots.txt:"
ROBOTS=$(curl -k -s https://earningstable.com/robots.txt)
if echo "$ROBOTS" | grep -q "User-agent\|Sitemap.*earningstable.com"; then
    echo "   ✅ robots.txt works"
    echo "   Content: $ROBOTS" | head -3
else
    echo "   ❌ robots.txt failed"
    echo "   $ROBOTS"
fi
echo ""

# Test sitemap.xml
echo "4️⃣  sitemap.xml:"
SITEMAP=$(curl -k -s https://earningstable.com/sitemap.xml)
if echo "$SITEMAP" | grep -q "urlset\|earningstable.com"; then
    echo "   ✅ sitemap.xml works"
    echo "   Content: $SITEMAP" | head -5
else
    echo "   ❌ sitemap.xml failed"
    echo "   $SITEMAP"
fi
echo ""

# Test www redirect
echo "5️⃣  www redirect:"
WWW_REDIRECT=$(curl -I -k -s https://www.earningstable.com/ | grep -i "location\|301")
if echo "$WWW_REDIRECT" | grep -q "https://earningstable.com"; then
    echo "   ✅ www redirects to non-www"
else
    echo "   ❌ www redirect failed"
    echo "   $WWW_REDIRECT"
fi
echo ""

echo "✅ All tests complete!"
echo ""
echo "🌐 Site should be accessible at: https://earningstable.com"
