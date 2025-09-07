# Fix Apache Final Script
Write-Host "Fix Apache Final Script" -ForegroundColor Green
Write-Host "=======================" -ForegroundColor Green
Write-Host ""

# Fix Virtual Host
Write-Host "1. Fixing Virtual Host..." -ForegroundColor Cyan
ssh root@89.185.250.213 "cat > /etc/apache2/sites-available/earnings-table.conf << 'EOF'
<VirtualHost *:80>
    ServerName earningstable.com
    ServerAlias www.earningstable.com
    DocumentRoot /var/www/html/EarningsTable/public

    <Directory /var/www/html/EarningsTable/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/earnings-table_error.log
    CustomLog \${APACHE_LOG_DIR}/earnings-table_access.log combined
</VirtualHost>
EOF"

# Test Apache config
Write-Host "2. Testing Apache config..." -ForegroundColor Cyan
ssh root@89.185.250.213 "apache2ctl configtest"

# Restart Apache
Write-Host "3. Restarting Apache..." -ForegroundColor Cyan
ssh root@89.185.250.213 "systemctl restart apache2"

# Check Apache status
Write-Host "4. Checking Apache status..." -ForegroundColor Cyan
ssh root@89.185.250.213 "systemctl status apache2"

# Test HTTP
Write-Host "5. Testing HTTP..." -ForegroundColor Cyan
ssh root@89.185.250.213 "curl -I http://earningstable.com"

# Test HTTPS
Write-Host "6. Testing HTTPS..." -ForegroundColor Cyan
ssh root@89.185.250.213 "curl -I https://earningstable.com"

Write-Host ""
Write-Host "Apache fix completed!" -ForegroundColor Green
Write-Host ""
Write-Host "Application should be available at:" -ForegroundColor Cyan
Write-Host "http://earningstable.com" -ForegroundColor White
Write-Host "http://www.earningstable.com" -ForegroundColor White
