# 🚀 Start Migration - Najjednoduchší spôsob
Write-Host "🚀 EarningsTable Migration" -ForegroundColor Green
Write-Host "=========================" -ForegroundColor Green
Write-Host ""

# Test SSH
Write-Host "🔍 Testujem SSH..." -ForegroundColor Yellow
ssh root@89.185.250.213 "echo 'SSH OK'"
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ SSH zlyhalo!" -ForegroundColor Red
    exit 1
}
Write-Host "✅ SSH funguje" -ForegroundColor Green
Write-Host ""

# Spustenie migrácie
Write-Host "🚀 Spúšťam migráciu..." -ForegroundColor Yellow
Write-Host "Toto môže trvať 5-10 minút..." -ForegroundColor Cyan
Write-Host ""

# Jeden veľký príkaz
$migration = @"
apt update && apt upgrade -y
apt install -y apache2 mysql-server php8.0 php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring git curl
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
systemctl enable mysql
systemctl start mysql
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'EJXTfBOG2t';"
mysql -u root -pEJXTfBOG2t -e "CREATE DATABASE earnings_table CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -pEJXTfBOG2t -e "CREATE USER 'earnings_user'@'localhost' IDENTIFIED BY 'EJXTfBOG2t';"
mysql -u root -pEJXTfBOG2t -e "GRANT ALL PRIVILEGES ON earnings_table.* TO 'earnings_user'@'localhost';"
mysql -u root -pEJXTfBOG2t -e "FLUSH PRIVILEGES;"
a2enmod rewrite
systemctl enable apache2
systemctl start apache2
cd /var/www/html
rm -rf EarningsTable
git clone https://github.com/dusan02/EarningsTable.git
cd /var/www/html/EarningsTable
composer install --no-dev --optimize-autoloader
chown -R www-data:www-data /var/www/html/EarningsTable
chmod -R 755 /var/www/html/EarningsTable
chmod -R 777 /var/www/html/EarningsTable/storage
chmod -R 777 /var/www/html/EarningsTable/logs
echo 'DB_HOST=localhost' > .env
echo 'DB_PORT=3306' >> .env
echo 'DB_NAME=earnings_table' >> .env
echo 'DB_USER=earnings_user' >> .env
echo 'DB_PASS=EJXTfBOG2t' >> .env
echo 'FINNHUB_API_KEY=your_finnhub_api_key_here' >> .env
echo 'POLYGON_API_KEY=your_polygon_api_key_here' >> .env
echo 'APP_ENV=production' >> .env
echo 'APP_DEBUG=false' >> .env
echo 'TIMEZONE=Europe/Prague' >> .env
mysql -u earnings_user -pEJXTfBOG2t earnings_table < sql/setup_all_tables.sql
echo '<?php phpinfo(); ?>' > /var/www/html/EarningsTable/public/info.php
echo 'Migration completed!'
"@

ssh root@89.185.250.213 $migration

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✅ Migrácia úspešne dokončená!" -ForegroundColor Green
    Write-Host ""
    Write-Host "🌐 Aplikácia je dostupná na:" -ForegroundColor Cyan
    Write-Host "   http://89.185.250.213/dashboard-fixed.html" -ForegroundColor White
    Write-Host "   http://89.185.250.213/info.php" -ForegroundColor White
    Write-Host ""
    Write-Host "📋 Ďalšie kroky:" -ForegroundColor Yellow
    Write-Host "1. Aktualizujte API kľúče v .env súbore" -ForegroundColor White
    Write-Host "2. Nastavte cron joby" -ForegroundColor White
    Write-Host "3. Skontrolujte logy" -ForegroundColor White
} else {
    Write-Host "❌ Migrácia zlyhala!" -ForegroundColor Red
}
