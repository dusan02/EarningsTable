import { FinnhubCronJob } from './jobs/FinnhubCronJob.js';
import { PolygonCronJob } from './jobs/PolygonCronJob.js';
import { db } from './core/DatabaseManager.js';

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

async function main() {
  console.log('🔍 DEBUG: Starting main function...');
  const args = process.argv.slice(2);
  console.log('🔍 DEBUG: Args:', args);
  const jobType = args[0] as 'finnhub' | 'polygon' || 'finnhub';
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
