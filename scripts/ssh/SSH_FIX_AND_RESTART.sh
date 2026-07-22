#!/bin/bash
# Fix PM2 process and restart with new code

cd /srv/EarningsTable

echo "=== 1. Checking PM2 status ==="
pm2 list

echo ""
echo "=== 2. Stopping all processes ==="
pm2 stop all

echo ""
echo "=== 3. Verifying latest code ==="
git log --oneline -1

echo ""
echo "=== 4. Starting earnings-table ==="
pm2 start ecosystem.config.js --only earnings-table

echo ""
echo "=== 5. Waiting 5 seconds ==="
sleep 5

echo ""
echo "=== 6. PM2 status ==="
pm2 list

echo ""
echo "=== 7. Recent logs (last 30 lines) ==="
pm2 logs earnings-table --lines 30 --nostream | tail -30

echo ""
echo "=== 8. Checking for errors ==="
pm2 logs earnings-table --err --lines 20 --nostream | tail -20

echo ""
echo "=== Done ==="
echo "To monitor in real-time, run:"
echo "  pm2 logs earnings-table --lines 0"

