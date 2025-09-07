# Fix Apache Script
Write-Host "Fix Apache Script" -ForegroundColor Green
Write-Host "=================" -ForegroundColor Green
Write-Host ""

# Deactivate default site
Write-Host "1. Deactivating default site..." -ForegroundColor Cyan
ssh root@89.185.250.213 "a2dissite 000-default.conf"

# Reload Apache
Write-Host "2. Reloading Apache..." -ForegroundColor Cyan
ssh root@89.185.250.213 "systemctl reload apache2"

# Check active sites
Write-Host "3. Checking active sites..." -ForegroundColor Cyan
ssh root@89.185.250.213 "ls -la /etc/apache2/sites-enabled/"

# Test Apache config
Write-Host "4. Testing Apache config..." -ForegroundColor Cyan
ssh root@89.185.250.213 "apache2ctl configtest"

# Test localhost
Write-Host "5. Testing localhost..." -ForegroundColor Cyan
ssh root@89.185.250.213 "curl -I http://localhost/dashboard-fixed.html"

# Test IP
Write-Host "6. Testing IP..." -ForegroundColor Cyan
ssh root@89.185.250.213 "curl -I http://89.185.250.213/dashboard-fixed.html"

# Check .htaccess
Write-Host "7. Checking .htaccess..." -ForegroundColor Cyan
ssh root@89.185.250.213 "ls -la /var/www/html/EarningsTable/public/.htaccess"

# Create .htaccess if needed
Write-Host "8. Creating .htaccess..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cat > /var/www/html/EarningsTable/public/.htaccess << 'EOF'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
EOF"

# Test again
Write-Host "9. Testing again..." -ForegroundColor Cyan
ssh root@89.185.250.213 "curl -I http://localhost/dashboard-fixed.html"

# Test info.php
Write-Host "10. Testing info.php..." -ForegroundColor Cyan
ssh root@89.185.250.213 "curl -I http://localhost/info.php"

Write-Host ""
Write-Host "Apache fix completed!" -ForegroundColor Green
Write-Host ""
Write-Host "Application should be available at:" -ForegroundColor Cyan
Write-Host "http://89.185.250.213/dashboard-fixed.html" -ForegroundColor White
Write-Host "http://89.185.250.213/info.php" -ForegroundColor White
