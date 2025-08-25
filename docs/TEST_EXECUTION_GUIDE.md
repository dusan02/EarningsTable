# 🚀 Test Execution Guide - EarningsTable

## Overview
Tento dokument obsahuje pokyny na spustenie testov v projekte EarningsTable v rôznych prostrediach.

## 🔧 Prerekvizity

### 1. PHP inštalácia
PHP 7.4+ je potrebné pre spustenie testov.

#### Windows - Inštalácia PHP:

**Možnosť 1: XAMPP**
```bash
# Stiahnite XAMPP z https://www.apachefriends.org/
# Nainštalujte a PHP bude dostupné v C:\xampp\php\php.exe
```

**Možnosť 2: WAMP**
```bash
# Stiahnite WAMP z https://www.wampserver.com/
# Nainštalujte a PHP bude dostupné v C:\wamp64\bin\php\php8.x.x\php.exe
```

**Možnosť 3: Chocolatey (Administrator)**
```bash
# Otvorte PowerShell ako Administrator
choco install php
```

**Možnosť 4: Manuálna inštalácia**
```bash
# Stiahnite PHP z https://windows.php.net/download/
# Rozbaľte do C:\php
# Pridajte C:\php do PATH
```

### 2. Databázová konfigurácia
Skontrolujte, či je `config.php` správne nakonfigurovaný:
- Databázové pripojenie
- API kľúče
- Timezone nastavenie

### 3. Composer závislosti
```bash
composer install
```

## 🧪 Spustenie testov

### Základné testy

#### 1. Databázový test
```bash
php Tests/test-db.php
```

**Očakávaný výstup:**
```
🔧 EarningsTable - Database Connection Test
Testing connection to mydreams.cz hosting...

✅ Database connection: SUCCESS
✅ Table EarningsTickersToday: EXISTS
✅ Table TodayEarningsMovements: EXISTS
✅ Table SharesOutstanding: EXISTS
✅ PHP version: 8.1.0
✅ Timezone: America/New_York
✅ Logs directory: WRITABLE
✅ Storage directory: WRITABLE

🎉 All tests completed!
```

#### 2. Path test (pre cron joby)
```bash
php Tests/test-path.php
```

**Očakávaný výstup:**
```
=== PATH TEST FOR EARNINGSTABLE.COM ===

Current directory (__DIR__): D:\Projects\EarningsTable
Document root: D:\Projects\EarningsTable
Script path: /Tests/test-path.php
Full path: D:\Projects\EarningsTable\Tests\test-path.php
Working directory: D:\Projects\EarningsTable

=== CRON JOB PATHS ===

✅ cron/clear_old_data.php: EXISTS at D:\Projects\EarningsTable\cron\clear_old_data.php
✅ cron/fetch_finnhub_earnings_today_tickers.php: EXISTS at D:\Projects\EarningsTable\cron\fetch_finnhub_earnings_today_tickers.php
✅ cron/fetch_missing_tickers_yahoo.php: EXISTS at D:\Projects\EarningsTable\cron\fetch_missing_tickers_yahoo.php
✅ cron/fetch_market_data_complete.php: EXISTS at D:\Projects\EarningsTable\cron\fetch_market_data_complete.php
✅ cron/run_5min_updates.php: EXISTS at D:\Projects\EarningsTable\cron\run_5min_updates.php
```

#### 3. Ticker data test
```bash
php Tests/check_tickers.php
```

**Očakávaný výstup:**
```
=== DATABASE STATUS ===
EarningsTickersToday: 15
TodayEarningsMovements: 12

Sample data from EarningsTickersToday:
- AAPL - 2024-01-15 16:30:00 - EPS Est: 2.10
- MSFT - 2024-01-15 17:00:00 - EPS Est: 2.78
- GOOGL - 2024-01-15 16:00:00 - EPS Est: 1.45

Sample data from TodayEarningsMovements:
- AAPL - Price: $185.64, Market Cap: $2,890,000,000,000, EPS: 2.18
- MSFT - Price: $374.58, Market Cap: $2,785,000,000,000, EPS: 2.93
- GOOGL - Price: $142.56, Market Cap: $1,789,000,000,000, EPS: 1.64
```

### API testy

#### 4. API endpoint test
```bash
php Tests/test_api.php
```

**Očakávaný výstup:**
```
Testing API endpoint...

Date: 2024-01-15

EarningsTickersToday count: 15
TodayEarningsMovements count: 12

Sample data (first 10 records):
--------------------------------------------------------------------------------
Ticker   Company             Current    Previous   Market Cap      Size     Change %     Time    
--------------------------------------------------------------------------------
AAPL     Apple Inc.          $185.64    $183.79    $2,890.00B      Large    1.01%        16:30   
MSFT     Microsoft Corp.     $374.58    $372.05    $2,785.00B      Large    0.68%        17:00   
GOOGL    Alphabet Inc.       $142.56    $141.80    $1,789.00B      Large    0.54%        16:00   
```

### Bezpečnostné testy

#### 5. Security headers test
```bash
php Tests/test_security_headers.php
```

**Očakávaný výstup:**
```
🌐 Security Headers Test
======================

1. Načítavam súbory...
✅ Súbory načítané

2. Test SecurityHeaders...
   ✅ SecurityHeaders vytvorený
   ✅ Bezpečnostné hlavičky nastavené

3. Test HTTPS detekcie...
   ✅ HTTPS detekcia funguje

4. Test validácie požiadavky...
   ✅ Validácia požiadavky funguje (detekované problémy)
     - Suspicious User-Agent detected

5. Test hlavičiek...
   ✅ Content-Security-Policy nastavená
   ✅ X-Frame-Options nastavená
   ✅ X-Content-Type-Options nastavená
   ✅ Strict-Transport-Security nastavená
```

#### 6. SQL injection test
```bash
php Tests/test_sql_injection.php
```

**Očakávaný výstup:**
```
🔍 SQL Injection Protection Test
================================

1. Test prepared statements...
   ✅ Prepared statements fungujú správne

2. Test input validácie...
   ✅ Input validácia funguje

3. Test SQL injection pokusov...
   ✅ SQL injection pokusy blokované

4. Test bezpečných parametrov...
   ✅ Bezpečné parametre fungujú správne

🎉 Všetky SQL injection testy prešli!
```

#### 7. Rate limiting test
```bash
php Tests/test_rate_limiting.php
```

**Očakávaný výstup:**
```
🚦 Rate Limiting Test
====================

1. Test API rate limiting...
   ✅ Rate limiting funguje správne

2. Test request throttling...
   ✅ Request throttling aktívny

3. Test concurrent requests...
   ✅ Concurrent request handling funguje

4. Test rate limit enforcement...
   ✅ Rate limit enforcement aktívny

🎉 Všetky rate limiting testy prešli!
```

### Environment testy

#### 8. Environment test
```bash
php Tests/test_env.php
```

**Očakávaný výstup:**
```
🧪 Environment Test
==================

1. Test .env načítania...
   ✅ .env súbor načítaný

2. Test databázových premenných...
   ✅ DB_HOST: localhost
   ✅ DB_NAME: earnings_table
   ✅ DB_USER: root

3. Test API kľúčov...
   ✅ FINNHUB_API_KEY: nastavený
   ✅ POLYGON_API_KEY: nastavený

4. Test debug nastavení...
   ✅ DEBUG_MODE: true
   ✅ LOG_LEVEL: INFO

5. Test timezone konfigurácie...
   ✅ Timezone: America/New_York

🎉 Všetky environment testy prešli!
```

## 🔄 Automatické spustenie testov

### Pomocou Makefile (Linux/Mac)
```bash
make test
```

### Pomocou PowerShell skriptu (Windows)
```powershell
# Vytvorte test-runner.ps1
.\test-runner.ps1
```

### Pomocou batch súboru (Windows)
```batch
# Vytvorte run-tests.bat
run-tests.bat
```

## 🚨 Riešenie problémov

### 1. PHP nie je dostupné
**Problém:** `'php' is not recognized as an internal or external command`

**Riešenie:**
```bash
# Pridajte PHP do PATH
setx PATH "%PATH%;C:\php"

# Alebo použite plnú cestu
C:\xampp\php\php.exe Tests/test-db.php
```

### 2. Databázové pripojenie zlyhá
**Problém:** `Database connection failed`

**Riešenie:**
- Skontrolujte `config.php`
- Overte databázové credentials
- Skontrolujte sieťové pripojenie
- Spustite `scripts/create_database.php`

### 3. Chýbajúce tabuľky
**Problém:** `Table XXX: MISSING`

**Riešenie:**
```bash
# Vytvorte databázu
php scripts/create_database.php

# Importujte SQL súbory
mysql -u root -p earnings_table < sql/setup_all_tables.sql
```

### 4. API testy zlyhajú
**Problém:** `API Error: Invalid API key`

**Riešenie:**
- Skontrolujte API kľúče v `config.php`
- Overte platnosť API kľúčov
- Skontrolujte rate limiting

### 5. Bezpečnostné testy zlyhajú
**Problém:** `Security headers not set`

**Riešenie:**
- Skontrolujte web server konfiguráciu
- Overte HTTPS nastavenie
- Skontrolujte `.htaccess` alebo `web.config`

## 📊 Výsledky testov

### Úspešné testy
Všetky testy by mali vrátiť:
- ✅ Zelené značky pre úspešné testy
- 📊 Štatistiky a dáta
- 🎉 Potvrdenie úspechu

### Zlyhané testy
Zlyhané testy zobrazia:
- ❌ Červené značky pre zlyhané testy
- 🔍 Detailné chybové správy
- 💡 Návrhy na riešenie

## 🔄 CI/CD integrácia

### GitHub Actions
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Run tests
        run: |
          php Tests/test-db.php
          php Tests/test_api.php
          php Tests/test_security_headers.php
```

### Lokálny CI/CD
```bash
# Kompletná kontrola kvality
make quality

# Individuálne kontroly
make test-db
make test-api
make test-security
```

## 📚 Ďalšie informácie

- **Dokumentácia testov:** `docs/TEST_SUMMARY.md`
- **Konfigurácia:** `config/` priečinok
- **Skripty:** `scripts/` priečinok
- **Logy:** `logs/` priečinok
- **Príklady:** `examples/` priečinok
