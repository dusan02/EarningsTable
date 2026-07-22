#!/bin/bash
# Diagnose why logs are not being written

cd /srv/EarningsTable

echo "=== 1. Check if cron job is running ==="
pm2 list | grep earnings-cron

echo ""
echo "=== 2. Check cron job logs (last 50 lines) ==="
pm2 logs earnings-cron --lines 50 --nostream | tail -30

echo ""
echo "=== 3. Check for errors in cron job ==="
pm2 logs earnings-cron --err --lines 100 --nostream | grep -iE "error|failed|CronExecutionLog|updateCronStatus" | tail -20

echo ""
echo "=== 4. Check CronStatus table (raw data) ==="
sqlite3 -header -column modules/database/prisma/prod.db "SELECT * FROM cron_status;"

echo ""
echo "=== 5. Check if cron job is writing to database ==="
sqlite3 modules/database/prisma/prod.db "SELECT COUNT(*) as total_logs FROM cron_execution_log;"

echo ""
echo "=== 6. Check cron job process ==="
ps aux | grep -E "earnings-cron|tsx.*main.ts" | grep -v grep

