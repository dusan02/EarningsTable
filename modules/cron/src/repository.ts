import { prisma } from '../../shared/src/prismaClient.js';

export type FinhubReportRow = {
  reportDate: Date;
  symbol: string;
  hour?: string | null;
  epsActual?: number | null;
  epsEstimate?: number | null;
  revenueActual?: number | null;
  revenueEstimate?: number | null;
  quarter?: number | null;
  year?: number | null;
};

export async function upsertReports(rows: FinhubReportRow[]) {
  console.log(`→ Upserting ${rows.length} finhub reports...`);
  
  for (const r of rows) {
    try {
      await prisma.finhubData.upsert({
        where: {
          reportDate_symbol: { 
            reportDate: r.reportDate, 
            symbol: r.symbol 
          },
        },
        update: {
          hour: r.hour ?? null,
          epsActual: r.epsActual ?? null,
          epsEstimate: r.epsEstimate ?? null,
          // BigInt cast pre revenue hodnoty
          revenueActual: r.revenueActual != null ? BigInt(Math.round(r.revenueActual)) : null,
          revenueEstimate: r.revenueEstimate != null ? BigInt(Math.round(r.revenueEstimate)) : null,
          quarter: r.quarter ?? null,
          year: r.year ?? null,
        },
        create: {
          reportDate: r.reportDate,
          symbol: r.symbol,
          hour: r.hour ?? null,
          epsActual: r.epsActual ?? null,
          epsEstimate: r.epsEstimate ?? null,
          revenueActual: r.revenueActual != null ? BigInt(Math.round(r.revenueActual)) : null,
          revenueEstimate: r.revenueEstimate != null ? BigInt(Math.round(r.revenueEstimate)) : null,
          quarter: r.quarter ?? null,
          year: r.year ?? null,
        },
      });
    } catch (error) {
      console.error(`Error upserting report for ${r.symbol} on ${r.reportDate.toISOString()}:`, error);
      throw error;
    }
  }
  
  console.log(`✓ Successfully upserted ${rows.length} reports`);
}

export async function getReportsByDate(date: Date) {
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

export async function disconnect() {
  await prisma.$disconnect();
}
