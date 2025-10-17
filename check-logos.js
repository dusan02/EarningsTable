const { PrismaClient } = require("@prisma/client");

const prisma = new PrismaClient({
  datasources: {
    db: {
      url:
        process.env.DATABASE_URL ||
        "file:D:/Projects/EarningsTable/modules/database/prisma/dev.db",
    },
  },
});

async function checkLogos() {
  try {
    const data = await prisma.finalReport.findMany({
      select: {
        symbol: true,
        logoUrl: true,
        logoSource: true,
      },
    });

    console.log("FinalReport logos:");
    data.forEach((item) => {
      console.log(`${item.symbol}: ${item.logoUrl} (${item.logoSource})`);
    });

    console.log(`\nTotal records: ${data.length}`);
    console.log(
      `Records with logos: ${data.filter((item) => item.logoUrl).length}`
    );
  } catch (error) {
    console.error("Error:", error);
  } finally {
    await prisma.$disconnect();
  }
}

checkLogos();
