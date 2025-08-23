# Earnings Table Module

PHP 8.2 module for fetching and displaying earnings tickers with daily movements.

## 📊 Database Tables

### EarningsTickersToday

Stores earnings calendar data from Finnhub API.

- **Source:** Finnhub `/calendar/earnings`
- **Columns:** 7 (report_date, ticker, report_time, eps_actual, eps_estimate, revenue_actual, revenue_estimate)
- **Update Frequency:** Daily at 02:15 CET (to ensure US Eastern Time compatibility)

### TodayEarningsMovements

Stores daily price movements and market cap calculations.

- **Source:** Polygon API + Finnhub shares outstanding
- **Columns:** 10 (ticker, company_name, current_price, previous_close, market_cap, size, market_cap_diff, market_cap_diff_billions, price_change_percent, shares_outstanding)
- **Update Frequency:** Every 5 minutes

### SharesOutstanding

Caches shares outstanding data to reduce API calls.

- **Source:** Finnhub + Polygon V3 Reference
- **Update Frequency:** Daily

## 🔧 Installation

1. **Database Setup:**

   ```bash
   php scripts/setup_all.php
   ```

2. **Configuration:**

   - Copy `config.sample.php` to `config.php`
   - Add your API keys (Finnhub, Polygon)

## Cron Jobs

### Production (Websupport.cz)

```bash
# Daily earnings fetch (Finnhub API)
0 2 15 * * /usr/bin/php /path/to/earnings-table/cron/fetch_earnings_tickers.php

# Update movements every 5 minutes (Polygon API)
*/5 * * * * /usr/bin/php /path/to/earnings-table/cron/current_prices_mcaps_updates.php

# Update earnings data every 5 minutes (Finnhub API)
*/5 * * * * /usr/bin/php /path/to/earnings-table/cron/update_earnings_eps_revenues.php
```

### Local Development (XAMPP)

```bash
# Manual execution for testing
php cron/fetch_earnings_tickers.php
php cron/current_prices_mcaps_updates.php
php cron/update_earnings_eps_revenues.php
```

## 🌐 API Endpoints

- **Earnings Tickers:** `/public/api/earnings-tickers-today.php`
- **Today Movements:** `/public/api/today-earnings-movements.php`

## 📱 Frontend

- **Earnings Table:** `/public/earnings-table.html`
- **Movements Table:** `/public/today-movements-table.html`

## ⚡ Performance

- **Batch API calls** for optimal performance
- **Rate limiting** to prevent API throttling
- **Caching** for shares outstanding data
- **Lock mechanism** to prevent concurrent execution

## 🔍 Monitoring

- **Status check:** `php scripts/status.php`
- **Performance metrics** in cron output
- **Error logging** for troubleshooting

## 📝 Notes

- **US Eastern Time:** Finnhub API uses ET, so cron runs at 02:15 CET to ensure correct date
- **Market Cap Calculation:** Current Price × Shares Outstanding
- **Size Categories:** Large (>$10B), Mid ($1B-$10B), Small (<$1B)
