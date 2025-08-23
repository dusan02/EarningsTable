# Cron Setup for Earnings Table

## Automatické spúšťanie cronov

Pre správne fungovanie aplikácie je potrebné nastaviť nasledujúce crony:

### 1. Denné načítanie earnings tickerov a company names
```bash
# Každý deň o 02:15 CET (pre kompatibilitu s US Eastern Time)
15 2 * * * /usr/bin/php /path/to/earnings-table/cron/fetch_earnings_tickers.php
```

**Tento cron:**
- Načíta earnings tickery z Finnhub API
- Uloží ich do databázy
- Automaticky načíta company names pre všetky tickery
- Spustí dodatočnú aktualizáciu company names

### 2. Aktualizácia cien a market cap každých 5 minút
```bash
# Každých 5 minút
*/5 * * * * /usr/bin/php /path/to/earnings-table/cron/current_prices_mcaps_updates.php
```

### 3. Aktualizácia EPS/Revenue dát každých 5 minút
```bash
# Každých 5 minút
*/5 * * * * /usr/bin/php /path/to/earnings-table/cron/update_earnings_eps_revenues.php
```

### 4. Cache shares outstanding (voliteľné)
```bash
# Denné o 03:00 CET
0 3 * * * /usr/bin/php /path/to/earnings-table/cron/cache_shares_outstanding.php
```

## Kontrola správneho fungovania

### Kontrola company names:
```bash
php check_company_names_join.php
```

### Kontrola API dát:
```bash
php test_api_data.php
```

### Kontrola EPS/Revenue dát:
```bash
php test_eps_data.php
```

## Riešenie problémov

### Ak sa company names nezobrazujú:
1. Spustite manuálne: `php cron/fetch_earnings_tickers.php`
2. Skontrolujte: `php check_company_names_join.php`
3. Ak problém pretrváva, spustite: `php cron/update_company_names.php`

### Ak sa znamienka nezobrazujú v Market Cap objektoch:
- Znamienka sa pridávajú automaticky cez `formatMarketCapDiff()` funkciu
- Kontrolujte, či sa `market_cap_diff` dáta správne načítavajú z API

## Poznámky

- **Časové pásmo:** Všetky crony používajú US Eastern Time pre kompatibilitu s Finnhub API
- **Rate limits:** Finnhub má limit 60 volaní/minútu, crony to berú do úvahy
- **Fallback:** Ak sa company name nedá načítať, použije sa ticker
- **Automatické opravy:** Crony majú vstavané opravy pre chyby a rate limits
