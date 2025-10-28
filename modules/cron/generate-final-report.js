import { DatabaseManager } from "./src/core/DatabaseManager.js";

async function generateFinalReport() {
  console.log("🔄 Generating Final Report...");

  const db = new DatabaseManager();

  try {
    await db.generateFinalReport();
    console.log("✅ Final Report generated successfully!");
  } catch (error) {
    console.error("❌ Error generating Final Report:", error);
  } finally {
    await db.disconnect();
  }
}

generateFinalReport();
