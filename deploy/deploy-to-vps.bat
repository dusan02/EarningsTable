@echo off
echo ========================================
echo EarningsTable VPS Auto Deployment
echo ========================================
echo.
echo VPS Details:
echo IP: 89.185.250.213
echo Login: root
echo.
echo This script will automatically deploy EarningsTable to your VPS.
echo.
echo Prerequisites:
echo - Git Bash or WSL installed
echo - sshpass installed (for password authentication)
echo.
echo ========================================
echo.

REM Check if sshpass is available
where sshpass >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: sshpass is not installed!
    echo.
    echo Please install sshpass:
    echo 1. Install Git Bash: https://git-scm.com/downloads
    echo 2. Or install WSL: wsl --install
    echo 3. Then install sshpass: sudo apt install sshpass
    echo.
    pause
    exit /b 1
)

echo Starting deployment...
echo.

REM Make the script executable and run it
bash deploy/auto-deploy.sh

echo.
echo ========================================
echo Deployment completed!
echo ========================================
echo.
echo Your EarningsTable should now be available at:
echo http://89.185.250.213/dashboard-fixed.php
echo.
echo Next steps:
echo 1. Update API keys in /var/www/html/EarningsTable/.env
echo 2. Configure DNS records
echo 3. Setup SSL certificate
echo.
pause
