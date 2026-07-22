#!/bin/bash
# 🔧 Fix robots.txt and sitemap.xml serving in Nginx

set -e

echo "🔧 Fixing robots.txt and sitemap.xml in Nginx..."

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
PUBLIC_DIR="/var/www/earnings-table/public"

# Backup
cp "$NGINX_CONFIG" "${NGINX_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"

# Remove old backups from sites-enabled
rm -f /etc/nginx/sites-enabled/*.backup.* 2>/dev/null || true

# Check if files exist
if [ ! -f "$PUBLIC_DIR/robots.txt" ] || [ ! -f "$PUBLIC_DIR/sitemap.xml" ]; then
    echo "❌ Files not found!"
    exit 1
fi

echo "✅ Files exist"

# Check current config
if grep -q "location = /robots.txt" "$NGINX_CONFIG" && grep -q "location = /sitemap.xml" "$NGINX_CONFIG"; then
    echo "✅ Location blocks exist"
    
    # Check if they use correct syntax (root + try_files)
    if grep -A 3 "location = /robots.txt" "$NGINX_CONFIG" | grep -q "try_files"; then
        echo "✅ robots.txt uses try_files"
    else
        echo "⚠️  robots.txt needs fixing"
        # Replace alias with root + try_files
        sed -i '/location = \/robots\.txt {/,/}/ {
            /alias/d
            /default_type/a\        try_files /robots.txt =404;
            /location = \/robots\.txt {/a\        root /var/www/earnings-table/public;
        }' "$NGINX_CONFIG"
    fi
    
    if grep -A 3 "location = /sitemap.xml" "$NGINX_CONFIG" | grep -q "try_files"; then
        echo "✅ sitemap.xml uses try_files"
    else
        echo "⚠️  sitemap.xml needs fixing"
        # Replace alias with root + try_files
        sed -i '/location = \/sitemap\.xml {/,/}/ {
            /alias/d
            /default_type/a\        try_files /sitemap.xml =404;
            /location = \/sitemap\.xml {/a\        root /var/www/earnings-table/public;
        }' "$NGINX_CONFIG"
    fi
else
    echo "⚠️  Location blocks missing, adding them..."
    
    # Find location / block and add before it
    if grep -q "location / {" "$NGINX_CONFIG"; then
        # Add location blocks before "location / {"
        sed -i '/location \/ {/i\
    # SEO: Serve robots.txt directly from disk\
    location = /robots.txt {\
        root /var/www/earnings-table/public;\
        try_files /robots.txt =404;\
        default_type text/plain;\
        access_log off;\
    }\
\
    # SEO: Serve sitemap.xml directly from disk\
    location = /sitemap.xml {\
        root /var/www/earnings-table/public;\
        try_files /sitemap.xml =404;\
        default_type application/xml;\
        access_log off;\
    }\
' "$NGINX_CONFIG"
        echo "✅ Added location blocks"
    else
        echo "❌ Could not find 'location / {' in config"
        exit 1
    fi
fi

# Ensure root is set in server block
if ! grep -q "^[[:space:]]*root[[:space:]]*/var/www/earnings-table/public;" "$NGINX_CONFIG"; then
    echo "⚠️  Adding root directive..."
    # Add root after server_name
    sed -i '/server_name.*earningstable\.com;/a\
    root /var/www/earnings-table/public;
' "$NGINX_CONFIG"
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
    else
        echo "❌ robots.txt still 404"
    fi
    
    SITEMAP=$(curl -k -s https://earningsstable.com/sitemap.xml)
    if [ -n "$SITEMAP" ] && echo "$SITEMAP" | grep -q "urlset\|lastmod"; then
        echo "✅ sitemap.xml works"
    else
        echo "❌ sitemap.xml still 404"
    fi
else
    echo "❌ Config has errors"
    nginx -t
    exit 1
fi

echo "✅ Done!"
