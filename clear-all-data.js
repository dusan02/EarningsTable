const { PrismaClient } = require('@prisma/client');

async function clearAllData() {
  const prisma = new PrismaClient();
  
  try {
    console.log('🧹 Clearing all data from tables...');
    
    // Clear FinalReport
    const finalResult = await prisma.finalReport.deleteMany({});
    console.log(`✅ Deleted ${finalResult.count} records from FinalReport`);
    
    // Clear PolygonData
    const polygonResult = await prisma.polygonData.deleteMany({});
    console.log(`✅ Deleted ${polygonResult.count} records from PolygonData`);
    
    // Clear FinhubData
    const finhubResult = await prisma.finhubData.deleteMany({});
    console.log(`✅ Deleted ${finhubResult.count} records from FinhubData`);
    
    console.log('🎉 All data cleared successfully!');
    
  } catch (error) {
    console.error('Error:', error);
  } finally {
    await prisma.$disconnect();
  }
}

clearAllData();
