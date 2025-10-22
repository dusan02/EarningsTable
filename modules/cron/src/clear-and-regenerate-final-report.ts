import { db } from './core/DatabaseManager.js';

async function clearAndRegenerate() {
  try {
    console.log('🗑️ Clearing FinalReport table...');
// await db.clearFinalReport(); // disabled: run only in daily clear job
    
    console.log('🔄 Regenerating FinalReport with Boolean = 1 condition...');
    await db.generateFinalReport();
    
    const reports = await db.getFinalReport();
    console.log(`✅ FinalReport regenerated with ${reports.length} records (Boolean = 1 only)`);
    
    if (reports.length > 0) {
      console.log('\n🔍 Records with Boolean = 1:');
      reports.forEach((report, index) => {
        console.log(`${index + 1}. ${report.symbol} (${report.name || 'N/A'}) - Size: ${report.size || 'N/A'}, Price: ${report.price ? `$${report.price.toFixed(2)}` : 'N/A'}`);
      });
    }
    
  } catch (error) {
    console.error('❌ Error:', error);
    throw error;
  } finally {
    await db.disconnect();
  }
}

clearAndRegenerate().catch(async (e) => {
  console.error('✗ Script failed:', e);
  await db.disconnect();
  process.exit(1);
});
