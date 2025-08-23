@echo off
echo Starting Earnings Table cron jobs...
echo Press Ctrl+C to stop

set /a cycle=0

:loop
echo.
echo [%date% %time%] Running cron jobs... (Cycle: %cycle%)

echo Running clear_old_movements.php...
D:\xampp\php\php.exe cron\clear_old_movements.php
if %errorlevel% neq 0 echo ERROR: clear_old_movements.php failed

echo Running fetch_earnings_tickers.php...
D:\xampp\php\php.exe cron\fetch_earnings_tickers.php
if %errorlevel% neq 0 echo ERROR: fetch_earnings_tickers.php failed

echo Running update_earnings_eps_revenues.php...
D:\xampp\php\php.exe cron\update_earnings_eps_revenues.php
if %errorlevel% neq 0 echo ERROR: update_earnings_eps_revenues.php failed

echo Running current_prices_mcaps_updates.php...
D:\xampp\php\php.exe cron\current_prices_mcaps_updates.php
if %errorlevel% neq 0 echo ERROR: current_prices_mcaps_updates.php failed

REM Static data - run every 48 cycles (4 hours = 48 * 5 minutes)
set /a static_cycle=cycle %% 48
if %static_cycle%==0 (
    echo.
    echo [%date% %time%] Running static data updates (every 4 hours)...
    
    echo Running update_company_names.php...
    D:\xampp\php\php.exe cron\update_company_names.php
    if %errorlevel% neq 0 echo ERROR: update_company_names.php failed
    
    echo Running cache_shares_outstanding.php...
    D:\xampp\php\php.exe cron\cache_shares_outstanding.php
    if %errorlevel% neq 0 echo ERROR: cache_shares_outstanding.php failed
) else (
    echo.
    echo [%date% %time%] Skipping static data updates (next update in %static_cycle% cycles)
)

set /a cycle+=1

echo.
echo [%date% %time%] All cron jobs completed. Waiting 5 minutes...
timeout /t 300 /nobreak > nul
goto loop
