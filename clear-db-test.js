const { PrismaClient } = require('@prisma/client');

const prisma = new PrismaClient({
  datasources: {
    db: {
      url: process.env.DATABASE_URL || "file:D:/Projects/EarningsTable/modules/database/prisma/dev.db"
    }
  }
});

async function clearDatabase() {
  try {
    console.log('🗑️  Clearing database...');
    
    // Delete all data from all tables
    await prisma.finalReport.deleteMany();
    console.log('✅ FinalReport cleared');
    
    await prisma.finnhubData.deleteMany();
    console.log('✅ FinnhubData cleared');
    
    await prisma.polygonData.deleteMany();
    console.log('✅ PolygonData cleared');
    
    console.log('🎯 Database completely cleared!');
    
  } catch (error) {
    console.error('❌ Error clearing database:', error);
  } finally {
    await prisma.$disconnect();
  }
}

clearDatabase();
