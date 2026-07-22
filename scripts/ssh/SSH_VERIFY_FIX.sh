#!/bin/bash
# Verify that SIGINT fix is working

cd /srv/EarningsTable

echo "=== 1. Current PM2 Status ==="
pm2 list

echo ""
echo "=== 2. Process Details ==="
pm2 describe earnings-table | grep -E "status|restart|uptime|unstable"

echo ""
echo "=== 3. Check for SIGINT in last 10 minutes ==="
pm2 logs earnings-table --err --lines 500 --nostream | grep -iE "SIGINT|beforeExit|exit event" | tail -20

if [ $? -eq 0 ]; then
    echo "⚠️ Found SIGINT events - problem still exists"
else
    echo "✅ No SIGINT events found - fix is working!"
fi

echo ""
echo "=== 4. Recent logs (last 30 lines) ==="
pm2 logs earnings-table --lines 30 --nostream | tail -30

echo ""
echo "=== 5. Monitor for 1 minute to see if SIGINT occurs ==="
echo "Watching for SIGINT events..."
timeout 60 pm2 logs earnings-table --err --lines 0 2>&1 | grep -iE "SIGINT|beforeExit|exit event" || echo "✅ No SIGINT in last 1 minute - GOOD!"

echo ""
echo "=== 6. Final Status ==="
pm2 describe earnings-table | grep -E "restart|uptime|status"

