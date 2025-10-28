import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function checkPolygonDataDetails() {
  try {
    console.log("=== POLYGON DATA DETAILED ANALYSIS ===");

    const polygonData = await prisma.polygonData.findMany({
      select: {
        symbol: true,
        name: true,
        marketCap: true,
        price: true,
        size: true,
        Boolean: true,
      },
      take: 10,
    });

    console.log("Sample PolygonData records:");
    polygonData.forEach((r, i) => {
      console.log(`${i + 1}. ${r.symbol}:`);
      console.log(`   name: ${r.name}`);
      console.log(`   marketCap: ${r.marketCap}`);
      console.log(`   price: ${r.price}`);
      console.log(`   size: ${r.size}`);
      console.log(`   Boolean: ${r.Boolean}`);
      console.log("---");
    });

    // Count null values
    const totalCount = await prisma.polygonData.count();
    const nameNullCount = await prisma.polygonData.count({
      where: { name: null },
    });
    const marketCapNullCount = await prisma.polygonData.count({
      where: { marketCap: null },
    });
    const priceNullCount = await prisma.polygonData.count({
      where: { price: null },
    });
    const sizeNullCount = await prisma.polygonData.count({
      where: { size: null },
    });

    console.log("\nNULL VALUES ANALYSIS:");
    console.log("Total records:", totalCount);
    console.log("name is null:", nameNullCount);
    console.log("marketCap is null:", marketCapNullCount);
    console.log("price is null:", priceNullCount);
    console.log("size is null:", sizeNullCount);
  } catch (error) {
    console.error("Error:", error.message);
  } finally {
    await prisma.$disconnect();
  }
}

checkPolygonDataDetails();
