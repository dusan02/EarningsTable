import { db } from './core/DatabaseManager.js';

export class ClearDatabaseCronJob {
  async execute(): Promise<void> {
    console.log('🧹 Starting database cleanup...');
    
    try {
      // Clear all tables
      await db.clearAllTables();
      console.log('✅ Database cleared successfully');
      
    } catch (error) {
      console.error('❌ Database cleanup failed:', error);
      throw error;
    }
  }
}
