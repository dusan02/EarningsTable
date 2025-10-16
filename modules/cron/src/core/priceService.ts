import axios from 'axios';
import Decimal from 'decimal.js';
import { CONFIG } from '../../../shared/src/config.js';

// Types
export interface Snapshot {
  ticker?: string;
  preMarket?: { price?: number; timestamp?: number };
  lastTrade?: { p?: number; t?: number };
  afterHours?: { price?: number; timestamp?: number };
  minute?: { c?: number; t?: number };
  day?: { c?: number; t?: number };
  prevDay?: { c?: number };
}

export interface PriceResult {
  price: number | null;
  source: string | null;
  ts: number | null;
}

export interface MarketCapData {
  symbol: string;
  symbolBoolean: boolean;
  marketCap?: bigint | null;
  previousMarketCap?: bigint | null;
  marketCapDiff?: bigint | null;
  marketCapBoolean: boolean;
  price?: number | null;
  previousClose?: number | null;
  change?: number | null;
  size?: string | null;
  name?: string | null;
  priceBoolean: boolean;
  Boolean: boolean;
  priceSource?: string | null;
  marketCapFetchedAt?: Date | null;
}

// In-memory caches with TTL
const prevCloseCache = new Map<string, { v: Map<string, number>; t: number }>();
const sharesCache = new Map<string, { v: number | null; t: number }>();
const snapCache = new Map<string, { v: Snapshot[]; t: number }>();

const DAY = 24 * 60 * 60 * 1000;
const SNAP_TTL = 2 * 60 * 1000; // 2 minutes

// API helper with retry logic
async function apiCall<T>(path: string, params: any = {}): Promise<T> {
  const maxRetries = 2;
  let lastError: any;

  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      const response = await axios.get(`https://api.polygon.io${path}`, {
        params: { apikey: CONFIG.POLYGON_API_KEY, ...params },
        timeout: 7000,
      });
      return response.data;
    } catch (error) {
      lastError = error;
      const status = error.response?.status;
      
      // Only retry on 5xx or 429 errors
      if (status && status < 500 && status !== 429) {
        throw error;
      }
      
      if (attempt < maxRetries - 1) {
        const delay = 300 * (2 ** attempt) + Math.random() * 300;
        await new Promise(resolve => setTimeout(resolve, delay));
      }
    }
  }
  
  throw lastError;
}

// Get NY date for grouped aggs (previous trading day)
function getNYDate(): string {
  const now = new Date();
  const nyTime = new Date(now.toLocaleString("en-US", { timeZone: "America/New_York" }));
  
  console.log(`→ Current NY time: ${nyTime.toISOString()}`);
  
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
  
  const result = nyTime.toISOString().split('T')[0]; // YYYY-MM-DD format
  console.log(`→ Using NY date: ${result}`);
  return result;
}

// 1. Grouped prevClose (1 call per day) - cached for 24h
export async function getPrevCloseMap(nyDateISO?: string): Promise<Map<string, number>> {
  const date = nyDateISO || getNYDate();
  const cacheKey = date;
  const now = Date.now();
  
  // Check cache first
  const cached = prevCloseCache.get(cacheKey);
  if (cached && now - cached.t < DAY) {
    console.log(`→ Using cached prevClose for ${date} (${cached.v.size} symbols)`);
    return cached.v;
  }
  
  console.log(`→ Fetching grouped previous close for ${date}...`);
  
  try {
    const data = await apiCall(`/v2/aggs/grouped/locale/us/market/stocks/${date}`, { adjusted: true });
    const map = new Map<string, number>();
    
    if (data?.results) {
      for (const r of data.results) {
        if (r.T && Number.isFinite(r.c) && r.c > 0) {
          map.set(r.T, Number(r.c));
        }
      }
    }
    
    console.log(`→ Found ${map.size} previous close prices`);
    
    // Cache the result
    prevCloseCache.set(cacheKey, { v: map, t: now });
    
    return map;
  } catch (error) {
    console.log(`→ Grouped aggs failed: ${error.response?.status || 'Unknown error'}`);
    return new Map();
  }
}

// 2. Market cap and company info with 24h cache
export async function getMarketCapInfo(ticker: string): Promise<{ marketCap: number | null; name: string | null; shares: number | null }> {
  const now = Date.now();
  
  // Check cache first
  const cached = sharesCache.get(ticker);
  if (cached && now - cached.t < DAY) {
    return { marketCap: cached.v, name: null, shares: null };
  }
  
  try {
    const data = await apiCall(`/v3/reference/tickers/${ticker}`);
    const marketCap = Number(data?.results?.market_cap) || null;
    const name = data?.results?.name || null;
    const shares = Number(data?.results?.share_class_shares_outstanding) || null;
    
    // Cache the result (even if null)
    sharesCache.set(ticker, { v: marketCap, t: now });
    
    return { marketCap, name, shares };
  } catch (error) {
    console.log(`→ Failed to get market cap info for ${ticker}: ${error.response?.status || error.message}`);
    return { marketCap: null, name: null, shares: null };
  }
}

// 3. Individual snapshots with batching and cache (more reliable than bulk)
export async function getSnapshotsBulk(tickers: string[]): Promise<Snapshot[]> {
  const key = tickers.join(",");
  const now = Date.now();
  
  // Check cache first
  const cached = snapCache.get(key);
  if (cached && now - cached.t < SNAP_TTL) {
    console.log(`→ Using cached snapshots for ${tickers.length} symbols`);
    return cached.v;
  }
  
  console.log(`→ Fetching individual snapshots for ${tickers.length} symbols...`);
  
  const results: Snapshot[] = [];
  const BATCH_SIZE = 10; // Process in small batches to avoid rate limits
  
  // Process in batches
  for (let i = 0; i < tickers.length; i += BATCH_SIZE) {
    const batch = tickers.slice(i, i + BATCH_SIZE);
    
    const batchPromises = batch.map(async (ticker) => {
      try {
        const data = await apiCall(`/v2/snapshot/locale/us/markets/stocks/tickers/${ticker}`);
        
        if (data?.ticker) {
          // Debug: Log raw ticker data for first few symbols
          if (ticker === 'AAPL' || ticker === 'MSFT' || ticker === 'ATLO') {
            console.log(`🔍 Raw ticker data for ${ticker}:`, JSON.stringify(data.ticker, null, 2));
          }
          
          return {
            ticker: ticker,
            preMarket: data.ticker.preMarket,
            lastTrade: data.ticker.lastTrade,
            afterHours: data.ticker.afterHours,
            minute: data.ticker.minute,
            day: data.ticker.day,
            prevDay: data.ticker.prevDay,
          };
        }
        return null;
      } catch (error) {
        console.log(`→ Failed to get snapshot for ${ticker}: ${error.response?.status || error.message}`);
        return null;
      }
    });
    
    const batchResults = await Promise.all(batchPromises);
    results.push(...batchResults.filter(Boolean) as Snapshot[]);
    
    // Small delay between batches
    if (i + BATCH_SIZE < tickers.length) {
      await new Promise(resolve => setTimeout(resolve, 100));
    }
  }
  
  // Cache the result
  snapCache.set(key, { v: results, t: now });
  
  console.log(`→ Retrieved ${results.length} snapshots`);
  return results;
}

// 4. Timestamp normalization
function normTs(n: any): number | null {
  const x = Number(n);
  if (!Number.isFinite(x)) return null;
  
  if (x > 1e17) return Math.floor(x / 1e6); // ns→ms
  if (x > 1e14) return Math.floor(x / 1e3); // μs→ms
  if (x > 1e11) return x;                   // ms
  if (x > 1e9) return x * 1000;            // s→ms
  return null;
}

// 5. Smart price selection (never fallback to previousClose)
export function pickPrice(t: any): PriceResult {
  if (!t) return { price: null, source: null, ts: null };
  
  // Debug: Log the ticker data structure
  console.log(`→ Price candidates for ${t.ticker || 'unknown'}:`, [
    t?.preMarket?.price != null && `pre:${t.preMarket.price} (ts:${t.preMarket.timestamp})`,
    t?.lastTrade?.p != null && `live:${t.lastTrade.p} (ts:${t.lastTrade.t})`,
    t?.afterHours?.price != null && `ah:${t.afterHours.price} (ts:${t.afterHours.timestamp})`,
    t?.minute?.c != null && `min:${t.minute.c} (ts:${t.minute.t})`,
    t?.day?.c != null && `day.c:${t.day.c} (ts:${t.day.t})`,
    t?.prevDay?.c != null && `prevDay.c:${t.prevDay.c}`,
  ].filter(Boolean));
  
  const candidates = [
    t?.preMarket?.price != null && { p: +t.preMarket.price, t: normTs(t.preMarket.timestamp), s: 'pre' },
    t?.lastTrade?.p != null && { p: +t.lastTrade.p, t: normTs(t.lastTrade.t), s: 'live' },
    t?.afterHours?.price != null && { p: +t.afterHours.price, t: normTs(t.afterHours.timestamp), s: 'ah' },
    t?.minute?.c != null && t.minute.c > 0 && { p: +t.minute.c, t: normTs(t.minute.t), s: 'min' },
    t?.day?.c != null && t.day.c > 0 && { p: +t.day.c, t: normTs(t.day.t), s: 'day' },
    t?.prevDay?.c != null && t.prevDay.c > 0 && { p: +t.prevDay.c, t: null, s: 'prevDay' },
  ].filter(Boolean) as { p: number; t: number | null; s: string }[];
  
  // Sort by timestamp (most recent first), then by session priority
  candidates.sort((a, b) => {
    const timeDiff = (b.t ?? -1) - (a.t ?? -1);
    if (timeDiff !== 0) return timeDiff;
    return ['pre', 'live', 'ah', 'min', 'day', 'prevDay'].indexOf(a.s) - ['pre', 'live', 'ah', 'min', 'day', 'prevDay'].indexOf(b.s);
  });
  
  const now = Date.now();
  const best = candidates.find(c => 
    Number.isFinite(c.p) && 
    c.p > 0 && 
    (c.t == null || (c.t <= now + 5 * 60_000 && (now - c.t) <= DAY)) // Allow null timestamps for prevDay
  );
  
  if (best) {
    console.log(`→ Selected price for ${t.ticker || 'unknown'}: ${best.s}:${best.p}`);
  } else {
    console.log(`→ No price found for ${t.ticker || 'unknown'}`);
  }
  
  return best ? { price: best.p, source: best.s, ts: best.t } : { price: null, source: null, ts: null };
}

// 6. Precise calculations with Decimal.js
export function pctChange(current: number, prev: number): number {
  return new Decimal(current).minus(prev).div(prev).times(100).toNumber();
}

export function marketCap(price: number, shares: number): number {
  return new Decimal(price).mul(shares).toNumber();
}

export function marketCapDiff(current: number, prev: number, shares: number): number {
  return new Decimal(current).minus(prev).mul(shares).toNumber();
}

// 7. Company size classification
export function getCompanySize(marketCap: number | null): string | null {
  if (!marketCap) return null;
  
  if (marketCap >= 100_000_000_000) return 'Mega';    // >= $100B
  if (marketCap >= 10_000_000_000) return 'Large';    // >= $10B
  if (marketCap >= 1_000_000_000) return 'Mid';       // >= $1B
  return 'Small';                                      // < $1B
}

// 8. Main function to process symbols with all optimizations
export async function processSymbolsWithPriceService(symbols: string[]): Promise<MarketCapData[]> {
  console.log(`→ Processing ${symbols.length} symbols with PriceService...`);
  
  // Step 1: Get previous close prices (1 call, cached)
  const prevCloseMap = await getPrevCloseMap();
  
  // Step 2: Get bulk snapshots (batched, cached)
  const snapshots = await getSnapshotsBulk(symbols);
  const snapshotMap = new Map<string, Snapshot>();
  snapshots.forEach(snap => {
    if (snap.ticker) {
      snapshotMap.set(snap.ticker, snap);
    }
  });
  
  const results: MarketCapData[] = [];
  
  // Step 3: Process each symbol
  for (const symbol of symbols) {
    try {
      // Get previous close
      const previousClose = prevCloseMap.get(symbol) || null;
      
      // Get current price from snapshot
      const snapshot = snapshotMap.get(symbol);
      const { price, source: priceSource, ts } = pickPrice(snapshot);
      
      // Get market cap info (cached)
      const { marketCap: marketCapFromAPI, name: companyName, shares } = await getMarketCapInfo(symbol);
      
      // Calculate change only if we have both price and previous close
      let change = null;
      if (price != null && previousClose != null) {
        change = pctChange(price, previousClose);
      }
      
      // Use market cap from API if available, otherwise calculate from price and shares
      let marketCapValue = marketCapFromAPI;
      let previousMarketCap = null;
      let marketCapDiff = null;
      
      if (marketCapValue == null && price != null && shares != null) {
        // Calculate market cap from price and shares if not available from API
        marketCapValue = marketCap(price, shares);
      }
      
      if (marketCapValue != null && change != null) {
        // Calculate previous market cap and diff
        previousMarketCap = marketCapValue / (1 + change / 100);
        marketCapDiff = marketCapValue - previousMarketCap;
      }
      
      // Determine company size
      const size = getCompanySize(marketCapValue);
      
      // Calculate boolean flags
      const symbolBoolean = true; // Symbol exists if we're processing it
      const marketCapBoolean = marketCapValue != null;
      const priceBoolean = price != null;
      const allConditionsMet = symbolBoolean && marketCapBoolean && priceBoolean;
      
      results.push({
        symbol,
        symbolBoolean,
        marketCap: marketCapValue ? BigInt(Math.round(marketCapValue)) : null,
        previousMarketCap: previousMarketCap ? BigInt(Math.round(previousMarketCap)) : null,
        marketCapDiff: marketCapDiff ? BigInt(Math.round(marketCapDiff)) : null,
        marketCapBoolean,
        price,
        previousClose,
        change,
        size,
        name: companyName || snapshot?.name || null,
        priceBoolean,
        Boolean: allConditionsMet,
        priceSource,
        marketCapFetchedAt: new Date()
      });
      
    } catch (error) {
      console.log(`→ Error processing ${symbol}: ${error.message}`);
      
      // Add failed symbol with minimal data
      results.push({
        symbol,
        symbolBoolean: false,
        marketCap: null,
        previousMarketCap: null,
        marketCapDiff: null,
        marketCapBoolean: false,
        price: null,
        previousClose: prevCloseMap.get(symbol) || null,
        change: null,
        size: null,
        name: null,
        priceBoolean: false,
        Boolean: false,
        priceSource: null,
        marketCapFetchedAt: new Date()
      });
    }
  }
  
  console.log(`✓ Processed ${results.length} symbols with PriceService`);
  return results;
}

// 9. Batch processing for large symbol lists
export async function processSymbolsInBatches(
  symbols: string[], 
  batchSize: number = 80, 
  concurrency: number = 10
): Promise<MarketCapData[]> {
  console.log(`→ Processing ${symbols.length} symbols in batches of ${batchSize} with concurrency ${concurrency}...`);
  
  const results: MarketCapData[] = [];
  
  // Process in batches
  for (let i = 0; i < symbols.length; i += batchSize) {
    const batch = symbols.slice(i, i + batchSize);
    console.log(`→ Processing batch ${Math.floor(i / batchSize) + 1}/${Math.ceil(symbols.length / batchSize)} (${batch.length} symbols)...`);
    
    const batchResults = await processSymbolsWithPriceService(batch);
    results.push(...batchResults);
    
    // Small delay between batches to prevent overwhelming the API
    if (i + batchSize < symbols.length) {
      await new Promise(resolve => setTimeout(resolve, 100));
    }
  }
  
  console.log(`✓ Completed batch processing for ${symbols.length} symbols`);
  return results;
}
