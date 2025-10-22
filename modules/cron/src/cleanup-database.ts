/**
 * Database cleanup script
 * Removes invalid future dates and fixes data integrity issues
 */

import { prisma } from '../../shared/src/prismaClient.js';
import { getNYMidnight } from './utils/time.js';

async function cleanupDatabase() {
  console.log('🧹 Starting database cleanup...');
  
  try {
    // Get current NY date
    const currentNYDate = getNYMidnight();
    const futureDate = new Date(currentNYDate.getTime() + (24 * 60 * 60 * 1000)); // +1 day
    
    console.log(`Current NY date: ${currentNYDate.toISOString()}`);
    console.log(`Future threshold: ${futureDate.toISOString()}`);
    
    // Check for future dates in FinalReport
    const futureReports = await prisma.finalReport.findMany({
      where: {
        reportDate: {
          gt: futureDate
        }
      },
      select: {
        id: true,
        symbol: true,
        reportDate: true
      }
    });
    
    console.log(`Found ${futureReports.length} records with future dates`);
    
    if (futureReports.length > 0) {
      console.log('Sample future records:');
      futureReports.slice(0, 5).forEach(record => {
        console.log(`  - ${record.symbol}: ${record.reportDate.toISOString()}`);
      });
      
      // Delete future records
      const deleteResult = await prisma.finalReport.deleteMany({
        where: {
          reportDate: {
            gt: futureDate
          }
        }
      });
      
      console.log(`✅ Deleted ${deleteResult.count} future records`);
    }
    
    // Check for invalid timestamps (like 1761004800000)
    const invalidTimestamp = new Date(1761004800000);
    console.log(`Checking for invalid timestamp: ${invalidTimestamp.toISOString()}`);
    
    const invalidReports = await prisma.finalReport.findMany({
      where: {
        reportDate: {
          gte: invalidTimestamp,
          lt: new Date(invalidTimestamp.getTime() + (24 * 60 * 60 * 1000))
        }
      },
      select: {
        id: true,
        symbol: true,
        reportDate: true
      }
    });
    
    console.log(`Found ${invalidReports.length} records with invalid timestamp`);
    
    if (invalidReports.length > 0) {
      // Delete invalid timestamp records
      const deleteInvalidResult = await prisma.finalReport.deleteMany({
        where: {
          reportDate: {
            gte: invalidTimestamp,
            lt: new Date(invalidTimestamp.getTime() + (24 * 60 * 60 * 1000))
          }
        }
      });
      
      console.log(`✅ Deleted ${deleteInvalidResult.count} invalid timestamp records`);
    }
    
    // Check database statistics
    const totalReports = await prisma.finalReport.count();
    const todayReports = await prisma.finalReport.count({
      where: {
        reportDate: {
          gte: currentNYDate,
          lt: new Date(currentNYDate.getTime() + (24 * 60 * 60 * 1000))
        }
      }
    });
    
    console.log(`📊 Database statistics:`);
    console.log(`  - Total FinalReport records: ${totalReports}`);
    console.log(`  - Today's records: ${todayReports}`);
    
    // Check for duplicate symbols on same date
    const duplicates = await prisma.$queryRaw`
      SELECT symbol, "reportDate", COUNT(*) as count
      FROM "FinalReport"
      GROUP BY symbol, "reportDate"
      HAVING COUNT(*) > 1
    `;
    
    if (Array.isArray(duplicates) && duplicates.length > 0) {
      console.log(`⚠️  Found ${duplicates.length} duplicate symbol-date combinations`);
      console.log('Sample duplicates:');
      (duplicates as any[]).slice(0, 3).forEach(dup => {
        console.log(`  - ${dup.symbol} on ${dup.reportDate}: ${dup.count} records`);
      });
    } else {
      console.log('✅ No duplicate symbol-date combinations found');
    }
    
    console.log('🎉 Database cleanup completed successfully!');
    
  } catch (error) {
    console.error('❌ Database cleanup failed:', error);
    throw error;
  } finally {
    await prisma.$disconnect();
  }
}

// Run cleanup if called directly
if (import.meta.url === `file://${process.argv[1]}`) {
  cleanupDatabase()
    .then(() => {
      console.log('✅ Cleanup script completed');
      process.exit(0);
    })
    .catch((error) => {
      console.error('❌ Cleanup script failed:', error);
      process.exit(1);
    });
}

export { cleanupDatabase };
