import cron from 'node-cron';
import { DatabaseManager } from './core/DatabaseManager.js';
import { processSymbolsWithPriceService } from './core/priceService.js';
import { fetchAndStoreLogo } from './core/logoService.js';
import { withMutex } from './utils/mutex.js';

const db = new DatabaseManager();

async function runPipelineOnce(): Promise<void> {
  console.log('🚀 Running pipeline...');
  
  try {
    // Update cron status to running
    await db.updateCronStatus('running');
    
    
    // Get symbols from database (if any exist)
    const existingSymbols = await db.getPolygonSymbols();
    
    if (existingSymbols.length > 0) {
      // Process symbols with price service
      const marketData = await processSymbolsWithPriceService(existingSymbols);
      await db.updatePolygonMarketCapData(marketData);
      
      // Generate final report
      await db.generateFinalReport();
      
      // Update logos for first 10 symbols
      for (const symbol of existingSymbols.slice(0, 10)) {
        try {
          await fetchAndStoreLogo(symbol);
        } catch (error) {
          console.log(`   → Logo fetch failed for ${symbol}: ${(error as any)?.message}`);
        }
      }
    }
    
    // Update cron status to success
    await db.updateCronStatus('success', { recordsProcessed: existingSymbols.length });
    
    console.log('✅ Pipeline completed');
  } catch (error) {
    // Update cron status to error
    await db.updateCronStatus('failed', { errorMessage: (error as any)?.message || 'Unknown error' });
    throw error;
  }
}

async function main() {
  const args = process.argv.slice(2);
  const once = args.includes('--once');
  const force = args.includes('--force');
  
  if (once) {
    console.log(`[cron] Boot in America/New_York (force=${force}, once=${once}, FORCE_RUN_ENV=${process.env.FORCE_RUN_ENV}, USE_REDIS_LOCK=${process.env.USE_REDIS_LOCK})`);
    
    if (force || process.env.FORCE_RUN_ENV === 'true') {
      try {
        await runPipelineOnce();
        return; // was return; // was process.exit(0)
      } catch (error) {
        console.error('[cron] FATAL:', error);
        return; // was return; // was process.exit(1)
      }
    } else {
      console.log('[cron] Failed (immediate):', error);
      return; // was return; // was process.exit(1)
    }
  } else {
    console.log('[cron] Boot in America/New_York');
    
    // Schedule clear data job (7:00 AM weekdays)
    cron.schedule('0 3 * * 1-5', async () => {
      console.log('🗑️ Running daily clear data job...');
      try {
        await withMutex('clear-data', async () => {
// await db.clearAllTables(); // disabled: run only in daily clear job
        });
      } catch (error) {
        console.error('❌ Clear data job failed:', error);
      }
    });
    
    // Schedule main pipeline (every 5 minutes during market hours, weekdays only)
    cron.schedule('*/5 * * * 1-5', async () => {
      console.log('🚀 Running pipeline...');
      try {
        await withMutex('earnings-pipeline', async () => {
          await runPipelineOnce();
        });
      } catch (error) {
        console.error('❌ Pipeline failed:', error);
      }
    });
    
    console.log('[cron] Scheduler armed:');
    console.log('  🗑️  Clear data: "0 3 * * 1-5" (07:00 Mon-Fri)');
    console.log('  📊 Cron jobs: "*/5 * * * 1-5" (07:05-06:55 every 5min Mon-Fri, weekends OFF)');
    console.log('  🌍 Timezone: America/New_York');
    console.log('[cron] Press Ctrl+C to stop');
  }
}

main().catch(console.error);
