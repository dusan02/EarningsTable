#!/bin/bash
# Check if logs are being written to database tables

cd /srv/EarningsTable

echo "=== 1. Check CronExecutionLog table (last 20 entries) ==="
sqlite3 modules/database/prisma/prod.db << 'EOF'
SELECT 
    id,
    jobType,
    status,
    datetime(startedAt, 'localtime') as startedAt,
    datetime(completedAt, 'localtime') as completedAt,
    duration,
    recordsProcessed,
    errorMessage
FROM cron_execution_log
ORDER BY startedAt DESC
LIMIT 20;
EOF

echo ""
echo "=== 2. Check CronStatus table (current status) ==="
sqlite3 modules/database/prisma/prod.db << 'EOF'
SELECT 
    jobType,
    datetime(lastRunAt, 'localtime') as lastRunAt,
    status,
    recordsProcessed,
    errorMessage
FROM cron_status
ORDER BY lastRunAt DESC;
EOF

echo ""
echo "=== 3. Count logs by jobType (last 24 hours) ==="
sqlite3 modules/database/prisma/prod.db << 'EOF'
SELECT 
    jobType,
    status,
    COUNT(*) as count
FROM cron_execution_log
WHERE startedAt > datetime('now', '-1 day')
GROUP BY jobType, status
ORDER BY jobType, status;
EOF

echo ""
echo "=== 4. Check if logs are being written (last 1 hour) ==="
sqlite3 modules/database/prisma/prod.db << 'EOF'
SELECT 
    COUNT(*) as recent_logs
FROM cron_execution_log
WHERE startedAt > datetime('now', '-1 hour');
EOF

echo ""
echo "=== 5. Latest log entry ==="
sqlite3 modules/database/prisma/prod.db << 'EOF'
SELECT 
    id,
    jobType,
    status,
    datetime(startedAt, 'localtime') as startedAt,
    datetime(completedAt, 'localtime') as completedAt,
    duration,
    recordsProcessed
FROM cron_execution_log
ORDER BY startedAt DESC
LIMIT 1;
EOF

