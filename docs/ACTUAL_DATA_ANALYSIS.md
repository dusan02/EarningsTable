# 📊 Actual Data Analysis - EPS & Revenue

## Overview

Analýza `eps_actual` a `revenue_actual` dát v EarningsTable projekte.

## 🔍 Čo som zistil

### ✅ **Dáta sa získavajú z Finnhub API**

**Cron súbory, ktoré získavajú actual dáta:**

1. **`cron/fetch_finnhub_earnings_today_tickers.php`**

   - Získava earnings calendar z Finnhub API
   - Extrahuje `epsActual` a `revenueActual` z API response
   - Ukladá dáta do `TodayEarningsMovements` tabuľky

2. **`cron/update_finnhub_data_5min.php`**

   - Spúšťa sa každých 5 minút
   - Aktualizuje `eps_actual` a `revenue_actual` hodnoty
   - Kontroluje nové actual hodnoty z Finnhub API

3. **`cron/run_5min_updates.php`**
   - Hlavný 5-minútový update script
   - Spúšťa Polygon aj Finnhub updates

### 📋 **Finnhub API Response**

Z Finnhub API sa získavajú tieto polia:

```json
{
  "symbol": "AAPL",
  "epsEstimate": 2.1,
  "epsActual": 2.18, // ← Toto sa ukladá do eps_actual
  "revenueEstimate": 85000000000,
  "revenueActual": 86000000000, // ← Toto sa ukladá do revenue_actual
  "quarter": 1,
  "year": 2024
}
```

### 🗄️ **Databázová štruktúra**

**Tabuľka `TodayEarningsMovements`:**

```sql
- eps_actual DECIMAL(10,2) NULL
- revenue_actual BIGINT NULL
- updated_at TIMESTAMP
```

**Tabuľka `EarningsTickersToday`:**

```sql
- eps_estimate DECIMAL(10,2) NULL
- revenue_estimate BIGINT NULL
```

## 🚨 **Možné príčiny chýbajúcich actual dát**

### 1. **Timing problém**

- **Actual hodnoty sa objavujú až po earnings report**
- Finnhub API môže vrátiť `null` pre actual hodnoty, ak earnings ešte neprebehli
- Potrebuje sa počkať na skutočné earnings reports

### 2. **Cron job sa nespustil**

- 5-minútové updates sa nespúšťajú automaticky
- Potrebuje sa nastaviť cron job alebo spustiť manuálne

### 3. **Finnhub API limit**

- API má rate limiting (60 calls per minute)
- Môže sa dosiahnuť limit a dáta sa neaktualizujú

### 4. **Dáta sa neuložili správne**

- Chyba v SQL INSERT/UPDATE
- Chyba v databázovom pripojení

## 🧪 **Test súbor pre kontrolu**

Vytvoril som test súbor `Tests/check_actual_data.php`, ktorý:

1. **Kontroluje databázu:**

   - Počet záznamov s `eps_actual`
   - Počet záznamov s `revenue_actual`
   - Ukážkové dáta s actual hodnotami

2. **Testuje Finnhub API:**

   - Získava aktuálne earnings dáta
   - Kontroluje, či API vracia actual hodnoty
   - Zobrazuje ukážkové dáta z API

3. **Poskytuje odporúčania:**
   - Čo robiť, ak nie sú actual dáta
   - Ako spustiť cron joby
   - Ako riešiť problémy

## 🚀 **Ako spustiť kontrolu**

### 1. **Spustite test súbor:**

```bash
php Tests/check_actual_data.php
```

### 2. **Spustite Finnhub cron job:**

```bash
php cron/fetch_finnhub_earnings_today_tickers.php
```

### 3. **Spustite 5-minútový update:**

```bash
php cron/update_finnhub_data_5min.php
```

### 4. **Spustite kompletný 5-minútový update:**

```bash
php cron/run_5min_updates.php
```

## 📊 **Očakávané výsledky**

### **Ak earnings ešte neprebehli:**

```
EPS Actual: 0 záznamov
Revenue Actual: 0 záznamov
Finnhub API: epsActual = null, revenueActual = null
```

### **Ak earnings už prebehli:**

```
EPS Actual: X záznamov
Revenue Actual: Y záznamov
Finnhub API: epsActual = 2.18, revenueActual = 86000000000
```

## 🔧 **Riešenia**

### **1. Počkajte na earnings reports**

- Actual hodnoty sa objavujú až po skutočnom earnings report
- Môže to trvať hodiny až dni

### **2. Spustite cron joby manuálne**

```bash
# Spustite hlavný earnings fetch
php cron/fetch_finnhub_earnings_today_tickers.php

# Spustite 5-minútové updates
php cron/run_5min_updates.php
```

### **3. Nastavte automatické cron joby**

```bash
# Každých 5 minút
*/5 * * * * php /path/to/cron/run_5min_updates.php

# Denné o 8:30 AM
30 8 * * * php /path/to/cron/fetch_finnhub_earnings_today_tickers.php
```

### **4. Skontrolujte Finnhub API kľúč**

- Overte, či je API kľúč platný
- Skontrolujte rate limiting

## 📈 **Monitoring**

### **Kontrola logov:**

```bash
tail -f logs/app.log
```

### **Kontrola databázy:**

```sql
SELECT COUNT(*) FROM TodayEarningsMovements WHERE eps_actual IS NOT NULL;
SELECT COUNT(*) FROM TodayEarningsMovements WHERE revenue_actual IS NOT NULL;
```

### **Kontrola cron jobov:**

```bash
crontab -l
```

## 🎯 **Záver**

**Actual dáta sa získavajú správne z Finnhub API**, ale môžu chýbať z nasledujúcich dôvodov:

1. **Timing** - earnings ešte neprebehli
2. **Cron joby** - nespúšťajú sa automaticky
3. **API limit** - dosiahnutý rate limit
4. **Chyby** - v databáze alebo API

**Odporúčam spustiť `Tests/check_actual_data.php` pre detailnú analýzu aktuálneho stavu.**
