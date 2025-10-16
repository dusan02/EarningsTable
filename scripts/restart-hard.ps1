# Hard reset script for Windows PowerShell
# Stops services, deletes DB file, recreates schema, starts services

Write-Host "🔄 Hard Restart - Complete reset..." -ForegroundColor Yellow

# 1) Stop services
Write-Host "🛑 Stopping services..." -ForegroundColor Red
pm2 stop earnings-web 2>$null
pm2 stop earnings-cron 2>$null

# 2) Delete DB file
Write-Host "🗑️ Deleting database file..." -ForegroundColor Red
if (Test-Path "modules\database\prisma\dev.db") {
    Remove-Item "modules\database\prisma\dev.db" -Force
    Write-Host "✅ Database file deleted" -ForegroundColor Green
} else {
    Write-Host "⚠️ Database file not found" -ForegroundColor Yellow
}

# 3) Recreate schema
Write-Host "🧩 Recreating database schema..." -ForegroundColor Blue
cd modules\database
npx prisma migrate deploy
cd ..\..

# 4) Start services
Write-Host "🚀 Starting services..." -ForegroundColor Green
pm2 start dist\web\server.js --name earnings-web -- --port 3001
pm2 start dist\cron\main.js --name earnings-cron

Write-Host "✅ Hard restart complete." -ForegroundColor Green
