# Run cron jobs from correct directory
Write-Host "🚀 Starting cron jobs..." -ForegroundColor Green

# Set environment variables
$env:FINNHUB_TOKEN = "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
$env:POLYGON_API_KEY = "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
$env:DATABASE_URL = "file:D:/Projects/EarningsTable/modules/database/prisma/dev.db"

# Change to cron directory
Set-Location "modules/cron"

Write-Host "📊 Running Finnhub cron job..." -ForegroundColor Yellow
npm run finnhub_data:once

Write-Host "📈 Running Polygon cron job..." -ForegroundColor Yellow
npm run polygon_data:once

Write-Host "✅ Cron jobs completed!" -ForegroundColor Green

# Return to root directory
Set-Location "../.."