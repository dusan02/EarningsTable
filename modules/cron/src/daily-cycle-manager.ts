import cron from 'node-cron';
import { db } from './core/DatabaseManager.js';
import { FinnhubCronJob } from './jobs/FinnhubCronJob.js';
import { PolygonCronJob } from './jobs/PolygonCronJob.js';
import { ClearDatabaseCronJob } from './clear-db-cron.js';

const TZ = 'America/New_York';

export class DailyCycleManager {
  private finnhubJob: FinnhubCronJob;
  private polygonJob: PolygonCronJob;
  private clearDbJob: ClearDatabaseCronJob;
  private isSequenceRunning: boolean = false;
  private isCronsRunning: boolean = false;
  
  // Timeout guards to prevent stuck flags
  private readonly SEQUENCE_TIMEOUT_MS = 10 * 60 * 1000; // 10 minutes
  private readonly CRONS_TIMEOUT_MS = 5 * 60 * 1000; // 5 minutes

  constructor() {
    this.finnhubJob = new FinnhubCronJob();
    this.polygonJob = new PolygonCronJob();
// lazy init: created when needed
    
    // Set timezone for consistency
    process.env.TZ = process.env.CRON_TZ || TZ;
  }

  async start(): Promise<void> {
    console.log('🚀 Starting Daily Cycle Manager...');
    console.log(`📅 Timezone: ${TZ}`);
    console.log('📋 Schedule:');
    console.log('  🧹 03:00 - Clear database tables');
    console.log('  📊 03:05 - Start Finnhub → Polygon sequence');
    console.log('  🔄 03:10+ - Both crons every 5 minutes until 02:30');
    console.log('  🧹 03:00 - Repeat cycle (clear tables)');

    // 1. Clear database at 03:00
    cron.schedule('0 3 * * *', async () => {
      console.log('🧹 [CRON] Database cleanup at 03:00');
      try {
        await this.clearDbJob.execute();
        console.log('✅ [CRON] Database cleanup completed');
      } catch (error) {
        console.error('❌ [CRON] Database cleanup failed:', error);
      }
    }, { timezone: TZ });

    // 2. Start Finnhub → Polygon sequence at 03:05
    cron.schedule('5 3 * * *', async () => {
      console.log('📊 [CRON] Starting Finnhub → Polygon sequence at 03:05');
      await this.runFinnhubThenPolygon();
    }, { timezone: TZ });

    // 3. Run both crons every 5 minutes from 03:10 to 23:55
    cron.schedule('10,15,20,25,30,35,40,45,50,55 3-23 * * *', async () => {
      this.log('🔄 [CRON] Running both crons every 5 minutes');
      await this.runBothCrons();
    }, { timezone: TZ });

    // 4. Run both crons every 5 minutes from 00:00 to 02:30 (fixed interval)
    cron.schedule('*/5 0-2 * * *', async () => {
      this.log('🔄 [CRON] Running both crons every 5 minutes (night hours)');
      await this.runBothCrons();
    }, { timezone: TZ });

    console.log('✅ Daily Cycle Manager started successfully');
    console.log('Press Ctrl+C to stop');
  }

  private async runFinnhubThenPolygon(): Promise<void> {
    if (this.isSequenceRunning) {
      this.log('⚠️ Previous sequence still running, skipping...');
      return;
    }

    this.isSequenceRunning = true;
    const start = Date.now();
    
    // Timeout guard to prevent stuck flag
    const timer = setTimeout(() => {
      this.log('⚠️ Sequence timeout — resetting flag');
      this.isSequenceRunning = false;
    }, this.SEQUENCE_TIMEOUT_MS);

    try {
      this.log('🚀 Starting Finnhub job...');
      await this.finnhubJob.execute();
      this.log('✅ Finnhub job completed');

      this.log('🚀 Starting Polygon job...');
      await this.polygonJob.execute();
      this.log('✅ Polygon job completed');

      const duration = ((Date.now() - start) / 1000).toFixed(2);
      this.log(`✅ Sequence completed in ${duration}s`);

    } catch (error) {
      const duration = ((Date.now() - start) / 1000).toFixed(2);
      this.log(`❌ Sequence failed after ${duration}s:`, error);
      throw error; // Re-throw to mark as failed
    } finally {
      clearTimeout(timer);
      this.isSequenceRunning = false;
    }
  }

  private async runBothCrons(): Promise<void> {
    if (this.isCronsRunning) {
      this.log('⚠️ Previous crons still running, skipping...');
      return;
    }

    this.isCronsRunning = true;
    const start = Date.now();
    
    // Timeout guard to prevent stuck flag
    const timer = setTimeout(() => {
      this.log('⚠️ Crons timeout — resetting flag');
      this.isCronsRunning = false;
    }, this.CRONS_TIMEOUT_MS);

    try {
      // Run both jobs in parallel for faster execution
      this.log('🚀 Running both crons in parallel...');
      
      const [finnhubResult, polygonResult] = await Promise.allSettled([
        this.finnhubJob.execute(),
        this.polygonJob.execute()
      ]);

      if (finnhubResult.status === 'fulfilled') {
        this.log('✅ Finnhub job completed');
      } else {
        this.log('❌ Finnhub job failed:', finnhubResult.reason);
      }

      if (polygonResult.status === 'fulfilled') {
        this.log('✅ Polygon job completed');
      } else {
        this.log('❌ Polygon job failed:', polygonResult.reason);
      }

      const duration = ((Date.now() - start) / 1000).toFixed(2);
      this.log(`✅ Parallel crons completed in ${duration}s`);

    } catch (error) {
      const duration = ((Date.now() - start) / 1000).toFixed(2);
      this.log(`❌ Both crons failed after ${duration}s:`, error);
    } finally {
      clearTimeout(timer);
      this.isCronsRunning = false;
    }
  }

  async stop(): Promise<void> {
    this.log('🛑 Stopping Daily Cycle Manager...');
    await db.disconnect();
  }

  // Centralized logging with timestamp
  private log(message: string, ...args: any[]): void {
    const timestamp = new Date().toISOString();
    console.log(`[${timestamp}] ${message}`, ...args);
  }
}

// Graceful shutdown
process.on('SIGINT', async () => {
  console.log('🛑 Graceful shutdown initiated');
  try {
    await db.disconnect();
    console.log('✅ Database disconnected');
  } catch (error) {
    console.error('❌ Error during shutdown:', error);
  }
  return; // was return; // was process.exit(0)
});

process.on('SIGTERM', async () => {
  console.log('🛑 SIGTERM received, shutting down gracefully');
  try {
    await db.disconnect();
    console.log('✅ Database disconnected');
  } catch (error) {
    console.error('❌ Error during shutdown:', error);
  }
  return; // was return; // was process.exit(0)
});
