/**
 * Logo Service - Handles downloading and processing company logos
 * 
 * Features:
 * - Downloads logos from multiple sources (Finnhub, IEX Cloud, Polygon, Clearbit)
 * - Processes images with clean, borderless settings (256x256, 95% quality, fully transparent)
 * - Stores logos in web/public/logos/ directory
 * - Updates database with logo metadata
 * - Batch processing with concurrency control
 * 
 * Logo Sources (in order of preference):
 * 1. Finnhub API
 * 2. Polygon API
 * 3. Clearbit (via company homepage)
 */

import axios from "axios";
import http from 'http';
import https from 'https';
import sharp from "sharp";
import fs from "fs/promises";
import path from "path";
import { CONFIG } from '../../../shared/src/config.js';
import { db } from './DatabaseManager.js';
import pLimit from 'p-limit';

// Absolute path to logo directory - always points to modules/web/public/logos in the repo root
const OUT_DIR = path.resolve(process.cwd(), "modules", "web", "public", "logos");
const httpAgent = new http.Agent({ keepAlive: true, maxSockets: 20 });
const httpsAgent = new https.Agent({ keepAlive: true, maxSockets: 20 });
const LOGO_TTL_DAYS = 30;

// Logo processing configuration
const LOGO_CONFIG = {
  size: 256,
  quality: 95,
  effort: 6,
  background: { r: 0, g: 0, b: 0, alpha: 0 }, // Fully transparent background
  fit: 'inside' as const, // Fit inside bounds without padding
  withoutEnlargement: true, // Don't enlarge smaller images
  sources: ['finnhub', 'polygon', 'clearbit'] as const
} as const;

function fromHomepageToDomain(url?: string | null): string | null {
  try { return url ? new URL(url).hostname : null; } catch { return null; }
}


/**
 * Fetches and stores a logo for a given symbol
 * 
 * @param symbol - Stock symbol (e.g., 'AAPL')
 * @returns Promise with logoUrl and logoSource
 * 
 * Process:
 * 1. Tries multiple sources in order of preference
 * 2. Downloads logo image
 * 3. Processes with Sharp (clean resize, no borders, transparent background)
 * 4. Saves to web/public/logos/{symbol}.webp
 * 5. Updates database with logo metadata
 */
export async function fetchAndStoreLogo(symbol: string): Promise<{
  logoUrl: string | null; logoSource: string | null;
}> {
  console.log(`🖼️  Fetching logo for ${symbol}...`);
  
  // Try multiple sources in order of preference
  const sources = [
    { name: 'finnhub', url: `https://finnhub.io/api/v1/stock/profile2?symbol=${symbol}&token=${CONFIG.FINNHUB_TOKEN}` },
    { name: 'polygon', url: `https://api.polygon.io/v3/reference/tickers/${symbol}?apiKey=${CONFIG.POLYGON_API_KEY}` },
    { name: 'clearbit', url: null } // Will be set dynamically
  ];

  // Try all sources in parallel to collect candidates (URLs), but process sequentially with fallbacks
  const sourcePromises = sources.map(async (source) => {
    try {
      let logoUrl: string | null = null;
      let sourceName = source.name;

      if (source.name === 'finnhub') {
        // Finnhub company profile
        const { data } = await axios.get(source.url!, { timeout: 7000, httpAgent, httpsAgent });
        if (data?.logo) {
          logoUrl = data.logo;
          console.log(`   → Finnhub logo: ${logoUrl}`);
        } else {
          console.log(`   → No Finnhub logo for ${symbol}`);
          return null;
        }
        
      } else if (source.name === 'polygon') {
        // Polygon branding
        const { data } = await axios.get(source.url!, { timeout: 7000, httpAgent, httpsAgent });
        if (data?.results?.branding?.logo_url) {
          logoUrl = `${data.results.branding.logo_url}?apiKey=${CONFIG.POLYGON_API_KEY}`;
          console.log(`   → Polygon logo: ${logoUrl}`);
        } else {
          console.log(`   → No Polygon logo for ${symbol}`);
          return null;
        }
        
      } else if (source.name === 'clearbit') {
        // Clearbit fallback - need to get homepage first
        const polygonResponse = await axios.get(`https://api.polygon.io/v3/reference/tickers/${symbol}?apiKey=${CONFIG.POLYGON_API_KEY}`, { timeout: 7000, httpAgent, httpsAgent });
        let homepageUrl = polygonResponse.data?.results?.homepage_url;
        // Fallback: guess domain from symbol if homepage missing (best-effort)
        if (!homepageUrl && symbol) {
          homepageUrl = `https://${symbol.toLowerCase()}.com`;
        }
        const domain = fromHomepageToDomain(homepageUrl);
        
        if (domain) {
          logoUrl = `https://logo.clearbit.com/${domain}`;
          console.log(`   → Clearbit logo: ${logoUrl}`);
        } else {
          console.log(`   → No Clearbit domain for ${symbol}`);
          return null;
        }
      }

      return logoUrl ? { logoUrl, sourceName } : null;
    } catch (error) {
      console.log(`   → Failed ${source.name} for ${symbol}: ${(error as Error).message}`);
      return null;
    }
  });

  // Wait for all sources to complete, keep all viable candidates
  const results = await Promise.allSettled(sourcePromises);
  const candidates = results
    .filter((r): r is PromiseFulfilledResult<{ logoUrl: string; sourceName: string } | null> => r.status === 'fulfilled')
    .map(r => r.value)
    .filter((v): v is { logoUrl: string; sourceName: string } => !!v && !!v.logoUrl);

  if (candidates.length === 0) {
    console.log(`   → No logo found for ${symbol} from any source`);
    return { logoUrl: null, logoSource: null };
  }

  // Try processing candidates in order; on sharp failure with SVG, save raw SVG as fallback
  await fs.mkdir(OUT_DIR, { recursive: true });
  const webpOut = path.join(OUT_DIR, `${symbol}.webp`);
  const svgOut = path.join(OUT_DIR, `${symbol}.svg`);

  for (const cand of candidates) {
    try {
      const resp = await axios.get<ArrayBuffer>(cand.logoUrl, {
        responseType: 'arraybuffer',
        timeout: 10000,
        headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' },
        httpAgent,
        httpsAgent,
        validateStatus: s => s >= 200 && s < 400,
      });

      const contentType = String((resp.headers as any)['content-type'] || '').toLowerCase();
      const raw = Buffer.from(resp.data);

      // Try webp conversion first for any raster/SVG; if SVG parse fails, store raw SVG
      try {
        const buf = await sharp(raw)
          .resize(LOGO_CONFIG.size, LOGO_CONFIG.size, {
            fit: LOGO_CONFIG.fit,
            background: LOGO_CONFIG.background,
            withoutEnlargement: LOGO_CONFIG.withoutEnlargement,
          })
          .webp({ quality: LOGO_CONFIG.quality, effort: LOGO_CONFIG.effort, lossless: false, nearLossless: false })
          .toBuffer();
        await fs.writeFile(webpOut, buf);
        const publicUrl = `/logos/${symbol}.webp`;
        console.log(`   → Logo saved: ${publicUrl} (source: ${cand.sourceName})`);
        return { logoUrl: publicUrl, logoSource: cand.sourceName };
      } catch (convErr) {
        if (contentType.includes('image/svg')) {
          try {
            await fs.writeFile(svgOut, raw);
            const publicUrl = `/logos/${symbol}.svg`;
            console.log(`   → SVG stored raw: ${publicUrl} (source: ${cand.sourceName})`);
            return { logoUrl: publicUrl, logoSource: cand.sourceName };
          } catch (writeErr) {
            // fallthrough to try next candidate
          }
        }
        // try next candidate
        console.log(`   → Conversion failed for ${symbol} (${cand.sourceName}): ${(convErr as Error).message}`);
      }
    } catch (e) {
      console.log(`   → Download failed for ${symbol} (${cand.sourceName}): ${(e as Error).message}`);
    }
  }

  console.log(`   → All candidates failed for ${symbol}`);
  return { logoUrl: null, logoSource: null };
}

/**
 * Processes multiple logos in batches with concurrency control
 * 
 * @param symbols - Array of stock symbols
 * @param batchSize - Number of symbols to process in each batch
 * @param concurrency - Maximum concurrent downloads per batch
 * @returns Promise with success and failed counts
 * 
 * Features:
 * - Batch processing to avoid overwhelming APIs
 * - Concurrency control to limit simultaneous requests
 * - Progress logging for each batch
 * - Error handling for individual symbols
 */
function toPosInt(v: any, d: number): number {
  const n = Number(v);
  return Number.isFinite(n) && n >= 1 ? Math.floor(n) : d;
}

export async function processLogosInBatches(
  symbols: string[],
  batchSize?: number,
  concurrency?: number
): Promise<{ success: number; failed: number }> {
  // Resolve safe batch/concurrency with guards against NaN/0/invalid inputs
  const BATCH = toPosInt(
    batchSize ?? (CONFIG as any)?.LOGO_BATCH_SIZE ?? process.env.LOGO_BATCH_SIZE,
    16
  );
  const CONC = toPosInt(
    concurrency ?? (CONFIG as any)?.LOGO_CONCURRENCY ?? process.env.LOGO_CONCURRENCY,
    6
  );

  const limit = pLimit(CONC);
  let successCount = 0;
  let failedCount = 0;
  const logoUpdates: Array<{ symbol: string; logoUrl: string | null; logoSource: string | null }> = [];

  console.log(`🖼️  Processing logos for ${symbols.length} symbols in batches of ${BATCH} with concurrency ${CONC}...`);

  // Pre-filter symbols that actually need logo updates
  const symbolsNeedingLogos = await db.getSymbolsNeedingLogoRefresh();
  const symbolsToProcess = symbols.filter(symbol => symbolsNeedingLogos.includes(symbol));
  
  if (symbolsToProcess.length === 0) {
    console.log('🖼️ All symbols already have up-to-date logos, skipping...');
    return { success: 0, failed: 0 };
  }

  console.log(`🖼️ Found ${symbolsToProcess.length} symbols that need logo updates (out of ${symbols.length} total)`);

  for (let i = 0; i < symbolsToProcess.length; i += BATCH) {
    const batch = symbolsToProcess.slice(i, i + BATCH);
    console.log(`   → Processing batch ${Math.floor(i / BATCH) + 1}/${Math.ceil(symbolsToProcess.length / BATCH)} (${batch.length} symbols)`);
    
    const tasks = batch.map(symbol => limit(async () => {
      const result = await fetchAndStoreLogo(symbol);
      logoUpdates.push({ symbol, logoUrl: result.logoUrl, logoSource: result.logoSource });
      if (result.logoUrl) {
        successCount++;
      } else {
        failedCount++;
      }
    }));
    await Promise.allSettled(tasks);

    // tunable delay between batches to reduce 429s
    if (i + BATCH < symbolsToProcess.length) {
      const delay = (CONFIG.LOGO_BATCH_DELAY_MS ?? 150) + Math.floor(Math.random() * 100);
      await new Promise(res => setTimeout(res, delay));
    }
  }

  // Batch update database for better performance
  if (logoUpdates.length > 0) {
    await db.batchUpdateLogoInfo(logoUpdates);
  }

  return { success: successCount, failed: failedCount };
}