# Manual Migration - Step by step
Write-Host "Manual Migration Script" -ForegroundColor Green
Write-Host "======================" -ForegroundColor Green
Write-Host ""

Write-Host "This script will run commands one by one." -ForegroundColor Yellow
Write-Host "You can see what's happening and stop if needed." -ForegroundColor Yellow
Write-Host ""

# Test SSH
Write-Host "1. Testing SSH connection..." -ForegroundColor Cyan
ssh root@89.185.250.213 "echo 'SSH connection successful'"
if ($LASTEXITCODE -ne 0) {
    Write-Host "SSH failed!" -ForegroundColor Red
    exit 1
}
Write-Host "SSH OK" -ForegroundColor Green
Write-Host ""

# Install packages
Write-Host "2. Installing packages..." -ForegroundColor Cyan
Write-Host "Running: apt install -y apache2 mariadb-server php php-mysql php-curl php-json php-mbstring git curl" -ForegroundColor Gray
ssh root@89.185.250.213 "apt install -y apache2 mariadb-server php php-mysql php-curl php-json php-mbstring git curl"
Write-Host ""

# Install Composer
Write-Host "3. Installing Composer..." -ForegroundColor Cyan
Write-Host "Running: curl -sS https://getcomposer.org/installer | php" -ForegroundColor Gray
ssh root@89.185.250.213 "curl -sS https://getcomposer.org/installer | php"
Write-Host "Running: mv composer.phar /usr/local/bin/composer" -ForegroundColor Gray
ssh root@89.185.250.213 "mv composer.phar /usr/local/bin/composer"
Write-Host "Running: chmod +x /usr/local/bin/composer" -ForegroundColor Gray
ssh root@89.185.250.213 "chmod +x /usr/local/bin/composer"
Write-Host ""

# Setup MariaDB
Write-Host "4. Setting up MariaDB..." -ForegroundColor Cyan
Write-Host "Running: systemctl enable mariadb" -ForegroundColor Gray
ssh root@89.185.250.213 "systemctl enable mariadb"
Write-Host "Running: systemctl start mariadb" -ForegroundColor Gray
ssh root@89.185.250.213 "systemctl start mariadb"
Write-Host ""

# Create database
Write-Host "5. Creating database..." -ForegroundColor Cyan
Write-Host "Running: mysql -e 'ALTER USER root@localhost IDENTIFIED BY EJXTfBOG2t;'" -ForegroundColor Gray
ssh root@89.185.250.213 "mysql -e 'ALTER USER root@localhost IDENTIFIED BY EJXTfBOG2t;'"
Write-Host "Running: mysql -u root -pEJXTfBOG2t -e 'CREATE DATABASE IF NOT EXISTS earnings_table;'" -ForegroundColor Gray
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e 'CREATE DATABASE IF NOT EXISTS earnings_table;'"
Write-Host "Running: mysql -u root -pEJXTfBOG2t -e 'CREATE USER IF NOT EXISTS earnings_user@localhost IDENTIFIED BY EJXTfBOG2t;'" -ForegroundColor Gray
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e 'CREATE USER IF NOT EXISTS earnings_user@localhost IDENTIFIED BY EJXTfBOG2t;'"
Write-Host "Running: mysql -u root -pEJXTfBOG2t -e 'GRANT ALL PRIVILEGES ON earnings_table.* TO earnings_user@localhost;'" -ForegroundColor Gray
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e 'GRANT ALL PRIVILEGES ON earnings_table.* TO earnings_user@localhost;'"
Write-Host "Running: mysql -u root -pEJXTfBOG2t -e 'FLUSH PRIVILEGES;'" -ForegroundColor Gray
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e 'FLUSH PRIVILEGES;'"
Write-Host ""

# Setup Apache
Write-Host "6. Setting up Apache..." -ForegroundColor Cyan
Write-Host "Running: a2enmod rewrite" -ForegroundColor Gray
ssh root@89.185.250.213 "a2enmod rewrite"
Write-Host "Running: systemctl enable apache2" -ForegroundColor Gray
ssh root@89.185.250.213 "systemctl enable apache2"
Write-Host "Running: systemctl start apache2" -ForegroundColor Gray
ssh root@89.185.250.213 "systemctl start apache2"
Write-Host ""

# Clone project
Write-Host "7. Cloning project..." -ForegroundColor Cyan
Write-Host "Running: cd /var/www/html" -ForegroundColor Gray
ssh root@89.185.250.213 "cd /var/www/html"
Write-Host "Running: rm -rf EarningsTable" -ForegroundColor Gray
ssh root@89.185.250.213 "rm -rf EarningsTable"
Write-Host "Running: git clone https://github.com/dusan02/EarningsTable.git" -ForegroundColor Gray
ssh root@89.185.250.213 "git clone https://github.com/dusan02/EarningsTable.git"
Write-Host ""

# Install dependencies
Write-Host "8. Installing dependencies..." -ForegroundColor Cyan
Write-Host "Running: cd /var/www/html/EarningsTable" -ForegroundColor Gray
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable"
Write-Host "Running: COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader" -ForegroundColor Gray
ssh root@89.185.250.213 "COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader"
Write-Host ""

# Set permissions
Write-Host "9. Setting permissions..." -ForegroundColor Cyan
Write-Host "Running: chown -R www-data:www-data /var/www/html/EarningsTable" -ForegroundColor Gray
ssh root@89.185.250.213 "chown -R www-data:www-data /var/www/html/EarningsTable"
Write-Host "Running: chmod -R 755 /var/www/html/EarningsTable" -ForegroundColor Gray
ssh root@89.185.250.213 "chmod -R 755 /var/www/html/EarningsTable"
Write-Host "Running: chmod -R 777 /var/www/html/EarningsTable/storage" -ForegroundColor Gray
ssh root@89.185.250.213 "chmod -R 777 /var/www/html/EarningsTable/storage"
Write-Host "Running: chmod -R 777 /var/www/html/EarningsTable/logs" -ForegroundColor Gray
ssh root@89.185.250.213 "chmod -R 777 /var/www/html/EarningsTable/logs"
Write-Host ""

# Create .env file
Write-Host "10. Creating .env file..." -ForegroundColor Cyan
Write-Host "Running: cd /var/www/html/EarningsTable" -ForegroundColor Gray
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable"
Write-Host "Running: echo 'DB_HOST=localhost' > .env" -ForegroundColor Gray
ssh root@89.185.250.213 "echo 'DB_HOST=localhost' > .env"
Write-Host "Running: echo 'DB_PORT=3306' >> .env" -ForegroundColor Gray
ssh root@89.185.250.213 "echo 'DB_PORT=3306' >> .env"
Write-Host "Running: echo 'DB_NAME=earnings_table' >> .env" -ForegroundColor Gray
ssh root@89.185.250.213 "echo 'DB_NAME=earnings_table' >> .env"
Write-Host "Running: echo 'DB_USER=earnings_user' >> .env" -ForegroundColor Gray
ssh root@89.185.250.213 "echo 'DB_USER=earnings_user' >> .env"
Write-Host "Running: echo 'DB_PASS=EJXTfBOG2t' >> .env" -ForegroundColor Gray
ssh root@89.185.250.213 "echo 'DB_PASS=EJXTfBOG2t' >> .env"
Write-Host "Running: echo 'FINNHUB_API_KEY=your_finnhub_api_key_here' >> .env" -ForegroundColor Gray
ssh root@89.185.250.213 "echo 'FINNHUB_API_KEY=your_finnhub_api_key_here' >> .env"
Write-Host "Running: echo 'POLYGON_API_KEY=your_polygon_api_key_here' >> .env" -ForegroundColor Gray
ssh root@89.185.250.213 "echo 'POLYGON_API_KEY=your_polygon_api_key_here' >> .env"
Write-Host "Running: echo 'APP_ENV=production' >> .env" -ForegroundColor Gray
ssh root@89.185.250.213 "echo 'APP_ENV=production' >> .env"
Write-Host "Running: echo 'APP_DEBUG=false' >> .env" -ForegroundColor Gray
ssh root@89.185.250.213 "echo 'APP_DEBUG=false' >> .env"
Write-Host "Running: echo 'TIMEZONE=Europe/Prague' >> .env" -ForegroundColor Gray
ssh root@89.185.250.213 "echo 'TIMEZONE=Europe/Prague' >> .env"
Write-Host ""

# Import database
Write-Host "11. Importing database..." -ForegroundColor Cyan
Write-Host "Running: cd /var/www/html/EarningsTable" -ForegroundColor Gray
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable"
Write-Host "Running: mysql -u earnings_user -pEJXTfBOG2t earnings_table < sql/setup_all_tables.sql" -ForegroundColor Gray
ssh root@89.185.250.213 "mysql -u earnings_user -pEJXTfBOG2t earnings_table < sql/setup_all_tables.sql"
Write-Host ""

# Create test file
Write-Host "12. Creating test file..." -ForegroundColor Cyan
Write-Host "Running: echo '<?php phpinfo(); ?>' > /var/www/html/EarningsTable/public/info.php" -ForegroundColor Gray
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
