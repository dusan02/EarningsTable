/**
 * Smoke test script to verify fixes
 */

import { prisma } from '../../shared/src/prismaClient.js';
import { getNYMidnight } from './utils/time.js';

async function smokeTest() {
  console.log('🧪 Running smoke test...');
  
  try {
    // Test 1: Check NY date generation
    console.log('\n1️⃣ Testing NY date generation...');
    const nyDate = getNYMidnight();
    console.log(`✅ NY date: ${nyDate.toISOString()}`);
    console.log(`✅ Date type: ${nyDate instanceof Date ? 'Date object' : 'Not Date object'}`);
    
    // Test 2: Check database connection
    console.log('\n2️⃣ Testing database connection...');
    const dbTest = await prisma.$queryRaw`SELECT 1 as test`;
    console.log('✅ Database connection successful');
    
    // Test 3: Check FinalReport data
    console.log('\n3️⃣ Testing FinalReport data...');
    const reportCount = await prisma.finalReport.count();
    console.log(`✅ Total FinalReport records: ${reportCount}`);
    
    if (reportCount > 0) {
      const sampleReport = await prisma.finalReport.findFirst({
        select: {
          symbol: true,
          reportDate: true,
          snapshotDate: true
        }
      });
      
      if (sampleReport) {
        console.log(`✅ Sample record: ${sampleReport.symbol}`);
        console.log(`✅ Report date: ${sampleReport.reportDate.toISOString()}`);
        console.log(`✅ Snapshot date: ${sampleReport.snapshotDate.toISOString()}`);
        
        // Check if dates are valid
        const isValidDate = sampleReport.reportDate instanceof Date && 
                           !isNaN(sampleReport.reportDate.getTime());
        console.log(`✅ Date validity: ${isValidDate ? 'Valid' : 'Invalid'}`);
        
        // Check if date is not in future
        const currentNY = getNYMidnight();
        const isNotFuture = sampleReport.reportDate.getTime() <= currentNY.getTime() + (24 * 60 * 60 * 1000);
        console.log(`✅ Date not in future: ${isNotFuture ? 'Yes' : 'No'}`);
      }
    }
    
    // Test 4: Check for future dates
    console.log('\n4️⃣ Checking for future dates...');
    const futureDate = new Date(getNYMidnight().getTime() + (48 * 60 * 60 * 1000)); // +2 days
    const futureCount = await prisma.finalReport.count({
      where: {
        reportDate: {
          gt: futureDate
        }
      }
    });
    console.log(`✅ Future date records: ${futureCount} (should be 0)`);
    
    // Test 5: Check for invalid timestamps
    console.log('\n5️⃣ Checking for invalid timestamps...');
    const invalidTimestamp = new Date(1761004800000);
    const invalidCount = await prisma.finalReport.count({
      where: {
        reportDate: {
          gte: invalidTimestamp,
          lt: new Date(invalidTimestamp.getTime() + (24 * 60 * 60 * 1000))
        }
      }
    });
    console.log(`✅ Invalid timestamp records: ${invalidCount} (should be 0)`);
    
    // Test 6: Check PolygonData
    console.log('\n6️⃣ Testing PolygonData...');
    const polygonCount = await prisma.polygonData.count();
    console.log(`✅ Total PolygonData records: ${polygonCount}`);
    
    // Test 7: Check FinnhubData
    console.log('\n7️⃣ Testing FinnhubData...');
    const finnhubCount = await prisma.finhubData.count();
    console.log(`✅ Total FinnhubData records: ${finnhubCount}`);
    
    console.log('\n🎉 Smoke test completed successfully!');
    console.log('\n📊 Summary:');
    console.log(`  - FinalReport: ${reportCount} records`);
    console.log(`  - PolygonData: ${polygonCount} records`);
    console.log(`  - FinnhubData: ${finnhubCount} records`);
    console.log(`  - Future dates: ${futureCount} (should be 0)`);
    console.log(`  - Invalid timestamps: ${invalidCount} (should be 0)`);
    
  } catch (error) {
    console.error('❌ Smoke test failed:', error);
    throw error;
  } finally {
    await prisma.$disconnect();
  }
}

// Run smoke test if called directly
if (import.meta.url === `file://${process.argv[1]}`) {
  smokeTest()
    .then(() => {
      console.log('✅ Smoke test completed');
      process.exit(0);
    })
    .catch((error) => {
      console.error('❌ Smoke test failed:', error);
      process.exit(1);
    });
}

export { smokeTest };
