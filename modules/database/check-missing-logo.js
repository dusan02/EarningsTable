import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function checkMissingLogo() {
  try {
    const missing = await prisma.finhubData.findFirst({
      where: { logoUrl: null },
      select: { symbol: true },
    });
    console.log("Symbol without logo:", missing);
  } catch (error) {
    console.error("Error:", error.message);
  } finally {
    await prisma.$disconnect();
  }
}

checkMissingLogo();
