/**
 * Logo Service - Handles downloading and processing company logos
 * 
 * Features:
 * - Downloads logos from multiple sources (Yahoo, Finnhub, Polygon, Clearbit)
 * - Processes images with clean, borderless settings (256x256, 95% quality, fully transparent)
 * - Stores logos in web/public/logos/ directory
 * - Updates database with logo metadata
 * - Batch processing with concurrency control
 * 
 * Logo Sources (in order of preference):
 * 1. Yahoo Finance (via Clearbit)
 * 2. Finnhub API
 * 3. Polygon API
 * 4. Clearbit (via company homepage)
 */

import axios from "axios";
import sharp from "sharp";
import fs from "fs/promises";
import path from "path";
import { CONFIG } from '../../../shared/src/config.js';
import { db } from './DatabaseManager.js';
import pLimit from 'p-limit';

// Get the correct path regardless of where the script is run from
const getOutDir = () => {
  const currentDir = process.cwd();
  if (currentDir.endsWith('modules/cron')) {
    return path.join(currentDir, "..", "web", "public", "logos");
  } else {
    return path.join(currentDir, "modules", "web", "public", "logos");
  }
};
const OUT_DIR = getOutDir();
const LOGO_TTL_DAYS = 30;

// Logo processing configuration
const LOGO_CONFIG = {
  size: 256,
  quality: 95,
  effort: 6,
  background: { r: 0, g: 0, b: 0, alpha: 0 }, // Fully transparent background
  fit: 'inside' as const, // Fit inside bounds without padding
  withoutEnlargement: true, // Don't enlarge smaller images
  sources: ['yahoo', 'finnhub', 'polygon', 'clearbit'] as const
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
    { name: 'yahoo', url: `https://logo.clearbit.com/finance.yahoo.com/quote/${symbol}` },
    { name: 'finnhub', url: `https://finnhub.io/api/v1/stock/profile2?symbol=${symbol}&token=${CONFIG.FINNHUB_TOKEN}` },
    { name: 'polygon', url: `https://api.polygon.io/v3/reference/tickers/${symbol}?apiKey=${CONFIG.POLYGON_API_KEY}` },
    { name: 'clearbit', url: null } // Will be set dynamically
  ];

  for (const source of sources) {
    try {
      let logoUrl: string | null = null;
      let sourceName = source.name;

      if (source.name === 'yahoo') {
        // Direct Yahoo Finance logo
        logoUrl = source.url;
        console.log(`   → Trying Yahoo Finance: ${logoUrl}`);
        
      } else if (source.name === 'finnhub') {
        // Finnhub company profile
        const { data } = await axios.get(source.url!, { timeout: 7000 });
        if (data?.logo) {
          logoUrl = data.logo;
          console.log(`   → Finnhub logo: ${logoUrl}`);
        } else {
          console.log(`   → No Finnhub logo for ${symbol}`);
          continue;
        }
        
      } else if (source.name === 'polygon') {
        // Polygon branding
        const { data } = await axios.get(source.url!, { timeout: 7000 });
        if (data?.results?.branding?.logo_url) {
          logoUrl = `${data.results.branding.logo_url}?apiKey=${CONFIG.POLYGON_API_KEY}`;
          console.log(`   → Polygon logo: ${logoUrl}`);
        } else {
          console.log(`   → No Polygon logo for ${symbol}`);
          continue;
        }
        
      } else if (source.name === 'clearbit') {
        // Clearbit fallback - need to get homepage first
        const polygonResponse = await axios.get(`https://api.polygon.io/v3/reference/tickers/${symbol}?apiKey=${CONFIG.POLYGON_API_KEY}`, { timeout: 7000 });
        const homepageUrl = polygonResponse.data?.results?.homepage_url;
        const domain = fromHomepageToDomain(homepageUrl);
        
        if (domain) {
          logoUrl = `https://logo.clearbit.com/${domain}`;
          console.log(`   → Clearbit logo: ${logoUrl}`);
        } else {
          console.log(`   → No Clearbit domain for ${symbol}`);
          continue;
        }
      }

      if (!logoUrl) continue;

      // Download and process logo
      const resp = await axios.get<ArrayBuffer>(logoUrl, { 
        responseType: "arraybuffer", 
        timeout: 7000,
        headers: {
          'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
      });
      
      await fs.mkdir(OUT_DIR, { recursive: true });
      const outPath = path.join(OUT_DIR, `${symbol}.webp`);
      
      // Process with clean, borderless settings
      const buf = await sharp(Buffer.from(resp.data))
        .resize(LOGO_CONFIG.size, LOGO_CONFIG.size, { 
          fit: LOGO_CONFIG.fit,
          background: LOGO_CONFIG.background,
          withoutEnlargement: LOGO_CONFIG.withoutEnlargement
        })
        .webp({ 
          quality: LOGO_CONFIG.quality, 
          effort: LOGO_CONFIG.effort,
          lossless: false,
          nearLossless: false
        })
        .toBuffer();
      
      await fs.writeFile(outPath, buf);
      
      const publicUrl = `/logos/${symbol}.webp`;
      console.log(`   → Logo saved: ${publicUrl} (source: ${sourceName})`);
      
      // Update database with logo info
      await db.updateLogoInfo(symbol, publicUrl, sourceName);
      
      return { logoUrl: publicUrl, logoSource: sourceName };
      
    } catch (error: any) {
      console.log(`   → Failed ${source.name} for ${symbol}: ${error.message}`);
      continue;
    }
  }

  console.log(`   → No logo found for ${symbol} from any source`);
  // Update database to clear logo info
  await db.updateLogoInfo(symbol, null, null);
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
export async function processLogosInBatches(symbols: string[], batchSize: number, concurrency: number): Promise<{ success: number; failed: number }> {
  const limit = pLimit(concurrency);
  let successCount = 0;
  let failedCount = 0;

  console.log(`🖼️  Processing logos for ${symbols.length} symbols in batches of ${batchSize}...`);

  for (let i = 0; i < symbols.length; i += batchSize) {
    const batch = symbols.slice(i, i + batchSize);
    console.log(`   → Processing batch ${Math.floor(i / batchSize) + 1}/${Math.ceil(symbols.length / batchSize)} (${batch.length} symbols)`);
    
    const tasks = batch.map(symbol => limit(async () => {
      const result = await fetchAndStoreLogo(symbol);
      if (result.logoUrl) {
        successCount++;
      } else {
        failedCount++;
      }
    }));
    await Promise.allSettled(tasks);
  }
  return { success: successCount, failed: failedCount };
}