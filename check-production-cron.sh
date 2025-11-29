#!/bin/bash
# Príkazy na kontrolu stavu cronu na produkcii

echo "=== 1. PM2 Status ==="
pm2 status

echo ""
echo "=== 2. Cron Proces Info ==="
pm2 describe earnings-cron

echo ""
echo "=== 3. Cron Logs (posledných 50 riadkov) ==="
pm2 logs earnings-cron --lines 50 --nostream

echo ""
echo "=== 4. Kontrola aktuálneho kódu (cron expression) ==="
cd /var/www/earnings-table/modules/cron
grep -A 5 "UNIFIED_CRON" src/main.ts | head -10

echo ""
echo "=== 5. Cron Status (z aplikácie) ==="
cd /var/www/earnings-table/modules/cron
npm run status 2>/dev/null || echo "Status command failed"

echo ""
echo "=== 6. Posledné behy (z logov) ==="
pm2 logs earnings-cron --lines 100 --nostream | grep -E "\[CRON\]|Pipeline|tick" | tail -20

echo ""
echo "=== 7. Čas posledného behu ==="
pm2 logs earnings-cron --lines 200 --nostream | grep -E "\[CRON\] tick" | tail -5

echo ""
echo "=== 8. Kontrola časového pásma ==="
echo "Server timezone: $(date)"
echo "NY timezone: $(TZ=America/New_York date)"
echo "CRON_TZ env: $(pm2 show earnings-cron | grep CRON_TZ || echo 'Not found')"

echo ""
echo "=== 9. Kontrola, či beží proces ==="
ps aux | grep -E "tsx.*main.ts|earnings-cron" | grep -v grep

echo ""
echo "=== 10. PM2 Monitor (realtime) ==="
echo "Spusti: pm2 monit"
echo "Alebo: pm2 logs earnings-cron --lines 0"

