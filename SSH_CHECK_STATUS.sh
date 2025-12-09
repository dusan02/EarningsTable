#!/bin/bash
# Check current status and recent logs

cd /srv/EarningsTable

echo "=== 1. PM2 Status ==="
pm2 list

echo ""
echo "=== 2. Process Info ==="
pm2 describe earnings-table | grep -E "status|restarts|uptime|memory|pid"

echo ""
echo "=== 3. Recent stdout logs (last 30 lines) ==="
pm2 logs earnings-table --lines 30 --nostream | tail -30

echo ""
echo "=== 4. Recent stderr logs (last 50 lines) - SIGINT should be here ==="
pm2 logs earnings-table --err --lines 50 --nostream | tail -50

echo ""
echo "=== 5. All logs with SIGINT/beforeExit/exit (last 100 lines) ==="
pm2 logs earnings-table --lines 100 --nostream 2>&1 | grep -iE "SIGINT|beforeExit|exit event|Keep-alive|heartbeat|Stack trace|uptime|Memory|Shutting down" | tail -30

echo ""
echo "=== 6. Check if process is restarting ==="
echo "Restart count:"
pm2 describe earnings-table | grep "restart time"
echo ""
echo "Current uptime:"
pm2 describe earnings-table | grep "uptime"

