import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function checkFinalReport() {
  try {
    console.log("=== FINAL REPORT ANALYSIS ===");

    const finalReport = await prisma.finalReport.findMany({
      select: {
        symbol: true,
        name: true,
        marketCap: true,
        price: true,
        logoUrl: true,
        size: true,
      },
      take: 10,
    });

    console.log("Sample FinalReport records:");
    finalReport.forEach((r, i) => {
      console.log(`${i + 1}. ${r.symbol}:`);
      console.log(`   name: ${r.name}`);
      console.log(`   marketCap: ${r.marketCap}`);
      console.log(`   price: ${r.price}`);
      console.log(`   logoUrl: ${r.logoUrl}`);
      console.log(`   size: ${r.size}`);
      console.log("---");
    });

    // Count null values
    const totalCount = await prisma.finalReport.count();
    const nameNullCount = await prisma.finalReport.count({
      where: { name: null },
    });
    const marketCapNullCount = await prisma.finalReport.count({
      where: { marketCap: null },
    });
    const priceNullCount = await prisma.finalReport.count({
      where: { price: null },
    });
    const logoUrlNullCount = await prisma.finalReport.count({
      where: { logoUrl: null },
    });

    console.log("\nFINAL REPORT NULL VALUES:");
    console.log("Total records:", totalCount);
    console.log("name is null:", nameNullCount);
    console.log("marketCap is null:", marketCapNullCount);
    console.log("price is null:", priceNullCount);
    console.log("logoUrl is null:", logoUrlNullCount);
  } catch (error) {
    console.error("Error:", error.message);
  } finally {
    await prisma.$disconnect();
  }
}

checkFinalReport();
