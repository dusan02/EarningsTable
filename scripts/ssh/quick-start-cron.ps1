# Rýchle spustenie cronu
Write-Host "=== Spúšťam earnings-cron ===" -ForegroundColor Cyan

# Vymaž starý proces
pm2 delete earnings-cron 2>$null

# Spusti nový
pm2 start ecosystem.config.js --only earnings-cron

# Počkaj 2 sekundy
Start-Sleep -Seconds 2

# Zobraz status
pm2 list

# Zobraz logy
Write-Host "`n=== Posledné logy ===" -ForegroundColor Cyan
pm2 logs earnings-cron --lines 20 --nostream

# Ulož
pm2 save
Write-Host "`n✓ Hotovo! Cron beží." -ForegroundColor Green
Write-Host 'Pre realtime logy: pm2 logs earnings-cron --lines 0' -ForegroundColor Yellow

