import { PrismaClient } from "@prisma/client";
const prisma = new PrismaClient();

async function checkBooleanFalse() {
  try {
    console.log("=== BOOLEAN FALSE ANALYSIS ===");
    
    // Get some examples of Boolean = false
    const falseExamples = await prisma.polygonData.findMany({
      where: { Boolean: false },
      select: { symbol: true, name: true, marketCap: true, price: true, size: true },
      take: 10
    });
    
    console.log("Examples of Boolean = false:");
    falseExamples.forEach((r, i) => {
      console.log(`${i+1}. ${r.symbol}:`);
      console.log(`   name: ${r.name}`);
      console.log(`   marketCap: ${r.marketCap}`);
      console.log(`   price: ${r.price}`);
      console.log(`   size: ${r.size}`);
      console.log("---");
    });
    
    // Check why they have Boolean = false
    const withMarketCap = await prisma.polygonData.count({ 
      where: { Boolean: false, marketCap: { not: null } } 
    });
    const withPrice = await prisma.polygonData.count({ 
      where: { Boolean: false, price: { not: null } } 
    });
    
    console.log("\nBoolean = false analysis:");
    console.log("With marketCap:", withMarketCap);
    console.log("With price:", withPrice);
    
  } catch (error) {
    console.error("Error:", error.message);
  } finally {
    await prisma.$disconnect();
  }
}

checkBooleanFalse();
