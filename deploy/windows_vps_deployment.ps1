# 🚀 Windows PowerShell VPS Deployment Script
# Pre EarningsTable deployment na VPS

param(
    [Parameter(Mandatory = $true)]
    [string]$VpsIp,
    
    [Parameter(Mandatory = $true)]
    [string]$VpsUser = "root",
    
    [Parameter(Mandatory = $true)]
    [string]$Domain,
    
    [Parameter(Mandatory = $true)]
    [string]$DbPassword
)

Write-Host "🚀 EarningsTable VPS Deployment Script" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green

# Check if required tools are installed
function Test-Command {
    param($Command)
    try {
        if (Get-Command $Command -ErrorAction Stop) {
            return $true
        }
    }
    catch {
        return $false
    }
}

# Check prerequisites
Write-Host "🔍 Checking prerequisites..." -ForegroundColor Yellow

if (-not (Test-Command "ssh")) {
    Write-Host "❌ SSH client not found. Please install OpenSSH or use PuTTY." -ForegroundColor Red
    exit 1
}

if (-not (Test-Command "scp")) {
    Write-Host "❌ SCP client not found. Please install OpenSSH or use WinSCP." -ForegroundColor Red
    exit 1
}

Write-Host "✅ Prerequisites check passed" -ForegroundColor Green

# Create temporary directory
$TempDir = "temp_upload"
$ProjectDir = "$TempDir\earningstable"

Write-Host "📁 Creating upload package..." -ForegroundColor Yellow

if (Test-Path $TempDir) {
    Remove-Item -Recurse -Force $TempDir
}
New-Item -ItemType Directory -Path $ProjectDir -Force | Out-Null

# Copy project files
$FoldersToCopy = @("public", "common", "config", "cron", "sql", "utils", "scripts", "deploy", "vendor", "logs", "storage")
$FilesToCopy = @("composer.json", "composer.lock", "README.md", "web.config")

foreach ($folder in $FoldersToCopy) {
    if (Test-Path $folder) {
        Copy-Item -Recurse -Path $folder -Destination $ProjectDir
        Write-Host "  ✅ Copied $folder" -ForegroundColor Green
    }
}

foreach ($file in $FilesToCopy) {
    if (Test-Path $file) {
        Copy-Item -Path $file -Destination $ProjectDir
        Write-Host "  ✅ Copied $file" -ForegroundColor Green
    }
}

# Create archive
Write-Host "📦 Creating archive..." -ForegroundColor Yellow
Compress-Archive -Path $ProjectDir -DestinationPath "$TempDir\earningstable.zip" -Force

# Upload to VPS
Write-Host "📤 Uploading to VPS..." -ForegroundColor Yellow
scp "$TempDir\earningstable.zip" "${VpsUser}@${VpsIp}:/tmp/"

# Extract on VPS
Write-Host "🔧 Extracting on VPS..." -ForegroundColor Yellow
$ExtractCommand = @"
cd /var/www
unzip -o /tmp/earningstable.zip
chown -R www-data:www-data /var/www/earningstable
chmod -R 755 /var/www/earningstable
"@

ssh "${VpsUser}@${VpsIp}" $ExtractCommand

# Setup database
Write-Host "🗄️ Setting up database..." -ForegroundColor Yellow
$DbCommand = @"
cd /var/www/earningstable
mysql -u earningstable_user -p$DbPassword earnings_db < sql/setup_database.sql
mysql -u earningstable_user -p$DbPassword earnings_db < sql/setup_all_tables.sql
"@

ssh "${VpsUser}@${VpsIp}" $DbCommand

# Run tests
Write-Host "🧪 Running tests..." -ForegroundColor Yellow
ssh "${VpsUser}@${VpsIp}" "cd /var/www/earningstable && php Tests/master_test.php"

# Setup SSL
Write-Host "🔒 Setting up SSL..." -ForegroundColor Yellow
ssh "${VpsUser}@${VpsIp}" "certbot --apache -d $Domain --non-interactive --agree-tos --email admin@$Domain"

# Cleanup
Write-Host "🧹 Cleaning up..." -ForegroundColor Yellow
Remove-Item -Recurse -Force $TempDir
ssh "${VpsUser}@${VpsIp}" "rm /tmp/earningstable.zip"

Write-Host ""
Write-Host "✅ Deployment completed successfully!" -ForegroundColor Green
Write-Host "🌐 Your site should be available at: https://$Domain" -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 Next steps:" -ForegroundColor Yellow
Write-Host "1. Test the website" -ForegroundColor White
Write-Host "2. Check cron jobs are running" -ForegroundColor White
Write-Host "3. Monitor logs" -ForegroundColor White
Write-Host ""
Write-Host "🔧 Useful commands:" -ForegroundColor Yellow
Write-Host "ssh $VpsUser@$VpsIp" -ForegroundColor White
Write-Host "tail -f /var/log/apache2/earningstable_error.log" -ForegroundColor White
Write-Host "systemctl status apache2 mysql" -ForegroundColor White
