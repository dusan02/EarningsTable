#!/bin/bash
# Rýchla kontrola stavu cronu

echo "=== 1. PM2 Status ==="
pm2 status earnings-cron

echo ""
echo "=== 2. Posledných 20 riadkov logov (bez čakania) ==="
pm2 logs earnings-cron --lines 20 --nostream

echo ""
echo "=== 3. Posledné cron ticky ==="
pm2 logs earnings-cron --lines 500 --nostream | grep "\[CRON\] tick" | tail -5

echo ""
echo "=== 4. Posledné pipeline behy ==="
pm2 logs earnings-cron --lines 500 --nostream | grep -E "Starting optimized pipeline|Pipeline completed|Pipeline failed" | tail -5

echo ""
echo "=== 5. Aktuálny čas (NY) ==="
TZ=America/New_York date

echo ""
echo "=== 6. Kontrola, či pipeline beží (z procesov) ==="
ps aux | grep -E "tsx|node.*main.ts|npm.*start" | grep -v grep

echo ""
echo "=== 7. Ak sa pipeline zasekol, restartni cron ==="
echo "Spusti: pm2 restart earnings-cron"

