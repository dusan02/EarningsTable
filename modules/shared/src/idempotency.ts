// modules/shared/src/idempotency.ts
import { prisma } from './prismaClient.js';

/**
 * Idempotency utilities to ensure safe re-runs
 */
export class IdempotencyManager {
  /**
   * Safe upsert with idempotency check
   */
  static async safeUpsertFinhubData(data: Array<{
    reportDate: Date;
    symbol: string;
    epsActual?: number | null;
    epsEstimate?: number | null;
    revenueActual?: number | null;
    revenueEstimate?: number | null;
    hour?: string | null;
    quarter?: number | null;
    year?: number | null;
    logoUrl?: string | null;
    logoSource?: string | null;
    logoFetchedAt?: Date | null;
  }>): Promise<{ changed: string[]; total: number }> {
    if (data.length === 0) return { changed: [], total: 0 };

    const changed = new Set<string>();
    
    // Process in batches for better performance
    const batchSize = 200;
    for (let i = 0; i < data.length; i += batchSize) {
      const batch = data.slice(i, i + batchSize);
      
      // Get existing records
      const keys = batch.map(r => ({ reportDate: r.reportDate, symbol: r.symbol }));
      const existing = await prisma.finhubData.findMany({
        where: { 
          OR: keys.map(k => ({
            reportDate: k.reportDate, 
            symbol: k.symbol
          })) 
        }
      });
      
      const existingMap = new Map(
        existing.map(e => [`${e.reportDate.toISOString()}|${e.symbol}`, e])
      );
      
      // Process each record
      for (const record of batch) {
        const key = `${record.reportDate.toISOString()}|${record.symbol}`;
        const existing = existingMap.get(key);
        
        if (!existing) {
          // New record
          changed.add(record.symbol);
          await prisma.finhubData.create({
            data: {
              reportDate: record.reportDate,
              symbol: record.symbol,
              epsActual: record.epsActual ?? null,
              epsEstimate: record.epsEstimate ?? null,
              revenueActual: record.revenueActual ? BigInt(Math.round(record.revenueActual)) : null,
              revenueEstimate: record.revenueEstimate ? BigInt(Math.round(record.revenueEstimate)) : null,
              hour: record.hour ?? null,
              quarter: record.quarter ?? null,
              year: record.year ?? null,
              logoUrl: record.logoUrl ?? null,
              logoSource: record.logoSource ?? null,
              logoFetchedAt: record.logoFetchedAt ?? null,
            }
          });
        } else {
          // Check if data changed
          const hasChanged = 
            (existing.epsActual ?? null) !== (record.epsActual ?? null) ||
            (existing.epsEstimate ?? null) !== (record.epsEstimate ?? null) ||
            (existing.revenueActual ?? null) !== (record.revenueActual ? BigInt(Math.round(record.revenueActual)) : null) ||
            (existing.revenueEstimate ?? null) !== (record.revenueEstimate ? BigInt(Math.round(record.revenueEstimate)) : null) ||
            (existing.hour ?? null) !== (record.hour ?? null) ||
            (existing.quarter ?? null) !== (record.quarter ?? null) ||
            (existing.year ?? null) !== (record.year ?? null) ||
            (existing.logoUrl ?? null) !== (record.logoUrl ?? null) ||
            (existing.logoSource ?? null) !== (record.logoSource ?? null);
          
          if (hasChanged) {
            changed.add(record.symbol);
            await prisma.finhubData.update({
              where: { id: existing.id },
              data: {
                epsActual: record.epsActual ?? null,
                epsEstimate: record.epsEstimate ?? null,
                revenueActual: record.revenueActual ? BigInt(Math.round(record.revenueActual)) : null,
                revenueEstimate: record.revenueEstimate ? BigInt(Math.round(record.revenueEstimate)) : null,
                hour: record.hour ?? null,
                quarter: record.quarter ?? null,
                year: record.year ?? null,
                logoUrl: record.logoUrl ?? null,
                logoSource: record.logoSource ?? null,
                logoFetchedAt: record.logoFetchedAt ?? null,
              }
            });
          }
        }
      }
    }
    
    return { changed: Array.from(changed), total: data.length };
  }

  /**
   * Safe upsert for FinalReport with idempotency
   */
  static async safeUpsertFinalReport(data: {
    symbol: string;
    name?: string | null;
    size?: string | null;
    marketCap?: bigint | null;
    marketCapDiff?: bigint | null;
    price?: number | null;
    change?: number | null;
    epsActual?: number | null;
    epsEst?: number | null;
    epsSurp?: number | null;
    revActual?: bigint | null;
    revEst?: bigint | null;
    revSurp?: number | null;
    logoUrl?: string | null;
    logoSource?: string | null;
    logoFetchedAt?: Date | null;
    reportDate?: Date | null;
    snapshotDate?: Date | null;
  }): Promise<boolean> {
    const existing = await prisma.finalReport.findUnique({
      where: { symbol: data.symbol }
    });

    if (!existing) {
      // Create new record
      await prisma.finalReport.create({
        data: {
          symbol: data.symbol,
          name: data.name ?? null,
          size: data.size ?? null,
          marketCap: data.marketCap ?? null,
          marketCapDiff: data.marketCapDiff ?? null,
          price: data.price ?? null,
          change: data.change ?? null,
          epsActual: data.epsActual ?? null,
          epsEst: data.epsEst ?? null,
          epsSurp: data.epsSurp ?? null,
          revActual: data.revActual ?? null,
          revEst: data.revEst ?? null,
          revSurp: data.revSurp ?? null,
          logoUrl: data.logoUrl ?? null,
          logoSource: data.logoSource ?? null,
          logoFetchedAt: data.logoFetchedAt ?? null,
          reportDate: data.reportDate ?? null,
          snapshotDate: data.snapshotDate ?? null,
        }
      });
      return true; // Created
    }

    // Check if data changed
    const hasChanged = 
      (existing.name ?? null) !== (data.name ?? null) ||
      (existing.size ?? null) !== (data.size ?? null) ||
      (existing.marketCap ?? null) !== (data.marketCap ?? null) ||
      (existing.marketCapDiff ?? null) !== (data.marketCapDiff ?? null) ||
      (existing.price ?? null) !== (data.price ?? null) ||
      (existing.change ?? null) !== (data.change ?? null) ||
      (existing.epsActual ?? null) !== (data.epsActual ?? null) ||
      (existing.epsEst ?? null) !== (data.epsEst ?? null) ||
      (existing.epsSurp ?? null) !== (data.epsSurp ?? null) ||
      (existing.revActual ?? null) !== (data.revActual ?? null) ||
      (existing.revEst ?? null) !== (data.revEst ?? null) ||
      (existing.revSurp ?? null) !== (data.revSurp ?? null) ||
      (existing.logoUrl ?? null) !== (data.logoUrl ?? null) ||
      (existing.logoSource ?? null) !== (data.logoSource ?? null);

    if (hasChanged) {
      await prisma.finalReport.update({
        where: { symbol: data.symbol },
        data: {
          name: data.name ?? null,
          size: data.size ?? null,
          marketCap: data.marketCap ?? null,
          marketCapDiff: data.marketCapDiff ?? null,
          price: data.price ?? null,
          change: data.change ?? null,
          epsActual: data.epsActual ?? null,
          epsEst: data.epsEst ?? null,
          epsSurp: data.epsSurp ?? null,
          revActual: data.revActual ?? null,
          revEst: data.revEst ?? null,
          revSurp: data.revSurp ?? null,
          logoUrl: data.logoUrl ?? null,
          logoSource: data.logoSource ?? null,
          logoFetchedAt: data.logoFetchedAt ?? null,
          reportDate: data.reportDate ?? null,
          snapshotDate: data.snapshotDate ?? null,
        }
      });
      return true; // Updated
    }

    return false; // No changes
  }

  /**
   * Check if pipeline was already processed today
   */
  static async wasProcessedToday(jobType: string): Promise<boolean> {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const lastRun = await prisma.cronStatus.findUnique({
      where: { jobType },
      select: { lastRunAt: true, status: true }
    });

    if (!lastRun || lastRun.status !== 'success') {
      return false;
    }

    const lastRunDate = new Date(lastRun.lastRunAt);
    lastRunDate.setHours(0, 0, 0, 0);
    
    return lastRunDate.getTime() === today.getTime();
  }

  /**
   * Mark pipeline as processed
   */
  static async markProcessed(jobType: string, recordsProcessed: number, errorMessage?: string): Promise<void> {
    await prisma.cronStatus.upsert({
      where: { jobType },
      update: {
        lastRunAt: new Date(),
        status: errorMessage ? 'error' : 'success',
        recordsProcessed,
        errorMessage: errorMessage ?? null,
      },
      create: {
        jobType,
        lastRunAt: new Date(),
        status: errorMessage ? 'error' : 'success',
        recordsProcessed,
        errorMessage: errorMessage ?? null,
      }
    });
  }
}
