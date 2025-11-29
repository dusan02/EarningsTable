#!/bin/bash
# 🔧 Surgical Nginx Fix - 7-step solution to fix robots.txt/sitemap.xml 404
# Based on user's detailed instructions

set -e

echo "🔧 Surgical Nginx Fix - 7 Steps"
echo "================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

MAIN_CONFIG="/etc/nginx/sites-enabled/earningstable.com"

# Step 1: Find and remove duplicate configs
echo -e "${BLUE}Step 1: Finding duplicate configs...${NC}"
echo ""

echo "Files in sites-enabled/:"
ls -la /etc/nginx/sites-enabled/ | grep -v "^total" | grep -v "^d"

echo ""
echo "Searching for server_name and listen 443:"
grep -RniE 'server_name|listen 443' /etc/nginx/sites-enabled/ 2>/dev/null | head -20

echo ""
echo -e "${YELLOW}⚠️  If you see multiple files with same server_name, we'll remove duplicates${NC}"
echo ""

# Find duplicates
DUPLICATES=$(grep -l "server_name.*earningsstable\|server_name.*earningstable" /etc/nginx/sites-enabled/* 2>/dev/null | grep -v "^${MAIN_CONFIG}$" || echo "")

if [ -n "$DUPLICATES" ]; then
    echo "Found potential duplicates:"
    echo "$DUPLICATES" | while read -r dup; do
        echo "  - $dup"
    done
    echo ""
    read -p "Remove these duplicates? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "$DUPLICATES" | while read -r dup; do
            if [ -f "$dup" ]; then
                echo "  Removing: $dup"
                rm -f "$dup"
            fi
        done
        echo -e "${GREEN}✅ Duplicates removed${NC}"
    fi
else
    echo -e "${GREEN}✅ No obvious duplicates found${NC}"
fi

# Step 2: Create unified config
echo ""
echo -e "${BLUE}Step 2: Creating unified config...${NC}"

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
    listen 80;
    listen [::]:80;
    server_name earningsstable.com www.earningsstable.com earnings-table.com www.earnings-table.com;
    return 301 https://earningsstable.com$request_uri;
}

# Main HTTPS server block (ONLY ONE for this domain)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
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

    # Static SEO files served directly from disk
    root /var/www/earnings-table/public;

    # Option A: root + try_files (simple and reliable)
    location = /robots.txt {
        try_files /robots.txt =404;
        default_type text/plain;
        access_log off;
    }

    location = /sitemap.xml {
        try_files /sitemap.xml =404;
        default_type application/xml;
        access_log off;
    }

    # (Alternative: alias - if you want absolute paths)
    # location = /robots.txt {
    #     alias /var/www/earnings-table/public/robots.txt;
    #     default_type text/plain;
    #     access_log off;
    # }
    # location = /sitemap.xml {
    #     alias /var/www/earnings-table/public/sitemap.xml;
    #     default_type application/xml;
    #     access_log off;
    # }

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
    sed -i 's/^    listen 443 ssl http2;/    # listen 443 ssl http2; # Uncomment after certbot/' "$MAIN_CONFIG"
    sed -i 's/^    listen \[::\]:443 ssl http2;/    # listen [::]:443 ssl http2; # Uncomment after certbot/' "$MAIN_CONFIG"
fi

echo -e "${GREEN}✅ Unified config created${NC}"

# Step 3: Validate and reload
echo ""
echo -e "${BLUE}Step 3: Validating and reloading Nginx...${NC}"

if nginx -t 2>&1 | grep -q "successful"; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    
    # Check conflicts
    CONFLICTS=$(nginx -t 2>&1 | grep -c "conflicting server name" || echo "0")
    if [ "$CONFLICTS" -eq 0 ]; then
        echo -e "${GREEN}✅ No conflicting server names!${NC}"
    else
        echo -e "${YELLOW}⚠️  Still have $CONFLICTS conflicting server names${NC}"
        echo "Run: nginx -t 2>&1 | grep conflicting"
    fi
    
    # Reload
    systemctl reload nginx
    echo -e "${GREEN}✅ Nginx reloaded${NC}"
    
    # If still issues, try restart
    echo ""
    echo "If robots.txt still returns 404, we'll try full restart..."
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    nginx -t
    echo "Restoring backup..."
    cp "$BACKUP" "$MAIN_CONFIG"
    exit 1
fi

# Step 4: Verify active block
echo ""
echo -e "${BLUE}Step 4: Verifying active server block...${NC}"
ACTIVE_BLOCK=$(nginx -T 2>/dev/null | grep -B3 -A30 'server_name .*earn.*stable.*' | head -50)

if echo "$ACTIVE_BLOCK" | grep -q "location = /robots.txt"; then
    echo -e "${GREEN}✅ Location blocks ARE in active config!${NC}"
    echo ""
    echo "Active block shows:"
    echo "$ACTIVE_BLOCK" | grep -A 5 "location = /robots.txt" | head -6
else
    echo -e "${RED}❌ Location blocks are NOT in active config${NC}"
    echo ""
    echo "Active block:"
    echo "$ACTIVE_BLOCK" | head -30
fi

# Step 5: Test endpoints
echo ""
echo -e "${BLUE}Step 5: Testing endpoints...${NC}"
echo ""

echo "Testing robots.txt:"
ROBOTS_RESPONSE=$(curl -k -I https://earningsstable.com/robots.txt 2>&1 | head -10)
echo "$ROBOTS_RESPONSE"

if echo "$ROBOTS_RESPONSE" | grep -q "HTTP/2 200\|HTTP/1.1 200"; then
    echo -e "${GREEN}✅ robots.txt returns 200!${NC}"
    if echo "$ROBOTS_RESPONSE" | grep -q "x-content-type-options: nosniff"; then
        echo -e "${YELLOW}⚠️  Still showing Express headers (should be Nginx)${NC}"
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

# Step 6: If still 404, additional checks
if echo "$ROBOTS_RESPONSE" | grep -q "404"; then
    echo ""
    echo -e "${YELLOW}Step 6: Still 404 - Additional checks...${NC}"
    echo ""
    
    echo "Checking file permissions:"
    if sudo -u www-data cat /var/www/earnings-table/public/robots.txt > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Nginx user can read robots.txt${NC}"
    else
        echo -e "${RED}❌ Nginx user cannot read robots.txt${NC}"
        echo "Fixing permissions..."
        chmod 644 /var/www/earnings-table/public/robots.txt
        chmod 644 /var/www/earnings-table/public/sitemap.xml
    fi
    
    echo ""
    echo "Checking error logs (last 10 lines):"
    tail -10 /var/log/nginx/error.log 2>/dev/null || echo "No error log found"
    
    echo ""
    echo "Trying full restart..."
    systemctl restart nginx
    echo -e "${GREEN}✅ Nginx restarted${NC}"
    
    echo ""
    echo "Test again:"
    echo "  curl -k -I https://earningsstable.com/robots.txt"
fi

# Step 7: Success message
echo ""
echo -e "${GREEN}🎉 Surgical fix complete!${NC}"
echo ""
echo "Next steps:"
echo "  1. If robots.txt/sitemap.xml return 200, proceed to Google Search Console"
echo "  2. Add property: https://earningsstable.com"
echo "  3. Submit sitemap: https://earningsstable.com/sitemap.xml"
echo "  4. Request indexing for homepage"
echo ""

