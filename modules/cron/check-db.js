import { PrismaClient } from "@prisma/client";

const prisma = new PrismaClient();

async function checkDatabase() {
  try {
    // Check FinhubData
    const finhubCount = await prisma.finhubData.count();
    console.log(`📊 FinhubData records: ${finhubCount}`);

    // Check PolygonData
    const polygonCount = await prisma.polygonData.count();
    console.log(`📊 PolygonData records: ${polygonCount}`);

    // Get unique symbols from FinhubData
    const symbols = await prisma.finhubData.findMany({
      select: { symbol: true },
      distinct: ["symbol"],
    });
    console.log(`📊 Unique symbols in FinhubData: ${symbols.length}`);
    console.log(
      `📊 Sample symbols: ${symbols
        .slice(0, 5)
        .map((s) => s.symbol)
        .join(", ")}`
    );
  } catch (error) {
    console.error("Error checking database:", error);
  } finally {
    await prisma.$disconnect();
  }
}

checkDatabase();
