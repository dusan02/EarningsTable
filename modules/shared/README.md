# Shared Module

Zdieľaný modul obsahujúci spoločné typy, konfiguráciu a utility funkcie pre celú EarningsTable aplikáciu.

## Funkcie

- **Typy** - TypeScript definície pre celú aplikáciu
- **Konfigurácia** - centralizovaná konfigurácia
- **Utility funkcie** - spoločné helper funkcie
- **Validácia** - validácia environment premenných

## Štruktúra

```
shared/
├── src/
│   ├── types.ts          # TypeScript typy
│   ├── config.ts         # Konfigurácia a environment
│   ├── utils.ts          # Utility funkcie
│   └── index.ts          # Hlavný export súbor
├── package.json          # Dependencies
└── README.md            # Dokumentácia
```

## Typy

### FinnhubEarning

Reprezentuje earnings dátum z Finnhub API.

### EarningsReport

Reprezentuje earnings report v databáze.

### CreateEarningsReport

Typ pre vytvorenie nového earnings reportu.

### ApiResponse<T>

Generický typ pre API odpovede.

### CronJobConfig

Konfigurácia pre cron joby.

## Konfigurácia

### CONFIG objekt

Centralizovaná konfigurácia aplikácie:

- `FINNHUB_TOKEN` - API token pre Finnhub
- `DATABASE_URL` - URL databázy
- `CRON_TZ` - časové pásmo pre cron joby
- `CRON_EXPR` - cron výraz
- `PORT` - port web servera
- `NODE_ENV` - environment (development/production)

### Utility funkcie

- `validateConfig()` - validuje povinné environment premenné
- `isDevelopment()` - kontroluje či je development mode
- `isProduction()` - kontroluje či je production mode
- `todayIsoNY()` - vráti dnešný dátum v NY časovom pásme

## Utility funkcie

### Konverzie

- `toNumber()` - konvertuje na číslo alebo null
- `toInteger()` - konvertuje na integer alebo null
- `toBigInt()` - konvertuje na BigInt alebo null

### Formátovanie

- `formatNumber()` - formátuje číslo s čiarkami
- `formatBigInt()` - formátuje BigInt s čiarkami
- `formatDate()` - formátuje dátum do ISO formátu
- `formatTime()` - formátuje čas do čitateľného formátu

### Logging

- `logWithTimestamp()` - loguje správu s timestampom

### Async

- `sleep()` - čaká určitý počet milisekúnd
- `retry()` - retry mechanizmus pre async funkcie

## Použitie

### Import typov:

```typescript
import { FinnhubEarning, EarningsReport } from "@earnings-table/shared";
```

### Import konfigurácie:

```typescript
import { CONFIG, validateConfig } from "@earnings-table/shared";
```

### Import utility funkcií:

```typescript
import { formatNumber, logWithTimestamp, retry } from "@earnings-table/shared";
```

## Environment premenné

```env
FINNHUB_TOKEN=your_finnhub_api_key
DATABASE_URL=file:./prisma/dev.db
CRON_TZ=America/New_York
CRON_EXPR=0 7 * * *
PORT=3000
NODE_ENV=development
```
