# 🕐 Run All Cron Jobs
# Spustí všetky cron úlohy postupne s environment premennými

Write-Host "🕐 Starting All Cron Jobs..." -ForegroundColor Green

# Nastaviť environment premenné
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
$env:FINNHUB_TOKEN = "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
$env:POLYGON_API_KEY = "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"

# Prejsť do cron modulu
Set-Location "D:\Projects\EarningsTable\modules\cron"

Write-Host "📊 Environment variables set:" -ForegroundColor Yellow
Write-Host "  DATABASE_URL: $env:DATABASE_URL" -ForegroundColor Cyan
Write-Host "  FINNHUB_TOKEN: $($env:FINNHUB_TOKEN.Substring(0,10))..." -ForegroundColor Cyan
Write-Host "  POLYGON_API_KEY: $($env:POLYGON_API_KEY.Substring(0,10))..." -ForegroundColor Cyan

Write-Host "🚀 Running all cron jobs with statistics..." -ForegroundColor Green
Write-Host "📋 Jobs: 1. Reset → 2. Finnhub → 3. Polygon → 4. Final Report" -ForegroundColor Green

# Spustiť všetky cron úlohy s štatistikami
npm run run-all
