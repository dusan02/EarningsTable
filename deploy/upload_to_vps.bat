@echo off
REM 🚀 VPS Upload Script for EarningsTable
REM Windows batch script to upload project to VPS

echo 🚀 EarningsTable VPS Upload Script
echo ==================================

REM Get VPS details
set /p VPS_IP="Enter VPS IP address: "
set /p VPS_USER="Enter VPS username (usually 'root'): "
set /p DOMAIN="Enter domain name: "

echo.
echo 📁 Uploading project files...

REM Create temporary directory structure
if not exist "temp_upload" mkdir temp_upload
if not exist "temp_upload\earningstable" mkdir temp_upload\earningstable

REM Copy project files (excluding unnecessary files)
xcopy /E /I /Y "public" "temp_upload\earningstable\public"
xcopy /E /I /Y "common" "temp_upload\earningstable\common"
xcopy /E /I /Y "config" "temp_upload\earningstable\config"
xcopy /E /I /Y "cron" "temp_upload\earningstable\cron"
xcopy /E /I /Y "sql" "temp_upload\earningstable\sql"
xcopy /E /I /Y "utils" "temp_upload\earningstable\utils"
xcopy /E /I /Y "scripts" "temp_upload\earningstable\scripts"
xcopy /E /I /Y "deploy" "temp_upload\earningstable\deploy"
xcopy /E /I /Y "vendor" "temp_upload\earningstable\vendor"
xcopy /E /I /Y "logs" "temp_upload\earningstable\logs"
xcopy /E /I /Y "storage" "temp_upload\earningstable\storage"

REM Copy individual files
copy "composer.json" "temp_upload\earningstable\"
copy "composer.lock" "temp_upload\earningstable\"
copy "README.md" "temp_upload\earningstable\"
copy "web.config" "temp_upload\earningstable\"

REM Create upload package
echo 📦 Creating upload package...
cd temp_upload
tar -czf earningstable.tar.gz earningstable/
cd ..

echo 📤 Uploading to VPS...
scp -r temp_upload\earningstable.tar.gz %VPS_USER%@%VPS_IP%:/tmp/

echo 🔧 Extracting on VPS...
ssh %VPS_USER%@%VPS_IP% "cd /var/www && tar -xzf /tmp/earningstable.tar.gz && chown -R www-data:www-data /var/www/earningstable && chmod -R 755 /var/www/earningstable"

echo 🗄️ Setting up database...
ssh %VPS_USER%@%VPS_IP% "cd /var/www/earningstable && mysql -u earningstable_user -p earnings_db < sql/setup_database.sql"
ssh %VPS_USER%@%VPS_IP% "cd /var/www/earningstable && mysql -u earningstable_user -p earnings_db < sql/setup_all_tables.sql"

echo 🧪 Running tests...
ssh %VPS_USER%@%VPS_IP% "cd /var/www/earningstable && php Tests/master_test.php"

echo 🔒 Setting up SSL...
ssh %VPS_USER%@%VPS_IP% "certbot --apache -d %DOMAIN% --non-interactive --agree-tos --email admin@%DOMAIN%"

echo 🧹 Cleaning up...
rmdir /S /Q temp_upload
ssh %VPS_USER%@%VPS_IP% "rm /tmp/earningstable.tar.gz"

echo.
echo ✅ Upload completed successfully!
echo 🌐 Your site should be available at: https://%DOMAIN%
echo.
echo 📋 Next steps:
echo 1. Test the website
echo 2. Check cron jobs are running
echo 3. Monitor logs
echo.
pause
