@echo off
echo ========================================
echo 5-MINUTE UPDATES - POLYGON & FINNHUB
echo ========================================
echo.

REM Set paths
set "PROJECT_DIR=D:\Projects\EarningsTable"
set "PHP_PATH=D:\xampp\php\php.exe"
set "UPDATE_SCRIPT=%PROJECT_DIR%\cron\run_5min_updates.php"
set "LOG_FILE=%PROJECT_DIR%\storage\5min_updates.log"

REM Create storage directory if it doesn't exist
if not exist "%PROJECT_DIR%\storage" mkdir "%PROJECT_DIR%\storage"

REM Get current timestamp
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YY=%dt:~2,2%" & set "YYYY=%dt:~0,4%" & set "MM=%dt:~4,2%" & set "DD=%dt:~6,2%"
set "HH=%dt:~8,2%" & set "Min=%dt:~10,2%" & set "Sec=%dt:~12,2%"
set "timestamp=%YYYY%-%MM%-%DD% %HH%:%Min%:%Sec%"

echo [%timestamp%] Starting 5-minute updates >> "%LOG_FILE%"
echo [%timestamp%] Starting 5-minute updates

REM Run the 5-minute updates
echo Running 5-minute updates...
echo [%timestamp%] Running 5-minute updates... >> "%LOG_FILE%"
"%PHP_PATH%" "%UPDATE_SCRIPT%" >> "%LOG_FILE%" 2>&1

if %errorlevel% equ 0 (
    echo ✅ 5-minute updates completed successfully
    echo [%timestamp%] 5-minute updates completed successfully >> "%LOG_FILE%"
) else (
    echo ❌ 5-minute updates failed with error code %errorlevel%
    echo [%timestamp%] 5-minute updates failed with error code %errorlevel% >> "%LOG_FILE%"
    exit /b %errorlevel%
)

echo.
echo ========================================
echo 5-MINUTE UPDATES COMPLETED
echo ========================================
echo Log file: %LOG_FILE%
echo [%timestamp%] 5-minute updates completed >> "%LOG_FILE%"
