# 🚀 Hosting na mydreams.cz - Návod

## 📋 Požiadavky na hosting

### Minimálne požiadavky:
- **PHP**: 8.0 alebo vyššie
- **MySQL**: 5.7 alebo vyššie
- **Web server**: Apache s mod_rewrite
- **Disk space**: minimálne 100 MB
- **RAM**: minimálne 128 MB

### Odporúčané:
- **PHP**: 8.1 alebo 8.2
- **MySQL**: 8.0
- **SSL certifikát**: pre bezpečné HTTPS
- **Cron jobs**: pre automatické spúšťanie skriptov

## 🔧 Postup inštalácie

### 1. **Upload súborov**

1. Stiahnite archív `EarningsTable-hosting.zip` z GitHub
2. Rozbaľte archív na váš počítač
3. Uploadujte súbory cez FTP/SFTP do root adresára vašej domény
4. **Dôležité**: Uploadujte všetky súbory okrem `config.php`

### 2. **Databázová konfigurácia**

1. Prihláste sa do **phpMyAdmin** (cez mydreams.cz admin panel)
2. Vytvorte novú databázu (napr. `earnings_table`)
3. Vytvorte databázového používateľa s prístupom k tejto databáze
4. Importujte SQL schému:
   ```sql
   -- Spustite súbor sql/setup_all_tables.sql
   ```

### 3. **Konfigurácia aplikácie**

1. Skopírujte `config.example.php` na `config.php`
2. Upravte `config.php` s vašimi údajmi:

```php
// Databázové údaje z mydreams.cz
define('DB_HOST', 'localhost'); // alebo váš DB server
define('DB_NAME', 'vaša_databáza');
define('DB_USER', 'vaš_username');
define('DB_PASS', 'vaše_heslo');

// API kľúče
define('FINNHUB_API_KEY', 'váš_finnhub_kľúč');
define('POLYGON_API_KEY', 'váš_polygon_kľúč');
```

### 4. **Nastavenie práv súborov**

Nastavte práva na adresáre:
```bash
chmod 755 logs/
chmod 755 storage/
chmod 644 config.php
```

### 5. **Web server konfigurácia**

Vytvorte `.htaccess` súbor v root adresári:

```apache
RewriteEngine On

# Presmerovanie na HTTPS (ak máte SSL)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Bezpečnosť
<Files "config.php">
    Order allow,deny
    Deny from all
</Files>

# PHP nastavenia
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 300
php_value memory_limit 256M
```

## ⏰ Nastavenie Cron Jobs

### Pre earningstable.com na mydreams.cz:

**Dôležité**: Nahraďte `/cesta/k/vašej/doméne/` skutočnou cestou na mydreams.cz. Typicky to bude:
- `/home/username/public_html/` alebo
- `/var/www/earningstable.com/` alebo
- `/home/earningstable/public_html/`

#### **Správne nastavenie pre earningstable.com:**

1. **Daily cleanup** (02:00 NY time = 08:00 CET):
   ```
   0 8 * * * /usr/bin/php /home/username/public_html/cron/clear_old_data.php
   ```

2. **Fetch earnings** (02:30 NY time = 08:30 CET):
   ```
   30 8 * * * /usr/bin/php /home/username/public_html/cron/fetch_finnhub_earnings_today_tickers.php
   ```

3. **Fetch missing tickers** (02:40 NY time = 08:40 CET):
   ```
   40 8 * * * /usr/bin/php /home/username/public_html/cron/fetch_missing_tickers_yahoo.php
   ```

4. **Fetch market data** (03:00 NY time = 09:00 CET):
   ```
   0 9 * * * /usr/bin/php /home/username/public_html/cron/fetch_market_data_complete.php
   ```

5. **5-min updates** (každých 5 minút):
   ```
   */5 * * * * /usr/bin/php /home/username/public_html/cron/run_5min_updates.php
   ```

#### **Ako zistiť správnu cestu:**

1. **Cez FTP**: Pripojte sa cez FTP a pozrite si cestu k súborom
2. **Cez SSH**: Ak máte SSH prístup, použite `pwd` príkaz
3. **Cez admin panel**: mydreams.cz admin panel zobrazuje cestu k súborom
4. **Test súbor**: Vytvorte test súbor a pozrite si jeho cestu

#### **Test správnej cesty:**

Vytvorte súbor `test-path.php` v root adresári:
```php
<?php
echo "Current path: " . __DIR__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
?>
```

Potom spustite: `php test-path.php` cez SSH alebo cez cron test.

#### **Alternatívne riešenie - relatívne cesty:**

Ak absolútne cesty nefungujú, použite relatívne cesty z root adresára:

```
0 8 * * * cd /home/username/public_html && /usr/bin/php cron/clear_old_data.php
30 8 * * * cd /home/username/public_html && /usr/bin/php cron/fetch_finnhub_earnings_today_tickers.php
40 8 * * * cd /home/username/public_html && /usr/bin/php cron/fetch_missing_tickers_yahoo.php
0 9 * * * cd /home/username/public_html && /usr/bin/php cron/fetch_market_data_complete.php
*/5 * * * * cd /home/username/public_html && /usr/bin/php cron/run_5min_updates.php
```

## 🔍 Testovanie

### 1. **Test databázového pripojenia**
```
https://vaša-doména.cz/test-db.php
```

### 2. **Test API endpointov**
```
https://vaša-doména.cz/public/api/earnings-tickers-today.php
```

### 3. **Test dashboardu**
```
https://vaša-doména.cz/public/dashboard-fixed.html
```

## 🛠️ Riešenie problémov

### Časté problémy:

1. **"Database connection failed"**
   - Skontrolujte DB údaje v `config.php`
   - Overte, či databáza existuje

2. **"Permission denied"**
   - Nastavte práva na adresáre `logs/` a `storage/`
   - Skontrolujte práva na `config.php`

3. **"API rate limit exceeded"**
   - Skontrolujte API kľúče
   - Overte limity vašich API účtov

4. **Cron jobs nefungujú**
   - Skontrolujte cestu k PHP
   - Overte logy v `logs/` adresári

## 📞 Podpora mydreams.cz

Ak máte problémy s hostingom:
- **Email**: support@mydreams.cz
- **Telefón**: +420 xxx xxx xxx
- **Live chat**: cez admin panel

## 🔒 Bezpečnosť

1. **Zálohovanie**: Pravidelne zálohujte databázu
2. **Aktualizácie**: Udržiavajte PHP a MySQL aktuálne
3. **SSL**: Používajte HTTPS
4. **API kľúče**: Chráňte svoje API kľúče

## 📊 Monitoring

Sledujte:
- Logy v `logs/` adresári
- Databázové veľkosti
- API použitie
- Výkon aplikácie

---

**Poznámka**: Tento návod predpokladá, že máte prístup k admin panelu mydreams.cz a možnosť nastaviť cron jobs.
