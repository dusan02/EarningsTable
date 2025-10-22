import { fetchTodayEarnings } from '../finnhub.js';
import { db } from '../core/DatabaseManager.js';
import { todayIsoNY, CONFIG } from '../config.js';

interface FinnhubJobOptions {
  date?: string;  // YYYY-MM-DD format
  force?: boolean; // Ignore cache/duplicates
}

interface FinnhubJobResult { 
  symbolsChanged: string[]; 
  upserted: number; 
}

export async function runFinnhubJob(options: FinnhubJobOptions = {}): Promise<FinnhubJobResult> {
  const startTime = Date.now();
  console.log('🚀 Starting Finnhub job execution...');
  
  try {
    // Use provided date or default to today
    const isoDate = options.date || todayIsoNY();
    console.log(`📅 Fetching earnings for ${isoDate} (NY time)`);
    
    if (options.force) {
      console.log('🔄 Force mode: will overwrite existing data');
    }
    
    const rows = await fetchTodayEarnings(CONFIG.FINNHUB_TOKEN, isoDate);
    console.log(`📊 Found ${rows.length} earnings reports for ${isoDate}`);

    if (rows.length === 0) {
      console.log('⚠️ No earnings reports found for the specified date');
      return { symbolsChanged: [], upserted: 0 };
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
    const changedSymbols = await db.upsertFinhubData(toSave);
    
    console.log('🔄 Copying symbols to PolygonData table...');
    await db.copySymbolsToPolygonData();
    
    const duration = Date.now() - startTime;
    console.log(`✅ Finnhub job completed successfully in ${duration}ms`);
    console.log(`📈 Summary: ${rows.length} records processed, ${changedSymbols.length} changed`);
    
    return { symbolsChanged: changedSymbols, upserted: changedSymbols.length };
    
  } catch (error) {
    const duration = Date.now() - startTime;
    console.error(`❌ Finnhub job failed after ${duration}ms:`, error);
    throw error;
  }
}
