#!/bin/bash
# Monitor detailed logs to find SIGINT source

cd /srv/EarningsTable

echo "=== 1. Checking if new code is deployed ==="
git log --oneline -1
echo ""

echo "=== 2. Restarting earnings-table to ensure latest code is running ==="
pm2 restart earnings-table
sleep 3

echo "=== 3. Current PM2 status ==="
pm2 list
echo ""

echo "=== 4. Monitoring logs in real-time (will show SIGINT details when it happens) ==="
echo "Press Ctrl+C to stop monitoring"
echo ""
echo "Watching for: SIGINT, beforeExit, exit, Keep-alive heartbeat"
echo ""

# Monitor logs and filter for important events
pm2 logs earnings-table --lines 0 --nostream | tail -50

echo ""
echo "=== 5. Starting real-time monitoring (last 30 seconds) ==="
timeout 30 pm2 logs earnings-table --lines 0 2>&1 | grep -iE "SIGINT|beforeExit|exit|Keep-alive|heartbeat|Shutting down" || echo "No matching events in last 30 seconds"

echo ""
echo "=== 6. Check recent stderr for detailed SIGINT info ==="
pm2 logs earnings-table --err --lines 100 --nostream | grep -iE "SIGINT|beforeExit|exit|Stack trace|uptime|Memory" | tail -20

echo ""
echo "=== 7. Check if process is still running ==="
pm2 describe earnings-table | grep -E "status|restarts|uptime"

