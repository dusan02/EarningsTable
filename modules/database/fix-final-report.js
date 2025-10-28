import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function fixFinalReport() {
  try {
    console.log("🔧 Fixing FinalReport with FinhubData logos...");

    // Get all FinalReport records
    const finalReports = await prisma.finalReport.findMany({
      select: { symbol: true },
    });

    console.log(`Found ${finalReports.length} FinalReport records`);

    let updatedCount = 0;

    for (const report of finalReports) {
      // Find corresponding FinhubData record
      const finhubData = await prisma.finhubData.findFirst({
        where: { symbol: report.symbol },
        select: { logoUrl: true, logoSource: true, logoFetchedAt: true },
      });

      if (finhubData && finhubData.logoUrl) {
        // Update FinalReport with logo data from FinhubData
        await prisma.finalReport.update({
          where: { symbol: report.symbol },
          data: {
            logoUrl: finhubData.logoUrl,
            logoSource: finhubData.logoSource,
            logoFetchedAt: finhubData.logoFetchedAt,
          },
        });
        updatedCount++;
      }
    }

    console.log(`Updated ${updatedCount} FinalReport records with logo data`);

    // Check the result
    const logoCount = await prisma.finalReport.count({
      where: { logoUrl: { not: null } },
    });

    console.log(`FinalReport records with logos: ${logoCount}`);
  } catch (error) {
    console.error("Error:", error.message);
  } finally {
    await prisma.$disconnect();
  }
}

fixFinalReport();
