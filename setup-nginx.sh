#!/bin/bash

# 🌐 Nginx Setup Script
# Configures Nginx to serve earnings-table on port 80/443

set -e  # Exit on any error

echo "🌐 Setting up Nginx for earnings-table..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="www.earningstable.com"
BACKUP_DOMAIN="earningstable.com"
APP_PORT=5555
NGINX_CONFIG="/etc/nginx/sites-available/earnings-table"
NGINX_ENABLED="/etc/nginx/sites-enabled/earnings-table"

echo -e "${BLUE}📋 Nginx Configuration:${NC}"
echo "  Domain: $DOMAIN"
echo "  Backup Domain: $BACKUP_DOMAIN"
echo "  App Port: $APP_PORT"
echo "  Config File: $NGINX_CONFIG"
echo ""

# 1. Install Nginx if not exists
echo -e "${YELLOW}📦 Installing Nginx...${NC}"
if ! command -v nginx &> /dev/null; then
    sudo apt update
    sudo apt install -y nginx
    echo -e "${GREEN}✅ Nginx installed${NC}"
else
    echo -e "${GREEN}✅ Nginx already installed${NC}"
fi

# 2. Create Nginx configuration
echo -e "${YELLOW}⚙️  Creating Nginx configuration...${NC}"
sudo tee "$NGINX_CONFIG" > /dev/null << EOF
# Earnings Table Nginx Configuration
# Ensures identical UX/UI as localhost:5555

server {
    listen 80;
    server_name $DOMAIN $BACKUP_DOMAIN;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;
    
    # Main application
    location / {
        proxy_pass http://localhost:$APP_PORT;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
    
    # Static files (logos, favicon) - direct serving for better performance
    location /logos/ {
        proxy_pass http://localhost:$APP_PORT;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
        # Cache static files
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    location /favicon.ico {
        proxy_pass http://localhost:$APP_PORT;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
        # Cache favicon
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # API endpoints
    location /api/ {
        proxy_pass http://localhost:$APP_PORT;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        
        # CORS headers for API
        add_header Access-Control-Allow-Origin "*" always;
        add_header Access-Control-Allow-Methods "GET, POST, OPTIONS" always;
        add_header Access-Control-Allow-Headers "DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range" always;
        
        # Handle preflight requests
        if (\$request_method = 'OPTIONS') {
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
EOF

echo -e "${GREEN}✅ Nginx configuration created${NC}"

# 3. Enable site
echo -e "${YELLOW}🔗 Enabling site...${NC}"
sudo ln -sf "$NGINX_CONFIG" "$NGINX_ENABLED"
echo -e "${GREEN}✅ Site enabled${NC}"

# 4. Test configuration
echo -e "${YELLOW}🧪 Testing Nginx configuration...${NC}"
sudo nginx -t
echo -e "${GREEN}✅ Nginx configuration is valid${NC}"

# 5. Reload Nginx
echo -e "${YELLOW}🔄 Reloading Nginx...${NC}"
sudo systemctl reload nginx
echo -e "${GREEN}✅ Nginx reloaded${NC}"

# 6. Enable Nginx on boot
echo -e "${YELLOW}🚀 Enabling Nginx on boot...${NC}"
sudo systemctl enable nginx
echo -e "${GREEN}✅ Nginx enabled on boot${NC}"

# 7. Test endpoints
echo -e "${YELLOW}🧪 Testing endpoints...${NC}"
sleep 2  # Wait for Nginx to reload

# Test main domain
if curl -s "http://$DOMAIN" > /dev/null; then
    echo -e "${GREEN}✅ Main domain ($DOMAIN) is working${NC}"
else
    echo -e "${RED}❌ Main domain ($DOMAIN) failed${NC}"
fi

# Test backup domain
if curl -s "http://$BACKUP_DOMAIN" > /dev/null; then
    echo -e "${GREEN}✅ Backup domain ($BACKUP_DOMAIN) is working${NC}"
else
    echo -e "${RED}❌ Backup domain ($BACKUP_DOMAIN) failed${NC}"
fi

echo ""
echo -e "${GREEN}🎉 Nginx setup completed successfully!${NC}"
echo ""
echo -e "${BLUE}🌐 URLs:${NC}"
echo "  Main: http://$DOMAIN"
echo "  Backup: http://$BACKUP_DOMAIN"
echo "  API: http://$DOMAIN/api/health"
echo "  Logos: http://$DOMAIN/logos/ALLY.webp"
echo "  Favicon: http://$DOMAIN/favicon.ico"
echo ""
echo -e "${BLUE}📝 Useful Commands:${NC}"
echo "  View logs: sudo tail -f /var/log/nginx/earnings-table.access.log"
echo "  Test config: sudo nginx -t"
echo "  Reload: sudo systemctl reload nginx"
echo "  Status: sudo systemctl status nginx"
echo ""
echo -e "${GREEN}✅ Nginx is now serving earnings-table with identical UX/UI!${NC}"
