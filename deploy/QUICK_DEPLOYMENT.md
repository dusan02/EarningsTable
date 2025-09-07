# 🚀 Quick VPS Deployment - EarningsTable

## 📋 VPS Údaje

- **IP:** 89.185.250.213
- **Login:** root
- **Password:** EJXTfBOG2t
- **VNC:** 89.185.250.242:5903 (heslo: 2uSI25ci)

## 🎯 Rýchly Deployment

### Možnosť 1: VNC Konzola (najjednoduchšie)

1. **Pripojte sa cez VNC:**

   - Host: `89.185.250.242`
   - Port: `5903`
   - Password: `2uSI25ci`

2. **Otvorte terminál v VNC a spustite:**

```bash
# Pripojenie cez SSH (ak chcete)
ssh root@89.185.250.213
# Password: EJXTfBOG2t
```

### Možnosť 2: SSH s heslem

```bash
# V PowerShell alebo Command Prompt
ssh root@89.185.250.213
# Keď sa spýta na heslo, zadajte: EJXTfBOG2t
```

### Možnosť 3: PuTTY (Windows)

1. Stiahnite si PuTTY
2. Host: `89.185.250.213`
3. Port: `22`
4. Login: `root`
5. Password: `EJXTfBOG2t`

## 📝 Deployment Príkazy

Po pripojení k VPS skopírujte a vkladajte tieto príkazy **jeden po druhom**:

### 1. Aktualizácia systému

```bash
apt update && apt upgrade -y
```

### 2. Inštalácia LAMP

```bash
apt install -y apache2 mysql-server software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.0 php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring php8.0-xml php8.0-zip php8.0-gd php8.0-cli php8.0-common php8.0-opcache php8.0-readline
```

### 3. Composer

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

### 4. Apache konfigurácia

```bash
a2enmod rewrite ssl headers
a2dissite 000-default
```

### 5. Virtual Host

```bash
cat > /etc/apache2/sites-available/earnings-table.com.conf << 'EOF'
<VirtualHost *:80>
    ServerName earnings-table.com
    ServerAlias www.earnings-table.com
    DocumentRoot /var/www/html/EarningsTable/public

    <Directory /var/www/html/EarningsTable/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/earnings-table_error.log
    CustomLog ${APACHE_LOG_DIR}/earnings-table_access.log combined
</VirtualHost>
EOF

a2ensite earnings-table.com.conf
systemctl reload apache2
```

### 6. MySQL konfigurácia

```bash
systemctl enable mysql
systemctl start mysql
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'EJXTfBOG2t';"
mysql -u root -pEJXTfBOG2t -e "CREATE DATABASE earnings_table CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -pEJXTfBOG2t -e "CREATE USER 'earnings_user'@'localhost' IDENTIFIED BY 'EJXTfBOG2t';"
mysql -u root -pEJXTfBOG2t -e "GRANT ALL PRIVILEGES ON earnings_table.* TO 'earnings_user'@'localhost';"
mysql -u root -pEJXTfBOG2t -e "FLUSH PRIVILEGES;"
```

### 7. Klonovanie projektu

```bash
cd /var/www/html
git clone https://github.com/dusan02/EarningsTable.git
```

### 8. Permissions

```bash
chown -R www-data:www-data /var/www/html/EarningsTable
chmod -R 755 /var/www/html/EarningsTable
chmod -R 777 /var/www/html/EarningsTable/storage
chmod -R 777 /var/www/html/EarningsTable/logs
```

### 9. Dependencies

```bash
cd /var/www/html/EarningsTable
composer install --no-dev --optimize-autoloader
```

### 10. Environment súbor

```bash
cat > /var/www/html/EarningsTable/.env << 'EOF'
DB_HOST=localhost
DB_PORT=3306
DB_NAME=earnings_table
DB_USER=earnings_user
DB_PASS=EJXTfBOG2t
FINNHUB_API_KEY=your_finnhub_api_key_here
POLYGON_API_KEY=your_polygon_api_key_here
BENZINGA_API_KEY=your_benzinga_api_key_here
GA_MEASUREMENT_ID=G-E6DJ7N6W1L
GA_ENABLED=true
GA_DEBUG_MODE=false
APP_ENV=production
APP_DEBUG=false
EOF
```

### 11. Cron Jobs

```bash
cat > /tmp/earnings_cron << 'EOF'
*/2 * * * * /usr/bin/php /var/www/html/EarningsTable/cron/1_enhanced_master_cron.php >> /var/www/html/EarningsTable/logs/master_cron.log 2>&1
0 0 * * * /usr/bin/php /var/www/html/EarningsTable/cron/2_clear_old_data.php >> /var/www/html/EarningsTable/logs/clear_old_data.log 2>&1
0 1 * * * /usr/bin/php /var/www/html/EarningsTable/cron/3_daily_data_setup_static.php >> /var/www/html/EarningsTable/logs/daily_setup.log 2>&1
*/5 * * * * /usr/bin/php /var/www/html/EarningsTable/cron/4_regular_data_updates_dynamic.php >> /var/www/html/EarningsTable/logs/regular_updates.log 2>&1
0 2 * * * /usr/bin/php /var/www/html/EarningsTable/cron/5_benzinga_guidance_updates.php >> /var/www/html/EarningsTable/logs/benzinga_updates.log 2>&1
EOF

crontab /tmp/earnings_cron
rm /tmp/earnings_cron
```

### 12. Firewall

```bash
apt install -y ufw
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
```

### 13. Restart služieb

```bash
systemctl restart apache2
systemctl restart mysql
```

### 14. Test

```bash
echo "<?php phpinfo(); ?>" > /var/www/html/EarningsTable/public/info.php
```

## ✅ Hotovo!

Po dokončení bude aplikácia dostupná na:

- **http://89.185.250.213/dashboard-fixed.php**
- **http://89.185.250.213/info.php** (PHP info)

## 📋 Ďalšie kroky:

1. **Aktualizujte API kľúče** v `/var/www/html/EarningsTable/.env`
2. **Nastavte DNS** v websupport.cz
3. **SSL certifikát:** `certbot --apache -d earnings-table.com`
