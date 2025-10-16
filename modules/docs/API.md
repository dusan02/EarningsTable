# API Dokumentácia

## Finnhub API

### Endpoint: Calendar Earnings

```
GET https://finnhub.io/api/v1/calendar/earnings
```

**Parametre:**

- `from` (string) - dátum od (YYYY-MM-DD)
- `to` (string) - dátum do (YYYY-MM-DD)
- `token` (string) - API token

**Príklad odpovede:**

```json
{
  "earningsCalendar": [
    {
      "symbol": "AAPL",
      "date": "2025-01-13",
      "hour": "amc",
      "epsActual": 1.52,
      "epsEstimate": 1.5,
      "revenueActual": 123900000000,
      "revenueEstimate": 120000000000,
      "quarter": 4,
      "year": 2024
    }
  ]
}
```

## Web API

### GET /api/earnings

Vráti zoznam earnings reportov z databázy.

**Response:**

```json
[
  {
    "id": 1,
    "reportDate": "2025-01-13T00:00:00.000Z",
    "symbol": "AAPL",
    "epsActual": 1.52,
    "epsEstimate": 1.5,
    "revenueActual": 123900000000,
    "revenueEstimate": 120000000000,
    "hour": "amc",
    "quarter": 4,
    "year": 2024,
    "createdAt": "2025-01-13T10:00:00.000Z",
    "updatedAt": "2025-01-13T10:00:00.000Z"
  }
]
```

## Cron Jobs

### Finnhub Data Cron

- **Schedule:** `0 7 * * *` (každý deň o 07:00 NY time)
- **Funkcia:** Načítava earnings dáta z Finnhub API
- **Timezone:** America/New_York

### News Cron (príklad)

- **Schedule:** `0 * * * *` (každú hodinu)
- **Funkcia:** Načítava finančné správy
- **Status:** Príklad implementácie
