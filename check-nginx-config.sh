#!/bin/bash
# 🔍 Check Nginx config for robots.txt and sitemap.xml locations

echo "🔍 Checking Nginx configuration..."
echo ""

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"

if [ ! -f "$NGINX_CONFIG" ]; then
    echo "❌ Config not found: $NGINX_CONFIG"
    exit 1
fi

echo "=== Checking for robots.txt location ==="
grep -A 6 "location = /robots.txt" "$NGINX_CONFIG" || echo "❌ robots.txt location NOT FOUND"

echo ""
echo "=== Checking for sitemap.xml location ==="
grep -A 6 "location = /sitemap.xml" "$NGINX_CONFIG" || echo "❌ sitemap.xml location NOT FOUND"

echo ""
echo "=== HTTPS server block structure ==="
# Show lines around listen 443
grep -n "listen 443" "$NGINX_CONFIG" | head -1
echo ""

# Show location blocks in HTTPS server
echo "Location blocks in HTTPS server:"
awk '/listen 443/,/^}/ { if (/location/) print NR": "$0 }' "$NGINX_CONFIG"

echo ""
echo "=== Full HTTPS server block ==="
# Extract HTTPS server block
awk '/server {/,/^}/ { if (/listen 443/ || p) { p=1; print } if (/^}/ && p) { p=0; print; exit } }' "$NGINX_CONFIG" | head -50

echo ""
echo "=== Testing file existence ==="
if [ -f "/var/www/earnings-table/public/robots.txt" ]; then
    echo "✅ /var/www/earnings-table/public/robots.txt exists"
    ls -la /var/www/earnings-table/public/robots.txt
else
    echo "❌ /var/www/earnings-table/public/robots.txt NOT FOUND"
fi

if [ -f "/var/www/earnings-table/public/sitemap.xml" ]; then
    echo "✅ /var/www/earnings-table/public/sitemap.xml exists"
    ls -la /var/www/earnings-table/public/sitemap.xml
else
    echo "❌ /var/www/earnings-table/public/sitemap.xml NOT FOUND"
fi

echo ""
echo "=== Testing direct file access ==="
cat /var/www/earnings-table/public/robots.txt 2>/dev/null | head -3 || echo "Cannot read robots.txt"
echo ""
cat /var/www/earnings-table/public/sitemap.xml 2>/dev/null | head -3 || echo "Cannot read sitemap.xml"

