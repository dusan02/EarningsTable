import cron from 'node-cron';
import { DatabaseManager } from './core/DatabaseManager.js';
import { processSymbolsWithPriceService } from './core/priceService.js';
import { processLogosInBatchesOptimized } from './core/logoService-optimized.js';
import { withMutex } from './utils/mutex.js';

const db = new DatabaseManager();

async function runPipelineOnceOptimized(): Promise<void> {
  console.log('🚀 Running optimized pipeline...');

  try {
    await db.updateCronStatus('running');

    // Len ak sú dáta staršie ako 1 hodina
    const lastUpdate = await db.getLastUpdateTime();
    const now = new Date();
    const hoursSinceUpdate = (now.getTime() - lastUpdate.getTime()) / (1000 * 60 * 60);

    if (hoursSinceUpdate < 1) {
      console.log('⏭️ Data is fresh, skipping update');
      await db.updateCronStatus('success', { recordsProcessed: 0 });
      return;
    }

    // Vyčisti len ak sú dáta staršie
// await db.clearFinhubData(); // disabled: run only in daily clear job
// await db.clearPolygonData(); // disabled: run only in daily clear job
// await db.clearFinalReport(); // disabled: run only in daily clear job

    const existingSymbols = await db.getPolygonSymbols();

    if (existingSymbols.length > 0) {
      // Paralelné spracovanie
      const [marketData] = await Promise.all([
        processSymbolsWithPriceService(existingSymbols),
        // Spusti logo processing paralelne
        processLogosInBatchesOptimized(existingSymbols.slice(0, 20), 10, 10)
      ]);

      await db.updatePolygonMarketCapData(marketData);
      await db.generateFinalReport();
    }

    await db.updateCronStatus('success', { recordsProcessed: existingSymbols.length });
    console.log('✅ Optimized pipeline completed');

  } catch (error) {
    await db.updateCronStatus('failed', { errorMessage: (error as any)?.message || 'Unknown error' });
    throw error;
  }
}

// Optimalizované cron schedule
async function main() {
  const args = process.argv.slice(2);
  const once = args.includes('--once');
  const force = args.includes('--force');

  if (once) {
    if (force || process.env.FORCE_RUN_ENV === 'true') {
      try {
        await runPipelineOnceOptimized();
        process.exit(0);
      } catch (error) {
        console.error('[cron] FATAL:', error);
        process.exit(1);
      }
    }
  } else {
    // Menej časté spúšťanie - každých 15 minút namiesto 5
    cron.schedule('*/15 * * * 1-5', async () => {
      console.log('🚀 Running optimized pipeline...');
      try {
        await withMutex('earnings-pipeline', async () => {
          await runPipelineOnceOptimized();
        });
      } catch (error) {
        console.error('❌ Pipeline failed:', error);
      }
    });

    console.log('[cron] Optimized scheduler armed:');
    console.log('  📊 Cron jobs: "*/15 * * * 1-5" (every 15min Mon-Fri)');
    console.log('  🌍 Timezone: America/New_York');
    console.log('[cron] Press Ctrl+C to stop');
  }
}

main().catch(console.error);
