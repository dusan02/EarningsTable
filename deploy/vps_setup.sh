#!/bin/bash

# 🚀 VPS Setup Script for EarningsTable
# Automatické nastavenie VPS servera pre EarningsTable

set -e  # Exit on any error

echo "🚀 EarningsTable VPS Setup Script"
echo "=================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    print_error "Please run as root (use sudo)"
    exit 1
fi

# Get domain name
read -p "Enter your domain name (e.g., earningstable.com): " DOMAIN_NAME
if [ -z "$DOMAIN_NAME" ]; then
    print_error "Domain name is required"
    exit 1
fi

# Get database password
read -s -p "Enter database password for 'earningstable_user': " DB_PASSWORD
echo
if [ -z "$DB_PASSWORD" ]; then
    print_error "Database password is required"
    exit 1
fi

# Get API keys
read -p "Enter Polygon API key: " POLYGON_KEY
read -p "Enter Finnhub API key: " FINNHUB_KEY

echo
echo "🔧 Starting VPS setup..."
echo

# Step 1: Update system
print_status "Updating system packages..."
apt update && apt upgrade -y

# Step 2: Install Apache
print_status "Installing Apache..."
apt install apache2 -y
systemctl enable apache2
systemctl start apache2

# Step 3: Install MySQL
print_status "Installing MySQL..."
apt install mysql-server -y
systemctl enable mysql
systemctl start mysql

# Secure MySQL installation
print_status "Securing MySQL installation..."
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$DB_PASSWORD';"
mysql -e "DELETE FROM mysql.user WHERE User='';"
mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
mysql -e "DROP DATABASE IF EXISTS test;"
mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
mysql -e "FLUSH PRIVILEGES;"

# Step 4: Install PHP
print_status "Installing PHP 8.1..."
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.1 php8.1-mysql php8.1-curl php8.1-json php8.1-mbstring php8.1-xml php8.1-zip php8.1-gd php8.1-cli -y

# Step 5: Install Composer
print_status "Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Step 6: Configure Apache
print_status "Configuring Apache..."
a2enmod rewrite
a2enmod headers

# Create virtual host
cat > /etc/apache2/sites-available/earningstable.conf << EOF
<VirtualHost *:80>
    ServerName $DOMAIN_NAME
    DocumentRoot /var/www/earningstable/public
    
    <Directory /var/www/earningstable/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/earningstable_error.log
    CustomLog \${APACHE_LOG_DIR}/earningstable_access.log combined
</VirtualHost>
EOF

a2ensite earningstable.conf
a2dissite 000-default.conf
systemctl reload apache2

# Step 7: Setup database
print_status "Setting up database..."
mysql -u root -p$DB_PASSWORD -e "CREATE DATABASE earnings_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p$DB_PASSWORD -e "CREATE USER 'earningstable_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
mysql -u root -p$DB_PASSWORD -e "GRANT ALL PRIVILEGES ON earnings_db.* TO 'earningstable_user'@'localhost';"
mysql -u root -p$DB_PASSWORD -e "FLUSH PRIVILEGES;"

# Step 8: Create project directory
print_status "Creating project directory..."
mkdir -p /var/www/earningstable
chown -R www-data:www-data /var/www/earningstable
chmod -R 755 /var/www/earningstable

# Step 9: Create configuration file
print_status "Creating configuration file..."
cat > /var/www/earningstable/config/config.php << EOF
<?php
return [
    'database' => [
        'host' => 'localhost',
        'dbname' => 'earnings_db',
        'username' => 'earningstable_user',
        'password' => '$DB_PASSWORD',
        'charset' => 'utf8mb4'
    ],
    'api' => [
        'polygon_key' => '$POLYGON_KEY',
        'finnhub_key' => '$FINNHUB_KEY'
    ],
    'environment' => 'production'
];
EOF

# Step 10: Setup firewall
print_status "Configuring firewall..."
ufw --force enable
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp

# Step 11: Install SSL (Let's Encrypt)
print_status "Installing SSL certificate..."
apt install certbot python3-certbot-apache -y

# Step 12: Create cron jobs
print_status "Setting up cron jobs..."
cat > /tmp/earningstable_cron << EOF
# EarningsTable Cron Jobs
*/5 * * * * /usr/bin/php /var/www/earningstable/cron/1_enhanced_master_cron.php
0 2 * * * /usr/bin/php /var/www/earningstable/cron/2_clear_old_data.php
0 6 * * * /usr/bin/php /var/www/earningstable/cron/3_daily_data_setup_static.php
*/15 * * * * /usr/bin/php /var/www/earningstable/cron/4_regular_data_updates_dynamic.php
0 8 * * * /usr/bin/php /var/www/earningstable/cron/5_benzinga_guidance_updates.php
EOF

crontab /tmp/earningstable_cron
rm /tmp/earningstable_cron

# Step 13: Create log directories
print_status "Creating log directories..."
mkdir -p /var/www/earningstable/logs
mkdir -p /var/www/earningstable/storage
chown -R www-data:www-data /var/www/earningstable/logs
chown -R www-data:www-data /var/www/earningstable/storage
chmod -R 777 /var/www/earningstable/logs
chmod -R 777 /var/www/earningstable/storage

echo
echo "🎉 VPS Setup completed successfully!"
echo
echo "📋 Next steps:"
echo "1. Upload your project files to /var/www/earningstable/"
echo "2. Run: sudo certbot --apache -d $DOMAIN_NAME"
echo "3. Import database schema from sql/ directory"
echo "4. Test the application"
echo
echo "🔧 Useful commands:"
echo "- Check Apache status: systemctl status apache2"
echo "- Check MySQL status: systemctl status mysql"
echo "- View logs: tail -f /var/log/apache2/earningstable_error.log"
echo "- Test database: mysql -u earningstable_user -p earnings_db"
echo
print_status "VPS is ready for EarningsTable deployment!"
