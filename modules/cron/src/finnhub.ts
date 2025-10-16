import { FinnhubEarning } from '../../shared/src/types.js';
import { toNumber, toInteger } from '../../shared/src/utils.js';
import { retryGet } from './utils/retry-axios.js';

// Normalize hour values to standard format
function mapHour(h?: string | null): 'bmo' | 'amc' | 'dmh' | null {
  if (!h) return null;
  const v = h.toLowerCase().trim();
  if (v.startsWith('b') || v.includes('before')) return 'bmo';
  if (v.startsWith('a') || v.includes('after')) return 'amc';
  if (v.includes('during') || v.includes('market')) return 'dmh';
  return null;
}

// Deduplicate and normalize data
function deduplicateAndNormalize(rows: any[]): FinnhubEarning[] {
  const uniq = new Map<string, FinnhubEarning>();
  let droppedCount = 0;

  for (const r of rows) {
    const symbol = (r.symbol || '').trim().toUpperCase();
    if (!symbol) {
      droppedCount++;
      continue;
    }

    const key = `${r.date}|${symbol}`;
    const normalized: FinnhubEarning = {
      symbol,
      date: r.date,
      hour: mapHour(r.hour),
      epsActual: toNumber(r.epsActual),
      epsEstimate: toNumber(r.epsEstimate),
      revenueActual: toNumber(r.revenueActual),
      revenueEstimate: toNumber(r.revenueEstimate),
      quarter: toInteger(r.quarter),
      year: toInteger(r.year),
    };

    // Keep the most complete record if duplicates exist
    if (!uniq.has(key) || hasMoreData(normalized, uniq.get(key)!)) {
      uniq.set(key, normalized);
    }
  }

  if (droppedCount > 0) {
    console.warn(`⚠️ Dropped ${droppedCount} records with invalid symbols`);
  }

  return Array.from(uniq.values());
}

// Helper to determine which record has more complete data
function hasMoreData(a: FinnhubEarning, b: FinnhubEarning): boolean {
  const aFields = [a.epsActual, a.epsEstimate, a.revenueActual, a.revenueEstimate, a.quarter, a.year].filter(x => x != null).length;
  const bFields = [b.epsActual, b.epsEstimate, b.revenueActual, b.revenueEstimate, b.quarter, b.year].filter(x => x != null).length;
  return aFields > bFields;
}

export async function fetchTodayEarnings(token: string, isoDate: string): Promise<FinnhubEarning[]> {
  // Finnhub endpoint (earnings calendar):
  // GET /api/v1/calendar/earnings?from=YYYY-MM-DD&to=YYYY-MM-DD&token=...
  const url = 'https://finnhub.io/api/v1/calendar/earnings';
  
  try {
    const { data } = await retryGet(url, {
      params: { 
        from: isoDate, 
        to: isoDate, 
        token 
      },
      timeout: 12000,
      headers: {
        'Connection': 'keep-alive',
        'User-Agent': 'EarningsTable/1.0'
      }
    }, {
      maxAttempts: 3,
      baseDelay: 1000,
      maxDelay: 5000,
      jitter: true
    });

    // Očakávaná štruktúra: { earningsCalendar: [ { symbol, date, epsActual, epsEstimate, revenueActual, revenueEstimate, hour, quarter, year } ] }
    const rows = (data?.earningsCalendar ?? []) as any[];
    console.log(`📥 Raw API response: ${rows.length} records`);

    const normalized = deduplicateAndNormalize(rows);
    console.log(`📊 After deduplication: ${normalized.length} unique records`);

    return normalized;
  } catch (error) {
    console.error('Error fetching earnings from Finnhub:', error);
    throw new Error(`Failed to fetch earnings data: ${error instanceof Error ? error.message : 'Unknown error'}`);
  }
}
