# 🧪 Test Summary - EarningsTable

## Overview

Tento dokument obsahuje súhrn všetkých testov v projekte EarningsTable a pokyny na ich spustenie.

## 🚀 Ako spustiť testy

### Prerekvizity

- PHP 7.4+ nainštalované
- Databáza nakonfigurovaná v `config.php`
- Všetky závislosti nainštalované cez Composer

### Spustenie testov

#### 1. Všetky základné testy (Makefile)

```bash
make test
```

alebo manuálne:

```bash
php Tests/test-db.php
php Tests/test-path.php
php Tests/check_tickers.php
```

#### 2. Individuálne testy

```bash
# Databázové testy
php Tests/test-db.php

# API testy
php Tests/test_api.php

# Bezpečnostné testy
php Tests/test_security_headers.php
php Tests/test_sql_injection.php
php Tests/test_rate_limiting.php

# Environment testy
php Tests/test_env.php
```

## 📋 Kategórie testov

### 🔧 Základné testy

#### `Tests/test-db.php`

**Účel:** Testuje databázové pripojenie a základnú funkcionalitu
**Testuje:**

- ✅ Databázové pripojenie
- ✅ Existencia tabuliek (EarningsTickersToday, TodayEarningsMovements, SharesOutstanding)
- ✅ PHP verzia
- ✅ Timezone nastavenie
- ✅ Zapísateľnosť priečinkov logs/ a storage/

#### `Tests/test-path.php`

**Účel:** Pomáha s konfiguráciou cron jobov
**Testuje:**

- ✅ Cesty k súborom
- ✅ Existencia cron súborov
- ✅ Odporúčané cron príkazy pre mydreams.cz

#### `Tests/check_tickers.php`

**Účel:** Kontroluje stav dát v databáze
**Testuje:**

- ✅ Počet záznamov v tabuľkách
- ✅ Ukážkové dáta z EarningsTickersToday
- ✅ Ukážkové dáta z TodayEarningsMovements

### 🌐 API testy

#### `Tests/test_api.php`

**Účel:** Testuje API endpoint a hlavné SQL dotazy
**Testuje:**

- ✅ Počet záznamov pre dnešný dátum
- ✅ Hlavný JOIN dotaz medzi tabuľkami
- ✅ Formátovanie dát pre zobrazenie
- ✅ Ukážkové dáta (prvých 10 záznamov)

### 🔐 Bezpečnostné testy

#### `Tests/test_security_headers.php`

**Účel:** Testuje HTTPS enforcement a security headers
**Testuje:**

- ✅ SecurityHeaders trieda
- ✅ Nastavenie bezpečnostných hlavičiek
- ✅ HTTPS detekcia
- ✅ Validácia požiadaviek
- ✅ CSP (Content Security Policy)
- ✅ HSTS (HTTP Strict Transport Security)

#### `Tests/test_sql_injection.php`

**Účel:** Testuje ochranu proti SQL injection
**Testuje:**

- ✅ Prepared statements
- ✅ Input validácia
- ✅ SQL injection pokusy
- ✅ Bezpečné parametre

#### `Tests/test_rate_limiting.php`

**Účel:** Testuje rate limiting funkcionalitu
**Testuje:**

- ✅ API rate limiting
- ✅ Request throttling
- ✅ Concurrent request handling
- ✅ Rate limit enforcement

### 🔧 Environment testy

#### `Tests/test_env.php`

**Účel:** Testuje environment premenné a konfiguráciu
**Testuje:**

- ✅ Načítanie .env súboru
- ✅ Databázové premenné
- ✅ API kľúče
- ✅ Debug nastavenia
- ✅ Timezone konfigurácia

### 📊 Logging a monitoring

#### `Tests/test_logging_monitoring.php`

**Účel:** Testuje logging a monitoring systém
**Testuje:**

- ✅ Logging funkcionalita
- ✅ Error handling
- ✅ Performance monitoring
- ✅ Log rotation

## 🧪 Debugging testy

### `Tests/debug_market_cap_update.php`

**Účel:** Debuguje aktualizácie market cap
**Funkcie:**

- Kontrola API volaní
- Validácia dát
- Error tracking

### `Tests/debug_polygon_market_cap.php`

**Účel:** Debuguje Polygon API volania
**Funkcie:**

- API response analýza
- Rate limiting kontrola
- Error handling

### `Tests/check_market_cap.php`

**Účel:** Kontroluje market cap dáta
**Funkcie:**

- Validácia market cap hodnôt
- Kontrola chýbajúcich dát
- Štatistiky

## 📝 Test data management

### `Tests/add_test_data.php`

**Účel:** Pridáva testovacie dáta do databázy
**Funkcie:**

- Vytvorenie testovacích tickerov
- Pridanie ukážkových earnings dát
- Simulácia market dát

### `Tests/remove_test_data.php`

**Účel:** Odstraňuje testovacie dáta
**Funkcie:**

- Vyčistenie testovacích záznamov
- Reset databázy na produkčné dáta

### `Tests/check_test_data.php`

**Účel:** Kontroluje testovacie dáta
**Funkcie:**

- Validácia testovacích záznamov
- Kontrola integrity dát

## 🎯 Výsledky testov

### Očakávané výsledky

- ✅ Všetky databázové testy by mali prejsť
- ✅ API testy by mali vrátiť dáta
- ✅ Bezpečnostné testy by mali potvrdiť ochranu
- ✅ Environment testy by mali potvrdiť konfiguráciu

### Časté problémy

1. **Databázové pripojenie zlyhá**

   - Skontrolujte `config.php`
   - Overte databázové credentials
   - Skontrolujte sieťové pripojenie

2. **Chýbajúce tabuľky**

   - Spustite `scripts/create_database.php`
   - Importujte SQL súbory z `sql/` priečinka

3. **API testy zlyhajú**

   - Overte API kľúče v konfigurácii
   - Skontrolujte rate limiting
   - Overte internetové pripojenie

4. **Bezpečnostné testy zlyhajú**
   - Skontrolujte HTTPS konfiguráciu
   - Overte security headers
   - Skontrolujte web server konfiguráciu

## 🔄 CI/CD integrácia

Testy sú integrované do CI/CD pipeline cez Makefile:

```bash
# Kompletná kontrola kvality
make quality

# Individuálne kontroly
make stan      # PHPStan analýza
make cs        # CodeSniffer kontrola
make test-env  # Environment testy
make test-rate # Rate limiting testy
make test-sql  # SQL injection testy
make test-log  # Logging testy
make test-headers # Security headers testy
```

## 📊 Monitoring

Testy sú automaticky spúšťané:

- **Cron joby:** Denné kontroly
- **Deployment:** Pred každým nasadením
- **Development:** Pri každej zmene kódu

## 📚 Ďalšie informácie

- **Dokumentácia:** `docs/` priečinok
- **Konfigurácia:** `config/` priečinok
- **Skripty:** `scripts/` priečinok
- **Logy:** `logs/` priečinok
