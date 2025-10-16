@echo off
echo 🚀 EarningsTable Application Restart
echo =====================================

echo 🛑 Stopping all Node.js processes...
taskkill /f /im node.exe >nul 2>&1

echo ⏳ Waiting for processes to terminate...
timeout /t 3 /nobreak >nul

echo 📁 Using database from modules/database/prisma/dev.db...

echo 🌐 Starting Web Application...
start "Web App" cmd /k "cd modules\web && set PORT=3001 && npm start"

echo 📊 Starting Prisma Studio...
start "Prisma Studio" cmd /k "cd modules\database && npm run prisma:studio"

echo ⏳ Waiting for services to start...
timeout /t 5 /nobreak >nul

echo 🎉 Restart completed!
echo.
echo 📊 Services available:
echo    - Web App: http://localhost:3001
echo    - Prisma Studio: http://localhost:5555
echo    - API: http://localhost:3001/api/earnings
echo.
pause
