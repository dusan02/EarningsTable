#!/bin/bash
# 🔧 Fix permissions and Nginx location blocks

set -e

echo "🔧 Fixing Nginx permissions and location blocks..."

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
PUBLIC_DIR="/var/www/earnings-table/public"

# 1. Fix permissions
echo "1. Fixing file permissions..."
chmod 644 "$PUBLIC_DIR/robots.txt" "$PUBLIC_DIR/sitemap.xml"
chown www-data:www-data "$PUBLIC_DIR/robots.txt" "$PUBLIC_DIR/sitemap.xml" 2>/dev/null || chown nginx:nginx "$PUBLIC_DIR/robots.txt" "$PUBLIC_DIR/sitemap.xml" 2>/dev/null || echo "⚠️  Could not change ownership, but 644 should work"

# Also fix directory permissions
chmod 755 "$PUBLIC_DIR"
echo "✅ Permissions fixed"

# 2. Test readability
echo "2. Testing readability..."
if sudo -u www-data cat "$PUBLIC_DIR/robots.txt" > /dev/null 2>&1 || sudo -u nginx cat "$PUBLIC_DIR/robots.txt" > /dev/null 2>&1; then
    echo "✅ Files are readable by Nginx user"
else
    # Make files world-readable
    chmod 644 "$PUBLIC_DIR/robots.txt" "$PUBLIC_DIR/sitemap.xml"
    echo "✅ Made files world-readable (644)"
fi

# 3. Check if location blocks are in correct server block
echo "3. Checking server block structure..."
if grep -A 30 "server_name.*earningstable\.com[^w]" "$NGINX_CONFIG" | grep -q "location = /robots.txt"; then
    echo "✅ Location blocks are in correct server block"
else
    echo "⚠️  Location blocks might be in wrong server block"
    echo "Looking for server block with earningsstable.com (non-www)..."
    grep -n "server_name.*earningstable\.com[^w]" "$NGINX_CONFIG" | head -3
fi

# 4. Check if root is set in the same server block as location blocks
echo "4. Checking root directive..."
ROOT_LINE=$(grep -n "root.*public" "$NGINX_CONFIG" | head -1 | cut -d: -f1)
ROBOTS_LINE=$(grep -n "location = /robots.txt" "$NGINX_CONFIG" | head -1 | cut -d: -f1)

if [ -n "$ROOT_LINE" ] && [ -n "$ROBOTS_LINE" ]; then
    if [ "$ROOT_LINE" -lt "$ROBOTS_LINE" ]; then
        echo "✅ Root directive is before location blocks"
    else
        echo "⚠️  Root directive is after location blocks - this might be a problem"
    fi
fi

# 5. Show the actual server block structure
echo "5. Server block structure for earningsstable.com (non-www):"
grep -B 2 -A 50 "server_name.*earningstable\.com[^w]" "$NGINX_CONFIG" | head -60

echo ""
echo "✅ Diagnostic complete"
