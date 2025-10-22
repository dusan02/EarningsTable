import cron, { ScheduledTask } from 'node-cron';
import { CONFIG } from '../config.js';
import { disconnect } from '../repository.js';

export interface CronJobConfig {
  name: string;
  schedule: string;
  timezone?: string;
  runOnStart?: boolean;
  description?: string;
}

export type CronJobStatus = {
  id: string;
  name: string;
  running: boolean;
  schedule: string;
  timezone: string;
  lastStartedAt: Date | null;
  lastFinishedAt: Date | null;
  lastDurationMs: number | null;
  lastError: string | null;
  description?: string;
};

export abstract class BaseCronJob {
  protected config: Required<Omit<CronJobConfig, 'description'>> & Pick<CronJobConfig, 'description'>;
  public readonly id: string;

  // runtime state
  protected task: ScheduledTask | null = null;
  protected isRunning = false;       // plánovač beží
  protected isExecuting = false;     // práve prebieha execute()
  protected lastStartedAt: Date | null = null;
  protected lastFinishedAt: Date | null = null;
  protected lastDurationMs: number | null = null;
  protected lastError: string | null = null;

  constructor(config: CronJobConfig) {
    this.config = {
      name: config.name,
      schedule: config.schedule,
      timezone: config.timezone ?? CONFIG.CRON_TZ ?? 'UTC',
      runOnStart: config.runOnStart ?? false,
      description: config.description,
    };
    this.id = this.config.name; // stabilné ID podľa názvu
  }

  abstract execute(): Promise<void>;

  async start(): Promise<void> {
    if (this.isRunning) {
      console.log(`⚠️ Cron job '${this.config.name}' is already scheduled`);
      return;
    }

    if (!cron.validate(this.config.schedule)) {
      throw new Error(`Invalid cron expression for '${this.config.name}': ${this.config.schedule}`);
    }

    console.log(`🚀 Starting ${this.config.name}`);
    console.log(`📅 Schedule: ${this.config.schedule}`);
    console.log(`🌍 Timezone: ${this.config.timezone}`);
    if (this.config.description) console.log(`📝 Description: ${this.config.description}`);

    // (A) inicial: voliteľné jednorazové spustenie
    if (this.config.runOnStart) {
      // nech sa neprekrýva so schedulom – použijeme rovnaký lock
      await this.safeExecute();
    }

    // (B) naplánuj úlohu a ulož referenciu
    this.task = cron.schedule(
      this.config.schedule,
      async () => {
        await this.safeExecute();
      },
      { timezone: this.config.timezone }
    );

    this.isRunning = true;
    console.log(`✅ ${this.config.name} cron job scheduled`);
  }

  protected async safeExecute(): Promise<void> {
    if (this.isExecuting) {
      console.log(`⏸️  Skip ${this.config.name}: previous run still executing`);
      return;
    }
    this.isExecuting = true;
    this.lastError = null;
    this.lastStartedAt = new Date();
    const started = Date.now();

    try {
      await this.execute();
    } catch (err: any) {
      this.lastError = err instanceof Error ? err.message : String(err);
      console.error(`✗ ${this.config.name} execution failed:`, err);
    } finally {
      this.lastFinishedAt = new Date();
      this.lastDurationMs = Date.now() - started;
      this.isExecuting = false;
      const outcome = this.lastError ? '❌ failed' : '✅ finished';
      console.log(`↩️ ${this.config.name} ${outcome} in ${this.lastDurationMs}ms`);
    }
  }

  async stop(): Promise<void> {
    if (!this.isRunning) {
      console.log(`ℹ️ ${this.config.name} is not scheduled`);
      return;
    }
    try {
      this.task?.stop();
      this.task = null;
      this.isRunning = false;
      console.log(`🛑 ${this.config.name} cron job stopped`);
    } catch (e) {
      console.error(`✗ Failed to stop ${this.config.name}:`, e);
    }
  }

  getStatus(): CronJobStatus {
    return {
      id: this.id,
      name: this.config.name,
      running: this.isRunning,
      schedule: this.config.schedule,
      timezone: this.config.timezone,
      lastStartedAt: this.lastStartedAt,
      lastFinishedAt: this.lastFinishedAt,
      lastDurationMs: this.lastDurationMs,
      lastError: this.lastError,
      description: this.config.description,
    };
  }
}

// Graceful shutdown handler (volaj iba raz – napr. v CronManageri)
let shutdownHookSet = false;
export function setupGracefulShutdown(cronJobs: BaseCronJob[]): void {
  if (shutdownHookSet) return;
  shutdownHookSet = true;

  const shutdown = async () => {
    console.log('\n🛑 Shutting down cron jobs…');
    await Promise.allSettled(cronJobs.map(job => job.stop()));
    await disconnect().catch(() => {});
    process.exit(0);
  };

  process.on('SIGINT', shutdown);
  process.on('SIGTERM', shutdown);
}
