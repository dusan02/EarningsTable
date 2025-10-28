import axios from 'axios';
import http from 'http';
import https from 'https';
import Decimal from 'decimal.js';
import { DateTime } from 'luxon';
import { CONFIG } from '../../../shared/src/config.js';
import pLimit from 'p-limit';

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
  session: 'premarket' | 'regular' | 'afterhours' | null;
}

type PrevClosePick = { 
  raw: number | null; 
  adj: number | null; 
  source: "snapshot-prevday" | "grouped-adjusted" | null;
};

function mapKeyForPrevClose(symbol: string): string {
  // prevCloseMap keys are canonical (e.g., BRK.B)
  return getCanonicalSymbol(normalizeToPolygonTicker(symbol));
}

function pickPreviousClose(snapshot: any, prevCloseMap: Map<string, number>, symbol: string): PrevClosePick {
  // Try to get both raw and adjusted from snapshot
  const snapPrevRaw = (snapshot?.prevDay?.c != null && snapshot.prevDay.c > 0)
    ? Number(snapshot.prevDay.c) : null;

  // Snapshot prevDay is not guaranteed to be adjusted
  const snapPrevAdj = snapPrevRaw;

  if (snapPrevRaw != null) {
    return { 
      raw: snapPrevRaw, 
      adj: snapPrevAdj, 
      source: "snapshot-prevday" 
    };
  }

  // Fallback to grouped aggregates (these are typically adjusted)
  const mapPrev = prevCloseMap.get(mapKeyForPrevClose(symbol)) ?? null;
  return { 
    raw: mapPrev, 
    adj: mapPrev, 
    source: mapPrev != null ? "grouped-adjusted" : null 
  };
}

// Helper function to pick the best previous close for percentage calculation
function pickPrevCloseForPct(pc: PrevClosePick): number | null {
  // Prefer adjusted, fallback to raw
  return Number.isFinite(pc.adj) ? pc.adj : (Number.isFinite(pc.raw) ? pc.raw : null);
}

export interface MarketCapData {
  symbol: string;
  symbolBoolean: boolean;
  marketCap?: bigint | null;
  previousMarketCap?: bigint | null;
  marketCapDiff?: bigint | null;
  marketCapBoolean: boolean;
  price?: number | null;
  previousCloseRaw?: number | null;
  previousCloseAdj?: number | null;
  previousCloseSource?: string | null;
  changeFromPrevClosePct?: number | null;
  changeFromOpenPct?: number | null;
  sessionRef?: 'premarket' | 'regular' | 'afterhours' | null;
  qualityFlags?: string[]; // Array of quality flags
  change?: number | null; // Keep for backward compatibility
  size?: string | null;
  name?: string | null;
  priceBoolean: boolean;
  Boolean: boolean;
  priceSource?: string | null;
}

// In-memory caches with TTL
const prevCloseCache = new Map<string, { v: Map<string, number>; t: number }>();
const tickerInfoCache = new Map<string, { v: { marketCap: string | number | null; name: string | null; shares: string | number | null }; t: number }>();
const snapCache = new Map<string, { v: Snapshot[]; t: number }>();

const DAY = 24 * 60 * 60 * 1000;
const SNAP_TTL = 5 * 60 * 1000; // 5 minutes (increased from 2)
const NULL_TTL = 15 * 60 * 1000; // 15 minutes for null/partial ticker info

// API helper with retry logic
const httpAgent = new http.Agent({ keepAlive: true, maxSockets: 50 });
const httpsAgent = new https.Agent({ keepAlive: true, maxSockets: 50 });

async function apiCall<T>(path: string, params: any = {}): Promise<T> {
  const maxRetries = 2;
  let lastError: any;

  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      const response = await axios.get(`https://api.polygon.io${path}`, {
        params: { apikey: CONFIG.POLYGON_API_KEY, ...params },
        timeout: 10000,
        headers: { 'User-Agent': 'EarningsTable/1.0 (+price-service)' },
        httpAgent,
        httpsAgent,
      });
      return response.data;
    } catch (error: any) {
      lastError = error;
      const status = error.response?.status;
      
      // Only retry on 5xx or 429 errors
      if (status && status < 500 && status !== 429) {
        throw error;
      }
      
      if (attempt < maxRetries - 1) {
        // Check for Retry-After header first
        const retryAfter = Number(error.response?.headers?.['retry-after']);
        const base = 1000; // 1s base
        const delay = Number.isFinite(retryAfter) 
          ? retryAfter * 1000  // Convert seconds to milliseconds
          : base * (2 ** attempt) + Math.floor(Math.random() * base);  // Exponential backoff with jitter (seconds)
        
        console.log(`⏳ Retry attempt ${attempt + 1}/${maxRetries} after ${delay}ms (${Number.isFinite(retryAfter) ? 'server-recommended' : 'exponential backoff'})`);
        await new Promise(resolve => setTimeout(resolve, delay));
      }
    }
  }
  
  throw lastError;
}

// Get NY date for grouped aggs (previous trading day)
// Get current time in New York timezone
function getNYTime(): Date {
  const nyTime = DateTime.now().setZone('America/New_York');
  return nyTime.toJSDate();
}
  
// Get current trading day in NY timezone
function getNYDate(): string {
  const nyTime = getNYTime();
  console.log(`→ Current NY time: ${nyTime.toLocaleString('en-US', { timeZone: 'America/New_York' })}`);
  
  // If weekend, jump to Friday and return immediately
  if (nyTime.getDay() === 0) { // Sunday
    nyTime.setDate(nyTime.getDate() - 2);
    nyTime.setHours(12, 0, 0, 0);
    const result = nyTime.toISOString().split('T')[0];
    console.log(`→ Using NY date (Sunday → Friday): ${result}`);
    return result;
  }
  if (nyTime.getDay() === 6) { // Saturday
    nyTime.setDate(nyTime.getDate() - 1);
    nyTime.setHours(12, 0, 0, 0);
    const result = nyTime.toISOString().split('T')[0];
    console.log(`→ Using NY date (Saturday → Friday): ${result}`);
    return result;
  }

  // Minutes since midnight to check session open precisely (9:30 = 570)
  const minutesSinceMidnight = nyTime.getHours() * 60 + nyTime.getMinutes();
  if (minutesSinceMidnight < 570) {
    // Before regular session open → use previous trading day
    const today = nyTime.toISOString().split('T')[0];
    const prev = getPreviousTradingDayNY(today);
    console.log(`→ Using NY date (pre-open → prev trading day): ${prev}`);
    return prev;
  }

  const result = nyTime.toISOString().split('T')[0];
  console.log(`→ Using NY date: ${result}`);
  return result;
}

// Get previous trading day in NY timezone
function getPreviousTradingDayNY(dateStr: string): string {
  // Parse the provided YYYY-MM-DD as UTC midnight to avoid host-local TZ shifts
  const dateUtc = new Date(Date.UTC(
    Number(dateStr.slice(0, 4)),
    Number(dateStr.slice(5, 7)) - 1,
    Number(dateStr.slice(8, 10))
  ));

  // Move back one day and then skip weekends deterministically
  const prevUtc = new Date(dateUtc);
  prevUtc.setUTCDate(prevUtc.getUTCDate() - 1);

  while (prevUtc.getUTCDay() === 0 || prevUtc.getUTCDay() === 6) {
    prevUtc.setUTCDate(prevUtc.getUTCDate() - 1);
  }

  // Return ISO date part in YYYY-MM-DD
  return prevUtc.toISOString().split('T')[0];
}

// Check if current time is in pre-market, regular, or after-hours session
function getCurrentSession(): 'premarket' | 'regular' | 'afterhours' {
  const nyTime = getNYTime();
  const hour = nyTime.getHours();
  const minute = nyTime.getMinutes();
  const day = nyTime.getDay();
  
  // Debug logging
  if (process.env.DEBUG_TICKER) {
    console.log(`→ getCurrentSession: nyTime=${nyTime.toISOString()}, day=${day}, hour=${hour}, minute=${minute}`);
  }
  
  // Weekend - no trading
  if (day === 0 || day === 6) {
    return 'afterhours'; // Treat as after-hours
  }
  
  // Convert to minutes since midnight for precise comparison
  const minutesSinceMidnight = hour * 60 + minute;
  
  // Pre-market: 4:00 AM - 9:29 AM ET (4:00 = 240 min, 9:29 = 569 min)
  if (minutesSinceMidnight >= 240 && minutesSinceMidnight < 570) {
    return 'premarket';
  }
  
  // Regular hours: 9:30 AM - 3:59 PM ET (9:30 = 570 min, 15:59 = 959 min)
  if (minutesSinceMidnight >= 570 && minutesSinceMidnight < 960) {
    return 'regular';
  }
  
  // After-hours: 4:00 PM - 8:00 PM ET (16:00 = 960 min, 20:00 = 1200 min)
  if (minutesSinceMidnight >= 960 && minutesSinceMidnight < 1200) {
    return 'afterhours';
  }
  
  // Outside trading hours (before 4 AM or after 8 PM)
  return 'afterhours';
}

// Normalize ticker symbol for Polygon API
function normalizeToPolygonTicker(symbol: string): string {
  // Only remap class tickers like BRK.A → BRK-A and BRK.B → BRK-B
  const m = symbol.toUpperCase().match(/^([A-Z]+)\.(A|B)$/);
  if (m) return `${m[1]}-${m[2]}`;
  return symbol.toUpperCase();
}

// Get canonical symbol (original format)
function getCanonicalSymbol(normalizedSymbol: string): string {
  // Only remap class tickers like BRK-A → BRK.A and BRK-B → BRK.B
  const m = normalizedSymbol.toUpperCase().match(/^([A-Z]+)-(A|B)$/);
  if (m) return `${m[1]}.${m[2]}`;
  return normalizedSymbol.toUpperCase();
}

// Safe percentage calculation with guards using Decimal.js
function safePctChange(current: number | null, previous: number | null): number | null {
  // Guard against invalid inputs
  if (!Number.isFinite(current) || !Number.isFinite(previous)) {
    return null;
  }
  
  // Guard against zero or very low previous close (penny stocks, halts, etc.)
  if (previous! <= 1e-4) {
    return null;
  }
  
  // Guard against negative prices
  if (current! <= 0 || previous! <= 0) {
    return null;
  }
  
  try {
    // Calculate percentage change using Decimal.js for precision
    const currentDecimal = new Decimal(current!);
    const previousDecimal = new Decimal(previous!);
    const change = currentDecimal.minus(previousDecimal).div(previousDecimal).times(100);
    
    // Do not hard-cap; spike handling is done via corporate actions + quality flags
    
    // Round to 4 decimal places for consistency
    return change.toDecimalPlaces(4).toNumber();
  } catch (error) {
    console.log(`→ Decimal calculation error for ${current} vs ${previous}: ${error}`);
    return null;
  }
}

// Corporate actions types
type CorporateAction = {
  symbol: string;
  date: string;
  type: 'split' | 'dividend' | 'spinoff' | 'merger';
  ratio?: number; // For splits (e.g., 2 for 2:1 split)
  amount?: number; // For dividends
};

// Cache for corporate actions
const corporateActionsCache = new Map<string, { v: CorporateAction[]; t: number }>();
const CORPORATE_ACTIONS_TTL = 7 * 24 * 60 * 60 * 1000; // 7 days

// Fetch corporate actions for a symbol (last 14 days)
async function getCorporateActions(symbol: string): Promise<CorporateAction[]> {
  const cacheKey = symbol;
  const now = Date.now();
  
  // Check cache first
  const cached = corporateActionsCache.get(cacheKey);
  if (cached && now - cached.t < CORPORATE_ACTIONS_TTL) {
    return cached.v;
  }
  
  try {
    // Normalize ticker for Polygon API
    const normalizedTicker = normalizeToPolygonTicker(symbol);
    const dateFrom = new Date(Date.now() - 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    const actions: CorporateAction[] = [];
    
    // Get dividends
    try {
      const dividendData = await apiCall(`/v3/reference/dividends`, {
        ticker: normalizedTicker,
        'ex_dividend_date.gte': dateFrom,
        limit: 10
      });
      
      if ((dividendData as any)?.results) {
        for (const result of (dividendData as any).results) {
          if (result.ex_dividend_date && result.cash_amount) {
            actions.push({
              symbol,
              date: result.ex_dividend_date,
              type: 'dividend',
              amount: Number(result.cash_amount)
            });
          }
        }
      }
    } catch (error) {
      console.log(`→ Failed to get dividends for ${symbol}: ${(error as any).response?.status || (error as any).message}`);
    }
    
    // Get stock splits
    try {
      const splitData = await apiCall(`/v3/reference/splits`, {
        ticker: normalizedTicker,
        'execution_date.gte': dateFrom,
        limit: 10
      });
      
      if ((splitData as any)?.results) {
        for (const result of (splitData as any).results) {
          if (result.execution_date && result.split_from && result.split_to) {
            const ratio = Number(result.split_to) / Number(result.split_from);
            actions.push({
              symbol,
              date: result.execution_date,
              type: 'split',
              ratio: ratio
            });
          }
        }
      }
    } catch (error) {
      console.log(`→ Failed to get splits for ${symbol}: ${(error as any).response?.status || (error as any).message}`);
    }
    
    // Cache the result
    corporateActionsCache.set(cacheKey, { v: actions, t: now });
    
    return actions;
  } catch (error) {
    console.log(`→ Failed to get corporate actions for ${symbol}: ${(error as any).response?.status || (error as any).message}`);
    return [];
  }
}

// Check if there were recent corporate actions that might explain large price changes
async function checkRecentCorporateActions(symbol: string, changePct: number | null): Promise<boolean> {
  // Only check for large changes
  if (!changePct || Math.abs(changePct) < 50) {
    return false;
  }
  
  const actions = await getCorporateActions(symbol);
  const now = new Date();
  const sevenDaysAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
  
  // Check if there were any corporate actions in the last 7 days
  return actions.some(action => {
    const actionDate = new Date(action.date);
    return actionDate >= sevenDaysAgo;
  });
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
    
    if ((data as any)?.results) {
      for (const r of (data as any).results) {
        if (r.T && Number.isFinite(r.c) && r.c > 0) {
          // Convert Polygon ticker format (BRK-B) back to canonical format (BRK.B)
          const canonicalTicker = getCanonicalSymbol(r.T);
          map.set(canonicalTicker, Number(r.c));
        }
      }
    }
    
    console.log(`→ Found ${map.size} previous close prices`);
    
    // If no data found for current date, try previous trading day
    if (map.size === 0) {
      console.log(`→ No data for ${date}, trying previous trading day...`);
      const prevDate = getPreviousTradingDayNY(date);
      if (prevDate !== date) {
        console.log(`→ Fetching grouped previous close for ${prevDate}...`);
        const prevData = await apiCall(`/v2/aggs/grouped/locale/us/market/stocks/${prevDate}`, { adjusted: true });
        
        if ((prevData as any)?.results) {
          for (const r of (prevData as any).results) {
            if (r.T && Number.isFinite(r.c) && r.c > 0) {
              // Convert Polygon ticker format (BRK-B) back to canonical format (BRK.B)
              const canonicalTicker = getCanonicalSymbol(r.T);
              map.set(canonicalTicker, Number(r.c));
            }
          }
        }
        console.log(`→ Found ${map.size} previous close prices from ${prevDate}`);
      }
    }
    
    // Cache the result
    prevCloseCache.set(cacheKey, { v: map, t: now });
    
    return map;
  } catch (error) {
    console.log(`→ Grouped aggs failed: ${(error as any).response?.status || 'Unknown error'}`);
    return new Map();
  }
}

// 2. Market cap and company info with 24h cache
export async function getMarketCapInfo(ticker: string): Promise<{ marketCap: string | number | null; name: string | null; shares: string | number | null }> {
  const now = Date.now();
  
  // Check cache first
  const cached = tickerInfoCache.get(ticker);
  if (cached) {
    const hasNullCore = (cached.v.marketCap == null) || (cached.v.shares == null);
    const ttl = hasNullCore ? NULL_TTL : DAY;
    if (now - cached.t < ttl) {
      return cached.v; // Return cached object within TTL
    }
  }
  
  try {
    // Normalize ticker for Polygon API
    const normalizedTicker = normalizeToPolygonTicker(ticker);
    const data = await apiCall(`/v3/reference/tickers/${normalizedTicker}`);
    const mcRaw = (data as any)?.results?.market_cap ?? null;
    const name = (data as any)?.results?.name ?? null;
    const shRaw = (data as any)?.results?.share_class_shares_outstanding ?? null;
    // Preserve as string/number; use Decimal at call site for precision
    const result = { 
      marketCap: mcRaw ?? null, 
      name, 
      shares: shRaw ?? null 
    };
    
    // Cache the result with different TTL handling on read
    tickerInfoCache.set(ticker, { v: result, t: now });
    
    return result;
  } catch (error) {
    console.log(`→ Failed to get market cap info for ${ticker} (normalized: ${normalizeToPolygonTicker(ticker)}): ${(error as any).response?.status || (error as any).message}`);
    const result = { marketCap: null, name: null, shares: null };
    
    // Cache the error result too
    tickerInfoCache.set(ticker, { v: result, t: now });
    
    return result;
  }
}

// 3. Optimized snapshots with higher concurrency
export async function getSnapshotsBulkAPI(tickers: string[]): Promise<Snapshot[]> {
  const key = tickers.map(t => t.toUpperCase()).sort().join(",");
  const now = Date.now();
  
  // Check cache first
  const cached = snapCache.get(key);
  if (cached && now - cached.t < SNAP_TTL) {
    console.log(`→ Using cached snapshots for ${tickers.length} symbols`);
    return cached.v;
  }
  
  console.log(`→ Fetching individual snapshots for ${tickers.length} symbols...`);
  
  // Use individual snapshots with higher concurrency
  return getSnapshotsIndividual(tickers);
}

// 3b. Individual snapshots with batching and cache (fallback method)
async function getSnapshotsIndividual(tickers: string[]): Promise<Snapshot[]> {
  const key = tickers.map(t => t.toUpperCase()).sort().join(",");
  const now = Date.now();
  
  // Check cache first
  const cached = snapCache.get(key);
  if (cached && now - cached.t < SNAP_TTL) {
    console.log(`→ Using cached snapshots for ${tickers.length} symbols`);
    return cached.v;
  }
  
  console.log(`→ Fetching individual snapshots for ${tickers.length} symbols...`);
  
  const results: Snapshot[] = [];
  const BATCH_SIZE = CONFIG.SNAPSHOT_BATCH_SIZE || 50; // tunable
  const limit = pLimit(CONFIG.SNAPSHOT_TICKER_CONCURRENCY || 12);
  
  // Process in batches
  for (let i = 0; i < tickers.length; i += BATCH_SIZE) {
    const batch = tickers.slice(i, i + BATCH_SIZE);
    
    const batchPromises = batch.map((ticker) => limit(async () => {
      try {
        // Normalize ticker for Polygon API
        const normalizedTicker = normalizeToPolygonTicker(ticker);
        const data = await apiCall(`/v2/snapshot/locale/us/markets/stocks/tickers/${normalizedTicker}`);
        
        if ((data as any)?.ticker) {
          // Optional debug for a specific ticker via env DEBUG_TICKER
          if (process.env.DEBUG_TICKER && ticker === process.env.DEBUG_TICKER) {
            console.log(`🔍 Raw ticker data for ${ticker} (normalized: ${normalizedTicker}):`, JSON.stringify((data as any).ticker, null, 2));
          }
          
          return {
            ticker: ticker, // Keep original ticker format
            preMarket: (data as any).ticker.preMarket,
            lastTrade: (data as any).ticker.lastTrade,
            afterHours: (data as any).ticker.afterHours,
            minute: (data as any).ticker.minute || (data as any).ticker.min, // Map 'min' to 'minute'
            day: (data as any).ticker.day,
            prevDay: (data as any).ticker.prevDay,
          };
        }
        return null;
      } catch (error) {
        console.log(`→ Failed to get snapshot for ${ticker}: ${(error as any).response?.status || (error as any).message}`);
        return null;
      }
    }));
    
    const batchResults = await Promise.all(batchPromises);
    results.push(...batchResults.filter(Boolean) as Snapshot[]);
    
    // Small delay between batches (tunable)
    if (i + BATCH_SIZE < tickers.length) {
      const delay = (CONFIG.SNAPSHOT_BATCH_DELAY_MS ?? 50) + Math.floor(Math.random() * 50);
      await new Promise(resolve => setTimeout(resolve, delay));
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

// 5. Smart price selection (with controlled fallback to previousClose only in after-hours)
export function pickPrice(t: any): PriceResult {
  if (!t) return { price: null, source: null, ts: null, session: null };
  
  // Optional debug for a specific ticker via env DEBUG_TICKER
  if (process.env.DEBUG_TICKER && t.ticker === process.env.DEBUG_TICKER) {
  console.log(`→ Price candidates for ${t.ticker || 'unknown'}:`, [
    t?.preMarket?.price != null && `pre:${t.preMarket.price} (ts:${t.preMarket.timestamp})`,
    t?.lastTrade?.p != null && `live:${t.lastTrade.p} (ts:${t.lastTrade.t})`,
    t?.afterHours?.price != null && `ah:${t.afterHours.price} (ts:${t.afterHours.timestamp})`,
    t?.minute?.c != null && `min:${t.minute.c} (ts:${t.minute.t})`,
    t?.day?.c != null && `day.c:${t.day.c} (ts:${t.day.t})`,
    t?.prevDay?.c != null && `prevDay.c:${t.prevDay.c}`,
  ].filter(Boolean));
  }
  
  // Get current session to determine if we should allow prevDay fallback
  const currentSession = getCurrentSession();
  // Allow prevDay fallback if explicitly enabled via env flag, otherwise only during after-hours
  const allowPrevDayFallback = (process.env.ALLOW_PREV_CLOSE_FALLBACK === 'true') || (currentSession === 'afterhours');
  
  const candidates = [
    t?.preMarket?.price != null && { p: +t.preMarket.price, t: normTs(t.preMarket.timestamp), s: 'pre', session: 'premarket' as const },
    t?.lastTrade?.p != null && { p: +t.lastTrade.p, t: normTs(t.lastTrade.t), s: 'live', session: 'regular' as const },
    t?.afterHours?.price != null && { p: +t.afterHours.price, t: normTs(t.afterHours.timestamp), s: 'ah', session: 'afterhours' as const },
    t?.minute?.c != null && t.minute.c > 0 && { p: +t.minute.c, t: normTs(t.minute.t), s: 'min', session: 'regular' as const },
    t?.day?.c != null && t.day.c > 0 && { p: +t.day.c, t: normTs(t.day.t), s: 'day', session: 'regular' as const },
    // Only include prevDay as fallback during after-hours when no other price is available
    allowPrevDayFallback && t?.prevDay?.c != null && t.prevDay.c > 0 && { p: +t.prevDay.c, t: null, s: 'prevDay', session: null },
  ].filter(Boolean) as { p: number; t: number | null; s: string; session: 'premarket' | 'regular' | 'afterhours' | null }[];
  
  // currentSession already defined above
  
  // Sort by session relevance first, then by timestamp, then by source priority
  candidates.sort((a, b) => {
    // Prioritize prices from current session
    const aSessionMatch = a.session === currentSession ? 0 : 1;
    const bSessionMatch = b.session === currentSession ? 0 : 1;
    if (aSessionMatch !== bSessionMatch) return aSessionMatch - bSessionMatch;
    
    // Then by timestamp (most recent first)
    const timeDiff = (b.t ?? -1) - (a.t ?? -1);
    if (timeDiff !== 0) return timeDiff;
    
    // Finally by source priority (session-aware default ordering)
    // Prioritize live/min data over day data to get most current prices
    const orderBySession: Record<'premarket' | 'regular' | 'afterhours', string[]> = {
      premarket: ['pre', 'live', 'min', 'ah', 'day', 'prevDay'],
      regular:   ['live', 'min', 'ah', 'day', 'pre', 'prevDay'],
      afterhours:['ah', 'live', 'min', 'day', 'pre', 'prevDay'],
    };
    const order = orderBySession[currentSession] || ['pre', 'live', 'ah', 'min', 'day', 'prevDay'];
    return order.indexOf(a.s) - order.indexOf(b.s);
  });
  
  const now = Date.now();
  const best = candidates.find(c => {
    const isValidPrice = Number.isFinite(c.p) && c.p > 0;
    
    // More lenient timestamp validation for current session
    let isValidTimestamp = c.t != null; // require timestamp for normal candidates
    if (c.t != null) {
      if (c.session === currentSession) {
        // For current session, be more lenient with min/live data (up to 30 minutes old)
        const maxAge = (c.s === 'min' || c.s === 'live') ? 30 * 60_000 : 20 * 60_000;
        isValidTimestamp = c.t <= now + 30 * 1000 && (now - c.t) <= maxAge;
      } else {
        // For other sessions, use stricter validation
        isValidTimestamp = c.t <= now + 30 * 1000 && (now - c.t) <= DAY;
      }
    } else if (c.s === 'prevDay' && allowPrevDayFallback) {
      // prevDay has no timestamp; allow only as last-resort when explicitly allowed
      isValidTimestamp = true;
    }
    
    // Optional per-ticker debug
    if (process.env.DEBUG_TICKER && t.ticker === process.env.DEBUG_TICKER) {
      console.log(`→ Candidate ${c.s}: price=${c.p}, ts=${c.t}, session=${c.session}, currentSession=${currentSession}, isValidPrice=${isValidPrice}, isValidTimestamp=${isValidTimestamp}`);
    }
    
    return isValidPrice && isValidTimestamp;
  });
  
  if (best) {
    console.log(`→ Selected price for ${t.ticker || 'unknown'}: ${best.s}:${best.p}`);
  } else {
    console.log(`→ No price found for ${t.ticker || 'unknown'}`);
  }
  
  return best ? { price: best.p, source: best.s, ts: best.t, session: best.session } : { price: null, source: null, ts: null, session: null };
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
export function getCompanySizeFromBigInt(mc: bigint | null): string | null {
  if (mc == null) return null;
  const B = 1_000_000_000n;           // 1B
  const T = 1_000_000_000_000n;       // 1T
  if (mc >= 100n * B * B) return 'Mega';  // >= $100B
  if (mc >= 10n * B * B)  return 'Large'; // >= $10B
  if (mc >= 1n * B * B)   return 'Mid';   // >= $1B
  return 'Small';                         // < $1B
}

// 8. Main function to process symbols with all optimizations
export async function processSymbolsWithPriceService(symbols: string[]): Promise<MarketCapData[]> {
  console.log(`→ Processing ${symbols.length} symbols with PriceService...`);
  
  // Step 1: Get previous close prices (1 call, cached)
  const prevCloseMap = await getPrevCloseMap();
  
  // Step 2: Get bulk snapshots (optimized with fallback)
  const snapshots = await getSnapshotsBulkAPI(symbols);
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
      // Get current price from snapshot
      const snapshot = snapshotMap.get(symbol);
      
      // Optional per-ticker debug
      if (process.env.DEBUG_TICKER && symbol === process.env.DEBUG_TICKER) {
        console.log(`→ Snapshot from map:`, snapshot);
        console.log(`→ snapshotMap keys:`, Array.from(snapshotMap.keys()).slice(0, 10));
      }
      
      const priceResult = pickPrice(snapshot);
      const selectedPrice = priceResult.price;
      const priceSource = priceResult.source;
      const sessionRef = priceResult.session;

      const prevCloseData = pickPreviousClose(snapshot, prevCloseMap, symbol);
      const previousCloseForSymbol = pickPrevCloseForPct(prevCloseData);

      // Calculate different types of percentage changes with guards
      const changeFromPrevClosePct = safePctChange(selectedPrice, previousCloseForSymbol);

      // For now, we don't have open price data, so set to null
      // TODO: Implement open price fetching from Polygon
      const changeFromOpenPct = null;

      // Check for corporate actions if there's a large price change
      let hasRecentCorporateActions = false;
  if (changeFromPrevClosePct != null && Math.abs(changeFromPrevClosePct) > 50) {
        hasRecentCorporateActions = await checkRecentCorporateActions(symbol, changeFromPrevClosePct);
      }

      // Keep backward compatibility
      const changePctForDb = changeFromPrevClosePct;
      
      console.log(`→ ${symbol}: price=${selectedPrice}, prevCloseRaw=${prevCloseData.raw}, prevCloseAdj=${prevCloseData.adj} (${prevCloseData.source}), change=${changePctForDb?.toFixed(2)}%`);
      
      // Get market cap info (cached)
      const { marketCap: marketCapFromAPI, name: companyName, shares } = await getMarketCapInfo(symbol);
      
      // --- market cap & diff calculation (robust) ---
      let marketCapValueDecimal: Decimal | null = null;
      let previousMarketCapDecimal: Decimal | null = null;
      let marketCapDiffDecimal: Decimal | null = null;

      const d = (x: number | string | bigint) => new Decimal(String(x));

      // 1) Prefer current market cap: from API; else from price*shares if both present
      if (marketCapFromAPI != null) {
        marketCapValueDecimal = d(marketCapFromAPI);
      } else if (selectedPrice != null && shares != null) {
        marketCapValueDecimal = d(selectedPrice).mul(d(shares));
      }

      // 2) Previous MC: prefer prevClose * shares
      if (previousCloseForSymbol != null && shares != null) {
        previousMarketCapDecimal = d(previousCloseForSymbol).mul(d(shares));
      }

      // 3) If still missing previous MC but we know current MC and change% → previous = current/(1+chg)
      if (!previousMarketCapDecimal && marketCapValueDecimal && changePctForDb != null) {
        const denom = d(1).plus(d(changePctForDb).div(100));
        if (!denom.isZero()) {
          previousMarketCapDecimal = marketCapValueDecimal.div(denom);
        }
      }

      // 4) If no shares but have current MC and both prices (curr and prev) → estimate shares
      if (!previousMarketCapDecimal && marketCapValueDecimal && selectedPrice != null && selectedPrice > 0 && previousCloseForSymbol != null) {
        const estShares = marketCapValueDecimal.div(d(selectedPrice));
        if (estShares.isFinite() && estShares.greaterThan(0)) {
          previousMarketCapDecimal = d(previousCloseForSymbol).mul(estShares);
        }
      }

      // 5) Compose diff if both MCs are present
      if (marketCapValueDecimal && previousMarketCapDecimal) {
        marketCapDiffDecimal = marketCapValueDecimal.minus(previousMarketCapDecimal);
      }

      // 5b) Consistency check: if we have current MC and % change and
      //     the computed diff sign disagrees with the price change sign,
      //     recompute previous MC from change% to ensure basis alignment.
      if (
        marketCapValueDecimal &&
        changePctForDb != null &&
        marketCapDiffDecimal &&
        !marketCapDiffDecimal.isZero()
      ) {
        const diffSign = marketCapDiffDecimal.isNegative() ? -1 : 1;
        const chSign = changePctForDb === 0 ? 0 : (changePctForDb < 0 ? -1 : 1);
        if (chSign !== 0 && diffSign !== chSign) {
          const denom = new Decimal(1).plus(new Decimal(changePctForDb).div(100));
          if (!denom.isZero()) {
            previousMarketCapDecimal = marketCapValueDecimal.div(denom);
            marketCapDiffDecimal = marketCapValueDecimal.minus(previousMarketCapDecimal);
          }
        }
      }

      // 6) Edge: change == 0 and current MC present → diff = 0
      const EPS = 1e-9;
      if (!marketCapDiffDecimal && marketCapValueDecimal && changePctForDb != null && Math.abs(changePctForDb) < EPS) {
        marketCapDiffDecimal = d(0);
      }

      // 7) Convert to outputs (BigInt only at the end)
      const mcOut  = marketCapValueDecimal  ? BigInt(marketCapValueDecimal.toFixed(0))   : null;
      const pmcOut = previousMarketCapDecimal ? BigInt(previousMarketCapDecimal.toFixed(0)) : null;
      let mcdOut = marketCapDiffDecimal   ? BigInt(marketCapDiffDecimal.toFixed(0))    : null;

      // SIMPLE FALLBACK: if diff still missing but we have previous MC and change% → compute directly
      if (!mcdOut && pmcOut != null && changePctForDb != null && Number.isFinite(changePctForDb) && Math.abs(changePctForDb) < 5000) {
        const diffDec = new Decimal(pmcOut.toString()).times(new Decimal(changePctForDb).div(100));
        mcdOut = BigInt(diffDec.toFixed(0));
      }

      // Determine company size using BigInt directly (no precision loss)
      const size = getCompanySizeFromBigInt(mcOut);
      
      // Calculate boolean flags
      const symbolBoolean = true; // Symbol exists if we're processing it
      const marketCapBoolean = mcOut != null;
      const priceBoolean = selectedPrice != null;
      const allConditionsMet = symbolBoolean && marketCapBoolean && priceBoolean;
      
      // Generate quality flags
      const qualityFlags: string[] = [];
      if (changeFromPrevClosePct === null) qualityFlags.push('no_prevclose_for_pct');
      if (Math.abs(changeFromPrevClosePct ?? 0) > 50 && !hasRecentCorporateActions) qualityFlags.push('pct_spike_no_ca');
      if (!selectedPrice) qualityFlags.push('no_current_price');
      if (previousCloseForSymbol && previousCloseForSymbol <= 1e-4) qualityFlags.push('low_prevclose');
      if (hasRecentCorporateActions) qualityFlags.push('recent_corporate_action');
      
      // quality flags enrich
      if (!mcdOut) {
        if (!marketCapValueDecimal) qualityFlags.push('no_current_mc');
        if (!previousMarketCapDecimal) qualityFlags.push('no_previous_mc');
        if (changePctForDb == null) qualityFlags.push('no_change_pct');
        if (shares == null) qualityFlags.push('no_shares');
        if (selectedPrice == null) qualityFlags.push('no_price');
        if (previousCloseForSymbol == null) qualityFlags.push('no_prev_close');
      }
      
      results.push({
        symbol,
        symbolBoolean,
        marketCap: mcOut,
        previousMarketCap: pmcOut,
        marketCapDiff: mcdOut,
        marketCapBoolean: mcOut != null,
        price: selectedPrice ?? null,
        previousCloseRaw: prevCloseData.raw,
        previousCloseAdj: prevCloseData.adj,
        previousCloseSource: prevCloseData.source,
        changeFromPrevClosePct,
        changeFromOpenPct,
        sessionRef,
        qualityFlags: qualityFlags ? JSON.stringify(qualityFlags) : null as any,
        change: changePctForDb, // Keep for backward compatibility
        size,
        name: companyName || (snapshot as any)?.name || null,
        priceBoolean,
        Boolean: allConditionsMet,
        priceSource
      });
      
    } catch (error) {
      console.log(`→ Error processing ${symbol}: ${(error as any).message}`);
      
      // Add failed symbol with minimal data - use fallback previousClose from map
      const fallbackPrevClose = prevCloseMap.get(mapKeyForPrevClose(symbol)) || null;
      results.push({
        symbol,
        symbolBoolean: false,
        marketCap: null,
        previousMarketCap: null,
        marketCapDiff: null,
        marketCapBoolean: false,
        price: null,
        previousCloseRaw: fallbackPrevClose,
        previousCloseAdj: fallbackPrevClose,
        previousCloseSource: fallbackPrevClose ? "grouped-adjusted" : null,
        changeFromPrevClosePct: null,
        changeFromOpenPct: null,
        sessionRef: null,
        qualityFlags: ['processing_error'],
        change: null,
        size: null,
        name: null,
        priceBoolean: false,
        Boolean: false,
        priceSource: null
      });
    }
  }
  
  console.log(`✓ Processed ${results.length} symbols with PriceService`);
  return results;
}

// 9. Batch processing for large symbol lists
export async function processSymbolsInBatches(
  symbols: string[], 
  batchSize: number = 100, 
  concurrency: number = 12
): Promise<MarketCapData[]> {
  console.log(`→ Processing ${symbols.length} symbols in batches of ${batchSize} with concurrency ${concurrency}...`);
  
  const results: MarketCapData[] = [];
  
  // Process in batches
  for (let i = 0; i < symbols.length; i += batchSize) {
    const batch = symbols.slice(i, i + batchSize);
    console.log(`→ Processing batch ${Math.floor(i / batchSize) + 1}/${Math.ceil(symbols.length / batchSize)} (${batch.length} symbols)...`);
    
    const batchResults = await processSymbolsWithPriceService(batch);
    results.push(...batchResults);
    
    // OPTIMIZED: Further reduced delay between batches (25ms vs 50ms)
    if (i + batchSize < symbols.length) {
      await new Promise(resolve => setTimeout(resolve, 25));
    }
  }
  
  console.log(`✓ Completed batch processing for ${symbols.length} symbols`);
  return results;
}
