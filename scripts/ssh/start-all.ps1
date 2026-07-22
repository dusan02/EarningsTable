# 🚀 Start All Services
# Spustí všetky služby (Web + Prisma Studio) v pozadí

Write-Host "🚀 Starting All Services..." -ForegroundColor Green

# Nastaviť environment premenné
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
$env:FINNHUB_TOKEN = "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
$env:POLYGON_API_KEY = "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"

Write-Host "📊 Environment variables set:" -ForegroundColor Yellow
Write-Host "  DATABASE_URL: $env:DATABASE_URL" -ForegroundColor Cyan
Write-Host "  FINNHUB_TOKEN: $($env:FINNHUB_TOKEN.Substring(0,10))..." -ForegroundColor Cyan
Write-Host "  POLYGON_API_KEY: $($env:POLYGON_API_KEY.Substring(0,10))..." -ForegroundColor Cyan

# Spustiť webovú aplikáciu v pozadí
Write-Host "🌐 Starting Web Application in background..." -ForegroundColor Green
Set-Location "D:\Projects\EarningsTable\modules\web"
Start-Process powershell -ArgumentList "-Command", "npm start" -WindowStyle Minimized

# Počkať chvíľu
Start-Sleep -Seconds 2

# Spustiť Prisma Studio v pozadí
Write-Host "🗄️ Starting Prisma Studio in background..." -ForegroundColor Green
Set-Location "D:\Projects\EarningsTable\modules\database"
Start-Process powershell -ArgumentList "-Command", "npx prisma studio --port 5556" -WindowStyle Minimized

# Počkať chvíľu
Start-Sleep -Seconds 3

Write-Host "✅ All services started!" -ForegroundColor Green
Write-Host "🌐 Web Application: http://localhost:5555" -ForegroundColor Cyan
Write-Host "🗄️ Prisma Studio: http://localhost:5556" -ForegroundColor Cyan
Write-Host "📊 Health Check: http://localhost:5555/health" -ForegroundColor Cyan

# Vrátiť sa do root priečinka
Set-Location "D:\Projects\EarningsTable"
