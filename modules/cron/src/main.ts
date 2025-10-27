import cron from 'node-cron';
import { db } from './core/DatabaseManager.js';
import { runFinnhubJob } from './jobs/finnhub.js';
import { runPolygonJob } from './jobs/polygon.js';
import { DailyCycleManager } from './daily-cycle-manager.js';
import { prisma } from '../../shared/src/prismaClient.js';

const TZ = 'America/New_York'; // správne pre 7:00 NY (zohľadní DST)

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

async function startAllCronJobs(once: boolean) {
  console.log('🚀 Starting one-big-cron pipeline...');
  
  if (!once) {
    const PIPELINE_CRON = "*/5 6-20 * * 1-5";
    const isValid = cron.validate(PIPELINE_CRON);
    if (!isValid) { console.error(`❌ Invalid cron expression: ${PIPELINE_CRON}`); }

    cron.schedule(PIPELINE_CRON, async () => {
      const tickAt = isoNY();
      console.log(`⏱️ [CRON] tick @ ${tickAt} (NY)`);
      await runPipeline('cron');
    }, { timezone: TZ });
    console.log(`✅ Pipeline scheduled @ ${PIPELINE_CRON} (NY, Mon–Fri) valid=${isValid}`);
    console.log('✅ All cron jobs started successfully');

    // Warm-up (iba ak sme v okne)
    function inWindowNY(h: number, dow: number) { return dow>=1 && dow<=5 && h>=6 && h<=20; }
    const _nowNY = new Date(new Date().toLocaleString('en-US', { timeZone: TZ }));
    if (inWindowNY(_nowNY.getHours(), _nowNY.getDay())) {
      console.log('⚡ Warm-up: running pipeline immediately (inside window)');
      runPipeline('warmup').catch(e => console.error('Warm-up failed:', e));
    } else {
      console.log('🕰️ Warm-up skipped (outside window)');
    }

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
    console.log('✅ Daily clear job scheduled @ 03:00 NY (Mon-Fri)');

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
