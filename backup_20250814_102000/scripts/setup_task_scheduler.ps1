# PowerShell script to create Task Scheduler tasks for Earnings Table cron jobs
# Run as Administrator

Write-Host "Setting up Task Scheduler for Earnings Table cron jobs..." -ForegroundColor Green

# Get current user
$currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name

# Base path
$basePath = "D:\Projects\EarningsTable"
$scriptsPath = "$basePath\scripts"

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

Write-Host "Task Scheduler setup completed!" -ForegroundColor Green
Write-Host "Tasks created:" -ForegroundColor Cyan
Write-Host "  - EarningsTable_DailyFetch (02:15 CET daily)" -ForegroundColor White
Write-Host "  - EarningsTable_PricesUpdate (every 5 minutes)" -ForegroundColor White
Write-Host "  - EarningsTable_EPSUpdate (every 5 minutes)" -ForegroundColor White
Write-Host ""
Write-Host "To view tasks: taskschd.msc" -ForegroundColor Yellow
Write-Host "To remove tasks: Unregister-ScheduledTask -TaskName 'TaskName'" -ForegroundColor Yellow 