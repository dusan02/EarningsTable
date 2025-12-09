#!/bin/bash
# Real-time monitoring for SIGINT events with detailed logging

cd /srv/EarningsTable

echo "🔍 Monitoring earnings-table for SIGINT events..."
echo "Looking for: SIGINT, beforeExit, exit, Keep-alive heartbeat"
echo "Press Ctrl+C to stop"
echo ""

# Monitor both stdout and stderr, filter for important events
pm2 logs earnings-table --lines 0 2>&1 | grep --line-buffered -iE "SIGINT|beforeExit|exit event|Keep-alive|heartbeat|Shutting down|Stack trace|uptime|Memory usage" || true

