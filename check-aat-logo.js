const { PrismaClient } = require("@prisma/client");
const prisma = new PrismaClient();

async function checkAATLogo() {
  try {
    const aatData = await prisma.finalReport.findFirst({
      where: { symbol: "AAT" },
      select: { symbol: true, logoUrl: true, logoSource: true },
    });

    console.log("📊 AAT logo data:", aatData);

    if (aatData && aatData.logoUrl) {
      console.log("✅ AAT has logo URL:", aatData.logoUrl);
      console.log("📁 Logo source:", aatData.logoSource);
    } else {
      console.log("❌ AAT has no logo URL");
    }
  } catch (error) {
    console.error("❌ Error:", error);
  } finally {
    await prisma.$disconnect();
  }
}

checkAATLogo();
