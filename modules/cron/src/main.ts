import cron from 'node-cron';
import { db } from './core/DatabaseManager.js';
import { runFinnhubJob } from './jobs/finnhub.js';
import { runPolygonJob } from './jobs/polygon.js';
import { DailyCycleManager } from './daily-cycle-manager.js';
import { prisma } from '../../shared/src/prismaClient.js';
import { validateConfig } from '../../shared/src/config.js';
import { TimezoneManager } from '../../shared/src/timezone.js';
import { IdempotencyManager } from '../../shared/src/idempotency.js';
import { optimizedPipeline } from './optimized-pipeline.js';
import { performanceMonitor } from './performance-monitor.js';
import { syntheticTestsJob } from './jobs/synthetic-tests.js';

const TZ = process.env.CRON_TZ || 'America/New_York'; // NY timezone je povinná pre konzistentné tick-y

function nowNY() {
  return TimezoneManager.nowNY();
}

function isoNY(d = nowNY()) {
  return TimezoneManager.getNYDateString(d);
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

  // Validate environment variables
  try {
    validateConfig();
    console.log('✅ Environment variables validated');
  } catch (error) {
    console.error('❌ Environment validation failed:', error);
    return;
  }

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
        console.log('  ✅ Pipeline: Finnhub → Polygon (03:05-03:55, 04:00-20:00 every 5min @ America/New_York)');
        console.log('  ✅ Daily Clear: 03:00 NY (Mon-Fri)');
        console.log('  ✅ Boot Guard: Automatic recovery after restart');
        console.log('  ✅ Environment: Validated');
        break;

      case 'list':
        console.log('📋 Available Cron Jobs:');
        console.log('  - Daily Cycle Manager (03:00 clear, 03:05 start, every 5min until 02:30)');
        console.log('  - Pipeline (03:05-03:55, 04:00-20:00): Finnhub → Polygon');
        console.log('  - Daily clear 03:00 NY (Mon–Fri)');
        console.log('  - Boot guard recovery system');
        break;

             case 'performance-report':
               console.log(performanceMonitor.generateReport());
               break;
             case 'synthetic-tests':
               await syntheticTestsJob.runOnce();
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

Schedule:
  🧹 03:00 NY - Daily clear (Mon-Fri)
  📊 03:05-03:55 NY - Early slot every 5min (Mon-Fri)
  📊 04:00-20:00 NY - Day slot every 5min (Mon-Fri)
  🛡️ Boot guard - Automatic recovery after restart

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
const PIPELINE_TIMEOUT_MS = 15 * 60 * 1000; // 15 minutes timeout
const QUIET_WINDOW_MS = 5 * 60 * 1000; // 5 minutes after daily clear
let __quietWindowUntil = 0;

function enterQuietWindow() {
  __quietWindowUntil = Date.now() + QUIET_WINDOW_MS;
  console.log(`🕊️  Entering quiet window for ${Math.round(QUIET_WINDOW_MS/1000)}s`);
}

function isInQuietWindow(): boolean {
  const inWindow = Date.now() < __quietWindowUntil;
  if (inWindow) {
    const remaining = Math.max(0, __quietWindowUntil - Date.now());
    console.log(`🕊️  Quiet window active (${Math.ceil(remaining/1000)}s left) — skipping tick`);
  }
  return inWindow;
}

async function runPipeline(label = "scheduled") {
  if (__pipelineRunning) {
    console.log("⏭️  Pipeline skip (previous run still in progress)");
    return;
  }
  __pipelineRunning = true;
  
  // Timeout guard to prevent stuck pipeline
  const timeoutId = setTimeout(() => {
    console.log("⚠️ Pipeline timeout — resetting flag");
    __pipelineRunning = false;
  }, PIPELINE_TIMEOUT_MS);
  
  try {
    // Use optimized pipeline for better performance
    const metrics = await optimizedPipeline.runPipeline(label);
    
    // Record performance metrics
    performanceMonitor.recordSnapshot(metrics);
    
    // Save performance data to database
    await performanceMonitor.saveToDatabase();
    
  } catch (e) {
    console.error('❌ Pipeline failed:', e);
    try { await db.updateCronStatus('pipeline', 'error', 0, (e as any)?.message || String(e)); } catch {}
  } finally {
    clearTimeout(timeoutId);
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

    const inWindow_03_00_to_03_05 = (nyHour === 3 && (nyMinute < 5 || (nyMinute === 5 && nySecond === 0)));
    const inWindow_03_05_to_03_10 = (nyHour === 3 && nyMinute >= 5 && nyMinute < 10);

    if (inWindow_03_00_to_03_05) {
      // Cieľ: dnes 03:05:00 NY
      const targetNY = new Date(nowNY);
      targetNY.setHours(3, 5, 0, 0);

      // Vypočítaj delay v ms v NY čase
      const delayMs = targetNY.getTime() - nowNY.getTime();
      if (delayMs > 0) {
        console.log(`🛡️  Boot guard: scheduled one-shot run @ 03:05 NY in ~${Math.round(delayMs/1000)}s`);
        setTimeout(async () => {
          try {
            console.log('🛡️  Boot guard firing @ 03:05 NY → runPipeline("boot-guard-03:05")');
            await runPipeline('boot-guard-03:05');
          } catch (e) {
            console.error('❌ Boot guard run failed:', e);
          }
        }, delayMs);
      }
      return;
    }

    if (inWindow_03_05_to_03_10) {
      // Reštart tesne po 03:05 – spusti hneď
      console.log('🛡️  Boot guard: within 03:05–03:10 NY → running immediately');
      runPipeline('boot-guard-03:05-late').catch(err =>
        console.error('❌ Boot guard late run failed:', err)
      );
      return;
    }

    // Mimo okna – nič nerob, crony sa postarajú
    console.log('🛡️  Boot guard: outside 03:00–03:10 NY window → no-op');
  } catch (e) {
    console.error('❌ scheduleBootGuardAfterClear error:', e);
  }
}

async function startAllCronJobs(once: boolean) {
  console.log('🚀 Starting one-big-cron pipeline...');
  
  if (!once) {
    // ✅ 5-min „prázdne“ okno po cleare (03:00–03:05 NY)
    // 1) Early slot: 03:05–03:55 každých 5 min
    const EARLY_CRON = '5,10,15,20,25,30,35,40,45,50,55 3 * * 1-5';
    const EARLY_VALID = cron.validate(EARLY_CRON);
    if (!EARLY_VALID) console.error(`❌ Invalid cron expression: ${EARLY_CRON}`);
    cron.schedule(EARLY_CRON, async () => {
      const tickAt = isoNY();
      console.log(`⏱️ [CRON] early tick @ ${tickAt} (NY)`);
      if (isInQuietWindow()) return;
      await runPipeline('early-slot');
    }, { timezone: TZ });
    console.log(`✅ Early pipeline scheduled @ ${EARLY_CRON} (NY, Mon–Fri) valid=${EARLY_VALID}`);

    // 2) Deň: 04:00–20:00 každých 5 min
    const DAY_CRON = '*/5 4-23 * * 1-5';
    const DAY_VALID = cron.validate(DAY_CRON);
    if (!DAY_VALID) console.error(`❌ Invalid cron expression: ${DAY_CRON}`);
    cron.schedule(DAY_CRON, async () => {
      const tickAt = isoNY();
      console.log(`⏱️ [CRON] day tick @ ${tickAt} (NY)`);
      if (isInQuietWindow()) return;
      await runPipeline('day-slot');
    }, { timezone: TZ });
    console.log(`✅ Day pipeline scheduled @ ${DAY_CRON} (NY, Mon–Fri) valid=${DAY_VALID}`);
    // 2b) Večer/Noč: 00:00–02:55 každých 5 min - pokrýva večer a noc
    const EVENING_CRON = '*/5 0-2 * * 1-5';
    const EVENING_VALID = cron.validate(EVENING_CRON);
    if (!EVENING_VALID) console.error(`❌ Invalid cron expression: ${EVENING_CRON}`);
    cron.schedule(EVENING_CRON, async () => {
      const tickAt = isoNY();
      console.log(`⏱️ [CRON] evening tick @ ${tickAt} (NY)`);
      if (isInQuietWindow()) return;
      await runPipeline('evening-slot');
    }, { timezone: TZ });
    console.log(`✅ Evening pipeline scheduled @ ${EVENING_CRON} (NY, Mon–Fri, 00:00–02:55) valid=${EVENING_VALID}`);
    // 2b) Večer/Noč: 00:00–02:55 každých 5 min - pokrýva večer a noc
    const EVENING_CRON = '*/5 0-2 * * 1-5';
    const EVENING_VALID = cron.validate(EVENING_CRON);
    if (!EVENING_VALID) console.error(`❌ Invalid cron expression: ${EVENING_CRON}`);
    cron.schedule(EVENING_CRON, async () => {
      const tickAt = isoNY();
      console.log(`⏱️ [CRON] evening tick @ ${tickAt} (NY)`);
      if (isInQuietWindow()) return;
      await runPipeline('evening-slot');
    }, { timezone: TZ });
    console.log(`✅ Evening pipeline scheduled @ ${EVENING_CRON} (NY, Mon–Fri, 00:00–02:55) valid=${EVENING_VALID}`);
    // 2b) Večer/Noč: 00:00–02:55 každých 5 min - pokrýva večer a noc
    const EVENING_CRON = '*/5 0-2 * * 1-5';
    const EVENING_VALID = cron.validate(EVENING_CRON);
    if (!EVENING_VALID) console.error(`❌ Invalid cron expression: ${EVENING_CRON}`);
    cron.schedule(EVENING_CRON, async () => {
      const tickAt = isoNY();
      console.log(`⏱️ [CRON] evening tick @ ${tickAt} (NY)`);
      if (isInQuietWindow()) return;
      await runPipeline('evening-slot');
    }, { timezone: TZ });
    console.log(`✅ Evening pipeline scheduled @ ${EVENING_CRON} (NY, Mon–Fri, 00:00–02:55) valid=${EVENING_VALID}`);

    // Daily clear job (03:00 AM weekdays) – jedna, konzistentná metla
    cron.schedule('0 3 * * 1-5', async () => {
      try {
        console.log('🧹 Daily clear starting @ 03:00 NY');
        process.env.ALLOW_CLEAR = 'true';
        await db.clearAllTables();
        console.log('✅ Daily clear done');
        enterQuietWindow();
      } catch (e) {
        console.error('❌ Daily clear failed', e);
      } finally {
        delete process.env.ALLOW_CLEAR;
      }
    }, { timezone: TZ });
    console.log('✅ Daily clear job scheduled @ 03:00 NY (Mon-Fri)');

    console.log('✅ All cron jobs started successfully');

    // Start synthetic tests job
    await syntheticTestsJob.start();

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
