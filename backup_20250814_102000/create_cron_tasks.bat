@echo off
echo Creating EarningsTable cron tasks...
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: This script must be run as Administrator!
    echo Right-click on this file and select "Run as administrator"
    pause
    exit /b 1
)

echo Running as Administrator - OK
echo.

REM Remove existing tasks
echo Removing existing tasks...
schtasks /delete /tn "EarningsTable_FetchTickers" /f 2>nul
schtasks /delete /tn "EarningsTable_UpdatePrices" /f 2>nul
schtasks /delete /tn "EarningsTable_UpdateEPS" /f 2>nul
schtasks /delete /tn "EarningsTable_CacheShares" /f 2>nul
echo Existing tasks removed.
echo.

REM Create new tasks
echo Creating new tasks...

REM 1. Fetch Tickers Task (daily at 02:15)
echo Creating EarningsTable_FetchTickers...
schtasks /create /tn "EarningsTable_FetchTickers" /tr "D:\xampp\php\php.exe cron\fetch_earnings_tickers.php" /sc daily /st 02:15 /ru "LEGION\dusan" /rl highest /f
if %errorLevel% equ 0 (
    echo ✓ EarningsTable_FetchTickers created successfully
) else (
    echo ✗ Failed to create EarningsTable_FetchTickers
)

REM 2. Update Prices Task (every 5 minutes)
echo Creating EarningsTable_UpdatePrices...
schtasks /create /tn "EarningsTable_UpdatePrices" /tr "D:\xampp\php\php.exe cron\current_prices_mcaps_updates.php" /sc minute /mo 5 /ru "LEGION\dusan" /rl highest /f
if %errorLevel% equ 0 (
    echo ✓ EarningsTable_UpdatePrices created successfully
) else (
    echo ✗ Failed to create EarningsTable_UpdatePrices
)

REM 3. Update EPS Task (every 5 minutes)
echo Creating EarningsTable_UpdateEPS...
schtasks /create /tn "EarningsTable_UpdateEPS" /tr "D:\xampp\php\php.exe cron\update_earnings_eps_revenues.php" /sc minute /mo 5 /ru "LEGION\dusan" /rl highest /f
if %errorLevel% equ 0 (
    echo ✓ EarningsTable_UpdateEPS created successfully
) else (
    echo ✗ Failed to create EarningsTable_UpdateEPS
)

REM 4. Cache Shares Task (daily at 06:00)
echo Creating EarningsTable_CacheShares...
schtasks /create /tn "EarningsTable_CacheShares" /tr "D:\xampp\php\php.exe cron\cache_shares_outstanding.php" /sc daily /st 06:00 /ru "LEGION\dusan" /rl highest /f
if %errorLevel% equ 0 (
    echo ✓ EarningsTable_CacheShares created successfully
) else (
    echo ✗ Failed to create EarningsTable_CacheShares
)

echo.
echo Task creation completed!
echo.
echo Created tasks:
echo   - EarningsTable_FetchTickers (02:15 daily)
echo   - EarningsTable_UpdatePrices (every 5 minutes)
echo   - EarningsTable_UpdateEPS (every 5 minutes)
echo   - EarningsTable_CacheShares (06:00 daily)
echo.
echo To view tasks: taskschd.msc
echo.
pause
