# PowerShell skript na kontrolu stavu cronu na produkcii

Write-Host "=== 1. Všetky PM2 procesy ===" -ForegroundColor Cyan
pm2 list

Write-Host "`n=== 2. PM2 info ===" -ForegroundColor Cyan
pm2 info

Write-Host "`n=== 3. Kontrola ecosystem.config.js ===" -ForegroundColor Cyan
if (Test-Path "ecosystem.config.js") {
    Get-Content ecosystem.config.js | Select-String -Pattern "earnings-cron" -Context 0, 10
}
else {
    Write-Host "ecosystem.config.js not found" -ForegroundColor Red
}

Write-Host "`n=== 4. Bežiace procesy s 'cron' alebo 'earnings' ===" -ForegroundColor Cyan
Get-Process | Where-Object { $_.ProcessName -like "*cron*" -or $_.ProcessName -like "*earnings*" -or $_.CommandLine -like "*main.ts*" } | Format-Table -AutoSize

Write-Host "`n=== 5. Kontrola, či existuje cron modul ===" -ForegroundColor Cyan
if (Test-Path "modules\cron\src\main.ts") {
    Write-Host "modules\cron\src\main.ts exists" -ForegroundColor Green
    Write-Host "`n=== 6. Kontrola cron expression v kóde ===" -ForegroundColor Cyan
    Get-Content "modules\cron\src\main.ts" | Select-String -Pattern "UNIFIED_CRON" -Context 0, 5
}
else {
    Write-Host "modules\cron\src\main.ts not found" -ForegroundColor Red
}

Write-Host "`n=== 7. Časové pásmo ===" -ForegroundColor Cyan
Write-Host "Server time: $(Get-Date)"
$nyTime = [TimeZoneInfo]::ConvertTimeBySystemTimeZoneId([DateTime]::Now, "Eastern Standard Time")
Write-Host "NY time: $nyTime"

Write-Host "`n=== 8. Ako spustiť cron proces ===" -ForegroundColor Yellow
Write-Host "pm2 start ecosystem.config.js --only earnings-cron"
Write-Host "alebo:"
Write-Host "cd modules\cron"
Write-Host "pm2 start npm --name earnings-cron -- run start"

