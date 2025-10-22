import cron from 'node-cron';
import { db } from './core/DatabaseManager.js';
import { runFinnhubJob } from './jobs/finnhub.js';
import { runPolygonJob } from './jobs/polygon.js';
import { DailyCycleManager } from './daily-cycle-manager.js';

const TZ = 'America/New_York'; // správne pre 7:00 NY (zohľadní DST)

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
    if (!symbolsChanged || symbolsChanged.length === 0) {
      console.log('🛌 No Finnhub changes → skipping Polygon');
    } else {
      console.log(`➡️  Running Polygon for ${symbolsChanged.length} changed symbols`);
      await runPolygonJob(symbolsChanged);
    }
    console.log(`✅ Pipeline done in ${Date.now() - t0}ms`);
  } catch (e) {
    console.error('❌ Pipeline failed:', e);
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
      const nowNY = new Date(new Date().toLocaleString('en-US', { timeZone: TZ }));
      console.log(`⏱️ [CRON] Pipeline tick @ ${nowNY.toISOString()} (${TZ})`);
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
