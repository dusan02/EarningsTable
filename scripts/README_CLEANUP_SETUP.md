# Cleanup and Setup Task Scheduler

## 🚀 Automatické vyčistenie a nastavenie všetkých cron jobov

### **PROBLÉM:**

Existujúce tasky majú nesprávnu konfiguráciu:

- UpdateEPS a UpdatePrices majú fixný dátum začiatku namiesto denného opakovania
- Chýbajúce tasky (DailyCleanup, DailySequence)
- Nesprávne časové nastavenia

### **RIEŠENIE:**

Vytvoril som 2 scripty na kompletné vyčistenie a nastavenie:

## 📋 **KROKY NASTAVENIA:**

### **Možnosť 1: PowerShell Script (Odporúčané)**

#### **1. Spustenie PowerShell ako Administrator**

```powershell
# Kliknite pravým tlačidlom na PowerShell
# "Run as Administrator"
```

#### **2. Navigácia do adresára**

```powershell
cd "D:\Projects\EarningsTable\scripts"
```

#### **3. Spustenie cleanup scriptu**

```powershell
.\cleanup_and_setup_tasks.ps1
```

### **Možnosť 2: Batch Script**

#### **1. Spustenie Command Prompt ako Administrator**

```cmd
# Kliknite pravým tlačidlom na Command Prompt
# "Run as Administrator"
```

#### **2. Navigácia do adresára**

```cmd
cd "D:\Projects\EarningsTable\scripts"
```

#### **3. Spustenie cleanup scriptu**

```cmd
cleanup_and_setup_tasks.bat
```

## ✅ **VYTVORENÉ TASKY:**

| Task Name                    | Frekvencia             | Účel                         |
| ---------------------------- | ---------------------- | ---------------------------- |
| `EarningsTable_DailyCleanup` | 1x denne o 02:00       | Čistenie starých dát         |
| `EarningsTable_FetchTickers` | 1x denne o 02:15       | Načítanie earnings tickerov  |
| `EarningsTable_CacheShares`  | 1x denne o 06:00       | Cache shares outstanding     |
| `EarningsTable_UpdateEPS`    | každých 5 min od 00:00 | Aktualizácia EPS/Revenue     |
| `EarningsTable_UpdatePrices` | každých 5 min od 00:00 | Aktualizácia cien/market cap |

## 🔍 **KONTROLA TASKOV:**

### **PowerShell:**

```powershell
Get-ScheduledTask -TaskName "EarningsTable*"
```

### **Command Line:**

```cmd
schtasks /query /tn "EarningsTable*" /fo table
```

### **GUI:**

```cmd
taskschd.msc
```

## 🧪 **TESTOVANIE TASKOV:**

### **Manuálne spustenie:**

1. Otvorte `taskschd.msc`
2. Nájdite EarningsTable task
3. Kliknite pravým → "Run"

### **Kontrola logov:**

- Všetky výstupy sa logujú do `storage/` adresára
- Pozrite si `storage/daily_run.log` pre denné tasky
- Pozrite si `storage/5min_updates.log` pre 5-minútové aktualizácie

## 🗑️ **ODSTRÁNENIE TASKOV (ak potrebné):**

### **PowerShell:**

```powershell
Get-ScheduledTask -TaskName "EarningsTable*" | Unregister-ScheduledTask -Confirm:$false
```

### **Command Line:**

```cmd
schtasks /delete /tn "EarningsTable_DailyCleanup" /f
schtasks /delete /tn "EarningsTable_FetchTickers" /f
schtasks /delete /tn "EarningsTable_CacheShares" /f
schtasks /delete /tn "EarningsTable_UpdateEPS" /f
schtasks /delete /tn "EarningsTable_UpdatePrices" /f
```

## ⚠️ **DÔLEŽITÉ POZNÁMKY:**

1. **Administrátorské práva** - Scripty musia byť spustené ako Administrator
2. **PHP cesta** - Overte, či je cesta `D:\xampp\php\php.exe` správna
3. **Projektová cesta** - Overte, či je cesta `D:\Projects\EarningsTable` správna
4. **Časové zóny** - Všetky časy sú v lokálnej časovej zóne

## 🎯 **OČAKÁVANÝ VÝSLEDOK:**

Po úspešnom spustení by ste mali vidieť:

- ✅ Všetky staré tasky vymazané
- ✅ 5 nových taskov vytvorených
- ✅ Správne časové nastavenia
- ✅ Všetky tasky v stave "Ready"

## 📞 **PODPORA:**

Ak sa vyskytnú problémy:

1. Skontrolujte, či máte administrátorské práva
2. Overte správnosť ciest k PHP a projektu
3. Pozrite si chybové hlásenia v PowerShell/Command Prompt
4. Skontrolujte Windows Event Log pre detailné chyby
