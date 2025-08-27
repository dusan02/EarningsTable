# 🧪 Tests Directory - EarningsTable Test Suite

Táto zložka obsahuje všetky testovacie súbory pre EarningsTable projekt.

## 📁 Kategórie testov

### 🔧 **Základné testy**

- `test-db.php` - Test databázového pripojenia
- `test-path.php` - Test ciest pre cron jobs
- `test_db.php` - Alternatívny test databázy
- `simple_db_test.php` - Jednoduchý test databázy
- `health_db.php` - Kontrola zdravia databázy
- `db_inspector.php` - Inšpektor databázy

### 🌐 **API testy**

- `test_api.php` - Test API endpointov
- `test_curl_multi_speed.php` - Test rýchlosti curl_multi
- `test_polygon_response.php` - Test Polygon API odpovedí
- `test_polygon_structure.php` - Test štruktúry Polygon dát
- `test_yahoo_finance.php` - Test Yahoo Finance API
- `test_yahoo_market_cap.php` - Test Yahoo Finance market cap

### 🔍 **Debug súbory**

- `debug_api.php` - Debug API volaní
- `debug_api_types.php` - Debug typov API dát
- `debug_direct.php` - Priamy debug
- `debug_finnhub_simple.php` - Jednoduchý debug Finnhub
- `debug_metrics.php` - Debug metrík
- `debug_revenue.php` - Debug revenue dát
- `debug_test.php` - Všeobecný debug test

### ✅ **Check súbory**

- `check_tickers.php` - Kontrola tickerov
- `check_data.php` - Kontrola dát
- `check_tables.php` - Kontrola tabuliek
- `check_table_structure.php` - Kontrola štruktúry tabuliek
- `check_earnings.php` - Kontrola earnings dát
- `check_earnings_tickers.php` - Kontrola earnings tickerov
- `check_estimates.php` - Kontrola estimates
- `check_estimates_detail.php` - Detailná kontrola estimates
- `check_final_results.php` - Kontrola finálnych výsledkov
- `check_finnhub_today.php` - Kontrola dnešných Finnhub dát
- `check_missing_tickers.php` - Kontrola chýbajúcich tickerov
- `check_missing_in_api.php` - Kontrola chýbajúcich v API
- `check_null_values.php` - Kontrola NULL hodnôt
- `check_page.php` - Kontrola stránky
- `check_valid_prices.php` - Kontrola platných cien
- `check_company_names_join.php` - Kontrola company names join
- `check_db_direct.php` - Priama kontrola databázy
- `check_count.php` - Kontrola počtu záznamov
- `check_update_time.php` - Kontrola času aktualizácie
- `check_today_earnings.php` - Kontrola dnešných earnings
- `check_gild_fixed.php` - Kontrola opravených GILD dát
- `check_large_tickers.php` - Kontrola veľkých tickerov
- `check_lly_accurate.php` - Kontrola presnosti LLY
- `check_panw.php` - Kontrola PANW
- `check_problematic_tickers.php` - Kontrola problematických tickerov
- `check_specific_tickers.php` - Kontrola špecifických tickerov
- `check_welnf.php` - Kontrola WELNF

### 🎨 **HTML testy**

- `test_colors.html` - Test farieb
- `test_sorting.html` - Test triedenia
- `test_sorting_final.html` - Finálny test triedenia
- `test_api_simple.html` - Jednoduchý API test
- `test_api_response.html` - Test API odpovedí
- `debug_js_simulation.html` - Debug JS simulácie

### 🧪 **Filtrovacie testy**

- `test_filter.php` - Test filtrovania
- `test_filter_detailed.php` - Detailný test filtrovania

### 📊 **Status a rýchle testy**

- `status_check.php` - Kontrola stavu
- `quick_test.php` - Rýchly test

## 🚀 **Ako spúšťať testy**

### **Všetky testy naraz:**

```powershell
Get-ChildItem Tests\*.php | ForEach-Object {
    Write-Host "=== Testing $_ ===" -ForegroundColor Cyan
    D:\XAMPP\php\php.exe $_.FullName
    Write-Host "`n"
}
```

### **Kategórie testov:**

```powershell
# Základné testy
D:\XAMPP\php\php.exe Tests\test-db.php
D:\XAMPP\php\php.exe Tests\test-path.php

# API testy
D:\XAMPP\php\php.exe Tests\test_api.php
D:\XAMPP\php\php.exe Tests\test_curl_multi_speed.php

# Check testy
D:\XAMPP\php\php.exe Tests\check_tickers.php
D:\XAMPP\php\php.exe Tests\check_data.php
```

### **Webové testy:**

```
http://localhost/earnings-table/Tests/test_colors.html
http://localhost/earnings-table/Tests/test_sorting.html
```

## 📝 **Poznámky**

- **Nedeľa:** Väčšina testov ukáže 0 záznamov (normálne)
- **API chyby:** Yahoo Finance API môže vrátiť 401 (potrebuje API kľúč)
- **Localhost:** Niektoré testy vyžadujú spustený webový server

## 🎯 **Najdôležitejšie testy**

1. `test-db.php` - Databázové pripojenie
2. `test-path.php` - Cron job cesty
3. `check_tickers.php` - Kontrola tickerov
4. `test_curl_multi_speed.php` - Rýchlosť API volaní
5. `test_api.php` - API funkcionalita

**Celkovo: 45 testovacích súborov** 🧪
