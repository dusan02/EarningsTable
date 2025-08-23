# PowerShell script to fix PHP path issues after Windows update
# Run as Administrator

Write-Host "Diagnosing PHP path issues after Windows update..." -ForegroundColor Green

# Check if XAMPP PHP exists at the expected path
$expectedPhpPath = "D:\xampp\php\php.exe"
$phpExists = Test-Path $expectedPhpPath

if ($phpExists) {
    Write-Host "✓ PHP found at: $expectedPhpPath" -ForegroundColor Green
    
    # Test PHP execution
    try {
        $phpVersion = & $expectedPhpPath -v 2>&1
        Write-Host "✓ PHP version:" -ForegroundColor Green
        Write-Host $phpVersion[0] -ForegroundColor White
    }
    catch {
        Write-Host "✗ PHP execution failed: $($_.Exception.Message)" -ForegroundColor Red
    }
}
else {
    Write-Host "✗ PHP not found at: $expectedPhpPath" -ForegroundColor Red
    
    # Search for PHP in common locations
    Write-Host "Searching for PHP in common locations..." -ForegroundColor Yellow
    
    $possiblePaths = @(
        "C:\xampp\php\php.exe",
        "C:\Program Files\xampp\php\php.exe",
        "C:\Program Files (x86)\xampp\php\php.exe",
        "C:\php\php.exe",
        "C:\Program Files\PHP\php.exe",
        "C:\Program Files (x86)\PHP\php.exe"
    )
    
    $foundPhp = $null
    foreach ($path in $possiblePaths) {
        if (Test-Path $path) {
            Write-Host "✓ Found PHP at: $path" -ForegroundColor Green
            $foundPhp = $path
            break
        }
    }
    
    if ($foundPhp) {
        Write-Host "Updating batch files with new PHP path..." -ForegroundColor Yellow
        
        # Update batch files with new PHP path
        $batchFiles = @(
            "scripts\run_earnings_fetch.bat",
            "scripts\run_prices_update.bat", 
            "scripts\run_eps_update.bat"
        )
        
        foreach ($batchFile in $batchFiles) {
            if (Test-Path $batchFile) {
                $content = Get-Content $batchFile -Raw
                $newContent = $content -replace "D:\\xampp\\php\\php\.exe", $foundPhp.Replace("\", "\\")
                Set-Content $batchFile $newContent -Encoding ASCII
                Write-Host "✓ Updated: $batchFile" -ForegroundColor Green
            }
        }
    }
    else {
        Write-Host "✗ PHP not found in any common location" -ForegroundColor Red
        Write-Host "Please install XAMPP or PHP manually" -ForegroundColor Yellow
    }
}

# Check if XAMPP is running
Write-Host "`nChecking XAMPP services..." -ForegroundColor Yellow
$apacheService = Get-Service -Name "Apache*" -ErrorAction SilentlyContinue
$mysqlService = Get-Service -Name "MySQL*" -ErrorAction SilentlyContinue

if ($apacheService) {
    Write-Host "Apache service: $($apacheService.Status)" -ForegroundColor $(if($apacheService.Status -eq "Running"){"Green"}else{"Red"})
} else {
    Write-Host "Apache service not found" -ForegroundColor Yellow
}

if ($mysqlService) {
    Write-Host "MySQL service: $($mysqlService.Status)" -ForegroundColor $(if($mysqlService.Status -eq "Running"){"Green"}else{"Red"})
} else {
    Write-Host "MySQL service not found" -ForegroundColor Yellow
}

# Test database connection
Write-Host "`nTesting database connection..." -ForegroundColor Yellow
try {
    $testScript = @'
<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=earnings_table', 'root', '');
    echo "Database connection: OK\n";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>
'@
    
    $testFile = "test_db_connection.php"
    Set-Content $testFile $testScript -Encoding UTF8
    
    if ($phpExists) {
        $result = & $expectedPhpPath $testFile 2>&1
        Write-Host $result -ForegroundColor $(if($result -like "*OK*"){"Green"}else{"Red"})
    }
    
    Remove-Item $testFile -ErrorAction SilentlyContinue
}
catch {
    Write-Host "Database test failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nDiagnosis completed!" -ForegroundColor Green
Write-Host "If PHP path was updated, you may need to restart Task Scheduler tasks." -ForegroundColor Yellow
