#!/bin/bash
# 🔧 Fix main server block and location blocks

set -e

echo "🔧 Fixing main Nginx server block..."

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
PUBLIC_DIR="/var/www/earnings-table/public"

# Backup
cp "$NGINX_CONFIG" "${NGINX_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"
rm -f /etc/nginx/sites-enabled/*.backup.* 2>/dev/null || true

# Fix permissions
chmod 644 "$PUBLIC_DIR/robots.txt" "$PUBLIC_DIR/sitemap.xml"
chmod 755 "$PUBLIC_DIR"

echo "✅ Permissions fixed"

# Find main server block (earningsstable.com without www)
echo "🔍 Looking for main server block..."

# Check if main server block exists
if grep -q "server_name.*earningstable\.com[^w]" "$NGINX_CONFIG"; then
    echo "✅ Main server block found"
    
    # Check if it has location blocks
    MAIN_BLOCK_START=$(grep -n "server_name.*earningstable\.com[^w]" "$NGINX_CONFIG" | head -1 | cut -d: -f1)
    MAIN_BLOCK_END=$(sed -n "${MAIN_BLOCK_START},\$p" "$NGINX_CONFIG" | grep -n "^}" | head -1 | cut -d: -f1)
    MAIN_BLOCK_END=$((MAIN_BLOCK_START + MAIN_BLOCK_END - 1))
    
    # Extract main server block
    MAIN_BLOCK=$(sed -n "${MAIN_BLOCK_START},${MAIN_BLOCK_END}p" "$NGINX_CONFIG")
    
    if echo "$MAIN_BLOCK" | grep -q "location = /robots.txt"; then
        echo "✅ Location blocks exist in main server block"
    else
        echo "⚠️  Location blocks missing in main server block, adding..."
        
        # Find location / in main block and add before it
        LOCATION_SLASH_LINE=$(sed -n "${MAIN_BLOCK_START},${MAIN_BLOCK_END}p" "$NGINX_CONFIG" | grep -n "location / {" | head -1 | cut -d: -f1)
        if [ -n "$LOCATION_SLASH_LINE" ]; then
            ACTUAL_LINE=$((MAIN_BLOCK_START + LOCATION_SLASH_LINE - 1))
            sed -i "${ACTUAL_LINE}i\\
    # SEO: Serve robots.txt directly from disk\\
    location = /robots.txt {\\
        root /var/www/earnings-table/public;\\
        try_files /robots.txt =404;\\
        default_type text/plain;\\
        access_log off;\\
    }\\
\\
    # SEO: Serve sitemap.xml directly from disk\\
    location = /sitemap.xml {\\
        root /var/www/earnings-table/public;\\
        try_files /sitemap.xml =404;\\
        default_type application/xml;\\
        access_log off;\\
    }\\
" "$NGINX_CONFIG"
            echo "✅ Added location blocks"
        fi
    fi
else
    echo "❌ Main server block NOT found!"
    echo "This is a critical issue - the main server block for earningsstable.com is missing!"
    exit 1
fi

# Remove duplicate www redirect blocks (keep only one)
echo "🧹 Removing duplicate www redirect blocks..."
# Count www redirect blocks
WWW_COUNT=$(grep -c "server_name.*www.earningstable.com" "$NGINX_CONFIG" || echo "0")
if [ "$WWW_COUNT" -gt 1 ]; then
    echo "⚠️  Found $WWW_COUNT www redirect blocks, removing duplicates..."
    # Keep only the first one, remove others
    FIRST_WWW_LINE=$(grep -n "server_name.*www.earningstable.com" "$NGINX_CONFIG" | head -1 | cut -d: -f1)
    # Remove all www blocks except the first one
    awk -v first="$FIRST_WWW_LINE" '
        /server_name.*www\.earningstable\.com/ {
            if (NR == first) {
                in_www_block = 1
                print
                next
            } else {
                in_www_block = 1
                skip = 1
            }
        }
        /^}/ && in_www_block && skip {
            in_www_block = 0
            skip = 0
            next
        }
        !skip { print }
    ' "$NGINX_CONFIG" > "${NGINX_CONFIG}.tmp" && mv "${NGINX_CONFIG}.tmp" "$NGINX_CONFIG"
    echo "✅ Removed duplicate www redirect blocks"
fi

# Test config
echo "🧪 Testing Nginx config..."
if nginx -t 2>&1 | grep -q "test is successful"; then
    echo "✅ Config is valid"
    
    # Reload
    echo "🔄 Reloading Nginx..."
    systemctl reload nginx || nginx -s reload
    echo "✅ Nginx reloaded"
    
    # Test
    sleep 2
    echo "🧪 Testing..."
    
    ROBOTS=$(curl -k -s https://earningsstable.com/robots.txt)
    if [ -n "$ROBOTS" ] && ! echo "$ROBOTS" | grep -q "404\|not found"; then
        echo "✅ robots.txt works"
        echo "$ROBOTS" | head -3
    else
        echo "❌ robots.txt still 404"
    fi
    
    SITEMAP=$(curl -k -s https://earningsstable.com/sitemap.xml)
    if [ -n "$SITEMAP" ] && echo "$SITEMAP" | grep -q "urlset\|lastmod"; then
        echo "✅ sitemap.xml works"
        echo "$SITEMAP" | head -5
    else
        echo "❌ sitemap.xml still 404"
    fi
else
    echo "❌ Config has errors"
    nginx -t
    exit 1
fi

echo "✅ Done!"
