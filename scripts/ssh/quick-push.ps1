# 🔄 Rýchly push na GitHub z lokálneho PC
# Použitie: .\quick-push.ps1 "Popis zmien"

param(
    [Parameter(Mandatory=$false)]
    [string]$Message = "Update: Local changes $(Get-Date -Format 'yyyy-MM-dd HH:mm')"
)

Write-Host "🔄 Rýchly push na GitHub..." -ForegroundColor Yellow

# 1. Prejsť do projektu
$projectPath = "D:\Projects\EarningsTable"
if (-not (Test-Path $projectPath)) {
    Write-Host "❌ Chyba: Priečinok $projectPath neexistuje" -ForegroundColor Red
    exit 1
}

Set-Location $projectPath

# 2. Skontrolovať Git status
Write-Host "📋 Kontrola Git statusu..." -ForegroundColor Yellow
git status

# 3. Pridať všetky zmeny
Write-Host "📦 Pridávanie zmien..." -ForegroundColor Yellow
git add .

# 4. Commit
Write-Host "💾 Commit zmien..." -ForegroundColor Yellow
if (git commit -m $Message) {
    Write-Host "✅ Zmeny commitnuté" -ForegroundColor Green
} else {
    Write-Host "⚠️  Žiadne zmeny na commitnutie" -ForegroundColor Yellow
}

# 5. Push na GitHub
Write-Host "📤 Push na GitHub..." -ForegroundColor Yellow
if (git push origin main) {
    Write-Host "✅ Zmeny pushnuté na GitHub" -ForegroundColor Green
} else {
    Write-Host "❌ Chyba pri pushnutí" -ForegroundColor Red
    exit 1
}

# 6. Zobraziť posledný commit
Write-Host "`n📝 Posledný commit:" -ForegroundColor Yellow
git log --oneline -1

Write-Host "`n✅ Hotovo!" -ForegroundColor Green

