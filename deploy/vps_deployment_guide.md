# 🚀 VPS DEPLOYMENT GUIDE - EarningsTable

## 📋 **VPS PARAMETRE**

- **RAM:** 3 GB
- **SSD:** 20 GB
- **CPU:** 2.6 GHz
- **OS:** Ubuntu 20.04/22.04 LTS (odporúčané)
- **Root prístup:** ✅

## 🔧 **KROK 1: NASTAVENIE VPS SERVERA**

### **1.1 Aktualizácia systému**

```bash
sudo apt update && sudo apt upgrade -y
```

### **1.2 Inštalácia LAMP stack**

```bash
# Apache
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2

# MySQL
sudo apt install mysql-server -y
sudo systemctl enable mysql
sudo systemctl start mysql
sudo mysql_secure_installation

# PHP 8.1+
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.1 php8.1-mysql php8.1-curl php8.1-json php8.1-mbstring php8.1-xml php8.1-zip php8.1-gd php8.1-cli -y

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### **1.3 Konfigurácia Apache**

```bash
# Povolenie mod_rewrite
sudo a2enmod rewrite
sudo a2enmod headers

# Vytvorenie virtual host
sudo nano /etc/apache2/sites-available/earningstable.conf
```

**Virtual Host konfigurácia:**

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/earningstable/public

    <Directory /var/www/earningstable/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/earningstable_error.log
    CustomLog ${APACHE_LOG_DIR}/earningstable_access.log combined
</VirtualHost>
```

```bash
# Aktivácia site
sudo a2ensite earningstable.conf
sudo systemctl reload apache2
```

## 🗄️ **KROK 2: NASTAVENIE DATABÁZY**

### **2.1 Vytvorenie databázy**

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE earnings_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'earningstable_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON earnings_db.* TO 'earningstable_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### **2.2 Import SQL štruktúry**

```bash
cd /var/www/earningstable
mysql -u earningstable_user -p earnings_db < sql/setup_database.sql
mysql -u earningstable_user -p earnings_db < sql/setup_all_tables.sql
```

## 📁 **KROK 3: UPLOAD PROJEKTU**

### **3.1 Vytvorenie priečinkov**

```bash
sudo mkdir -p /var/www/earningstable
sudo chown -R www-data:www-data /var/www/earningstable
sudo chmod -R 755 /var/www/earningstable
```

### **3.2 Upload súborov (vyberte jednu metódu)**

#### **Metóda A: Git Clone**

```bash
cd /var/www
sudo git clone https://github.com/dusan02/EarningsTable.git earningstable
sudo chown -R www-data:www-data /var/www/earningstable
```

#### **Metóda B: SCP Upload**

```bash
# Z lokálneho počítača
scp -r /path/to/EarningsTable/* root@your-vps-ip:/var/www/earningstable/
```

#### **Metóda C: ZIP Upload**

```bash
# Upload zip súboru cez SFTP/SCP
# Potom na serveri:
cd /var/www/earningstable
unzip EarningsTable.zip
```

## ⚙️ **KROK 4: KONFIGURÁCIA APLIKÁCIE**

### **4.1 Environment premenné**

```bash
cd /var/www/earningstable
sudo cp config/config.example.php config/config.php
sudo nano config/config.php
```

**Konfigurácia databázy:**

```php
<?php
return [
    'database' => [
        'host' => 'localhost',
        'dbname' => 'earnings_db',
        'username' => 'earningstable_user',
        'password' => 'strong_password_here',
        'charset' => 'utf8mb4'
    ],
    'api' => [
        'polygon_key' => 'your_polygon_api_key',
        'finnhub_key' => 'your_finnhub_api_key'
    ],
    'environment' => 'production'
];
```

### **4.2 Nastavenie oprávnení**

```bash
sudo chown -R www-data:www-data /var/www/earningstable
sudo chmod -R 755 /var/www/earningstable
sudo chmod -R 777 /var/www/earningstable/logs
sudo chmod -R 777 /var/www/earningstable/storage
```

### **4.3 Composer dependencies**

```bash
cd /var/www/earningstable
sudo -u www-data composer install --no-dev --optimize-autoloader
```

## ⏰ **KROK 5: NASTAVENIE CRON JOBS**

### **5.1 Crontab nastavenie**

```bash
sudo crontab -e
```

**Pridajte tieto riadky:**

```cron
# EarningsTable Cron Jobs
*/5 * * * * /usr/bin/php /var/www/earningstable/cron/1_enhanced_master_cron.php
0 2 * * * /usr/bin/php /var/www/earningstable/cron/2_clear_old_data.php
0 6 * * * /usr/bin/php /var/www/earningstable/cron/3_daily_data_setup_static.php
*/15 * * * * /usr/bin/php /var/www/earningstable/cron/4_regular_data_updates_dynamic.php
0 8 * * * /usr/bin/php /var/www/earningstable/cron/5_benzinga_guidance_updates.php
```

## 🔒 **KROK 6: BEZPEČNOSŤ**

### **6.1 Firewall**

```bash
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

### **6.2 SSL Certificate (Let's Encrypt)**

```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d your-domain.com
```

## 🧪 **KROK 7: TESTOVANIE**

### **7.1 Test databázy**

```bash
cd /var/www/earningstable
php Tests/master_test.php
```

### **7.2 Test web stránky**

```bash
curl -I http://your-domain.com
curl -I http://your-domain.com/dashboard-fixed.html
```

## 📊 **KROK 8: MONITORING**

### **8.1 Log monitoring**

```bash
# Apache logs
sudo tail -f /var/log/apache2/earningstable_error.log

# Application logs
sudo tail -f /var/www/earningstable/logs/app.log
```

### **8.2 System monitoring**

```bash
# System resources
htop
df -h
free -h
```

## 🚨 **TROUBLESHOOTING**

### **Časté problémy:**

1. **Permission denied**

   ```bash
   sudo chown -R www-data:www-data /var/www/earningstable
   sudo chmod -R 755 /var/www/earningstable
   ```

2. **Database connection failed**

   - Skontrolujte credentials v `config/config.php`
   - Overte, že MySQL beží: `sudo systemctl status mysql`

3. **Cron jobs nebežia**

   - Skontrolujte crontab: `sudo crontab -l`
   - Testujte manuálne: `php /var/www/earningstable/cron/1_enhanced_master_cron.php`

4. **API errors**
   - Overte API kľúče v konfigurácii
   - Skontrolujte rate limity

## 📞 **PODPORA**

- **Logy:** `/var/www/earningstable/logs/`
- **Konfigurácia:** `/var/www/earningstable/config/`
- **Testy:** `php /var/www/earningstable/Tests/master_test.php`

**Deployment je pripravený!** 🚀
