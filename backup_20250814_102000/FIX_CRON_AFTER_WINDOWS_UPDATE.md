# Oprava Cron Úloh po Windows Aktualizácii

## Problém
Po aktualizácii Windows sa cron úlohy prestali spúšťať kvôli problémom s cestou k PHP.

## Diagnóza
1. **PHP funguje správne** - nájdené na `D:\xampp\php\php.exe`
2. **Cron skripty fungujú** - testované manuálne
3. **Task Scheduler úlohy majú nesprávnu konfiguráciu** - chýba Working Directory

## Riešenie

### Krok 1: Spustite PowerShell ako Administrátor
1. Kliknite pravým tlačidlom na PowerShell
2. Vyberte "Spustiť ako správca"

### Krok 2: Prejdite do priečinka projektu
```powershell
cd "D:\Projects\EarningsTable"
```

### Krok 3: Spustite opravný skript
```powershell
powershell -ExecutionPolicy Bypass -File "scripts\fix_task_scheduler.ps1"
```

### Alternatívne riešenie - Manuálne nastavenie

Ak automatický skript nefunguje, nastavte úlohy manuálne:

#### 1. Otvorte Task Scheduler
- Stlačte `Win + R`
- Napíšte `taskschd.msc`
- Stlačte Enter

#### 2. Vymažte existujúce úlohy
- Nájdite úlohy začínajúce "EarningsTable_"
- Kliknite pravým tlačidlom → "Odstrániť"

#### 3. Vytvorte nové úlohy

**EarningsTable_FetchTickers:**
- **Action:** `D:\xampp\php\php.exe`
- **Arguments:** `cron\fetch_earnings_tickers.php`
- **Working Directory:** `D:\Projects\EarningsTable`
- **Trigger:** Daily at 02:15

**EarningsTable_UpdatePrices:**
- **Action:** `D:\xampp\php\php.exe`
- **Arguments:** `cron\current_prices_mcaps_updates.php`
- **Working Directory:** `D:\Projects\EarningsTable`
- **Trigger:** Every 5 minutes

**EarningsTable_UpdateEPS:**
- **Action:** `D:\xampp\php\php.exe`
- **Arguments:** `cron\update_earnings_eps_revenues.php`
- **Working Directory:** `D:\Projects\EarningsTable`
- **Trigger:** Every 5 minutes

**EarningsTable_CacheShares:**
- **Action:** `D:\xampp\php\php.exe`
- **Arguments:** `cron\cache_shares_outstanding.php`
- **Working Directory:** `D:\Projects\EarningsTable`
- **Trigger:** Daily at 06:00

### Krok 4: Testovanie
Spustite manuálne test:
```powershell
.\test_php_manual.bat
```

### Krok 5: Kontrola logov
Skontrolujte logy v priečinku `logs\`:
- `earnings_fetch.log`
- `prices_update.log`
- `eps_update.log`

## Dôležité poznámky

1. **Working Directory je kľúčové** - bez neho sa skripty nespustia správne
2. **PHP cesta** - používa sa `D:\xampp\php\php.exe`
3. **Práva** - úlohy musia mať práva na spustenie
4. **XAMPP** - uistite sa, že Apache a MySQL bežia

## Rýchle riešenie
Ak potrebujete rýchlo opraviť, spustite:
```powershell
# Ako administrátor
cd "D:\Projects\EarningsTable"
powershell -ExecutionPolicy Bypass -File "scripts\fix_task_scheduler.ps1"
```
