import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function fixPolygonData() {
  try {
    console.log("🔧 Fixing PolygonData Boolean values...");

    // Get all symbols from FinhubData
    const finhubSymbols = await prisma.finhubData.findMany({
      select: { symbol: true },
    });

    console.log("Found", finhubSymbols.length, "symbols in FinhubData");

    // Update PolygonData Boolean to true for all FinhubData symbols
    const result = await prisma.polygonData.updateMany({
      where: {
        symbol: {
          in: finhubSymbols.map((s) => s.symbol),
        },
      },
      data: {
        Boolean: true,
      },
    });

    console.log("Updated", result.count, "PolygonData records");

    // Check the result
    const trueCount = await prisma.polygonData.count({
      where: { Boolean: true },
    });

    console.log("PolygonData records with Boolean=true:", trueCount);
  } catch (error) {
    console.error("Error:", error.message);
  } finally {
    await prisma.$disconnect();
  }
}

fixPolygonData();
