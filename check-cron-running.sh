#!/bin/bash
# Skript na kontrolu, či cron skutočne beží a spúšťa sa

echo "=== 1. PM2 Status ==="
pm2 status

echo ""
echo "=== 2. Posledné behy cronu (z logov) ==="
pm2 logs earnings-cron --lines 200 --nostream | grep -E "\[CRON\] tick|Pipeline|Starting optimized pipeline" | tail -10

echo ""
echo "=== 3. Čas posledného behu ==="
pm2 logs earnings-cron --lines 500 --nostream | grep "\[CRON\] tick" | tail -5

echo ""
echo "=== 4. Aktuálny čas (NY timezone) ==="
TZ=America/New_York date

echo ""
echo "=== 5. Kontrola cron expression v kóde ==="
grep -A 3 "UNIFIED_CRON" modules/cron/src/main.ts

echo ""
echo "=== 6. Realtime logy (stlač Ctrl+C pre ukončenie) ==="
echo "Čakám na ďalší cron tick..."
pm2 logs earnings-cron --lines 0 | grep --line-buffered -E "\[CRON\]|Pipeline|tick"

