import axios from 'axios';
import { CONFIG } from '../../shared/src/config.js';
import pLimit from 'p-limit';
import { processSymbolsInBatches, MarketCapData } from './core/priceService.js';
import Decimal from 'decimal.js';

// Optimized retry utility - only retry on 5xx/429, max 1 retry, shorter jitter
const shouldRetry = (e: any) => {
  const s = e?.response?.status;
  return !s || s >= 500 || s === 429;
};

async function retry<T>(fn: () => Promise<T>, tries = 2): Promise<T> {
  let err;
  for (let i = 0; i < tries; i++) {
    try {
      return await fn();
    } catch (e) {
      err = e;
      if (!shouldRetry(e) || i === tries - 1) break;
      // Shorter jitter: 300ms base + 300ms random
      await new Promise(r => setTimeout(r, 300 * (2 ** i) + Math.random() * 300));
    }
  }
  throw err;
}

export interface PolygonMarketData {
  symbol: string;
  symbolBoolean: boolean;
  marketCap?: bigint | null;
  previousMarketCap?: bigint | null;
  marketCapDiff?: bigint | null;
  marketCapBoolean: boolean;
  price?: number | null;
  previousClose?: number | null;
  change?: number | null;
  size?: string | null;  // Mega, Large, Mid, Small, null
  name?: string | null;  // Company name from Polygon API
  priceBoolean: boolean;
  Boolean: boolean;
  priceSource?: string | null; // 'pre'|'live'|'ah'|'min'|'day' for audit/debug
  marketCapFetchedAt?: Date | null; // Cache timestamp
}

// Cache for market cap data to avoid repeated API calls
const marketCapCache = new Map<string, { marketCap: bigint; name: string; fetchedAt: Date }>();

// Get NY date for grouped aggs (previous trading day)
function getNYDate(): string {
  const now = new Date();
  const nyTime = new Date(now.toLocaleString("en-US", { timeZone: "America/New_York" }));
  
  // If it's weekend, get Friday
  if (nyTime.getDay() === 0) { // Sunday
    nyTime.setDate(nyTime.getDate() - 2);
  } else if (nyTime.getDay() === 6) { // Saturday
    nyTime.setDate(nyTime.getDate() - 1);
  } else if (nyTime.getDay() === 1 && nyTime.getHours() < 9) { // Monday before market open
    nyTime.setDate(nyTime.getDate() - 3); // Previous Friday
  } else if (nyTime.getHours() < 9) { // Before market open
    nyTime.setDate(nyTime.getDate() - 1); // Previous day
  }
  
  return nyTime.toISOString().split('T')[0]; // YYYY-MM-DD format
}

// Fetch previous close prices for all symbols in one request
async function fetchGroupedPrevClose(symbols: string[]): Promise<Map<string, number>> {
  const yIso = getNYDate();
  console.log(`→ Fetching grouped previous close for ${symbols.length} symbols on ${yIso}...`);
  
  try {
    const { data } = await retry(() => 
      axios.get(`https://api.polygon.io/v2/aggs/grouped/locale/us/market/stocks/${yIso}`, {
        params: { 
          apikey: CONFIG.POLYGON_API_KEY,
          adjusted: true
        },
        timeout: 7000,
      })
    );

    const prevCloseMap = new Map<string, number>();
    
    if (data?.results) {
      for (const result of data.results) {
        if (result.T && result.c && result.c > 0) {
          prevCloseMap.set(result.T, result.c);
        }
      }
    }
    
    console.log(`→ Found ${prevCloseMap.size} previous close prices`);
    return prevCloseMap;
    
  } catch (error) {
    console.log(`→ Grouped aggs failed: ${(error as any).response?.status || 'Unknown error'}`);
    return new Map();
  }
}

// Optimized bulk snapshot with batching and concurrency
async function fetchSnapshotBulk(symbols: string[], options: { batchSize: number; concurrency: number }): Promise<BulkSnapshotData> {
  const { batchSize, concurrency } = options;
  const limit = pLimit(concurrency);
  
  console.log(`→ Fetching bulk snapshots for ${symbols.length} symbols in batches of ${batchSize} with concurrency ${concurrency}...`);
  
  const bulkData: BulkSnapshotData = {};
  
  // Process symbols in batches
  for (let i = 0; i < symbols.length; i += batchSize) {
    const batch = symbols.slice(i, i + batchSize);
    
    const batchPromises = batch.map(symbol => 
      limit(async () => {
        try {
          const { data } = await retry(() => 
            axios.get(`https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers/${symbol}`, {
              params: { apikey: CONFIG.POLYGON_API_KEY },
              timeout: 5000,
            })
          );

          // Debug: Log API response for first few symbols
          if (symbol === 'ABT' || symbol === 'AAPL' || symbol === 'MSFT') {
            console.log(`🔍 API response for ${symbol}:`, JSON.stringify(data, null, 2));
          }

          if (data?.ticker) {
            bulkData[symbol] = {
              preMarket: data.ticker.preMarket,
              lastTrade: data.ticker.lastTrade,
              afterHours: data.ticker.afterHours,
              minute: data.ticker.minute,
              day: data.ticker.day,
              prevDay: data.ticker.prevDay,
            };
          } else {
            console.log(`⚠️ No ticker data in API response for ${symbol}`);
          }
        } catch (error) {
          // Only log on final failure
          if ((error as any).response?.status === 429) {
            console.log(`→ Rate limited for ${symbol}, will retry`);
          } else {
            console.log(`❌ API error for ${symbol}: ${(error as any).response?.status || (error as any).message}`);
          }
        }
      })
    );
    
    await Promise.all(batchPromises);
    
    // No fixed sleep between batches - only retry on 429
    if (i + batchSize < symbols.length) {
      // Small delay to prevent overwhelming the API
      await new Promise(resolve => setTimeout(resolve, 100));
    }
  }
  
  console.log(`→ Bulk snapshots completed: ${Object.keys(bulkData).length}/${symbols.length} symbols`);
  return bulkData;
}

// Optimized price selection logic
function pickPrice(ticker: any, symbol: string): { price: number | null; source: string | null; ts: number | null } {
  if (!ticker) return { price: null, source: null, ts: null };

  const sessionOrder = ['pre', 'live', 'ah', 'min', 'day'];
  
  // Helper functions for robust number and timestamp handling
  const toNum = (v: any): number | null => {
    const num = Number(v);
    return Number.isFinite(num) && num > 0 ? num : null;
  };
  
  const normTs = (t: any): number | null => {
    const n = Number(t);
    if (!Number.isFinite(n)) return null;
    
    // Fixed timestamp normalization logic
    if (n > 1e17) {
      return Math.floor(n / 1e6); // Nanoseconds to milliseconds
    } else if (n > 1e14) {
      return Math.floor(n / 1e3); // Microseconds to milliseconds
    } else if (n > 1e11) {
      return n; // Already in milliseconds
    } else if (n > 1e9) {
      return n * 1000; // Seconds to milliseconds
    } else {
      return null; // Too small/unreliable
    }
  };

  // Get all available prices with normalized timestamps
  const candidates = [
    ticker.preMarket?.price != null 
      ? { p: toNum(ticker.preMarket.price), t: normTs(ticker.preMarket.timestamp), s: 'pre' } 
      : null,
    ticker.lastTrade?.p != null 
      ? { p: toNum(ticker.lastTrade.p), t: normTs(ticker.lastTrade.t), s: 'live' } 
      : null,
    ticker.afterHours?.price != null 
      ? { p: toNum(ticker.afterHours.price), t: normTs(ticker.afterHours.timestamp), s: 'ah' } 
      : null,
    ticker.minute?.c != null && ticker.minute.c > 0
      ? { p: toNum(ticker.minute.c), t: normTs(ticker.minute.t), s: 'min' }
      : null,
    ticker.day?.c != null && ticker.day.c > 0
      ? { p: toNum(ticker.day.c), t: null, s: 'day' } // Day close has no timestamp
      : null,
  ].filter((x) => x && x.p != null) as {p: number; t: number | null; s: string}[];

  // Sort by timestamp (most recent first), then by session priority
  candidates.sort((a, b) => {
    const timeDiff = (b.t ?? -1) - (a.t ?? -1);
    if (timeDiff !== 0) return timeDiff;
    return sessionOrder.indexOf(a.s) - sessionOrder.indexOf(b.s);
  });

  // Check for freshness with skew guard (allow +30 min for clock drift and API delays)
  const now = Date.now();
  
  // Debug: Log all candidates
  console.log(`→ Price candidates for ${symbol}:`, candidates.map(c => `${c.s}:${c.p} (ts:${c.t})`));
  
  const fresh = candidates.find(c => {
    if (c.t == null) {
      console.log(`→ ${c.s}: null timestamp, will use as fallback`);
      return false; // Skip null timestamps initially
    }
    const age = now - c.t;
    const isFresh = c.t <= now + 30 * 60_000 && age <= 24 * 60 * 60_000; // Within 24h, allow +30min future
    console.log(`→ ${c.s}: timestamp=${c.t} (${new Date(c.t).toISOString()}), age=${Math.round(age/1000/60)}min, fresh=${isFresh}`);
    return isFresh;
  }) ?? candidates.find(c => c.t == null); // Fallback to null timestamp only if no fresh data

  if (fresh) {
    console.log(`→ Selected price for ${symbol}: ${fresh.s}:${fresh.p}`);
  } else {
    console.log(`→ No price found for ${symbol}`);
  }

  return fresh ? { price: fresh.p, source: fresh.s, ts: fresh.t } : { price: null, source: null, ts: null };
}

// Market cap cache with 24h TTL
async function getMarketCapWithCache(symbol: string): Promise<{ marketCap: bigint | null; name: string | null; tickerExists: boolean }> {
  const cached = marketCapCache.get(symbol);
  const now = new Date();
  const CACHE_TTL = 24 * 60 * 60 * 1000; // 24 hours

  // Return cached data if still fresh
  if (cached && (now.getTime() - cached.fetchedAt.getTime()) < CACHE_TTL) {
    return { marketCap: cached.marketCap, name: cached.name, tickerExists: true };
  }

  // Fetch fresh data
  try {
    const response = await retry(() => 
      axios.get(`https://api.polygon.io/v3/reference/tickers/${symbol}`, {
        params: { apikey: CONFIG.POLYGON_API_KEY },
        timeout: 5000,
      })
    );

    const tickerData = response.data;
    let marketCap = null;
    let name = null;
    let tickerExists = false;

    if (tickerData?.results?.market_cap) {
      marketCap = BigInt(Math.round(tickerData.results.market_cap));
      tickerExists = true;
    }
    
    if (tickerData?.results?.name) {
      name = tickerData.results.name;
    }

    // Cache the result
    if (tickerExists) {
      marketCapCache.set(symbol, { marketCap: marketCap!, name: name || '', fetchedAt: now });
    }

    return { marketCap, name, tickerExists };
  } catch (error) {
    return { marketCap: null, name: null, tickerExists: false };
  }
}

export async function fetchMarketCapData(symbol: string, bulkSnapshotData?: any, prevCloseMap?: Map<string, number>): Promise<PolygonMarketData> {
  // Step 1: Get market cap with cache
  const { marketCap, name: companyName, tickerExists } = await getMarketCapWithCache(symbol);
  
  // Step 2: Get previous close from grouped aggs
  const previousClose = prevCloseMap?.get(symbol) || null;
  
  // Step 3: Get current price from snapshot data
  const { price, source: priceSource, ts } = pickPrice(bulkSnapshotData, symbol);
  
  // Step 4: Calculate change only if we have real current price (not previous close)
  let change = null;
  if (price != null && previousClose != null && priceSource !== 'prev') {
    change = ((price - previousClose) / previousClose) * 100;
  }
  
  // Debug: Log price information
  if (price === null) {
    console.log(`→ No current price found for ${symbol}, will use previousClose as fallback`);
  }
  
  // Step 5: Calculate previous market cap based on previous close price
  // If shares outstanding haven't changed, market cap scales proportionally with price
  // Use Decimal for precision when dealing with large BigInt values
  let previousMarketCap = null;
  let marketCapDiff = null;
  
  if (previousClose && marketCap && price != null && price > 0) {
    // Use Decimal for precise calculation: currentMarketCap * (previousClose / currentPrice)
    // This assumes shares outstanding haven't changed
    const d = (x: number | bigint | string) => new Decimal(String(x));
    const marketCapDecimal = d(marketCap);
    const ratio = d(previousClose).div(d(price));
    const previousMarketCapDecimal = marketCapDecimal.mul(ratio);
    previousMarketCap = BigInt(previousMarketCapDecimal.toFixed(0));

    // Step 6: Calculate marketCapDiff as the difference between current and previous market cap
    if (marketCap && previousMarketCap) {
      // Direct calculation: current market cap - previous market cap
      marketCapDiff = marketCap - previousMarketCap;
    }
  } else if (marketCap && change !== null) {
    // Fallback: if we don't have previousMarketCap but have change percentage,
    // calculate diff directly from current marketCap and change using Decimal for precision
    const d = (x: number | bigint | string) => new Decimal(String(x));
    const marketCapDecimal = d(marketCap);
    const changeDecimal = d(change).div(100);
    const diffDecimal = marketCapDecimal.mul(changeDecimal);
    marketCapDiff = BigInt(diffDecimal.toFixed(0));
  }

  // Step 7: Calculate Size based on market cap
  let size = null;
  if (marketCap) {
    const marketCapValue = Number(marketCap);
    if (marketCapValue >= 100000000000) { // >= $100B
      size = 'Mega';
    } else if (marketCapValue >= 10000000000) { // >= $10B
      size = 'Large';
    } else if (marketCapValue >= 1000000000) { // >= $1B
      size = 'Mid';
    } else { // < $1B
      size = 'Small';
    }
  }
  
  // Step 8: Calculate Boolean flags (0/1 instead of null)
  const hasMarketCap = marketCap != null;
  const hasPrice = price != null;
  const allConditionsMet = tickerExists && hasMarketCap && hasPrice;
  
  return {
    symbol,
    symbolBoolean: tickerExists,
    marketCap: marketCap,
    previousMarketCap: previousMarketCap,
    marketCapDiff: marketCapDiff,
    marketCapBoolean: hasMarketCap,
    price: price,
    previousClose: previousClose,
    change: change,
    size: size,
    name: companyName,
    priceBoolean: hasPrice,
    Boolean: allConditionsMet,
    priceSource: priceSource,
    marketCapFetchedAt: new Date()
  };
}

// Bulk snapshot data interface
interface BulkSnapshotData {
  [symbol: string]: {
    preMarket?: { price?: number; timestamp?: number };
    lastTrade?: { p?: number; t?: number };
    afterHours?: { price?: number; timestamp?: number };
    minute?: { c?: number; t?: number };
    day?: { c?: number };
    prevDay?: { c?: number };
  };
}

export async function fetchMarketCapDataForSymbols(symbols: string[]): Promise<PolygonMarketData[]> {
  console.log(`→ Fetching market cap data for ${symbols.length} symbols using PriceService...`);
  
  // Use the new PriceService for optimized processing
  const results = await processSymbolsInBatches(symbols, 80, 10);
  
  // Convert MarketCapData to PolygonMarketData format for compatibility
  const polygonResults: PolygonMarketData[] = results.map(result => ({
    symbol: result.symbol,
    symbolBoolean: result.symbolBoolean,
    marketCap: result.marketCap,
    previousMarketCap: result.previousMarketCap,
    marketCapDiff: result.marketCapDiff,
    marketCapBoolean: result.marketCapBoolean,
    price: result.price,
    previousClose: result.previousCloseAdj || result.previousCloseRaw,
    change: result.change,
    size: result.size,
    name: result.name,
    priceBoolean: result.priceBoolean,
    Boolean: result.Boolean,
    priceSource: result.priceSource,
  }));
  
  console.log(`✓ Completed fetching market cap data for ${symbols.length} symbols using PriceService`);
  return polygonResults;
}
