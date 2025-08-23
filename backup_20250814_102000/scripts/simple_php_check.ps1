# Simple PHP path check script
Write-Host "Checking PHP installation..." -ForegroundColor Green

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

Write-Host "`nPHP check completed!" -ForegroundColor Green
