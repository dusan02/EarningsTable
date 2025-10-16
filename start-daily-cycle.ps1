# Start Daily Cycle Manager
Write-Host "🚀 Starting Daily Cycle Manager..." -ForegroundColor Green
Write-Host "📅 Schedule:" -ForegroundColor Yellow
Write-Host "  🧹 03:00 - Clear database tables" -ForegroundColor Cyan
Write-Host "  📊 03:05 - Start Finnhub → Polygon sequence" -ForegroundColor Cyan
Write-Host "  🔄 03:10+ - Both crons every 5 minutes until 02:30" -ForegroundColor Cyan
Write-Host "  🧹 03:00 - Repeat cycle (clear tables)" -ForegroundColor Cyan
Write-Host ""

# Set environment variables
$env:FINNHUB_TOKEN = "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
$env:POLYGON_API_KEY = "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
$env:DATABASE_URL = "file:D:/Projects/EarningsTable/modules/database/prisma/dev.db"

# Change to cron directory
Set-Location "modules/cron"

# Start the daily cycle manager
npm run daily-cycle
