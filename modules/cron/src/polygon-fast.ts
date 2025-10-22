import axios from 'axios';
import { CONFIG } from './config.js';
import { db } from './core/DatabaseManager.js';
import { PrismaClient } from "@prisma/client";
// import { toNumber } from '../../../shared/src/utils.js';
import { processSymbolsInBatches, MarketCapData } from './core/priceService.js';

const prisma = new PrismaClient();

// Optional helper to filter list by provided symbols
function filterSymbols(all: string[], subset?: string[]): string[] {
  if (!subset || subset.length === 0) return all;
  const set = new Set(subset.map(s => s.toUpperCase()));
  return all.filter(s => set.has(s.toUpperCase()));
}

// Utility functions
function chunk<T>(array: T[], size: number): T[][] {
  const chunks: T[][] = [];
  for (let i = 0; i < array.length; i += size) {
    chunks.push(array.slice(i, i + size));
  }
  return chunks;
}

function sleep(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// Smart retry function - only retry on 5xx/429, minimal logging
function shouldRetry(err: any): boolean {
  const s = err?.response?.status;
  return !s || s >= 500 || s === 429;
}

async function retry<T>(fn: () => Promise<T>, attempts: number = 2): Promise<T> {
  let lastError: any;

  for (let i = 0; i < attempts; i++) {
    try {
      return await fn();
    } catch (e) {
      lastError = e;
      if (!shouldRetry(e) || i === attempts - 1) break;

      const delay = 300 * (2 ** i) + Math.random() * 200;
      await sleep(delay);
    }
  }

  throw lastError;
}

// Get previous NY business day (adjusted for weekends)
function getPrevNybDate(daysBack: number = 1): string {
  const date = new Date();
  date.setDate(date.getDate() - daysBack);

  while (date.getDay() === 0 || date.getDay() === 6) {
    date.setDate(date.getDate() - 1);
  }

  return date.toISOString().split('T')[0];
}

// Normalize timestamp (ns/μs/ms/s -> ms)
function normTs(n: number | null | undefined): number | null {
  const x = Number(n);
  if (!Number.isFinite(x)) return null;

  if (x > 1e17) return Math.floor(x / 1e6);
  if (x > 1e14) return Math.floor(x / 1e3);
  if (x > 1e11) return x;
  if (x > 1e9)  return x * 1000;
  return null;
}

// Pick the best available price from snapshot data
function pickPrice(ticker: any): { price: number | null; source: string | null; ts: number | null } {
  const candidates = [
    ticker.preMarket?.price != null ? {
      p: +ticker.preMarket.price,
      t: normTs(ticker.preMarket.timestamp),
      s: 'pre'
    } : null,
    ticker.lastTrade?.p != null ? {
      p: +ticker.lastTrade.p,
      t: normTs(ticker.lastTrade.t),
      s: 'live'
    } : null,
    ticker.afterHours?.price != null ? {
      p: +ticker.afterHours.price,
      t: normTs(ticker.afterHours.timestamp),
      s: 'ah'
    } : null,
    ticker.min?.c != null ? {
      p: +ticker.min.c,
      t: normTs(ticker.min.t),
      s: 'min'
    } : null,
    ticker.day?.c != null && ticker.day.c > 0 ? {
      p: +ticker.day.c,
      t: normTs(ticker.day.t),
      s: 'day'
    } : null,
  ].filter(Boolean) as { p: number; t: number | null; s: string }[];

  candidates.sort((a, b) => (b.t ?? -1) - (a.t ?? -1));

  const best = candidates.find(c => Number.isFinite(c.p) && c.p > 0) ?? null;
  return best ? { price: best.p, source: best.s, ts: best.t } : { price: null, source: null, ts: null };
}

// Get company size based on market cap
function getCompanySize(marketCap: number | null): string | null {
  if (!marketCap || marketCap <= 0) return null;

  const billions = marketCap / 1e9;
  if (billions >= 200) return 'Mega';
  if (billions >= 10) return 'Large';
  if (billions >= 2) return 'Mid';
  return 'Small';
}

// Main fast polygon processing function
export async function runPolygonJobFast(symbols?: string[]): Promise<void> {
  const startTime = Date.now();
  console.log('Starting Fast Polygon job execution with PriceService...');

  try {
    // decide which symbols to process
    let symbolsToProcess: string[];
    if (symbols && symbols.length) {
      console.log(`→ Fast Polygon (delta): ${symbols.length} symbols provided`);
      symbolsToProcess = symbols;
    } else {
      console.log('→ Fast Polygon (full set)');
      const finhubSymbols = await prisma.finhubData.findMany({
        select: { symbol: true },
        distinct: ['symbol'],
      });
      symbolsToProcess = finhubSymbols.map(s => s.symbol);
    }
    symbolsToProcess = filterSymbols(symbolsToProcess, symbols);
    if (!symbolsToProcess.length) {
      console.log('⚠️ No symbols to process');
      return;
    }
    console.log(`Found ${symbolsToProcess.length} symbols to process`);

    console.log('🚀 OPTIMIZED: Processing symbols with PriceService...');
    // OPTIMIZED: Increased batch size and concurrency (200/25 vs 80/10)
    const marketData = await processSymbolsInBatches(symbolsToProcess, 200, 25);

    console.log(`Processed ${marketData.length} symbols with PriceService`);

    console.log('💾 OPTIMIZED: Saving data to database in batches...');
    // OPTIMIZED: Increased database batch size (200 vs 100)
    const DB_BATCH_SIZE = 200;
    const dbBatches = chunk(marketData, DB_BATCH_SIZE);

    for (let i = 0; i < dbBatches.length; i++) {
      const batch = dbBatches[i];
      console.log(`Processing database batch ${i + 1}/${dbBatches.length} (${batch.length} records)...`);

      await prisma.$transaction(
        batch.map(row => prisma.polygonData.upsert({
          where: { symbol: row.symbol },
          create: row,
          update: row,
        }))
      );
    }

    console.log('📊 OPTIMIZED: Generating final report...');
    await db.generateFinalReport();

    const duration = Date.now() - startTime;
    console.log(`Fast Polygon job completed successfully in ${duration}ms`);
    console.log(`Summary: ${symbolsToProcess.length} symbols processed, ${marketData.filter(r => r.Boolean).length} with complete data`);

  } catch (error) {
    const duration = Date.now() - startTime;
    console.error(`Fast Polygon job failed after ${duration}ms:`, error);
    throw error;
  }
}
