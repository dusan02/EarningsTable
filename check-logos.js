const { PrismaClient } = require("@prisma/client");
const prisma = new PrismaClient();

async function checkLogos() {
  try {
    const finhubCount = await prisma.finhubData.count({
      where: { logoUrl: { not: null } },
    });
    const finalReportCount = await prisma.finalReport.count({
      where: { logoUrl: { not: null } },
    });

    console.log("📊 Logo counts:");
    console.log("  FinhubData with logos:", finhubCount);
    console.log("  FinalReport with logos:", finalReportCount);

    if (finhubCount > 0 && finalReportCount === 0) {
      console.log("❌ PROBLEM: FinhubData has logos but FinalReport does not!");
    } else if (finhubCount > 0 && finalReportCount > 0) {
      console.log("✅ Both tables have logos");
    } else {
      console.log("❌ No logos found in either table");
    }
  } catch (error) {
    console.error("❌ Error:", error);
  } finally {
    await prisma.$disconnect();
  }
}

checkLogos();
