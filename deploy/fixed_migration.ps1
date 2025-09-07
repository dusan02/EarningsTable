# Fixed Migration Script
Write-Host "Starting fixed migration..." -ForegroundColor Green

# Test SSH
ssh root@89.185.250.213 "echo 'SSH OK'"
if ($LASTEXITCODE -ne 0) {
    Write-Host "SSH failed!" -ForegroundColor Red
    exit 1
}

Write-Host "SSH OK" -ForegroundColor Green

# Install correct packages
Write-Host "Installing packages..." -ForegroundColor Yellow
ssh root@89.185.250.213 "apt install -y apache2 mariadb-server php php-mysql php-curl php-json php-mbstring git curl"

# Install Composer
Write-Host "Installing Composer..." -ForegroundColor Yellow
ssh root@89.185.250.213 "curl -sS https://getcomposer.org/installer | php"
ssh root@89.185.250.213 "mv composer.phar /usr/local/bin/composer"
ssh root@89.185.250.213 "chmod +x /usr/local/bin/composer"

# Setup MariaDB
Write-Host "Setting up MariaDB..." -ForegroundColor Yellow
ssh root@89.185.250.213 "systemctl enable mariadb"
ssh root@89.185.250.213 "systemctl start mariadb"

# Create database with correct syntax
Write-Host "Creating database..." -ForegroundColor Yellow
ssh root@89.185.250.213 "mysql -e \"ALTER USER 'root'@'localhost' IDENTIFIED BY 'EJXTfBOG2t';\""
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e \"CREATE DATABASE IF NOT EXISTS earnings_table CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e \"CREATE USER IF NOT EXISTS 'earnings_user'@'localhost' IDENTIFIED BY 'EJXTfBOG2t';\""
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e \"GRANT ALL PRIVILEGES ON earnings_table.* TO 'earnings_user'@'localhost';\""
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e \"FLUSH PRIVILEGES;\""

# Setup Apache
Write-Host "Setting up Apache..." -ForegroundColor Yellow
ssh root@89.185.250.213 "a2enmod rewrite"
ssh root@89.185.250.213 "systemctl enable apache2"
ssh root@89.185.250.213 "systemctl start apache2"

# Clone project
Write-Host "Cloning project..." -ForegroundColor Yellow
ssh root@89.185.250.213 "cd /var/www/html && rm -rf EarningsTable && git clone https://github.com/dusan02/EarningsTable.git"

# Install dependencies
Write-Host "Installing dependencies..." -ForegroundColor Yellow
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader"

# Set permissions
Write-Host "Setting permissions..." -ForegroundColor Yellow
ssh root@89.185.250.213 "chown -R www-data:www-data /var/www/html/EarningsTable"
ssh root@89.185.250.213 "chmod -R 755 /var/www/html/EarningsTable"
ssh root@89.185.250.213 "chmod -R 777 /var/www/html/EarningsTable/storage"
ssh root@89.185.250.213 "chmod -R 777 /var/www/html/EarningsTable/logs"

# Create .env file
Write-Host "Creating .env file..." -ForegroundColor Yellow
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

# Import database
Write-Host "Importing database..." -ForegroundColor Yellow
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && mysql -u earnings_user -pEJXTfBOG2t earnings_table < sql/setup_all_tables.sql"

# Create test file
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
