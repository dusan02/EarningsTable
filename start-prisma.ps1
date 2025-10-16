# 🗄️ Start Prisma Studio
# Spustí Prisma Studio s environment premennými

Write-Host "🗄️ Starting Prisma Studio..." -ForegroundColor Green

# Nastaviť environment premenné
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"

# Prejsť do database modulu
Set-Location "D:\Projects\EarningsTable\modules\database"

Write-Host "📊 Environment variables set:" -ForegroundColor Yellow
Write-Host "  DATABASE_URL: $env:DATABASE_URL" -ForegroundColor Cyan

Write-Host "🚀 Starting Prisma Studio on http://localhost:5556" -ForegroundColor Green
Write-Host "📊 Database tables: FinhubData, PolygonData, FinalReport" -ForegroundColor Green

# Spustiť Prisma Studio
npx prisma studio --port 5556
