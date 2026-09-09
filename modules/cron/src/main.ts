import cron from 'node-cron';
import { db } from './core/DatabaseManager.js';
import { prisma } from '../../shared/src/prismaClient.js';
import { validateConfig } from '../../shared/src/config.js';
import { TimezoneManager } from '../../shared/src/timezone.js';
import { optimizedPipeline } from './optimized-pipeline.js';
import { performanceMonitor } from './performance-monitor.js';
import { syntheticTestsJob } from './jobs/synthetic-tests.js';

const TZ = process.env.CRON_TZ || 'America/New_York';

function nowNY() {
  return TimezoneManager.nowNY();
}

function isoNY(d = nowNY()) {
  return TimezoneManager.getNYDateString(d);
}

function applyCliOverrides(args: string[]) {
  const dateArg = args.find((arg) => arg.startsWith('--date='));
  if (dateArg) {
    const [, value] = dateArg.split('=');
    if (value) {
      process.env.FINNHUB_FORCE_DATE = value;
      console.log(`Finnhub override date set via CLI: ${value}`);
    }
  }

  if (args.includes('--force')) {
    process.env.FINNHUB_FORCE = 'true';
    console.log('Finnhub force mode enabled via CLI');
  }
}

async function bootstrap() {
  const args = process.argv.slice(2);
  applyCliOverrides(args);
  const command = args[0];
  const once = args.includes('--once') || process.env.RUN_ONCE === 'true';

  console.log('Starting Cron Manager...');
  console.log(`Timezone: ${TZ}`);
  console.log(`Mode: ${once ? 'once' : 'scheduled'}`);

  try {
    validateConfig();
    console.log('Environment variables validated');
  } catch (error) {
    console.error('Environment validation failed:', error);
    return;
  }

  try {
    switch (command) {
      case 'start':
        await startAllCronJobs(once);
        break;

      case 'start-finnhub':
        console.log('start-finnhub is deprecated, using unified pipeline');
        await startAllCronJobs(once);
        break;

      case 'start-polygon':
        console.log('start-polygon is deprecated, using unified pipeline');
        await startAllCronJobs(once);
        break;

      case 'status':
        console.log('Cron Jobs Status:');
        console.log('  Pipeline: Finnhub -> Polygon (every 5min @ America/New_York, 24/7 except 03:00)');
        console.log('  Daily Clear: 03:00 NY (every day)');
        console.log('  Boot Guard: Automatic recovery after restart');
        console.log('  Environment: Validated');
        break;

      case 'list':
        console.log('Available Cron Jobs:');
        console.log('  - Pipeline: Finnhub -> Polygon every 5min (24/7 except 03:00)');
        console.log('  - Daily clear 03:00 NY (every day)');
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
Cron Manager

Usage: npm run cron [command] [options]

Commands:
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
  03:00 NY - Daily clear (every day)
  Every 5min NY - Pipeline 24/7 (every day, except 03:00)
  Boot guard - Automatic recovery after restart

Examples:
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
    console.error('Bootstrap failed:', error);
    return;
  }
}


let __pipelineRunning = false;
let __currentPipeline: Promise<void> | null = null;
const PIPELINE_TIMEOUT_MS = 4 * 60 * 1000;
const QUIET_WINDOW_MS = 5 * 60 * 1000;
let __quietWindowUntil = 0;

function enterQuietWindow() {
  __quietWindowUntil = Date.now() + QUIET_WINDOW_MS;
  console.log(`Entering quiet window for ${Math.round(QUIET_WINDOW_MS/1000)}s`);
}

function resetQuietWindow() {
  __quietWindowUntil = 0;
  console.log('Quiet window reset (process restart)');
}

function isInQuietWindow(): boolean {
  if (__quietWindowUntil > 0 && Date.now() >= __quietWindowUntil) {
    __quietWindowUntil = 0;
    console.log('Quiet window expired, reset');
    return false;
  }

  const inWindow = Date.now() < __quietWindowUntil;
  if (inWindow) {
    const remaining = Math.max(0, __quietWindowUntil - Date.now());
    console.log(`Quiet window active (${Math.ceil(remaining/1000)}s left) -- skipping tick`);
  }
  return inWindow;
}

async function runPipeline(label = "scheduled") {
  if (__pipelineRunning) {
    console.log("Pipeline skip (previous run still in progress)");
    return;
  }
  __pipelineRunning = true;

  const startTime = new Date();
  try {
    await db.updateCronStatus('pipeline', 'running', undefined, undefined, startTime);
  } catch (logError) {
    console.error('Failed to log pipeline start:', logError);
  }

  // Soft watchdog: only warn on long runs. We intentionally do NOT reset the
  // flag here, because doing so would allow a second concurrent pipeline to
  // start while the first is still running (data corruption / double API calls).
  const watchdogId = setTimeout(() => {
    console.warn(`Pipeline running longer than ${Math.round(PIPELINE_TIMEOUT_MS / 1000)}s [${label}] -- will keep blocking new ticks until it finishes`);
  }, PIPELINE_TIMEOUT_MS);

  const work = (async () => {
    try {
      const metrics = await optimizedPipeline.runPipeline(label);

      performanceMonitor.recordSnapshot({
        pipelineDuration: metrics.duration,
        finnhubDuration: metrics.finnhubDuration,
        polygonDuration: metrics.polygonDuration,
        logoDuration: metrics.logoDuration,
        dbDuration: metrics.dbDuration,
        totalRecords: metrics.totalRecords,
        symbolsChanged: metrics.symbolsChanged
      });

      await performanceMonitor.saveToDatabase();

      const duration = Date.now() - startTime.getTime();
      try {
        await db.updateCronStatus('pipeline', 'success', metrics.totalRecords, undefined, startTime, duration);
      } catch (logError) {
        console.error('Failed to log pipeline success:', logError);
      }

    } catch (e) {
      console.error('Pipeline failed:', e);
      const duration = Date.now() - startTime.getTime();
      try {
        await db.updateCronStatus('pipeline', 'error', 0, (e as any)?.message || String(e), startTime, duration);
      } catch (logError) {
        console.error('Failed to log pipeline error:', logError);
      }
    } finally {
      clearTimeout(watchdogId);
      __pipelineRunning = false;
      __currentPipeline = null;
    }
  })();

  __currentPipeline = work;
  await work;
}

// Wait for any in-flight pipeline to finish (used by daily clear + shutdown).
async function waitForPipelineIdle(timeoutMs = 90 * 1000): Promise<void> {
  if (!__pipelineRunning || !__currentPipeline) return;
  console.log('Waiting for in-flight pipeline to finish before proceeding...');
  try {
    await Promise.race([
      __currentPipeline,
      new Promise<void>((_, reject) => setTimeout(() => reject(new Error('waitForPipelineIdle timeout')), timeoutMs)),
    ]);
  } catch (e) {
    console.error('waitForPipelineIdle:', e);
  }
}

async function checkAndRunDailyResetIfNeeded() {
  try {
    const now = new Date();
    const nowNY = new Date(now.toLocaleString('en-US', { timeZone: TZ }));
    const nyHour = nowNY.getHours();
    const nyMinute = nowNY.getMinutes();

    if (nyHour === 3 && nyMinute < 30) {
      // Check for very old data (>90 days) that should have been pruned.
      const cutoff = new Date(nowNY.getTime() - 90 * 24 * 60 * 60 * 1000);

      const oldRecords = await prisma.finhubData.findFirst({
        where: {
          reportDate: { lt: cutoff }
        }
      });

      if (oldRecords) {
        console.log('Boot guard: Detected very old data (>90 days), running missed daily prune');
        try {
          process.env.ALLOW_CLEAR = 'true';
          await db.pruneOldRecords(90);
          console.log('Boot guard: Daily prune completed');
          enterQuietWindow();
        } catch (e) {
          console.error('Boot guard: Daily prune failed', e);
        } finally {
          delete process.env.ALLOW_CLEAR;
        }
      } else {
        console.log('Boot guard: No very old data found, daily prune already done');
      }
    }
  } catch (e) {
    console.error('checkAndRunDailyResetIfNeeded error:', e);
  }
}

function scheduleBootGuardAfterClear() {
  try {
    const now = new Date();
    const nowNY = new Date(now.toLocaleString('en-US', { timeZone: TZ }));
    const nyHour = nowNY.getHours();
    const nyMinute = nowNY.getMinutes();
    const nySecond = nowNY.getSeconds();

    const inWindow_03_00_to_03_05 = (nyHour === 3 && (nyMinute < 5 || (nyMinute === 5 && nySecond === 0)));
    const inWindow_03_05_to_03_30 = (nyHour === 3 && nyMinute >= 5 && nyMinute < 30);

    if (inWindow_03_00_to_03_05) {
      const targetNY = new Date(nowNY);
      targetNY.setHours(3, 5, 0, 0);

      const delayMs = targetNY.getTime() - nowNY.getTime();
      if (delayMs > 0) {
        console.log(`Boot guard: scheduled one-shot run @ 03:05 NY in ~${Math.round(delayMs/1000)}s`);
        setTimeout(async () => {
          try {
            console.log('Boot guard firing @ 03:05 NY -> runPipeline("boot-guard-03:05")');
            await runPipeline('boot-guard-03:05');
          } catch (e) {
            console.error('Boot guard run failed:', e);
          }
        }, delayMs);
      }
      return;
    }

    if (inWindow_03_05_to_03_30) {
      console.log('Boot guard: within 03:05-03:30 NY -> running immediately');
      runPipeline('boot-guard-03:05-late').catch(err =>
        console.error('Boot guard late run failed:', err)
      );
      return;
    }

    console.log('Boot guard: outside 03:00-03:30 NY window -> no-op');
  } catch (e) {
    console.error('scheduleBootGuardAfterClear error:', e);
  }
}

async function startAllCronJobs(once: boolean) {
  console.log('Starting one-big-cron pipeline...');

  resetQuietWindow();

  if (!once) {
    const UNIFIED_CRON = '*/5 * * * *';
    const UNIFIED_VALID = cron.validate(UNIFIED_CRON);
    if (!UNIFIED_VALID) console.error(`Invalid cron expression: ${UNIFIED_CRON}`);
    cron.schedule(UNIFIED_CRON, async () => {
      const tickAt = isoNY();
      const nowNY = new Date(new Date().toLocaleString('en-US', { timeZone: TZ }));
      const hour = nowNY.getHours();
      const minute = nowNY.getMinutes();

      if (hour === 3 && minute === 0) {
        console.log(`[CRON] skipping tick @ ${tickAt} (NY) - daily clear time`);
        return;
      }

      console.log(`[CRON] tick @ ${tickAt} (NY)`);
      if (isInQuietWindow()) return;
      await runPipeline('unified-slot');
    }, { timezone: TZ });
    console.log(`Unified pipeline scheduled @ ${UNIFIED_CRON} (NY, 24/7) valid=${UNIFIED_VALID}`);

    const DAILY_CLEAR_CRON = '0 3 * * *';
    const DAILY_CLEAR_VALID = cron.validate(DAILY_CLEAR_CRON);
    if (!DAILY_CLEAR_VALID) {
      console.error(`Invalid cron expression for daily clear: ${DAILY_CLEAR_CRON}`);
    } else {
      const scheduledTask = cron.schedule(DAILY_CLEAR_CRON, async () => {
        try {
          const nowNY = new Date(new Date().toLocaleString('en-US', { timeZone: TZ }));
          console.log(`Daily prune starting @ 03:00 NY (actual: ${nowNY.toLocaleString()})`);
          // Wait for any in-flight pipeline so we don't wipe tables mid-write.
          await waitForPipelineIdle();
          process.env.ALLOW_CLEAR = 'true';
          // Prune old records (keep 90 days history) instead of wiping everything.
          await db.pruneOldRecords(90);
          console.log('Daily prune done');
          enterQuietWindow();
        } catch (e) {
          console.error('Daily clear failed', e);
        } finally {
          delete process.env.ALLOW_CLEAR;
        }
      }, { timezone: TZ, scheduled: true });

      if (scheduledTask) {
        console.log(`Daily clear job scheduled @ ${DAILY_CLEAR_CRON} (03:00 NY) valid=${DAILY_CLEAR_VALID}`);
      } else {
        console.error('Failed to schedule daily clear job');
      }
    }

    console.log('All cron jobs started successfully');

    await syntheticTestsJob.start();

    scheduleBootGuardAfterClear();
    checkAndRunDailyResetIfNeeded();

    console.log('Press Ctrl+C to stop all cron jobs');
    // node-cron scheduled tasks hold timers that keep the process alive;
    // no need for stdin.resume() or an empty setInterval keep-alive.
  }

  if (once) {
    console.log('Running all jobs once...');
    await runPipeline("once");
    console.log('All jobs completed');
    await db.disconnect().catch(() => {});
    return;
  }
}

process.on('beforeExit', (code) => {
  console.error(`beforeExit: ${code}`);
});

process.on('exit', (code) => {
  console.error(`exit: ${code}`);
});

process.on('uncaughtException', (err) => {
  console.error('uncaughtException:', err);
});

process.on('unhandledRejection', (reason) => {
  console.error('unhandledRejection:', reason);
});

// Graceful shutdown
async function gracefulShutdown(signal: string) {
  console.log(`${signal} received, shutting down gracefully`);
  // Let any in-flight pipeline finish (bounded) so we don't leave partial DB writes.
  await waitForPipelineIdle(60 * 1000);
  await db.disconnect().catch(() => {});
  process.exit(0);
}

process.on('SIGINT', () => gracefulShutdown('SIGINT'));
process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));

// Start the application
bootstrap().catch((error) => {
  console.error('Failed to start:', error);
  process.exit(1);
});
