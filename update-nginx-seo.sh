#!/bin/bash
# 🔧 Update Nginx Configuration for SEO (301 Redirects + HTTPS)
# Adds proper redirects for old domains and HTTP→HTTPS

set -e

echo "🔧 Updating Nginx configuration for SEO..."

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    SUDO=""
    echo "Running as root - sudo not needed"
else
    SUDO="sudo"
    echo "Not running as root - will use sudo"
fi

# Configuration
NGINX_CONFIG="/etc/nginx/sites-available/earnings-table"
NGINX_ENABLED="/etc/nginx/sites-enabled/earnings-table"
PROJECT_DIR="/var/www/earnings-table"

# Check if config exists
if [ ! -f "$NGINX_CONFIG" ]; then
    echo -e "${RED}❌ Nginx config not found at $NGINX_CONFIG${NC}"
    echo "Run setup-nginx.sh first or create config manually"
    exit 1
fi

# Backup existing config
echo -e "${YELLOW}📦 Backing up existing config...${NC}"
$SUDO cp "$NGINX_CONFIG" "${NGINX_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"
echo -e "${GREEN}✅ Backup created${NC}"

# Check if SSL certificates exist
SSL_CERT="/etc/letsencrypt/live/earningsstable.com/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/earningsstable.com/privkey.pem"

if [ ! -f "$SSL_CERT" ] || [ ! -f "$SSL_KEY" ]; then
    echo -e "${YELLOW}⚠️  SSL certificates not found at standard location${NC}"
    echo "Please update SSL_CERT and SSL_KEY paths in this script"
    echo "Or use Let's Encrypt: sudo certbot --nginx -d earningsstable.com"
    SSL_CERT="/path/to/cert.pem"
    SSL_KEY="/path/to/key.pem"
fi

# Create updated Nginx config
echo -e "${YELLOW}⚙️  Creating updated Nginx configuration...${NC}"

$SUDO tee "$NGINX_CONFIG" > /dev/null << 'NGINX_EOF'
# Earnings Table Nginx Configuration with SEO redirects
# HTTP → HTTPS redirects
# Old domains → New domain redirects

# Redirect HTTP to HTTPS (all domains)
server {
    listen 80;
    listen [::]:80;
    server_name earningsstable.com www.earningsstable.com earnings-table.com www.earnings-table.com;
    
    # Redirect all HTTP to HTTPS
    return 301 https://earningsstable.com$request_uri;
}

# Redirect old domains and www to main domain (HTTPS)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.earningsstable.com earnings-table.com www.earnings-table.com;
    
    ssl_certificate /etc/letsencrypt/live/earningsstable.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/earningsstable.com/privkey.pem;
    
    # Redirect to main domain
    return 301 https://earningsstable.com$request_uri;
}

# Main server (HTTPS only)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name earningsstable.com;
    
    ssl_certificate /etc/letsencrypt/live/earningsstable.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/earningsstable.com/privkey.pem;
    
    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript application/json;
    
    # SEO: Serve robots.txt and sitemap.xml directly from disk (BEFORE proxy)
    location = /robots.txt {
        alias /var/www/earnings-table/public/robots.txt;
        access_log off;
        default_type text/plain;
        add_header Content-Type text/plain;
    }
    
    location = /sitemap.xml {
        alias /var/www/earnings-table/public/sitemap.xml;
        access_log off;
        default_type application/xml;
        add_header Content-Type application/xml;
    }
    
    # Main application
    location / {
        proxy_pass http://localhost:5555;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
    
    # Static files (logos, favicon) - direct serving for better performance
    location /logos/ {
        proxy_pass http://localhost:5555;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Cache static files
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    location /favicon.ico {
        proxy_pass http://localhost:5555;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Cache favicon
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # API endpoints
    location /api/ {
        proxy_pass http://localhost:5555;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # CORS headers for API
        add_header Access-Control-Allow-Origin "*" always;
        add_header Access-Control-Allow-Methods "GET, POST, OPTIONS" always;
        add_header Access-Control-Allow-Headers "DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range" always;
        
        # Handle preflight requests
        if ($request_method = 'OPTIONS') {
            add_header Access-Control-Allow-Origin "*";
            add_header Access-Control-Allow-Methods "GET, POST, OPTIONS";
            add_header Access-Control-Allow-Headers "DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range";
            add_header Access-Control-Max-Age 1728000;
            add_header Content-Type 'text/plain; charset=utf-8';
            add_header Content-Length 0;
            return 204;
        }
    }
    
    # Error pages
    error_page 404 /404.html;
    error_page 500 502 503 504 /50x.html;
    
    # Logging
    access_log /var/log/nginx/earnings-table.access.log;
    error_log /var/log/nginx/earnings-table.error.log;
}
NGINX_EOF

echo -e "${GREEN}✅ Nginx configuration updated${NC}"

# Test configuration
echo -e "${YELLOW}🧪 Testing Nginx configuration...${NC}"
if $SUDO nginx -t; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    BACKUP_FILE=$(ls -t ${NGINX_CONFIG}.backup.* 2>/dev/null | head -1)
    if [ -n "$BACKUP_FILE" ]; then
        echo "Restoring backup: $BACKUP_FILE"
        $SUDO cp "$BACKUP_FILE" "$NGINX_CONFIG"
    fi
    exit 1
fi

# Reload Nginx
echo -e "${YELLOW}🔄 Reloading Nginx...${NC}"
$SUDO systemctl reload nginx
echo -e "${GREEN}✅ Nginx reloaded${NC}"

echo ""
echo -e "${GREEN}🎉 Nginx SEO configuration updated!${NC}"
echo ""
echo -e "${BLUE}📝 Next steps:${NC}"
echo "  1. If SSL certificates don't exist, run: sudo certbot --nginx -d earningsstable.com"
echo "  2. Test redirects: curl -I http://earningsstable.com"
echo "  3. Test HTTPS: curl -I https://earningsstable.com"
echo "  4. Run post-deployment-seo-check.sh again"
echo ""

