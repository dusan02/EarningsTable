#!/bin/bash
# 🔍 Diagnostika Nginx location blocks

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"
PUBLIC_DIR="/var/www/earnings-table/public"

echo "🔍 Diagnostika Nginx location blocks..."
echo ""

# 1. Skontrolovať, či súbory existujú
echo "1. Kontrola súborov:"
ls -la "$PUBLIC_DIR/robots.txt" "$PUBLIC_DIR/sitemap.xml" 2>&1
echo ""

# 2. Skontrolovať permissions
echo "2. Permissions:"
stat -c "%a %U:%G %n" "$PUBLIC_DIR/robots.txt" "$PUBLIC_DIR/sitemap.xml" 2>&1
echo ""

# 3. Skontrolovať, či Nginx môže čítať súbory
echo "3. Test čitateľnosti (ako Nginx user):"
sudo -u www-data cat "$PUBLIC_DIR/robots.txt" > /dev/null 2>&1 && echo "✅ robots.txt readable" || echo "❌ robots.txt NOT readable"
sudo -u www-data cat "$PUBLIC_DIR/sitemap.xml" > /dev/null 2>&1 && echo "✅ sitemap.xml readable" || echo "❌ sitemap.xml NOT readable"
echo ""

# 4. Zobraziť location blocks
echo "4. Location blocks v konfigurácii:"
grep -A 10 "location.*robots\|location.*sitemap" "$NGINX_CONFIG"
echo ""

# 5. Skontrolovať root directive
echo "5. Root directive:"
grep "root" "$NGINX_CONFIG" | head -5
echo ""

# 6. Skontrolovať poradie location blocks
echo "6. Poradie location blocks:"
grep -n "location" "$NGINX_CONFIG" | head -10
echo ""

# 7. Test priamo cez Nginx (bez proxy)
echo "7. Test priamo cez Nginx root:"
if [ -f "$PUBLIC_DIR/robots.txt" ]; then
    echo "Testing: root + /robots.txt = $PUBLIC_DIR/robots.txt"
    if [ -f "$PUBLIC_DIR/robots.txt" ]; then
        echo "✅ File exists at expected path"
    fi
fi
echo ""

# 8. Skontrolovať, či location blocks sú v správnom server blocku
echo "8. Server block pre earningsstable.com:"
grep -B 5 -A 20 "server_name.*earningstable\.com" "$NGINX_CONFIG" | grep -A 20 "listen.*443"
echo ""

# 9. Test Express routes
echo "9. Test Express routes:"
curl -s http://localhost:5555/robots.txt | head -3
echo "---"
curl -s http://localhost:5555/sitemap.xml | head -3
echo ""

# 10. Test cez HTTPS
echo "10. Test cez HTTPS:"
curl -k -I https://earningsstable.com/robots.txt 2>&1 | head -5
echo "---"
curl -k -I https://earningsstable.com/sitemap.xml 2>&1 | head -5
echo ""

echo "✅ Diagnostika dokončená"
