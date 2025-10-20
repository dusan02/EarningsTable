import 'dotenv/config';
import cron from 'node-cron';
import { DateTime } from 'luxon';
import yargs from 'yargs';
import { hideBin } from 'yargs/helpers';
import { createClient } from 'redis';
import { db } from './core/DatabaseManager';
import { runFinnhubJob } from './jobs/finnhub';
import { runPolygonJob } from './jobs/polygon';
import { processLogosInBatches } from './core/logoService';

// Basic, in-process mutex to avoid overlapping runs in the same process
let isRunning = false;
async function withMutex<T>(label: string, fn: () => Promise<T>): Promise<T | null> {
  if (isRunning) {
    console.log(`[cron] Skip run – mutex held (${label})`);
    return null;
  }
  isRunning = true;
  const started = Date.now();
  try {
    const res = await fn();
    console.log(`[cron] Done (${label}) in ${Date.now() - started}ms`);
    return res;
  } catch (e) {
    console.error(`[cron] Failed (${label}):`, e);
    throw e;
  } finally {
    isRunning = false;
  }
}

const argv = yargs(hideBin(process.argv))
  .option('force', { type: 'boolean', default: false })
  .option('once', { type: 'boolean', default: false })
  .parseSync();

const TZ = process.env.TZ || 'America/New_York';
const SCHEDULE = '*/5 * * * 1-5'; // every 5 minutes, Mon–Fri
const FORCE_RUN_ENV = process.env.FORCE_RUN === '1' || process.env.FORCE_RUN === 'true';
const SKIP_RESET_CHECK = process.env.SKIP_RESET_CHECK === '1' || process.env.SKIP_RESET_CHECK === 'true';
const USE_REDIS_LOCK = process.env.USE_REDIS_LOCK === '1' || process.env.USE_REDIS_LOCK === 'true';
const REDIS_URL = process.env.REDIS_URL || 'redis://127.0.0.1:6379';
const LOCK_KEY = 'cron:runPipeline:lock';
const LOCK_TTL_MS = 4 * 60 * 1000;

function nowNY(): DateTime {
  return DateTime.now().setZone(TZ);
}

function isMonFriNY(dt: DateTime): boolean {
  const wd = dt.weekday; // 1..7 (Mon..Sun)
  return wd >= 1 && wd <= 5;
}

// Minimal placeholder – for full NYSE holiday support, wire a proper calendar
function isNyseHolidayNY(dt: DateTime): boolean {
  const fixed = new Set(['01-01', '07-04', '12-25']);
  if (fixed.has(dt.toFormat('MM-dd'))) return true;
  return false;
}

async function ensureDailyResetNY(): Promise<void> {
  // No-op placeholder. If you need to hard reset before open, implement here.
  // Example: await db.clearAllTables();
}

async function withRedisMutex<T>(label: string, fn: () => Promise<T>): Promise<T | null> {
  if (!USE_REDIS_LOCK) return withMutex(label, fn);
  const redis = createClient({ url: REDIS_URL });
  await redis.connect();
  const token = `${process.pid}-${Date.now()}`;
  try {
    const ok = await redis.set(LOCK_KEY, token, { NX: true, PX: LOCK_TTL_MS });
    if (ok !== 'OK') {
      console.log(`[cron] Skip run – redis lock held (${LOCK_KEY})`);
      return null;
    }
    const started = Date.now();
    const res = await fn();
    console.log(`[cron] Done (${label}) in ${Date.now() - started}ms`);
    return res as T;
  } finally {
    try {
      const current = await redis.get(LOCK_KEY);
      if (current === token) await redis.del(LOCK_KEY);
    } catch {}
    await redis.quit();
  }
}

async function runPipelineOnce(label: string): Promise<void> {
  const now = nowNY();
  if (!isMonFriNY(now)) {
    console.log(`[cron] Guard: ${label} – not Mon–Fri in ${TZ} (${now.toISO()}). Skipping.`);
    return;
  }
  if (isNyseHolidayNY(now)) {
    console.log(`[cron] Guard: ${label} – NYSE holiday in ${TZ} (${now.toISO()}). Skipping.`);
    return;
  }

  await withRedisMutex(label, async () => {
    try {
      // Update cron status to running
      await db.updateCronStatus('pipeline', 'running');
      // Optional daily reset hook
      if (!SKIP_RESET_CHECK) {
        try {
          await ensureDailyResetNY();
        } catch (e: any) {
          console.warn('[cron] daily reset failed (continuing):', e?.message || e);
        }
      } else {
        console.log('[cron] SKIP_RESET_CHECK=1 → skipping daily reset');
      }

    const t0 = Date.now();
    console.log('🚀 Running Finnhub job...');
    await runFinnhubJob();
    console.log(`[timing] finnhub=${Date.now() - t0}ms`);

    const t1 = Date.now();
    console.log('🚀 Copying symbols into PolygonData...');
    await db.copySymbolsToPolygonData();
    console.log(`[timing] copySymbols=${Date.now() - t1}ms`);

    const t2 = Date.now();
    console.log('🚀 Running Polygon job...');
    await runPolygonJob();
    console.log(`[timing] polygon=${Date.now() - t2}ms`);

    const t3 = Date.now();
    console.log('🔄 Generating final report...');
    await db.generateFinalReport();
    console.log(`[timing] finalReport=${Date.now() - t3}ms`);

    const t4 = Date.now();
    console.log('🖼️  Fetching logos...');
    const symbolsNeedingLogos = await db.getSymbolsNeedingLogoRefresh();
    if (symbolsNeedingLogos.length > 0) {
      const logoResult = await processLogosInBatches(symbolsNeedingLogos, 15, 8);
      console.log(`✅ Logo processing completed: ${logoResult.success} success, ${logoResult.failed} failed`);
      
      // Regenerate final report to include logos
      const t5 = Date.now();
      console.log('🔄 Regenerating final report with logos...');
      await db.generateFinalReport();
      console.log(`[timing] finalReportWithLogos=${Date.now() - t5}ms`);
    } else {
      console.log('✅ All logos are up to date');
    }
    console.log(`[timing] logos=${Date.now() - t4}ms`);
    
    // Update cron status to success
    await db.updateCronStatus('pipeline', 'success');
    } catch (error) {
      // Update cron status to error
      await db.updateCronStatus('pipeline', 'error', undefined, (error as any)?.message || 'Unknown error');
      throw error;
    }
  });
}

async function main() {
  console.log(`[cron] Boot in ${TZ} (force=${argv.force}, once=${argv.once}, FORCE_RUN_ENV=${FORCE_RUN_ENV}, USE_REDIS_LOCK=${USE_REDIS_LOCK})`);

  if (argv.force || argv.once || FORCE_RUN_ENV) {
    await runPipelineOnce('immediate');
    if (argv.once) {
      console.log('[cron] --once completed → exiting');
      try { await db.disconnect(); } catch {}
      process.exit(0);
    }
  }

  cron.schedule(
    SCHEDULE,
    async () => {
      const now = nowNY();
      if (!isMonFriNY(now)) return;
      if (isNyseHolidayNY(now)) return;
      await runPipelineOnce('scheduled');
    },
    { timezone: TZ }
  );

  console.log(`[cron] Scheduler armed: "${SCHEDULE}" in ${TZ} (Mon–Fri, every 5 minutes)`);
  console.log('[cron] Press Ctrl+C to stop');
}

process.on('SIGINT', async () => {
  console.log('↩️ SIGINT: shutting down…');
  try { await db.disconnect(); } catch {}
  process.exit(0);
});

process.on('SIGTERM', async () => {
  console.log('↩️ SIGTERM: shutting down…');
  try { await db.disconnect(); } catch {}
  process.exit(0);
});

main().catch((e) => {
  console.error('[cron] FATAL:', e);
  process.exit(1);
});

process.on('unhandledRejection', (reason) => {
  console.error('[cron] UNHANDLED REJECTION:', reason);
});
process.on('uncaughtException', (err) => {
  console.error('[cron] UNCAUGHT EXCEPTION:', err);
  process.exit(1);
});


