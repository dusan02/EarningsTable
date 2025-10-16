import { BaseCronJob } from '../core/BaseCronJob.js';
import { fetchMarketCapDataForSymbols } from '../polygon.js';
import { db } from '../core/DatabaseManager.js';
import { processSymbolsInBatches } from '../core/priceService.js';

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
    console.log('🚀 Starting PolygonCronJob execution with PriceService...');
    try {
      // Get all symbols from PolygonData table
      console.log('📊 Getting symbols from PolygonData table...');
      const symbols = await db.getUniqueSymbolsFromPolygonData();
      
      if (symbols.length === 0) {
        console.log('⚠️ No symbols found in PolygonData table');
        return;
      }

      console.log(`📈 Found ${symbols.length} symbols to process`);

      // Use PriceService for optimized market cap data fetching
      console.log('🌐 Fetching market cap data using PriceService...');
      const marketData = await processSymbolsInBatches(symbols, 80, 10);

      // Update PolygonData table with market cap data
      console.log('💾 Updating PolygonData with market cap information...');
      await db.updatePolygonMarketCapData(marketData);

      // Generate final report after updating market cap data
      console.log('🔄 Generating final report...');
      await db.generateFinalReport();

      console.log('✅ PolygonCronJob completed successfully');
      
    } catch (error) {
      console.error('❌ PolygonCronJob failed:', error);
      throw error;
    }
  }
}
