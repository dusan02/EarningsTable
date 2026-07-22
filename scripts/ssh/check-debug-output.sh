#!/bin/bash
# Skript na kontrolu debug výstupu

cd /srv/EarningsTable

echo "=== 1. Stiahnutie najnovších zmien ==="
git pull origin main

echo ""
echo "=== 2. Reštartovanie PM2 servera ==="
pm2 restart earnings-table
sleep 2

echo ""
echo "=== 3. Volanie API a kontrola logov ==="
curl http://localhost:5555/api/final-report > /dev/null
sleep 1

echo ""
echo "=== 4. Zobrazenie posledných logov ==="
pm2 logs earnings-table --lines 50 --nostream | tail -50

echo ""
echo "=== 5. Hľadanie debug výstupu ==="
pm2 logs earnings-table --lines 200 --nostream | grep -E "DEBUG|Total records|First 5 symbols|with marketCap" | tail -20

