# PowerShell script to cleanup and setup Task Scheduler tasks for Earnings Table
# Run as Administrator

Write-Host "Cleaning up and setting up Task Scheduler for Earnings Table..." -ForegroundColor Green

# Get current user
$currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name

# Base path
$basePath = "D:\Projects\EarningsTable"
$phpPath = "D:\xampp\php\php.exe"

Write-Host "Current user: $currentUser" -ForegroundColor Yellow
Write-Host "Base path: $basePath" -ForegroundColor Yellow
Write-Host "PHP path: $phpPath" -ForegroundColor Yellow

# Step 1: Delete all existing EarningsTable tasks
Write-Host "`nStep 1: Deleting existing EarningsTable tasks..." -ForegroundColor Yellow

$existingTasks = Get-ScheduledTask -TaskName "EarningsTable*" -ErrorAction SilentlyContinue
if ($existingTasks) {
    foreach ($task in $existingTasks) {
        Write-Host "Deleting task: $($task.TaskName)" -ForegroundColor Red
        Unregister-ScheduledTask -TaskName $task.TaskName -Confirm:$false
    }
    Write-Host "All existing tasks deleted successfully" -ForegroundColor Green
} else {
    Write-Host "No existing EarningsTable tasks found" -ForegroundColor Yellow
}

# Step 2: Create new tasks with correct configuration
Write-Host "`nStep 2: Creating new tasks..." -ForegroundColor Yellow

# 1. Daily Cleanup Task (02:00 AM daily)
Write-Host "Creating Daily Cleanup Task..." -ForegroundColor Cyan
$cleanupAction = New-ScheduledTaskAction -Execute $phpPath -Argument "cron\clear_old_data.php" -WorkingDirectory $basePath
$cleanupTrigger = New-ScheduledTaskTrigger -Daily -At "02:00"
$cleanupPrincipal = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Highest
$cleanupSettings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Hours 72)

Register-ScheduledTask -TaskName "EarningsTable_DailyCleanup" -Action $cleanupAction -Trigger $cleanupTrigger -Principal $cleanupPrincipal -Settings $cleanupSettings -Description "Daily cleanup of old earnings data at 02:00 AM"

# 2. Fetch Tickers Task (02:15 AM daily)
Write-Host "Creating Fetch Tickers Task..." -ForegroundColor Cyan
$fetchAction = New-ScheduledTaskAction -Execute $phpPath -Argument "cron\fetch_finnhub_earnings_today_tickers.php" -WorkingDirectory $basePath
$fetchTrigger = New-ScheduledTaskTrigger -Daily -At "02:15"
$fetchPrincipal = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Highest
$fetchSettings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Hours 72)

Register-ScheduledTask -TaskName "EarningsTable_FetchTickers" -Action $fetchAction -Trigger $fetchTrigger -Principal $fetchPrincipal -Settings $fetchSettings -Description "Daily fetch of earnings tickers from Finnhub at 02:15 AM"

# 3. Cache Shares Task (06:00 AM daily)
Write-Host "Creating Cache Shares Task..." -ForegroundColor Cyan
$cacheAction = New-ScheduledTaskAction -Execute $phpPath -Argument "cron\cache_shares_outstanding.php" -WorkingDirectory $basePath
$cacheTrigger = New-ScheduledTaskTrigger -Daily -At "06:00"
$cachePrincipal = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Highest
$cacheSettings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Hours 72)

Register-ScheduledTask -TaskName "EarningsTable_CacheShares" -Action $cacheAction -Trigger $cacheTrigger -Principal $cachePrincipal -Settings $cacheSettings -Description "Daily cache of shares outstanding data at 06:00 AM"

# 4. Update EPS Task (every 5 minutes, starting at 00:00 daily)
Write-Host "Creating Update EPS Task..." -ForegroundColor Cyan
$epsAction = New-ScheduledTaskAction -Execute $phpPath -Argument "cron\update_earnings_eps_revenues.php" -WorkingDirectory $basePath
$epsTrigger = New-ScheduledTaskTrigger -Daily -At "00:00" -RepetitionInterval (New-TimeSpan -Minutes 5) -RepetitionDuration (New-TimeSpan -Days 365)
$epsPrincipal = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Highest
$epsSettings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Hours 72)

Register-ScheduledTask -TaskName "EarningsTable_UpdateEPS" -Action $epsAction -Trigger $epsTrigger -Principal $epsPrincipal -Settings $epsSettings -Description "Update EPS and revenue data every 5 minutes"

# 5. Update Prices Task (every 5 minutes, starting at 00:00 daily)
Write-Host "Creating Update Prices Task..." -ForegroundColor Cyan
$pricesAction = New-ScheduledTaskAction -Execute $phpPath -Argument "cron\current_prices_mcaps_updates.php" -WorkingDirectory $basePath
$pricesTrigger = New-ScheduledTaskTrigger -Daily -At "00:00" -RepetitionInterval (New-TimeSpan -Minutes 5) -RepetitionDuration (New-TimeSpan -Days 365)
$pricesPrincipal = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Highest
$pricesSettings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Hours 72)

Register-ScheduledTask -TaskName "EarningsTable_UpdatePrices" -Action $pricesAction -Trigger $pricesTrigger -Principal $pricesPrincipal -Settings $pricesSettings -Description "Update prices and market cap data every 5 minutes"

# Step 3: Verify created tasks
Write-Host "`nStep 3: Verifying created tasks..." -ForegroundColor Yellow

$createdTasks = Get-ScheduledTask -TaskName "EarningsTable*"
Write-Host "`nCreated tasks:" -ForegroundColor Green
foreach ($task in $createdTasks) {
    Write-Host "  - $($task.TaskName): $($task.State)" -ForegroundColor White
}

Write-Host "`nTask Scheduler setup completed successfully!" -ForegroundColor Green
Write-Host "`nSchedule summary:" -ForegroundColor Cyan
Write-Host "  02:00 AM - Daily Cleanup" -ForegroundColor White
Write-Host "  02:15 AM - Fetch Tickers" -ForegroundColor White
Write-Host "  06:00 AM - Cache Shares" -ForegroundColor White
Write-Host "  00:00 AM + every 5 min - Update EPS" -ForegroundColor White
Write-Host "  00:00 AM + every 5 min - Update Prices" -ForegroundColor White

Write-Host "`nTo view tasks: taskschd.msc" -ForegroundColor Yellow
Write-Host "To test tasks: Right-click on task → Run" -ForegroundColor Yellow
