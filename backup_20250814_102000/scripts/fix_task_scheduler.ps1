Write-Host "Fixing Task Scheduler tasks..." -ForegroundColor Green

# Remove existing tasks
$tasksToRemove = @(
    "EarningsTable_CacheShares",
    "EarningsTable_FetchTickers", 
    "EarningsTable_UpdateEPS",
    "EarningsTable_UpdatePrices"
)

foreach ($taskName in $tasksToRemove) {
    try {
        Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction SilentlyContinue
        Write-Host "Removed task: $taskName" -ForegroundColor Green
    }
    catch {
        Write-Host "Task not found: $taskName" -ForegroundColor Yellow
    }
}

# Get current user
$currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$basePath = "D:\Projects\EarningsTable"

# Create new tasks with correct configuration
Write-Host "Creating new tasks..." -ForegroundColor Yellow

# 1. Fetch Tickers Task (daily at 02:15)
$action1 = New-ScheduledTaskAction -Execute "D:\xampp\php\php.exe" -Argument "cron\fetch_earnings_tickers.php" -WorkingDirectory $basePath
$trigger1 = New-ScheduledTaskTrigger -Daily -At "02:15"
$principal1 = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Highest
$settings1 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName "EarningsTable_FetchTickers" -Action $action1 -Trigger $trigger1 -Principal $principal1 -Settings $settings1 -Description "Daily earnings fetch at 02:15 CET"

# 2. Update Prices Task (every 5 minutes)
$action2 = New-ScheduledTaskAction -Execute "D:\xampp\php\php.exe" -Argument "cron\current_prices_mcaps_updates.php" -WorkingDirectory $basePath
$trigger2 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 5) -RepetitionDuration (New-TimeSpan -Days 365)
$principal2 = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Highest
$settings2 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName "EarningsTable_UpdatePrices" -Action $action2 -Trigger $trigger2 -Principal $principal2 -Settings $settings2 -Description "Prices update every 5 minutes"

# 3. Update EPS Task (every 5 minutes)
$action3 = New-ScheduledTaskAction -Execute "D:\xampp\php\php.exe" -Argument "cron\update_earnings_eps_revenues.php" -WorkingDirectory $basePath
$trigger3 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 5) -RepetitionDuration (New-TimeSpan -Days 365)
$principal3 = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Highest
$settings3 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName "EarningsTable_UpdateEPS" -Action $action3 -Trigger $trigger3 -Principal $principal3 -Settings $settings3 -Description "EPS update every 5 minutes"

# 4. Cache Shares Task (daily at 06:00)
$action4 = New-ScheduledTaskAction -Execute "D:\xampp\php\php.exe" -Argument "cron\cache_shares_outstanding.php" -WorkingDirectory $basePath
$trigger4 = New-ScheduledTaskTrigger -Daily -At "06:00"
$principal4 = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Highest
$settings4 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName "EarningsTable_CacheShares" -Action $action4 -Trigger $trigger4 -Principal $principal4 -Settings $settings4 -Description "Cache shares outstanding daily at 06:00"

Write-Host "Tasks created successfully!" -ForegroundColor Green
Write-Host "Tasks:" -ForegroundColor Cyan
Write-Host "  - EarningsTable_FetchTickers (02:15 daily)" -ForegroundColor White
Write-Host "  - EarningsTable_UpdatePrices (every 5 minutes)" -ForegroundColor White
Write-Host "  - EarningsTable_UpdateEPS (every 5 minutes)" -ForegroundColor White
Write-Host "  - EarningsTable_CacheShares (06:00 daily)" -ForegroundColor White
