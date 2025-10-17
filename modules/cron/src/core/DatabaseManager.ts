import { prisma } from '../../../shared/src/prismaClient.js';
import { CreateFinhubData, CreatePolygonData, CreateFinalReport } from '../../../shared/src/types.js';
import { Prisma } from '@prisma/client';

export class DatabaseManager {
  public prisma = prisma;

  /**
   * Utility function for FinalReport upsert with automatic marketCapDiff calculation
   * 
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
  }): Promise<void> {
    const prevMC = incoming.previousMarketCap ?? null;
    const changePct = incoming.change ?? null;

    // marketCapDiff = previousMarketCap * (change / 100)
    const marketCapDiff = prevMC != null && changePct != null ? Math.round(prevMC * (changePct / 100)) : null;
    const currentMarketCap = prevMC != null && marketCapDiff != null ? prevMC + marketCapDiff : null;

    await prisma.finalReport.upsert({
      where: { symbol: incoming.symbol },
      create: {
        symbol: incoming.symbol,
        name: incoming.name ?? null,
        size: incoming.size ?? null,
        marketCap: currentMarketCap != null ? BigInt(currentMarketCap) : null,
        marketCapDiff: marketCapDiff != null ? BigInt(marketCapDiff) : null,
        price: incoming.price ?? null,
        change: changePct ?? null,
        epsActual: incoming.epsActual ?? null,
        epsEst: incoming.epsEst ?? null,
        epsSurp: incoming.epsSurp ?? (incoming.epsActual != null && incoming.epsEst != null && incoming.epsEst !== 0
          ? ((incoming.epsActual / incoming.epsEst) * 100) - 100
          : null),
        revActual: incoming.revActual != null ? BigInt(incoming.revActual) : null,
        revEst: incoming.revEst != null ? BigInt(incoming.revEst) : null,
        revSurp: (incoming.revActual != null && incoming.revEst != null && Number(incoming.revEst) !== 0
          ? ((Number(incoming.revActual) / Number(incoming.revEst)) * 100) - 100
          : null),
      },
      update: {
        name: incoming.name ?? null,
        size: incoming.size ?? null,
        marketCap: currentMarketCap != null ? BigInt(currentMarketCap) : null,
        marketCapDiff: marketCapDiff != null ? BigInt(marketCapDiff) : null,
        price: incoming.price ?? null,
        change: changePct ?? null,
        epsActual: incoming.epsActual ?? null,
        epsEst: incoming.epsEst ?? null,
        epsSurp: incoming.epsSurp ?? (incoming.epsActual != null && incoming.epsEst != null && incoming.epsEst !== 0
          ? ((incoming.epsActual / incoming.epsEst) * 100) - 100
          : null),
        revActual: incoming.revActual != null ? BigInt(incoming.revActual) : null,
        revEst: incoming.revEst != null ? BigInt(incoming.revEst) : null,
        revSurp: (incoming.revActual != null && incoming.revEst != null && Number(incoming.revEst) !== 0
          ? ((Number(incoming.revActual) / Number(incoming.revEst)) * 100) - 100
          : null),
      },
    });
  }
  // FinhubData operations
  async upsertFinhubData(data: CreateFinhubData[]): Promise<void> {
    console.log(`→ Upserting ${data.length} finhub reports in batch transaction...`);
    
    if (data.length === 0) {
      console.log('✓ No data to upsert');
      return;
    }

    // Batch operations for better performance
    const batchSize = 100;

    // Execute batches sequentially to avoid overwhelming the database
    let totalUpserted = 0;
    for (let i = 0; i < data.length; i += batchSize) {
      const batch = data.slice(i, i + batchSize);
      console.log(`→ Processing batch ${Math.floor(i / batchSize) + 1}/${Math.ceil(data.length / batchSize)} (${batch.length} records)...`);
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
  }

  async getFinhubDataByDate(date: Date) {
    return await prisma.finhubData.findMany({
      where: {
        reportDate: {
          gte: new Date(date.getFullYear(), date.getMonth(), date.getDate()),
          lt: new Date(date.getFullYear(), date.getMonth(), date.getDate() + 1),
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
        console.log(`→ Upserting ${data.length} polygon symbols...`);
        
        for (const record of data) {
          await prisma.polygonData.upsert({
            where: {
              symbol: record.symbol,
            },
            update: {
              symbolBoolean: record.symbolBoolean ?? false,
              marketCap: record.marketCap ?? null,
              previousMarketCap: record.previousMarketCap ?? null,
              marketCapDiff: record.marketCapDiff ?? null,
              marketCapBoolean: record.marketCapBoolean ?? false,
              price: record.price ?? null,
              previousClose: record.previousClose ?? null,
              change: record.change ?? null,
              size: record.size ?? null,
              name: record.name ?? null,
              priceBoolean: record.priceBoolean ?? false,
              Boolean: record.Boolean ?? false,
            },
            create: {
              symbol: record.symbol,
              symbolBoolean: record.symbolBoolean ?? false,
              marketCap: record.marketCap ?? null,
              previousMarketCap: record.previousMarketCap ?? null,
              marketCapDiff: record.marketCapDiff ?? null,
              marketCapBoolean: record.marketCapBoolean ?? false,
              price: record.price ?? null,
              previousClose: record.previousClose ?? null,
              change: record.change ?? null,
              size: record.size ?? null,
              name: record.name ?? null,
              priceBoolean: record.priceBoolean ?? false,
              Boolean: record.Boolean ?? false,
            },
          });
        }
        
        console.log(`✓ Successfully upserted ${data.length} polygon symbols`);
      }

      async copySymbolsToPolygonData(): Promise<void> {
        console.log('🔄 Copying symbols from FinhubData to PolygonData...');
        
        const symbols = await prisma.finhubData.findMany({
          select: { symbol: true },
          distinct: ['symbol'],
          where: { symbol: { not: '' } }
        });
        
        // Use upsert for each symbol to handle duplicates
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

      async getUniqueSymbolsFromPolygonData(): Promise<string[]> {
        const symbols = await prisma.polygonData.findMany({
          select: {
            symbol: true,
          },
        });
        
        return symbols.map(s => s.symbol);
      }

      async getPolygonSymbols(): Promise<string[]> {
        return this.getUniqueSymbolsFromPolygonData();
      }

      async updatePolygonMarketCapData(marketData: any[]): Promise<void> {
        console.log(`→ Upserting market cap data for ${marketData.length} symbols in batches...`);
        
        // Process in batches of 100 for better performance
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
                  symbolBoolean: Boolean(data.symbolBoolean ?? 0), // Convert to boolean
                  marketCap: data.marketCap,
                  previousMarketCap: data.previousMarketCap,
                  marketCapDiff: data.marketCapDiff,
                  marketCapBoolean: Boolean(data.marketCapBoolean ?? 0), // Convert to boolean
                  price: data.price,
                  previousClose: data.previousClose,
                  change: data.change,
                  size: data.size,
                  name: data.name,
                  priceBoolean: Boolean(data.priceBoolean ?? 0), // Convert to boolean
                  Boolean: Boolean(data.Boolean ?? 0), // Convert to boolean
                  // Logo fields - only set if provided
                  ...(data.logoUrl !== undefined && { logoUrl: data.logoUrl }),
                  ...(data.logoSource !== undefined && { logoSource: data.logoSource }),
                  ...(data.logoFetchedAt !== undefined && { logoFetchedAt: data.logoFetchedAt }),
                },
                update: {
                  symbolBoolean: Boolean(data.symbolBoolean ?? 0), // Convert to boolean
                  marketCap: data.marketCap,
                  previousMarketCap: data.previousMarketCap,
                  marketCapDiff: data.marketCapDiff,
                  marketCapBoolean: Boolean(data.marketCapBoolean ?? 0), // Convert to boolean
                  price: data.price,
                  previousClose: data.previousClose,
                  change: data.change,
                  size: data.size,
                  name: data.name,
                  priceBoolean: Boolean(data.priceBoolean ?? 0), // Convert to boolean
                  Boolean: Boolean(data.Boolean ?? 0), // Convert to boolean
                  // Logo fields - only update if provided (preserve existing)
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
        
        // Get all symbols that exist in both tables
        const finhubSymbols = await prisma.finhubData.findMany({
          select: { symbol: true },
          distinct: ['symbol'],
        });
        
        // Get only symbols from PolygonData where Boolean = true (all conditions met)
        const polygonSymbols = await prisma.polygonData.findMany({
          select: { symbol: true },
          where: { 
            Boolean: true
          },
        });
        
        const finhubSymbolSet = new Set(finhubSymbols.map(s => s.symbol));
        const polygonSymbolSet = new Set(polygonSymbols.map(s => s.symbol));
        
        // Find symbols that exist in both tables AND have Boolean = true in PolygonData
        const commonSymbols = Array.from(finhubSymbolSet).filter(symbol => polygonSymbolSet.has(symbol));
        
        console.log(`📊 Found ${commonSymbols.length} symbols in both FinhubData and PolygonData with Boolean = true (all conditions met)`);
        
        for (const symbol of commonSymbols) {
          // Get data from FinhubData
          const finhubData = await prisma.finhubData.findFirst({
            where: { symbol },
            orderBy: { reportDate: 'desc' }, // Get the most recent data
          });
          
          // Get data from PolygonData
          const polygonData = await prisma.polygonData.findUnique({
            where: { symbol },
          });
          
          if (finhubData && polygonData) {
            // Calculate percentage differences
            const epsSurp = finhubData.epsActual && finhubData.epsEstimate 
              ? ((finhubData.epsActual / finhubData.epsEstimate) * 100) - 100
              : null;
            
            const revSurp = finhubData.revenueActual && finhubData.revenueEstimate 
              ? ((Number(finhubData.revenueActual) / Number(finhubData.revenueEstimate)) * 100) - 100
              : null;
            
            // Round decimal values to max 2 decimal places
            const roundedPrice = polygonData.price ? Math.round(polygonData.price * 100) / 100 : null;
            const roundedChange = polygonData.change !== null && polygonData.change !== undefined ? Math.round(polygonData.change * 100) / 100 : null;
            const roundedEpsActual = finhubData.epsActual ? Math.round(finhubData.epsActual * 100) / 100 : null;
            const roundedEpsEst = finhubData.epsEstimate ? Math.round(finhubData.epsEstimate * 100) / 100 : null;
            const roundedEpsSurp = epsSurp ? Math.round(epsSurp * 100) / 100 : null;
            const roundedRevSurp = revSurp ? Math.round(revSurp * 100) / 100 : null;
            
            // Use direct upsert since marketCap and marketCapDiff are already calculated in PolygonData
            await prisma.finalReport.upsert({
              where: { symbol },
              create: {
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
                // Copy logo fields from FinhubData
                logoUrl: finhubData.logoUrl,
                logoSource: finhubData.logoSource,
                logoFetchedAt: finhubData.logoFetchedAt,
              },
              update: {
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
                // Copy logo fields from FinhubData
                logoUrl: finhubData.logoUrl,
                logoSource: finhubData.logoSource,
                logoFetchedAt: finhubData.logoFetchedAt,
              },
            });
          }
        }
        
        console.log(`✅ Successfully generated FinalReport for ${commonSymbols.length} symbols`);
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

  /**
   * Update logo information for a symbol
   */
      async updateLogoInfo(symbol: string, logoUrl: string | null, logoSource: string | null): Promise<void> {
        // Update all records for this symbol (there might be multiple report dates)
        await prisma.finhubData.updateMany({
          where: { symbol },
          data: {
            logoUrl,
            logoSource,
            logoFetchedAt: new Date(),
          },
        });
        console.log(`   → Updated logo info for ${symbol}: ${logoUrl} (${logoSource})`);
      }

  /**
   * Get symbols that need logo refresh
   */
  async getSymbolsNeedingLogoRefresh(): Promise<string[]> {
    const symbols = await prisma.finhubData.findMany({
      where: {
        OR: [
          { logoUrl: null },
          { logoFetchedAt: null },
          {
            logoFetchedAt: {
              lt: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000), // 30 days ago
            },
          },
        ],
      },
      select: { symbol: true },
      distinct: ['symbol'],
    });

    return symbols.map(s => s.symbol);
  }

  /**
   * Update cron job status
   */
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

  /**
   * Get last cron run timestamp
   */
  async getLastCronRun(jobType: string): Promise<Date | null> {
    const status = await prisma.cronStatus.findUnique({
      where: { jobType },
      select: { lastRunAt: true },
    });
    return status?.lastRunAt || null;
  }

  /**
   * Get all cron statuses
   */
  async getAllCronStatuses(): Promise<any[]> {
    return await prisma.cronStatus.findMany({
      orderBy: { lastRunAt: 'desc' },
    });
  }

  async clearAllTables(): Promise<void> {
    console.log('🛑 Clearing all database tables...');
    
    // Clear tables in correct order (respecting foreign key constraints)
    await prisma.finalReport.deleteMany();
    await prisma.polygonData.deleteMany();
    await prisma.finhubData.deleteMany();
    await prisma.cronStatus.deleteMany();
    
    console.log('✅ All tables cleared successfully');
  }

  async disconnect(): Promise<void> {
    await prisma.$disconnect();
  }
}

// Singleton instance
export const db = new DatabaseManager();