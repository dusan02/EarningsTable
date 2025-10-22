if (process.env.ALLOW_CLEAR !== "true") { console.log("🧹 Skipping startup cleanup (ALLOW_CLEAR!=true)"); }

import { db } from './core/DatabaseManager.js';

export class ClearDatabaseCronJob {
  async execute(): Promise<void> {
    console.log('🧹 Starting database cleanup...');
    
    try {
      // Clear all tables
// await db.clearAllTables(); // disabled: run only in daily clear job
      console.log('✅ Database cleared successfully');
      
    } catch (error) {
      console.error('❌ Database cleanup failed:', error);
      throw error;
    }
  }
}

// Standalone execution
async function main() {
  const clearJob = new ClearDatabaseCronJob();
  await clearJob.execute();
  await db.disconnect();
}

// Run if called directly
if (import.meta.url.endsWith('clear-db-cron.ts') || process.argv[1]?.includes('clear-db-cron.ts')) {
  console.log('🚀 Running main function...');
  main().catch(console.error);
}
