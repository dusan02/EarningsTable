#!/bin/bash
# Check if workaround is working

cd /srv/EarningsTable

echo "=== 1. Current Status ==="
pm2 list
pm2 describe earnings-table | grep -E "restart|uptime|status"

echo ""
echo "=== 2. Check for SIGINT events in last 20 minutes ==="
pm2 logs earnings-table --err --lines 1000 --nostream | grep -iE "SIGINT|Ignoring SIGINT" | tail -20

echo ""
echo "=== 3. Check recent logs for 'Ignoring SIGINT' messages ==="
pm2 logs earnings-table --err --lines 500 --nostream | grep -A 3 "Ignoring SIGINT" | tail -30

echo ""
echo "=== 4. Check if process is running stable (last 5 minutes) ==="
pm2 logs earnings-table --lines 100 --nostream | tail -20

echo ""
echo "=== 5. Monitor for 1 minute to see SIGINT behavior ==="
echo "If SIGINT occurs, you should see 'Ignoring SIGINT' message"
timeout 60 pm2 logs earnings-table --err --lines 0 2>&1 | grep -iE "SIGINT|Ignoring" || echo "No SIGINT events in last 1 minute"

