#!/bin/bash

# ========================================
# EarningsTable Auto Deployment Script
# ========================================

set -e  # Exit on any error

echo "🚀 EarningsTable Auto Deployment Script"
echo "=================================="

# VPS Configuration
VPS_IP="89.185.250.213"
VPS_USER="root"
VPS_PASS="EJXTfBOG2t"
PROJECT_DIR="/var/www/html/EarningsTable"

echo "📋 VPS Configuration:"
echo "IP: $VPS_IP"
echo "User: $VPS_USER"
echo "Project Dir: $PROJECT_DIR"
echo ""

# Function to run commands on VPS
run_on_vps() {
    sshpass -p "$VPS_PASS" ssh -o StrictHostKeyChecking=no "$VPS_USER@$VPS_IP" "$1"
}

# Function to copy files to VPS
copy_to_vps() {
    sshpass -p "$VPS_PASS" scp -o StrictHostKeyChecking=no -r "$1" "$VPS_USER@$VPS_IP:$2"
}

echo "🔧 Step 1: Updating system..."
run_on_vps "apt update && apt upgrade -y"

echo "🔧 Step 2: Installing LAMP stack..."
run_on_vps "apt install -y apache2 mysql-server software-properties-common"
run_on_vps "add-apt-repository -y ppa:ondrej/php && apt update"
run_on_vps "apt install -y php8.0 php8.0-mysql php8.0-curl php8.0-json php8.0-mbstring php8.0-xml php8.0-zip php8.0-gd php8.0-cli php8.0-common php8.0-opcache php8.0-readline"

echo "🔧 Step 3: Installing Composer..."
run_on_vps "curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer && chmod +x /usr/local/bin/composer"

echo "🔧 Step 4: Configuring Apache..."
run_on_vps "a2enmod rewrite ssl headers"
run_on_vps "a2dissite 000-default"

# Create virtual host
run_on_vps "cat > /etc/apache2/sites-available/earnings-table.com.conf << 'EOF'
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
EOF"

run_on_vps "a2ensite earnings-table.com.conf && systemctl reload apache2"

echo "🔧 Step 5: Configuring MySQL..."
run_on_vps "systemctl enable mysql && systemctl start mysql"

# Secure MySQL (automated)
run_on_vps "mysql -e \"ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$VPS_PASS';\""
run_on_vps "mysql -u root -p$VPS_PASS -e \"DELETE FROM mysql.user WHERE User='';\""
run_on_vps "mysql -u root -p$VPS_PASS -e \"DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');\""
run_on_vps "mysql -u root -p$VPS_PASS -e \"DROP DATABASE IF EXISTS test;\""
run_on_vps "mysql -u root -p$VPS_PASS -e \"DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';\""
run_on_vps "mysql -u root -p$VPS_PASS -e \"FLUSH PRIVILEGES;\""

# Create database and user
run_on_vps "mysql -u root -p$VPS_PASS << 'EOF'
CREATE DATABASE IF NOT EXISTS earnings_table CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'earnings_user'@'localhost' IDENTIFIED BY '$VPS_PASS';
GRANT ALL PRIVILEGES ON earnings_table.* TO 'earnings_user'@'localhost';
FLUSH PRIVILEGES;
EOF"

echo "🔧 Step 6: Cloning project..."
run_on_vps "cd /var/www/html && git clone https://github.com/dusan02/EarningsTable.git"

echo "🔧 Step 7: Setting permissions..."
run_on_vps "chown -R www-data:www-data $PROJECT_DIR"
run_on_vps "chmod -R 755 $PROJECT_DIR"
run_on_vps "chmod -R 777 $PROJECT_DIR/storage"
run_on_vps "chmod -R 777 $PROJECT_DIR/logs"

echo "🔧 Step 8: Installing dependencies..."
run_on_vps "cd $PROJECT_DIR && composer install --no-dev --optimize-autoloader"

echo "🔧 Step 9: Creating .env file..."
run_on_vps "cat > $PROJECT_DIR/.env << 'EOF'
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=earnings_table
DB_USER=earnings_user
DB_PASS=$VPS_PASS

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
EOF"

echo "🔧 Step 10: Setting up cron jobs..."
run_on_vps "cat > /tmp/earnings_cron << 'EOF'
# EarningsTable Cron Jobs
*/2 * * * * /usr/bin/php $PROJECT_DIR/cron/1_enhanced_master_cron.php >> $PROJECT_DIR/logs/master_cron.log 2>&1
0 0 * * * /usr/bin/php $PROJECT_DIR/cron/2_clear_old_data.php >> $PROJECT_DIR/logs/clear_old_data.log 2>&1
0 1 * * * /usr/bin/php $PROJECT_DIR/cron/3_daily_data_setup_static.php >> $PROJECT_DIR/logs/daily_setup.log 2>&1
*/5 * * * * /usr/bin/php $PROJECT_DIR/cron/4_regular_data_updates_dynamic.php >> $PROJECT_DIR/logs/regular_updates.log 2>&1
0 2 * * * /usr/bin/php $PROJECT_DIR/cron/5_benzinga_guidance_updates.php >> $PROJECT_DIR/logs/benzinga_updates.log 2>&1
EOF"

run_on_vps "crontab /tmp/earnings_cron && rm /tmp/earnings_cron"

echo "🔧 Step 11: Configuring firewall..."
run_on_vps "apt install -y ufw"
run_on_vps "ufw default deny incoming"
run_on_vps "ufw default allow outgoing"
run_on_vps "ufw allow ssh"
run_on_vps "ufw allow 80/tcp"
run_on_vps "ufw allow 443/tcp"
run_on_vps "ufw --force enable"

echo "🔧 Step 12: Final configuration..."
run_on_vps "systemctl restart apache2"
run_on_vps "systemctl restart mysql"

echo "✅ Deployment Complete!"
echo "=================================="
echo "🌐 Your EarningsTable is now available at:"
echo "   http://$VPS_IP/dashboard-fixed.php"
echo ""
echo "📋 Next steps:"
echo "1. Update API keys in $PROJECT_DIR/.env"
echo "2. Configure DNS records to point to $VPS_IP"
echo "3. Setup SSL certificate: certbot --apache -d earnings-table.com"
echo "4. Test the application"
echo ""
echo "🎉 EarningsTable is ready!"
