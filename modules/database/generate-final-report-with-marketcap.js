import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function generateFinalReportWithMarketCap() {
  try {
    console.log("🔄 Generating FinalReport with market cap condition...");

    const finhubSymbols = await prisma.finhubData.findMany({
      select: { symbol: true },
      distinct: ['symbol'],
    });

    // Changed condition: include symbols with market cap (even without price)
    const polygonSymbols = await prisma.polygonData.findMany({
      select: { symbol: true },
      where: {
        marketCap: { not: null } // Changed from Boolean: true to marketCap: { not: null }
      },
    });

    const finhubSymbolSet = new Set(finhubSymbols.map(s => s.symbol));      
    const polygonSymbolSet = new Set(polygonSymbols.map(s => s.symbol));    

    const commonSymbols = Array.from(finhubSymbolSet).filter(symbol => polygonSymbolSet.has(symbol));

    console.log(`📊 Found ${commonSymbols.length} symbols in both FinhubData and PolygonData with market cap`);
    
    const todayNY = new Date().toLocaleDateString('en-CA', { timeZone: 'America/New_York' });
    const reportDateISO = new Date(`${todayNY}T00:00:00.000Z`);
    const snapshotDateISO = reportDateISO;

    for (const symbol of commonSymbols) {
      const finhubData = await prisma.finhubData.findFirst({
        where: { symbol },
        orderBy: { reportDate: 'desc' }
      });

      const polygonData = await prisma.polygonData.findUnique({
        where: { symbol }
      });

      if (finhubData && polygonData) {
        // Calculate market cap diff and current market cap
        const prevMC = polygonData.previousMarketCap;
        const changePct = polygonData.changeFromPrevClosePct;
        
        let marketCapDiff = null;
        let currentMarketCap = polygonData.marketCap;
        
        if (prevMC != null && changePct != null) {
          const diff = (Number(prevMC) * changePct) / 100;
          marketCapDiff = BigInt(Math.round(diff));
        }

        // Round price and change to 2 decimal places
        const roundedPrice = polygonData.price ? Math.round(polygonData.price * 100) / 100 : null;
        const roundedChange = polygonData.changeFromPrevClosePct ? Math.round(polygonData.changeFromPrevClosePct * 100) / 100 : null;
        
        // Round EPS values
        const roundedEpsActual = finhubData.epsActual ? Math.round(finhubData.epsActual * 100) / 100 : null;
        const roundedEpsEst = finhubData.epsEstimate ? Math.round(finhubData.epsEstimate * 100) / 100 : null;
        const roundedEpsSurp = finhubData.epsActual && finhubData.epsEstimate ? 
          Math.round(((finhubData.epsActual - finhubData.epsEstimate) / finhubData.epsEstimate * 100) * 100) / 100 : null;
        
        // Round revenue values
        const roundedRevSurp = finhubData.revenueActual && finhubData.revenueEstimate ? 
          Math.round(((Number(finhubData.revenueActual) - Number(finhubData.revenueEstimate)) / Number(finhubData.revenueEstimate) * 100) * 100) / 100 : null;

        const createData = {
          symbol,
          name: polygonData.name,
          size: polygonData.size,
          marketCap: currentMarketCap,
          marketCapDiff,
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
          logoUrl: null, // Will be updated separately
          logoSource: null,
          logoFetchedAt: null,
        };

        const updateData = {
          name: polygonData.name,
          size: polygonData.size,
          marketCap: currentMarketCap,
          marketCapDiff,
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
        };

        await prisma.finalReport.upsert({
          where: { symbol },
          create: createData,
          update: updateData,
        });
      }
    }

    console.log(`✅ FinalReport snapshot stored: ${commonSymbols.length} symbols`);
    
    // Now copy logos from FinhubData
    console.log("🔄 Copying logos from FinhubData...");
    const finalReports = await prisma.finalReport.findMany({
      select: { symbol: true }
    });
    
    let updatedCount = 0;
    for (const report of finalReports) {
      const finhubData = await prisma.finhubData.findFirst({
        where: { symbol: report.symbol },
        select: { logoUrl: true, logoSource: true, logoFetchedAt: true }
      });
      
      if (finhubData && finhubData.logoUrl) {
        await prisma.finalReport.update({
          where: { symbol: report.symbol },
          data: {
            logoUrl: finhubData.logoUrl,
            logoSource: finhubData.logoSource,
            logoFetchedAt: finhubData.logoFetchedAt
          }
        });
        updatedCount++;
      }
    }
    
    console.log(`✅ Updated ${updatedCount} FinalReport records with logo data`);
    
  } catch (error) {
    console.error("Error:", error.message);
  } finally {
    await prisma.$disconnect();
  }
}

generateFinalReportWithMarketCap();
