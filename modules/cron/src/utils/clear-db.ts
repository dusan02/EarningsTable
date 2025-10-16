import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient({
  datasources: {
    db: {
      url: process.env.DATABASE_URL || 'file:../../database/prisma/dev.db'
    }
  }
});

async function clearAll() {
  console.log('🗑️ Clearing FinalReport, PolygonData, FinhubData...');
  
  try {
    // Transakčné mazanie všetkých tabuliek
    const result = await prisma.$transaction([
      prisma.finalReport.deleteMany({}),
      prisma.polygonData.deleteMany({}),
      prisma.finhubData.deleteMany({}),
    ]);
    
    console.log(`✅ Cleared all tables:`);
    console.log(`   - FinalReport: ${result[0].count} records`);
    console.log(`   - PolygonData: ${result[1].count} records`);
    console.log(`   - FinhubData: ${result[2].count} records`);
    
  } catch (error) {
    console.error('❌ Error clearing database:', error);
    throw error;
  }
}

clearAll()
  .catch((e) => {
    console.error('❌ Clear database failed:', e);
    process.exit(1);
  })
  .finally(() => {
    prisma.$disconnect();
  });
