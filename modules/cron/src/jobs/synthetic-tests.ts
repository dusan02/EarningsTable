// modules/cron/src/jobs/synthetic-tests.ts
import cron from 'node-cron';
import { syntheticTestRunner } from '../../../shared/src/synthetic-tests.js';
import { logoSyncManager } from '../../../shared/src/logo-sync.js';
import { IdempotencyManager } from '../../../shared/src/idempotency.js';

export class SyntheticTestsJob {
  private isRunning = false;

  async start(): Promise<void> {
    console.log('🧪 Starting synthetic tests job...');
    
    // Run tests every minute
    cron.schedule('* * * * *', async () => {
      if (this.isRunning) {
        console.log('⏭️ Synthetic tests skip (previous run still in progress)');
        return;
      }
      
      this.isRunning = true;
      try {
        await this.runTests();
      } catch (error) {
        console.error('❌ Synthetic tests failed:', error);
      } finally {
        this.isRunning = false;
      }
    }, { timezone: 'America/New_York' });

    // Run logo sync every hour
    cron.schedule('0 * * * *', async () => {
      try {
        console.log('🔄 Running hourly logo sync...');
        const result = await logoSyncManager.syncLogosFromFS();
        console.log(`✅ Logo sync completed: ${result.synced} synced, ${result.skipped} skipped, ${result.errors} errors`);
      } catch (error) {
        console.error('❌ Logo sync failed:', error);
      }
    }, { timezone: 'America/New_York' });

    console.log('✅ Synthetic tests job started');
  }

  private async runTests(): Promise<void> {
    try {
      const suite = await syntheticTestRunner.runAllTests();
      const report = syntheticTestRunner.generateReport(suite);
      
      // Log the report
      console.log(report);
      
      // Update cron status
      await IdempotencyManager.markProcessed(
        'synthetic-tests',
        suite.results.length,
        suite.overallStatus === 'FAIL' ? 'Some tests failed' : undefined
      );
      
      // Alert on failures
      if (suite.overallStatus === 'FAIL') {
        console.error('🚨 SYNTHETIC TESTS FAILED - Immediate attention required!');
        // Here you could add Slack/Email notifications
      }
      
    } catch (error) {
      console.error('❌ Synthetic tests error:', error);
      await IdempotencyManager.markProcessed(
        'synthetic-tests',
        0,
        error instanceof Error ? error.message : String(error)
      );
    }
  }

  async runOnce(): Promise<void> {
    console.log('🧪 Running synthetic tests once...');
    await this.runTests();
  }
}

// Export singleton instance
export const syntheticTestsJob = new SyntheticTestsJob();
