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
FINNHUB_TOKEN="YOUR_FINNHUB_TOKEN_HERE"
POLYGON_API_KEY="YOUR_POLYGON_API_KEY_HERE"
CRON_TZ="America/New_York"
PORT=5555
NODE_ENV="development"
```

### API kľúče:

- **Finnhub**: `YOUR_FINNHUB_TOKEN_HERE`
- **Polygon**: `YOUR_POLYGON_API_KEY_HERE`

## 🗄️ Databáza (Prisma + SQLite)

### Lokácia:

- **Súbor**: `modules/database/prisma/dev.db`
- **Schema**: `modules/database/prisma/schema.prisma`

### Tabuľky:

1. **FinhubData** - Earnings dáta z Finnhub API (s logami)
2. **PolygonData** - Market cap dáta z Polygon API
3. **FinalReport** - Kombinované dáta z oboch zdrojov (s logami)
4. **CronStatus** - Stav cron úloh

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

1. **Finnhub Cron** - Načítava earnings dáta a logá (denne o 7:00 NY)
2. **Polygon Cron** - Načítava market cap dáta (každé 4 hodiny)
3. **Final Report** - Generuje kombinované reporty s logami

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

# 6. Vymazať dáta (centralizovaný script)
npx tsx src/clear-db-cron.ts
```

## 🖼️ Logo systém

### Automatické sťahovanie logov

- **Zdroj**: Finnhub API (primárny), Polygon API, Yahoo Finance, Clearbit
- **Kvalita**: 256x256px, WebP, 95% kvalita, úplne transparentné pozadie
- **Ukladanie**: `modules/web/public/logos/` + databáza
- **Dokumentácia**: [modules/docs/LOGOS.md](modules/docs/LOGOS.md)

### Prečo sú logá krajšie:

- **Vyššia kvalita**: 95% namiesto 80%
- **Maximum effort**: 6 namiesto 4
- **Úplne transparentné pozadie**: Žiadne biele pásy ani hranice
- **Čisté okraje**: Fit 'inside' bez padding-u
- **Konzistentná veľkosť**: 256x256 pre všetky logá
- **Lepšie zdroje**: Finnhub poskytuje oficiálne logá

## 📈 Pre-market ceny a zmeny

### Automatické doťahovanie aktuálnych cien

- **Zdroj**: Polygon API (pre-market, after-hours, live obchodovanie)
- **Priorita**: Pre-market → Live → After-hours → Minute → Day → PrevDay
- **Zmeny**: Automatický výpočet percentuálnych zmien vs. včerajšia cena
- **Aktualizácia**: Každé 4 hodiny cez Polygon cron job

### Ako funguje výber cien:

1. **Pre-market ceny** - ak sú dostupné (napr. AXP: 319.5 vs 323.12 = -1.12%)
2. **Live obchodovanie** - ak je aktívne (napr. TFC: 40.9 vs 41.09 = -0.46%)
3. **After-hours** - ak je dostupné
4. **Minute ceny** - posledné minútové dáta
5. **Dnešné ceny** - ak sú dostupné
6. **Včerajšie ceny** - fallback (bez zmeny = 0%)

### Prečo nie všetky akcie majú zmeny:

- **Aktívne obchodovanie**: TFC, SLB, HBAN, IMG, CLPS, AXP (majú pre-market/live ceny)
- **Neaktívne obchodovanie**: STT, FITB (používajú včerajšie ceny, zmena = 0%)
- **Dostupnosť dát**: Polygon API vracia len dostupné ceny v reálnom čase

### Príklady aktuálnych zmien:

- **AXP**: 319.5 (-1.12%) - pre-market obchodovanie
- **TFC**: 40.9 (-0.46%) - pre-market obchodovanie
- **SLB**: 32.75 (-0.52%) - pre-market obchodovanie
- **STT**: 112.95 (0%) - žiadne aktívne obchodovanie

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

## 🚀 Produkčná migrácia

Pre kompletnú produkčnú migráciu pozrite si:

- **[PRODUCTION_MIGRATION_GUIDE.md](PRODUCTION_MIGRATION_GUIDE.md)** - Kompletný guide pre produkčnú migráciu
- **Deployment script**: `./deploy-production.sh`
- **PM2 konfigurácia**: `ecosystem.config.js`
- **Produkčný server**: `simple-server.js`

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

### Percentuálne zmeny sa nezobrazujú:

- **Príčina**: `change=0` sa považovalo za `falsy` hodnotu
- **Riešenie**: Opravené v `DatabaseManager.ts` - `change !== null && change !== undefined`
- **Test**: Spustiť Polygon cron job a skontrolovať API response

### Neexistujúce pole v databáze:

- **Príčina**: `marketCapFetchedAt` pole neexistovalo v schéme
- **Riešenie**: Odstránené z `priceService.ts`
- **Test**: Spustiť Polygon cron job bez chýb

### AXP nemá percentuálnu zmenu:

- **Príčina**: Polygon API nevracia pre-market ceny pre AXP
- **Riešenie**: Normálne správanie - nie všetky akcie majú aktívne obchodovanie
- **Test**: Skontrolovať Polygon API response pre AXP vs. TFC

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
- `modules/cron/src/core/priceService.ts` - Pre-market ceny a zmeny
- `modules/cron/src/core/DatabaseManager.ts` - Kopírovanie zmien do FinalReport

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

### Testovať pre-market ceny:

1. Vymazať dáta z tabuliek
2. Spustiť Finnhub cron (earnings + logá)
3. Spustiť Polygon cron (pre-market ceny + zmeny)
4. Skontrolovať API response pre percentuálne zmeny

### Debugovať percentuálne zmeny:

1. Skontrolovať PolygonData tabuľku v Prisma Studio
2. Porovnať `price` vs `previousClose` vs `change`
3. Skontrolovať `priceSource` (pre-market, live, prevDay)
4. Testovať Polygon API response pre konkrétne symboly

## 📞 Kontakt a podpora

- **Projekt**: EarningsTable
- **Architektúra**: Modulárna (database, cron, web, shared)
- **Databáza**: Prisma + SQLite
- **APIs**: Finnhub + Polygon
- **Web**: Express + React-like UI
