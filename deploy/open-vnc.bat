@echo off
echo ========================================
echo EarningsTable VNC Console Access
echo ========================================
echo.
echo VNC Details:
echo Host: 89.185.250.242
echo Port: 5903
echo Password: 2uSI25ci
echo.
echo This will open VNC console to your VPS.
echo You can then run deployment commands directly.
echo.
echo ========================================
echo.

REM Try to open VNC viewer
echo Opening VNC connection...
echo.

REM Check if VNC viewer is available
where vncviewer >nul 2>nul
if %errorlevel% equ 0 (
    echo Using VNC Viewer...
    vncviewer 89.185.250.242:5903
) else (
    echo VNC Viewer not found. Please install one of these:
    echo.
    echo 1. RealVNC Viewer: https://www.realvnc.com/download/viewer/
    echo 2. TightVNC: https://www.tightvnc.com/download.php
    echo 3. UltraVNC: https://www.uvnc.com/downloads/ultravnc.html
    echo.
    echo Or use online VNC: https://www.vncviewer.com/
    echo.
    echo Manual connection:
    echo Host: 89.185.250.242
    echo Port: 5903
    echo Password: 2uSI25ci
    echo.
    pause
)

echo.
echo ========================================
echo VNC session ended
echo ========================================
pause

