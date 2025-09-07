# 🚀 Quick Migration Script - Zjednodušená verzia
# Pre rýchlu migráciu bez interakcie

param(
    [string]$FinnhubApiKey = "",
    [string]$PolygonApiKey = ""
)

Write-Host "🚀 Quick Migration Script" -ForegroundColor Green
Write-Host "=========================" -ForegroundColor Green

# SSH pripojenie a základné príkazy
$sshCommands = @(
    "apt update && apt upgrade -y",
    "apt install -y apache2 mysql-server php8.0 php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring git",
    "curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer",
    "systemctl enable apache2 mysql && systemctl start apache2 mysql",
    "mysql -e \"ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'EJXTfBOG2t';\"",
    "mysql -u root -pEJXTfBOG2t -e \"CREATE DATABASE earnings_table CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \"",
    "mysql -u root -pEJXTfBOG2t -e \"CREATE USER 'earnings_user'@'localhost' IDENTIFIED BY 'EJXTfBOG2t';\"",
    "mysql -u root -pEJXTfBOG2t -e \"GRANT ALL PRIVILEGES ON earnings_table.* TO 'earnings_user'@'localhost';\"",
    "cd /var/www/html && git clone https://github.com/dusan02/EarningsTable.git",
    "cd /var/www/html/EarningsTable && composer install --no-dev --optimize-autoloader",
    "chown -R www-data:www-data /var/www/html/EarningsTable",
    "chmod -R 755 /var/www/html/EarningsTable",
    "chmod -R 777 /var/www/html/EarningsTable/storage /var/www/html/EarningsTable/logs"
)

Write-Host "🔧 Spúšťam základné príkazy..." -ForegroundColor Yellow

foreach ($cmd in $sshCommands) {
    Write-Host "Spúšťam: $cmd" -ForegroundColor Cyan
    ssh root@89.185.250.213 $cmd
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Príkaz zlyhal: $cmd" -ForegroundColor Red
        exit 1
    }
}

# Vytvorenie .env súboru
$envContent = @"
DB_HOST=localhost
DB_PORT=3306
DB_NAME=earnings_table
DB_USER=earnings_user
DB_PASS=EJXTfBOG2t
FINNHUB_API_KEY=$FinnhubApiKey
POLYGON_API_KEY=$PolygonApiKey
APP_ENV=production
APP_DEBUG=false
TIMEZONE=Europe/Prague
"@

Write-Host "⚙️ Vytváram .env súbor..." -ForegroundColor Yellow
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && cat > .env << 'EOF'
$envContent
EOF"

# Import databázy
Write-Host "🗄️ Importujem databázu..." -ForegroundColor Yellow
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && mysql -u earnings_user -pEJXTfBOG2t earnings_table < sql/setup_all_tables.sql"

# Apache konfigurácia
Write-Host "🌐 Konfigurujem Apache..." -ForegroundColor Yellow

Write-Host ""
Write-Host "✅ Rýchla migrácia dokončená!" -ForegroundColor Green
Write-Host "🌐 Testujte na: http://89.185.250.213/dashboard-fixed.html" -ForegroundColor Cyan
