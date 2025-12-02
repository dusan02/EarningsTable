#!/bin/bash
# 🔧 Hard Reset Nginx Vhosts - Remove ALL conflicts, keep only one clean config

set -e

echo "🔧 Hard Reset Nginx Vhosts..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Step A: Find all conflicts
echo -e "${BLUE}Step A: Finding all conflict sources...${NC}"
echo ""

echo "1. Checking nginx.conf includes:"
grep -n 'sites-enabled\|conf.d\|include' /etc/nginx/nginx.conf | head -10

echo ""
echo "2. Saving compiled config..."
nginx -T 2>/dev/null | tee /root/nginx-compiled.txt > /dev/null
echo "Saved to: /root/nginx-compiled.txt"

echo ""
echo "3. All server_name and listen directives:"
grep -nE 'server_name|listen 443|listen 80|default_server' /root/nginx-compiled.txt | head -30

echo ""
echo "4. All files with earningsstable.com:"
grep -RniE 'earningsstable\.com|earnings-table\.com' /etc/nginx 2>/dev/null | grep -v ".backup" | head -20

# Step B: Backup
echo ""
echo -e "${BLUE}Step B: Creating backups...${NC}"
BACKUP_DIR="/root/nginx-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

cp -a /etc/nginx/nginx.conf "$BACKUP_DIR/nginx.conf" 2>/dev/null || true
cp -a /etc/nginx/sites-enabled/* "$BACKUP_DIR/sites-enabled/" 2>/dev/null || true
cp -a /etc/nginx/conf.d/* "$BACKUP_DIR/conf.d/" 2>/dev/null || true

echo "Backup created: $BACKUP_DIR"

# Step C: Clean up
echo ""
echo -e "${BLUE}Step C: Cleaning up all vhosts except main config...${NC}"

# Remove all symlinks except main
echo "Removing symlinks..."
find /etc/nginx/sites-enabled -type l ! -name 'earningstable.com' -exec rm -f {} + 2>/dev/null || true

# Remove all files except main
echo "Removing duplicate files..."
find /etc/nginx/sites-enabled -maxdepth 1 -type f ! -name 'earningstable.com' -exec rm -f {} + 2>/dev/null || true

# Empty conf.d (often contains conflicting configs)
echo "Emptying conf.d/..."
rm -f /etc/nginx/conf.d/*.conf 2>/dev/null || true
rm -f /etc/nginx/conf.d/* 2>/dev/null || true

echo -e "${GREEN}✅ Cleanup complete${NC}"

# Step D: Create clean config with default_server
echo ""
echo -e "${BLUE}Step D: Creating clean config with default_server...${NC}"

MAIN_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
CONFIG_BACKUP="${MAIN_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"
cp "$MAIN_CONFIG" "$CONFIG_BACKUP"

# Check SSL certs
SSL_CERT="/etc/letsencrypt/live/earningstable.com/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/earningstable.com/privkey.pem"

if [ -f "$SSL_CERT" ] && [ -f "$SSL_KEY" ]; then
    USE_SSL=true
else
    USE_SSL=false
fi

# Create clean config
cat > "$MAIN_CONFIG" << 'NGINX_EOF'
# HTTP -> HTTPS (all variants)
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name earningsstable.com www.earningsstable.com earnings-table.com www.earnings-table.com _;
    return 301 https://earningsstable.com$request_uri;
}

# HTTPS - ONLY block for this domain
server {
    listen 443 ssl http2 default_server;
    listen [::]:443 ssl http2 default_server;
    server_name earningsstable.com;

    # SSL certificates (Let's Encrypt) - NOTE: earningsstable.com (with TWO s)
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

    # Direct serving of SEO files
    root /var/www/earnings-table/public;

    # SEO files with debug header
    location = /robots.txt {
        try_files /robots.txt =404;
        default_type text/plain;
        access_log off;
        add_header X-Debug-Block A always;
    }

    location = /sitemap.xml {
        try_files /sitemap.xml =404;
        default_type application/xml;
        access_log off;
        add_header X-Debug-Block A always;
    }

    # Diagnostic test route
    location = /__nginx__ {
        return 200 "I am earningsstable.com 443 block\n";
        add_header Content-Type text/plain;
        add_header X-Debug-Block A;
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
NGINX_EOF

# Comment out SSL if certs don't exist
if [ "$USE_SSL" = false ]; then
    sed -i 's/^    ssl_certificate/#    ssl_certificate/' "$MAIN_CONFIG"
    sed -i 's/^    ssl_certificate_key/#    ssl_certificate_key/' "$MAIN_CONFIG"
    sed -i 's/^    listen 443 ssl http2 default_server;/    # listen 443 ssl http2 default_server; # Uncomment after certbot/' "$MAIN_CONFIG"
    sed -i 's/^    listen \[::\]:443 ssl http2 default_server;/    # listen [::]:443 ssl http2 default_server; # Uncomment after certbot/' "$MAIN_CONFIG"
fi

echo -e "${GREEN}✅ Clean config created${NC}"

# Step E: Validate and restart
echo ""
echo -e "${BLUE}Step E: Validating and restarting...${NC}"

if nginx -t 2>&1 | grep -q "successful"; then
    echo -e "${GREEN}✅ Config valid${NC}"
    
    # Check conflicts - MUST be 0
    CONFLICTS=$(nginx -t 2>&1 | grep -c "conflicting server name" || echo "0")
    if [ "$CONFLICTS" -eq 0 ]; then
        echo -e "${GREEN}✅ No conflicting server names!${NC}"
    else
        echo -e "${RED}❌ Still have $CONFLICTS conflicts!${NC}"
        echo "There are more configs to remove"
        nginx -t 2>&1 | grep "conflicting" | head -5
    fi
    
    systemctl restart nginx
    echo -e "${GREEN}✅ Nginx restarted${NC}"
else
    echo -e "${RED}❌ Config invalid${NC}"
    nginx -t
    cp "$CONFIG_BACKUP" "$MAIN_CONFIG"
    exit 1
fi

# Step F: Check what's listening on ports
echo ""
echo -e "${BLUE}Step F: Checking what's listening on ports 80/443...${NC}"
ss -tulpn | grep -E ':(80|443)\s' || echo "Nothing found"

echo ""
echo "Checking for other web servers:"
systemctl status apache2 2>/dev/null | head -3 || echo "Apache not running"
systemctl status caddy 2>/dev/null | head -3 || echo "Caddy not running"

# Step G: Test with --resolve (bypasses DNS/CDN)
echo ""
echo -e "${BLUE}Step G: Testing with --resolve (bypasses DNS/CDN)...${NC}"
echo ""

echo "Testing debug route:"
DEBUG_RESPONSE=$(curl -k -i --resolve earningsstable.com:443:127.0.0.1 https://earningsstable.com/__nginx__ 2>&1 | head -20)
echo "$DEBUG_RESPONSE"

if echo "$DEBUG_RESPONSE" | grep -q "I am earningsstable.com 443 block"; then
    echo -e "${GREEN}✅ Debug route works! We're in the correct block${NC}"
    if echo "$DEBUG_RESPONSE" | grep -q "X-Debug-Block: A"; then
        echo -e "${GREEN}✅ X-Debug-Block header confirmed${NC}"
    fi
else
    echo -e "${RED}❌ Debug route failed - still NOT in correct block${NC}"
    echo "Check for other web servers or external proxies"
fi

echo ""
echo "Testing robots.txt:"
ROBOTS_RESPONSE=$(curl -k -I --resolve earningsstable.com:443:127.0.0.1 https://earningsstable.com/robots.txt 2>&1 | head -15)
echo "$ROBOTS_RESPONSE"

if echo "$ROBOTS_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ robots.txt returns 200!${NC}"
    if echo "$ROBOTS_RESPONSE" | grep -q "X-Debug-Block: A"; then
        echo -e "${GREEN}✅ From correct block${NC}"
    fi
    if echo "$ROBOTS_RESPONSE" | grep -q "x-content-type-options: nosniff"; then
        echo -e "${RED}❌ Still showing Express headers!${NC}"
    else
        echo -e "${GREEN}✅ Nginx headers (not Express)${NC}"
    fi
else
    echo -e "${RED}❌ robots.txt still returns 404${NC}"
fi

echo ""
echo "Testing sitemap.xml:"
SITEMAP_RESPONSE=$(curl -k -I --resolve earningsstable.com:443:127.0.0.1 https://earningsstable.com/sitemap.xml 2>&1 | head -15)
echo "$SITEMAP_RESPONSE"

if echo "$SITEMAP_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ sitemap.xml returns 200!${NC}"
else
    echo -e "${RED}❌ sitemap.xml still returns 404${NC}"
fi

echo ""
echo -e "${GREEN}🎉 Hard reset complete!${NC}"
echo ""
echo "Next steps:"
echo "  1. If conflicts still exist, check /etc/nginx/conf.d/ and other includes"
echo "  2. If debug route works, proceed to Let's Encrypt and GSC"
echo ""

