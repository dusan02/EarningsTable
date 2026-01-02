#!/bin/bash
# 🔧 Clean fix for Nginx config - remove duplicates and fix location blocks

set -e

echo "🔧 Cleaning Nginx configuration..."

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
PUBLIC_DIR="/var/www/earnings-table/public"

# Backup
cp "$NGINX_CONFIG" "${NGINX_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"
rm -f /etc/nginx/sites-enabled/*.backup.* 2>/dev/null || true

# Fix permissions
chmod 644 "$PUBLIC_DIR/robots.txt" "$PUBLIC_DIR/sitemap.xml"
chmod 755 "$PUBLIC_DIR"

# Create clean config
cat > "$NGINX_CONFIG" << 'NGINX_EOF'
# HTTP -> HTTPS (all variants)
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name earningsstable.com www.earningsstable.com earnings-table.com www.earnings-table.com _;
    return 301 https://earningsstable.com$request_uri;
}

# HTTPS - Main server block for earningsstable.com
server {
    listen 443 ssl http2 default_server;
    listen [::]:443 ssl http2 default_server;
    server_name earningsstable.com;

    # SSL certificates
    ssl_certificate /etc/letsencrypt/live/earningstable.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/earningstable.com/privkey.pem;

    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Root for static files
    root /var/www/earnings-table/public;

    # SEO: Serve robots.txt directly from disk (BEFORE proxy)
    location = /robots.txt {
        root /var/www/earnings-table/public;
        try_files /robots.txt =404;
        default_type text/plain;
        access_log off;
    }

    # SEO: Serve sitemap.xml directly from disk (BEFORE proxy)
    location = /sitemap.xml {
        root /var/www/earnings-table/public;
        try_files /sitemap.xml =404;
        default_type application/xml;
        access_log off;
    }

    # Everything else -> Express
    location / {
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header Connection "";
        proxy_pass http://127.0.0.1:5555;
        proxy_http_version 1.1;
        proxy_read_timeout 60s;
    }

    # Logging
    access_log /var/log/nginx/earnings-table.access.log;
    error_log /var/log/nginx/earnings-table.error.log;
}

# Redirect www to non-www (HTTPS) - ONLY ONE
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.earningstable.com;

    ssl_certificate /etc/letsencrypt/live/earningstable.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/earningstable.com/privkey.pem;

    return 301 https://earningsstable.com$request_uri;
}
NGINX_EOF

echo "✅ Clean config created"

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
        echo "$ROBOTS"
    else
        echo "❌ robots.txt still 404"
        echo "$ROBOTS"
    fi
    
    SITEMAP=$(curl -k -s https://earningsstable.com/sitemap.xml)
    if [ -n "$SITEMAP" ] && echo "$SITEMAP" | grep -q "urlset\|lastmod"; then
        echo "✅ sitemap.xml works"
        echo "$SITEMAP" | head -10
    else
        echo "❌ sitemap.xml still 404"
        echo "$SITEMAP"
    fi
else
    echo "❌ Config has errors"
    nginx -t
    exit 1
fi

echo "✅ Done!"
