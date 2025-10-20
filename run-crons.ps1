Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

Write-Host 'Starting one-shot cron run (local test)...' -ForegroundColor Green

try {
    # Prejdi do priečinka s cron modulom
    Push-Location "$PSScriptRoot\modules\cron"

    # Over, že npm existuje
    $npm = (Get-Command npm -ErrorAction Stop).Source
    Write-Host ("Using npm: " + $npm) -ForegroundColor Cyan

    # Spusti one-shot režim (Finnhub → Polygon → Final report)
    & $npm run start:once

    Write-Host 'Cron run finished successfully.' -ForegroundColor Green
}
catch {
    Write-Host ("ERROR: " + $_.Exception.Message) -ForegroundColor Red
    exit 1
}
finally {
    Pop-Location
}