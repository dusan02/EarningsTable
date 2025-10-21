import { FinnhubCronJob } from './jobs/FinnhubCronJob';
import { PolygonCronJob } from './jobs/PolygonCronJob';
import { db } from './core/DatabaseManager';

interface JobStats {
  name: string;
  startTime: Date;
  endTime?: Date;
  duration?: number;
  status: 'running' | 'completed' | 'failed';
  error?: string;
  recordsProcessed?: number;
}

interface OverallStats {
  totalStartTime: Date;
  totalEndTime?: Date;
  totalDuration?: number;
  jobs: JobStats[];
  summary: {
    totalJobs: number;
    successfulJobs: number;
    failedJobs: number;
    totalRecordsProcessed: number;
  };
}

class CronRunnerWithStats {
  private stats: OverallStats;

  constructor() {
    this.stats = {
      totalStartTime: new Date(),
      jobs: [],
      summary: {
        totalJobs: 0,
        successfulJobs: 0,
        failedJobs: 0,
        totalRecordsProcessed: 0
      }
    };
  }

  private addJobStats(name: string): JobStats {
    const jobStat: JobStats = {
      name,
      startTime: new Date(),
      status: 'running'
    };
    this.stats.jobs.push(jobStat);
    this.stats.summary.totalJobs++;
    return jobStat;
  }

  private completeJobStats(jobStat: JobStats, status: 'completed' | 'failed', error?: string, recordsProcessed?: number) {
    jobStat.endTime = new Date();
    jobStat.duration = jobStat.endTime.getTime() - jobStat.startTime.getTime();
    jobStat.status = status;
    if (error) jobStat.error = error;
    if (recordsProcessed) jobStat.recordsProcessed = recordsProcessed;

    if (status === 'completed') {
      this.stats.summary.successfulJobs++;
      if (recordsProcessed) {
        this.stats.summary.totalRecordsProcessed += recordsProcessed;
      }
    } else {
      this.stats.summary.failedJobs++;
    }
  }

  async runFinnhubJob(): Promise<void> {
    const jobStat = this.addJobStats('Finnhub Earnings Data');
    console.log(`\n🚀 [${jobStat.startTime.toISOString()}] Starting Finnhub job...`);
    
    try {
      const finnhubJob = new FinnhubCronJob();
      await finnhubJob.execute();
      
      // Try to get count of processed records
      let recordsProcessed = 0;
      try {
        const finhubData = await db.getFinhubDataByDate(new Date());
        recordsProcessed = finhubData.length;
      } catch (e) {
        // Ignore if we can't get count
      }
      
      this.completeJobStats(jobStat, 'completed', undefined, recordsProcessed);
      console.log(`✅ [${jobStat.endTime!.toISOString()}] Finnhub job completed successfully (${recordsProcessed} records, ${jobStat.duration}ms)`);
      
    } catch (error) {
      this.completeJobStats(jobStat, 'failed', error instanceof Error ? error.message : String(error));
      console.error(`❌ [${jobStat.endTime!.toISOString()}] Finnhub job failed: ${jobStat.error}`);
      throw error;
    }
  }

  async runPolygonJob(): Promise<void> {
    const jobStat = this.addJobStats('Polygon Market Data');
    console.log(`\n🚀 [${jobStat.startTime.toISOString()}] Starting Polygon job...`);
    
    try {
      const polygonJob = new PolygonCronJob();
      await polygonJob.execute();
      
      // Try to get count of processed records
      let recordsProcessed = 0;
      try {
        const polygonData = await db.getUniqueSymbolsFromPolygonData();
        recordsProcessed = polygonData.length;
      } catch (e) {
        // Ignore if we can't get count
      }
      
      this.completeJobStats(jobStat, 'completed', undefined, recordsProcessed);
      console.log(`✅ [${jobStat.endTime!.toISOString()}] Polygon job completed successfully (${recordsProcessed} symbols, ${jobStat.duration}ms)`);
      
    } catch (error) {
      this.completeJobStats(jobStat, 'failed', error instanceof Error ? error.message : String(error));
      console.error(`❌ [${jobStat.endTime!.toISOString()}] Polygon job failed: ${jobStat.error}`);
      throw error;
    }
  }

  async generateFinalReport(): Promise<void> {
    const jobStat = this.addJobStats('Final Report Generation');
    console.log(`\n🚀 [${jobStat.startTime.toISOString()}] Starting Final Report generation...`);
    
    try {
      // Generate the final report
      await db.generateFinalReport();
      
      // Get count of final report records
      let recordsProcessed = 0;
      try {
        const finalReports = await db.getFinalReport();
        recordsProcessed = finalReports.length;
      } catch (e) {
        // Ignore if we can't get count
      }
      
      this.completeJobStats(jobStat, 'completed', undefined, recordsProcessed);
      console.log(`✅ [${jobStat.endTime!.toISOString()}] Final Report generation completed successfully (${recordsProcessed} records, ${jobStat.duration}ms)`);
      
    } catch (error) {
      this.completeJobStats(jobStat, 'failed', error instanceof Error ? error.message : String(error));
      console.error(`❌ [${jobStat.endTime!.toISOString()}] Final Report generation failed: ${jobStat.error}`);
      throw error;
    }
  }

  async runAllJobs(): Promise<void> {
    console.log('🚀 Starting all cron jobs sequentially with statistics...');
    console.log(`📅 Start time: ${this.stats.totalStartTime.toISOString()}`);
    console.log('='.repeat(80));

    try {
      // Run Finnhub job
      await this.runFinnhubJob();
      
      // Run Polygon job
      await this.runPolygonJob();
      
      // Generate final report
      await this.generateFinalReport();
      
    } catch (error) {
      console.error('❌ One or more jobs failed:', error);
    } finally {
      this.stats.totalEndTime = new Date();
      this.stats.totalDuration = this.stats.totalEndTime.getTime() - this.stats.totalStartTime.getTime();
      
      this.printFinalStats();
      await db.disconnect();
    }
  }

  private printFinalStats(): void {
    console.log('\n' + '='.repeat(80));
    console.log('📊 FINAL STATISTICS');
    console.log('='.repeat(80));
    
    console.log(`🕐 Total execution time: ${this.stats.totalDuration}ms (${(this.stats.totalDuration! / 1000).toFixed(2)}s)`);
    console.log(`📅 Start: ${this.stats.totalStartTime.toISOString()}`);
    console.log(`📅 End: ${this.stats.totalEndTime!.toISOString()}`);
    
    console.log('\n📋 Job Summary:');
    console.log(`   Total jobs: ${this.stats.summary.totalJobs}`);
    console.log(`   ✅ Successful: ${this.stats.summary.successfulJobs}`);
    console.log(`   ❌ Failed: ${this.stats.summary.failedJobs}`);
    console.log(`   📊 Total records processed: ${this.stats.summary.totalRecordsProcessed}`);
    
    console.log('\n📈 Individual Job Details:');
    this.stats.jobs.forEach((job, index) => {
      const status = job.status === 'completed' ? '✅' : '❌';
      const duration = job.duration ? `${job.duration}ms` : 'N/A';
      const records = job.recordsProcessed ? ` (${job.recordsProcessed} records)` : '';
      const error = job.error ? ` - Error: ${job.error}` : '';
      
      console.log(`   ${index + 1}. ${status} ${job.name}`);
      console.log(`      Duration: ${duration}${records}${error}`);
    });
    
    console.log('\n' + '='.repeat(80));
    
    // Performance metrics
    const n = this.stats.jobs.length || 1;
    const avgDuration = this.stats.jobs.reduce((sum, job) => sum + (job.duration || 0), 0) / n;
    console.log(`⚡ Average job duration: ${avgDuration.toFixed(2)}ms`);
    console.log(`📊 Records per second: ${(this.stats.summary.totalRecordsProcessed / (this.stats.totalDuration! / 1000)).toFixed(2)}`);
    
    console.log('='.repeat(80));
  }
}

async function main() {
  // Guard na kritické environment premenné
  ['FINNHUB_TOKEN', 'POLYGON_API_KEY', 'DATABASE_URL'].forEach(k => {
    if (!process.env[k]) throw new Error(`[env] Missing ${k}`);
  });

  const runner = new CronRunnerWithStats();
  await runner.runAllJobs();
}

// Run the script
main().catch(async (e) => {
  console.error('✗ Script failed:', e);
  await db.disconnect();
  process.exit(1);
});

export { CronRunnerWithStats };
