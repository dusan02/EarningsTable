# PowerShell script to refresh Task Scheduler tasks with correct PHP paths
# Run as Administrator

Write-Host "Refreshing Task Scheduler tasks with correct PHP paths..." -ForegroundColor Green

# First, remove existing tasks
Write-Host "Removing existing tasks..." -ForegroundColor Yellow
$tasksToRemove = @(
    "EarningsTable_DailyFetch",
    "EarningsTable_PricesUpdate", 
    "EarningsTable_EPSUpdate",
    "EarningsTable_Cache",
    "EarningsTable_FetchTickers",
    "EarningsTable_UpdateEPS",
    "EarningsTable_UpdatePrices",
    "EarningsTable_CacheShares"
)

foreach ($taskName in $tasksToRemove) {
    try {
        Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction SilentlyContinue
        Write-Host "✓ Removed task: $taskName" -ForegroundColor Green
    }
    catch {
        Write-Host "- Task not found or already removed: $taskName" -ForegroundColor Yellow
    }
}

# Find PHP path
$phpPath = $null
$possiblePaths = @(
    "D:\xampp\php\php.exe",
    "C:\xampp\php\php.exe",
    "C:\Program Files\xampp\php\php.exe",
    "C:\Program Files (x86)\xampp\php\php.exe",
    "C:\php\php.exe",
    "C:\Program Files\PHP\php.exe",
    "C:\Program Files (x86)\PHP\php.exe"
)

foreach ($path in $possiblePaths) {
    if (Test-Path $path) {
        $phpPath = $path
        Write-Host "✓ Using PHP at: $phpPath" -ForegroundColor Green
        break
    }
}

if (-not $phpPath) {
    Write-Host "✗ PHP not found! Please install XAMPP or PHP first." -ForegroundColor Red
    exit 1
}

# Update batch files with correct PHP path
Write-Host "Updating batch files..." -ForegroundColor Yellow
$batchFiles = @(
    "scripts\run_earnings_fetch.bat",
    "scripts\run_prices_update.bat", 
    "scripts\run_eps_update.bat"
)

foreach ($batchFile in $batchFiles) {
    if (Test-Path $batchFile) {
        $content = Get-Content $batchFile -Raw
        # Replace any existing PHP path with the correct one
        $newContent = $content -replace ".*\\php\.exe", $phpPath
        Set-Content $batchFile $newContent -Encoding ASCII
        Write-Host "✓ Updated: $batchFile" -ForegroundColor Green
    }
}

# Get current user
$currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name

# Base path
$basePath = "D:\Projects\EarningsTable"
$scriptsPath = "$basePath\scripts"

# Create new tasks with correct paths
Write-Host "Creating new tasks..." -ForegroundColor Yellow

# 1. Daily Earnings Fetch Task (02:15 CET = 01:15 UTC)
Write-Host "Creating Daily Earnings Fetch Task..." -ForegroundColor Yellow
$action1 = New-ScheduledTaskAction -Execute "$scriptsPath\run_earnings_fetch.bat" -WorkingDirectory $basePath
$trigger1 = New-ScheduledTaskTrigger -Daily -At "02:15"
$principal1 = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Highest
$settings1 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName "EarningsTable_DailyFetch" -Action $action1 -Trigger $trigger1 -Principal $principal1 -Settings $settings1 -Description "Daily earnings fetch at 02:15 CET"

# 2. Prices Update Task (every 5 minutes)
Write-Host "Creating Prices Update Task..." -ForegroundColor Yellow
$action2 = New-ScheduledTaskAction -Execute "$scriptsPath\run_prices_update.bat" -WorkingDirectory $basePath
$trigger2 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 5) -RepetitionDuration (New-TimeSpan -Days 365)
$principal2 = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Highest
$settings2 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName "EarningsTable_PricesUpdate" -Action $action2 -Trigger $trigger2 -Principal $principal2 -Settings $settings2 -Description "Prices update every 5 minutes"

# 3. EPS Update Task (every 5 minutes)
Write-Host "Creating EPS Update Task..." -ForegroundColor Yellow
$action3 = New-ScheduledTaskAction -Execute "$scriptsPath\run_eps_update.bat" -WorkingDirectory $basePath
$trigger3 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 5) -RepetitionDuration (New-TimeSpan -Days 365)
$principal3 = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Highest
$settings3 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName "EarningsTable_EPSUpdate" -Action $action3 -Trigger $trigger3 -Principal $principal3 -Settings $settings3 -Description "EPS update every 5 minutes"

# Test one task manually
Write-Host "`nTesting task execution..." -ForegroundColor Yellow
try {
    Start-ScheduledTask -TaskName "EarningsTable_PricesUpdate"
    Write-Host "✓ Test task started successfully" -ForegroundColor Green
    
    # Wait a moment and check status
    Start-Sleep -Seconds 3
    $task = Get-ScheduledTask -TaskName "EarningsTable_PricesUpdate"
    Write-Host "Task status: $($task.State)" -ForegroundColor Cyan
}
catch {
    Write-Host "✗ Task test failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nTask Scheduler refresh completed!" -ForegroundColor Green
Write-Host "Tasks created:" -ForegroundColor Cyan
Write-Host "  - EarningsTable_DailyFetch (02:15 CET daily)" -ForegroundColor White
Write-Host "  - EarningsTable_PricesUpdate (every 5 minutes)" -ForegroundColor White
Write-Host "  - EarningsTable_EPSUpdate (every 5 minutes)" -ForegroundColor White
Write-Host ""
Write-Host "To view tasks: taskschd.msc" -ForegroundColor Yellow
Write-Host "PHP path used: $phpPath" -ForegroundColor Cyan
