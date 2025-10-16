# EarningsTable Application Restart Script
Write-Host "🚀 EarningsTable Application Restart" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green

# Stop all Node.js processes
Write-Host "🛑 Stopping all Node.js processes..." -ForegroundColor Yellow
Get-Process -Name "node" -ErrorAction SilentlyContinue | Stop-Process -Force
Start-Sleep -Seconds 2

# Database is now in modules/database/prisma/dev.db
Write-Host "📁 Using database from modules/database/prisma/dev.db..." -ForegroundColor Yellow

# Start Web Application
Write-Host "🌐 Starting Web Application..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd modules\web; `$env:PORT=3001; npm start"

# Start Prisma Studio
Write-Host "📊 Starting Prisma Studio..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd modules\database; npm run prisma:studio"

# Wait for services to start
Write-Host "⏳ Waiting for services to start..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

Write-Host "🎉 Restart completed!" -ForegroundColor Green
Write-Host ""
Write-Host "📊 Services available:" -ForegroundColor Cyan
Write-Host "   - Web App: http://localhost:3001" -ForegroundColor White
Write-Host "   - Prisma Studio: http://localhost:5555" -ForegroundColor White
Write-Host "   - API: http://localhost:3001/api/earnings" -ForegroundColor White
Write-Host ""
Read-Host "Press Enter to continue"
