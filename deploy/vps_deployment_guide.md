# VPS Deployment Guide - EarningsTable

## 🖥️ VPS Informácie

**Server:** bardus  
**IP:** 89.185.250.213  
**OS:** Debian 12  
**Login:** root  
**Password:** EJXTfBOG2t  

**VNC Console:**
- Host: 89.185.250.242
- Port: 5903
- Password: 2uSI25ci

## 🚀 Deployment Kroky

### 1. Pripojenie k VPS

```bash
ssh root@89.185.250.213
# Password: EJXTfBOG2t
```

### 2. Aktualizácia systému

```bash
apt update && apt upgrade -y
```

### 3. Inštalácia LAMP Stack

```bash
# Apache
apt install -y apache2
systemctl enable apache2
systemctl start apache2

# MySQL
apt install -y mysql-server
systemctl enable mysql
systemctl start mysql

# PHP 8.0+
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.0 php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring php8.0-xml php8.0-zip php8.0-gd php8.0-cli php8.0-common php8.0-opcache php8.0-readline

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

### 4. Konfigurácia Apache

```bash
# Enable modules
a2enmod rewrite
a2enmod ssl
a2enmod headers

# Create virtual host
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

# Enable site
a2ensite earnings-table.com.conf
a2dissite 000-default.conf
systemctl reload apache2
```

### 5. Konfigurácia MySQL

```bash
# Secure MySQL
mysql_secure_installation
# Answer: y, EJXTfBOG2t, EJXTfBOG2t, y, y, y, y

# Create database
mysql -u root -p << 'EOF'
CREATE DATABASE earnings_table CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'earnings_user'@'localhost' IDENTIFIED BY 'EJXTfBOG2t';
GRANT ALL PRIVILEGES ON earnings_table.* TO 'earnings_user'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### 6. Klonovanie projektu

```bash
cd /var/www/html
git clone https://github.com/dusan02/EarningsTable.git
cd EarningsTable

# Set permissions
chown -R www-data:www-data /var/www/html/EarningsTable
chmod -R 755 /var/www/html/EarningsTable
chmod -R 777 /var/www/html/EarningsTable/storage
chmod -R 777 /var/www/html/EarningsTable/logs
```

### 7. Inštalácia závislostí

```bash
cd /var/www/html/EarningsTable
composer install --no-dev --optimize-autoloader
```

### 8. Konfigurácia aplikácie

```bash
# Create .env file
cat > /var/www/html/EarningsTable/.env << 'EOF'
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=earnings_table
DB_USER=earnings_user
DB_PASS=EJXTfBOG2t

# API Keys (Please update these)
FINNHUB_API_KEY=your_finnhub_api_key_here
POLYGON_API_KEY=your_polygon_api_key_here
BENZINGA_API_KEY=your_benzinga_api_key_here

# Google Analytics
GA_MEASUREMENT_ID=G-E6DJ7N6W1L
GA_ENABLED=true
GA_DEBUG_MODE=false

# Environment
APP_ENV=production
APP_DEBUG=false
EOF
```

### 9. Nastavenie Cron Jobs

```bash
# Create cron jobs
cat > /tmp/earnings_cron << 'EOF'
# EarningsTable Cron Jobs
*/2 * * * * /usr/bin/php /var/www/html/EarningsTable/cron/1_enhanced_master_cron.php >> /var/www/html/EarningsTable/logs/master_cron.log 2>&1
0 0 * * * /usr/bin/php /var/www/html/EarningsTable/cron/2_clear_old_data.php >> /var/www/html/EarningsTable/logs/clear_old_data.log 2>&1
0 1 * * * /usr/bin/php /var/www/html/EarningsTable/cron/3_daily_data_setup_static.php >> /var/www/html/EarningsTable/logs/daily_setup.log 2>&1
*/5 * * * * /usr/bin/php /var/www/html/EarningsTable/cron/4_regular_data_updates_dynamic.php >> /var/www/html/EarningsTable/logs/regular_updates.log 2>&1
0 2 * * * /usr/bin/php /var/www/html/EarningsTable/cron/5_benzinga_guidance_updates.php >> /var/www/html/EarningsTable/logs/benzinga_updates.log 2>&1
EOF

# Install cron jobs
crontab /tmp/earnings_cron
rm /tmp/earnings_cron
```

### 10. SSL Certifikát

```bash
# Install Certbot
apt install -y certbot python3-certbot-apache

# Setup SSL (run after DNS is configured)
certbot --apache -d earnings-table.com -d www.earnings-table.com
```

### 11. Firewall

```bash
# Install UFW
apt install -y ufw

# Configure firewall
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
```

### 12. Testovanie

```bash
# Restart services
systemctl restart apache2
systemctl restart mysql

# Test PHP
echo "<?php phpinfo(); ?>" > /var/www/html/EarningsTable/public/info.php
# Visit: http://89.185.250.213/info.php

# Test application
# Visit: http://89.185.250.213/dashboard-fixed.php
```

## 🔧 DNS Konfigurácia

V websupport/active24.cz nastavte:

```
A    earnings-table.com     → 89.185.250.213
A    www.earnings-table.com → 89.185.250.213
```

## 📊 Monitoring

```bash
# Check services
systemctl status apache2
systemctl status mysql

# Check cron jobs
crontab -l

# Check logs
tail -f /var/www/html/EarningsTable/logs/*.log
```

## 🎯 Výsledok

Po dokončení budete mať:
- ✅ EarningsTable dostupný na http://earnings-table.com
- ✅ Google Analytics aktívne
- ✅ Cron jobs bežiace
- ✅ SSL certifikát (po DNS setup)
- ✅ Automatické zálohovanie

## 🆘 Troubleshooting

**Ak niečo nefunguje:**
1. Skontrolujte logy: `tail -f /var/log/apache2/error.log`
2. Skontrolujte PHP: `php -v`
3. Skontrolujte MySQL: `systemctl status mysql`
4. Skontrolujte cron: `crontab -l`