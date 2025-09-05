@echo off
echo ========================================
echo EarningsTable VPS Connection Script
echo ========================================
echo.
echo VPS Details:
echo IP: 89.185.250.213
echo Login: root
echo Password: EJXTfBOG2t
echo.
echo VNC Console:
echo Host: 89.185.250.242
echo Port: 5903
echo Password: 2uSI25ci
echo.
echo ========================================
echo.
echo Connecting to VPS...
echo.
echo After connecting, run these commands step by step:
echo 1. Copy commands from: deploy/manual-deploy-commands.txt
echo 2. Paste them one by one into SSH session
echo 3. Enter password when prompted: EJXTfBOG2t
echo.

ssh root@89.185.250.213

echo.
echo ========================================
echo SSH session ended
echo ========================================
pause
