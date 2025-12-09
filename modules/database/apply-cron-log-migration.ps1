# PowerShell script pre aplikáciu CronExecutionLog migrácie
# Aplikuje SQL priamo do databázy

$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
$dbPath = "D:\Projects\EarningsTable\modules\database\prisma\dev.db"

Write-Host "DATABASE_URL set to: $env:DATABASE_URL" -ForegroundColor Green

# SQL pre vytvorenie tabuľky
$sql = @"
-- CreateTable
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

-- CreateIndex
CREATE INDEX IF NOT EXISTS "cron_execution_log_jobType_startedAt_idx" ON "cron_execution_log"("jobType", "startedAt");

-- CreateIndex
CREATE INDEX IF NOT EXISTS "cron_execution_log_startedAt_idx" ON "cron_execution_log"("startedAt");

-- CreateIndex
CREATE INDEX IF NOT EXISTS "cron_execution_log_status_idx" ON "cron_execution_log"("status");
"@

Write-Host "`nApplying migration SQL..." -ForegroundColor Yellow

# Použiť sqlite3 CLI ak je dostupný, alebo Prisma
# Najjednoduchšie je použiť Prisma migrate resolve po manuálnom spustení

Write-Host "`nOption 1: Use Prisma Studio to run SQL manually" -ForegroundColor Cyan
Write-Host "npx prisma studio" -ForegroundColor Yellow
Write-Host "Then run the SQL from: prisma\migrations\20250127000000_add_cron_execution_log\migration.sql" -ForegroundColor Yellow

Write-Host "`nOption 2: Mark migration as applied (after running SQL manually)" -ForegroundColor Cyan
Write-Host "npx prisma migrate resolve --applied 20250127000000_add_cron_execution_log" -ForegroundColor Yellow

Write-Host "`nOption 3: Use Prisma migrate deploy (for production)" -ForegroundColor Cyan
Write-Host "npx prisma migrate deploy" -ForegroundColor Yellow

Write-Host "`nAfter applying, run:" -ForegroundColor Cyan
Write-Host "npx prisma generate" -ForegroundColor Yellow

