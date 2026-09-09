# ⚡ Quick Start - EarningsTable

## 🚀 Po reštarte Cursor - Rýchly start

### 1. Spustiť všetky služby naraz:

```powershell
.\start-all.ps1
```

### 2. Alebo spustiť jednotlivo:

#### Webová aplikácia:

```powershell
.\start-web.ps1
```

**URL**: http://localhost:5555/

#### Prisma Studio:

```powershell
.\start-prisma.ps1
```

**URL**: http://localhost:5556/

#### Cron úlohy:

```powershell
.\run-crons.ps1
```

## 🔑 Environment premenné

```powershell
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
$env:FINNHUB_TOKEN = "<FINNHUB_TOKEN_REDACTED>"
$env:POLYGON_API_KEY = "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
```

## 📊 Testovanie

### Health check:

```bash
curl http://localhost:5555/health
```

### API test:

```bash
curl http://localhost:5555/api/earnings
```

## 🗄️ Databáza

- **Súbor**: `modules/database/prisma/dev.db`
- **Tabuľky**: FinhubData, PolygonData, FinalReport
- **Prisma Studio**: http://localhost:5556/

## 🕐 Cron úlohy

- **Lokácia**: `modules/cron/`
- **Finnhub**: Earnings dáta (denne 7:00 NY)
- **Polygon**: Market cap dáta (každé 4 hodiny)
- **Final Report**: Kombinované dáta

## 🌐 Webová aplikácia

- **Lokácia**: `modules/web/`
- **URL**: http://localhost:5555/
- **API**: http://localhost:5555/api/earnings

## 📁 Kľúčové súbory

- `SETUP_GUIDE.md` - Kompletná dokumentácia
- `start-all.ps1` - Spustiť všetko
- `start-web.ps1` - Len web
- `start-prisma.ps1` - Len Prisma Studio
- `run-crons.ps1` - Len cron úlohy
- `.env` - Environment premenné

## 🎯 Typické úlohy

1. **Spustiť systém**: `.\start-all.ps1`
2. **Spustiť crony**: `.\run-crons.ps1`
3. **Skontrolovať zdravie**: `curl http://localhost:5555/health`
4. **Pozrieť databázu**: http://localhost:5556/

## 🚀 Produkčná migrácia

Pre produkčnú migráciu pozrite si:

- **[PRODUCTION_MIGRATION_GUIDE.md](../PRODUCTION_MIGRATION_GUIDE.md)** - Kompletný guide
- **Deployment**: `./deploy-production.sh`
- **PM2**: `ecosystem.config.js`
- **Server**: `simple-server.js`
