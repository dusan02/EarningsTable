# ⏰ Testovanie Cron Jobs pre earningstable.com

## 🔍 Postup testovania

### 1. **Zistenie správnej cesty**

Po uploadovaní súborov na mydreams.cz:

1. Otvorte: `https://earningstable.com/test-path.php`
2. Skopírujte si výstup - obsahuje správne cesty pre cron jobs
3. Pozrite si, či všetky cron súbory existujú

### 2. **Manuálne testovanie**

Pred nastavením automatických cron jobs, otestujte manuálne:

#### **Test 1: Daily cleanup**
```bash
# Cez SSH alebo cez mydreams.cz admin panel
php /cesta/k/earningstable.com/cron/clear_old_data.php --force
```

#### **Test 2: Fetch earnings**
```bash
php /cesta/k/earningstable.com/cron/fetch_finnhub_earnings_today_tickers.php
```

#### **Test 3: 5-min updates**
```bash
php /cesta/k/earningstable.com/cron/run_5min_updates.php
```

### 3. **Nastavenie cron jobs**

Cez mydreams.cz admin panel nastavte:

#### **Správne cesty pre earningstable.com:**

```
# Daily cleanup (08:00 CET)
0 8 * * * /usr/bin/php /home/username/public_html/cron/clear_old_data.php

# Fetch earnings (08:30 CET)
30 8 * * * /usr/bin/php /home/username/public_html/cron/fetch_finnhub_earnings_today_tickers.php

# Fetch missing tickers (08:40 CET)
40 8 * * * /usr/bin/php /home/username/public_html/cron/fetch_missing_tickers_yahoo.php

# Fetch market data (09:00 CET)
0 9 * * * /usr/bin/php /home/username/public_html/cron/fetch_market_data_complete.php

# 5-min updates
*/5 * * * * /usr/bin/php /home/username/public_html/cron/run_5min_updates.php
```

**Dôležité**: Nahraďte `/home/username/public_html/` skutočnou cestou z `test-path.php`

### 4. **Monitoring cron jobs**

#### **Kontrola logov:**
```bash
# Pozrite si logy v logs/ adresári
tail -f /cesta/k/earningstable.com/logs/app.log
```

#### **Test API endpointov:**
```
https://earningstable.com/public/api/earnings-tickers-today.php
```

#### **Test dashboardu:**
```
https://earningstable.com/public/dashboard-fixed.html
```

### 5. **Riešenie problémov**

#### **Problém: "Permission denied"**
```bash
# Nastavte práva na cron súbory
chmod 755 /cesta/k/earningstable.com/cron/
chmod 644 /cesta/k/earningstable.com/cron/*.php
```

#### **Problém: "PHP not found"**
```bash
# Zistite cestu k PHP
which php
# Alebo
/usr/bin/php --version
```

#### **Problém: "Database connection failed"**
- Skontrolujte `config.php`
- Overte databázové údaje
- Testujte cez `test-db.php`

#### **Problém: "API rate limit"**
- Skontrolujte API kľúče
- Overte limity účtov
- Pozrite si logy pre detaily

### 6. **Časové pásma**

**Dôležité**: Cron jobs používajú serverové časové pásmo (CET/CEST)

- **02:00 NY time** = **08:00 CET** (zimný čas)
- **02:00 NY time** = **07:00 CEST** (letný čas)

### 7. **Bezpečnosť**

#### **Ochrana cron súborov:**
```apache
# V .htaccess
<Files "cron/*.php">
    Order allow,deny
    Deny from all
</Files>
```

#### **Logovanie:**
Všetky cron jobs logujú do `logs/` adresára pre debugging.

### 8. **Optimalizácia**

#### **Pre lepší výkon:**
- Nastavte `memory_limit = 256M` v PHP
- Použite `max_execution_time = 300`
- Povolte `allow_url_fopen = On`

#### **Pre spoľahlivosť:**
- Nastavte `error_reporting = E_ALL` pre debugging
- Použite `log_errors = On`
- Nastavte `error_log = /cesta/k/logs/php_errors.log`

---

**Poznámka**: Vždy najprv otestujte cron jobs manuálne pred nastavením automatického spúšťania!
