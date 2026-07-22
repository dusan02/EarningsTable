#!/bin/bash
# Pull latest code with detailed logging and monitor SIGINT events

cd /srv/EarningsTable

echo "=== 1. Stashing local changes (if any) ==="
git stash push -u -m "Stash before pull $(date +%Y%m%d_%H%M%S)" 2>/dev/null || true

echo ""
echo "=== 2. Pulling latest code ==="
git pull origin main

echo ""
echo "=== 3. Verifying new commit ==="
git log --oneline -1

echo ""
echo "=== 4. Restarting earnings-table ==="
pm2 restart earnings-table
sleep 5

echo ""
echo "=== 5. Current PM2 status ==="
pm2 list

echo ""
echo "=== 6. Recent logs (last 50 lines) ==="
pm2 logs earnings-table --lines 50 --nostream | tail -50

echo ""
echo "=== 7. Monitoring for SIGINT events (30 seconds) ==="
echo "Looking for: SIGINT, beforeExit, exit, Keep-alive, heartbeat, Stack trace"
echo ""
timeout 30 pm2 logs earnings-table --lines 0 2>&1 | grep -iE "SIGINT|beforeExit|exit event|Keep-alive|heartbeat|Stack trace|uptime|Memory usage|Shutting down" || echo "No matching events in last 30 seconds"

echo ""
echo "=== 8. Check stderr for detailed logs ==="
pm2 logs earnings-table --err --lines 100 --nostream | grep -iE "SIGINT|beforeExit|exit|Stack trace|uptime|Memory" | tail -20

echo ""
echo "=== 9. Process status ==="
pm2 describe earnings-table | grep -E "status|restarts|uptime|memory"

echo ""
echo "=== Done ==="
echo "To continue monitoring, run:"
echo "  pm2 logs earnings-table --lines 0 2>&1 | grep -iE 'SIGINT|beforeExit|exit|Keep-alive|heartbeat|Stack trace'"

