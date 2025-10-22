import axios from "axios";
import sharp from "sharp";
import fs from "fs/promises";
import path from "path";
import { CONFIG } from '../../../shared/src/config.js';
import { db } from './DatabaseManager.js';
import pLimit from 'p-limit';

const OUT_DIR = path.resolve(process.cwd(), "..", "web", "public", "logos");
const LOGO_CACHE_DAYS = 7; // Kratší cache pre rýchlejšie aktualizácie

// Optimalizované nastavenia
const LOGO_CONFIG = {
  size: 128, // Menšie logá = rýchlejšie
  quality: 80, // Nižšia kvalita = rýchlejšie
  effort: 3, // Nižší effort = rýchlejšie
  background: { r: 0, g: 0, b: 0, alpha: 0 },
  fit: 'inside' as const,
  withoutEnlargement: true,
} as const;

// Cache pre API odpovede
const apiCache = new Map<string, { data: any; timestamp: number }>();
const CACHE_TTL = 5 * 60 * 1000; // 5 minút

async function getCachedApiResponse(url: string): Promise<any> {
  const cached = apiCache.get(url);
  if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
    return cached.data;
  }
  
  const response = await axios.get(url, { timeout: 5000 });
  apiCache.set(url, { data: response.data, timestamp: Date.now() });
  return response.data;
}

// Batch processing s vyššou konkurenciou
export async function processLogosInBatchesOptimized(
  symbols: string[], 
  batchSize: number = 20, 
  concurrency: number = 15
): Promise<{ success: number; failed: number }> {
  const limit = pLimit(concurrency);
  let successCount = 0;
  let failedCount = 0;

  console.log(`🖼️ Processing ${symbols.length} logos in batches of ${batchSize} (concurrency: ${concurrency})...`);

  for (let i = 0; i < symbols.length; i += batchSize) {
    const batch = symbols.slice(i, i + batchSize);
    console.log(`   → Batch ${Math.floor(i / batchSize) + 1}/${Math.ceil(symbols.length / batchSize)} (${batch.length} symbols)`);

    const tasks = batch.map(symbol => limit(async () => {
      try {
        const result = await fetchAndStoreLogoOptimized(symbol);
        if (result.logoUrl) {
          successCount++;
        } else {
          failedCount++;
        }
      } catch (error) {
        failedCount++;
        console.log(`   → Failed ${symbol}: ${(error as any)?.message}`);
      }
    }));

    await Promise.allSettled(tasks);
  }

  console.log(`🖼️ Logo processing completed: ${successCount} success, ${failedCount} failed`);
  return { success: successCount, failed: failedCount };
}

async function fetchAndStoreLogoOptimized(symbol: string): Promise<{
  logoUrl: string | null; 
  logoSource: string | null;
}> {
  // Skontroluj či logo už existuje a je fresh
  const logoPath = path.join(OUT_DIR, `${symbol}.webp`);
  try {
    const stats = await fs.stat(logoPath);
    const age = Date.now() - stats.mtime.getTime();
    if (age < LOGO_CACHE_DAYS * 24 * 60 * 60 * 1000) {
      console.log(`   → Using cached logo for ${symbol}`);
      return { logoUrl: `/logos/${symbol}.webp`, logoSource: 'cached' };
    }
  } catch {
    // Logo neexistuje, pokračuj
  }

  // Rýchle API volania s cache
  const sources = [
    { name: 'polygon', url: `https://api.polygon.io/v3/reference/tickers/${symbol}?apiKey=${CONFIG.POLYGON_API_KEY}` },
    { name: 'finnhub', url: `https://finnhub.io/api/v1/stock/profile2?symbol=${symbol}&token=${CONFIG.FINNHUB_TOKEN}` },
  ];

  for (const source of sources) {
    try {
      let logoUrl: string | null = null;
      
      if (source.name === 'polygon') {
        const data = await getCachedApiResponse(source.url);
        if (data?.results?.branding?.logo_url) {
          logoUrl = `${data.results.branding.logo_url}?apiKey=${CONFIG.POLYGON_API_KEY}`;
        }
      } else if (source.name === 'finnhub') {
        const data = await getCachedApiResponse(source.url);
        if (data?.logo) {
          logoUrl = data.logo;
        }
      }

      if (!logoUrl) continue;

      // Rýchle sťahovanie a spracovanie
      const resp = await axios.get<ArrayBuffer>(logoUrl, {
        responseType: "arraybuffer",
        timeout: 3000, // Kratší timeout
        headers: { 'User-Agent': 'Mozilla/5.0' }
      });

      await fs.mkdir(OUT_DIR, { recursive: true });

      // Optimalizované spracovanie
      const buf = await sharp(Buffer.from(resp.data))
        .resize(LOGO_CONFIG.size, LOGO_CONFIG.size, {
          fit: LOGO_CONFIG.fit,
          background: LOGO_CONFIG.background,
          withoutEnlargement: LOGO_CONFIG.withoutEnlargement
        })
        .webp({
          quality: LOGO_CONFIG.quality,
          effort: LOGO_CONFIG.effort,
          lossless: false
        })
        .toBuffer();

      await fs.writeFile(logoPath, buf);
      
      const publicUrl = `/logos/${symbol}.webp`;
      await db.updateLogoInfo(symbol, publicUrl, source.name);
      
      return { logoUrl: publicUrl, logoSource: source.name };

    } catch (error) {
      continue;
    }
  }

  await db.updateLogoInfo(symbol, null, null);
  return { logoUrl: null, logoSource: null };
}
