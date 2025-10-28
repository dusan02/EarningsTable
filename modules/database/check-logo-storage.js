import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function checkLogoStorage() {
  try {
    console.log("=== FINHUB DATA LOGO STATUS ===");
    const total = await prisma.finhubData.count();
    const withLogos = await prisma.finhubData.count({
      where: { logoUrl: { not: null } },
    });

    console.log("Total FinhubData records:", total);
    console.log("With logos:", withLogos);
    console.log("Without logos:", total - withLogos);
    console.log("Logo coverage:", Math.round((withLogos / total) * 100) + "%");
    console.log("");

    console.log("=== SAMPLE FINHUB DATA WITH LOGOS ===");
    const samples = await prisma.finhubData.findMany({
      where: { logoUrl: { not: null } },
      select: {
        symbol: true,
        logoUrl: true,
        logoSource: true,
        logoFetchedAt: true,
      },
      take: 5,
    });

    samples.forEach((r) => {
      console.log(
        `${r.symbol}: ${r.logoUrl} (${
          r.logoSource
        }) - ${r.logoFetchedAt?.toDateString()}`
      );
    });

    console.log("");
    console.log("=== FINAL REPORT LOGO STATUS ===");
    const finalTotal = await prisma.finalReport.count();
    const finalWithLogos = await prisma.finalReport.count({
      where: { logoUrl: { not: null } },
    });

    console.log("Total FinalReport records:", finalTotal);
    console.log("With logos:", finalWithLogos);
    console.log("Without logos:", finalTotal - finalWithLogos);
    console.log(
      "Logo coverage:",
      Math.round((finalWithLogos / finalTotal) * 100) + "%"
    );

    console.log("");
    console.log("=== LOGO SOURCES BREAKDOWN ===");
    const sources = await prisma.finhubData.groupBy({
      by: ["logoSource"],
      where: { logoSource: { not: null } },
      _count: { symbol: true },
    });

    sources.forEach((source) => {
      console.log(`${source.logoSource}: ${source._count.symbol} symbols`);
    });
  } catch (error) {
    console.error("Error:", error.message);
  } finally {
    await prisma.$disconnect();
  }
}

checkLogoStorage();
