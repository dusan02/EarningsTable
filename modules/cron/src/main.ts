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
        console.log('  ✅ Pipeline: Finnhub → Polygon (every 5min @ America/New_York, 24/7 except 03:00)');
        console.log('  ✅ Daily Clear: 03:00 NY (Mon-Fri)');
        console.log('  ✅ Boot Guard: Automatic recovery after restart');
        console.log('  ✅ Environment: Validated');
        break;

      case 'list':
        console.log('📋 Available Cron Jobs:');
        console.log('  - Daily Cycle Manager (03:00 clear, 03:05 start, every 5min until 02:30)');
        console.log('  - Pipeline: Finnhub → Polygon every 5min (24/7 except 03:00)');
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
  📊 Every 5min NY - Pipeline 24/7 (Mon-Fri, except 03:00)
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
    performanceMonitor.recordSnapshot({
      pipelineDuration: metrics.duration,
      finnhubDuration: metrics.finnhubDuration,
      polygonDuration: metrics.polygonDuration,
      logoDuration: metrics.logoDuration,
      dbDuration: metrics.dbDuration,
      totalRecords: metrics.totalRecords,
      symbolsChanged: metrics.symbolsChanged
    });
    
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
 * Boot guard funkcie:
 * - scheduleBootGuardAfterClear: Ak je NY čas medzi 03:00–03:29:59, naplánuje runPipeline na 03:30 NY
 * - checkAndRunDailyResetIfNeeded: Ak sa proces reštartuje po 03:00 NY, spustí denný reset manuálne
 */
async function checkAndRunDailyResetIfNeeded() {
  try {
    const now = new Date();
    const nowNY = new Date(now.toLocaleString('en-US', { timeZone: TZ }));
    const nyHour = nowNY.getHours();
    const nyMinute = nowNY.getMinutes();
    
    // Ak je medzi 03:00-03:05 NY, skontroluj či už bol reset
    if (nyHour === 3 && nyMinute < 5) {
      // Skontroluj dátum posledného resetu (cez počet záznamov v tabuľkách)
      const today = new Date(nowNY);
      today.setHours(0, 0, 0, 0);
      
      // Ak sú v databáze záznamy z predošlého dňa, reset nebol spustený
      const oldRecords = await prisma.finhubData.findFirst({
        where: {
          reportDate: { lt: today }
        }
      });
      
      if (oldRecords) {
        console.log('🛡️ Boot guard: Detected old data, running missed daily reset');
        try {
          process.env.ALLOW_CLEAR = 'true';
          await db.clearAllTables();
          console.log('✅ Boot guard: Daily reset completed');
          enterQuietWindow();
        } catch (e) {
          console.error('❌ Boot guard: Daily reset failed', e);
        } finally {
          delete process.env.ALLOW_CLEAR;
        }
      } else {
        console.log('🛡️ Boot guard: No old data found, daily reset already done');
      }
    }
  } catch (e) {
    console.error('❌ checkAndRunDailyResetIfNeeded error:', e);
  }
}

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
    // Unified cron: každých 5 minút počas celého dňa (okrem 03:00 pre reset)
    // Cron expression: */5 * * * 1-5 = každých 5 min počas celého dňa, Mon-Fri
    const UNIFIED_CRON = '*/5 * * * 1-5';
    const UNIFIED_VALID = cron.validate(UNIFIED_CRON);
    if (!UNIFIED_VALID) console.error(`❌ Invalid cron expression: ${UNIFIED_CRON}`);
    cron.schedule(UNIFIED_CRON, async () => {
      const tickAt = isoNY();
      const nowNY = new Date(new Date().toLocaleString('en-US', { timeZone: TZ }));
      const hour = nowNY.getHours();
      const minute = nowNY.getMinutes();
      
      // Preskočiť 03:00 (kedy beží daily clear)
      if (hour === 3 && minute === 0) {
        console.log(`⏭️  [CRON] skipping tick @ ${tickAt} (NY) - daily clear time`);
        return;
      }
      
      console.log(`⏱️ [CRON] tick @ ${tickAt} (NY)`);
      if (isInQuietWindow()) return;
      await runPipeline('unified-slot');
    }, { timezone: TZ });
    console.log(`✅ Unified pipeline scheduled @ ${UNIFIED_CRON} (NY, Mon–Fri, každých 5 min okrem 03:00) valid=${UNIFIED_VALID}`);

    // Daily clear job (03:00 AM weekdays) – reset databázy
    const DAILY_CLEAR_CRON = '0 3 * * 1-5';
    const DAILY_CLEAR_VALID = cron.validate(DAILY_CLEAR_CRON);
    if (!DAILY_CLEAR_VALID) {
      console.error(`❌ Invalid cron expression for daily clear: ${DAILY_CLEAR_CRON}`);
    } else {
      const scheduledTask = cron.schedule(DAILY_CLEAR_CRON, async () => {
        try {
          const nowNY = new Date(new Date().toLocaleString('en-US', { timeZone: TZ }));
          console.log(`🧹 Daily clear starting @ 03:00 NY (actual NY time: ${nowNY.toLocaleString()})`);
          process.env.ALLOW_CLEAR = 'true';
          await db.clearAllTables();
          console.log('✅ Daily clear done');
          enterQuietWindow(); // 5-minútová pauza po cleare
        } catch (e) {
          console.error('❌ Daily clear failed', e);
        } finally {
          delete process.env.ALLOW_CLEAR;
        }
      }, { timezone: TZ, scheduled: true });
      
      if (scheduledTask) {
        console.log(`✅ Daily clear job scheduled @ ${DAILY_CLEAR_CRON} (03:00 NY, Mon-Fri) valid=${DAILY_CLEAR_VALID}`);
      } else {
        console.error('❌ Failed to schedule daily clear job');
      }
    }

    console.log('✅ All cron jobs started successfully');

    // Start synthetic tests job
    await syntheticTestsJob.start();

    // 🛡️  Jednorazový guard – ak by unified cron nebehol (reštart okolo 03:30 a pod.)
    // plánuje / spustí runPipeline v okne po daily cleare
    scheduleBootGuardAfterClear();
    
    // 🛡️ Boot guard pre daily clear - ak sa proces reštartuje po 03:00, spusti reset
    checkAndRunDailyResetIfNeeded();

    console.log('Press Ctrl+C to stop all cron jobs');
    // Keep-alive - proces zostane nažive pomocou while loop
    while (true) {
      await new Promise(resolve => setTimeout(resolve, 60000)); // Wait 1 minute
    }
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
  console.log('�� Graceful shutdown initiated');
  console.log('↩️ SIGINT: shutting down…');
  await db.disconnect().catch(() => {});
  process.exit(0);
});

process.on('SIGTERM', async () => {
  console.log('🛑 Graceful shutdown initiated');
  console.log('↩️ SIGTERM: shutting down…');
  await db.disconnect().catch(() => {});
  process.exit(0);
});

// Start the application
bootstrap().catch((error) => {
  console.error('❌ Failed to start:', error);
  process.exit(1);
});
