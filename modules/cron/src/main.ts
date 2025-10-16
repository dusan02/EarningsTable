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
        console.log('  ✅ Finnhub Earnings Data (0 7 * * * @ America/New_York)');
        console.log('  ✅ Polygon Market Cap Data (0 */4 * * * @ America/New_York)');
        break;

      case 'list':
        console.log('📋 Available Cron Jobs:');
        console.log('  - Daily Cycle Manager (03:00 clear, 03:05 start, every 5min until 02:30)');
        console.log('  - Finnhub Earnings Data (daily @ 07:00 NY)');
        console.log('  - Polygon Market Cap Data (every 4 hours)');
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
    process.exit(1);
  }
}

async function startDailyCycle() {
  console.log('🚀 Starting Daily Cycle Manager...');
  const manager = new DailyCycleManager();
  await manager.start();
  
  // Keep-alive
  await new Promise<void>(() => {}); // nikdy nerezolvni -> udrží event loop
}

async function startAllCronJobs(once: boolean) {
  console.log('🚀 Starting all cron jobs...');
  
  // Start Finnhub cron
  await startFinnhubCron(once);
  
  // Start Polygon cron
  await startPolygonCron(once);
  
  if (!once) {
    console.log('✅ All cron jobs started successfully');
    console.log('Press Ctrl+C to stop all cron jobs');
    // Keep-alive (bez hackov so stdin)
    await new Promise<void>(() => {}); // nikdy nerezolvni -> udrží event loop
  }
}

async function startFinnhubCron(once: boolean, options: { date?: string; force?: boolean } = {}) {
  console.log('🚀 Starting Finnhub cron job...');
  
  // 1) CRON definícia
  const task = cron.schedule('0 7 * * *', async () => {
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
    process.exit(0);
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
  const task = cron.schedule('0 */4 * * *', async () => {
    console.log('🕖 [CRON] Polygon job start');
    try {
      await runPolygonJob();
      console.log('✅ [CRON] Polygon job finished');
    } catch (e) {
      console.error('❌ [CRON] Polygon job error:', e);
    }
  }, { scheduled: !once, timezone: TZ });

  console.log(`✅ Polygon cron ${once ? '(once)' : '(scheduled every 4 hours)'} started.`);

  if (once) {
    console.log('🔄 Running Polygon job once...');
    await runPolygonJob();
    console.log('✅ Polygon job completed');
    // pri --once *normálne* ukončíme (graceful)
    await db.disconnect().catch(() => {});
    process.exit(0);
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
  process.exit(0);
});

process.on('SIGTERM', async () => {
  console.log('↩️ SIGTERM: shutting down…');
  try { 
    await db.disconnect(); 
  } catch {} 
  process.exit(0);
});

// Safety for unhandled errors
process.on('unhandledRejection', (r) => console.error('unhandledRejection:', r));
process.on('uncaughtException', (e) => { 
  console.error('uncaughtException:', e); 
  process.exit(1); 
});

bootstrap().catch((e) => {
  console.error('Bootstrap failed:', e);
  process.exit(1);
});
