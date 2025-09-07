# 🚀 One-Click Migration Script
# Najjednoduchší spôsob migrácie - jeden klik a hotovo!

Write-Host "🚀 EarningsTable - One-Click Migration" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green
Write-Host ""

# Kontrola SSH
Write-Host "🔍 Kontrolujem SSH pripojenie..." -ForegroundColor Yellow
$sshTest = ssh -o ConnectTimeout=5 root@89.185.250.213 "echo 'SSH OK'" 2>$null
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ SSH pripojenie zlyhalo!" -ForegroundColor Red
    Write-Host "Skontrolujte pripojenie k internetu a IP adresu" -ForegroundColor Yellow
    exit 1
}
Write-Host "✅ SSH pripojenie funguje" -ForegroundColor Green

# Jeden veľký príkaz pre všetko
Write-Host "🚀 Spúšťam kompletnú migráciu..." -ForegroundColor Yellow

$migrationScript = @"
# Aktualizácia a inštalácia
apt update && apt upgrade -y
apt install -y apache2 mysql-server php8.0 php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring git curl

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# MySQL setup
systemctl enable mysql
systemctl start mysql
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'EJXTfBOG2t';"
mysql -u root -pEJXTfBOG2t -e "CREATE DATABASE earnings_table CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -pEJXTfBOG2t -e "CREATE USER 'earnings_user'@'localhost' IDENTIFIED BY 'EJXTfBOG2t';"
mysql -u root -pEJXTfBOG2t -e "GRANT ALL PRIVILEGES ON earnings_table.* TO 'earnings_user'@'localhost';"
mysql -u root -pEJXTfBOG2t -e "FLUSH PRIVILEGES;"

# Apache setup
a2enmod rewrite
systemctl enable apache2
systemctl start apache2

# Klonovanie projektu
cd /var/www/html
if [ -d "EarningsTable" ]; then rm -rf EarningsTable; fi
git clone https://github.com/dusan02/EarningsTable.git

# Dependencies
cd /var/www/html/EarningsTable
composer install --no-dev --optimize-autoloader

# Permissions
chown -R www-data:www-data /var/www/html/EarningsTable
chmod -R 755 /var/www/html/EarningsTable
chmod -R 777 /var/www/html/EarningsTable/storage
chmod -R 777 /var/www/html/EarningsTable/logs

# Environment file
cat > .env << 'EOF'
DB_HOST=localhost
DB_PORT=3306
DB_NAME=earnings_table
DB_USER=earnings_user
DB_PASS=EJXTfBOG2t
FINNHUB_API_KEY=your_finnhub_api_key_here
POLYGON_API_KEY=your_polygon_api_key_here
APP_ENV=production
APP_DEBUG=false
TIMEZONE=Europe/Prague
EOF

# Database import
if [ -f "sql/setup_all_tables.sql" ]; then
    mysql -u earnings_user -pEJXTfBOG2t earnings_table < sql/setup_all_tables.sql
fi

# Virtual Host
cat > /etc/apache2/sites-available/earnings-table.conf << 'EOF'
<VirtualHost *:80>
    ServerName earnings-table.mydreams.cz
    DocumentRoot /var/www/html/EarningsTable/public
    <Directory /var/www/html/EarningsTable/public>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/earnings-table_error.log
    CustomLog \${APACHE_LOG_DIR}/earnings-table_access.log combined
</VirtualHost>
EOF

a2ensite earnings-table.conf
systemctl reload apache2

# Cron jobs
cat > /tmp/earnings_cron << 'EOF'
*/2 * * * * /usr/bin/php /var/www/html/EarningsTable/cron/1_enhanced_master_cron.php >> /var/www/html/EarningsTable/logs/master_cron.log 2>&1
0 0 * * * /usr/bin/php /var/www/html/EarningsTable/cron/2_clear_old_data.php >> /var/www/html/EarningsTable/logs/clear_old_data.log 2>&1
0 1 * * * /usr/bin/php /var/www/html/EarningsTable/cron/3_daily_data_setup_static.php >> /var/www/html/EarningsTable/logs/daily_setup.log 2>&1
*/5 * * * * /usr/bin/php /var/www/html/EarningsTable/cron/4_regular_data_updates_dynamic.php >> /var/www/html/EarningsTable/logs/regular_updates.log 2>&1
0 2 * * * /usr/bin/php /var/www/html/EarningsTable/cron/5_benzinga_guidance_updates.php >> /var/www/html/EarningsTable/logs/benzinga_updates.log 2>&1
EOF

crontab /tmp/earnings_cron
rm /tmp/earnings_cron

# Test file
echo '<?php phpinfo(); ?>' > /var/www/html/EarningsTable/public/info.php

echo "Migration completed successfully!"
"@

# Spustenie migrácie
Write-Host "⏳ Migrácia prebieha... (môže trvať 5-10 minút)" -ForegroundColor Yellow
ssh root@89.185.250.213 "$migrationScript"

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
    Write-Host "2. Nastavte DNS pre vašu doménu" -ForegroundColor White
    Write-Host "3. Nainštalujte SSL certifikát" -ForegroundColor White
    Write-Host ""
    Write-Host "🔧 Pre úpravu API kľúčov:" -ForegroundColor Yellow
    Write-Host "ssh root@89.185.250.213" -ForegroundColor White
    Write-Host "nano /var/www/html/EarningsTable/.env" -ForegroundColor White
} else {
    Write-Host "❌ Migrácia zlyhala!" -ForegroundColor Red
    Write-Host "Skontrolujte chyby vyššie a skúste znovu" -ForegroundColor Yellow
}
