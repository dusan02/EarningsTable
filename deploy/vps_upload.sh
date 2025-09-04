#!/bin/bash

# 🚀 VPS Upload Script for EarningsTable
# Linux/Mac script to upload project to VPS

set -e  # Exit on any error

echo "🚀 EarningsTable VPS Upload Script"
echo "=================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Get VPS details
read -p "Enter VPS IP address: " VPS_IP
read -p "Enter VPS username (usually 'root'): " VPS_USER
read -p "Enter domain name: " DOMAIN

if [ -z "$VPS_IP" ] || [ -z "$VPS_USER" ] || [ -z "$DOMAIN" ]; then
    print_error "All fields are required"
    exit 1
fi

echo
print_status "Creating upload package..."

# Create temporary directory
mkdir -p temp_upload/earningstable

# Copy project files (excluding unnecessary files)
cp -r public temp_upload/earningstable/
cp -r common temp_upload/earningstable/
cp -r config temp_upload/earningstable/
cp -r cron temp_upload/earningstable/
cp -r sql temp_upload/earningstable/
cp -r utils temp_upload/earningstable/
cp -r scripts temp_upload/earningstable/
cp -r deploy temp_upload/earningstable/
cp -r vendor temp_upload/earningstable/
cp -r logs temp_upload/earningstable/
cp -r storage temp_upload/earningstable/

# Copy individual files
cp composer.json temp_upload/earningstable/
cp composer.lock temp_upload/earningstable/
cp README.md temp_upload/earningstable/
cp web.config temp_upload/earningstable/

# Create upload package
cd temp_upload
tar -czf earningstable.tar.gz earningstable/
cd ..

print_status "Uploading to VPS..."
scp temp_upload/earningstable.tar.gz $VPS_USER@$VPS_IP:/tmp/

print_status "Extracting on VPS..."
ssh $VPS_USER@$VPS_IP "cd /var/www && tar -xzf /tmp/earningstable.tar.gz && chown -R www-data:www-data /var/www/earningstable && chmod -R 755 /var/www/earningstable"

print_status "Setting up database..."
ssh $VPS_USER@$VPS_IP "cd /var/www/earningstable && mysql -u earningstable_user -p earnings_db < sql/setup_database.sql"
ssh $VPS_USER@$VPS_IP "cd /var/www/earningstable && mysql -u earningstable_user -p earnings_db < sql/setup_all_tables.sql"

print_status "Running tests..."
ssh $VPS_USER@$VPS_IP "cd /var/www/earningstable && php Tests/master_test.php"

print_status "Setting up SSL..."
ssh $VPS_USER@$VPS_IP "certbot --apache -d $DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN"

print_status "Cleaning up..."
rm -rf temp_upload
ssh $VPS_USER@$VPS_IP "rm /tmp/earningstable.tar.gz"

echo
print_status "Upload completed successfully!"
echo "🌐 Your site should be available at: https://$DOMAIN"
echo
echo "📋 Next steps:"
echo "1. Test the website"
echo "2. Check cron jobs are running"
echo "3. Monitor logs"
echo
