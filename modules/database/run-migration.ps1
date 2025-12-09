# PowerShell script pre Prisma migráciu
# Nastaví DATABASE_URL a spustí migráciu

$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
Write-Host "DATABASE_URL set to: $env:DATABASE_URL" -ForegroundColor Green

Write-Host "`nRunning Prisma migration..." -ForegroundColor Yellow
npx prisma migrate dev --name add_cron_execution_log

Write-Host "`nGenerating Prisma Client..." -ForegroundColor Yellow
npx prisma generate

Write-Host "`n✅ Migration complete!" -ForegroundColor Green

