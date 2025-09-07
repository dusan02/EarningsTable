# 🚀 Simple Migration Script
# Najjednoduchší spôsob migrácie

Write-Host "🚀 EarningsTable - Simple Migration" -ForegroundColor Green
Write-Host "=================================" -ForegroundColor Green
Write-Host ""

# Kontrola SSH
Write-Host "🔍 Testujem SSH pripojenie..." -ForegroundColor Yellow
ssh -o ConnectTimeout=5 root@89.185.250.213 "echo 'SSH OK'"
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ SSH pripojenie zlyhalo!" -ForegroundColor Red
    exit 1
}
Write-Host "✅ SSH pripojenie funguje" -ForegroundColor Green

Write-Host "🚀 Spúšťam migráciu..." -ForegroundColor Yellow

# Krok 1: Aktualizácia systému
Write-Host "📦 Aktualizujem systém..." -ForegroundColor Cyan
ssh root@89.185.250.213 "apt update && apt upgrade -y"

# Krok 2: Inštalácia LAMP
Write-Host "🔧 Inštalujem LAMP stack..." -ForegroundColor Cyan
ssh root@89.185.250.213 "apt install -y apache2 mysql-server php8.0 php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring git curl"

# Krok 3: Composer
Write-Host "🎼 Inštalujem Composer..." -ForegroundColor Cyan
ssh root@89.185.250.213 "curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer && chmod +x /usr/local/bin/composer"

# Krok 4: MySQL setup
Write-Host "🗄️ Nastavujem MySQL..." -ForegroundColor Cyan
ssh root@89.185.250.213 "systemctl enable mysql && systemctl start mysql"
ssh root@89.185.250.213 "mysql -e \"ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'EJXTfBOG2t';\""
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e \"CREATE DATABASE earnings_table CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e \"CREATE USER 'earnings_user'@'localhost' IDENTIFIED BY 'EJXTfBOG2t';\""
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e \"GRANT ALL PRIVILEGES ON earnings_table.* TO 'earnings_user'@'localhost';\""
ssh root@89.185.250.213 "mysql -u root -pEJXTfBOG2t -e \"FLUSH PRIVILEGES;\""

# Krok 5: Apache setup
Write-Host "🌐 Nastavujem Apache..." -ForegroundColor Cyan
ssh root@89.185.250.213 "a2enmod rewrite && systemctl enable apache2 && systemctl start apache2"

# Krok 6: Klonovanie projektu
Write-Host "📥 Klonujem projekt..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cd /var/www/html && rm -rf EarningsTable && git clone https://github.com/dusan02/EarningsTable.git"

# Krok 7: Dependencies
Write-Host "📦 Inštalujem dependencies..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && composer install --no-dev --optimize-autoloader"

# Krok 8: Permissions
Write-Host "🔐 Nastavujem permissions..." -ForegroundColor Cyan
ssh root@89.185.250.213 "chown -R www-data:www-data /var/www/html/EarningsTable"
ssh root@89.185.250.213 "chmod -R 755 /var/www/html/EarningsTable"
ssh root@89.185.250.213 "chmod -R 777 /var/www/html/EarningsTable/storage"
ssh root@89.185.250.213 "chmod -R 777 /var/www/html/EarningsTable/logs"

# Krok 9: Environment file
Write-Host "⚙️ Vytváram .env súbor..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && echo 'DB_HOST=localhost
DB_PORT=3306
DB_NAME=earnings_table
DB_USER=earnings_user
DB_PASS=EJXTfBOG2t
FINNHUB_API_KEY=your_finnhub_api_key_here
POLYGON_API_KEY=your_polygon_api_key_here
APP_ENV=production
APP_DEBUG=false
TIMEZONE=Europe/Prague' > .env"

# Krok 10: Database import
Write-Host "🗄️ Importujem databázu..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && mysql -u earnings_user -pEJXTfBOG2t earnings_table < sql/setup_all_tables.sql"

# Krok 11: Test file
Write-Host "🧪 Vytváram test súbor..." -ForegroundColor Cyan
ssh root@89.185.250.213 "echo '<?php phpinfo(); ?>' > /var/www/html/EarningsTable/public/info.php"

Write-Host ""
Write-Host "✅ Migrácia úspešne dokončená!" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 Aplikácia je dostupná na:" -ForegroundColor Cyan
Write-Host "   http://89.185.250.213/dashboard-fixed.html" -ForegroundColor White
Write-Host "   http://89.185.250.213/info.php" -ForegroundColor White
Write-Host ""
Write-Host "📋 Ďalšie kroky:" -ForegroundColor Yellow
Write-Host "1. Aktualizujte API kľúče:" -ForegroundColor White
Write-Host "   ssh root@89.185.250.213" -ForegroundColor Gray
Write-Host "   nano /var/www/html/EarningsTable/.env" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Nastavte cron joby:" -ForegroundColor White
Write-Host "   crontab -e" -ForegroundColor Gray
Write-Host ""
Write-Host "3. Skontrolujte logy:" -ForegroundColor White
Write-Host "   tail -f /var/www/html/EarningsTable/logs/app.log" -ForegroundColor Gray
