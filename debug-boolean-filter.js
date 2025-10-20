const { PrismaClient } = require('@prisma/client');
const prisma = new PrismaClient();

async function debugBooleanFilter() {
  const symbols = ['HBCP', 'SFST', 'AVBH', 'FLXS', 'LODE', 'NTZ', 'ENTO', 'ADN', 'BOKF', 'PFBC'];
  
  console.log('=== DEBUGGING BOOLEAN FILTER ===');
  
  for (const symbol of symbols) {
    console.log(`\n--- ${symbol} ---`);
    
    // Check FinhubData
    const finhub = await prisma.finnhubData.findFirst({ where: { symbol } });
    if (finnhub) {
      console.log(`FinnhubData: Boolean=${finnhub.boolean}`);
    } else {
      console.log('FinnhubData: NOT FOUND');
      continue;
    }
    
    // Check PolygonData
    const polygon = await prisma.polygonData.findFirst({ where: { symbol } });
    if (polygon) {
      console.log(`PolygonData: Boolean=${polygon.boolean}, selectedPrice=${polygon.selectedPrice}, marketCap=${polygon.marketCap}`);
    } else {
      console.log('PolygonData: NOT FOUND');
      continue;
    }
    
    // Check why Boolean might be false
    if (finnhub.boolean === false) {
      console.log('❌ FinnhubData.boolean = false');
    }
    if (polygon.boolean === false) {
      console.log('❌ PolygonData.boolean = false');
      if (!polygon.selectedPrice) {
        console.log('  → selectedPrice is null/undefined');
      }
      if (!polygon.marketCap) {
        console.log('  → marketCap is null/undefined');
      }
    }
  }
  
  await prisma.$disconnect();
}

debugBooleanFilter().catch(console.error);
