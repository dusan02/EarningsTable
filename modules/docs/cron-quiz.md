### Cron System – Answer Key (with code references)

Short, corrected answers with inline code citations to your source files.

Related guides:

- See `modules/docs/logo-troubleshooting.md` for the production logos incident runbook.
- See `modules/docs/diagnostics-cron-db-api-fe.md` for an end-to-end diagnostics checklist.

### 1) Hlavný súbor a príkazy

- Ktorý súbor riadi všetky crony?

  - `modules/cron/src/main.ts`.

- start-finnhub / start-polygon
  - Deprecated; presmerované na jednotnú pipeline.

```30:40:modules/cron/src/main.ts
      case 'start-finnhub':
        // Legacy: now runs unified pipeline
        console.log('⚠️ start-finnhub is deprecated, using unified pipeline');
        await startAllCronJobs(once);
        break;

      case 'start-polygon':
        // Legacy: now runs unified pipeline
        console.log('⚠️ start-polygon is deprecated, using unified pipeline');
        await startAllCronJobs(once);
        break;
```

- One-shot vs scheduled režim
  - Rozpoznanie `--once` a rozvetvenie na scheduled/once.

```9:17:modules/cron/src/main.ts
async function bootstrap() {
  const args = process.argv.slice(2);
  const command = args[0];
  const once = args.includes('--once') || process.env.RUN_ONCE === 'true';

  console.log('🚀 Starting Cron Manager...');
  console.log(`📅 Timezone: ${TZ}`);
  console.log(`🔄 Mode: ${once ? 'once' : 'scheduled'}`);
```

```174:181:modules/cron/src/main.ts
  if (once) {
    console.log('🔄 Running all jobs once...');
    await runPipeline("once");
    console.log('✅ All jobs completed');
    await db.disconnect().catch(() => {});
    return;
  }
```

- Zabránenie duplicitnému behu (mutex)
  - Guard `__pipelineRunning` skipne ďalší tick.

```103:109:modules/cron/src/main.ts
let __pipelineRunning = false;
async function runPipeline(label = "scheduled") {
  if (__pipelineRunning) {
    console.log("⏭️  Pipeline skip (previous run still in progress)");
    return;
  }
  __pipelineRunning = true;
```

- Poradie v `runPipeline()`
  - Finnhub → (ak sú zmeny) Polygon → FinalReport. Logos môže byť podľa jobu preskočený (vo „fast“ variantoch), preto sa niekedy logá nezapíšu.

```111:120:modules/cron/src/main.ts
  console.log(`🚦 Pipeline start [${label}]`);
  try {
    const { symbolsChanged } = await runFinnhubJob();
    if (!symbolsChanged || symbolsChanged.length === 0) {
      console.log('🛌 No Finnhub changes → skipping Polygon');
    } else {
      console.log(`➡️  Running Polygon for ${symbolsChanged.length} changed symbols`);
      await runPolygonJob(symbolsChanged);
    }
    console.log(`✅ Pipeline done in ${Date.now() - t0}ms`);
```

- Timezone a log ticku
  - Plánovanie s `America/New_York` a log: `⏱️ [CRON] Pipeline tick @ …`.

```128:141:modules/cron/src/main.ts
async function startAllCronJobs(once: boolean) {
  console.log('🚀 Starting one-big-cron pipeline...');

  if (!once) {
    const PIPELINE_CRON = "*/5 6-20 * * 1-5";
    const isValid = cron.validate(PIPELINE_CRON);
    if (!isValid) { console.error(`❌ Invalid cron expression: ${PIPELINE_CRON}`); }

    cron.schedule(PIPELINE_CRON, async () => {
      const nowNY = new Date(new Date().toLocaleString('en-US', { timeZone: TZ }));
      console.log(`⏱️ [CRON] Pipeline tick @ ${nowNY.toISOString()} (${TZ})`);
      await runPipeline('cron');
    }, { timezone: TZ });
```

- Warm-up inside window

```144:153:modules/cron/src/main.ts
    // Warm-up (iba ak sme v okne)
    function inWindowNY(h: number, dow: number) { return dow>=1 && dow<=5 && h>=6 && h<=20; }
    const _nowNY = new Date(new Date().toLocaleString('en-US', { timeZone: TZ }));
    if (inWindowNY(_nowNY.getHours(), _nowNY.getDay())) {
      console.log('⚡ Warm-up: running pipeline immediately (inside window)');
      runPipeline('warmup').catch(e => console.error('Warm-up failed:', e));
    } else {
      console.log('🕰️ Warm-up skipped (outside window)');
    }
```

- Graceful shutdown (SIGINT/SIGTERM)

```185:201:modules/cron/src/main.ts
// Graceful shutdown
process.on('SIGINT', async () => {
  console.log('↩️ SIGINT: shutting down…');
  try {
    await db.disconnect();
  } catch {}
  return;
});

process.on('SIGTERM', async () => {
  console.log('↩️ SIGTERM: shutting down…');
  try {
    await db.disconnect();
  } catch {}
  return;
});
```

- Deprecated `cron-scheduler.ts`

```1:3:modules/cron/src/cron-scheduler.ts
console.warn('[cron-scheduler] Deprecated. Use src/main.ts. No schedules armed.');
console.warn('↪️ Try: npx tsx src/main.ts start --once  or  npm run start:once');
export {}; // no-op
```

### 2) Frekvencia spúšťania

- Pipeline harmonogram a pásmo

  - `*/5 6-20 * * 1-5` @ `America/New_York` (viď vyššie 128–141).

- Dlhší beh než interval

  - Ďalší tick sa preskočí vďaka `__pipelineRunning` (viď 103–109).

- Zmena intervalu na 10 min

  - Zmeň výraz na `*/10 6-20 * * 1-5` v `startAllCronJobs`.

- Víkendový job

  - Pridaj nový `cron.schedule('0 10 * * 6', ...)` s `timezone: TZ`.

- Log ticku
  - Hľadaj `[CRON] Pipeline tick` (viď 136–139).

### 3) Denný reset (03:00 NY)

- Plánovanie, guard a logy

```154:168:modules/cron/src/main.ts
    // Daily clear job (03:00 AM weekdays) – jedna, konzistentná metla
    cron.schedule('0 3 * * 1-5', async () => {
      try {
        console.log('🧹 Daily clear starting @ 03:00 NY');
        process.env.ALLOW_CLEAR = 'true';
        await db.clearAllTables();
        console.log('✅ Daily clear done');
      } catch (e) {
        console.error('❌ Daily clear failed', e);
      } finally {
        delete process.env.ALLOW_CLEAR;
      }
    }, { timezone: 'America/New_York' });
```

- Čo presne sa maže

```619:628:modules/cron/src/core/DatabaseManager.ts
  async clearAllTables(): Promise<void> {
    console.log('🛑 Clearing all database tables...');

    await prisma.finalReport.deleteMany();
    await prisma.polygonData.deleteMany();
    await prisma.finhubData.deleteMany();
    await prisma.cronStatus.deleteMany();

    console.log('✅ All tables cleared successfully');
  }
```

- Generovanie FinalReport (odľahčený filter podľa marketCap)

```396:418:modules/cron/src/core/DatabaseManager.ts
  async generateFinalReport(): Promise<void> {
    console.log('🔄 Generating FinalReport from FinhubData and PolygonData...');

    const finhubSymbols = await prisma.finhubData.findMany({
      select: { symbol: true },
      distinct: ['symbol'],
    });

    const polygonSymbols = await prisma.polygonData.findMany({
      select: { symbol: true },
      where: {
        // Relaxed condition: accept records with marketCap present even if live price is missing
        marketCap: { not: null }
      },
    });
```

Tipy na verifikáciu:

- Po resete: `/api/final-report` → `count: 0`, potom po one-shot → `count > 0`.
- Logy: hľadaj `🧹 Daily clear starting @ 03:00 NY`, `⏱️ [CRON] Pipeline tick @ …`, `✅ FinalReport snapshot stored: N symbols`.
