#!/bin/bash
# 🔍 Diagnose www.earningstable.com issue

echo "🔍 Diagnosing www.earningstable.com..."
echo ""

# 1. Check DNS for www
echo "📡 DNS Check:"
echo "---"
echo "www.earningstable.com:"
WWW_IP=$(dig +short www.earningstable.com)
if [ -n "$WWW_IP" ]; then
    echo "  ✅ Resolves to: $WWW_IP"
else
    echo "  ❌ Not found"
fi
echo ""

# 2. Check Nginx config for www
echo "📝 Nginx Config for www:"
grep -A 10 "server_name.*www.earningstable.com" /etc/nginx/sites-enabled/earningstable.com 2>/dev/null || echo "  ❌ No www server block found"
echo ""

# 3. Check SSL certificates
echo "🔒 SSL Certificates:"
if [ -d "/etc/letsencrypt/live/earningstable.com" ]; then
    echo "  ✅ Found: /etc/letsencrypt/live/earningstable.com"
    ls -la /etc/letsencrypt/live/earningstable.com/ 2>/dev/null | grep -E "fullchain|privkey" || echo "    ⚠️  Cert files missing"
fi
if [ -d "/etc/letsencrypt/live/www.earningstable.com" ]; then
    echo "  ✅ Found: /etc/letsencrypt/live/www.earningstable.com"
    ls -la /etc/letsencrypt/live/www.earningstable.com/ 2>/dev/null | grep -E "fullchain|privkey" || echo "    ⚠️  Cert files missing"
fi
echo ""

# 4. Test www access
echo "🧪 Testing www.earningstable.com:"
echo "---"
echo "HTTP:"
HTTP_WWW=$(curl -I -s http://www.earningstable.com/ | head -5)
echo "$HTTP_WWW"
echo ""

echo "HTTPS (with -k to ignore cert errors):"
HTTPS_WWW=$(curl -I -k -s https://www.earningstable.com/ 2>&1 | head -10)
echo "$HTTPS_WWW"
echo ""

echo "HTTPS (without -k, to see cert errors):"
HTTPS_WWW_CERT=$(curl -I -s https://www.earningstable.com/ 2>&1 | head -10)
echo "$HTTPS_WWW_CERT"
echo ""

# 5. Check Nginx error log for www
echo "📋 Recent Nginx Errors for www (last 5 lines):"
grep -i "www.earningstable.com" /var/log/nginx/error.log 2>/dev/null | tail -5 || echo "  ⚠️  No errors found or cannot read log"
echo ""

# 6. Check if certbot has www in certificate
echo "🔐 Certificate Details:"
if [ -f "/etc/letsencrypt/live/earningstable.com/fullchain.pem" ]; then
    echo "Main certificate domains:"
    openssl x509 -in /etc/letsencrypt/live/earningstable.com/fullchain.pem -noout -text 2>/dev/null | grep -A 1 "Subject Alternative Name" || echo "  ⚠️  Cannot read certificate"
fi
echo ""

echo "✅ Diagnosis complete!"
