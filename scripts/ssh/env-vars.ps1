# 🔑 Environment Variables Setup
# Nastaví všetky potrebné environment premenné

Write-Host "Setting up Environment Variables..." -ForegroundColor Green

# Nastaviť environment premenné
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
$env:FINNHUB_TOKEN = "YOUR_FINNHUB_TOKEN_HERE"
$env:POLYGON_API_KEY = "YOUR_POLYGON_API_KEY_HERE"
$env:ALLOW_PREV_CLOSE_FALLBACK = "true"
$env:CRON_TZ = "America/New_York"
$env:PORT = "3001"
$env:NODE_ENV = "development"

Write-Host "✅ Environment variables set:" -ForegroundColor Green
Write-Host "  DATABASE_URL: $env:DATABASE_URL" -ForegroundColor Cyan
Write-Host "  FINNHUB_TOKEN: $($env:FINNHUB_TOKEN.Substring(0,10))..." -ForegroundColor Cyan
Write-Host "  POLYGON_API_KEY: $($env:POLYGON_API_KEY.Substring(0,10))..." -ForegroundColor Cyan
Write-Host "  CRON_TZ: $env:CRON_TZ" -ForegroundColor Cyan
Write-Host "  PORT: $env:PORT" -ForegroundColor Cyan
Write-Host "  NODE_ENV: $env:NODE_ENV" -ForegroundColor Cyan

Write-Host "🚀 Ready to run services!" -ForegroundColor Green
