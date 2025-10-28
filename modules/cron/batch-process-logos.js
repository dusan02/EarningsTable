import { PrismaClient } from "@prisma/client";
import axios from "axios";
import sharp from "sharp";
import fs from "fs/promises";
import path from "path";
import pLimit from "p-limit";

const prisma = new PrismaClient();
const OUT_DIR = path.resolve(process.cwd(), "..", "web", "public", "logos");

// Enhanced logo sources
const LOGO_SOURCES = [
  {
    name: "finnhub",
    priority: 1,
    getLogoUrl: async (symbol) => {
      try {
        const token = process.env.FINNHUB_TOKEN;
        const response = await axios.get(
          `https://finnhub.io/api/v1/stock/profile2?symbol=${symbol}&token=${token}`,
          { timeout: 10000 }
        );
        return response.data?.logo || null;
      } catch (error) {
        console.log(
          `   → Finnhub API error for ${symbol}:`,
          error.response?.status
        );
        return null;
      }
    },
  },
  {
    name: "clearbit",
    priority: 2,
    getLogoUrl: async (symbol) => {
      try {
        // Get company homepage from Polygon
        const polygonKey = process.env.POLYGON_API_KEY;
        const response = await axios.get(
          `https://api.polygon.io/v3/reference/tickers/${symbol}?apiKey=${polygonKey}`,
          { timeout: 10000 }
        );
        const homepageUrl = response.data?.results?.homepage_url;

        if (homepageUrl) {
          const domain = new URL(homepageUrl).hostname;
          return `https://logo.clearbit.com/${domain}`;
        }
        return null;
      } catch (error) {
        console.log(`   → Clearbit domain error for ${symbol}:`, error.message);
        return null;
      }
    },
  },
];

async function downloadAndProcessLogo(symbol, logoUrl, sourceName) {
  try {
    console.log(`   → Downloading: ${logoUrl}`);

    const response = await axios.get(logoUrl, {
      responseType: "arraybuffer",
      timeout: 10000,
      headers: {
        "User-Agent":
          "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
      },
    });

    if (!response.data || response.data.length < 1024) {
      console.log(`   → Image too small: ${response.data?.length || 0} bytes`);
      return false;
    }

    await fs.mkdir(OUT_DIR, { recursive: true });
    const outPath = path.join(OUT_DIR, `${symbol}.webp`);

    const processedBuffer = await sharp(Buffer.from(response.data))
      .resize(256, 256, {
        fit: "inside",
        background: { r: 0, g: 0, b: 0, alpha: 0 },
        withoutEnlargement: true,
      })
      .webp({
        quality: 95,
        effort: 6,
      })
      .toBuffer();

    await fs.writeFile(outPath, processedBuffer);

    const stats = await fs.stat(outPath);
    console.log(
      `   → ✅ Logo saved: ${outPath} (${stats.size} bytes, source: ${sourceName})`
    );

    return true;
  } catch (error) {
    console.log(`   → Processing failed: ${error.message}`);
    return false;
  }
}

async function fetchLogoForSymbol(symbol) {
  console.log(`🖼️  Fetching logo for ${symbol}...`);

  // Check if logo already exists
  const existingLogoPath = path.join(OUT_DIR, `${symbol}.webp`);
  try {
    const stats = await fs.stat(existingLogoPath);
    const ageInDays =
      (Date.now() - stats.mtime.getTime()) / (1000 * 60 * 60 * 24);

    if (ageInDays < 30) {
      console.log(
        `   → Logo already exists and is fresh (${Math.round(
          ageInDays
        )} days old)`
      );
      return { success: true, source: "cached" };
    }
  } catch {
    // Logo doesn't exist, continue with fetching
  }

  // Try sources in priority order
  for (const source of LOGO_SOURCES) {
    try {
      const logoUrl = await source.getLogoUrl(symbol);
      if (!logoUrl) {
        console.log(`   → No logo URL from ${source.name}`);
        continue;
      }

      const success = await downloadAndProcessLogo(
        symbol,
        logoUrl,
        source.name
      );
      if (success) {
        // Update database
        await prisma.finhubData.updateMany({
          where: { symbol },
          data: {
            logoUrl: `/logos/${symbol}.webp`,
            logoSource: source.name,
            logoFetchedAt: new Date(),
          },
        });

        return { success: true, source: source.name };
      }
    } catch (error) {
      console.log(`   → ${source.name} failed: ${error.message}`);
    }
  }

  console.log(`   → ❌ No logo found for ${symbol}`);
  return { success: false, source: null };
}

async function batchProcessLogos() {
  console.log("=== BATCH LOGO PROCESSING ===\n");

  // Get symbols that need logos
  const symbolsNeedingLogos = await prisma.finhubData.findMany({
    where: {
      OR: [
        { logoUrl: null },
        { logoFetchedAt: null },
        {
          logoFetchedAt: {
            lt: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000),
          },
        },
      ],
    },
    select: { symbol: true },
    distinct: ["symbol"],
  });

  console.log(`Found ${symbolsNeedingLogos.length} symbols needing logos`);

  if (symbolsNeedingLogos.length === 0) {
    console.log("No symbols need logo refresh");
    return;
  }

  const symbols = symbolsNeedingLogos.map((s) => s.symbol);
  const batchSize = 5;
  const concurrency = 2;
  const limit = pLimit(concurrency);

  let successCount = 0;
  let failedCount = 0;
  const results = [];

  console.log(
    `Processing in batches of ${batchSize} with concurrency ${concurrency}...\n`
  );

  for (let i = 0; i < symbols.length; i += batchSize) {
    const batch = symbols.slice(i, i + batchSize);
    const batchNum = Math.floor(i / batchSize) + 1;
    const totalBatches = Math.ceil(symbols.length / batchSize);

    console.log(
      `📦 Processing batch ${batchNum}/${totalBatches} (${batch.length} symbols)`
    );

    const tasks = batch.map((symbol) =>
      limit(async () => {
        const result = await fetchLogoForSymbol(symbol);
        results.push({
          symbol,
          success: result.success,
          source: result.source,
        });

        if (result.success) {
          successCount++;
        } else {
          failedCount++;
        }
      })
    );

    await Promise.allSettled(tasks);

    // Progress update
    const processed = Math.min(i + batchSize, symbols.length);
    const progress = Math.round((processed / symbols.length) * 100);
    console.log(
      `   → Progress: ${processed}/${symbols.length} (${progress}%) - Success: ${successCount}, Failed: ${failedCount}\n`
    );

    // Delay between batches
    if (i + batchSize < symbols.length) {
      await new Promise((resolve) => setTimeout(resolve, 2000));
    }
  }

  console.log(`\n✅ Logo processing complete:`);
  console.log(`   → Success: ${successCount}`);
  console.log(`   → Failed: ${failedCount}`);
  console.log(
    `   → Success rate: ${Math.round((successCount / symbols.length) * 100)}%`
  );

  // Show detailed results
  console.log("\nDetailed results:");
  results.forEach((r) => {
    console.log(`${r.symbol}: ${r.success ? "✅" : "❌"} ${r.source || ""}`);
  });
}

// Run the batch processing
batchProcessLogos()
  .catch(console.error)
  .finally(() => prisma.$disconnect());
