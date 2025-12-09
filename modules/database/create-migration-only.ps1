# PowerShell script pre vytvorenie migrácie bez resetu
# Vytvorí len migračný súbor, neaplikuje ho

$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
Write-Host "DATABASE_URL set to: $env:DATABASE_URL" -ForegroundColor Green

Write-Host "`nCreating migration file only (without applying)..." -ForegroundColor Yellow
npx prisma migrate dev --create-only --name add_cron_execution_log

Write-Host "`n✅ Migration file created!" -ForegroundColor Green
Write-Host "Now you can review the migration SQL and apply it manually if needed." -ForegroundColor Yellow

