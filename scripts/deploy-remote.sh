#!/bin/bash
# Fix nginx for SPA routes + dynamic sitemap
echo "=== NGINX FIX START ==="

# Backup current config
cp /etc/nginx/sites-enabled/earningstable.com /etc/nginx/sites-enabled/earningstable.com.bak

# Write new config
cat > /etc/nginx/sites-enabled/earningstable.com << 'NGINXEOF'
# HTTP -> HTTPS (all variants)
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name earningstable.com www.earningstable.com earnings-table.com www.earnings-table.com _;
    return 301 https://earningstable.com$request_uri;
}

# HTTPS - Main server block for earningstable.com
server {
    listen 443 ssl http2 default_server;
    listen [::]:443 ssl http2 default_server;
    server_name earningstable.com;

    ssl_certificate /etc/letsencrypt/live/earningstable.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/earningstable.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Static files from public dir (build output)
    root /var/www/earnings-table/public;

    # robots.txt — serve from disk
    location = /robots.txt {
        alias /var/www/earnings-table/public/robots.txt;
        default_type text/plain;
        access_log off;
    }

    # sitemap.xml — proxy to Express for dynamic generation
    location = /sitemap.xml {
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_pass http://127.0.0.1:5555;
        proxy_http_version 1.1;
    }

    # Logos — serve from disk (fast)
    location /logos/ {
        alias /srv/EarningsTable/modules/web/public/logos/;
        expires 7d;
        access_log off;
    }

    # Static assets from build (JS, CSS, images)
    location /static/ {
        alias /var/www/earnings-table/public/static/;
        expires 1y;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    # Favicon
    location = /favicon.svg {
        alias /var/www/earnings-table/public/favicon.svg;
        access_log off;
    }
    location = /favicon.ico {
        alias /var/www/earnings-table/public/favicon.ico;
        access_log off;
    }

    # SPA routes (/date/:date, etc.) — proxy to Express which serves index.html
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

    access_log /var/log/nginx/earnings-table.access.log;
    error_log /var/log/nginx/earnings-table.error.log;
}

# Redirect www to non-www (HTTPS)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.earningstable.com;

    ssl_certificate /etc/letsencrypt/live/earningstable.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/earningstable.com/privkey.pem;

    return 301 https://earningstable.com$request_uri;
}
NGINXEOF

echo "=== NGINX TEST ==="
nginx -t 2>&1

echo "=== NGINX RELOAD ==="
systemctl reload nginx 2>&1

echo "=== SYNC PUBLIC DIR ==="
# Ensure /var/www/earnings-table/public has latest build
mkdir -p /var/www/earnings-table/public
cp -r /srv/EarningsTable/build/* /var/www/earnings-table/public/ 2>&1
# Remove static sitemap so nginx proxies to Express
rm -f /var/www/earnings-table/public/sitemap.xml 2>&1
ls -la /var/www/earnings-table/public/index.html 2>&1

echo "=== RESTART EXPRESS ==="
systemctl restart earnings-table 2>&1
sleep 3
systemctl is-active earnings-table 2>&1

echo "=== CHECKS ==="
curl -s -o /dev/null -w "root=%{http_code}\n" https://earningstable.com/ 2>&1
curl -s -o /dev/null -w "date_route=%{http_code}\n" https://earningstable.com/date/2026-09-09 2>&1
curl -s -o /dev/null -w "sitemap=%{http_code}\n" https://earningstable.com/sitemap.xml 2>&1
curl -s -o /dev/null -w "robots=%{http_code}\n" https://earningstable.com/robots.txt 2>&1
curl -s -o /dev/null -w "api_health=%{http_code}\n" https://earningstable.com/api/health 2>&1
curl -s -o /dev/null -w "static_js=%{http_code}\n" https://earningstable.com/static/js/main.842b1ce4.js 2>&1
echo "--- sitemap (first 400 chars) ---"
curl -s https://earningstable.com/sitemap.xml 2>&1 | head -c 400
echo ""

echo "=== NGINX FIX END ==="
