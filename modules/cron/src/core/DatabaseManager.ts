import { prisma } from '../../../shared/src/prismaClient.js';
import { CreateFinhubData, CreatePolygonData, CreateFinalReport } from '../../../shared/src/types.js';
import { Prisma } from '@prisma/client';
import Decimal from 'decimal.js';

function toDateTime(v: any): Date | null {
  if (v == null) return null;
  if (v instanceof Date) return v;
  if (typeof v === "string") {
    if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return new Date(`${v}T00:00:00.000Z`);
    return new Date(v);
  }
  return new Date(v);
}

function normalizeFinalReportDates<T extends { reportDate?: any; snapshotDate?: any }>(o: T): T {
  return {
    ...o,
    reportDate: toDateTime(o.reportDate),
    snapshotDate: toDateTime(o.snapshotDate),
  };
}

export class DatabaseManager {
  /**
   * Calculates marketCapDiff from previousMarketCap and change percentage: 
   * - marketCapDiff = previousMarketCap * (change / 100)
   * - currentMarketCap = previousMarketCap + marketCapDiff
   *
   * @param incoming - Data object containing symbol and optional fields    
   * @returns Promise<void>
   */
  async upsertFinalReport(incoming: {
    symbol: string;
    name?: string | null;
    size?: string | null;
    previousMarketCap?: number | null;
    change?: number | null;
    price?: number | null;
    epsActual?: number | null;
    epsEst?: number | null;
    epsSurp?: number | null;
    revActual?: number | null;
    revEst?: number | null;
    revSurp?: number | null;
  }, ctx?: { reportDate?: Date; snapshotDate?: Date }): Promise<void> {
    const prevMC = incoming.previousMarketCap ?? null;
    const changePct = incoming.change ?? null;

    let marketCapDiff: bigint | null = null;
    let currentMarketCap: bigint | null = null;
    if (prevMC != null && Number.isFinite(changePct)) {
      const diffDec = new Decimal(prevMC.toString()).mul(new Decimal(changePct as number).div(100));
      const currDec = new Decimal(prevMC.toString()).add(diffDec);
      marketCapDiff = BigInt(diffDec.toDecimalPlaces(0, Decimal.ROUND_HALF_UP).toString());
      currentMarketCap = BigInt(currDec.toDecimalPlaces(0, Decimal.ROUND_HALF_UP).toString());
    }

    const createData = normalizeFinalReportDates({
      symbol: incoming.symbol,
      name: incoming.name,
      size: incoming.size,
      marketCap: currentMarketCap,
      marketCapDiff,
      price: incoming.price,
      change: incoming.change,
      epsActual: incoming.epsActual,
      epsEst: incoming.epsEst,
      epsSurp: incoming.epsSurp,
      revActual: incoming.revActual,
      revEst: incoming.revEst,
      revSurp: incoming.revSurp,
      reportDate: ctx?.reportDate ?? new Date(),
      snapshotDate: ctx?.snapshotDate ?? new Date(),
    });

    const updateData = normalizeFinalReportDates({
      name: incoming.name,
      size: incoming.size,
      marketCap: currentMarketCap,
      marketCapDiff,
      price: incoming.price,
      change: incoming.change,
      epsActual: incoming.epsActual,
      epsEst: incoming.epsEst,
      epsSurp: incoming.epsSurp,
      revActual: incoming.revActual,
      revEst: incoming.revEst,
      revSurp: incoming.revSurp,
      reportDate: ctx?.reportDate ?? new Date(),
      snapshotDate: ctx?.snapshotDate ?? new Date(),
    });

    await prisma.finalReport.upsert({
      where: { symbol: incoming.symbol },
      create: createData,
      update: updateData,
    });
  }

  // FinhubData operations
  async upsertFinhubData(data: CreateFinhubData[]): Promise<string[]> {
    console.log(`→ Upserting ${data.length} finhub reports in batch transaction...`);

    if (data.length === 0) {
      console.log('✓ No data to upsert');
      return [];
    }

    // 1) Načítaj existujúce riadky pre rovnaké (reportDate,symbol)
    const keys = data.map(r => ({ reportDate: r.reportDate, symbol: r.symbol }));
    const existing = await prisma.finhubData.findMany({
      where: { OR: keys.map(k => ({
        reportDate: k.reportDate, symbol: k.symbol
      })) }
    });
    const eKey = (d: any) => `${d.reportDate.toISOString()}|${d.symbol}`;
    const exMap = new Map(existing.map(e => [eKey(e), e]));

    // 2) Porovnaj polia a zozbieraj zmenené symboly
    const changed = new Set<string>();
    for (const r of data) {
      const k = eKey(r);
      const prev = exMap.get(k);
      if (!prev) {
        changed.add(r.symbol);
        continue;
      }
      const diff = (a: any, b: any) => (a ?? null) !== (b ?? null);
      if (
        diff(prev.hour, r.hour ?? null) ||
        diff(prev.epsActual, r.epsActual ?? null) ||
        diff(prev.epsEstimate, r.epsEstimate ?? null) ||
        diff(prev.revenueActual, r.revenueActual != null ? BigInt(Math.round(r.revenueActual)) : null) ||
        diff(prev.revenueEstimate, r.revenueEstimate != null ? BigInt(Math.round(r.revenueEstimate)) : null) ||
        diff(prev.quarter, r.quarter ?? null) ||
        diff(prev.year, r.year ?? null)
      ) {
        changed.add(r.symbol);
      }
    }

    const batchSize = 200; // OPTIMIZED: Increased batch size
    let totalUpserted = 0;
    for (let i = 0; i < data.length; i += batchSize) {
      const batch = data.slice(i, i + batchSize);
      console.log(`→ OPTIMIZED: Processing batch ${Math.floor(i / batchSize) + 1}/${Math.ceil(data.length / batchSize)} (${batch.length} records)...`);
      await prisma.$transaction(
        batch.map(record =>
          prisma.finhubData.upsert({
            where: {
              reportDate_symbol: {
                reportDate: record.reportDate,
                symbol: record.symbol,
              },
            },
            update: {
              hour: record.hour ?? null,
              epsActual: record.epsActual ?? null,
              epsEstimate: record.epsEstimate ?? null,
              revenueActual: record.revenueActual ? BigInt(Math.round(record.revenueActual)) : null,
              revenueEstimate: record.revenueEstimate ? BigInt(Math.round(record.revenueEstimate)) : null,
              quarter: record.quarter ?? null,
              year: record.year ?? null,
            },
            create: {
              reportDate: record.reportDate,
              symbol: record.symbol,
              hour: record.hour ?? null,
              epsActual: record.epsActual ?? null,
              epsEstimate: record.epsEstimate ?? null,
              revenueActual: record.revenueActual ? BigInt(Math.round(record.revenueActual)) : null,
              revenueEstimate: record.revenueEstimate ? BigInt(Math.round(record.revenueEstimate)) : null,
              quarter: record.quarter ?? null,
              year: record.year ?? null,
            },
          })
        )
      );
      totalUpserted += batch.length;
    }

    console.log(`✓ Successfully upserted ${totalUpserted} finhub reports in ${Math.ceil(data.length / batchSize)} batches`);
    return Array.from(changed);
  }

  async getFinhubDataByDate(date: Date) {
    const y = date.getUTCFullYear();
    const m = date.getUTCMonth();
    const d = date.getUTCDate();
    const start = new Date(Date.UTC(y, m, d, 0, 0, 0));
    const end = new Date(Date.UTC(y, m, d + 1, 0, 0, 0));

    return await prisma.finhubData.findMany({
      where: {
        reportDate: {
          gte: start,
          lt: end,
        }
      },
      orderBy: {
        symbol: 'asc'
      }
    });
  }

  async clearFinhubData(): Promise<void> {
    console.log('🗑️ Clearing FinhubData...');
    const result = await prisma.finhubData.deleteMany();
    console.log(`✅ Cleared ${result.count} FinhubData records`);
  }

  // PolygonData operations
  async upsertPolygonData(data: CreatePolygonData[]): Promise<void> {       
    console.log(`→ Upserting ${data.length} polygon symbols (batched)...`); 
    if (data.length === 0) return;

    const batchSize = 100;
    let total = 0;
    for (let i = 0; i < data.length; i += batchSize) {
      const batch = data.slice(i, i + batchSize);
      await prisma.$transaction(
        batch.map(record =>
          prisma.polygonData.upsert({
            where: { symbol: record.symbol },
            update: {
              symbolBoolean: record.symbolBoolean ?? false,
              marketCap: record.marketCap ?? null,
              previousMarketCap: record.previousMarketCap ?? null,
              marketCapDiff: record.marketCapDiff ?? null,
              marketCapBoolean: record.marketCapBoolean ?? false,
              price: record.price ?? null,
              previousCloseRaw: record.previousCloseRaw ?? null,
              previousCloseAdj: record.previousCloseAdj ?? null,
              previousCloseSource: record.previousCloseSource ?? null,      
              changeFromPrevClosePct: record.changeFromPrevClosePct ?? null,
              changeFromOpenPct: record.changeFromOpenPct ?? null,
              sessionRef: record.sessionRef ?? null,
              qualityFlags: record.qualityFlags as any,
              change: record.change ?? null,
              size: record.size ?? null,
              name: record.name ?? null,
              priceBoolean: record.priceBoolean ?? false,
              Boolean: record.Boolean ?? false,
              priceSource: record.priceSource ?? null,
            },
            create: {
              symbol: record.symbol,
              symbolBoolean: record.symbolBoolean ?? false,
              marketCap: record.marketCap ?? null,
              previousMarketCap: record.previousMarketCap ?? null,
              marketCapDiff: record.marketCapDiff ?? null,
              marketCapBoolean: record.marketCapBoolean ?? false,
              price: record.price ?? null,
              previousCloseRaw: record.previousCloseRaw ?? null,
              previousCloseAdj: record.previousCloseAdj ?? null,
              previousCloseSource: record.previousCloseSource ?? null,      
              changeFromPrevClosePct: record.changeFromPrevClosePct ?? null,
              changeFromOpenPct: record.changeFromOpenPct ?? null,
              sessionRef: record.sessionRef ?? null,
              qualityFlags: record.qualityFlags as any,
              change: record.change ?? null,
              size: record.size ?? null,
              name: record.name ?? null,
              priceBoolean: record.priceBoolean ?? false,
              Boolean: record.Boolean ?? false,
              priceSource: record.priceSource ?? null,
            },
          })
        )
      );
      total += batch.length;
    }
    console.log(`✓ Successfully upserted ${total} polygon symbols`);        
  }

  async copySymbolsToPolygonData(): Promise<void> {
    console.log('🔄 Copying symbols from FinhubData to PolygonData...');    

    const symbols = await prisma.finhubData.findMany({
      select: { symbol: true },
      distinct: ['symbol'],
      where: { symbol: { not: '' } }
    });

    for (const symbol of symbols) {
      await prisma.polygonData.upsert({
        where: { symbol: symbol.symbol },
        create: {
          symbol: symbol.symbol,
          symbolBoolean: true
        },
        update: {
          symbolBoolean: true
        }
      });
    }

    console.log(`✓ PolygonData: inserted ${symbols.length} (deduped) symbols`);
  }

  async getUniqueSymbolsFromPolygonData(onlyReady = false): Promise<string[]> {
    const symbols = await prisma.polygonData.findMany({
      select: { symbol: true },
      ...(onlyReady ? { where: { Boolean: true } } : {}),
    });
    return symbols.map(s => s.symbol);
  }

  async getPolygonSymbols(onlyReady = false): Promise<string[]> {
    return this.getUniqueSymbolsFromPolygonData(onlyReady);
  }

  async updatePolygonMarketCapData(marketData: any[]): Promise<void> {      
    console.log(`→ Upserting market cap data for ${marketData.length} symbols in batches...`);

    const batchSize = 100;
    let totalUpserted = 0;

    for (let i = 0; i < marketData.length; i += batchSize) {
      const batch = marketData.slice(i, i + batchSize);
      console.log(`→ Processing batch ${Math.floor(i / batchSize) + 1}/${Math.ceil(marketData.length / batchSize)} (${batch.length} records)...`);      

      await prisma.$transaction(
        batch.map(data =>
          prisma.polygonData.upsert({
            where: {
              symbol: data.symbol,
            },
            create: {
              symbol: data.symbol,
              symbolBoolean: Boolean(data.symbolBoolean ?? 0),
              marketCap: data.marketCap,
              previousMarketCap: data.previousMarketCap,
              marketCapDiff: data.marketCapDiff,
              marketCapBoolean: Boolean(data.marketCapBoolean ?? 0),
              price: data.price,
              previousCloseRaw: data.previousCloseRaw,
              previousCloseAdj: data.previousCloseAdj,
              previousCloseSource: data.previousCloseSource,
              changeFromPrevClosePct: data.changeFromPrevClosePct,
              changeFromOpenPct: data.changeFromOpenPct,
              sessionRef: data.sessionRef,
              qualityFlags: data.qualityFlags,
              change: data.change,
              size: data.size,
              name: data.name,
              priceBoolean: Boolean(data.priceBoolean ?? 0),
              Boolean: Boolean(data.Boolean ?? 0),
              priceSource: data.priceSource,
              ...(data.logoUrl !== undefined && { logoUrl: data.logoUrl }), 
              ...(data.logoSource !== undefined && { logoSource: data.logoSource }),
              ...(data.logoFetchedAt !== undefined && { logoFetchedAt: data.logoFetchedAt }),
            },
            update: {
              symbolBoolean: Boolean(data.symbolBoolean ?? 0),
              marketCap: data.marketCap,
              previousMarketCap: data.previousMarketCap,
              marketCapDiff: data.marketCapDiff,
              marketCapBoolean: Boolean(data.marketCapBoolean ?? 0),
              price: data.price,
              previousCloseRaw: data.previousCloseRaw,
              previousCloseAdj: data.previousCloseAdj,
              previousCloseSource: data.previousCloseSource,
              changeFromPrevClosePct: data.changeFromPrevClosePct,
              changeFromOpenPct: data.changeFromOpenPct,
              sessionRef: data.sessionRef,
              qualityFlags: data.qualityFlags,
              change: data.change,
              size: data.size,
              name: data.name,
              priceBoolean: Boolean(data.priceBoolean ?? 0),
              Boolean: Boolean(data.Boolean ?? 0),
              priceSource: data.priceSource,
              ...(data.logoUrl !== undefined && { logoUrl: data.logoUrl }), 
              ...(data.logoSource !== undefined && { logoSource: data.logoSource }),
              ...(data.logoFetchedAt !== undefined && { logoFetchedAt: data.logoFetchedAt }),
            },
          })
        )
      );

      totalUpserted += batch.length;
    }

    console.log(`✓ Successfully upserted market cap data for ${totalUpserted} symbols in ${Math.ceil(marketData.length / batchSize)} batches`);
  }

  async clearPolygonData(): Promise<void> {
    console.log('🗑️ Clearing PolygonData...');
    const result = await prisma.polygonData.deleteMany();
    console.log(`✅ Cleared ${result.count} PolygonData records`);
  }

  // FinalReport operations
  async generateFinalReport(): Promise<void> {
    console.log('🔄 Generating FinalReport from FinhubData and PolygonData...');

    const finhubSymbols = await prisma.finhubData.findMany({
      select: { symbol: true },
      distinct: ['symbol'],
    });

    const polygonSymbols = await prisma.polygonData.findMany({
      select: { symbol: true },
      where: {
        // Relaxed condition: accept records with marketCap present even if live price is missing
        marketCap: { not: null }
      },
    });

    const finhubSymbolSet = new Set(finhubSymbols.map(s => s.symbol));      
    const polygonSymbolSet = new Set(polygonSymbols.map(s => s.symbol));    

    const commonSymbols = Array.from(finhubSymbolSet).filter(symbol => polygonSymbolSet.has(symbol));

    console.log(`📊 Found ${commonSymbols.length} symbols in both FinhubData and PolygonData (marketCap present)`);
    // Use deterministic timestamps for this run (NY midnight)
    const { getRunTimestamps } = await import('../utils/time.js');
    const { reportDate: reportDateISO, snapshotDate: snapshotDateISO } = getRunTimestamps();

    // Fetch all required rows in bulk to reduce round-trips
    const finRows = await prisma.finhubData.findMany({
      where: { symbol: { in: commonSymbols } },
      orderBy: [{ symbol: 'asc' }, { reportDate: 'desc' }],
    });
    const finMap = new Map<string, typeof finRows[number]>();
    for (const row of finRows) {
      if (!finMap.has(row.symbol)) finMap.set(row.symbol, row);
    }
    const polRows = await prisma.polygonData.findMany({ where: { symbol: { in: commonSymbols } } });
    const polMap = new Map(polRows.map(r => [r.symbol, r] as const));

    const upserts: Parameters<typeof prisma.finalReport.upsert>[0][] = [];
    for (const symbol of commonSymbols) {
      const finhubData = finMap.get(symbol);
      const polygonData = polMap.get(symbol);

      if (finhubData && polygonData) {
        const epsSurp = (finhubData.epsActual != null && finhubData.epsEstimate != null && finhubData.epsEstimate !== 0)
          ? ((finhubData.epsActual - finhubData.epsEstimate) / Math.abs(finhubData.epsEstimate)) * 100
          : null;

        const revSurp = (finhubData.revenueActual != null && finhubData.revenueEstimate != null && finhubData.revenueEstimate !== 0n)
          ? new Decimal(finhubData.revenueActual.toString()).minus(finhubData.revenueEstimate.toString()).div(new Decimal(finhubData.revenueEstimate.toString()).abs()).times(100).toNumber()
          : null;

        const priceToUse = polygonData.price ?? polygonData.previousCloseRaw;
        const roundedPrice = priceToUse != null ? Math.round(priceToUse * 100) / 100 : null;
        const roundedChange = polygonData.change !== null && polygonData.change !== undefined ? Math.round(polygonData.change * 100) / 100 : null;      
        const roundedEpsActual = finhubData.epsActual != null ? Math.round(finhubData.epsActual * 100) / 100 : null;
        const roundedEpsEst = finhubData.epsEstimate != null ? Math.round(finhubData.epsEstimate * 100) / 100 : null;
        const roundedEpsSurp = epsSurp != null ? Math.round(epsSurp * 100) / 100 : null;
        const roundedRevSurp = revSurp != null ? Math.round(revSurp * 100) / 100 : null;

        const createData = normalizeFinalReportDates({
          symbol,
          name: polygonData.name,
          size: polygonData.size,
          marketCap: polygonData.marketCap,
          marketCapDiff: polygonData.marketCapDiff,
          price: roundedPrice,
          change: roundedChange,
          epsActual: roundedEpsActual,
          epsEst: roundedEpsEst,
          epsSurp: roundedEpsSurp,
          revActual: finhubData.revenueActual,
          revEst: finhubData.revenueEstimate,
          revSurp: roundedRevSurp,
          reportDate: reportDateISO,
          snapshotDate: snapshotDateISO,
          logoUrl: finhubData.logoUrl,
          logoSource: finhubData.logoSource,
          logoFetchedAt: finhubData.logoFetchedAt,
        });

        const updateData = normalizeFinalReportDates({
          name: polygonData.name,
          size: polygonData.size,
          marketCap: polygonData.marketCap,
          marketCapDiff: polygonData.marketCapDiff,
          price: roundedPrice,
          change: roundedChange,
          epsActual: roundedEpsActual,
          epsEst: roundedEpsEst,
          epsSurp: roundedEpsSurp,
          revActual: finhubData.revenueActual,
          revEst: finhubData.revenueEstimate,
          revSurp: roundedRevSurp,
          reportDate: reportDateISO,
          snapshotDate: snapshotDateISO,
          logoUrl: finhubData.logoUrl,
          logoSource: finhubData.logoSource,
          logoFetchedAt: finhubData.logoFetchedAt,
        });

        upserts.push({
          where: { symbol },
          create: createData,
          update: updateData,
        });
      }
    }
    if (upserts.length > 0) {
      await prisma.$transaction(upserts.map(d => prisma.finalReport.upsert(d)));
    }
    console.log(`✅ FinalReport snapshot stored: ${upserts.length} symbols`);
  }

  async getFinalReport(): Promise<any[]> {
    return await prisma.finalReport.findMany({
      orderBy: { symbol: 'asc' },
    });
  }

  async clearFinalReport(): Promise<void> {
    console.log('🗑️ Clearing FinalReport...');
    const result = await prisma.finalReport.deleteMany();
    console.log(`✅ Cleared ${result.count} FinalReport records`);
  }

  async updateLogoInfo(symbol: string, logoUrl: string | null, logoSource: string | null): Promise<void> {
    // Update both FinhubData and FinalReport to maintain consistency
    await Promise.all([
      // Update FinhubData (for backward compatibility)
      prisma.finhubData.updateMany({
        where: { symbol },
        data: {
          logoUrl,
          logoSource,
          logoFetchedAt: new Date(),
        },
      }),
      // Update FinalReport (primary source for frontend)
      prisma.finalReport.updateMany({
        where: { symbol },
        data: {
          logoUrl,
          logoSource,
          logoFetchedAt: new Date(),
        },
      })
    ]);
    
    // Reduced logging for better performance
    if (logoUrl) {
      console.log(`   → Updated logo info for ${symbol}: ${logoUrl} (${logoSource})`);
    }
  }

  // Batch update logo info for better performance
  async batchUpdateLogoInfo(updates: Array<{ symbol: string; logoUrl: string | null; logoSource: string | null }>): Promise<void> {
    if (updates.length === 0) return;
    
    console.log(`   → Batch updating logo info for ${updates.length} symbols...`);
    
    const batchSize = 50;
    for (let i = 0; i < updates.length; i += batchSize) {
      const batch = updates.slice(i, i + batchSize);
      
      await prisma.$transaction(
        batch.map(update =>
          prisma.finhubData.updateMany({
            where: { symbol: update.symbol },
            data: {
              logoUrl: update.logoUrl,
              logoSource: update.logoSource,
              logoFetchedAt: new Date(),
            },
          })
        )
      );
    }
    
    const successCount = updates.filter(u => u.logoUrl).length;
    console.log(`   → Batch updated ${successCount}/${updates.length} logos successfully`);
  }

  async getSymbolsNeedingLogoRefresh(): Promise<string[]> {
    const symbols = await prisma.finhubData.findMany({
      where: {
        OR: [
          { logoUrl: null },
          { logoSource: null },
          { logoFetchedAt: null },
          {
            logoFetchedAt: {
              lt: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000),
            },
          },
        ],
      },
      select: { symbol: true },
      distinct: ['symbol'],
    });

    return symbols.map(s => s.symbol);
  }

  async updateCronStatus(jobType: string, status: 'success' | 'error' | 'running', recordsProcessed?: number, errorMessage?: string): Promise<void> {   
    await prisma.cronStatus.upsert({
      where: { jobType },
      create: {
        jobType,
        lastRunAt: new Date(),
        status,
        recordsProcessed,
        errorMessage,
      },
      update: {
        lastRunAt: new Date(),
        status,
        recordsProcessed,
        errorMessage,
      },
    });
  }

  async getLastCronRun(jobType: string): Promise<Date | null> {
    const status = await prisma.cronStatus.findUnique({
      where: { jobType },
      select: { lastRunAt: true },
    });
    return status?.lastRunAt || null;
  }

  async getAllCronStatuses(): Promise<any[]> {
    return await prisma.cronStatus.findMany({
      orderBy: { lastRunAt: 'desc' },
    });
  }

  async clearAllTables(): Promise<void> {
    if (process.env.ALLOW_CLEAR !== 'true') {
      console.log('🧹 Skipping clearAllTables (ALLOW_CLEAR!=true)');
      return;
    }
    console.log('🛑 Clearing all database tables (transaction)...');

    await prisma.$transaction([
      prisma.finalReport.deleteMany(),
      prisma.polygonData.deleteMany(),
      prisma.finhubData.deleteMany(),
      prisma.cronStatus.deleteMany(),
    ]);

    console.log('✅ All tables cleared successfully');
  }

  async disconnect(): Promise<void> {
    await prisma.$disconnect();
  }
}

// Singleton instance
export const db = new DatabaseManager();
