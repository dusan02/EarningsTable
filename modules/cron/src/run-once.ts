import { FinnhubCronJob } from './jobs/FinnhubCronJob.js';
import { PolygonCronJob } from './jobs/PolygonCronJob.js';
import { db } from './core/DatabaseManager.js';

function applyCliOverrides(args: string[]) {
  const dateArg = args.find((arg) => arg.startsWith('--date='));
  if (dateArg) {
    const [, value] = dateArg.split('=');
    if (value) {
      process.env.FINNHUB_FORCE_DATE = value;
      console.log(`📅 CLI override date detected: ${value}`);
    }
  }

  if (args.includes('--force')) {
    process.env.FINNHUB_FORCE = 'true';
    console.log('🔄 CLI force mode enabled for Finnhub job');
  }
}

async function runOnce(jobType: 'finnhub' | 'polygon') {
  try {
    if (jobType === 'finnhub') {
      console.log('🔄 Running Finnhub job once...');
      const finnhubJob = new FinnhubCronJob();
      await finnhubJob.execute();
    } else if (jobType === 'polygon') {
      console.log('🔄 Running Polygon job once...');
      const polygonJob = new PolygonCronJob();
      await polygonJob.execute();
    }

    console.log('✅ One-time execution completed successfully');
    
  } catch (error) {
    console.error('✗ One-time execution failed:', error);
    throw error;
  } finally {
    await db.disconnect();
  }
}

export async function main() {
  console.log('🔍 DEBUG: Starting main function...');
  const args = process.argv.slice(2);
  console.log('🔍 DEBUG: Args:', args);
  const jobType = args[0] as 'finnhub' | 'polygon' || 'finnhub';
  applyCliOverrides(args);
  console.log('🔍 DEBUG: Job type:', jobType);

  console.log(`🚀 Running cron jobs once (${jobType})...`);
  await runOnce(jobType);
}

// Always run main function
main().catch(async (e) => {
  console.error('✗ Script failed:', e);
  await db.disconnect();
  process.exit(1);
});

export { runOnce };
