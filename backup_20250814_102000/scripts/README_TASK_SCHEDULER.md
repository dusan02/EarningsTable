# Windows Task Scheduler Setup

## 🚀 Automatické spustenie cron jobov

### **KROKY NASTAVENIA:**

#### **1. Spustenie PowerShell ako Administrator**

```powershell
# Kliknite pravým tlačidlom na PowerShell
# "Run as Administrator"
```

#### **2. Navigácia do adresára**

```powershell
cd "D:\Projects\EarningsTable\scripts"
```

#### **3. Spustenie setup scriptu**

```powershell
.\setup_task_scheduler.ps1
```

### **VYTVORENÉ TASKY:**

| Task Name                    | Frekvencia       | Účel                     |
| ---------------------------- | ---------------- | ------------------------ |
| `EarningsTable_DailyFetch`   | 1x denne o 02:15 | Denné earnings dáta      |
| `EarningsTable_PricesUpdate` | každých 5 min    | Aktualizácia cien        |
| `EarningsTable_EPSUpdate`    | každých 5 min    | Aktualizácia EPS/Revenue |

### **KONTROLA TASKOV:**

#### **GUI:**

```powershell
taskschd.msc
```

#### **PowerShell:**

```powershell
Get-ScheduledTask -TaskName "EarningsTable*"
```

### **LOG SÚBORY:**

Všetky výstupy sa logujú do:

- `logs\earnings_fetch.log`
- `logs\prices_update.log`
- `logs\eps_update.log`

### **ODSTRÁNENIE TASKOV:**

```powershell
Unregister-ScheduledTask -TaskName "EarningsTable_DailyFetch"
Unregister-ScheduledTask -TaskName "EarningsTable_PricesUpdate"
Unregister-ScheduledTask -TaskName "EarningsTable_EPSUpdate"
```

### **VÝHODY:**

✅ **Automatické spustenie** po reštarte PC
✅ **Presné časovanie** na sekundu
✅ **Logovanie** všetkých výstupov
✅ **Spoľahlivosť** - Windows systém
✅ **Žiadne manuálne spúšťanie**

### **PO REŠTARTE PC:**

**Všetky tasky sa spustia automaticky!** 🎯
