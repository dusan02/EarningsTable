# 🚀 Start Web Application
# Spustí webovú aplikáciu s environment premennými

Write-Host "🌐 Starting Web Application..." -ForegroundColor Green

# Nastaviť environment premenné
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
$env:FINNHUB_TOKEN = "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
$env:POLYGON_API_KEY = "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"

# Prejsť do web modulu
Set-Location "D:\Projects\EarningsTable\modules\web"

Write-Host "📊 Environment variables set:" -ForegroundColor Yellow
Write-Host "  DATABASE_URL: $env:DATABASE_URL" -ForegroundColor Cyan
Write-Host "  FINNHUB_TOKEN: $($env:FINNHUB_TOKEN.Substring(0,10))..." -ForegroundColor Cyan
Write-Host "  POLYGON_API_KEY: $($env:POLYGON_API_KEY.Substring(0,10))..." -ForegroundColor Cyan

Write-Host "🚀 Starting web server on http://localhost:5555" -ForegroundColor Green
Write-Host "📊 Earnings table will be available at http://localhost:5555" -ForegroundColor Green

# Spustiť webovú aplikáciu
npm start
