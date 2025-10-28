import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function analyzeLogos() {
  try {
    console.log("=== LOGO ANALYSIS ===");
    
    // Check symbols needing logo refresh
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
      distinct: ['symbol'],
    });
    
    console.log("Symbols needing logo refresh:", symbolsNeedingLogos.length);
    
    // Check current logo status
    const allSymbols = await prisma.finhubData.findMany({
      select: { symbol: true },
      distinct: ['symbol']
    });
    const totalSymbols = allSymbols.length;
    
    const symbolsWithLogos = await prisma.finhubData.findMany({
      where: { logoUrl: { not: null } },
      select: { symbol: true },
      distinct: ['symbol']
    });
    const withLogos = symbolsWithLogos.length;
    const withoutLogos = totalSymbols - withLogos;
    
    console.log("\nLogo Status:");
    console.log("Total symbols:", totalSymbols);
    console.log("With logos:", withLogos);
    console.log("Without logos:", withoutLogos);
    console.log("Logo coverage:", Math.round((withLogos / totalSymbols) * 100) + "%");
    
    // Check logo sources
    const logoSources = await prisma.finhubData.groupBy({
      by: ['logoSource'],
      where: { logoUrl: { not: null } },
      _count: { symbol: true }
    });
    
    console.log("\nLogo Sources:");
    logoSources.forEach(s => {
      console.log(`${s.logoSource || 'null'}: ${s._count.symbol} symbols`);
    });
    
    // Check recent logo fetches
    const recentLogos = await prisma.finhubData.findMany({
      where: { 
        logoFetchedAt: { 
          gte: new Date(Date.now() - 24 * 60 * 60 * 1000) // Last 24 hours
        }
      },
      select: { symbol: true, logoSource: true, logoFetchedAt: true },
      orderBy: { logoFetchedAt: 'desc' },
      take: 10
    });
    
    console.log("\nRecent logo fetches (last 24h):");
    recentLogos.forEach(r => {
      console.log(`${r.symbol}: ${r.logoSource} (${r.logoFetchedAt})`);
    });
    
  } catch (error) {
    console.error("Error:", error.message);
  } finally {
    await prisma.$disconnect();
  }
}

analyzeLogos();
