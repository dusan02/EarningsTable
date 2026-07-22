// modules/shared/src/idempotency.ts
import { prisma } from './prismaClient.js';

/**
 * Idempotency utilities to ensure safe re-runs
 */
export class IdempotencyManager {
  /**
   * Check if pipeline was already processed today
   */
  static async wasProcessedToday(jobType: string): Promise<boolean> {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const lastRun = await prisma.cronStatus.findUnique({
      where: { jobType },
      select: { lastRunAt: true, status: true }
    });

    if (!lastRun || lastRun.status !== 'success') {
      return false;
    }

    const lastRunDate = new Date(lastRun.lastRunAt);
    lastRunDate.setHours(0, 0, 0, 0);
    
    return lastRunDate.getTime() === today.getTime();
  }

  /**
   * Mark pipeline as processed
   */
  static async markProcessed(jobType: string, recordsProcessed: number, errorMessage?: string): Promise<void> {
    await prisma.cronStatus.upsert({
      where: { jobType },
      update: {
        lastRunAt: new Date(),
        status: errorMessage ? 'error' : 'success',
        recordsProcessed,
        errorMessage: errorMessage ?? null,
      },
      create: {
        jobType,
        lastRunAt: new Date(),
        status: errorMessage ? 'error' : 'success',
        recordsProcessed,
        errorMessage: errorMessage ?? null,
      }
    });
  }
}
