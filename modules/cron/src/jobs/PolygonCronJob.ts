import { BaseCronJob } from '../core/BaseCronJob.js';
import { db } from '../core/DatabaseManager.js';
import { processSymbolsInBatches } from '../core/priceService.js';
import { CONFIG } from '../../../shared/src/config.js';

export class PolygonCronJob extends BaseCronJob {
  constructor() {
    super({
      name: 'Polygon Market Cap Data',
      schedule: '0 */4 * * *', // Every 4 hours
      description: 'Fetches market cap data from Polygon API for symbols in PolygonData table',
      runOnStart: false
    });
  }

  async execute(): Promise<void> {
    console.log('🚀 Starting PolygonCronJob execution with batch processing...');
    
    // Mark job as running
    await db.updateCronStatus('polygon', 'running');
    
    try {
      // Get symbols from PolygonData table (these were copied from Finnhub)
      console.log('📊 Getting symbols from PolygonData table...');
      const symbols = await db.getUniqueSymbolsFromPolygonData();
      
      if (symbols.length === 0) {
        console.log('⚠️ No symbols found in PolygonData table');
        await db.updateCronStatus('polygon', 'success', 0);
        return;
      }

      console.log(`📈 Found ${symbols.length} symbols to process`);

      // STEP 1: Fetch market cap, price, company names in batches
      console.log('🌐 STEP 1: Fetching market cap data using batch processing...');
      const marketData = await processSymbolsInBatches(
        symbols,
        CONFIG.SNAPSHOT_BATCH_SIZE || 100,
        12
      );

      // STEP 2: Update PolygonData table with market cap data
      console.log('💾 STEP 2: Updating PolygonData with market cap information...');
      await db.updatePolygonMarketCapData(marketData);

      // STEP 3: Process missing logos (only for symbols that don't have logos yet)
      console.log('🖼️ STEP 3: Processing missing logos...');
      const { processLogosInBatches } = await import('../core/logoService.js');
      const logoResult = await processLogosInBatches(
        symbols,
        CONFIG.LOGO_BATCH_SIZE || 12,
        CONFIG.LOGO_CONCURRENCY || 6
      );
      console.log(`✅ Logo processing completed: ${logoResult.success} success, ${logoResult.failed} failed`);

      // STEP 4: Generate final report after updating all data
      console.log('🔄 STEP 4: Generating final report...');
      await db.generateFinalReport();

      // Mark job as successful
      await db.updateCronStatus('polygon', 'success', marketData.length);
      console.log('✅ PolygonCronJob completed successfully');
      
    } catch (error) {
      console.error('❌ PolygonCronJob failed:', error);
      // Mark job as failed
      await db.updateCronStatus('polygon', 'error', undefined, (error as any)?.message || 'Unknown error');
      throw error;
    }
  }
}
