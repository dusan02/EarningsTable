#!/bin/bash
# Find which process is sending SIGINT to earnings-table

cd /srv/EarningsTable

echo "=== 1. Get current PID of earnings-table ==="
PID=$(pm2 jlist | grep -A 5 "earnings-table" | grep "pid" | head -1 | cut -d: -f2 | tr -d ' ,"')
echo "PID: $PID"

if [ -z "$PID" ]; then
    echo "❌ Could not find PID"
    exit 1
fi

echo ""
echo "=== 2. Check parent process (PM2 daemon) ==="
PPID=$(ps -o ppid= -p $PID | tr -d ' ')
echo "Parent PID: $PPID"
ps -fp $PPID

echo ""
echo "=== 3. Check PM2 daemon process ==="
ps aux | grep pm2 | grep -v grep

echo ""
echo "=== 4. Check PM2 version ==="
pm2 --version

echo ""
echo "=== 5. Check PM2 internal logs for watchdog/healthcheck ==="
pm2 logs PM2 --lines 200 --nostream 2>&1 | grep -iE "watchdog|healthcheck|earnings-table|kill|signal|restart" | tail -30

echo ""
echo "=== 6. Check systemd (if PM2 runs via systemd) ==="
systemctl status pm2-* 2>/dev/null || echo "No systemd PM2 service"
journalctl -u pm2* -n 100 --no-pager 2>/dev/null | grep -iE "earnings|signal|kill" | tail -20 || echo "No relevant systemd logs"

echo ""
echo "=== 7. Check cron jobs ==="
crontab -l 2>/dev/null | grep -iE "pm2|earnings|kill" || echo "No relevant cron jobs"
grep -r "pm2\|earnings" /etc/cron* 2>/dev/null | head -10 || echo "No cron jobs found"

echo ""
echo "=== 8. Monitor process with strace (if available) ==="
if command -v strace &> /dev/null; then
    echo "Monitoring process $PID for 30 seconds..."
    timeout 30 strace -p $PID -e trace=signal 2>&1 | grep -iE "SIGINT|kill" || echo "No SIGINT signals caught (process may have restarted)"
else
    echo "strace not available"
fi

echo ""
echo "=== 9. Check if PM2 has any timers/intervals configured ==="
pm2 conf earnings-table | grep -iE "timer|interval|watchdog|healthcheck" || echo "No timers found in config"

echo ""
echo "=== 10. Check PM2 ecosystem config ==="
cat ecosystem.config.js | grep -A 20 "earnings-table"

