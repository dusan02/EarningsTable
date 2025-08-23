@echo off
echo ========================================
echo COMPLETE DAILY EARNINGS DATA SEQUENCE
echo ========================================
echo.

REM Set paths
set "PROJECT_DIR=D:\Projects\EarningsTable"
set "PHP_PATH=D:\xampp\php\php.exe"
set "CLEANUP_SCRIPT=%PROJECT_DIR%\cron\clear_old_data.php"
set "FINNHUB_SCRIPT=%PROJECT_DIR%\cron\fetch_finnhub_earnings_today_tickers.php"
set "YAHOO_MISSING_SCRIPT=%PROJECT_DIR%\cron\fetch_missing_tickers_yahoo.php"
set "MARKET_DATA_SCRIPT=%PROJECT_DIR%\cron\fetch_market_data_complete.php"
set "LOG_FILE=%PROJECT_DIR%\storage\daily_run_complete.log"

REM Create storage directory if it doesn't exist
if not exist "%PROJECT_DIR%\storage" mkdir "%PROJECT_DIR%\storage"

REM Get current timestamp
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YY=%dt:~2,2%" & set "YYYY=%dt:~0,4%" & set "MM=%dt:~4,2%" & set "DD=%dt:~6,2%"
set "HH=%dt:~8,2%" & set "Min=%dt:~10,2%" & set "Sec=%dt:~12,2%"
set "timestamp=%YYYY%-%MM%-%DD% %HH%:%Min%:%Sec%"

echo [%timestamp%] Starting complete daily sequence >> "%LOG_FILE%"
echo [%timestamp%] Starting complete daily sequence

REM Step 1: 02:00h - Cleanup old data
echo.
echo Step 1 (02:00h): Cleaning old data...
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

REM Wait 30 minutes (simulate 02:30h)
echo.
echo Waiting 30 minutes for 02:30h...
echo [%timestamp%] Waiting for 02:30h... >> "%LOG_FILE%"
timeout /t 1800 /nobreak > nul

REM Step 2: 02:30h - Fetch Finnhub tickers
echo.
echo Step 2 (02:30h): Fetching Finnhub tickers...
echo [%timestamp%] Running Finnhub fetch... >> "%LOG_FILE%"
"%PHP_PATH%" "%FINNHUB_SCRIPT%" >> "%LOG_FILE%" 2>&1
if %errorlevel% equ 0 (
    echo ✅ Finnhub fetch completed successfully
    echo [%timestamp%] Finnhub fetch completed successfully >> "%LOG_FILE%"
) else (
    echo ❌ Finnhub fetch failed with error code %errorlevel%
    echo [%timestamp%] Finnhub fetch failed with error code %errorlevel% >> "%LOG_FILE%"
    exit /b %errorlevel%
)

REM Wait 10 minutes (simulate 02:40h)
echo.
echo Waiting 10 minutes for 02:40h...
echo [%timestamp%] Waiting for 02:40h... >> "%LOG_FILE%"
timeout /t 600 /nobreak > nul

REM Step 3: 02:40h - Fetch missing Yahoo Finance tickers
echo.
echo Step 3 (02:40h): Fetching missing Yahoo Finance tickers...
echo [%timestamp%] Running Yahoo missing tickers fetch... >> "%LOG_FILE%"
"%PHP_PATH%" "%YAHOO_MISSING_SCRIPT%" >> "%LOG_FILE%" 2>&1
if %errorlevel% equ 0 (
    echo ✅ Yahoo missing tickers fetch completed successfully
    echo [%timestamp%] Yahoo missing tickers fetch completed successfully >> "%LOG_FILE%"
) else (
    echo ❌ Yahoo missing tickers fetch failed with error code %errorlevel%
    echo [%timestamp%] Yahoo missing tickers fetch failed with error code %errorlevel% >> "%LOG_FILE%"
    exit /b %errorlevel%
)

REM Wait 20 minutes (simulate 03:00h)
echo.
echo Waiting 20 minutes for 03:00h...
echo [%timestamp%] Waiting for 03:00h... >> "%LOG_FILE%"
timeout /t 1200 /nobreak > nul

REM Step 4: 03:00h - Fetch complete market data
echo.
echo Step 4 (03:00h): Fetching complete market data...
echo [%timestamp%] Running complete market data fetch... >> "%LOG_FILE%"
"%PHP_PATH%" "%MARKET_DATA_SCRIPT%" >> "%LOG_FILE%" 2>&1
if %errorlevel% equ 0 (
    echo ✅ Complete market data fetch completed successfully
    echo [%timestamp%] Complete market data fetch completed successfully >> "%LOG_FILE%"
) else (
    echo ❌ Complete market data fetch failed with error code %errorlevel%
    echo [%timestamp%] Complete market data fetch failed with error code %errorlevel% >> "%LOG_FILE%"
    exit /b %errorlevel%
)

echo.
echo ========================================
echo COMPLETE DAILY SEQUENCE COMPLETED SUCCESSFULLY
echo ========================================
echo Log file: %LOG_FILE%
echo [%timestamp%] Complete daily sequence completed successfully >> "%LOG_FILE%"
