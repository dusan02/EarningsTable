# 📊 Analýza logov z produkcie - SSH výstup

## 🔍 Zistenia z logov

### 1. PM2 Status
```
earnings-cron:   198 reštartov  ⚠️ (vysoké)
earnings-table:  3347 reštartov 🚨 (KRITICKÉ!)
```

**Problém**: Oba procesy majú veľa reštartov, obzvlášť `earnings-table` má 3347 reštartov!

### 2. Logovanie - Čo sa loguje

#### **Cron Jobs (earnings-cron)** - stdout:
- ✅ Syntetické testy (PASS)
- ✅ Pipeline behy (každých 5 min)
- ✅ Cron status správy
- ✅ Timezone consistency
- ✅ Database connectivity
- ✅ Data freshness
- ✅ Logo availability

#### **Cron Jobs (earnings-cron)** - stderr:
- 🚨 **MNOŽSTVO** "SYNTHETIC TESTS FAILED" správ
- ↩️ Veľa "SIGINT received" správ
- ⚠️ Veľa "exit: 0" správ
- → Failed to get snapshot (404 chyby pre niektoré symboly)

#### **Web Server (earnings-table)** - stdout:
- [ALL REQUESTS] - každá HTTP požiadavka
- 📊 Fetching FinalReport data
- [DB] Connection successful
- ✅ Found 31 records in FinalReport
- GET /logos/* - požiadavky na logá

#### **Web Server (earnings-table)** - stderr:
- 🔍 DEBUG: Got data from DB, count: 31
- 📊 Total records: 31, with marketCap: 27
- 📊 First 5 symbols after sorting (debug info)

### 3. Pipeline behy

Pipeline beží **každých 5 minút**:
```
🚀 Starting optimized pipeline [unified-slot]
✅ Optimized pipeline completed in 2038ms / 6181ms / 2364ms
```

### 4. Problémy identifikované

#### 🚨 **KRITICKÉ**:
1. **3347 reštartov na earnings-table** - proces sa neustále reštartuje
2. **MNOŽSTVO "SYNTHETIC TESTS FAILED"** v error logoch (ale v stdout sú PASS?)
3. **Veľa SIGINT** správ - niekto/niečo zastavuje procesy

#### ⚠️ **VAROVANIA**:
1. **198 reštartov na earnings-cron** - stále vysoké
2. **404 chyby** pre niektoré symboly (ASCBF, VGES)
3. **Debug správy v stderr** namiesto stdout (earnings-table)

### 5. Čo funguje dobre

✅ Pipeline beží každých 5 minút
✅ Syntetické testy v stdout ukazujú PASS
✅ Database connection funguje
✅ Data sa načítavajú (31 records)
✅ Web server odpovedá na požiadavky
✅ Logá sa ukladajú do `~/.pm2/logs/`

## 📝 Odporúčania

### 1. Vyriešiť reštarty
```bash
# Zistiť prečo sa earnings-table reštartuje
pm2 logs earnings-table --lines 1000 --nostream | grep -i "restart\|error\|crash\|exit" | tail -50

# Skontrolovať memory limit
pm2 describe earnings-table
```

### 2. Vyriešiť "SYNTHETIC TESTS FAILED"
```bash
# Zistiť kedy a prečo zlyhávajú
pm2 logs earnings-cron --lines 2000 --nostream | grep -B 5 -A 5 "SYNTHETIC TESTS FAILED" | head -100
```

### 3. Presunúť debug logy
Debug správy z `earnings-table` by mali ísť do stdout, nie stderr.

### 4. Monitorovať reštarty
```bash
# Sledovať reštarty v reálnom čase
pm2 monit
```

## 🔧 Príkazy na ďalšiu diagnostiku

```bash
# 1. Zistiť prečo sa earnings-table reštartuje
pm2 logs earnings-table --lines 500 --nostream --err | grep -iE "error|crash|out of memory|killed|signal" | tail -30

# 2. Zistiť kedy sa reštartuje
pm2 logs earnings-table --lines 1000 --nostream | grep -iE "restart|exit|SIGINT|SIGTERM" | tail -50

# 3. Skontrolovať memory usage
pm2 describe earnings-table | grep -i memory

# 4. Zistiť detailnejšie info o SYNTHETIC TESTS FAILED
pm2 logs earnings-cron --lines 5000 --nostream --err | grep -B 10 "SYNTHETIC TESTS FAILED" | head -100

# 5. Sledovať v reálnom čase
pm2 logs earnings-table --err
```

## 📊 Súhrn správania logov

### Kde sa loguje:
- **PM2 logy**: `~/.pm2/logs/`
  - `earnings-cron-out.log` - stdout z cron
  - `earnings-cron-error.log` - stderr z cron
  - `earnings-table-out.log` - stdout z web servera
  - `earnings-table-error.log` - stderr z web servera

### Čo sa loguje:
- **Cron**: Pipeline behy, syntetické testy, database operácie
- **Web Server**: HTTP požiadavky, database queries, debug info

### Problémy:
- Veľa reštartov (obzvlášť earnings-table)
- SYNTHETIC TESTS FAILED v error logoch (ale PASS v stdout?)
- Debug info v stderr namiesto stdout

