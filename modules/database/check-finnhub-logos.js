import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function checkFinnhubLogos() {
  try {
    console.log("=== FINNHUB DATA LOGO ANALYSIS ===");

    const finhubData = await prisma.finhubData.findMany({
      select: {
        symbol: true,
        logoUrl: true,
        logoSource: true,
        logoFetchedAt: true,
      },
      take: 10,
    });

    console.log("Sample FinhubData logo records:");
    finhubData.forEach((r, i) => {
      console.log(`${i + 1}. ${r.symbol}:`);
      console.log(`   logoUrl: ${r.logoUrl}`);
      console.log(`   logoSource: ${r.logoSource}`);
      console.log(`   logoFetchedAt: ${r.logoFetchedAt}`);
      console.log("---");
    });

    // Count logo data
    const totalCount = await prisma.finhubData.count();
    const logoUrlNullCount = await prisma.finhubData.count({
      where: { logoUrl: null },
    });
    const logoUrlNotNullCount = await prisma.finhubData.count({
      where: { logoUrl: { not: null } },
    });

    console.log("\nLOGO ANALYSIS:");
    console.log("Total records:", totalCount);
    console.log("logoUrl is null:", logoUrlNullCount);
    console.log("logoUrl is NOT null:", logoUrlNotNullCount);
  } catch (error) {
    console.error("Error:", error.message);
  } finally {
    await prisma.$disconnect();
  }
}

checkFinnhubLogos();
