#!/bin/bash
# Create table if missing and check logs

cd /srv/EarningsTable

echo "=== 1. Check if table exists ==="
sqlite3 modules/database/prisma/prod.db ".tables" | grep cron

echo ""
echo "=== 2. Create CronExecutionLog table if missing ==="
sqlite3 modules/database/prisma/prod.db << 'EOF'
CREATE TABLE IF NOT EXISTS "cron_execution_log" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "jobType" TEXT NOT NULL,
    "status" TEXT NOT NULL,
    "startedAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "completedAt" DATETIME,
    "duration" INTEGER,
    "recordsProcessed" INTEGER,
    "errorMessage" TEXT,
    "createdAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "cron_execution_log_jobType_startedAt_idx" ON "cron_execution_log"("jobType", "startedAt");
CREATE INDEX IF NOT EXISTS "cron_execution_log_startedAt_idx" ON "cron_execution_log"("startedAt");
CREATE INDEX IF NOT EXISTS "cron_execution_log_status_idx" ON "cron_execution_log"("status");
EOF

echo "✅ Table created/verified"

echo ""
echo "=== 3. Check all logs ==="
sqlite3 -header -column modules/database/prisma/prod.db "SELECT id, jobType, status, datetime(startedAt, 'localtime') as startedAt, datetime(completedAt, 'localtime') as completedAt, duration, recordsProcessed FROM cron_execution_log ORDER BY startedAt DESC LIMIT 20;"

echo ""
echo "=== 4. Current CronStatus ==="
sqlite3 -header -column modules/database/prisma/prod.db "SELECT jobType, datetime(lastRunAt, 'localtime') as lastRunAt, status, recordsProcessed, errorMessage FROM cron_status ORDER BY lastRunAt DESC;"

echo ""
echo "=== 5. Recent logs count (last 1h) ==="
sqlite3 modules/database/prisma/prod.db "SELECT COUNT(*) as recent_logs FROM cron_execution_log WHERE startedAt > datetime('now', '-1 hour');"

