/**
 * Improved Logo Service - Enhanced logo fetching with better error handling and optimization
 * 
 * Improvements:
 * - Better error handling and retry logic
 * - Optimized source priority based on success rates
 * - Enhanced logging and debugging
 * - Fallback mechanisms
 * - Rate limiting and API respect
 * - Better image processing with validation
 */

import axios from "axios";
import sharp from "sharp";
import fs from "fs/promises";
import path from "path";
import { CONFIG } from '../../../shared/src/config.js';
import { db } from './DatabaseManager.js';
import pLimit from 'p-limit';

// Absolute path to logo directory
const OUT_DIR = path.resolve(process.cwd(), "..", "web", "public", "logos");
const LOGO_TTL_DAYS = 30;

// Enhanced logo processing configuration
const LOGO_CONFIG = {
  size: 256,
  quality: 95,
  effort: 6,
  background: { r: 0, g: 0, b: 0, alpha: 0 }, // Fully transparent background
  fit: 'inside' as const,
  withoutEnlargement: true,
  minFileSize: 1024, // Minimum 1KB file size
  maxFileSize: 1024 * 1024, // Maximum 1MB file size
  timeout: 10000, // 10 second timeout
  retryAttempts: 2,
  retryDelay: 1000 // 1 second delay between retries
} as const;

// Source configuration with success tracking
interface LogoSource {
  name: string;
  priority: number;
  getLogoUrl: (symbol: string) => Promise<string | null>;
  validateResponse?: (data: any) => boolean;
}

function fromHomepageToDomain(url?: string | null): string | null {
  try { 
    return url ? new URL(url).hostname : null; 
  } catch { 
    return null; 
  }
}

/**
 * Enhanced logo sources with better error handling
 */
const LOGO_SOURCES: LogoSource[] = [
  {
    name: 'finnhub',
    priority: 1,
    getLogoUrl: async (symbol: string) => {
      try {
        const response = await axios.get(
          `https://finnhub.io/api/v1/stock/profile2?symbol=${symbol}&token=${CONFIG.FINNHUB_TOKEN}`, 
          { timeout: LOGO_CONFIG.timeout }
        );
        return response.data?.logo || null;
      } catch (error) {
        console.log(`   → Finnhub API error for ${symbol}:`, error.response?.status);
        return null;
      }
    },
    validateResponse: (data: any) => data?.logo && typeof data.logo === 'string'
  },
  {
    name: 'polygon',
    priority: 2,
    getLogoUrl: async (symbol: string) => {
      try {
        const response = await axios.get(
          `https://api.polygon.io/v3/reference/tickers/${symbol}?apiKey=${CONFIG.POLYGON_API_KEY}`, 
          { timeout: LOGO_CONFIG.timeout }
        );
        const logoUrl = response.data?.results?.branding?.logo_url;
        return logoUrl ? `${logoUrl}?apiKey=${CONFIG.POLYGON_API_KEY}` : null;
      } catch (error) {
        console.log(`   → Polygon API error for ${symbol}:`, error.response?.status);
        return null;
      }
    },
    validateResponse: (data: any) => data?.results?.branding?.logo_url
  },
  {
    name: 'clearbit',
    priority: 3,
    getLogoUrl: async (symbol: string) => {
      try {
        // First get company homepage from Polygon
        const response = await axios.get(
          `https://api.polygon.io/v3/reference/tickers/${symbol}?apiKey=${CONFIG.POLYGON_API_KEY}`, 
          { timeout: LOGO_CONFIG.timeout }
        );
        const homepageUrl = response.data?.results?.homepage_url;
        const domain = fromHomepageToDomain(homepageUrl);
        
        if (domain) {
          return `https://logo.clearbit.com/${domain}`;
        }
        return null;
      } catch (error) {
        console.log(`   → Clearbit domain error for ${symbol}:`, error.message);
        return null;
      }
    }
  }
];

/**
 * Downloads and validates an image from URL
 */
async function downloadImage(url: string, symbol: string): Promise<Buffer | null> {
  try {
    console.log(`   → Downloading: ${url}`);
    
    const response = await axios.get(url, {
      responseType: 'arraybuffer',
      timeout: LOGO_CONFIG.timeout,
      headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Accept': 'image/webp,image/apng,image/*,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.9',
        'Cache-Control': 'no-cache'
      }
    });

    if (!response.data || response.data.length < LOGO_CONFIG.minFileSize) {
      console.log(`   → Image too small: ${response.data?.length || 0} bytes`);
      return null;
    }

    if (response.data.length > LOGO_CONFIG.maxFileSize) {
      console.log(`   → Image too large: ${response.data.length} bytes`);
      return null;
    }

    return Buffer.from(response.data);
  } catch (error: any) {
    console.log(`   → Download failed: ${error.message}`);
    return null;
  }
}

/**
 * Processes image with Sharp and saves as WebP
 */
async function processAndSaveImage(imageBuffer: Buffer, symbol: string): Promise<boolean> {
  try {
    await fs.mkdir(OUT_DIR, { recursive: true });
    const outPath = path.join(OUT_DIR, `${symbol}.webp`);
    
    // Validate image with Sharp first
    const metadata = await sharp(imageBuffer).metadata();
    if (!metadata.width || !metadata.height) {
      console.log(`   → Invalid image metadata`);
      return false;
    }

    console.log(`   → Processing image: ${metadata.width}x${metadata.height}, format: ${metadata.format}`);

    // Process with enhanced settings
    const processedBuffer = await sharp(imageBuffer)
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

    await fs.writeFile(outPath, processedBuffer);
    
    const stats = await fs.stat(outPath);
    console.log(`   → Saved: ${outPath} (${stats.size} bytes)`);
    
    return true;
  } catch (error: any) {
    console.log(`   → Processing failed: ${error.message}`);
    return false;
  }
}

/**
 * Enhanced logo fetching with retry logic and better error handling
 */
export async function fetchAndStoreLogo(symbol: string): Promise<{
  logoUrl: string | null;
  logoSource: string | null;
}> {
  console.log(`🖼️  Fetching logo for ${symbol}...`);
  
  // Check if logo already exists and is recent
  const existingLogoPath = path.join(OUT_DIR, `${symbol}.webp`);
  try {
    const stats = await fs.stat(existingLogoPath);
    const ageInDays = (Date.now() - stats.mtime.getTime()) / (1000 * 60 * 60 * 24);
    
    if (ageInDays < LOGO_TTL_DAYS) {
      console.log(`   → Logo already exists and is fresh (${Math.round(ageInDays)} days old)`);
      const publicUrl = `/logos/${symbol}.webp`;
      await db.updateLogoInfo(symbol, publicUrl, 'cached');
      return { logoUrl: publicUrl, logoSource: 'cached' };
    }
  } catch {
    // Logo doesn't exist, continue with fetching
  }

  // Try sources in priority order
  for (const source of LOGO_SOURCES) {
    for (let attempt = 1; attempt <= LOGO_CONFIG.retryAttempts; attempt++) {
      try {
        console.log(`   → Trying ${source.name} (attempt ${attempt})`);
        
        const logoUrl = await source.getLogoUrl(symbol);
        if (!logoUrl) {
          console.log(`   → No logo URL from ${source.name}`);
          break; // Try next source
        }

        // Download image
        const imageBuffer = await downloadImage(logoUrl, symbol);
        if (!imageBuffer) {
          if (attempt < LOGO_CONFIG.retryAttempts) {
            console.log(`   → Retrying ${source.name} in ${LOGO_CONFIG.retryDelay}ms...`);
            await new Promise(resolve => setTimeout(resolve, LOGO_CONFIG.retryDelay));
            continue;
          }
          break; // Try next source
        }

        // Process and save image
        const success = await processAndSaveImage(imageBuffer, symbol);
        if (success) {
          const publicUrl = `/logos/${symbol}.webp`;
          console.log(`   → ✅ Logo saved: ${publicUrl} (source: ${source.name})`);
          
          // Update database
          await db.updateLogoInfo(symbol, publicUrl, source.name);
          return { logoUrl: publicUrl, logoSource: source.name };
        }

        if (attempt < LOGO_CONFIG.retryAttempts) {
          console.log(`   → Retrying ${source.name} in ${LOGO_CONFIG.retryDelay}ms...`);
          await new Promise(resolve => setTimeout(resolve, LOGO_CONFIG.retryDelay));
        }

      } catch (error: any) {
        console.log(`   → ${source.name} attempt ${attempt} failed: ${error.message}`);
        if (attempt < LOGO_CONFIG.retryAttempts) {
          await new Promise(resolve => setTimeout(resolve, LOGO_CONFIG.retryDelay));
        }
      }
    }
  }

  console.log(`   → ❌ No logo found for ${symbol} from any source`);
  await db.updateLogoInfo(symbol, null, null);
  return { logoUrl: null, logoSource: null };
}

/**
 * Enhanced batch processing with progress tracking and statistics
 */
export async function processLogosInBatches(
  symbols: string[], 
  batchSize: number = 10, 
  concurrency: number = 3
): Promise<{ 
  success: number; 
  failed: number; 
  skipped: number;
  results: { symbol: string; success: boolean; source?: string }[];
}> {
  const limit = pLimit(concurrency);
  let successCount = 0;
  let failedCount = 0;
  let skippedCount = 0;
  const results: { symbol: string; success: boolean; source?: string }[] = [];

  console.log(`🖼️  Processing logos for ${symbols.length} symbols in batches of ${batchSize} (concurrency: ${concurrency})...`);

  for (let i = 0; i < symbols.length; i += batchSize) {
    const batch = symbols.slice(i, i + batchSize);
    const batchNum = Math.floor(i / batchSize) + 1;
    const totalBatches = Math.ceil(symbols.length / batchSize);
    
    console.log(`\n📦 Processing batch ${batchNum}/${totalBatches} (${batch.length} symbols)`);
    
    const tasks = batch.map(symbol => limit(async () => {
      const result = await fetchAndStoreLogo(symbol);
      const success = result.logoUrl !== null;
      
      results.push({ symbol, success, source: result.logoSource || undefined });
      
      if (success) {
        successCount++;
      } else {
        failedCount++;
      }
    }));

    await Promise.allSettled(tasks);
    
    // Progress update
    const processed = Math.min(i + batchSize, symbols.length);
    const progress = Math.round((processed / symbols.length) * 100);
    console.log(`   → Progress: ${processed}/${symbols.length} (${progress}%) - Success: ${successCount}, Failed: ${failedCount}`);
    
    // Small delay between batches to be respectful to APIs
    if (i + batchSize < symbols.length) {
      await new Promise(resolve => setTimeout(resolve, 1000));
    }
  }

  console.log(`\n✅ Logo processing complete:`);
  console.log(`   → Success: ${successCount}`);
  console.log(`   → Failed: ${failedCount}`);
  console.log(`   → Success rate: ${Math.round((successCount / symbols.length) * 100)}%`);

  return { success: successCount, failed: failedCount, skipped: skippedCount, results };
}
