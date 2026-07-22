#!/bin/bash
# 🚀 Rýchla kontrola logov - jednoduchý príkaz

echo "=== PM2 Status ==="
pm2 list

echo ""
echo "=== Posledných 30 riadkov z Cron logov ==="
pm2 logs earnings-cron --lines 30 --nostream | tail -30

echo ""
echo "=== Posledných 20 riadkov z Error logov (Cron) ==="
pm2 logs earnings-cron --lines 20 --nostream --err | tail -20

echo ""
echo "=== Posledných 20 riadkov z Web Server logov ==="
pm2 logs earnings-table --lines 20 --nostream | tail -20

echo ""
echo "=== Posledné chyby (Cron) ==="
pm2 logs earnings-cron --lines 100 --nostream | grep -iE "error|failed|❌" | tail -10

echo ""
echo "=== Posledné pipeline behy ==="
pm2 logs earnings-cron --lines 100 --nostream | grep -iE "pipeline|tick" | tail -10

