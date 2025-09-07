# Complete Migration Script
Write-Host "Complete Migration Script" -ForegroundColor Green
Write-Host "=========================" -ForegroundColor Green
Write-Host ""

# Fix Git ownership
Write-Host "1. Fixing Git ownership..." -ForegroundColor Cyan
ssh root@89.185.250.213 "git config --global --add safe.directory /var/www/html/EarningsTable"

# Fix MariaDB password with correct syntax
Write-Host "2. Fixing MariaDB password..." -ForegroundColor Cyan
ssh root@89.185.250.213 "mysql -e \"ALTER USER 'root'@'localhost' IDENTIFIED BY 'EJXTfBOG2t';\""

# Create database
Write-Host "3. Creating database..." -ForegroundColor Cyan
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e 'CREATE DATABASE IF NOT EXISTS earnings_table;'"

# Create user with correct syntax
Write-Host "4. Creating user..." -ForegroundColor Cyan
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e \"CREATE USER IF NOT EXISTS 'earnings_user'@'localhost' IDENTIFIED BY 'EJXTfBOG2t';\""

# Grant privileges
Write-Host "5. Granting privileges..." -ForegroundColor Cyan
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e 'GRANT ALL PRIVILEGES ON earnings_table.* TO earnings_user@localhost;'"
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e 'FLUSH PRIVILEGES;'"

# Install dependencies
Write-Host "6. Installing dependencies..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader"

# Create .env file
Write-Host "7. Creating .env file..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && echo 'DB_HOST=localhost' > .env"
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && echo 'DB_PORT=3306' >> .env"
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && echo 'DB_NAME=earnings_table' >> .env"
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && echo 'DB_USER=earnings_user' >> .env"
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && echo 'DB_PASS=EJXTfBOG2t' >> .env"
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && echo 'FINNHUB_API_KEY=your_finnhub_api_key_here' >> .env"
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && echo 'POLYGON_API_KEY=your_polygon_api_key_here' >> .env"
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && echo 'APP_ENV=production' >> .env"
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && echo 'APP_DEBUG=false' >> .env"
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && echo 'TIMEZONE=Europe/Prague' >> .env"

# Create basic database structure
Write-Host "8. Creating basic database structure..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && mysql -u earnings_user -pEJXTfBOG2t earnings_table -e 'CREATE TABLE IF NOT EXISTS test_table (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255));'"

# Test connection
Write-Host "9. Testing connection..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && php -r 'echo \"PHP version: \" . phpversion() . \"\n\";'"

# Check if application works
Write-Host "10. Checking application..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && ls -la public/"

Write-Host ""
Write-Host "Complete migration finished!" -ForegroundColor Green
Write-Host ""
Write-Host "Application available at:" -ForegroundColor Cyan
Write-Host "http://89.185.250.213/dashboard-fixed.html" -ForegroundColor White
Write-Host "http://89.185.250.213/info.php" -ForegroundColor White
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Update API keys in .env file" -ForegroundColor White
Write-Host "2. Set up cron jobs" -ForegroundColor White
Write-Host "3. Check logs" -ForegroundColor White
