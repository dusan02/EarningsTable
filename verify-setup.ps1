# ✅ Verify Setup - Check if everything is running correctly
Write-Host "🔍 Verifying Setup..." -ForegroundColor Green
Write-Host "====================" -ForegroundColor Green

# Check if server is running on port 3000
Write-Host "`n🌐 Checking server on port 3000..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:3000/health" -TimeoutSec 5 -UseBasicParsing
    Write-Host "✅ Server is running on port 3000" -ForegroundColor Green
    Write-Host "   Status Code: $($response.StatusCode)" -ForegroundColor Cyan
} catch {
    Write-Host "❌ Server is not responding on port 3000" -ForegroundColor Red
    Write-Host "   Error: $_" -ForegroundColor Red
}

# Check API endpoint
Write-Host "`n📊 Checking API endpoint..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:3000/api/final-report" -TimeoutSec 5 -UseBasicParsing
    $data = $response.Content | ConvertFrom-Json
    Write-Host "✅ API is responding" -ForegroundColor Green
    Write-Host "   Records in database: $($data.Count)" -ForegroundColor Cyan
} catch {
    Write-Host "⚠️ API might not be ready yet" -ForegroundColor Yellow
    Write-Host "   Error: $_" -ForegroundColor Yellow
}

# Check Node.js processes
Write-Host "`n🔄 Checking Node.js processes..." -ForegroundColor Yellow
$nodeProcesses = Get-Process -Name "node" -ErrorAction SilentlyContinue
if ($nodeProcesses) {
    Write-Host "✅ Found $($nodeProcesses.Count) Node.js process(es)" -ForegroundColor Green
    $nodeProcesses | ForEach-Object {
        Write-Host "   PID: $($_.Id) | Started: $($_.StartTime)" -ForegroundColor Cyan
    }
} else {
    Write-Host "⚠️ No Node.js processes found" -ForegroundColor Yellow
}

# Check database
Write-Host "`n🗄️ Checking database..." -ForegroundColor Yellow
$dbPath = "D:\Projects\EarningsTable\modules\database\prisma\dev.db"
if (Test-Path $dbPath) {
    $dbSize = (Get-Item $dbPath).Length / 1KB
    Write-Host "✅ Database file exists" -ForegroundColor Green
    Write-Host "   Size: $([math]::Round($dbSize, 2)) KB" -ForegroundColor Cyan
} else {
    Write-Host "❌ Database file not found" -ForegroundColor Red
}

Write-Host "`n📋 Summary:" -ForegroundColor Cyan
Write-Host "   - Server: http://localhost:3000" -ForegroundColor White
Write-Host "   - API: http://localhost:3000/api/final-report" -ForegroundColor White
Write-Host "   - Health: http://localhost:3000/health" -ForegroundColor White
Write-Host "`n✅ Verification complete!" -ForegroundColor Green
