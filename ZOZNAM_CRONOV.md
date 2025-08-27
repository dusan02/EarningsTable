# 📋 ZOZNAM VŠETKÝCH CRONOV V PROJEKTE

## 🚀 **AKTÍVNE CRONY**

### **1. 🎯 ENHANCED MASTER CRON**

**Súbor:** `cron/enhanced_master_cron.php`
**Frekvencia:** Raz denne (02:00 NY time)
**Úloha:** Orchestrácia všetkých cronov
**Popis:** Hlavný orchestrátor, ktorý spúšťa všetky ostatné crony v správnom poradí

- Clear old data
- Intelligent earnings fetch
- Polygon market data update
- Yahoo Finance removed (používa sa len Finnhub)

### **2. 🧹 CLEAR OLD DATA**

**Súbor:** `cron/clear_old_data.php`
**Frekvencia:** Raz denne (02:00 NY time)
**Úloha:** Čistenie starých dát
**Popis:**

- Maže staré záznamy z `TodayEarningsMovements`
- Prune staré záznamy z `EarningsTickersToday`
- Spúšťa sa automaticky cez master cron

### **3. 📊 INTELLIGENT EARNINGS FETCH**

**Súbor:** `cron/intelligent_earnings_fetch.php`
**Frekvencia:** Raz denne (cez master cron)
**Úloha:** Získanie základných earnings dát
**Popis:**

- Získava tickery z Finnhub (primary source)
- Ukladá EPS/Revenue estimates
- Získava market data z Polygon (batch API)
- Yahoo Finance removed pre lepšiu stabilitu

### **4. ⚡ OPTIMIZED 5-MIN UPDATE**

**Súbor:** `cron/optimized_5min_update.php`
**Frekvencia:** Každých 5 minút
**Úloha:** Aktualizácia dynamických dát
**Popis:**

- Aktualizuje actual EPS/Revenue hodnoty z Finnhub
- Aktualizuje ceny z Polygon (batch processing)
- Minimalizuje API volania
- Optimalizované databázové operácie

## 🔄 **REFAKTOROVANÉ CRONY (NOVÁ ARCHITEKTÚRA)**

### **5. 🏗️ DAILY DATA SETUP - STATIC**

**Súbor:** `cron/daily_data_setup_static.php`
**Frekvencia:** Raz denne
**Úloha:** Nastavenie statických dát
**Popis:**

- **REFAKTOROVANÝ** - používa triedu `DailyDataSetup`
- Získava tickery z Finnhub (earnings calendar)
- Získava statické dáta z Polygon (market cap, company info)
- Paralelné spracovanie Polygon details
- Batch INSERT operácie
- Retry logic a error handling

### **6. ⚡ REGULAR DATA UPDATES - DYNAMIC**

**Súbor:** `cron/regular_data_updates_dynamic.php`
**Frekvencia:** Každých 5 minút
**Úloha:** Aktualizácia dynamických dát
**Popis:**

- **REFAKTOROVANÝ** - používa triedu `RegularDataUpdatesDynamic`
- Finnhub: EPS/Revenue Actual hodnoty
- Polygon: Current Price, Price Change %, Market Cap Diff
- Batch SELECT/UPDATE operácie
- Retry logic a data validation
- Configuration management a monitoring

## 📈 **ARCHITEKTÚRA CRONOV**

### **DENNÉ CRONY (STATICKÉ DÁTA):**

```
enhanced_master_cron.php
├── clear_old_data.php
├── intelligent_earnings_fetch.php (LEGACY)
└── daily_data_setup_static.php (NOVÝ)
```

### **5-MINÚTOVÉ CRONY (DYNAMICKÉ DÁTA):**

```
optimized_5min_update.php (LEGACY)
└── regular_data_updates_dynamic.php (NOVÝ)
```

## 🔧 **TECHNICKÉ DETAILY**

### **LOCK MECHANISM:**

- Všetky crony používajú `Lock` triedu
- Prevencia concurrent execution
- Automatické uvoľnenie lock-u pri ukončení

### **ERROR HANDLING:**

- Všetky crony majú try-catch bloky
- Logovanie chýb do `storage/logs/`
- Graceful error handling

### **PERFORMANCE:**

- Batch API volania kde je možné
- Optimalizované databázové operácie
- Rate limiting pre API volania

## 📊 **POROVNANIE STARÁ vs NOVÁ ARCHITEKTÚRA**

| **Funkcia**            | **Stará**                        | **Nová**                           |
| ---------------------- | -------------------------------- | ---------------------------------- |
| **Statické dáta**      | `intelligent_earnings_fetch.php` | `daily_data_setup_static.php`      |
| **Dynamické dáta**     | `optimized_5min_update.php`      | `regular_data_updates_dynamic.php` |
| **Kódová organizácia** | Monolitické súbory               | Modulárne triedy                   |
| **Performance**        | Základné optimalizácie           | Pokročilé optimalizácie            |
| **Error handling**     | Základné                         | Retry logic + validation           |
| **Monitoring**         | Základné logovanie               | Metrics + performance tracking     |

## 🎯 **ODPORÚČANÉ POUŽITIE**

### **PRODUKČNÉ PROSTREDIE:**

- **Denné:** `daily_data_setup_static.php` (nový)
- **5-minútové:** `regular_data_updates_dynamic.php` (nový)
- **Orchestrácia:** `enhanced_master_cron.php` (aktualizovaný)

### **LEGACY SUPPORT:**

- `intelligent_earnings_fetch.php` - ešte funkčný, ale odporúčané nahradiť
- `optimized_5min_update.php` - ešte funkčný, ale odporúčané nahradiť

## ✅ **ZÁVER**

**Aktuálne máme 6 cronov:**

1. **enhanced_master_cron.php** - Orchestrátor
2. **clear_old_data.php** - Čistenie dát
3. **intelligent_earnings_fetch.php** - Legacy statické dáta
4. **optimized_5min_update.php** - Legacy dynamické dáta
5. **daily_data_setup_static.php** - Nový refaktorovaný statický cron
6. **regular_data_updates_dynamic.php** - Nový refaktorovaný dynamický cron

**Odporúčané:** Používať novú architektúru s refaktorovanými cronmi pre lepšiu performance a maintainability.
