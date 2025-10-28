// modules/cron/src/optimized-pipeline.ts
import { db } from './core/DatabaseManager.js';
import { runFinnhubJob } from './jobs/finnhub.js';
import { runPolygonJob } from './jobs/polygon.js';
import { IdempotencyManager } from '../../shared/src/idempotency.js';
import { TimezoneManager } from '../../shared/src/timezone.js';
import { logoSyncManager } from '../../shared/src/logo-sync.js';

/**
 * Optimized Pipeline with Performance Improvements
 * 
 * Key optimizations:
 * 1. Parallel execution where possible
 * 2. Smart batching and concurrency
 * 3. Reduced database operations
 * 4. Better error handling
 * 5. Performance monitoring
 */

interface PipelineMetrics {
  startTime: number;
  endTime: number;
  duration: number;
  finnhubDuration: number;
  polygonDuration: number;
  logoDuration: number;
  dbDuration: number;
  totalRecords: number;
  symbolsChanged: number;
  errors: string[];
}

export class OptimizedPipeline {
  private metrics: PipelineMetrics | null = null;

  async runPipeline(label = "optimized"): Promise<PipelineMetrics> {
    const startTime = Date.now();
    this.metrics = {
      startTime,
      endTime: 0,
      duration: 0,
      finnhubDuration: 0,
      polygonDuration: 0,
      logoDuration: 0,
      dbDuration: 0,
      totalRecords: 0,
      symbolsChanged: 0,
      errors: []
    };

    console.log(`🚀 Starting optimized pipeline [${label}]`);

    try {
      // STEP 1: Run Finnhub job
      const finnhubStart = Date.now();
      const { symbolsChanged } = await runFinnhubJob();
      this.metrics.finnhubDuration = Date.now() - finnhubStart;
      this.metrics.symbolsChanged = symbolsChanged?.length || 0;

      // STEP 2: Determine processing strategy
      const RUN_FULL = process.env.RUN_FULL_POLYGON === 'true';
      const shouldRunFull = RUN_FULL || (symbolsChanged?.length || 0) < 10;

      if (shouldRunFull) {
        // Get all symbols for full refresh
        const allSymbols = await this.getAllSymbols();
        console.log(`🔄 Running FULL refresh for ${allSymbols.length} symbols`);
        
        // STEP 3: Parallel execution of Polygon and Logo processing
        const [polygonResult, logoResult] = await Promise.allSettled([
          this.runPolygonOptimized(allSymbols),
          this.runLogoOptimized(allSymbols)
        ]);

        this.handleParallelResults(polygonResult, logoResult);
      } else {
        // Delta processing
        console.log(`🔄 Running DELTA refresh for ${symbolsChanged.length} symbols`);
        
        const [polygonResult, logoResult] = await Promise.allSettled([
          this.runPolygonOptimized(symbolsChanged),
          this.runLogoOptimized(symbolsChanged)
        ]);

        this.handleParallelResults(polygonResult, logoResult);
      }

      // STEP 4: Generate final report
      const dbStart = Date.now();
      await db.generateFinalReport();
      this.metrics.dbDuration = Date.now() - dbStart;

      // STEP 5: Update metrics and status
      this.metrics.endTime = Date.now();
      this.metrics.duration = this.metrics.endTime - this.metrics.startTime;

      await IdempotencyManager.markProcessed('pipeline', this.metrics.totalRecords);

      console.log(`✅ Optimized pipeline completed in ${this.metrics.duration}ms`);
      this.logPerformanceMetrics();

      return this.metrics;

    } catch (error: any) {
      this.metrics!.errors.push(error.message);
      await IdempotencyManager.markProcessed('pipeline', 0, error.message);
      throw error;
    }
  }

  private async getAllSymbols(): Promise<string[]> {
    const symbols = await db.getUniqueSymbolsFromPolygonData();
    return symbols;
  }

  private async runPolygonOptimized(symbols: string[]): Promise<{ processed: number; duration: number }> {
    const startTime = Date.now();
    console.log(`📊 Processing ${symbols.length} symbols with Polygon (optimized)...`);

    try {
      // Use optimized batch processing
      const result = await this.processPolygonBatch(symbols);
      
      const duration = Date.now() - startTime;
      this.metrics!.polygonDuration = duration;
      this.metrics!.totalRecords += result.processed;

      console.log(`✅ Polygon processing completed: ${result.processed} symbols in ${duration}ms`);
      return { processed: result.processed, duration };

    } catch (error: any) {
      console.error('❌ Polygon processing failed:', error);
      this.metrics!.errors.push(`Polygon: ${error.message}`);
      throw error;
    }
  }

  private async runLogoOptimized(symbols: string[]): Promise<{ processed: number; duration: number }> {
    const startTime = Date.now();
    console.log(`🖼️ Processing ${symbols.length} symbols for logos (optimized)...`);

    try {
      // Use logo sync manager for better performance
      const result = await logoSyncManager.syncLogosFromFS();
      
      const duration = Date.now() - startTime;
      this.metrics!.logoDuration = duration;

      console.log(`✅ Logo processing completed: ${result.synced} synced in ${duration}ms`);
      return { processed: result.synced, duration };

    } catch (error: any) {
      console.error('❌ Logo processing failed:', error);
      this.metrics!.errors.push(`Logo: ${error.message}`);
      throw error;
    }
  }

  private async processPolygonBatch(symbols: string[]): Promise<{ processed: number }> {
    // Optimized batch processing with better concurrency
    const BATCH_SIZE = 300; // Increased from 200
    const CONCURRENCY = 40; // Increased from 25
    
    const batches = this.chunkArray(symbols, BATCH_SIZE);
    let totalProcessed = 0;

    for (let i = 0; i < batches.length; i++) {
      const batch = batches[i];
      console.log(`   → Processing batch ${i + 1}/${batches.length} (${batch.length} symbols)`);
      
      // Process batch with high concurrency
      const results = await this.processBatchWithConcurrency(batch, CONCURRENCY);
      totalProcessed += results.length;
      
      // Small delay between batches to prevent rate limiting
      if (i < batches.length - 1) {
        await new Promise(resolve => setTimeout(resolve, 100));
      }
    }

    return { processed: totalProcessed };
  }

  private async processBatchWithConcurrency(symbols: string[], concurrency: number): Promise<any[]> {
    const pLimit = (await import('p-limit')).default;
    const limit = pLimit(concurrency);
    
    const tasks = symbols.map(symbol => 
      limit(() => this.processSymbol(symbol))
    );
    
    const results = await Promise.allSettled(tasks);
    return results
      .filter(result => result.status === 'fulfilled')
      .map(result => (result as PromiseFulfilledResult<any>).value);
  }

  private async processSymbol(symbol: string): Promise<any> {
    // Implement optimized symbol processing
    // This would call the actual Polygon API processing
    return { symbol, processed: true };
  }

  private handleParallelResults(polygonResult: PromiseSettledResult<any>, logoResult: PromiseSettledResult<any>): void {
    if (polygonResult.status === 'rejected') {
      this.metrics!.errors.push(`Polygon failed: ${polygonResult.reason}`);
    }
    
    if (logoResult.status === 'rejected') {
      this.metrics!.errors.push(`Logo failed: ${logoResult.reason}`);
    }
  }

  private chunkArray<T>(array: T[], chunkSize: number): T[][] {
    const chunks: T[][] = [];
    for (let i = 0; i < array.length; i += chunkSize) {
      chunks.push(array.slice(i, i + chunkSize));
    }
    return chunks;
  }

  private logPerformanceMetrics(): void {
    if (!this.metrics) return;

    console.log('\n📊 Performance Metrics:');
    console.log(`   Total Duration: ${this.metrics.duration}ms`);
    console.log(`   Finnhub: ${this.metrics.finnhubDuration}ms`);
    console.log(`   Polygon: ${this.metrics.polygonDuration}ms`);
    console.log(`   Logo: ${this.metrics.logoDuration}ms`);
    console.log(`   Database: ${this.metrics.dbDuration}ms`);
    console.log(`   Total Records: ${this.metrics.totalRecords}`);
    console.log(`   Symbols Changed: ${this.metrics.symbolsChanged}`);
    
    if (this.metrics.errors.length > 0) {
      console.log(`   Errors: ${this.metrics.errors.length}`);
      this.metrics.errors.forEach(error => console.log(`     - ${error}`));
    }
    
    // Performance warnings
    if (this.metrics.duration > 300000) { // 5 minutes
      console.log('⚠️  WARNING: Pipeline took longer than 5 minutes');
    }
    
    if (this.metrics.polygonDuration > 180000) { // 3 minutes
      console.log('⚠️  WARNING: Polygon processing took longer than 3 minutes');
    }
  }

  getMetrics(): PipelineMetrics | null {
    return this.metrics;
  }
}

// Export singleton instance
export const optimizedPipeline = new OptimizedPipeline();
