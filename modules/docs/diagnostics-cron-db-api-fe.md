# 🔎 Diagnostika: crony bežia nepravidelne / FE sa neaktualizuje

Chcem overiť, že cron beží pravidelne, zapisuje čerstvé dáta a FE (produkcia) ich naozaj ťahá. Odpovedaj postupne podľa sekcií. Ku každej otázke vždy prilož:

- konkrétnu odpoveď,
- ak je to o kóde, prilož aj výrez kódu,
- ak je to o behu, prilož aj log/command output.

---

## 1) Orchestrácia a plánovanie (PM2, node-cron, crontab)

**Otázky:**

1. Používame iba node-cron v procese pod PM2, alebo aj systémový `crontab`? Nie sú spustené duplicitné plánovače?
2. Je v produkcii nastavené `CRON_TZ=America/New_York` (alebo iné)? Je táto TZ naozaj použitá node-cronom?
3. Bežia vždy tieto dva procesy?
   - `earnings-cron` (pipeline)
   - `earnings-table` (API server)
4. Nie je cron proces reštartovaný „v strede jobu“ (watch & restart), čo by prerušilo pipeline?

**Vyžiadané ukážky:**

- Súbor `ecosystem.config.js` – celý blok `apps`, vrátane `env`/`env_production`.
- Súbor so schedulerom (napr. `modules/cron/src/cron-scheduler.ts`) – plán (`cron.schedule(...)`) a `CRON_TZ`.
- Výstup príkazov:

```bash
pm2 status
pm2 logs earnings-cron --lines 200
pm2 logs earnings-table --lines 200
crontab -l || true
```

- Ak je `watch: true`, ukáž nastavenie (či nerestartuje uprostred jobu).

---

## 2) Mutex/lock & single-run garancie

**Otázky:**

1. Ako je implementovaný mutex/lock (aby súbežne nebežali 2 instancie pipeline)?
2. Uvoľňuje sa lock aj pri výnimke (finally)? Neostáva „zamknuté“ po páde?
3. Má pipeline aj „stuck-guard“ (TTL/timeout na lock)?

**Vyžiadané ukážky kódu:**

- `modules/cron/src/utils/mutex.ts` (alebo podobné) – celé.
- Miesto, kde sa mutex používa v hlavnom behu (napr. `withMutex(runPipelineOnce)`).

---

## 3) Datumy, hranice dňa a idempotencia

**Otázky:**

1. Ako určujete „dnešný“ deň pre report (NY polnoc vs UTC)?
2. Sú `reportDate`/`snapshotDate` Date objekty (nie ms čísla / stringy)?
3. Je upsert idempotentný (unikátny composite key napr. `symbol + reportDate`), aby sme nemali duplicitné dni?
4. Máme normalize funkciu na dátumy pre všetky create/update cesty?

**Vyžiadané ukážky:**

- `modules/cron/src/core/DatabaseManager.ts` – funkcie, kde sa vytvára/upsertuje `FinalReport`/„daily snapshot“.
- Normalizačná funkcia (napr. `normalizeFinalReportDates`).
- Prisma schema pre tabuľky s dátumami:
  - `modules/database/prisma/schema.prisma` – modely obsahujúce `reportDate`, `snapshotDate`, indexy/unique.

---

## 4) Pipeline kroky a error handling

**Otázky:**

1. Ak jeden zdroj (napr. Finnhub) zlyhá, pipeline skončí celá, alebo pokračuje s tým, čo je dostupné (a označí partial)?
2. Logujete začiatok/koniec každej fázy + počty spracovaných symbolov a celkový čas?
3. Sú chyby „caught & logged“ so stackom a bez `process.exit()`?
4. Má pipeline health metriku (napr. `cron_status` tabuľku s timestampami posledného behu a trvaním)?

**Vyžiadané ukážky:**

- Hlavný „runner“ (napr. `runPipelineOnce`), vrátane `try/catch/finally`.
- Funkcie: fetch Finnhub, fetch Polygon, výpočet, zápis, „final report assemble“.
- Výstup posledných 500 riadkov logu `earnings-cron` s časom spustení (grep „Running pipeline“/„Completed“).

---

## 5) DB write → API read (FinalReport)

**Otázky:**

1. Ktorá funkcia presne generuje „FinalReport“ dáta pre API?
2. Sú do `FinalReport`/výstupnej tabuľky prenesené všetky polia, ktoré FE očakáva (vrátane `logoUrl`)?
3. Existuje „backfill“ zo súborov v `/logos/*.webp|svg` → `logoUrl` v DB? (Ak áno, kedy beží?)

**Vyžiadané ukážky:**

- Kód generovania „FinalReport“ (funkcia, kde sa skladá dataset).
- Prisma model/y pre `FinalReport` (indexy, unique, typy).
- Krátky SQL / výstup:

```sql
-- koľko záznamov má dnešný reportDate?
SELECT reportDate, COUNT(*) FROM FinalReport GROUP BY reportDate ORDER BY reportDate DESC LIMIT 5;
-- koľko majú NULL logoUrl?
SELECT COUNT(*) FROM FinalReport WHERE reportDate = <DNES> AND (logoUrl IS NULL OR logoUrl='');
```

- Ak existuje backfill skript na logá, prilož jeho obsah.

---

## 6) API vrstva (server.ts, /api/final-report)

**Otázky:**

1. API endpointy:
   - `GET /api/final-report`
   - `GET /api/final-report/stats`
   - `POST /api/final-report/refresh`
   Sú stabilné, s cashovaním/bez? Nepoužíva sa niekde zastaraný súbor?
2. Je tam žiadne `process.exit()` ani „hard kill“ v runtime?
3. Sú odpovede označené anti-cache hlavičkami, ak chceme vždy čerstvé dáta?

**Vyžiadané ukážky:**

- `server.ts` a `api-routes.ts` – definície vyššie uvedených rout.
- Výstup:

```bash
curl -sS https://www.earningstable.com/api/final-report | head -c 500
curl -sS https://www.earningstable.com/api/final-report/stats
```

- Nginx (ak je pred tým) – snippet lokácie pre `/api/` (cache headers / proxy cache?).

---

## 7) Cache & CDN (Nginx/Cloudflare/FE cache)

**Otázky:**

1. Je pred API reverse proxy (Nginx, Cloudflare)? Neblokuje/zacachuje starý JSON?
2. Ak FE používa SWR/React Query – aká je `staleTime`/`cacheTime`/`refetchOnWindowFocus`?
3. Je tam `ETag`/`Cache-Control: no-store` pre API?

**Vyžiadané ukážky:**

- Nginx config pre doménu (aspoň lokácie `/` a `/api/`).
- FE fetch layer (hook alebo miesto, kde sa fetchuje `final-report`), vrátane SWR/React Query nastavení.

---

## 8) FE produkcia ≠ lokál (env, build, deploy)

**Otázky:**

1. Má FE v produkcii správnu `API_BASE_URL`? Nie je v builde „upečená“ vývojová URL?
2. Kedy prebieha deploy FE a je po ňom aj invalidácia CDN?
3. Ak FE zobrazuje „placeholder“ iniciály, je to preto, že `logoUrl` je prázdne v API, alebo FE string manipulačná logika padá?

**Vyžiadané ukážky:**

- `.env.production` (bez tajomstiev, stačí mená premenných a url).
- FE kód komponentu, ktorý zobrazuje logo (funkcia `getLogoSrc(symbol)`) a fallback.

---

## 9) Monitoring, alarmy a rýchle sanity checks

**Otázky:**

1. Máme health endpoint `/api/health` – je napojený na ping (UptimeRobot/Healthchecks)?
2. Je niekde metrika `lastSuccessfulRunAt` + `durationMs` pre cron?
3. Máme alert pri:
   - 0 nových záznamoch za pracovný deň,
   - lock držaný > X minút,
   - API vráti prázdny `final-report`.

**Vyžiadané ukážky:**

- Kód `/api/health`.
- Ak existuje `cron_status` tabuľka, ukáž model + posledné riadky.

---

## 10) „Smoke“ skripty, ktoré môžem pustiť hneď

**Otázky/požiadavky:**

1. Pridaj krátky Node skript `scripts/smoke-cron.ts`, ktorý:
   - vytlačí `process.env.CRON_TZ` a aktuálny čas v UTC a v `America/New_York`,
   - zavolá malú DB query: počet riadkov `FinalReport` pre dnešný `reportDate`,
   - vypíše posledný riadok z `cron_status` (ak existuje).

**Vyžiadaná ukážka kódu:**

```ts
// scripts/smoke-cron.ts
import 'dotenv/config';
import { PrismaClient } from '@prisma/client';

async function main() {
  console.log('CRON_TZ=', process.env.CRON_TZ);
  console.log('Now UTC:', new Date().toISOString());
  const nowNY = new Date().toLocaleString('en-US', { timeZone: 'America/New_York' });
  console.log('Now NY :', nowNY);

  const prisma = new PrismaClient();
  const todayNY = new Date(new Date().toLocaleString('en-US', { timeZone: 'America/New_York' }));
  const yyyy = todayNY.getFullYear();
  const mm = String(todayNY.getMonth() + 1).padStart(2, '0');
  const dd = String(todayNY.getDate()).padStart(2, '0');
  const reportDate = new Date(`${yyyy}-${mm}-${dd}T00:00:00.000Z`);

  const count = await prisma.finalReport.count({ where: { reportDate }});
  console.log('FinalReport today rows:', count);

  try {
    const status = await prisma.cronStatus.findFirst({ orderBy: { updatedAt: 'desc' }});
    console.log('Last cron status:', status);
  } catch (e) {
    console.log('No cron_status table or error:', (e as Error).message);
  }
  await prisma.$disconnect();
}

main().catch(e => { console.error(e); process.exit(1); });
```

- Pridaj npm script: `"smoke:cron": "tsx scripts/smoke-cron.ts"` a ukáž výstup z produkcie:

```bash
node -v && npm -v
npm run smoke:cron
```

---

## 11) Rýchla hypotéza k tvojmu problému (Cursor, potvrď/vyvrať)

- Cron beží, ale:
  - dátum/časová zóna → `reportDate` sa zapíše zle (string/ms), a „dnešný“ set nenájde FE API,
  - alebo pipeline skončí bez „FinalReport assemble“ pri parciálnej chybe (ticho),
  - alebo Nginx/Cloudflare vracia starý JSON (cache header/ETag),
  - alebo FE má dlhé `staleTime` a nefetchuje po deployi/po polnoci NY,
  - alebo chýba `logoUrl` backfill → FE padá do iniciálov.

**Cursor, pre každú hypotézu urob minimálny test** (log, SQL count, `curl`) a prilož dôkaz.

---

## 12) Akčná „fix“ sada (nevynechaj)

1. V `cron-scheduler.ts` doplň logy: START/END s timestampom a duration.
2. Všetky `reportDate`/`snapshotDate` spracúvaj ako Date a normalizuj na NY polnoc.
3. Unikátna constraint `symbol+reportDate` na „final rows“.
4. `withMutex(..., ttlMs=15*60*1000)` + `finally { release }`.
5. `/api/final-report`: `Cache-Control: no-store` (kým neladíme cache).
6. FE fetch: dočasne `staleTime: 0` + `refetchOnWindowFocus: true` (na test).
7. Nginx: vypni/probierni proxy cache pre `/api/` a po deploy urob purge.
8. Backfill `logoUrl` z `/logos/*.webp|svg` (ak nie je v DB).
9. Healthcheck + UptimeRobot na `/api/health` + alarm pri 0 riadkoch pre dnešný deň.


