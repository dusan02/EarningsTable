const { PrismaClient } = require('@prisma/client');
const prisma = new PrismaClient();

async function checkData() {
  const symbols = ['HBCP', 'SFST', 'AVBH', 'FLXS', 'LODE', 'NTZ', 'ENTO', 'ADN', 'BOKF', 'PFBC'];
  
  console.log('=== FINAL REPORT DATA ===');
  for (const symbol of symbols) {
    const final = await prisma.finalReport.findFirst({ where: { symbol } });
    if (final) {
      console.log(`${symbol}: price=${final.price}, change=${final.change}, changePercent=${final.changePercent}`);
    } else {
      console.log(`${symbol}: NOT FOUND in FinalReport`);
    }
  }
  
  console.log('\n=== POLYGON DATA ===');
  for (const symbol of symbols) {
    const polygon = await prisma.polygonData.findFirst({ where: { symbol } });
    if (polygon) {
      console.log(`${symbol}: selectedPrice=${polygon.selectedPrice}, prevCloseRaw=${polygon.prevCloseRaw}, prevCloseAdj=${polygon.prevCloseAdj}`);
    } else {
      console.log(`${symbol}: NOT FOUND in PolygonData`);
    }
  }
  
  await prisma.$disconnect();
}

checkData().catch(console.error);
