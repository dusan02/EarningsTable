/**
 * Enhanced Logo Service with Better Error Handling
 * 
 * Features:
 * - Retry logic with exponential backoff
 * - Better error categorization
 * - Detailed logging and metrics
 * - Graceful degradation
 * - Timeout handling
 */

import axios from "axios";
import http from 'http';
import https from 'https';
import sharp from "sharp";
import fs from "fs/promises";
import path from "path";
import { CONFIG } from '../../../shared/src/config.js';
import { db } from './DatabaseManager.ts';
import { prisma } from '../../../shared/src/prismaClient.js';
import pLimit from 'p-limit';
import { 
  recordLogoAttempt, 
  recordLogoSuccess, 
  recordLogoFailure,
  generateLogoReport 
} from './LogoMetrics.js';

// Absolute path to logo directory - always points to modules/web/public/logos in the repo root
const OUT_DIR = path.resolve(process.cwd(), "..", "web", "public", "logos");
const httpAgent = new http.Agent({ keepAlive: true, maxSockets: 20 });
const httpsAgent = new https.Agent({ keepAlive: true, maxSockets: 20 });
const LOGO_TTL_DAYS = 30;

// Enhanced configuration with retry settings
const LOGO_CONFIG = {
  size: 256,
  quality: 95,
  effort: 6,
  background: { r: 0, g: 0, b: 0, alpha: 0 },
  fit: 'inside' as const,
  withoutEnlargement: true,
  sources: ['finnhub', 'polygon', 'clearbit'] as const,
  retry: {
    maxAttempts: 3,
    baseDelay: 1000, // 1 second
    maxDelay: 5000,  // 5 seconds
    backoffMultiplier: 2
  },
  timeout: 10000, // 10 seconds
  maxFileSize: 5 * 1024 * 1024, // 5MB
  minFileSize: 1024 // 1KB
} as const;

// Metrics tracking
const logoMetrics = {
  total: 0,
  success: 0,
  failed: 0,
  sources: { finnhub: 0, polygon: 0, clearbit: 0 },
  errors: { timeout: 0, network: 0, processing: 0, invalid: 0 }
};

function fromHomepageToDomain(url?: string | null): string | null {
  try { 
    return url ? new URL(url).hostname : null; 
  } catch { 
    return null; 
  }
}

/**
 * Enhanced error handling with retry logic
 */
async function fetchWithRetry<T>(
  fetchFn: () => Promise<T>,
  context: string,
  maxAttempts: number = LOGO_CONFIG.retry.maxAttempts
): Promise<T> {
  let lastError: Error;
  
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      return await fetchFn();
    } catch (error: any) {
      lastError = error;
      
      // Categorize error
      if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
        logoMetrics.errors.timeout++;
      } else if (error.code === 'ENOTFOUND' || error.code === 'ECONNREFUSED') {
        logoMetrics.errors.network++;
      } else if (error.message.includes('Sharp') || error.message.includes('processing')) {
        logoMetrics.errors.processing++;
      } else {
        logoMetrics.errors.invalid++;
      }
      
      if (attempt < maxAttempts) {
        const delay = Math.min(
          LOGO_CONFIG.retry.baseDelay * Math.pow(LOGO_CONFIG.retry.backoffMultiplier, attempt - 1),
          LOGO_CONFIG.retry.maxDelay
        );
        
        console.log(`   → ${context} attempt ${attempt} failed: ${error.message}. Retrying in ${delay}ms...`);
        await new Promise(resolve => setTimeout(resolve, delay));
      }
    }
  }
  
  throw lastError!;
}

/**
 * Enhanced image processing with validation
 */
async function processAndSaveImage(imageBuffer: Buffer, symbol: string, sourceName: string): Promise<boolean> {
  try {
    // Validate file size
    if (imageBuffer.length < LOGO_CONFIG.minFileSize) {
      console.log(`   → Image too small (${imageBuffer.length} bytes) for ${symbol}`);
      return false;
    }
    
    if (imageBuffer.length > LOGO_CONFIG.maxFileSize) {
      console.log(`   → Image too large (${imageBuffer.length} bytes) for ${symbol}`);
      return false;
    }
    
    // Ensure output directory exists
    await fs.mkdir(OUT_DIR, { recursive: true });
    
    const webpOut = path.join(OUT_DIR, `${symbol}.webp`);
    const svgOut = path.join(OUT_DIR, `${symbol}.svg`);
    
    // Try WebP conversion first
    try {
      const processedBuffer = await sharp(imageBuffer)
        .resize(LOGO_CONFIG.size, LOGO_CONFIG.size, {
          fit: LOGO_CONFIG.fit,
          background: LOGO_CONFIG.background,
          withoutEnlargement: LOGO_CONFIG.withoutEnlargement,
        })
        .webp({ 
          quality: LOGO_CONFIG.quality, 
          effort: LOGO_CONFIG.effort, 
          lossless: false, 
          nearLossless: false 
        })
        .toBuffer();
      
      await fs.writeFile(webpOut, processedBuffer);
      console.log(`   → Logo saved: /logos/${symbol}.webp (source: ${sourceName})`);
      return true;
      
    } catch (sharpError: any) {
      // If Sharp fails, try to save as SVG if it's SVG content
      if (sharpError.message.includes('Input file is missing') || 
          sharpError.message.includes('unsupported image format')) {
        
        const contentType = imageBuffer.toString('utf8', 0, 100).toLowerCase();
        if (contentType.includes('<svg') || contentType.includes('<?xml')) {
          try {
            await fs.writeFile(svgOut, imageBuffer);
            console.log(`   → SVG stored raw: /logos/${symbol}.svg (source: ${sourceName})`);
            return true;
          } catch (svgError) {
            console.log(`   → SVG save failed for ${symbol}: ${svgError}`);
          }
        }
      }
      
      console.log(`   → Image processing failed for ${symbol}: ${sharpError.message}`);
      return false;
    }
    
  } catch (error: any) {
    console.log(`   → File system error for ${symbol}: ${error.message}`);
    return false;
  }
}

/**
 * Enhanced logo fetching with better error handling
 */
export async function fetchAndStoreLogo(symbol: string): Promise<{
  logoUrl: string | null; 
  logoSource: string | null;
}> {
  const startTime = Date.now();
  console.log(`🖼️  Fetching logo for ${symbol}...`);
  recordLogoAttempt();
  
  // Check if logo already exists and is recent
  const existingLogoPath = path.join(OUT_DIR, `${symbol}.webp`);
  const existingSvgPath = path.join(OUT_DIR, `${symbol}.svg`);
  
  try {
    const webpStats = await fs.stat(existingLogoPath);
    const ageInDays = (Date.now() - webpStats.mtime.getTime()) / (1000 * 60 * 60 * 24);
    
    if (ageInDays < LOGO_TTL_DAYS) {
      console.log(`   → Logo already exists and is fresh (${Math.round(ageInDays)} days old)`);
      const publicUrl = `/logos/${symbol}.webp`;
      await db.updateLogoInfo(symbol, publicUrl, 'cached');
      recordLogoSuccess('cached', Date.now() - startTime);
      return { logoUrl: publicUrl, logoSource: 'cached' };
    }
  } catch {
    // Logo doesn't exist, continue with fetching
  }
  
  // Try sources in priority order with enhanced error handling
  const sources = [
    { 
      name: 'finnhub', 
      fetchFn: async () => {
        const { data } = await axios.get(
          `https://finnhub.io/api/v1/stock/profile2?symbol=${symbol}&token=${CONFIG.FINNHUB_TOKEN}`,
          { timeout: LOGO_CONFIG.timeout, httpAgent, httpsAgent }
        );
        return data?.logo || null;
      }
    },
    { 
      name: 'polygon', 
      fetchFn: async () => {
        const { data } = await axios.get(
          `https://api.polygon.io/v3/reference/tickers/${symbol}?apiKey=${CONFIG.POLYGON_API_KEY}`,
          { timeout: LOGO_CONFIG.timeout, httpAgent, httpsAgent }
        );
        return data?.results?.branding?.logo_url ? 
          `${data.results.branding.logo_url}?apiKey=${CONFIG.POLYGON_API_KEY}` : null;
      }
    },
    { 
      name: 'clearbit', 
      fetchFn: async () => {
        // Get company homepage from Polygon first
        const { data } = await axios.get(
          `https://api.polygon.io/v3/reference/tickers/${symbol}?apiKey=${CONFIG.POLYGON_API_KEY}`,
          { timeout: LOGO_CONFIG.timeout, httpAgent, httpsAgent }
        );
        
        const homepage = data?.results?.homepage_url;
        if (!homepage) return null;
        
        const domain = fromHomepageToDomain(homepage);
        if (!domain) return null;
        
        return `https://logo.clearbit.com/${domain}`;
      }
    }
  ];

  for (const source of sources) {
    try {
      console.log(`   → Trying ${source.name}...`);
      
      // Fetch logo URL with retry
      const logoUrl = await fetchWithRetry(
        source.fetchFn,
        `${source.name} URL fetch for ${symbol}`
      );
      
      if (!logoUrl) {
        console.log(`   → No logo URL from ${source.name}`);
        continue;
      }
      
      console.log(`   → ${source.name} logo: ${logoUrl}`);
      
      // Download image with retry
      const imageBuffer = await fetchWithRetry(
        async () => {
          const response = await axios.get<ArrayBuffer>(logoUrl, {
            responseType: 'arraybuffer',
            timeout: LOGO_CONFIG.timeout,
            headers: { 
              'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' 
            },
            httpAgent,
            httpsAgent,
            validateStatus: s => s >= 200 && s < 400,
          });
          return Buffer.from(response.data);
        },
        `${source.name} image download for ${symbol}`
      );
      
      // Process and save image
      const success = await processAndSaveImage(imageBuffer, symbol, source.name);
      if (success) {
        const publicUrl = logoUrl.includes('.svg') ? `/logos/${symbol}.svg` : `/logos/${symbol}.webp`;
        
        // Update database
        await db.updateLogoInfo(symbol, publicUrl, source.name);
        recordLogoSuccess(source.name, Date.now() - startTime);
        
        return { logoUrl: publicUrl, logoSource: source.name };
      }
      
    } catch (error: any) {
      console.log(`   → ${source.name} failed for ${symbol}: ${error.message}`);
      
      // Categorize error for metrics
      if (error.code === 'ECONNABORTED' || error.message.includes('timeout')) {
        recordLogoFailure('timeout');
      } else if (error.code === 'ENOTFOUND' || error.code === 'ECONNREFUSED') {
        recordLogoFailure('network');
      } else if (error.message.includes('Sharp') || error.message.includes('processing')) {
        recordLogoFailure('processing');
      } else if (error.response?.status === 404) {
        recordLogoFailure('notFound');
      } else {
        recordLogoFailure('invalid');
      }
    }
  }
  
  console.log(`   → ❌ No logo found for ${symbol} from any source`);
  await db.updateLogoInfo(symbol, null, null);
  recordLogoFailure('notFound');
  
  return { logoUrl: null, logoSource: null };
}

/**
 * Enhanced batch processing with metrics
 */
export async function processLogosInBatches(
  symbols: string[],
  batchSize: number = 12,
  concurrency: number = 6
): Promise<{ success: number; failed: number }> {
  console.log(`🖼️  Processing logos for ${symbols.length} symbols in batches of ${batchSize} with concurrency ${concurrency}...`);
  
  const limit = pLimit(concurrency);
  let successCount = 0;
  let failedCount = 0;
  
  // Process in batches
  for (let i = 0; i < symbols.length; i += batchSize) {
    const batch = symbols.slice(i, i + batchSize);
    const batchNumber = Math.floor(i / batchSize) + 1;
    const totalBatches = Math.ceil(symbols.length / batchSize);
    
    console.log(`   → Processing batch ${batchNumber}/${totalBatches} (${batch.length} symbols)`);
    
    const batchPromises = batch.map(symbol => 
      limit(async () => {
        try {
          const result = await fetchAndStoreLogo(symbol);
          if (result.logoUrl) {
            successCount++;
          } else {
            failedCount++;
          }
        } catch (error: any) {
          console.log(`   → Batch processing failed for ${symbol}: ${error.message}`);
          failedCount++;
        }
      })
    );
    
    await Promise.all(batchPromises);
    
    // Log progress
    const processed = Math.min(i + batchSize, symbols.length);
    console.log(`   → Processed ${processed}/${symbols.length} symbols`);
  }
  
  // Log final metrics
  console.log(`✅ Logo processing completed: ${successCount} success, ${failedCount} failed`);
  console.log(generateLogoReport());
  
  return { success: successCount, failed: failedCount };
}

/**
 * Get current logo metrics
 */
export { getLogoMetrics, resetLogoMetrics } from './LogoMetrics.js';
