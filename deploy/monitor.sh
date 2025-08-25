#!/bin/bash

# 📊 MONITORING SCRIPT
# Sledovanie stavu aplikácie po deploymente

set -e

# Konfigurácia
SITE_URL="http://localhost"
LOG_FILE="/var/log/earnings-table-monitor.log"
ALERT_EMAIL="admin@earningstable.com"

echo "📊 Starting application monitoring..."

# 1. Kontrola dostupnosti webu
echo "🌐 Checking website availability..."
if curl -f -s "$SITE_URL" > /dev/null; then
    echo "✅ Website is accessible"
    WEBSITE_STATUS="OK"
else
    echo "❌ Website is not accessible"
    WEBSITE_STATUS="ERROR"
fi

# 2. Kontrola databázového pripojenia
echo "🗄️ Checking database connection..."
if curl -f -s "$SITE_URL/test-db.php" > /dev/null; then
    echo "✅ Database connection is working"
    DB_STATUS="OK"
else
    echo "❌ Database connection failed"
    DB_STATUS="ERROR"
fi

# 3. Kontrola API endpointov
echo "🌐 Checking API endpoints..."
if curl -f -s "$SITE_URL/api/status" > /dev/null 2>&1; then
    echo "✅ API endpoints are working"
    API_STATUS="OK"
else
    echo "⚠️ API endpoints may have issues"
    API_STATUS="WARNING"
fi

# 4. Kontrola cron jobov
echo "⏰ Checking cron jobs..."
if pgrep -f "cron" > /dev/null; then
    echo "✅ Cron jobs are running"
    CRON_STATUS="OK"
else
    echo "❌ Cron jobs are not running"
    CRON_STATUS="ERROR"
fi

# 5. Kontrola disk space
echo "💾 Checking disk space..."
DISK_USAGE=$(df -h /var/www | tail -1 | awk '{print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -lt 80 ]; then
    echo "✅ Disk space is sufficient ($DISK_USAGE%)"
    DISK_STATUS="OK"
else
    echo "⚠️ Disk space is low ($DISK_USAGE%)"
    DISK_STATUS="WARNING"
fi

# 6. Kontrola memory usage
echo "🧠 Checking memory usage..."
MEMORY_USAGE=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100.0}')
if [ "$MEMORY_USAGE" -lt 80 ]; then
    echo "✅ Memory usage is normal ($MEMORY_USAGE%)"
    MEMORY_STATUS="OK"
else
    echo "⚠️ Memory usage is high ($MEMORY_USAGE%)"
    MEMORY_STATUS="WARNING"
fi

# 7. Generovanie reportu
echo "📋 Generating monitoring report..."
REPORT="
=== EARNINGS TABLE MONITORING REPORT ===
Time: $(date)
Website: $WEBSITE_STATUS
Database: $DB_STATUS
API: $API_STATUS
Cron Jobs: $CRON_STATUS
Disk Usage: $DISK_USAGE% ($DISK_STATUS)
Memory Usage: $MEMORY_USAGE% ($MEMORY_STATUS)
"

echo "$REPORT"

# 8. Logovanie
echo "[$(date)] Monitoring completed - Website: $WEBSITE_STATUS, DB: $DB_STATUS, API: $API_STATUS" >> "$LOG_FILE"

# 9. Alerting pri problémoch
if [[ "$WEBSITE_STATUS" == "ERROR" || "$DB_STATUS" == "ERROR" || "$CRON_STATUS" == "ERROR" ]]; then
    echo "🚨 CRITICAL ISSUES DETECTED!"
    echo "$REPORT" | mail -s "🚨 EarningsTable Alert - Critical Issues" "$ALERT_EMAIL"
    exit 1
elif [[ "$WEBSITE_STATUS" == "WARNING" || "$API_STATUS" == "WARNING" || "$DISK_STATUS" == "WARNING" || "$MEMORY_STATUS" == "WARNING" ]]; then
    echo "⚠️ Warnings detected - monitoring closely"
fi

echo "✅ Monitoring completed successfully!"
