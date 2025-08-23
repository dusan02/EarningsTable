@echo off
echo ========================================
echo WINDOWS TASK SCHEDULER SETUP
echo ========================================
echo.

REM Set paths
set "PROJECT_DIR=D:\Projects\EarningsTable"
set "PHP_PATH=D:\xampp\php\php.exe"
set "CLEANUP_SCRIPT=%PROJECT_DIR%\cron\clear_old_data.php"
set "FETCH_SCRIPT=%PROJECT_DIR%\cron\fetch_finnhub_earnings_today_tickers.php"

echo Project Directory: %PROJECT_DIR%
echo PHP Path: %PHP_PATH%
echo.

REM Create daily cleanup task (runs at 02:00 AM NY time)
echo Creating daily cleanup task...
schtasks /create /tn "EarningsTable_DailyCleanup" /tr "%PHP_PATH% %CLEANUP_SCRIPT%" /sc daily /st 02:00 /f
if %errorlevel% equ 0 (
    echo ✅ Daily cleanup task created successfully
) else (
    echo ❌ Failed to create daily cleanup task
)

echo.

REM Create fetch task (runs at 02:05 AM NY time, after cleanup)
echo Creating fetch task...
schtasks /create /tn "EarningsTable_FetchTickers" /tr "%PHP_PATH% %FETCH_SCRIPT%" /sc daily /st 02:05 /f
if %errorlevel% equ 0 (
    echo ✅ Fetch task created successfully
) else (
    echo ❌ Failed to create fetch task
)

echo.
echo ========================================
echo TASK SCHEDULER SETUP COMPLETE
echo ========================================
echo.
echo Tasks created:
echo - EarningsTable_DailyCleanup: Daily at 02:00 AM
echo - EarningsTable_FetchTickers: Daily at 02:05 AM
echo.
echo To view tasks: schtasks /query /tn "EarningsTable*"
echo To delete tasks: schtasks /delete /tn "EarningsTable_DailyCleanup" /f
echo To delete tasks: schtasks /delete /tn "EarningsTable_FetchTickers" /f
echo.
pause
