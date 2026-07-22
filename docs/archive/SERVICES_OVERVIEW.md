# 🎯 Services Overview - EarningsTable

## 🌐 Webová aplikácia

- **Lokácia**: `modules/web/`
- **URL**: http://localhost:5555/
- **Spustenie**: `.\start-web.ps1`
- **Funkcie**: Dashboard, API endpointy, BigInt serializácia

## 🗄️ Prisma Studio

- **Lokácia**: `modules/database/`
- **URL**: http://localhost:5556/
- **Spustenie**: `.\start-prisma.ps1`
- **Funkcie**: Databázový prehliadač, tabuľky, dáta

## 🕐 Cron úlohy

- **Lokácia**: `modules/cron/`
- **Spustenie**: `.\run-crons.ps1`
- **Úlohy**:
  1. **Finnhub Cron** - Earnings dáta (denne 7:00 NY)
  2. **Polygon Cron** - Market cap dáta (každé 4 hodiny)
  3. **Final Report** - Kombinované dáta

## 🔑 Environment premenné

- **Súbor**: `.env`
- **Setup**: `.\env-vars.ps1`
- **API kľúče**: Finnhub + Polygon

## 📊 Databáza

- **Súbor**: `modules/database/prisma/dev.db`
- **Tabuľky**: FinhubData, PolygonData, FinalReport
- **Prisma**: Schema + migrácie

## 🚀 Rýchle príkazy

### Spustiť všetko:

```powershell
.\start-all.ps1
```

### Testovanie:

```bash
curl http://localhost:5555/health
curl http://localhost:5555/api/earnings
```

### Environment setup:

```powershell
.\env-vars.ps1
```

## 📁 Kľúčové súbory

### Dokumentácia:

- `QUICK_START.md` - Rýchle inštrukcie
- `SETUP_GUIDE.md` - Kompletná dokumentácia
- `SERVICES_OVERVIEW.md` - Tento súbor

### Skripty:

- `start-all.ps1` - Spustiť všetko
- `start-web.ps1` - Len web
- `start-prisma.ps1` - Len Prisma Studio
- `run-crons.ps1` - Len cron úlohy
- `env-vars.ps1` - Environment setup

### Konfigurácia:

- `.env` - Environment premenné
- `modules/database/prisma/schema.prisma` - Databázová schéma

## 🎯 Typické scenáre

### 1. Po reštarte Cursor:

```powershell
.\start-all.ps1
```

### 2. Spustiť len cron úlohy:

```powershell
.\run-crons.ps1
```

### 3. Skontrolovať stav:

```bash
curl http://localhost:5555/health
```

### 4. Pozrieť databázu:

- Otvoriť http://localhost:5556/

## 🔧 Riešenie problémov

### Environment premenné sa stratia:

```powershell
.\env-vars.ps1
```

### Webová aplikácia nefunguje:

```powershell
.\start-web.ps1
```

### Prisma Studio nefunguje:

```powershell
.\start-prisma.ps1
```

### Cron úlohy nefungujú:

```powershell
.\run-crons.ps1
```
