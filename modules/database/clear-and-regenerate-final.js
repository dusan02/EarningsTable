import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function clearAndRegenerate() {
  try {
    console.log("🗑️ Clearing FinalReport...");
    await prisma.finalReport.deleteMany();
    console.log("✅ FinalReport cleared");

    // Now regenerate with Boolean = true condition
    const finhubSymbols = await prisma.finhubData.findMany({
      select: { symbol: true },
      distinct: ["symbol"],
    });

    const polygonSymbols = await prisma.polygonData.findMany({
      select: { symbol: true },
      where: { Boolean: true },
    });

    const finhubSymbolSet = new Set(finhubSymbols.map((s) => s.symbol));
    const polygonSymbolSet = new Set(polygonSymbols.map((s) => s.symbol));

    const commonSymbols = Array.from(finhubSymbolSet).filter((symbol) =>
      polygonSymbolSet.has(symbol)
    );

    console.log(`📊 Found ${commonSymbols.length} symbols with Boolean = true`);

    for (const symbol of commonSymbols) {
      const finhubData = await prisma.finhubData.findFirst({
        where: { symbol },
        orderBy: { reportDate: "desc" },
      });

      const polygonData = await prisma.polygonData.findUnique({
        where: { symbol },
      });

      if (finhubData && polygonData) {
        await prisma.finalReport.create({
          data: {
            symbol,
            name: polygonData.name,
            size: polygonData.size,
            marketCap: polygonData.marketCap,
            marketCapDiff: polygonData.marketCapDiff,
            price: polygonData.price,
            change: polygonData.changeFromPrevClosePct,
            epsActual: finhubData.epsActual,
            epsEst: finhubData.epsEstimate,
            revActual: finhubData.revenueActual,
            revEst: finhubData.revenueEstimate,
            reportDate: new Date(),
            snapshotDate: new Date(),
          },
        });
      }
    }

    const finalCount = await prisma.finalReport.count();
    console.log(`✅ FinalReport regenerated with ${finalCount} records`);
  } catch (error) {
    console.error("Error:", error.message);
  } finally {
    await prisma.$disconnect();
  }
}

clearAndRegenerate();
