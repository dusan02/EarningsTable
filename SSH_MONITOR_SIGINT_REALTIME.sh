#!/bin/bash
# Monitor SIGINT events in real-time

cd /srv/EarningsTable

echo "🔍 Monitoring earnings-table for SIGINT events..."
echo "Looking for: SIGINT, beforeExit, exit, Keep-alive heartbeat, Stack trace"
echo ""
echo "Current status:"
pm2 list | grep earnings-table
echo ""
echo "Starting real-time monitoring (press Ctrl+C to stop)..."
echo ""

# Monitor logs and filter for important events
pm2 logs earnings-table --lines 0 2>&1 | grep --line-buffered -iE "SIGINT|beforeExit|exit event|Keep-alive|heartbeat|Stack trace|uptime|Memory usage|Shutting down|ReferenceError|Error" || true

