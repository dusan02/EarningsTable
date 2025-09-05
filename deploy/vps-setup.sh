#!/bin/bash

# ========================================
# EarningsTable VPS Setup Script
# ========================================
# Tento script nastaví VPS pre EarningsTable
# Spustiť ako: sudo bash vps-setup.sh

set -e  # Exit on any error

echo "🚀 EarningsTable VPS Setup Script"
echo "=================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="EarningsTable"
WEB_ROOT="/var/www/html"
PROJECT_DIR="$WEB_ROOT/$PROJECT_NAME"
DB_NAME="earnings_table"
DB_USER="earnings_user"
DB_PASS=$(openssl rand -base64 32)  # Generate random password

echo -e "${BLUE}📋 Configuration:${NC}"
echo "Project: $PROJECT_NAME"
echo "Web Root: $WEB_ROOT"
echo "Project Dir: $PROJECT_DIR"
echo "Database: $DB_NAME"
echo "DB User: $DB_USER"
echo ""

# ========================================
# 1. System Update
# ========================================
echo -e "${YELLOW}🔄 Updating system...${NC}"
apt update && apt upgrade -y

# ========================================
# 2. Install LAMP Stack
# ========================================
echo -e "${YELLOW}🔧 Installing LAMP stack...${NC}"

# Install Apache
apt install -y apache2
systemctl enable apache2
systemctl start apache2

# Install MySQL
apt install -y mysql-server
systemctl enable mysql
systemctl start mysql

# Install PHP 8.0+
apt install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt update
apt install -y php8.0 php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring php8.0-xml php8.0-zip php8.0-gd php8.0-cli php8.0-common php8.0-opcache php8.0-readline

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# ========================================
# 3. Configure Apache
# ========================================
echo -e "${YELLOW}🌐 Configuring Apache...${NC}"

# Enable required modules
a2enmod rewrite
a2enmod ssl
a2enmod headers

# Create virtual host for earnings-table.com
cat > /etc/apache2/sites-available/earnings-table.com.conf << EOF
<VirtualHost *:80>
    ServerName earnings-table.com
    ServerAlias www.earnings-table.com
    DocumentRoot $PROJECT_DIR/public
    
    <Directory $PROJECT_DIR/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/earnings-table_error.log
    CustomLog \${APACHE_LOG_DIR}/earnings-table_access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName earnings-table.com
    ServerAlias www.earnings-table.com
    DocumentRoot $PROJECT_DIR/public
    
    <Directory $PROJECT_DIR/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
    SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
    
    ErrorLog \${APACHE_LOG_DIR}/earnings-table_ssl_error.log
    CustomLog \${APACHE_LOG_DIR}/earnings-table_ssl_access.log combined
</VirtualHost>
EOF

# Enable site
a2ensite earnings-table.com.conf
a2dissite 000-default.conf
systemctl reload apache2

# ========================================
# 4. Configure MySQL
# ========================================
echo -e "${YELLOW}🗄️ Configuring MySQL...${NC}"

# Secure MySQL installation
mysql_secure_installation << EOF

y
$DB_PASS
$DB_PASS
y
y
y
y
EOF

# Create database and user
mysql -u root -p$DB_PASS << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

# ========================================
# 5. Setup Project Directory
# ========================================
echo -e "${YELLOW}📁 Setting up project directory...${NC}"

# Create project directory
mkdir -p $PROJECT_DIR
cd $PROJECT_DIR

# Clone from GitHub (replace with your repository)
echo "Please provide your GitHub repository URL:"
read -p "GitHub URL: " GITHUB_URL

if [ ! -z "$GITHUB_URL" ]; then
    git clone $GITHUB_URL .
else
    echo "⚠️  Skipping Git clone. Please upload files manually."
fi

# Set permissions
chown -R www-data:www-data $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 777 $PROJECT_DIR/storage
chmod -R 777 $PROJECT_DIR/logs

# ========================================
# 6. Install Dependencies
# ========================================
echo -e "${YELLOW}📦 Installing dependencies...${NC}"

if [ -f "$PROJECT_DIR/composer.json" ]; then
    cd $PROJECT_DIR
    composer install --no-dev --optimize-autoloader
else
    echo "⚠️  composer.json not found. Skipping Composer install."
fi

# ========================================
# 7. Setup Environment
# ========================================
echo -e "${YELLOW}⚙️ Setting up environment...${NC}"

# Create .env file
cat > $PROJECT_DIR/.env << EOF
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASS=$DB_PASS

# API Keys (Please update these)
FINNHUB_API_KEY=your_finnhub_api_key_here
POLYGON_API_KEY=your_polygon_api_key_here
BENZINGA_API_KEY=your_benzinga_api_key_here

# Google Analytics
GA_MEASUREMENT_ID=G-E6DJ7N6W1L
GA_ENABLED=true
GA_DEBUG_MODE=false

# Google Analytics Stream Info
GA_STREAM_NAME=EarningsTable.com
GA_STREAM_URL=https://earningstable.com
GA_STREAM_ID=12120280480

# Environment
APP_ENV=production
APP_DEBUG=false
EOF

# ========================================
# 8. Setup Cron Jobs
# ========================================
echo -e "${YELLOW}⏰ Setting up cron jobs...${NC}"

# Create cron jobs
cat > /tmp/earnings_cron << EOF
# EarningsTable Cron Jobs
*/2 * * * * /usr/bin/php $PROJECT_DIR/cron/1_enhanced_master_cron.php >> $PROJECT_DIR/logs/master_cron.log 2>&1
0 0 * * * /usr/bin/php $PROJECT_DIR/cron/2_clear_old_data.php >> $PROJECT_DIR/logs/clear_old_data.log 2>&1
0 1 * * * /usr/bin/php $PROJECT_DIR/cron/3_daily_data_setup_static.php >> $PROJECT_DIR/logs/daily_setup.log 2>&1
*/5 * * * * /usr/bin/php $PROJECT_DIR/cron/4_regular_data_updates_dynamic.php >> $PROJECT_DIR/logs/regular_updates.log 2>&1
0 2 * * * /usr/bin/php $PROJECT_DIR/cron/5_benzinga_guidance_updates.php >> $PROJECT_DIR/logs/benzinga_updates.log 2>&1
EOF

# Install cron jobs
crontab /tmp/earnings_cron
rm /tmp/earnings_cron

# ========================================
# 9. Setup SSL Certificate
# ========================================
echo -e "${YELLOW}🔒 Setting up SSL certificate...${NC}"

# Install Certbot
apt install -y certbot python3-certbot-apache

echo "To setup SSL certificate, run:"
echo "sudo certbot --apache -d earnings-table.com -d www.earnings-table.com"

# ========================================
# 10. Configure Firewall
# ========================================
echo -e "${YELLOW}🔥 Configuring firewall...${NC}"

# Install UFW
apt install -y ufw

# Configure firewall
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# ========================================
# 11. Setup Log Rotation
# ========================================
echo -e "${YELLOW}📝 Setting up log rotation...${NC}"

cat > /etc/logrotate.d/earnings-table << EOF
$PROJECT_DIR/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload apache2
    endscript
}
EOF

# ========================================
# 12. Final Configuration
# ========================================
echo -e "${YELLOW}🎯 Final configuration...${NC}"

# Restart services
systemctl restart apache2
systemctl restart mysql

# ========================================
# 13. Summary
# ========================================
echo -e "${GREEN}✅ VPS Setup Complete!${NC}"
echo "=================================="
echo -e "${BLUE}📋 Summary:${NC}"
echo "Project Directory: $PROJECT_DIR"
echo "Web Root: $PROJECT_DIR/public"
echo "Database: $DB_NAME"
echo "DB User: $DB_USER"
echo "DB Password: $DB_PASS"
echo ""
echo -e "${YELLOW}🔧 Next Steps:${NC}"
echo "1. Update API keys in $PROJECT_DIR/.env"
echo "2. Setup SSL certificate: sudo certbot --apache -d earnings-table.com"
echo "3. Configure DNS records to point to this server"
echo "4. Test the application: http://your-server-ip"
echo ""
echo -e "${YELLOW}📊 Monitoring:${NC}"
echo "Apache Status: systemctl status apache2"
echo "MySQL Status: systemctl status mysql"
echo "Cron Jobs: crontab -l"
echo "Logs: tail -f $PROJECT_DIR/logs/*.log"
echo ""
echo -e "${GREEN}🎉 EarningsTable is ready!${NC}"
