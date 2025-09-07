# Quick Update Script
Write-Host "Quick Update Script" -ForegroundColor Green
Write-Host "===================" -ForegroundColor Green
Write-Host ""

# Update from GitHub
Write-Host "1. Updating from GitHub..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && git pull origin main"

# Update dependencies
Write-Host "2. Updating dependencies..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader"

# Set permissions
Write-Host "3. Setting permissions..." -ForegroundColor Cyan
ssh root@89.185.250.213 "chown -R www-data:www-data /var/www/html/EarningsTable"
ssh root@89.185.250.213 "chmod -R 755 /var/www/html/EarningsTable"
ssh root@89.185.250.213 "chmod -R 777 /var/www/html/EarningsTable/storage"
ssh root@89.185.250.213 "chmod -R 777 /var/www/html/EarningsTable/logs"

# Clear cache
Write-Host "4. Clearing cache..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cd /var/www/html/EarningsTable && rm -rf storage/cache/*"

# Test
Write-Host "5. Testing..." -ForegroundColor Cyan
ssh root@89.185.250.213 "curl -I http://localhost/dashboard-fixed.html"

Write-Host ""
Write-Host "Update completed!" -ForegroundColor Green
Write-Host "Application available at: http://89.185.250.213/dashboard-fixed.html" -ForegroundColor Cyan
