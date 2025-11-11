#!/bin/bash
# 🔧 Consolidate Nginx Config - Remove duplicates, create clean unified config
# Based on user's template with root + try_files approach

set -e

echo "🔧 Consolidating Nginx configuration..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
NGINX_AVAILABLE="/etc/nginx/sites-available/earningstable.com"

# Check if config exists
if [ ! -f "$NGINX_CONFIG" ] && [ ! -f "$NGINX_AVAILABLE" ]; then
    echo -e "${RED}❌ Nginx config not found${NC}"
    echo "Looking for: $NGINX_CONFIG or $NGINX_AVAILABLE"
    exit 1
fi

# Use available if enabled doesn't exist
if [ ! -f "$NGINX_CONFIG" ]; then
    NGINX_CONFIG="$NGINX_AVAILABLE"
fi

# Backup
BACKUP_FILE="${NGINX_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"
cp "$NGINX_CONFIG" "$BACKUP_FILE"
echo -e "${GREEN}✅ Backup created: $BACKUP_FILE${NC}"
echo ""

# Check for duplicates
echo -e "${BLUE}Checking for duplicate server blocks...${NC}"
HTTPS_COUNT=$(grep -c "listen 443" "$NGINX_CONFIG" || echo "0")
echo "  HTTPS server blocks found: $HTTPS_COUNT"

if [ "$HTTPS_COUNT" -gt 1 ]; then
    echo -e "${YELLOW}⚠️  Multiple HTTPS blocks detected - will consolidate${NC}"
fi
echo ""

# Check SSL cert paths
SSL_CERT="/etc/letsencrypt/live/earningstable.com/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/earningstable.com/privkey.pem"

if [ -f "$SSL_CERT" ] && [ -f "$SSL_KEY" ]; then
    echo -e "${GREEN}✅ SSL certificates found${NC}"
    USE_SSL=true
else
    echo -e "${YELLOW}⚠️  SSL certificates not found at standard location${NC}"
    echo "  Will create config without SSL (you can add certbot later)"
    USE_SSL=false
fi
echo ""

# Create unified config
echo -e "${BLUE}Creating unified Nginx configuration...${NC}"

cat > "$NGINX_CONFIG" << 'NGINX_EOF'
# Earnings Table - Unified Nginx Configuration
# HTTP → HTTPS redirect for all domain variants
server {
    listen 80;
    listen [::]:80;
    server_name earningsstable.com www.earningsstable.com earnings-table.com www.earnings-table.com;
    return 301 https://earningsstable.com$request_uri;
}

# Primary HTTPS server block (ONLY ONE)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name earningsstable.com;

    # SSL certificates (managed by Certbot)
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

    # 1) SEO: robots.txt and sitemap.xml served directly from disk (BEFORE proxy)
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

    # 2) Everything else → Express/Node
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

    # API endpoints (optional: separate cache control)
    location /api/ {
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_pass http://127.0.0.1:5555;
        add_header Cache-Control "no-store" always;
        add_header Pragma "no-cache" always;
        add_header Expires "0" always;
    }

    # Logging
    access_log /var/log/nginx/earnings-table.access.log;
    error_log /var/log/nginx/earnings-table.error.log;
}
NGINX_EOF

# If SSL certs don't exist, comment out SSL lines
if [ "$USE_SSL" = false ]; then
    echo -e "${YELLOW}Commenting out SSL lines (certs not found)...${NC}"
    sed -i 's/^    ssl_certificate/#    ssl_certificate/' "$NGINX_CONFIG"
    sed -i 's/^    ssl_certificate_key/#    ssl_certificate_key/' "$NGINX_CONFIG"
    sed -i 's/^    listen 443 ssl http2;/    # listen 443 ssl http2; # Uncomment after certbot/' "$NGINX_CONFIG"
    sed -i 's/^    listen \[::\]:443 ssl http2;/    # listen [::]:443 ssl http2; # Uncomment after certbot/' "$NGINX_CONFIG"
fi

echo -e "${GREEN}✅ Unified configuration created${NC}"
echo ""

# Test configuration
echo -e "${BLUE}Testing Nginx configuration...${NC}"
if nginx -t 2>&1 | grep -q "successful"; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    echo "Restoring backup..."
    cp "$BACKUP_FILE" "$NGINX_CONFIG"
    nginx -t
    exit 1
fi

# Check for remaining conflicts
CONFLICTS=$(nginx -t 2>&1 | grep -c "conflicting server name" || echo "0")
if [ "$CONFLICTS" -gt 0 ]; then
    echo -e "${YELLOW}⚠️  Still have conflicting server names${NC}"
    echo "Checking other config files..."
    echo ""
    echo "Other configs in sites-enabled:"
    ls -la /etc/nginx/sites-enabled/ | grep -v "earningstable.com" | grep -v "^total" || echo "  None"
    echo ""
    echo "You may need to disable other configs:"
    echo "  sudo unlink /etc/nginx/sites-enabled/other-config"
else
    echo -e "${GREEN}✅ No conflicting server names${NC}"
fi

# Reload Nginx
echo ""
echo -e "${BLUE}Reloading Nginx...${NC}"
systemctl reload nginx
echo -e "${GREEN}✅ Nginx reloaded${NC}"

echo ""
echo -e "${GREEN}🎉 Configuration consolidated!${NC}"
echo ""
echo -e "${BLUE}Testing endpoints:${NC}"
echo "  curl -k -I https://earningsstable.com/robots.txt"
echo "  curl -k -I https://earningsstable.com/sitemap.xml"
echo ""
echo -e "${BLUE}Expected:${NC}"
echo "  HTTP/2 200 OK"
echo "  Content-Type: text/plain (robots.txt) or application/xml (sitemap.xml)"
echo "  NO 'x-content-type-options: nosniff' header (that's Express, not Nginx)"
echo ""

# Verify active config
echo -e "${BLUE}Verifying active configuration...${NC}"
ACTIVE_CHECK=$(nginx -T 2>/dev/null | grep -A 5 "server_name.*earningsstable.com" | grep -A 5 "listen 443" | head -10 || echo "")

if echo "$ACTIVE_CHECK" | grep -q "location = /robots.txt"; then
    echo -e "${GREEN}✅ Location blocks ARE in active config${NC}"
else
    echo -e "${YELLOW}⚠️  Location blocks might not be in active config${NC}"
    echo "Run: nginx -T | grep -A 30 'server_name.*earningsstable' to verify"
fi

echo ""
echo -e "${BLUE}Next steps:${NC}"
if [ "$USE_SSL" = false ]; then
    echo "  1. Get SSL certificate: sudo certbot --nginx -d earningsstable.com"
    echo "  2. Uncomment SSL lines in config"
    echo "  3. Reload Nginx"
fi
echo "  4. Test: curl -k -I https://earningsstable.com/robots.txt"
echo "  5. Submit sitemap in Google Search Console"
echo ""

