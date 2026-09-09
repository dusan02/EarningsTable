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

const DATE_OVERRIDE_REGEX = /^\d{4}-\d{2}-\d{2}$/;

export function resolveFinnhubTargetDate(preferred?: string): string {
  const override = preferred || process.env.FINNHUB_FORCE_DATE;
  if (override) {
    if (DATE_OVERRIDE_REGEX.test(override)) {
      console.log(`📅 Finnhub override date in use: ${override}`);
      return override;
    }
    console.warn(`⚠️ Ignoring invalid Finnhub date override "${override}" (expected YYYY-MM-DD). Falling back to today.`);
  }
  return todayIsoNY();
}

function resolveForceMode(explicit?: boolean): boolean {
  return Boolean(explicit || process.env.FINNHUB_FORCE === 'true');
}

export async function runFinnhubJob(options: FinnhubJobOptions = {}): Promise<FinnhubJobResult> {
  const startTime = Date.now();
  console.log('🚀 Starting Finnhub job execution...');
  
  try {
    const isoDate = resolveFinnhubTargetDate(options.date);
    console.log(`📅 Fetching earnings for ${isoDate} (NY time)`);
    
    // Fetch a 7-day window (±3 days) so the calendar has data for the whole week
    const centerDate = new Date(`${isoDate}T00:00:00.000Z`);
    const fromDate = new Date(centerDate);
    fromDate.setUTCDate(centerDate.getUTCDate() - 3);
    const toDate = new Date(centerDate);
    toDate.setUTCDate(centerDate.getUTCDate() + 3);
    const fromStr = fromDate.toISOString().split('T')[0];
    const toStr = toDate.toISOString().split('T')[0];
    console.log(`📅 Fetching earnings window: ${fromStr} to ${toStr}`);
    
    if (resolveForceMode(options.force)) {
      console.log('🔄 Force mode: will overwrite existing data');
    }
    
    const rows = await fetchTodayEarnings(CONFIG.FINNHUB_TOKEN, isoDate, { from: fromStr, to: toStr });
    console.log(`📊 Found ${rows.length} earnings reports for ${fromStr} to ${toStr}`);

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
