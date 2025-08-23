@echo off
echo ========================================
echo CLEANUP AND SETUP TASK SCHEDULER
echo ========================================
echo.

REM Set paths
set "PROJECT_DIR=D:\Projects\EarningsTable"
set "PHP_PATH=D:\xampp\php\php.exe"

echo Project Directory: %PROJECT_DIR%
echo PHP Path: %PHP_PATH%
echo.

REM Step 1: Delete all existing EarningsTable tasks
echo Step 1: Deleting existing EarningsTable tasks...
echo.

schtasks /delete /tn "EarningsTable_CacheShares" /f 2>nul
if %errorlevel% equ 0 (
    echo ✅ Deleted: EarningsTable_CacheShares
) else (
    echo ⚠️  Task not found or already deleted: EarningsTable_CacheShares
)

schtasks /delete /tn "EarningsTable_DailyCleanup" /f 2>nul
if %errorlevel% equ 0 (
    echo ✅ Deleted: EarningsTable_DailyCleanup
) else (
    echo ⚠️  Task not found or already deleted: EarningsTable_DailyCleanup
)

schtasks /delete /tn "EarningsTable_DailySequence" /f 2>nul
if %errorlevel% equ 0 (
    echo ✅ Deleted: EarningsTable_DailySequence
) else (
    echo ⚠️  Task not found or already deleted: EarningsTable_DailySequence
)

schtasks /delete /tn "EarningsTable_FetchTickers" /f 2>nul
if %errorlevel% equ 0 (
    echo ✅ Deleted: EarningsTable_FetchTickers
) else (
    echo ⚠️  Task not found or already deleted: EarningsTable_FetchTickers
)

schtasks /delete /tn "EarningsTable_UpdateEPS" /f 2>nul
if %errorlevel% equ 0 (
    echo ✅ Deleted: EarningsTable_UpdateEPS
) else (
    echo ⚠️  Task not found or already deleted: EarningsTable_UpdateEPS
)

schtasks /delete /tn "EarningsTable_UpdatePrices" /f 2>nul
if %errorlevel% equ 0 (
    echo ✅ Deleted: EarningsTable_UpdatePrices
) else (
    echo ⚠️  Task not found or already deleted: EarningsTable_UpdatePrices
)

echo.
echo All existing tasks deleted.
echo.

REM Step 2: Create new tasks with correct configuration
echo Step 2: Creating new tasks...
echo.

REM 1. Daily Cleanup Task (02:00 AM daily)
echo Creating Daily Cleanup Task...
schtasks /create /tn "EarningsTable_DailyCleanup" /tr "%PHP_PATH% cron\clear_old_data.php" /sc daily /st 02:00 /f
if %errorlevel% equ 0 (
    echo ✅ Created: EarningsTable_DailyCleanup (02:00 AM daily)
) else (
    echo ❌ Failed to create: EarningsTable_DailyCleanup
)

REM 2. Fetch Tickers Task (02:15 AM daily)
echo Creating Fetch Tickers Task...
schtasks /create /tn "EarningsTable_FetchTickers" /tr "%PHP_PATH% cron\fetch_finnhub_earnings_today_tickers.php" /sc daily /st 02:15 /f
if %errorlevel% equ 0 (
    echo ✅ Created: EarningsTable_FetchTickers (02:15 AM daily)
) else (
    echo ❌ Failed to create: EarningsTable_FetchTickers
)

REM 3. Cache Shares Task (06:00 AM daily)
echo Creating Cache Shares Task...
schtasks /create /tn "EarningsTable_CacheShares" /tr "%PHP_PATH% cron\cache_shares_outstanding.php" /sc daily /st 06:00 /f
if %errorlevel% equ 0 (
    echo ✅ Created: EarningsTable_CacheShares (06:00 AM daily)
) else (
    echo ❌ Failed to create: EarningsTable_CacheShares
)

REM 4. Update EPS Task (every 5 minutes, starting at 00:00 daily)
echo Creating Update EPS Task...
schtasks /create /tn "EarningsTable_UpdateEPS" /tr "%PHP_PATH% cron\update_earnings_eps_revenues.php" /sc daily /st 00:00 /mo 1 /f
if %errorlevel% equ 0 (
    echo ✅ Created: EarningsTable_UpdateEPS (00:00 AM + every 5 minutes)
) else (
    echo ❌ Failed to create: EarningsTable_UpdateEPS
)

REM 5. Update Prices Task (every 5 minutes, starting at 00:00 daily)
echo Creating Update Prices Task...
schtasks /create /tn "EarningsTable_UpdatePrices" /tr "%PHP_PATH% cron\current_prices_mcaps_updates.php" /sc daily /st 00:00 /mo 1 /f
if %errorlevel% equ 0 (
    echo ✅ Created: EarningsTable_UpdatePrices (00:00 AM + every 5 minutes)
) else (
    echo ❌ Failed to create: EarningsTable_UpdatePrices
)

echo.
echo ========================================
echo TASK SCHEDULER SETUP COMPLETE
echo ========================================
echo.
echo Created tasks:
echo - EarningsTable_DailyCleanup: 02:00 AM daily
echo - EarningsTable_FetchTickers: 02:15 AM daily
echo - EarningsTable_CacheShares: 06:00 AM daily
echo - EarningsTable_UpdateEPS: 00:00 AM + every 5 minutes
echo - EarningsTable_UpdatePrices: 00:00 AM + every 5 minutes
echo.
echo To view tasks: schtasks /query /tn "EarningsTable*"
echo To view in GUI: taskschd.msc
echo.
pause
