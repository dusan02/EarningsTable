#!/bin/bash
# Find PM2 watchdog/healthcheck that sends SIGINT every 5 minutes

cd /srv/EarningsTable

echo "=== 1. Check what process 8863 is (PM2 daemon) ==="
ps aux | grep 8863 | grep -v grep
ps -fp 8863 2>/dev/null || echo "Process 8863 not found"

echo ""
echo "=== 2. Check PM2 daemon process ==="
ps aux | grep pm2 | grep -v grep

echo ""
echo "=== 3. Check PM2 configuration for earnings-table ==="
pm2 describe earnings-table

echo ""
echo "=== 4. Check PM2 ecosystem config ==="
cat ecosystem.config.js | grep -A 30 "earnings-table"

echo ""
echo "=== 5. Check PM2 logs for watchdog/healthcheck ==="
pm2 logs --lines 100 --nostream | grep -iE "watchdog|healthcheck|restart|kill|signal" | tail -20

echo ""
echo "=== 6. Check if PM2 has any timers/intervals ==="
pm2 conf earnings-table

echo ""
echo "=== 7. Check PM2 version and features ==="
pm2 --version
pm2 info

echo ""
echo "=== 8. Check systemd (if PM2 runs via systemd) ==="
systemctl status pm2-* 2>/dev/null || echo "No systemd PM2 service"

echo ""
echo "=== 9. Check cron jobs ==="
crontab -l 2>/dev/null || echo "No crontab for root"
grep -r "pm2\|earnings" /etc/cron* 2>/dev/null | head -10 || echo "No cron jobs found"

echo ""
echo "=== 10. Check if there's a PM2 watchdog script ==="
find /root -name "*watchdog*" -o -name "*healthcheck*" 2>/dev/null | head -10
find /srv -name "*watchdog*" -o -name "*healthcheck*" 2>/dev/null | head -10

