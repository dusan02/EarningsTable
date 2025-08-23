# 📊 CRON JOBS SUMMARY - EarningsTable

## 🔄 **Aktívne Cron Joby (v run_crons_loop.bat)**

### **1. clear_old_movements.php** ⭐ NOVÝ
- **Účel:** Čistí staré záznamy z `TodayEarningsMovements` tabuľky
- **Frekvencia:** Každých 5 minút (v loop)
- **Čo robí:** 
  - Maže všetky staré záznamy z `TodayEarningsMovements`
  - Zabráni hromadeniu dát z predchádzajúcich dní
  - Používa lock mechanism

### **2. fetch_earnings_tickers.php**
- **Účel:** Získava dnešné earnings tickery z Finnhub API
- **Frekvencia:** Každých 5 minút (v loop)
- **Čo robí:**
  - Získa zoznam tickerov, ktoré dnes reportujú earnings
  - Uloží ich do `EarningsTickersToday` tabuľky
  - Aktualizuje company names

### **3. update_earnings_eps_revenues.php**
- **Účel:** Aktualizuje EPS a revenue dáta
- **Frekvencia:** Každých 5 minút (v loop)
- **Čo robí:**
  - Získa aktuálne EPS a revenue dáta
  - Aktualizuje `earnings_eps_revenues` tabuľku

### **4. current_prices_mcaps_updates.php**
- **Účel:** Aktualizuje ceny a market cap dáta
- **Frekvencia:** Každých 5 minút (v loop)
- **Čo robí:**
  - Získa aktuálne ceny z Polygon API
  - Získa market cap dáta z Polygon V3
  - Uloží do `TodayEarningsMovements` tabuľky
  - Najväčší a najkomplexnejší script (296 riadkov)

### **5. update_company_names.php** ⚡ OPTIMIZOVANÉ
- **Účel:** Aktualizuje názvy spoločností
- **Frekvencia:** Každých 4 hodiny (namiesto každých 5 minút)
- **Dôvod:** Statické dáta, menia sa zriedka
- **Čo robí:**
  - Získa aktuálne názvy spoločností z Finnhub
  - Aktualizuje `company_names` tabuľku

### **6. cache_shares_outstanding.php** ⚡ OPTIMIZOVANÉ
- **Účel:** Cachuje shares outstanding dáta
- **Frekvencia:** Každých 4 hodiny (namiesto každých 5 minút)
- **Dôvod:** Statické dáta, menia sa zriedka
- **Čo robí:**
  - Získa shares outstanding dáta z Polygon V3
  - Uloží do cache pre rýchlejší prístup

---

## ⏰ **Windows Task Scheduler Úlohy**

### **EarningsTable_FetchTickers**
- **Spustenie:** Denně o 02:15
- **Script:** `fetch_earnings_tickers.php`
- **Účel:** Denné získanie earnings tickerov

### **EarningsTable_UpdateEPS**
- **Spustenie:** Denně o 02:30
- **Script:** `update_earnings_eps_revenues.php`
- **Účel:** Denná aktualizácia EPS dát

### **EarningsTable_UpdatePrices**
- **Spustenie:** Denně o 02:45
- **Script:** `current_prices_mcaps_updates.php`
- **Účel:** Denná aktualizácia cien a market cap

### **EarningsTable_CacheShares**
- **Spustenie:** Denně o 03:00
- **Script:** `cache_shares_outstanding.php`
- **Účel:** Denné cachovanie shares outstanding

---

## 🔧 **Dodatočné Cron Scripty (nie v loop)**

### **fetch_market_data_only_prod.php**
- **Účel:** Len market data (produkčná verzia)
- **Použitie:** Samostatne

### **fetch_estimates_only_prod.php**
- **Účel:** Len estimates (produkčná verzia)
- **Použitie:** Samostatne

### **fetch_earnings_tickers_finnhub_prod.php**
- **Účel:** Finnhub earnings tickers (produkčná verzia)
- **Použitie:** Samostatne

---

## 📋 **Poradie Spustenia**

### **V Loop (každých 5 minút):**
1. `clear_old_movements.php` ← **NOVÝ** (čistí staré dáta)
2. `fetch_earnings_tickers.php` (získa dnešné tickery)
3. `update_earnings_eps_revenues.php` (EPS a revenue)
4. `current_prices_mcaps_updates.php` (ceny a market cap)

### **V Loop (každých 4 hodiny):** ⚡ OPTIMIZOVANÉ
5. `update_company_names.php` (názvy spoločností)
6. `cache_shares_outstanding.php` (shares outstanding)

### **Windows Task Scheduler (denne):**
1. 02:15 - `fetch_earnings_tickers.php`
2. 02:30 - `update_earnings_eps_revenues.php`
3. 02:45 - `current_prices_mcaps_updates.php`
4. 03:00 - `cache_shares_outstanding.php`

---

## ⚡ **OPTIMIZÁCIA - Nové Verzie**

### **run_crons_loop.bat** (aktuálna)
- Statické dáta: každých 4 hodiny
- Dynamické dáta: každých 5 minút

### **run_crons_loop_daily_static.bat** (alternatívna)
- Statické dáta: raz denne
- Dynamické dáta: každých 5 minút

---

## ⚠️ **Problémy a Riešenia**

### **Predtým:**
- ❌ Tabuľka `TodayEarningsMovements` sa nečistila
- ❌ Hromadili sa staré záznamy (719 namiesto 133)
- ❌ Chýbal script na čistenie
- ❌ Statické dáta sa spúšťali každých 5 minút (zbytočné)

### **Teraz:**
- ✅ Pridaný `clear_old_movements.php`
- ✅ Tabuľka sa čistí pred každým cyklom
- ✅ Správny počet záznamov (133)
- ✅ Automatické čistenie každých 5 minút
- ✅ Statické dáta sa spúšťajú každých 4 hodiny (optimalizované)

---

## 🎯 **Celkový Počet Cron Jobov: 9**

**Aktívne v loop:** 6 (4 každých 5 minút + 2 každých 4 hodiny)
**Windows Task Scheduler:** 4
**Dodatočné:** 3

## 📈 **Výkonnostné Zlepšenia**

### **Pred optimalizáciou:**
- 6 scriptov každých 5 minút = 1728 spustení denne
- API limity sa rýchlo vyčerpávali

### **Po optimalizácii:**
- 4 scripty každých 5 minút = 1152 spustení denne
- 2 scripty každých 4 hodiny = 12 spustení denne
- **Celkovo:** 1164 spustení denne (32% úspora)
