const { PrismaClient } = require("@prisma/client");
const prisma = new PrismaClient();

async function clearOldData() {
  try {
    console.log("🗑️ Clearing old data...");

    // Clear all data from all tables
    await prisma.finalReport.deleteMany();
    console.log("✅ Cleared FinalReport");

    await prisma.finhubData.deleteMany();
    console.log("✅ Cleared FinhubData");

    await prisma.polygonData.deleteMany();
    console.log("✅ Cleared PolygonData");

    await prisma.cronStatus.deleteMany();
    console.log("✅ Cleared CronStatus");

    console.log("🎉 All old data cleared successfully!");
  } catch (error) {
    console.error("❌ Error clearing data:", error);
  } finally {
    await prisma.$disconnect();
  }
}

clearOldData();
