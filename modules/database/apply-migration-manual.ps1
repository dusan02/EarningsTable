# PowerShell script pre manuálnu aplikáciu migrácie
# Aplikuje len novú tabuľku CronExecutionLog

$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
Write-Host "DATABASE_URL set to: $env:DATABASE_URL" -ForegroundColor Green

Write-Host "`nApplying migration manually..." -ForegroundColor Yellow

# Použiť Prisma migrate resolve na označenie migrácie ako aplikovanej
# Alebo aplikovať SQL priamo
Write-Host "`nOption 1: Mark migration as applied (if SQL was run manually)" -ForegroundColor Cyan
Write-Host "npx prisma migrate resolve --applied add_cron_execution_log" -ForegroundColor Yellow

Write-Host "`nOption 2: Apply migration SQL directly to database" -ForegroundColor Cyan
Write-Host "The migration SQL is in: prisma\migrations\20250127000000_add_cron_execution_log\migration.sql" -ForegroundColor Yellow

Write-Host "`nAfter applying, run:" -ForegroundColor Cyan
Write-Host "npx prisma generate" -ForegroundColor Yellow

