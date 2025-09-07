# Migration Script
Write-Host "Starting migration..." -ForegroundColor Green

# Test SSH
ssh root@89.185.250.213 "echo 'SSH OK'"
if ($LASTEXITCODE -ne 0) {
    Write-Host "SSH failed!" -ForegroundColor Red
    exit 1
}

Write-Host "SSH OK" -ForegroundColor Green

# Run migration commands one by one
Write-Host "Updating system..." -ForegroundColor Yellow
ssh root@89.185.250.213 "apt update"

Write-Host "Installing packages..." -ForegroundColor Yellow
ssh root@89.185.250.213 "apt install -y apache2 mysql-server php8.0 php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring git curl"

Write-Host "Installing Composer..." -ForegroundColor Yellow
ssh root@89.185.250.213 "curl -sS https://getcomposer.org/installer | php"
ssh root@89.185.250.213 "mv composer.phar /usr/local/bin/composer"
ssh root@89.185.250.213 "chmod +x /usr/local/bin/composer"

Write-Host "Setting up MySQL..." -ForegroundColor Yellow
ssh root@89.185.250.213 "systemctl enable mysql"
ssh root@89.185.250.213 "systemctl start mysql"

Write-Host "Creating database..." -ForegroundColor Yellow
ssh root@89.185.250.213 "mysql -e 'ALTER USER root@localhost IDENTIFIED WITH mysql_native_password BY EJXTfBOG2t;'"
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e 'CREATE DATABASE earnings_table CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'"
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e 'CREATE USER earnings_user@localhost IDENTIFIED BY EJXTfBOG2t;'"
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e 'GRANT ALL PRIVILEGES ON earnings_table.* TO earnings_user@localhost;'"
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e 'FLUSH PRIVILEGES;'"

Write-Host "Setting up Apache..." -ForegroundColor Yellow
ssh root@89.185.250.213 "a2enmod rewrite"
ssh root@89.185.250.213 "systemctl enable apache2"
ssh root@89.185.250.213 "systemctl start apache2"

Write-Host "Cloning project..." -ForegroundColor Yellow
ssh root@89.185.250.213 "cd /var/www/html"
ssh root@89.185.250.213 "rm -rf EarningsTable"
ssh root@89.185.250.213 "git clone https://github.com/dusan02/EarningsTable.git"

Write-Host "Installing dependencies..." -ForegroundColor Yellow
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable"
ssh root@89.185.250.213 "composer install --no-dev --optimize-autoloader"

Write-Host "Setting permissions..." -ForegroundColor Yellow
ssh root@89.185.250.213 "chown -R www-data:www-data /var/www/html/EarningsTable"
ssh root@89.185.250.213 "chmod -R 755 /var/www/html/EarningsTable"
ssh root@89.185.250.213 "chmod -R 777 /var/www/html/EarningsTable/storage"
ssh root@89.185.250.213 "chmod -R 777 /var/www/html/EarningsTable/logs"

Write-Host "Creating .env file..." -ForegroundColor Yellow
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable"
ssh root@89.185.250.213 "echo 'DB_HOST=localhost' > .env"
ssh root@89.185.250.213 "echo 'DB_PORT=3306' >> .env"
ssh root@89.185.250.213 "echo 'DB_NAME=earnings_table' >> .env"
ssh root@89.185.250.213 "echo 'DB_USER=earnings_user' >> .env"
ssh root@89.185.250.213 "echo 'DB_PASS=EJXTfBOG2t' >> .env"
ssh root@89.185.250.213 "echo 'FINNHUB_API_KEY=your_finnhub_api_key_here' >> .env"
ssh root@89.185.250.213 "echo 'POLYGON_API_KEY=your_polygon_api_key_here' >> .env"
ssh root@89.185.250.213 "echo 'APP_ENV=production' >> .env"
ssh root@89.185.250.213 "echo 'APP_DEBUG=false' >> .env"
ssh root@89.185.250.213 "echo 'TIMEZONE=Europe/Prague' >> .env"

Write-Host "Importing database..." -ForegroundColor Yellow
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable"
ssh root@89.185.250.213 "mysql -u earnings_user -pEJXTfBOG2t earnings_table < sql/setup_all_tables.sql"

Write-Host "Creating test file..." -ForegroundColor Yellow
ssh root@89.185.250.213 "echo '<?php phpinfo(); ?>' > /var/www/html/EarningsTable/public/info.php"

Write-Host ""
Write-Host "Migration completed!" -ForegroundColor Green
Write-Host ""
Write-Host "Application available at:" -ForegroundColor Cyan
Write-Host "http://89.185.250.213/dashboard-fixed.html" -ForegroundColor White
Write-Host "http://89.185.250.213/info.php" -ForegroundColor White
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Update API keys in .env file" -ForegroundColor White
Write-Host "2. Set up cron jobs" -ForegroundColor White
Write-Host "3. Check logs" -ForegroundColor White
