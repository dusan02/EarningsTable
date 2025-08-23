@echo off
echo Testing PHP and cron scripts manually...
echo.

echo 1. Testing PHP availability...
where php
if %errorlevel% neq 0 (
    echo PHP not found in PATH
    echo Testing common PHP locations...
    if exist "D:\xampp\php\php.exe" (
        echo Found PHP at: D:\xampp\php\php.exe
        set PHP_PATH=D:\xampp\php\php.exe
    ) else if exist "C:\xampp\php\php.exe" (
        echo Found PHP at: C:\xampp\php\php.exe
        set PHP_PATH=C:\xampp\php\php.exe
    ) else (
        echo PHP not found in common locations!
        pause
        exit /b 1
    )
) else (
    set PHP_PATH=php
)

echo.
echo 2. Testing PHP version...
%PHP_PATH% -v
if %errorlevel% neq 0 (
    echo PHP execution failed!
    pause
    exit /b 1
)

echo.
echo 3. Testing database connection...
%PHP_PATH% -r "try { $pdo = new PDO('mysql:host=localhost;dbname=earnings_table', 'root', ''); echo 'Database connection: OK'; } catch (Exception $e) { echo 'Database connection failed: ' . $e->getMessage(); }"

echo.
echo 4. Testing cron scripts...
echo Testing earnings fetch...
%PHP_PATH% cron\fetch_earnings_tickers.php
echo.

echo Testing prices update...
%PHP_PATH% cron\current_prices_mcaps_updates.php
echo.

echo Testing EPS update...
%PHP_PATH% cron\update_earnings_eps_revenues.php
echo.

echo All tests completed!
pause
