import { BaseCronJob, setupGracefulShutdown, CronJobStatus } from './BaseCronJob.js';
import { FinnhubCronJob } from '../jobs/FinnhubCronJob.js';
import { PolygonCronJob } from '../jobs/PolygonCronJob.js';

export class CronManager {
  private cronJobs: Map<string, BaseCronJob> = new Map();
  private shutdownHookSet = false;

  constructor() {
    this.initializeCronJobs();
  }

  private initializeCronJobs(): void {
    // Register available cron jobs
    this.registerCronJob(new FinnhubCronJob());
    this.registerCronJob(new PolygonCronJob());
  }

  registerCronJob(cronJob: BaseCronJob): void {
    const id = cronJob.id;
    if (this.cronJobs.has(id)) {
      throw new Error(`Cron job '${id}' already registered`);
    }
    this.cronJobs.set(id, cronJob);
  }

  async startCronJob(id: string): Promise<void> {
    const cronJob = this.cronJobs.get(id);
    if (!cronJob) {
      throw new Error(`Cron job '${id}' not found. Available: ${this.listAvailableCronJobs().join(', ')}`);
    }
    await cronJob.start();
  }

  async startAllCronJobs(): Promise<void> {
    console.log('🚀 Starting all cron jobs...');
    const jobs = Array.from(this.cronJobs.values());

    const results = await Promise.allSettled(jobs.map(j => j.start()));
    results.forEach((result, i) => {
      const id = jobs[i].id;
      if (result.status === 'fulfilled') {
        console.log(`✅ Started ${id}`);
      } else {
        console.error(`❌ Failed to start ${id}:`, result.reason);
      }
    });

    if (!this.shutdownHookSet) {
      setupGracefulShutdown(jobs);
      this.shutdownHookSet = true;
    }

    console.log('✅ All start attempts finished');
    console.log('ℹ️ Press Ctrl+C to stop all cron jobs');
  }

  async stopCronJob(id: string): Promise<void> {
    const cronJob = this.cronJobs.get(id);
    if (!cronJob) {
      throw new Error(`Cron job '${id}' not found`);
    }
    cronJob.stop();
  }

  async stopAllCronJobs(): Promise<void> {
    console.log('🛑 Stopping all cron jobs...');
    const jobs = Array.from(this.cronJobs.values());
    await Promise.allSettled(jobs.map(j => j.stop()));
  }

  getCronJobStatus(id: string): CronJobStatus {
    const cronJob = this.cronJobs.get(id);
    if (!cronJob) {
      throw new Error(`Cron job '${id}' not found`);
    }
    return cronJob.getStatus();
  }

  getAllCronJobStatuses(): CronJobStatus[] {
    return Array.from(this.cronJobs.values()).map(j => j.getStatus());
  }

  listAvailableCronJobs(): string[] {
    return Array.from(this.cronJobs.keys());
  }
}

// Singleton instance
export const cronManager = new CronManager();
