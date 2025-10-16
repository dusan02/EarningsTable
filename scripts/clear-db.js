import { PrismaClient } from "@prisma/client";

const prisma = new PrismaClient();

async function main() {
  console.log("🛑 Clearing FinalReport, PolygonData, FinhubData…");

  // Pozor na poradie kvôli FK / deriváciám: najprv FinalReport
  const [finalReportResult, polygonDataResult, finhubDataResult] =
    await prisma.$transaction([
      prisma.finalReport.deleteMany({}),
      prisma.polygonData.deleteMany({}),
      prisma.finhubData.deleteMany({}),
    ]);

  console.log("✅ Cleared:");
  console.log(`   - FinalReport: ${finalReportResult.count} records`);
  console.log(`   - PolygonData: ${polygonDataResult.count} records`);
  console.log(`   - FinhubData: ${finhubDataResult.count} records`);
}

main()
  .catch((e) => {
    console.error("❌ Clear failed:", e);
    process.exit(1);
  })
  .finally(() => prisma.$disconnect());
