# PowerShell script pre manuálne vytvorenie CronExecutionLog tabuľky
# Použije Prisma Studio alebo sqlite3

$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
$dbPath = "D:\Projects\EarningsTable\modules\database\prisma\dev.db"

Write-Host "DATABASE_URL set to: $env:DATABASE_URL" -ForegroundColor Green

Write-Host "`nCreating CronExecutionLog table..." -ForegroundColor Yellow
Write-Host "`nOption 1: Use Prisma Studio (Recommended)" -ForegroundColor Cyan
Write-Host "Run: npx prisma studio" -ForegroundColor Yellow
Write-Host "Then go to Database tab → Run SQL and paste the SQL below:" -ForegroundColor Yellow

$sql = @"
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
"@

Write-Host "`nSQL to run:" -ForegroundColor Cyan
Write-Host $sql -ForegroundColor White

Write-Host "`nOption 2: Use sqlite3 CLI (if installed)" -ForegroundColor Cyan
Write-Host "sqlite3 `"$dbPath`" `"$sql`"" -ForegroundColor Yellow

Write-Host "`nAfter creating table, run:" -ForegroundColor Cyan
Write-Host "npx prisma generate" -ForegroundColor Yellow

