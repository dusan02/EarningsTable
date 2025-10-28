import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function checkPriceIssues() {
  try {
    console.log("=== PRICE ISSUES ANALYSIS ===");
    
    // Check symbols with market cap but no price
    const noPrice = await prisma.polygonData.findMany({
      where: { 
        Boolean: false, 
        marketCap: { not: null },
        price: null 
      },
      select: { symbol: true, name: true, marketCap: true, size: true },
      take: 5
    });
    
    console.log("Examples of symbols with market cap but no price:");
    noPrice.forEach((r, i) => {
      console.log(`${i+1}. ${r.symbol} (${r.name}):`);
      console.log(`   marketCap: ${r.marketCap}`);
      console.log(`   size: ${r.size}`);
    });
    
    // Check if it's a size issue
    const sizeBreakdown = await prisma.polygonData.groupBy({
      by: ['size'],
      where: { Boolean: false, marketCap: { not: null }, price: null },
      _count: { symbol: true }
    });
    
    console.log("\nSize breakdown for symbols without prices:");
    sizeBreakdown.forEach(s => {
      console.log(`${s.size}: ${s._count.symbol} symbols`);
    });
    
  } catch (error) {
    console.error("Error:", error.message);
  } finally {
    await prisma.$disconnect();
  }
}

checkPriceIssues();
