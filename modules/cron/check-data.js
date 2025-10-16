import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function checkData() {
  try {
    const polygonData = await prisma.polygonData.findMany({
      select: {
        symbol: true,
        Boolean: true,
        priceBoolean: true,
        marketCapBoolean: true,
        symbolBoolean: true,
        price: true,
        marketCap: true,
      },
    });

    console.log("PolygonData Boolean flags:");
    polygonData.slice(0, 5).forEach((p) => {
      console.log(
        `${p.symbol}: Boolean=${p.Boolean}, price=${p.priceBoolean}, marketCap=${p.marketCapBoolean}, symbol=${p.symbolBoolean}, price=${p.price}, marketCap=${p.marketCap}`
      );
    });

    const trueCount = polygonData.filter((p) => p.Boolean === true).length;
    console.log(
      `\nTotal with Boolean=true: ${trueCount}/${polygonData.length}`
    );

    // Check why Boolean is false
    const falseData = polygonData
      .filter((p) => p.Boolean === false)
      .slice(0, 3);
    console.log("\nExamples with Boolean=false:");
    falseData.forEach((p) => {
      console.log(
        `${p.symbol}: price=${p.price}, marketCap=${p.marketCap}, priceBool=${p.priceBoolean}, marketCapBool=${p.marketCapBoolean}`
      );
    });
  } catch (error) {
    console.error("Error:", error);
  } finally {
    await prisma.$disconnect();
  }
}

checkData();
