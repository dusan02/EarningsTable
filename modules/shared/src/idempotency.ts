// modules/shared/src/idempotency.ts
import { prisma } from './prismaClient.js';
import { TimezoneManager } from './timezone.js';

/**
 * Idempotency utilities to ensure safe re-runs
 */
export class IdempotencyManager {
  /**
   * Check if pipeline was already processed today (NY calendar day)
   */
  static async wasProcessedToday(jobType: string): Promise<boolean> {
    // Compare NY calendar dates, not server-local dates.
    const todayNYStr = TimezoneManager.getNYDateString(new Date());

    const lastRun = await prisma.cronStatus.findUnique({
      where: { jobType },
      select: { lastRunAt: true, status: true }
    });

    if (!lastRun || lastRun.status !== 'success') {
      return false;
    }

    const lastRunNYStr = TimezoneManager.getNYDateString(new Date(lastRun.lastRunAt));

    return lastRunNYStr === todayNYStr;
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
