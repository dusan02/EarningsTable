#!/bin/bash
# 🔍 Diagnose Nginx HTTPS Configuration
# Checks why HTTPS returns 404

set -e

echo "🔍 Diagnosing Nginx HTTPS Configuration..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    SUDO=""
else
    SUDO="sudo"
fi

# 1. Check Nginx status
echo -e "${BLUE}1. Nginx Status${NC}"
echo "----------------"
if systemctl is-active --quiet nginx; then
    echo -e "${GREEN}✅ Nginx is running${NC}"
    systemctl status nginx --no-pager -l | head -5
else
    echo -e "${RED}❌ Nginx is NOT running${NC}"
    echo "Start it with: systemctl start nginx"
fi
echo ""

# 2. Check Nginx config files
echo -e "${BLUE}2. Nginx Configuration Files${NC}"
echo "----------------------------"
NGINX_AVAILABLE="/etc/nginx/sites-available"
NGINX_ENABLED="/etc/nginx/sites-enabled"

echo "Available configs:"
ls -la $NGINX_AVAILABLE/ 2>/dev/null | grep -E "earnings|default" || echo "  No earnings config found"

echo ""
echo "Enabled configs:"
ls -la $NGINX_ENABLED/ 2>/dev/null | grep -v "^total" || echo "  No enabled configs found"
echo ""

# 3. Check if earningsstable.com is in config
echo -e "${BLUE}3. Checking for earningsstable.com in Nginx configs${NC}"
echo "---------------------------------------------------"
CONFIG_FOUND=false
for config in $NGINX_AVAILABLE/* $NGINX_ENABLED/*; do
    if [ -f "$config" ]; then
        if grep -q "earningsstable.com" "$config" 2>/dev/null; then
            echo -e "${GREEN}✅ Found in: $config${NC}"
            echo "  Server blocks:"
            grep -A 5 "server_name.*earningsstable" "$config" 2>/dev/null | head -10 || true
            CONFIG_FOUND=true
        fi
    fi
done

if [ "$CONFIG_FOUND" = false ]; then
    echo -e "${RED}❌ earningsstable.com not found in any Nginx config${NC}"
fi
echo ""

# 4. Check SSL certificates
echo -e "${BLUE}4. SSL Certificates${NC}"
echo "------------------"
SSL_CERT="/etc/letsencrypt/live/earningsstable.com/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/earningsstable.com/privkey.pem"

if [ -f "$SSL_CERT" ]; then
    echo -e "${GREEN}✅ SSL cert found: $SSL_CERT${NC}"
    openssl x509 -in "$SSL_CERT" -noout -subject -dates 2>/dev/null | head -2 || true
else
    echo -e "${YELLOW}⚠️  Let's Encrypt cert not found${NC}"
    echo "  Looking for other SSL certs..."
    find /etc/nginx -name "*.pem" -o -name "*.crt" 2>/dev/null | head -5 || echo "  No SSL certs found"
fi
echo ""

# 5. Test Nginx config
echo -e "${BLUE}5. Nginx Configuration Test${NC}"
echo "---------------------------"
if $SUDO nginx -t 2>&1; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
fi
echo ""

# 6. Check what's listening on ports
echo -e "${BLUE}6. Port Listening Status${NC}"
echo "----------------------"
echo "Port 80 (HTTP):"
netstat -tuln 2>/dev/null | grep ":80 " || ss -tuln 2>/dev/null | grep ":80 " || echo "  Not listening"

echo ""
echo "Port 443 (HTTPS):"
netstat -tuln 2>/dev/null | grep ":443 " || ss -tuln 2>/dev/null | grep ":443 " || echo "  Not listening"

echo ""
echo "Port 5555 (App):"
netstat -tuln 2>/dev/null | grep ":5555 " || ss -tuln 2>/dev/null | grep ":5555 " || echo "  Not listening"
echo ""

# 7. Test direct app access (bypassing Nginx)
echo -e "${BLUE}7. Testing Direct App Access (Port 5555)${NC}"
echo "----------------------------------------"
echo -n "  http://localhost:5555: "
APP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:5555/ 2>/dev/null || echo "000")
if [ "$APP_STATUS" = "200" ]; then
    echo -e "${GREEN}✅ HTTP ${APP_STATUS}${NC}"
else
    echo -e "${RED}❌ HTTP ${APP_STATUS}${NC}"
fi

echo -n "  http://localhost:5555/robots.txt: "
ROBOTS_LOCAL=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:5555/robots.txt 2>/dev/null || echo "000")
if [ "$ROBOTS_LOCAL" = "200" ]; then
    echo -e "${GREEN}✅ HTTP ${ROBOTS_LOCAL}${NC}"
else
    echo -e "${RED}❌ HTTP ${ROBOTS_LOCAL}${NC}"
fi

echo -n "  http://localhost:5555/api/health: "
HEALTH_LOCAL=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:5555/api/health 2>/dev/null || echo "000")
if [ "$HEALTH_LOCAL" = "200" ]; then
    echo -e "${GREEN}✅ HTTP ${HEALTH_LOCAL}${NC}"
else
    echo -e "${RED}❌ HTTP ${HEALTH_LOCAL}${NC}"
fi
echo ""

# 8. Check Nginx access/error logs
echo -e "${BLUE}8. Recent Nginx Logs${NC}"
echo "-------------------"
if [ -f "/var/log/nginx/error.log" ]; then
    echo "Last 5 error log entries:"
    tail -5 /var/log/nginx/error.log 2>/dev/null || $SUDO tail -5 /var/log/nginx/error.log 2>/dev/null || echo "  Cannot read error log"
else
    echo "  Error log not found"
fi

echo ""
if [ -f "/var/log/nginx/access.log" ]; then
    echo "Last 5 access log entries:"
    tail -5 /var/log/nginx/access.log 2>/dev/null || $SUDO tail -5 /var/log/nginx/access.log 2>/dev/null || echo "  Cannot read access log"
else
    echo "  Access log not found"
fi
echo ""

# 9. Summary and recommendations
echo -e "${BLUE}9. Summary & Recommendations${NC}"
echo "=============================="
echo ""

if [ "$APP_STATUS" = "200" ]; then
    echo -e "${GREEN}✅ App is working on port 5555${NC}"
    echo "  → Problem is likely in Nginx configuration"
    echo ""
    echo "Next steps:"
    echo "  1. Check Nginx config for earningsstable.com"
    echo "  2. Ensure server_name matches earningsstable.com"
    echo "  3. Check if HTTPS server block exists"
    echo "  4. If no HTTPS block, add one or run: ./update-nginx-seo.sh"
else
    echo -e "${RED}❌ App is NOT working on port 5555${NC}"
    echo "  → Problem is in the application, not Nginx"
    echo ""
    echo "Next steps:"
    echo "  1. Check PM2 logs: pm2 logs earnings-table"
    echo "  2. Restart app: pm2 restart earnings-table"
    echo "  3. Check if app is listening: netstat -tuln | grep 5555"
fi
echo ""

