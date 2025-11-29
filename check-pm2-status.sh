#!/bin/bash
# Kontrola PM2 stavu a nastavenia cronu

echo "=== 1. Všetky PM2 procesy ==="
pm2 list

echo ""
echo "=== 2. PM2 info ==="
pm2 info

echo ""
echo "=== 3. PM2 saved processes ==="
pm2 save --force 2>/dev/null
cat ~/.pm2/dump.pm2 2>/dev/null || echo "No PM2 dump file"

echo ""
echo "=== 4. Kontrola ecosystem.config.js ==="
if [ -f "ecosystem.config.js" ]; then
    cat ecosystem.config.js | grep -A 10 "earnings-cron"
else
    echo "ecosystem.config.js not found"
fi

echo ""
echo "=== 5. Kontrola systemd (ak existuje) ==="
systemctl list-units | grep -i earnings || echo "No systemd units found"

echo ""
echo "=== 6. Kontrola crontab ==="
crontab -l 2>/dev/null | grep -i earnings || echo "No crontab entries for earnings"

echo ""
echo "=== 7. Bežiace procesy s 'cron' alebo 'earnings' ==="
ps aux | grep -E "cron|earnings|tsx.*main" | grep -v grep

echo ""
echo "=== 8. Kontrola, či existuje cron modul ==="
if [ -d "modules/cron" ]; then
    echo "modules/cron exists"
    ls -la modules/cron/src/main.ts 2>/dev/null || echo "main.ts not found"
else
    echo "modules/cron not found"
fi

