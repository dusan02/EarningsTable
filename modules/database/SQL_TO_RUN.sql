-- SQL pre vytvorenie CronExecutionLog tabuľky
-- Spustiť v Prisma Studio alebo sqlite3 CLI

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

