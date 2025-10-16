import axios from "axios";
import sharp from "sharp";
import fs from "fs/promises";
import path from "path";
import { CONFIG } from '../../../shared/src/config.js';
import { db } from './DatabaseManager.js';
import pLimit from 'p-limit';

const OUT_DIR = path.join(process.cwd(), "..", "modules", "web", "public", "logos");
const LOGO_TTL_DAYS = 30;

function fromHomepageToDomain(url?: string | null): string | null {
  try { return url ? new URL(url).hostname : null; } catch { return null; }
}

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
      
      // Process with higher quality settings
      const buf = await sharp(Buffer.from(resp.data))
        .resize(256, 256, { 
          fit: "contain", 
          background: { r: 255, g: 255, b: 255, alpha: 0 } 
        })
        .webp({ quality: 95, effort: 6 })
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