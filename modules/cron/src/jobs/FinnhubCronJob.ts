import { BaseCronJob } from '../core/BaseCronJob.js';
import { fetchTodayEarnings } from '../finnhub.js';
import { db } from '../core/DatabaseManager.js';
import { processLogosInBatches } from '../core/logoService.js';
import { resolveFinnhubTargetDate } from './finnhub.js';

export class FinnhubCronJob extends BaseCronJob {
  constructor() {
    super({
      name: 'Finnhub Earnings Data',
      schedule: '0 7 * * *', // Every day at 07:00 NY time
      description: 'Fetches daily earnings data from Finnhub API',
      runOnStart: false
    });
  }

  async execute(): Promise<void> {
    console.log('🚀 Starting FinnhubCronJob execution...');
    
    const startTime = new Date();
    // Mark job as running (with startedAt for logging)
    await db.updateCronStatus('finnhub', 'running', undefined, undefined, startTime);
    
    try {
      const isoDate = resolveFinnhubTargetDate();
      console.log(`📅 Fetching earnings for ${isoDate} (NY time)`);
      
      const rows = await fetchTodayEarnings(process.env.FINNHUB_TOKEN!, isoDate);
      console.log(`📊 Found ${rows.length} earnings reports for today`);

      if (rows.length === 0) {
        console.log('⚠️ No earnings reports found for today');
        const duration = Date.now() - startTime.getTime();
        await db.updateCronStatus('finnhub', 'success', 0, undefined, startTime, duration);
        return;
      }

      console.log(`💾 Preparing ${rows.length} records for database...`);
      const toSave = rows.map(r => ({
        reportDate: new Date(`${r.date}T00:00:00.000Z`),
        symbol: r.symbol,
        hour: r.hour ?? null,
        epsActual: r.epsActual ?? null,
        epsEstimate: r.epsEstimate ?? null,
        revenueActual: r.revenueActual ?? null,
        revenueEstimate: r.revenueEstimate ?? null,
        quarter: r.quarter ?? null,
        year: r.year ?? null,
      }));

      console.log('💾 Saving data to FinhubData table...');
      await db.upsertFinhubData(toSave);
      
      console.log('🔄 Copying symbols to PolygonData table...');
      await db.copySymbolsToPolygonData();
      
      // Skip logo processing here - will be done in main pipeline to avoid duplicates
      console.log('🖼️ Logo processing will be handled by main pipeline to avoid duplicates');
      
      // Mark job as successful with duration
      const duration = Date.now() - startTime.getTime();
      await db.updateCronStatus('finnhub', 'success', rows.length, undefined, startTime, duration);
      console.log('✅ FinnhubCronJob completed successfully');
      
    } catch (error) {
      console.error('❌ FinnhubCronJob failed:', error);
      // Mark job as failed with duration
      const duration = Date.now() - startTime.getTime();
      await db.updateCronStatus('finnhub', 'error', 0, (error as any)?.message || 'Unknown error', startTime, duration);
      throw error;
    }
  }
}
