# Test Runner for EarningsTable
# PowerShell script to run all tests

Write-Host "EarningsTable Test Runner" -ForegroundColor Cyan
Write-Host "=========================" -ForegroundColor Cyan
Write-Host ""

# Function to find PHP executable
function Find-PHP {
    $phpPaths = @(
        "php.exe",
        "C:\xampp\php\php.exe",
        "C:\wamp64\bin\php\php8.1.0\php.exe",
        "C:\wamp64\bin\php\php8.0.0\php.exe",
        "C:\wamp64\bin\php\php7.4.0\php.exe",
        "C:\php\php.exe",
        "C:\Program Files\PHP\php.exe"
    )
    
    foreach ($path in $phpPaths) {
        if (Get-Command $path -ErrorAction SilentlyContinue) {
            return $path
        }
        if (Test-Path $path) {
            return $path
        }
    }
    
    return $null
}

# Function to run test
function Run-Test {
    param(
        [string]$TestName,
        [string]$TestFile,
        [string]$Description
    )
    
    Write-Host "Running $TestName..." -ForegroundColor Yellow
    Write-Host "   $Description" -ForegroundColor Gray
    
    try {
        $result = & $phpPath $TestFile 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "   PASSED: $TestName" -ForegroundColor Green
            return $true
        } else {
            Write-Host "   FAILED: $TestName" -ForegroundColor Red
            Write-Host "   Error: $result" -ForegroundColor Red
            return $false
        }
    } catch {
        Write-Host "   FAILED: $TestName with exception" -ForegroundColor Red
        Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Find PHP
Write-Host "Looking for PHP executable..." -ForegroundColor Yellow
$phpPath = Find-PHP

if ($null -eq $phpPath) {
    Write-Host "PHP not found!" -ForegroundColor Red
    Write-Host "Please install PHP using one of these methods:" -ForegroundColor Yellow
    Write-Host "1. XAMPP: https://www.apachefriends.org/" -ForegroundColor White
    Write-Host "2. WAMP: https://www.wampserver.com/" -ForegroundColor White
    Write-Host "3. Chocolatey: choco install php (as Administrator)" -ForegroundColor White
    Write-Host "4. Manual: https://windows.php.net/download/" -ForegroundColor White
    exit 1
}

Write-Host "Found PHP at: $phpPath" -ForegroundColor Green

# Check PHP version
$phpVersion = & $phpPath -v | Select-Object -First 1
Write-Host "PHP Version: $phpVersion" -ForegroundColor Cyan
Write-Host ""

# Define tests
$tests = @(
    @{
        Name = "Database Connection Test"
        File = "Tests\test-db.php"
        Description = "Tests database connection and basic functionality"
    },
    @{
        Name = "Path Test"
        File = "Tests\test-path.php"
        Description = "Tests file paths and cron job configuration"
    },
    @{
        Name = "Ticker Data Test"
        File = "Tests\check_tickers.php"
        Description = "Tests database data and sample records"
    },
    @{
        Name = "API Test"
        File = "Tests\test_api.php"
        Description = "Tests API endpoint and main SQL queries"
    },
    @{
        Name = "Security Headers Test"
        File = "Tests\test_security_headers.php"
        Description = "Tests HTTPS enforcement and security headers"
    },
    @{
        Name = "SQL Injection Test"
        File = "Tests\test_sql_injection.php"
        Description = "Tests SQL injection protection"
    },
    @{
        Name = "Rate Limiting Test"
        File = "Tests\test_rate_limiting.php"
        Description = "Tests rate limiting functionality"
    },
    @{
        Name = "Environment Test"
        File = "Tests\test_env.php"
        Description = "Tests environment variables and configuration"
    }
)

# Run tests
$passedTests = 0
$totalTests = $tests.Count

Write-Host "Starting test execution..." -ForegroundColor Cyan
Write-Host ""

foreach ($test in $tests) {
    if (Test-Path $test.File) {
        $result = Run-Test -TestName $test.Name -TestFile $test.File -Description $test.Description
        if ($result) {
            $passedTests++
        }
    } else {
        Write-Host "Test file not found: $($test.File)" -ForegroundColor Yellow
    }
    Write-Host ""
}

# Summary
Write-Host "Test Summary" -ForegroundColor Cyan
Write-Host "============" -ForegroundColor Cyan
Write-Host "Total tests: $totalTests" -ForegroundColor White
Write-Host "Passed: $passedTests" -ForegroundColor Green
Write-Host "Failed: $($totalTests - $passedTests)" -ForegroundColor Red

if ($passedTests -eq $totalTests) {
    Write-Host ""
    Write-Host "All tests passed!" -ForegroundColor Green
    Write-Host "Your EarningsTable installation is working correctly." -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "Some tests failed." -ForegroundColor Yellow
    Write-Host "Please check the error messages above and fix the issues." -ForegroundColor Yellow
    Write-Host "See docs/TEST_EXECUTION_GUIDE.md for troubleshooting help." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "For more information:" -ForegroundColor Cyan
Write-Host "- Test documentation: docs/TEST_SUMMARY.md" -ForegroundColor White
Write-Host "- Execution guide: docs/TEST_EXECUTION_GUIDE.md" -ForegroundColor White
Write-Host "- Configuration: config/" -ForegroundColor White
Write-Host "- Scripts: scripts/" -ForegroundColor White
