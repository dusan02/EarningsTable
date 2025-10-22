import cron from 'node-cron';
import { db } from './core/DatabaseManager.js';

const CLEAR_SCHEDULE = '0 7 * * 1-5'; // 07:00 every weekday
const CRON_SCHEDULE = '*/5 * * * 1-5'; // every 5 minutes, Mon-Fri

async function clearAllData() {
  console.log('🗑️ Clearing all data...');
  await db.clearAllData();
  console.log('✅ Data cleared');
}

async function runPipelineOnce() {
  console.log('🚀 Running pipeline...');
  try {
    await db.updateCronStatus('running');
    console.log('✅ Pipeline completed');
  } catch (error) {
    console.error('❌ Pipeline failed:', error);
  } finally {
    await db.updateCronStatus('idle');
  }
}

async function main() {
  console.log('[cron] Boot in America/New_York');
  
  // Schedule data clearing
  cron.schedule(CLEAR_SCHEDULE, clearAllData, {
    timezone: 'America/New_York'
  });
  
  // Schedule pipeline execution
  cron.schedule(CRON_SCHEDULE, runPipelineOnce, {
    timezone: 'America/New_York'
  });
  
  console.log('[cron] Scheduler armed:');
  console.log(`  🗑️  Clear data: "${CLEAR_SCHEDULE}" (07:00 Mon-Fri)`);
  console.log(`  📊 Cron jobs: "${CRON_SCHEDULE}" (07:05-06:55 every 5min Mon-Fri, weekends OFF)`);
  console.log('  🌍 Timezone: America/New_York');
  console.log('[cron] Press Ctrl+C to stop');
}

main().catch(console.error);
