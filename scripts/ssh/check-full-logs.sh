#!/bin/bash
# Skript na kontrolu celých logov

cd /srv/EarningsTable

echo "=== 1. Volanie API ==="
curl http://localhost:5555/api/final-report > /dev/null
sleep 1

echo ""
echo "=== 2. Celé logy (posledných 100 riadkov) ==="
pm2 logs earnings-table --lines 100 --nostream | tail -100

echo ""
echo "=== 3. Hľadanie všetkých debug správ ==="
pm2 logs earnings-table --lines 500 --nostream | grep -i "debug\|total\|first\|marketcap\|found.*records" | tail -30

