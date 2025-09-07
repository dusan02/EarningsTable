# 🚀 EarningsTable Migration Script for mydreams.cz
# Automatická migrácia projektu na VPS server

param(
    [Parameter(Mandatory = $false)]
    [string]$VpsIp = "89.185.250.213",
    
    [Parameter(Mandatory = $false)]
    [string]$VpsUser = "root",
    
    [Parameter(Mandatory = $false)]
    [string]$VpsPassword = "EJXTfBOG2t",
    
    [Parameter(Mandatory = $false)]
    [string]$Domain = "earnings-table.mydreams.cz",
    
    [Parameter(Mandatory = $false)]
    [string]$DbPassword = "EJXTfBOG2t",
    
    [Parameter(Mandatory = $false)]
    [string]$FinnhubApiKey = "",
    
    [Parameter(Mandatory = $false)]
    [string]$PolygonApiKey = ""
)

# Farba pre výstup
$ErrorActionPreference = "Stop"

function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Color
}

function Test-SSHConnection {
    param([string]$Host, [string]$User)
    
    try {
        Write-ColorOutput "🔍 Testujem SSH pripojenie..." "Yellow"
        $result = ssh -o ConnectTimeout=10 -o BatchMode=yes "${User}@${Host}" "echo 'SSH connection successful'" 2>$null
        if ($LASTEXITCODE -eq 0) {
            Write-ColorOutput "✅ SSH pripojenie funguje" "Green"
            return $true
        } else {
            Write-ColorOutput "❌ SSH pripojenie zlyhalo" "Red"
            return $false
        }
    }
    catch {
        Write-ColorOutput "❌ SSH pripojenie zlyhalo: $($_.Exception.Message)" "Red"
        return $false
    }
}

function Invoke-SSHCommand {
    param(
        [string]$Host,
        [string]$User,
        [string]$Command,
        [string]$Password = ""
    )
    
    Write-ColorOutput "🔧 Spúšťam: $Command" "Cyan"
    
    if ($Password) {
        # Použitie sshpass ak je dostupný, inak manuálne zadanie hesla
        $env:SSHPASS = $Password
        $result = sshpass -e ssh "${User}@${Host}" $Command 2>$null
    } else {
        $result = ssh "${User}@${Host}" $Command 2>$null
    }
    
    if ($LASTEXITCODE -eq 0) {
        Write-ColorOutput "✅ Príkaz úspešne vykonaný" "Green"
        return $result
    } else {
        Write-ColorOutput "❌ Príkaz zlyhal s kódom: $LASTEXITCODE" "Red"
        throw "SSH command failed"
    }
}

function Copy-FileToServer {
    param(
        [string]$LocalPath,
        [string]$RemotePath,
        [string]$Host,
        [string]$User,
        [string]$Password = ""
    )
    
    Write-ColorOutput "📤 Kopírujem $LocalPath na server..." "Yellow"
    
    if ($Password) {
        $env:SSHPASS = $Password
        scp -r $LocalPath "${User}@${Host}:${RemotePath}" 2>$null
    } else {
        scp -r $LocalPath "${User}@${Host}:${RemotePath}" 2>$null
    }
    
    if ($LASTEXITCODE -eq 0) {
        Write-ColorOutput "✅ Súbor úspešne skopírovaný" "Green"
    } else {
        Write-ColorOutput "❌ Kopírovanie zlyhalo" "Red"
        throw "File copy failed"
    }
}

# Hlavný skript
Write-ColorOutput "🚀 EarningsTable Migration Script" "Green"
Write-ColorOutput "=================================" "Green"
Write-ColorOutput "VPS: $VpsIp" "Cyan"
Write-ColorOutput "User: $VpsUser" "Cyan"
Write-ColorOutput "Domain: $Domain" "Cyan"
Write-ColorOutput ""

# Kontrola predpokladov
Write-ColorOutput "🔍 Kontrolujem predpoklady..." "Yellow"

$requiredCommands = @("ssh", "scp")
foreach ($cmd in $requiredCommands) {
    if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) {
        Write-ColorOutput "❌ $cmd nie je nainštalovaný" "Red"
        Write-ColorOutput "Nainštalujte OpenSSH alebo použite PuTTY/WinSCP" "Yellow"
        exit 1
    }
}

Write-ColorOutput "✅ Všetky predpoklady sú splnené" "Green"

# Test SSH pripojenia
if (-not (Test-SSHConnection -Host $VpsIp -User $VpsUser)) {
    Write-ColorOutput "❌ Nemôžem sa pripojiť k serveru" "Red"
    exit 1
}

try {
    # Krok 1: Aktualizácia systému
    Write-ColorOutput "📦 Aktualizujem systém..." "Yellow"
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command "apt update && apt upgrade -y" -Password $VpsPassword
    
    # Krok 2: Inštalácia LAMP stack
    Write-ColorOutput "🔧 Inštalujem LAMP stack..." "Yellow"
    $lampCommand = @"
apt install -y apache2 mysql-server software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.0 php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring php8.0-xml php8.0-zip php8.0-gd php8.0-cli php8.0-common php8.0-opcache php8.0-readline
"@
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command $lampCommand -Password $VpsPassword
    
    # Krok 3: Inštalácia Composer
    Write-ColorOutput "🎼 Inštalujem Composer..." "Yellow"
    $composerCommand = @"
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
"@
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command $composerCommand -Password $VpsPassword
    
    # Krok 4: Inštalácia Git
    Write-ColorOutput "📚 Inštalujem Git..." "Yellow"
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command "apt install -y git" -Password $VpsPassword
    
    # Krok 5: Apache konfigurácia
    Write-ColorOutput "🌐 Konfigurujem Apache..." "Yellow"
    $apacheCommand = @"
a2enmod rewrite ssl headers
a2dissite 000-default
systemctl enable apache2
systemctl start apache2
"@
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command $apacheCommand -Password $VpsPassword
    
    # Krok 6: MySQL konfigurácia
    Write-ColorOutput "🗄️ Konfigurujem MySQL..." "Yellow"
    $mysqlCommand = @"
systemctl enable mysql
systemctl start mysql
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$DbPassword';"
mysql -u root -p$DbPassword -e "CREATE DATABASE earnings_table CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p$DbPassword -e "CREATE USER 'earnings_user'@'localhost' IDENTIFIED BY '$DbPassword';"
mysql -u root -p$DbPassword -e "GRANT ALL PRIVILEGES ON earnings_table.* TO 'earnings_user'@'localhost';"
mysql -u root -p$DbPassword -e "FLUSH PRIVILEGES;"
"@
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command $mysqlCommand -Password $VpsPassword
    
    # Krok 7: Klonovanie projektu
    Write-ColorOutput "📥 Klonujem projekt z GitHubu..." "Yellow"
    $gitCommand = @"
cd /var/www/html
if [ -d "EarningsTable" ]; then
    rm -rf EarningsTable
fi
git clone https://github.com/dusan02/EarningsTable.git
"@
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command $gitCommand -Password $VpsPassword
    
    # Krok 8: Dependencies a permissions
    Write-ColorOutput "📦 Inštalujem dependencies..." "Yellow"
    $depsCommand = @"
cd /var/www/html/EarningsTable
composer install --no-dev --optimize-autoloader
chown -R www-data:www-data /var/www/html/EarningsTable
chmod -R 755 /var/www/html/EarningsTable
chmod -R 777 /var/www/html/EarningsTable/storage
chmod -R 777 /var/www/html/EarningsTable/logs
"@
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command $depsCommand -Password $VpsPassword
    
    # Krok 9: Environment súbor
    Write-ColorOutput "⚙️ Vytváram .env súbor..." "Yellow"
    $envContent = @"
DB_HOST=localhost
DB_PORT=3306
DB_NAME=earnings_table
DB_USER=earnings_user
DB_PASS=$DbPassword
FINNHUB_API_KEY=$FinnhubApiKey
POLYGON_API_KEY=$PolygonApiKey
APP_ENV=production
APP_DEBUG=false
TIMEZONE=Europe/Prague
APP_URL=https://$Domain
"@
    
    $envCommand = @"
cd /var/www/html/EarningsTable
cat > .env << 'EOF'
$envContent
EOF
"@
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command $envCommand -Password $VpsPassword
    
    # Krok 10: Import databázy
    Write-ColorOutput "🗄️ Importujem databázovú schému..." "Yellow"
    $dbCommand = @"
cd /var/www/html/EarningsTable
if [ -f "sql/setup_all_tables.sql" ]; then
    mysql -u earnings_user -p$DbPassword earnings_table < sql/setup_all_tables.sql
    echo "Database schema imported successfully"
else
    echo "Database schema file not found"
fi
"@
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command $dbCommand -Password $VpsPassword
    
    # Krok 11: Virtual Host
    Write-ColorOutput "🌐 Vytváram Virtual Host..." "Yellow"
    $vhostContent = @"
<VirtualHost *:80>
    ServerName $Domain
    ServerAlias www.$Domain
    DocumentRoot /var/www/html/EarningsTable/public

    <Directory /var/www/html/EarningsTable/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/earnings-table_error.log
    CustomLog \${APACHE_LOG_DIR}/earnings-table_access.log combined
</VirtualHost>
"@
    
    $vhostCommand = @"
cat > /etc/apache2/sites-available/earnings-table.conf << 'EOF'
$vhostContent
EOF
a2ensite earnings-table.conf
systemctl reload apache2
"@
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command $vhostCommand -Password $VpsPassword
    
    # Krok 12: Cron joby
    Write-ColorOutput "⏰ Nastavujem Cron joby..." "Yellow"
    $cronCommand = @"
cat > /tmp/earnings_cron << 'EOF'
*/2 * * * * /usr/bin/php /var/www/html/EarningsTable/cron/1_enhanced_master_cron.php >> /var/www/html/EarningsTable/logs/master_cron.log 2>&1
0 0 * * * /usr/bin/php /var/www/html/EarningsTable/cron/2_clear_old_data.php >> /var/www/html/EarningsTable/logs/clear_old_data.log 2>&1
0 1 * * * /usr/bin/php /var/www/html/EarningsTable/cron/3_daily_data_setup_static.php >> /var/www/html/EarningsTable/logs/daily_setup.log 2>&1
*/5 * * * * /usr/bin/php /var/www/html/EarningsTable/cron/4_regular_data_updates_dynamic.php >> /var/www/html/EarningsTable/logs/regular_updates.log 2>&1
0 2 * * * /usr/bin/php /var/www/html/EarningsTable/cron/5_benzinga_guidance_updates.php >> /var/www/html/EarningsTable/logs/benzinga_updates.log 2>&1
EOF
crontab /tmp/earnings_cron
rm /tmp/earnings_cron
"@
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command $cronCommand -Password $VpsPassword
    
    # Krok 13: Firewall
    Write-ColorOutput "🔥 Konfigurujem Firewall..." "Yellow"
    $firewallCommand = @"
apt install -y ufw
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
"@
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command $firewallCommand -Password $VpsPassword
    
    # Krok 14: Restart služieb
    Write-ColorOutput "🔄 Reštartujem služby..." "Yellow"
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command "systemctl restart apache2 mysql" -Password $VpsPassword
    
    # Krok 15: Test
    Write-ColorOutput "🧪 Vytváram test súbor..." "Yellow"
    Invoke-SSHCommand -Host $VpsIp -User $VpsUser -Command "echo '<?php phpinfo(); ?>' > /var/www/html/EarningsTable/public/info.php" -Password $VpsPassword
    
    Write-ColorOutput ""
    Write-ColorOutput "✅ Migrácia úspešne dokončená!" "Green"
    Write-ColorOutput "🌐 Aplikácia je dostupná na:" "Cyan"
    Write-ColorOutput "   http://$VpsIp/dashboard-fixed.html" "White"
    Write-ColorOutput "   http://$VpsIp/info.php (PHP info)" "White"
    Write-ColorOutput ""
    Write-ColorOutput "📋 Ďalšie kroky:" "Yellow"
    Write-ColorOutput "1. Aktualizujte API kľúče v .env súbore" "White"
    Write-ColorOutput "2. Nastavte DNS pre doménu $Domain" "White"
    Write-ColorOutput "3. Nainštalujte SSL certifikát: certbot --apache -d $Domain" "White"
    Write-ColorOutput "4. Skontrolujte logy: tail -f /var/www/html/EarningsTable/logs/app.log" "White"
    Write-ColorOutput ""
    Write-ColorOutput "🔧 Užitočné príkazy:" "Yellow"
    Write-ColorOutput "ssh $VpsUser@$VpsIp" "White"
    Write-ColorOutput "systemctl status apache2 mysql" "White"
    Write-ColorOutput "crontab -l" "White"
    
}
catch {
    Write-ColorOutput "❌ Migrácia zlyhala: $($_.Exception.Message)" "Red"
    Write-ColorOutput "Skontrolujte logy a skúste znovu" "Yellow"
    exit 1
}
