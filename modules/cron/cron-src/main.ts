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
        // Start only Finnhub cron job
        const finnhubDate = args.find(arg => arg.startsWith('--date='))?.split('=')[1];
        const finnhubForce = args.includes('--force');
        await startFinnhubCron(once, { date: finnhubDate, force: finnhubForce });
        break;

      case 'start-polygon':
        // Start only Polygon cron job
        await startPolygonCron(once);
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
    // 1) Finnhub (inkrementálne UPSERT)
    await runFinnhubJob();
    // 2) Polygon hneď po Finnhub
    await runPolygonJob();
    const ms = Date.now() - t0;
    console.log(`✅ Pipeline done in ${ms}ms`);
  } catch (e) {
    console.error("❌ Pipeline failed:", e);
  } finally {
    __pipelineRunning = false;
  }
}

async function startAllCronJobs(once: boolean) {
  console.log('🚀 Starting one-big-cron pipeline...');
  
  // Start Finnhub cron
  if (!once) {

    // === ONE BIG CRON: Finnhub -> Polygon ===
    const PIPELINE_CRON = "*/5 6-20 * * 1-5"; // každých 5 min, 06:00–20:00 NY, Mon–Fri
    cron.schedule(PIPELINE_CRON, async () => {
      await runPipeline("cron");
    }, { timezone: TZ });
    console.log(`✅ Pipeline scheduled @ ${PIPELINE_CRON} (NY, Mon–Fri)`);
    console.log('✅ All cron jobs started successfully');

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
}

async function startFinnhubCron(once: boolean, options: { date?: string; force?: boolean } = {}) {
  console.log('🚀 Starting Finnhub cron job...');
  
  // 1) CRON definícia
  const task = cron.schedule('0 7 * * 1-5', async () => {
    console.log('🕖 [CRON] Finnhub job start');
    try {
      await runFinnhubJob();
      console.log('✅ [CRON] Finnhub job finished');
    } catch (e) {
      console.error('❌ [CRON] Finnhub job error:', e);
    }
  }, { scheduled: !once, timezone: TZ });

  console.log(`✅ Finnhub cron ${once ? '(once)' : '(scheduled @ 07:00 NY)'} started.`);

  if (once) {
    console.log('🔄 Running Finnhub job once...');
    await runFinnhubJob(options);
    console.log('✅ Finnhub job completed');
    // pri --once *normálne* ukončíme (graceful)
    await db.disconnect().catch(() => {});
    return;
  }

  if (process.argv.includes('start-finnhub')) {
    console.log('Press Ctrl+C to stop.');
    // Keep-alive (bez hackov so stdin)
    await new Promise<void>(() => {}); // nikdy nerezolvni -> udrží event loop
  }
}

async function startPolygonCron(once: boolean) {
  console.log('🚀 Starting Polygon cron job...');
  
  // 1) CRON definícia
  const task = cron.schedule('*/5 9-17 * * 1-5', async () => {
    console.log('🕖 [CRON] Polygon job start');
    try {
      await runPolygonJob();
      console.log('✅ [CRON] Polygon job finished');
    } catch (e) {
      console.error('❌ [CRON] Polygon job error:', e);
    }
  }, { scheduled: !once, timezone: TZ });

  console.log(`✅ Polygon cron ${once ? '(once)' : '(scheduled @ */5 9-17 NY (Mon–Fri))'} started.`);

  if (once) {
    console.log('🔄 Running Polygon job once...');
    await runPolygonJob();
    console.log('✅ Polygon job completed');
    // pri --once *normálne* ukončíme (graceful)
    await db.disconnect().catch(() => {});
    return;
  }

  if (process.argv.includes('start-polygon')) {
    console.log('Press Ctrl+C to stop.');
    // Keep-alive (bez hackov so stdin)
    await new Promise<void>(() => {}); // nikdy nerezolvni -> udrží event loop
  }
}

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
