@echo off
echo ========================================
echo DAILY EARNINGS DATA SEQUENCE
echo ========================================
echo.

REM Set paths
set "PROJECT_DIR=D:\Projects\EarningsTable"
set "PHP_PATH=D:\xampp\php\php.exe"
set "CLEANUP_SCRIPT=%PROJECT_DIR%\cron\clear_old_data.php"
set "FETCH_SCRIPT=%PROJECT_DIR%\cron\fetch_finnhub_earnings_today_tickers.php"
set "LOG_FILE=%PROJECT_DIR%\storage\daily_run.log"

REM Create storage directory if it doesn't exist
if not exist "%PROJECT_DIR%\storage" mkdir "%PROJECT_DIR%\storage"

REM Get current timestamp
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YY=%dt:~2,2%" & set "YYYY=%dt:~0,4%" & set "MM=%dt:~4,2%" & set "DD=%dt:~6,2%"
set "HH=%dt:~8,2%" & set "Min=%dt:~10,2%" & set "Sec=%dt:~12,2%"
set "timestamp=%YYYY%-%MM%-%DD% %HH%:%Min%:%Sec%"

echo [%timestamp%] Starting daily sequence >> "%LOG_FILE%"
echo [%timestamp%] Starting daily sequence

REM Step 1: Cleanup old data
echo.
echo Step 1: Cleaning old data...
echo [%timestamp%] Running cleanup... >> "%LOG_FILE%"
"%PHP_PATH%" "%CLEANUP_SCRIPT%" >> "%LOG_FILE%" 2>&1
if %errorlevel% equ 0 (
    echo ✅ Cleanup completed successfully
    echo [%timestamp%] Cleanup completed successfully >> "%LOG_FILE%"
) else (
    echo ❌ Cleanup failed with error code %errorlevel%
    echo [%timestamp%] Cleanup failed with error code %errorlevel% >> "%LOG_FILE%"
    exit /b %errorlevel%
)

REM Step 2: Fetch new data
echo.
echo Step 2: Fetching new data...
echo [%timestamp%] Running fetch... >> "%LOG_FILE%"
"%PHP_PATH%" "%FETCH_SCRIPT%" >> "%LOG_FILE%" 2>&1
if %errorlevel% equ 0 (
    echo ✅ Fetch completed successfully
    echo [%timestamp%] Fetch completed successfully >> "%LOG_FILE%"
) else (
    echo ❌ Fetch failed with error code %errorlevel%
    echo [%timestamp%] Fetch failed with error code %errorlevel% >> "%LOG_FILE%"
    exit /b %errorlevel%
)

echo.
echo ========================================
echo DAILY SEQUENCE COMPLETED SUCCESSFULLY
echo ========================================
echo Log file: %LOG_FILE%
echo [%timestamp%] Daily sequence completed successfully >> "%LOG_FILE%"
