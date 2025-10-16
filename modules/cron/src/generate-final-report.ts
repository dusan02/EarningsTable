import { db } from './core/DatabaseManager.js';

async function generateFinalReport() {
  try {
    console.log('🚀 Starting FinalReport generation...');
    
    // Generate the final report by combining data from both tables
    await db.generateFinalReport();
    
    // Get and display the results
    const finalReports = await db.getFinalReport();
    console.log(`\n📊 FinalReport generated successfully!`);
    console.log(`📈 Total records: ${finalReports.length}`);
    
    if (finalReports.length > 0) {
      console.log('\n🔍 Sample records:');
      finalReports.slice(0, 5).forEach((report, index) => {
        console.log(`\n${index + 1}. ${report.symbol} (${report.name || 'N/A'})`);
        console.log(`   Size: ${report.size || 'N/A'}`);
        console.log(`   Market Cap: ${report.marketCap ? `$${(Number(report.marketCap) / 1000000000).toFixed(2)}B` : 'N/A'}`);
        console.log(`   Price: ${report.price ? `$${report.price.toFixed(2)}` : 'N/A'}`);
        console.log(`   Change: ${report.change ? `${report.change.toFixed(2)}%` : 'N/A'}`);
        console.log(`   EPS Actual: ${report.epsActual || 'N/A'}`);
        console.log(`   EPS Est: ${report.epsEst || 'N/A'}`);
        console.log(`   EPS Diff: ${report.epsDiff || 'N/A'}`);
        console.log(`   Rev Actual: ${report.revActual ? `$${(Number(report.revActual) / 1000000).toFixed(2)}M` : 'N/A'}`);
        console.log(`   Rev Est: ${report.revEst ? `$${(Number(report.revEst) / 1000000).toFixed(2)}M` : 'N/A'}`);
        console.log(`   Rev Diff: ${report.revDiff ? `$${(Number(report.revDiff) / 1000000).toFixed(2)}M` : 'N/A'}`);
      });
    }
    
    console.log('\n✅ FinalReport generation completed successfully!');
    
  } catch (error) {
    console.error('❌ FinalReport generation failed:', error);
    throw error;
  } finally {
    await db.disconnect();
  }
}

// Run the script
generateFinalReport().catch(async (e) => {
  console.error('✗ Script failed:', e);
  await db.disconnect();
  process.exit(1);
});
