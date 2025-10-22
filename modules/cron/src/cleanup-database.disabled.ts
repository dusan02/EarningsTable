if (process.env.ALLOW_CLEAR !== "true") { console.log("🧹 Skipping startup cleanup (ALLOW_CLEAR!=true)"); }

import { prisma } from '../../shared/src/prismaClient.js';
import { getNYMidnight } from './utils/time.js';

async function cleanupDatabase() {
  console.log('🧹 Starting database cleanup...');
  
  try {
    const currentNYDate = getNYMidnight();
    const futureDate = new Date(currentNYDate.getTime() + (24 * 60 * 60 * 1000));
    
    console.log(`Current NY date: ${currentNYDate.toISOString()}`);
    
    const deleteResult = await prisma.finalReport.deleteMany({
      where: {
        reportDate: {
          gt: futureDate
        }
      }
    });
    
    console.log(`✅ Deleted ${deleteResult.count} future records`);
    
    const invalidTimestamp = new Date(1761004800000);
    const deleteInvalidResult = await prisma.finalReport.deleteMany({
      where: {
        reportDate: {
          gte: invalidTimestamp,
          lt: new Date(invalidTimestamp.getTime() + (24 * 60 * 60 * 1000))
        }
      }
    });
    
    console.log(`✅ Deleted ${deleteInvalidResult.count} invalid timestamp records`);
    
    const totalReports = await prisma.finalReport.count();
    console.log(`📊 Total FinalReport records: ${totalReports}`);
    
    console.log('🎉 Database cleanup completed!');
    
  } catch (error) {
    console.error('❌ Database cleanup failed:', error);
    throw error;
  } finally {
    await prisma.$disconnect();
  }
}

cleanupDatabase()
  .then(() => {
    console.log('✅ Cleanup script completed');
    process.exit(0);
  })
  .catch((error) => {
    console.error('❌ Cleanup script failed:', error);
    process.exit(1);
  });
