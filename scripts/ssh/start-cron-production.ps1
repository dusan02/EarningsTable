# PowerShell skript na spustenie cronu na produkcii

Write-Host "=== 1. Prechod do správneho adresára ===" -ForegroundColor Cyan
$projectPath = "D:\Projects\EarningsTable"
if (Test-Path $projectPath) {
    Set-Location $projectPath
    Write-Host "✓ V adresári: $(Get-Location)" -ForegroundColor Green
}
else {
    Write-Host "✗ Adresár $projectPath neexistuje!" -ForegroundColor Red
    exit 1
}

Write-Host "`n=== 2. Kontrola súborov ===" -ForegroundColor Cyan
$cronFile = "modules\cron\src\main.ts"
$ecosystemFile = "ecosystem.config.js"

if (Test-Path $cronFile) {
    Write-Host "✓ $cronFile existuje" -ForegroundColor Green
}
else {
    Write-Host "✗ $cronFile neexistuje!" -ForegroundColor Red
    exit 1
}

if (Test-Path $ecosystemFile) {
    Write-Host "✓ $ecosystemFile existuje" -ForegroundColor Green
}
else {
    Write-Host "✗ $ecosystemFile neexistuje!" -ForegroundColor Red
}

Write-Host "`n=== 3. Kontrola cron expression ===" -ForegroundColor Cyan
$cronCode = Get-Content $cronFile -Raw
if ($cronCode -match "UNIFIED_CRON\s*=\s*['`"]([^'`"]+)['`"]") {
    $cronExpr = $matches[1]
    Write-Host "Aktuálny cron expression: $cronExpr" -ForegroundColor Yellow
    if ($cronExpr -eq "*/5 * * * 1-5") {
        Write-Host "✓ Cron je nastavený na každých 5 min (24/7 okrem 03:00)" -ForegroundColor Green
    }
    else {
        Write-Host "⚠ Cron expression je: $cronExpr" -ForegroundColor Yellow
    }
}
else {
    Write-Host "✗ Nepodarilo sa nájsť UNIFIED_CRON" -ForegroundColor Red
}

Write-Host "`n=== 4. PM2 Status ===" -ForegroundColor Cyan
pm2 list

Write-Host "`n=== 5. Spustenie cron procesu ===" -ForegroundColor Cyan
Write-Host "Možnosti:" -ForegroundColor Yellow
Write-Host "A) Cez ecosystem.config.js (odporúčané)"
Write-Host "B) Priamo cez tsx"
Write-Host "C) Cez npm script"

$choice = Read-Host "`nVyber možnosť (A/B/C) alebo Enter pre A"

if ($choice -eq "B" -or $choice -eq "b") {
    Write-Host "`nSpúšťam cez tsx..." -ForegroundColor Cyan
    Set-Location "modules\cron"
    $tsxPath = Get-Command tsx -ErrorAction SilentlyContinue
    if ($tsxPath) {
        pm2 delete earnings-cron 2>$null
        pm2 start tsx --name earnings-cron -- "src/main.ts" "start"
        Write-Host "✓ Cron spustený" -ForegroundColor Green
    }
    else {
        Write-Host "✗ tsx nie je nainštalovaný. Použi: npm install -g tsx" -ForegroundColor Red
    }
}
elseif ($choice -eq "C" -or $choice -eq "c") {
    Write-Host "`nSpúšťam cez npm..." -ForegroundColor Cyan
    Set-Location "modules\cron"
    pm2 delete earnings-cron 2>$null
    pm2 start npm --name earnings-cron -- run start
    Write-Host "✓ Cron spustený" -ForegroundColor Green
}
else {
    Write-Host "`nSpúšťam cez ecosystem.config.js..." -ForegroundColor Cyan
    if (Test-Path $ecosystemFile) {
        pm2 delete earnings-cron 2>$null
        pm2 start $ecosystemFile --only earnings-cron
        Write-Host "✓ Cron spustený" -ForegroundColor Green
    }
    else {
        Write-Host "✗ ecosystem.config.js neexistuje, používam tsx..." -ForegroundColor Yellow
        Set-Location "modules\cron"
        pm2 delete earnings-cron 2>$null
        pm2 start tsx --name earnings-cron -- "src/main.ts" "start"
    }
}

Write-Host "`n=== 6. Kontrola stavu ===" -ForegroundColor Cyan
Start-Sleep -Seconds 2
pm2 list
pm2 logs earnings-cron --lines 20 --nostream

Write-Host "`n=== 7. Uloženie PM2 konfigurácie ===" -ForegroundColor Cyan
pm2 save
Write-Host "✓ Konfigurácia uložená" -ForegroundColor Green

Write-Host "`n=== Hotovo! ===" -ForegroundColor Green
Write-Host "Pre realtime logy: pm2 logs earnings-cron --lines 0" -ForegroundColor Yellow
Write-Host "Pre status: pm2 status" -ForegroundColor Yellow

