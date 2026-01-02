#!/bin/bash
# 🔍 Diagnose DNS and server status

echo "🔍 Diagnosing DNS and server status..."
echo ""

# 1. Check DNS for both domain variants
echo "📡 DNS Check:"
echo "---"
echo "earningsstable.com (two 's'):"
dig +short earningsstable.com || echo "  ❌ Not found"
echo ""
echo "earningstable.com (one 's'):"
dig +short earningstable.com || echo "  ❌ Not found"
echo ""

# 2. Check server IP
echo "🖥️  Server IP:"
hostname -I | awk '{print $1}'
echo ""

# 3. Check if Nginx is running
echo "🌐 Nginx Status:"
systemctl status nginx --no-pager -l | head -5 || echo "  ❌ Nginx not running"
echo ""

# 4. Check if Express/PM2 is running
echo "🚀 PM2 Status:"
pm2 list 2>/dev/null || echo "  ❌ PM2 not running"
echo ""

# 5. Check Nginx config syntax
echo "⚙️  Nginx Config Test:"
nginx -t 2>&1 | tail -3
echo ""

# 6. Check SSL certificates
echo "🔒 SSL Certificates:"
if [ -d "/etc/letsencrypt/live/earningstable.com" ]; then
    echo "  ✅ Found: /etc/letsencrypt/live/earningstable.com (one 's')"
    ls -la /etc/letsencrypt/live/earningstable.com/ 2>/dev/null | grep -E "fullchain|privkey" || echo "    ⚠️  Cert files missing"
fi
if [ -d "/etc/letsencrypt/live/earningsstable.com" ]; then
    echo "  ✅ Found: /etc/letsencrypt/live/earningsstable.com (two 's')"
    ls -la /etc/letsencrypt/live/earningsstable.com/ 2>/dev/null | grep -E "fullchain|privkey" || echo "    ⚠️  Cert files missing"
fi
if [ ! -d "/etc/letsencrypt/live/earningstable.com" ] && [ ! -d "/etc/letsencrypt/live/earningsstable.com" ]; then
    echo "  ❌ No SSL certificates found"
fi
echo ""

# 7. Check Nginx server_name in config
echo "📝 Nginx server_name in config:"
grep "server_name" /etc/nginx/sites-enabled/earningstable.com 2>/dev/null | head -5 || echo "  ❌ Config file not found"
echo ""

# 8. Check if ports are listening
echo "🔌 Port Status:"
netstat -tlnp 2>/dev/null | grep -E ":80 |:443 |:5555 " || ss -tlnp 2>/dev/null | grep -E ":80 |:443 |:5555 " || echo "  ⚠️  Cannot check ports"
echo ""

# 9. Test local access
echo "🧪 Local Access Test:"
curl -k -s -o /dev/null -w "HTTPS (earningsstable.com): %{http_code}\n" https://earningsstable.com/ 2>/dev/null || echo "  ❌ Cannot connect"
curl -k -s -o /dev/null -w "HTTPS (earningstable.com): %{http_code}\n" https://earningstable.com/ 2>/dev/null || echo "  ❌ Cannot connect"
echo ""

# 10. Check Nginx error log
echo "📋 Recent Nginx Errors (last 5 lines):"
tail -5 /var/log/nginx/error.log 2>/dev/null || echo "  ⚠️  Cannot read error log"
echo ""

echo "✅ Diagnosis complete!"
