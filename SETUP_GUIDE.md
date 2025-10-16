# 🚀 EarningsTable - Kompletný Setup Guide

## 📋 Prehľad projektu

**EarningsTable** je modulárna web aplikácia pre zobrazenie earnings dát s cron jobmi pre načítavanie dát z Finnhub a Polygon API.

## 🏗️ Architektúra projektu

```
EarningsTable/
├── modules/
│   ├── database/          # Databázový modul (Prisma + SQLite)
│   ├── cron/             # Cron joby pre načítavanie dát
│   ├── web/              # Web aplikácia (Express + React-like UI)
│   ├── shared/           # Zdieľané typy a utility
│   └── docs/             # Dokumentácia
├── .env                  # Environment premenné (API kľúče)
├── package.json         # Hlavný package.json
└── SETUP_GUIDE.md       # Tento súbor
```

## 🔑 Environment premenné

### Súbor: `.env` (v root priečinku)

```env
DATABASE_URL="file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
FINNHUB_TOKEN="d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
POLYGON_API_KEY="Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
CRON_TZ="America/New_York"
PORT=5555
NODE_ENV="development"
```

### API kľúče:

- **Finnhub**: `d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0`
- **Polygon**: `Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX`

## 🗄️ Databáza (Prisma + SQLite)

### Lokácia:

- **Súbor**: `modules/database/prisma/dev.db`
- **Schema**: `modules/database/prisma/schema.prisma`

### Tabuľky:

1. **FinhubData** - Earnings dáta z Finnhub API
2. **PolygonData** - Market cap dáta z Polygon API
3. **FinalReport** - Kombinované dáta z oboch zdrojov

### Prisma Studio:

```bash
cd modules/database
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
npx prisma studio --port 5556
```

**URL**: http://localhost:5556/

## 🕐 Cron úlohy

### Lokácia: `modules/cron/`

### Dostupné úlohy:

1. **Finnhub Cron** - Načítava earnings dáta (denne o 7:00 NY)
2. **Polygon Cron** - Načítava market cap dáta (každé 4 hodiny)
3. **Final Report** - Generuje kombinované reporty

### Spustenie cron úloh:

```bash
cd modules/cron

# 1. Reset cron
npm run restart

# 2. Finnhub cron (jednorazovo)
$env:FINNHUB_TOKEN = "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
npm run finnhub_data:once

# 3. Polygon cron (jednorazovo)
$env:POLYGON_API_KEY = "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
npm run polygon_data:once

# 4. Finálny report
npx tsx src/generate-final-report.ts

# 5. Všetky crony s štatistikami
$env:FINNHUB_TOKEN = "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
$env:POLYGON_API_KEY = "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
npm run run-all
```

## 🌐 Webová aplikácia

### Lokácia: `modules/web/`

### Spustenie:

```bash
cd modules/web
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
$env:FINNHUB_TOKEN = "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
$env:POLYGON_API_KEY = "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
npm start
```

### URL:

- **Hlavná stránka**: http://localhost:5555/
- **Health check**: http://localhost:5555/health
- **API endpoint**: http://localhost:5555/api/earnings

## 🚀 Rýchly start po reštarte Cursor

### 1. Spustiť webovú aplikáciu:

```powershell
cd D:\Projects\EarningsTable\modules\web
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
$env:FINNHUB_TOKEN = "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
$env:POLYGON_API_KEY = "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
npm start
```

### 2. Spustiť Prisma Studio:

```powershell
cd D:\Projects\EarningsTable\modules\database
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
npx prisma studio --port 5556
```

### 3. Spustiť cron úlohy:

```powershell
cd D:\Projects\EarningsTable\modules\cron
$env:FINNHUB_TOKEN = "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
$env:POLYGON_API_KEY = "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
npm run run-all
```

## 📊 Štatistiky a monitoring

### Health check:

```bash
curl http://localhost:5555/health
```

### API test:

```bash
curl http://localhost:5555/api/earnings
```

### Prisma Studio:

- **URL**: http://localhost:5556/
- **Tabuľky**: FinhubData, PolygonData, FinalReport

## 🔧 Riešenie problémov

### Environment premenné sa stratia:

- **Príčina**: PowerShell session sa reštartuje
- **Riešenie**: Nastaviť environment premenné znovu

### BigInt serializácia chyba:

- **Príčina**: Webová aplikácia nereštartovaná s opravami
- **Riešenie**: Reštartovať webovú aplikáciu

### Prisma chyby:

- **Príčina**: Chýbajúce DATABASE_URL
- **Riešenie**: Nastaviť `$env:DATABASE_URL`

## 📁 Kľúčové súbory

### Konfigurácia:

- `.env` - Environment premenné
- `modules/database/prisma/schema.prisma` - Databázová schéma
- `modules/shared/src/config.ts` - Spoločná konfigurácia

### Cron úlohy:

- `modules/cron/src/jobs/FinnhubCronJob.ts` - Finnhub cron
- `modules/cron/src/jobs/PolygonCronJob.ts` - Polygon cron
- `modules/cron/src/polygon-fast.ts` - Rýchle Polygon spracovanie
- `modules/cron/src/run-all-with-stats.ts` - Štatistický skript

### Web aplikácia:

- `modules/web/src/web.ts` - Hlavný web server
- `modules/web/src/config.ts` - Web konfigurácia

### Databáza:

- `modules/database/prisma/dev.db` - SQLite databáza
- `modules/database/src/prismaClient.ts` - Prisma klient

## 🎯 Typické úlohy

### Spustiť všetky crony postupne:

1. Reset cron
2. FH cron
3. PG cron
4. Final report

### Skontrolovať stav systému:

1. Health check webovej aplikácie
2. Prisma Studio pre databázu
3. API endpoint test

### Opraviť environment problémy:

1. Nastaviť DATABASE_URL
2. Nastaviť API kľúče
3. Reštartovať služby

## 📞 Kontakt a podpora

- **Projekt**: EarningsTable
- **Architektúra**: Modulárna (database, cron, web, shared)
- **Databáza**: Prisma + SQLite
- **APIs**: Finnhub + Polygon
- **Web**: Express + React-like UI
