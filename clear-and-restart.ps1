# 🧹 Clear Database & Cache, Restart Application & Start Cron Jobs
Write-Host "🧹 Clearing Database and Cache, Restarting Application..." -ForegroundColor Green
Write-Host "=========================================================" -ForegroundColor Green

# Set environment variables
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
$env:FINNHUB_TOKEN = "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
$env:POLYGON_API_KEY = "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
$env:CRON_TZ = "America/New_York"
$env:ALLOW_CLEAR = "true"

# Step 1: Stop all Node.js processes
Write-Host "`n🛑 Step 1: Stopping all Node.js processes..." -ForegroundColor Yellow
Get-Process -Name "node" -ErrorAction SilentlyContinue | Stop-Process -Force
Start-Sleep -Seconds 2

# Step 2: Clear database
Write-Host "`n🗄️ Step 2: Clearing database..." -ForegroundColor Yellow
Set-Location "D:\Projects\EarningsTable\modules\cron"
try {
    npx tsx -e "import('./src/core/DatabaseManager.js').then(async ({ db }) => { process.env.ALLOW_CLEAR = 'true'; await db.clearAllTables(); await db.disconnect(); console.log('✅ Database cleared'); process.exit(0); }).catch(e => { console.error(e); process.exit(1); })"
    Write-Host "✅ Database cleared successfully" -ForegroundColor Green
}
catch {
    Write-Host "❌ Error clearing database: $_" -ForegroundColor Red
    # Try alternative method
    Write-Host "Trying alternative method..." -ForegroundColor Yellow
    Set-Location "D:\Projects\EarningsTable"
    node clear-all-data.js
}

# Step 3: Clear cache files
Write-Host "`n🧹 Step 3: Clearing cache files..." -ForegroundColor Yellow
Set-Location "D:\Projects\EarningsTable"

# Remove common cache directories
$cacheDirs = @(
    "node_modules\.cache",
    "build",
    ".next",
    "dist",
    "modules\web\build",
    "modules\web\.next",
    "modules\web\dist",
    "modules\cron\build",
    "modules\cron\dist"
)

foreach ($dir in $cacheDirs) {
    if (Test-Path $dir) {
        Write-Host "  Removing: $dir" -ForegroundColor Cyan
        Remove-Item -Path $dir -Recurse -Force -ErrorAction SilentlyContinue
    }
}

# Clear npm cache
Write-Host "  Clearing npm cache..." -ForegroundColor Cyan
npm cache clean --force 2>$null

Write-Host "✅ Cache cleared" -ForegroundColor Green

# Step 4: Start application on port 3000
Write-Host "`n🌐 Step 4: Starting application on localhost:3000..." -ForegroundColor Yellow
Set-Location "D:\Projects\EarningsTable"
$env:PORT = "3000"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd D:\Projects\EarningsTable; `$env:DATABASE_URL = 'file:D:\Projects\EarningsTable\modules\database\prisma\dev.db'; `$env:FINNHUB_TOKEN = 'd28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0'; `$env:POLYGON_API_KEY = 'Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX'; `$env:PORT = '3000'; node simple-server.js"

# Wait a bit for server to start
Start-Sleep -Seconds 5

# Step 5: Start cron jobs
Write-Host "`n⏰ Step 5: Starting cron jobs..." -ForegroundColor Yellow
Set-Location "D:\Projects\EarningsTable\modules\cron"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd D:\Projects\EarningsTable\modules\cron; `$env:DATABASE_URL = 'file:D:\Projects\EarningsTable\modules\database\prisma\dev.db'; `$env:FINNHUB_TOKEN = 'd28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0'; `$env:POLYGON_API_KEY = 'Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX'; `$env:CRON_TZ = 'America/New_York'; npx tsx src/main.ts start"

# Wait a bit for cron to start
Start-Sleep -Seconds 3

# Step 6: Test cron jobs
Write-Host "`n🧪 Step 6: Testing cron jobs..." -ForegroundColor Yellow
Start-Sleep -Seconds 2

# Test by running a single job
Write-Host "  Running test job (finnhub --once)..." -ForegroundColor Cyan
Set-Location "D:\Projects\EarningsTable\modules\cron"
Start-Process powershell -ArgumentList "-Command", "cd D:\Projects\EarningsTable\modules\cron; `$env:DATABASE_URL = 'file:D:\Projects\EarningsTable\modules\database\prisma\dev.db'; `$env:FINNHUB_TOKEN = 'd28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0'; `$env:POLYGON_API_KEY = 'Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX'; `$env:CRON_TZ = 'America/New_York'; npx tsx src/main.ts run-finnhub --once; Read-Host 'Press Enter to close'"

# Return to root
Set-Location "D:\Projects\EarningsTable"

Write-Host "`n✅ All steps completed!" -ForegroundColor Green
Write-Host "`n📊 Services:" -ForegroundColor Cyan
Write-Host "   - Web App: http://localhost:3000" -ForegroundColor White
Write-Host "   - API: http://localhost:3000/api/final-report" -ForegroundColor White
Write-Host "   - Health: http://localhost:3000/health" -ForegroundColor White
Write-Host "`n⏰ Cron jobs are running in separate windows" -ForegroundColor Cyan
Write-Host "`nPress Enter to exit..." -ForegroundColor Yellow
Read-Host
