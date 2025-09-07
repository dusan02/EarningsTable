# 🚀 Run Migration - Najjednoduchší spôsob
# Spustí migráciu krok za krokom

Write-Host "🚀 EarningsTable Migration" -ForegroundColor Green
Write-Host "=========================" -ForegroundColor Green
Write-Host ""

# Funkcia pre spustenie SSH príkazu
function Run-SSH {
    param([string]$Command)
    Write-Host "🔧 $Command" -ForegroundColor Cyan
    ssh root@89.185.250.213 $Command
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Príkaz zlyhal: $Command" -ForegroundColor Red
        exit 1
    }
    Write-Host "✅ Dokončené" -ForegroundColor Green
    Write-Host ""
}

# Kontrola SSH
Write-Host "🔍 Testujem SSH pripojenie..." -ForegroundColor Yellow
Run-SSH "echo 'SSH connection OK'"

# Krok 1: Aktualizácia
Write-Host "📦 Aktualizujem systém..." -ForegroundColor Yellow
Run-SSH "apt update"
Run-SSH "apt upgrade -y"

# Krok 2: Inštalácia balíčkov
Write-Host "🔧 Inštalujem balíčky..." -ForegroundColor Yellow
Run-SSH "apt install -y apache2"
Run-SSH "apt install -y mysql-server"
Run-SSH "apt install -y php8.0"
Run-SSH "apt install -y php8.0-mysql"
Run-SSH "apt install -y php8.0-curl"
Run-SSH "apt install -y php8.0-json"
Run-SSH "apt install -y php8.0-mbstring"
Run-SSH "apt install -y git"
Run-SSH "apt install -y curl"

# Krok 3: Composer
Write-Host "🎼 Inštalujem Composer..." -ForegroundColor Yellow
Run-SSH "curl -sS https://getcomposer.org/installer -o composer-setup.php"
Run-SSH "php composer-setup.php"
Run-SSH "mv composer.phar /usr/local/bin/composer"
Run-SSH "chmod +x /usr/local/bin/composer"

# Krok 4: MySQL
Write-Host "🗄️ Nastavujem MySQL..." -ForegroundColor Yellow
Run-SSH "systemctl enable mysql"
Run-SSH "systemctl start mysql"

# Krok 5: Databáza
Write-Host "🗄️ Vytváram databázu..." -ForegroundColor Yellow
Run-SSH "mysql -e 'ALTER USER root@localhost IDENTIFIED WITH mysql_native_password BY EJXTfBOG2t;'"
Run-SSH "mysql -u root -pEJXTfBOG2t -e 'CREATE DATABASE earnings_table CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'"
Run-SSH "mysql -u root -pEJXTfBOG2t -e 'CREATE USER earnings_user@localhost IDENTIFIED BY EJXTfBOG2t;'"
Run-SSH "mysql -u root -pEJXTfBOG2t -e 'GRANT ALL PRIVILEGES ON earnings_table.* TO earnings_user@localhost;'"
Run-SSH "mysql -u root -pEJXTfBOG2t -e 'FLUSH PRIVILEGES;'"

# Krok 6: Apache
Write-Host "🌐 Nastavujem Apache..." -ForegroundColor Yellow
Run-SSH "a2enmod rewrite"
Run-SSH "systemctl enable apache2"
Run-SSH "systemctl start apache2"

# Krok 7: Projekt
Write-Host "📥 Klonujem projekt..." -ForegroundColor Yellow
Run-SSH "cd /var/www/html"
Run-SSH "rm -rf EarningsTable"
Run-SSH "git clone https://github.com/dusan02/EarningsTable.git"

# Krok 8: Dependencies
Write-Host "📦 Inštalujem dependencies..." -ForegroundColor Yellow
Run-SSH "cd /var/www/html/EarningsTable"
Run-SSH "composer install --no-dev --optimize-autoloader"

# Krok 9: Permissions
Write-Host "🔐 Nastavujem permissions..." -ForegroundColor Yellow
Run-SSH "chown -R www-data:www-data /var/www/html/EarningsTable"
Run-SSH "chmod -R 755 /var/www/html/EarningsTable"
Run-SSH "chmod -R 777 /var/www/html/EarningsTable/storage"
Run-SSH "chmod -R 777 /var/www/html/EarningsTable/logs"

# Krok 10: Environment
Write-Host "⚙️ Vytváram .env súbor..." -ForegroundColor Yellow
Run-SSH "cd /var/www/html/EarningsTable"
Run-SSH "echo 'DB_HOST=localhost' > .env"
Run-SSH "echo 'DB_PORT=3306' >> .env"
Run-SSH "echo 'DB_NAME=earnings_table' >> .env"
Run-SSH "echo 'DB_USER=earnings_user' >> .env"
Run-SSH "echo 'DB_PASS=EJXTfBOG2t' >> .env"
Run-SSH "echo 'FINNHUB_API_KEY=your_finnhub_api_key_here' >> .env"
Run-SSH "echo 'POLYGON_API_KEY=your_polygon_api_key_here' >> .env"
Run-SSH "echo 'APP_ENV=production' >> .env"
Run-SSH "echo 'APP_DEBUG=false' >> .env"
Run-SSH "echo 'TIMEZONE=Europe/Prague' >> .env"

# Krok 11: Databáza
Write-Host "🗄️ Importujem databázu..." -ForegroundColor Yellow
Run-SSH "cd /var/www/html/EarningsTable"
Run-SSH "mysql -u earnings_user -pEJXTfBOG2t earnings_table < sql/setup_all_tables.sql"

# Krok 12: Test
Write-Host "🧪 Vytváram test súbor..." -ForegroundColor Yellow
Run-SSH "echo '<?php phpinfo(); ?>' > /var/www/html/EarningsTable/public/info.php"

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
