@echo off
echo Starting EarningsTable Server...
echo.

REM Kill existing PHP processes
taskkill /f /im php.exe >nul 2>&1

REM Start PHP server
echo Server starting on http://localhost:8000
echo Dashboard: http://localhost:8000/dashboard-fixed.html
echo.
echo Press Ctrl+C to stop the server
echo.

php -S localhost:8000 -t public

pause


