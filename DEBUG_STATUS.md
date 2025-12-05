# Status Debugovania - EarningsTable API

## Aktuálny problém
Frontend zobrazuje symboly s `null` marketCap na začiatku, aj keď v databáze sú symboly s validnými marketCap hodnotami. API vracia len 43 záznamov namiesto očakávaných 151.

## Zistenia

### 1. Dve rôzne databázy
- **Server databáza**: `/srv/EarningsTable/modules/database/prisma/prod.db`
  - 43 záznamov v FinalReport
  - Všetky záznamy majú `marketCap: null`
  - Túto databázu používa PM2 server (`earnings-table`)
  
- **Iná databáza**: `/var/www/earnings-table/modules/database/prisma/prod.db`
  - 151 záznamov v FinalReport
  - 110 záznamov má validné `marketCap` hodnoty
  - Táto databáza sa nepoužíva serverom

### 2. Polygon job problém
- Polygon job zlyhával kvôli chýbajúcim environment variables (`FINNHUB_TOKEN`)
- Riešenie: Načítanie env premenných pred spustením jobu:
  ```bash
  export $(cat /srv/EarningsTable/.env | xargs)
  npm run cron start-polygon --once
  ```

### 3. Debug logging problém
- Pridali sme debug logging do `simple-server.js` (riadky 441-469)
- Debug výstup sa **nezobrazuje** v PM2 logoch
- Vidíme len: `📊 Fetching FinalReport data...`, `[DB] Connection successful`, `✅ Found 43 records`
- Chýbajú: `🔍 DEBUG: Got data from DB`, `📊 Total records`, `📊 First 5 symbols after sorting`

### 4. Posledné zmeny
- Zmenili sme `console.error` na `process.stderr.write` pre debug výstup (obchádza PM2 buffering)
- Commit sa nepodaril dokončiť (timeout) - **potrebuje dokončiť**

## Čo treba urobiť zajtra

### 1. Dokončiť commit a push
```bash
git add simple-server.js
git commit -m "Use process.stderr.write for debug output to bypass PM2 buffering"
git push origin main
```

### 2. Na SSH serveri
```bash
cd /srv/EarningsTable
git pull origin main
pm2 restart earnings-table
sleep 2
curl http://localhost:5555/api/final-report > /dev/null
sleep 1
pm2 logs earnings-table --lines 100 --nostream | tail -50
```

### 3. Skontrolovať, či sa debug výstup zobrazuje
- Ak áno: vidíme, koľko záznamov má marketCap a ako sú zoradené
- Ak nie: možno je problém s tým, že sa kód medzi `findMany()` a `✅ Found` nevykonáva

### 4. Ak sa debug výstup zobrazí
- Zistiť, prečo má FinalReport len 43 záznamov s `marketCap: null`
- Spustiť Polygon job s načítanými env premennými
- Spustiť `generateFinalReport` aby sa aktualizoval FinalReport s marketCap dátami

### 5. Ak sa debug výstup nezobrazí
- Skontrolovať, či sa kód vôbec vykonáva (pridať try-catch okolo problematickej časti)
- Skontrolovať, či nie je problém s asynchrónnym kódom
- Možno použiť iný spôsob logovania (súbor, alebo PM2 log file priamo)

## Súbory, ktoré sme upravili
- `simple-server.js` - pridaný debug logging (riadky 441-469)
- `modules/shared/src/synthetic-tests.ts` - opravený parsing API response

## Dôležité príkazy

### Na SSH serveri
```bash
# Načítanie env premenných
export $(cat /srv/EarningsTable/.env | xargs)

# Spustenie Polygon jobu
cd /srv/EarningsTable
npm run cron start-polygon --once

# Regenerovanie FinalReport
DATABASE_URL="file:/srv/EarningsTable/modules/database/prisma/prod.db" npx tsx -e "
const { generateFinalReport } = require('./modules/shared/src/generateFinalReport.js');
generateFinalReport().then(() => console.log('Done')).catch(console.error);
"

# Kontrola počtu záznamov s marketCap
DATABASE_URL="file:/srv/EarningsTable/modules/database/prisma/prod.db" npx tsx -e "
const PrismaClient = require('./modules/shared/node_modules/@prisma/client').PrismaClient;
const prisma = new PrismaClient({ datasources: { db: { url: 'file:/srv/EarningsTable/modules/database/prisma/prod.db' } } });
(async () => {
  const finalWithCap = await prisma.finalReport.count({ where: { marketCap: { not: null } } });
  const finalTotal = await prisma.finalReport.count();
  console.log('FinalReport: ' + finalTotal + ' total, ' + finalWithCap + ' s marketCap');
  await prisma.\$disconnect();
})();
"
```

## Poznámky
- Server používa databázu `/srv/EarningsTable/modules/database/prisma/prod.db`
- PM2 process: `earnings-table` (id: 2)
- API endpoint: `http://localhost:5555/api/final-report`
- Debug výstup by sa mal zobrazovať v PM2 logoch po použití `process.stderr.write`

