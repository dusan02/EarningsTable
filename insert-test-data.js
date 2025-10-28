const { PrismaClient } = require('@prisma/client');
const prisma = new PrismaClient();

async function insertTestData() {
  try {
    // Clear existing data
    await prisma.finalReport.deleteMany();
    console.log('✅ Cleared existing data');
    
    // Insert test data
    const testData = [
      {
        symbol: 'AAPL',
        name: 'Apple Inc.',
        size: 'Mega',
        marketCap: BigInt(2500000000000),
        marketCapDiff: BigInt(50000000000),
        price: 150.25,
        change: 2.15,
        epsActual: 1.52,
        epsEst: 1.43,
        epsSurp: 6.29,
        revActual: BigInt(89498000000),
        revEst: BigInt(88500000000),
        revSurp: 1.13,
        logoUrl: null,
        logoSource: null,
        logoFetchedAt: null,
        reportDate: new Date(),
        snapshotDate: new Date(),
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        symbol: 'MSFT',
        name: 'Microsoft Corporation',
        size: 'Mega',
        marketCap: BigInt(2200000000000),
        marketCapDiff: BigInt(-30000000000),
        price: 295.80,
        change: -1.02,
        epsActual: 2.93,
        epsEst: 2.85,
        epsSurp: 2.81,
        revActual: BigInt(52857000000),
        revEst: BigInt(52500000000),
        revSurp: 0.68,
        logoUrl: null,
        logoSource: null,
        logoFetchedAt: null,
        reportDate: new Date(),
        snapshotDate: new Date(),
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        symbol: 'GOOGL',
        name: 'Alphabet Inc.',
        size: 'Mega',
        marketCap: BigInt(1800000000000),
        marketCapDiff: BigInt(25000000000),
        price: 135.45,
        change: 1.85,
        epsActual: 1.55,
        epsEst: 1.45,
        epsSurp: 6.90,
        revActual: BigInt(76093000000),
        revEst: BigInt(75000000000),
        revSurp: 1.46,
        logoUrl: null,
        logoSource: null,
        logoFetchedAt: null,
        reportDate: new Date(),
        snapshotDate: new Date(),
        createdAt: new Date(),
        updatedAt: new Date()
      }
    ];
    
    await prisma.finalReport.createMany({ data: testData });
    console.log('✅ Inserted test data');
    
    const count = await prisma.finalReport.count();
    console.log('📊 Total records:', count);
    
  } catch (error) {
    console.error('❌ Error:', error);
  } finally {
    await prisma.$disconnect();
  }
}

insertTestData();
