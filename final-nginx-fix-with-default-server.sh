#!/bin/bash
# 🔧 Final Nginx Fix - Add default_server and debug route to ensure correct block is used

set -e

echo "🔧 Final Nginx Fix with default_server..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

MAIN_CONFIG="/etc/nginx/sites-enabled/earningstable.com"

# Step 1: Check what's loaded
echo -e "${BLUE}Step 1: Checking what's currently loaded...${NC}"
echo ""
echo "Files in sites-enabled/:"
ls -la /etc/nginx/sites-enabled/ | grep -v "^total" | grep -v "^d"

echo ""
echo "Compiled config - server_name earningsstable.com:"
nginx -T 2>/dev/null | grep -B3 -A30 'server_name .*earningsstable\.com' | head -50

echo ""
echo "Conflicting server names:"
CONFLICTS=$(nginx -t 2>&1 | grep -c "conflicting server name" || echo "0")
echo "  Found: $CONFLICTS conflicts"

# Step 2: Disconnect all except main config
echo ""
echo -e "${BLUE}Step 2: Disconnecting duplicate configs...${NC}"

# Backup list
ls -la /etc/nginx/sites-enabled/ > /root/sites-enabled.before.txt 2>/dev/null || true

# Remove all except main config
echo "Removing duplicates..."
rm -f /etc/nginx/sites-enabled/*.backup* 2>/dev/null || true
rm -f /etc/nginx/sites-enabled/*.bak* 2>/dev/null || true
unlink /etc/nginx/sites-enabled/default 2>/dev/null || true
unlink /etc/nginx/sites-enabled/earnings-table 2>/dev/null || true
unlink /etc/nginx/sites-enabled/earningstable.conf 2>/dev/null || true

# Keep only main config
echo ""
echo "Files remaining in sites-enabled/:"
ls -la /etc/nginx/sites-enabled/ | grep -v "^total" | grep -v "^d"

# Step 3: Create clean unified config with default_server
echo ""
echo -e "${BLUE}Step 3: Creating clean config with default_server...${NC}"

# Backup
BACKUP="${MAIN_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"
cp "$MAIN_CONFIG" "$BACKUP"
echo "Backup: $BACKUP"
echo ""

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

    # SSL certificates (Let's Encrypt)
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

    # Option A: root + try_files (simple and reliable)
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

    # Diagnostic test route (to verify we're in the correct block)
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

echo -e "${GREEN}✅ Clean config created with default_server flags${NC}"

# Step 4: Validate and reload
echo ""
echo -e "${BLUE}Step 4: Validating and reloading...${NC}"

if nginx -t 2>&1 | grep -q "successful"; then
    echo -e "${GREEN}✅ Config valid${NC}"
    
    # Check conflicts
    NEW_CONFLICTS=$(nginx -t 2>&1 | grep -c "conflicting server name" || echo "0")
    if [ "$NEW_CONFLICTS" -eq 0 ]; then
        echo -e "${GREEN}✅ No conflicting server names!${NC}"
    else
        echo -e "${YELLOW}⚠️  Still have $NEW_CONFLICTS conflicts${NC}"
        echo "There may be more configs to remove"
    fi
    
    systemctl restart nginx
    echo -e "${GREEN}✅ Nginx restarted${NC}"
else
    echo -e "${RED}❌ Config invalid${NC}"
    nginx -t
    cp "$BACKUP" "$MAIN_CONFIG"
    exit 1
fi

# Step 5: Verify active block
echo ""
echo -e "${BLUE}Step 5: Verifying active server block...${NC}"
ACTIVE_BLOCK=$(nginx -T 2>/dev/null | grep -B3 -A30 'server_name .*earningsstable\.com' | head -50)

if echo "$ACTIVE_BLOCK" | grep -q "default_server"; then
    echo -e "${GREEN}✅ default_server flag found in active block${NC}"
else
    echo -e "${YELLOW}⚠️  default_server flag NOT found${NC}"
fi

if echo "$ACTIVE_BLOCK" | grep -q "location = /robots.txt"; then
    echo -e "${GREEN}✅ Location blocks found in active block${NC}"
else
    echo -e "${RED}❌ Location blocks NOT found in active block${NC}"
fi

# Step 6: Test endpoints
echo ""
echo -e "${BLUE}Step 6: Testing endpoints...${NC}"
echo ""

echo "Testing debug route /__nginx__:"
DEBUG_RESPONSE=$(curl -k -i https://earningsstable.com/__nginx__ 2>&1 | head -15)
echo "$DEBUG_RESPONSE"

if echo "$DEBUG_RESPONSE" | grep -q "I am earningsstable.com 443 block"; then
    echo -e "${GREEN}✅ Debug route works! We're in the correct block${NC}"
    if echo "$DEBUG_RESPONSE" | grep -q "X-Debug-Block: A"; then
        echo -e "${GREEN}✅ X-Debug-Block header confirmed${NC}"
    fi
else
    echo -e "${RED}❌ Debug route failed - we're NOT in the correct block!${NC}"
    echo "Check for other active server blocks"
fi

echo ""
echo "Testing robots.txt:"
ROBOTS_RESPONSE=$(curl -k -I https://earningsstable.com/robots.txt 2>&1 | head -10)
echo "$ROBOTS_RESPONSE"

if echo "$ROBOTS_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ robots.txt returns 200!${NC}"
    if echo "$ROBOTS_RESPONSE" | grep -q "X-Debug-Block: A"; then
        echo -e "${GREEN}✅ From correct block (X-Debug-Block: A)${NC}"
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
SITEMAP_RESPONSE=$(curl -k -I https://earningsstable.com/sitemap.xml 2>&1 | head -10)
echo "$SITEMAP_RESPONSE"

if echo "$SITEMAP_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ sitemap.xml returns 200!${NC}"
else
    echo -e "${RED}❌ sitemap.xml still returns 404${NC}"
fi

echo ""
echo "Testing HTTP redirect:"
HTTP_RESPONSE=$(curl -I http://earningsstable.com/ 2>&1 | grep -iE 'HTTP|location' | head -2)
echo "$HTTP_RESPONSE"

if echo "$HTTP_RESPONSE" | grep -q "301\|302"; then
    echo -e "${GREEN}✅ HTTP redirects to HTTPS${NC}"
else
    echo -e "${YELLOW}⚠️  HTTP redirect might not be working${NC}"
fi

echo ""
echo -e "${GREEN}🎉 Final fix complete!${NC}"
echo ""
echo "If debug route /__nginx__ works but robots.txt doesn't, check:"
echo "  1. File permissions: sudo -u www-data cat /var/www/earnings-table/public/robots.txt"
echo "  2. Nginx error logs: tail -f /var/log/nginx/error.log"
echo ""

