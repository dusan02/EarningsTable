import cron from 'node-cron';
import { db } from './core/DatabaseManager.js';
import { runFinnhubJob } from './jobs/finnhub.js';
import { runPolygonJob } from './jobs/polygon.js';
import { DailyCycleManager } from './daily-cycle-manager.js';
import { prisma } from '../../shared/src/prismaClient.js';

const TZ = process.env.CRON_TZ || 'America/New_York'; // NY timezone je povinná pre konzistentné tick-y

function nowNY() {
  return new Date(new Date().toLocaleString('en-US', { timeZone: TZ }));
}

function isoNY(d = nowNY()) {
  const pad = (n: number) => String(n).padStart(2, '0');
  const y = d.getUTCFullYear();
  const m = pad(d.getUTCMonth() + 1);
  const dd = pad(d.getUTCDate());
  const hh = pad(d.getUTCHours());
  const mm = pad(d.getUTCMinutes());
  const ss = pad(d.getUTCSeconds());
  return `${y}-${m}-${dd}T${hh}:${mm}:${ss}.000Z`;
}

function truthyEnv(name: string): boolean {
  const v = (process.env[name] || '').toLowerCase();
  return v === '1' || v === 'true' || v === 'yes';
}

function getNYMidnight(): Date {
  const nowNY = new Date(new Date().toLocaleString('en-US', { timeZone: TZ }));
  const yyyy = nowNY.getFullYear();
  const mm = String(nowNY.getMonth() + 1).padStart(2, '0');
  const dd = String(nowNY.getDate()).padStart(2, '0');
  return new Date(`${yyyy}-${mm}-${dd}T00:00:00.000Z`);
}

async function getTodaySymbolsFromFinnhub(): Promise<string[]> {
  const reportDate = getNYMidnight();
  const rows = await prisma.finhubData.findMany({
    where: { reportDate },
    select: { symbol: true },
    distinct: ['symbol'],
  });
  return rows.map(r => r.symbol);
}

async function bootstrap() {
  const args = process.argv.slice(2);
  const command = args[0];
  const once = args.includes('--once') || process.env.RUN_ONCE === 'true';

  console.log('🚀 Starting Cron Manager...');
  console.log(`📅 Timezone: ${TZ}`);
  console.log(`🔄 Mode: ${once ? 'once' : 'scheduled'}`);

  try {
    switch (command) {
      case 'start':
        // Start all cron jobs
        await startAllCronJobs(once);
        break;

      case 'daily-cycle':
        // Start daily cycle manager (03:00 clear, 03:05 start, every 5min until 02:30)
        await startDailyCycle();
        break;

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

      case 'status':
        console.log('📊 Cron Jobs Status:');
        console.log('  ✅ Pipeline: Finnhub → Polygon (*/5 6-20 * * 1-5 @ America/New_York)');
        console.log('  ✅ Polygon Market Cap Data (*/5 9-17 * * 1-5 @ America/New_York)');
        break;

      case 'list':
        console.log('📋 Available Cron Jobs:');
        console.log('  - Daily Cycle Manager (03:00 clear, 03:05 start, every 5min until 02:30)');
        console.log('  - Pipeline (*/5 6-20): Finnhub → Polygon');
        console.log('  - Daily clear 03:00 NY (Mon–Fri)');
        break;

      case 'help':
      default:
        console.log(`
🕐 Cron Manager

Usage: npm run cron [command] [options]

Commands:
  daily-cycle        Start daily cycle manager (03:00 clear, 03:05 start, every 5min until 02:30)
  start              Start all cron jobs
  start-finnhub      Start Finnhub cron job only
  start-polygon      Start Polygon cron job only
  status             Show status of all cron jobs
  list               List available cron jobs
  help               Show this help

Options:
  --once             Run once and exit (for testing/debugging)
  --date=YYYY-MM-DD  Fetch data for specific date (Finnhub only)
  --force            Force overwrite existing data (Finnhub only)

Examples:
  npm run cron daily-cycle                        # Start daily cycle manager
  npm run cron start                              # Start all cron jobs (scheduled)
  npm run cron start-finnhub                      # Start only Finnhub cron (scheduled)
  npm run cron start-finnhub --once               # Run Finnhub job once and exit
  npm run cron start-finnhub --once --date=2025-10-15  # Fetch specific date
  npm run cron start-finnhub --once --force       # Force overwrite existing data
  npm run cron status                             # Check status
        `);
        break;
    }
  } catch (error) {
    console.error('❌ Bootstrap failed:', error);
    return;
  }
}

async function startDailyCycle() {
  console.log('🚀 Starting Daily Cycle Manager...');
  const manager = new DailyCycleManager();
  await manager.start();
  
  // Keep-alive
  await new Promise<void>(() => {}); // nikdy nerezolvni -> udrží event loop
}


let __pipelineRunning = false;
async function runPipeline(label = "scheduled") {
  if (__pipelineRunning) {
    console.log("⏭️  Pipeline skip (previous run still in progress)");
    return;
  }
  __pipelineRunning = true;
  const t0 = Date.now();
  console.log(`🚦 Pipeline start [${label}]`);
  try {
    const { symbolsChanged } = await runFinnhubJob();

    const RUN_FULL = truthyEnv('RUN_FULL_POLYGON');
    let todaySymbolsCount = 0;
    if (RUN_FULL || !symbolsChanged || symbolsChanged.length < 10) {
      // Lazy-evaluate only when needed
      const todaySymbols = await getTodaySymbolsFromFinnhub().catch(() => []);
      todaySymbolsCount = todaySymbols.length;
      const SMALL_DELTA = (symbolsChanged?.length || 0) < 10 && todaySymbolsCount >= 50;

      if (RUN_FULL || SMALL_DELTA) {
        console.log(`➡️  Running Polygon in FULL mode (${RUN_FULL ? 'env RUN_FULL_POLYGON' : 'small-delta heuristic'}) — today=${todaySymbolsCount}, delta=${symbolsChanged?.length || 0}`);
        await runPolygonJob(todaySymbols);
        console.log('✅ FULL Polygon refresh done');
        await db.updateCronStatus('pipeline', 'success', todaySymbolsCount, `full:${symbolsChanged?.length || 0}`);
      } else if (symbolsChanged && symbolsChanged.length > 0) {
        console.log(`➡️  Running Polygon (delta) for ${symbolsChanged.length} symbols`);
        await runPolygonJob(symbolsChanged);
        await db.updateCronStatus('pipeline', 'success', symbolsChanged.length, 'delta');
      } else {
        console.log('🛌 No Finnhub changes → skipping Polygon');
        await db.updateCronStatus('pipeline', 'success', 0, 'delta:0');
      }
    } else {
      console.log(`➡️  Running Polygon (delta) for ${symbolsChanged.length} symbols`);
      await runPolygonJob(symbolsChanged);
      await db.updateCronStatus('pipeline', 'success', symbolsChanged.length, 'delta');
    }
    console.log(`✅ Pipeline done in ${Date.now() - t0}ms`);
  } catch (e) {
    console.error('❌ Pipeline failed:', e);
    try { await db.updateCronStatus('pipeline', 'error', 0, (e as any)?.message || String(e)); } catch {}
  } finally {
    __pipelineRunning = false;
  }
}

/**
 * Jednorazový „boot guard“ po daily cleare:
 * - Ak je aktuálny NY čas medzi 03:00–03:29:59, naplánuje runPipeline presne na 03:30 NY (setTimeout).
 * - Ak je medzi 03:30–03:35, spustí pipeline ihneď (záchytný scenár po reštarte).
 * - Inak nerobí nič – spoľahneme sa na pravidelné crony.
 */
function scheduleBootGuardAfterClear() {
  try {
    const now = new Date();
    // Získaj "teraz" v NY
    const nowNY = new Date(now.toLocaleString('en-US', { timeZone: TZ }));
    const nyYear = nowNY.getFullYear();
    const nyMonth = nowNY.getMonth();
    const nyDate = nowNY.getDate();
    const nyHour = nowNY.getHours();
    const nyMinute = nowNY.getMinutes();
    const nySecond = nowNY.getSeconds();

    const inWindow_03_00_to_03_30 = (nyHour === 3 && (nyMinute < 30 || (nyMinute === 30 && nySecond === 0)));
    const inWindow_03_30_to_03_35 = (nyHour === 3 && nyMinute >= 30 && nyMinute < 35);

    if (inWindow_03_00_to_03_30) {
      // Cieľ: dnes 03:30:00 NY
      const targetNY = new Date(nowNY);
      targetNY.setHours(3, 30, 0, 0);

      // Vypočítaj delay v ms v NY čase
      const delayMs = targetNY.getTime() - nowNY.getTime();
      if (delayMs > 0) {
        console.log(`🛡️  Boot guard: scheduled one-shot run @ 03:30 NY in ~${Math.round(delayMs/1000)}s`);
        setTimeout(async () => {
          try {
            console.log('🛡️  Boot guard firing @ 03:30 NY → runPipeline("boot-guard-03:30")');
            await runPipeline('boot-guard-03:30');
          } catch (e) {
            console.error('❌ Boot guard run failed:', e);
          }
        }, delayMs);
      }
      return;
    }

    if (inWindow_03_30_to_03_35) {
      // Reštart tesne po 03:30 – spusti hneď
      console.log('🛡️  Boot guard: within 03:30–03:35 NY → running immediately');
      runPipeline('boot-guard-03:30-late').catch(err =>
        console.error('❌ Boot guard late run failed:', err)
      );
      return;
    }

    // Mimo okna – nič nerob, crony sa postarajú
    console.log('🛡️  Boot guard: outside 03:00–03:35 NY window → no-op');
  } catch (e) {
    console.error('❌ scheduleBootGuardAfterClear error:', e);
  }
}

async function startAllCronJobs(once: boolean) {
  console.log('🚀 Starting one-big-cron pipeline...');
  
  if (!once) {
    // ✅ 30-min „prázdne“ okno po cleare (03:00–03:30 NY)
    // 1) Early slot: 03:30–03:55 každých 5 min
    const EARLY_CRON = '30,35,40,45,50,55 3 * * 1-5';
    const EARLY_VALID = cron.validate(EARLY_CRON);
    if (!EARLY_VALID) console.error(`❌ Invalid cron expression: ${EARLY_CRON}`);
    cron.schedule(EARLY_CRON, async () => {
      const tickAt = isoNY();
      console.log(`⏱️ [CRON] early tick @ ${tickAt} (NY)`);
      await runPipeline('early-slot');
    }, { timezone: TZ });
    console.log(`✅ Early pipeline scheduled @ ${EARLY_CRON} (NY, Mon–Fri) valid=${EARLY_VALID}`);

    // 2) Deň: 04:00–20:00 každých 5 min
    const DAY_CRON = '*/5 4-20 * * 1-5';
    const DAY_VALID = cron.validate(DAY_CRON);
    if (!DAY_VALID) console.error(`❌ Invalid cron expression: ${DAY_CRON}`);
    cron.schedule(DAY_CRON, async () => {
      const tickAt = isoNY();
      console.log(`⏱️ [CRON] day tick @ ${tickAt} (NY)`);
      await runPipeline('day-slot');
    }, { timezone: TZ });
    console.log(`✅ Day pipeline scheduled @ ${DAY_CRON} (NY, Mon–Fri) valid=${DAY_VALID}`);

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
    }, { timezone: TZ });
    console.log('✅ Daily clear job scheduled @ 03:00 NY (Mon-Fri)');

    console.log('✅ All cron jobs started successfully');

    // 🛡️  Jednorazový guard – ak by early slot nebehol (reštart okolo 03:30 a pod.)
    // plánuje / spustí runPipeline v okne po daily cleare
    scheduleBootGuardAfterClear();

    console.log('Press Ctrl+C to stop all cron jobs');
    // Keep-alive (bez hackov so stdin)
    await new Promise<void>(() => {}); // nikdy nerezolvni -> udrží event loop
  }

  if (once) {
    console.log('🔄 Running all jobs once...');
    await runPipeline("once");
    console.log('✅ All jobs completed');
    await db.disconnect().catch(() => {});
    return;
  }
}

// Old separate cron functions removed - now using unified smart pipeline

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

// Safety for unhandled errors
process.on('unhandledRejection', (r) => console.error('unhandledRejection:', r));
process.on('uncaughtException', (e) => { 
  console.error('uncaughtException:', e); 
  return; 
});

bootstrap().catch((e) => {
  console.error('Bootstrap failed:', e);
  return;
});
