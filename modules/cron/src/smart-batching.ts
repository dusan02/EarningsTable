// modules/cron/src/smart-batching.ts
import pLimit from 'p-limit';

/**
 * Smart Batching System
 * 
 * Dynamically adjusts batch sizes and concurrency based on:
 * - API response times
 * - Error rates
 * - System load
 * - Historical performance
 */

interface BatchConfig {
  batchSize: number;
  concurrency: number;
  delayMs: number;
  retryAttempts: number;
}

interface BatchMetrics {
  successRate: number;
  averageResponseTime: number;
  errorRate: number;
  throughput: number;
}

export class SmartBatching {
  private configs: Map<string, BatchConfig> = new Map();
  private metrics: Map<string, BatchMetrics[]> = new Map();
  private readonly maxMetricsHistory = 20;

  constructor() {
    // Initialize default configurations
    this.initializeDefaultConfigs();
  }

  private initializeDefaultConfigs(): void {
    // Polygon API configuration
    this.configs.set('polygon', {
      batchSize: 300,
      concurrency: 40,
      delayMs: 100,
      retryAttempts: 3
    });

    // Logo processing configuration
    this.configs.set('logo', {
      batchSize: 20,
      concurrency: 8,
      delayMs: 200,
      retryAttempts: 2
    });

    // Database operations configuration
    this.configs.set('database', {
      batchSize: 500,
      concurrency: 1, // Sequential for database
      delayMs: 0,
      retryAttempts: 1
    });
  }

  getConfig(service: string): BatchConfig {
    return this.configs.get(service) || this.getDefaultConfig();
  }

  private getDefaultConfig(): BatchConfig {
    return {
      batchSize: 100,
      concurrency: 10,
      delayMs: 100,
      retryAttempts: 2
    };
  }

  async processBatch<T, R>(
    service: string,
    items: T[],
    processor: (item: T) => Promise<R>,
    options: Partial<BatchConfig> = {}
  ): Promise<R[]> {
    const config = { ...this.getConfig(service), ...options };
    const startTime = Date.now();
    let successCount = 0;
    let errorCount = 0;
    const results: R[] = [];

    console.log(`🔄 Processing ${items.length} items with ${service} (batch: ${config.batchSize}, concurrency: ${config.concurrency})`);

    // Create chunks
    const chunks = this.createChunks(items, config.batchSize);
    const limit = pLimit(config.concurrency);

    for (let i = 0; i < chunks.length; i++) {
      const chunk = chunks[i];
      const chunkStartTime = Date.now();

      try {
        const chunkResults = await Promise.allSettled(
          chunk.map(item => limit(() => this.processWithRetry(processor, item, config.retryAttempts)))
        );

        // Process results
        chunkResults.forEach(result => {
          if (result.status === 'fulfilled') {
            results.push(result.value);
            successCount++;
          } else {
            errorCount++;
            console.warn(`Item processing failed: ${result.reason}`);
          }
        });

        const chunkDuration = Date.now() - chunkStartTime;
        console.log(`   → Chunk ${i + 1}/${chunks.length} completed in ${chunkDuration}ms (${chunk.length} items)`);

        // Adaptive delay between chunks
        if (i < chunks.length - 1) {
          const adaptiveDelay = this.calculateAdaptiveDelay(service, chunkDuration, errorCount);
          await new Promise(resolve => setTimeout(resolve, adaptiveDelay));
        }

      } catch (error) {
        console.error(`Chunk ${i + 1} failed:`, error);
        errorCount += chunk.length;
      }
    }

    // Record metrics
    const totalDuration = Date.now() - startTime;
    this.recordMetrics(service, {
      successRate: successCount / items.length,
      averageResponseTime: totalDuration / items.length,
      errorRate: errorCount / items.length,
      throughput: items.length / (totalDuration / 1000)
    });

    // Adjust configuration based on performance
    this.adjustConfiguration(service);

    console.log(`✅ ${service} processing completed: ${successCount} success, ${errorCount} errors in ${totalDuration}ms`);
    return results;
  }

  private async processWithRetry<T, R>(
    processor: (item: T) => Promise<R>,
    item: T,
    maxAttempts: number
  ): Promise<R> {
    let lastError: any;

    for (let attempt = 1; attempt <= maxAttempts; attempt++) {
      try {
        return await processor(item);
      } catch (error: any) {
        lastError = error;
        
        // Don't retry on certain errors
        if (this.isNonRetryableError(error)) {
          throw error;
        }

        if (attempt < maxAttempts) {
          const delay = Math.min(1000 * Math.pow(2, attempt - 1), 5000);
          await new Promise(resolve => setTimeout(resolve, delay));
        }
      }
    }

    throw lastError;
  }

  private isNonRetryableError(error: any): boolean {
    // Don't retry on client errors (4xx) except 429 (rate limit)
    const status = error.response?.status;
    return status >= 400 && status < 500 && status !== 429;
  }

  private createChunks<T>(items: T[], batchSize: number): T[][] {
    const chunks: T[][] = [];
    for (let i = 0; i < items.length; i += batchSize) {
      chunks.push(items.slice(i, i + batchSize));
    }
    return chunks;
  }

  private calculateAdaptiveDelay(service: string, chunkDuration: number, errorCount: number): number {
    const baseDelay = this.getConfig(service).delayMs;
    
    // Increase delay if chunk took too long
    if (chunkDuration > 5000) { // 5 seconds
      return baseDelay * 2;
    }
    
    // Increase delay if there were errors
    if (errorCount > 0) {
      return baseDelay * 1.5;
    }
    
    // Decrease delay if performance is good
    if (chunkDuration < 1000) { // 1 second
      return Math.max(baseDelay * 0.5, 50);
    }
    
    return baseDelay;
  }

  private recordMetrics(service: string, metrics: BatchMetrics): void {
    if (!this.metrics.has(service)) {
      this.metrics.set(service, []);
    }

    const serviceMetrics = this.metrics.get(service)!;
    serviceMetrics.push(metrics);

    // Keep only recent metrics
    if (serviceMetrics.length > this.maxMetricsHistory) {
      serviceMetrics.splice(0, serviceMetrics.length - this.maxMetricsHistory);
    }
  }

  private adjustConfiguration(service: string): void {
    const serviceMetrics = this.metrics.get(service);
    if (!serviceMetrics || serviceMetrics.length < 5) return;

    const recentMetrics = serviceMetrics.slice(-5);
    const avgSuccessRate = recentMetrics.reduce((a, b) => a + b.successRate, 0) / recentMetrics.length;
    const avgResponseTime = recentMetrics.reduce((a, b) => a + b.averageResponseTime, 0) / recentMetrics.length;
    const avgErrorRate = recentMetrics.reduce((a, b) => a + b.errorRate, 0) / recentMetrics.length;

    const currentConfig = this.getConfig(service);
    let newConfig = { ...currentConfig };

    // Adjust based on success rate
    if (avgSuccessRate < 0.9 && avgErrorRate > 0.1) {
      // High error rate - reduce concurrency and batch size
      newConfig.concurrency = Math.max(1, Math.floor(currentConfig.concurrency * 0.8));
      newConfig.batchSize = Math.max(10, Math.floor(currentConfig.batchSize * 0.8));
      newConfig.delayMs = Math.min(2000, currentConfig.delayMs * 1.5);
    } else if (avgSuccessRate > 0.95 && avgErrorRate < 0.05) {
      // Low error rate - increase concurrency and batch size
      newConfig.concurrency = Math.min(50, Math.floor(currentConfig.concurrency * 1.2));
      newConfig.batchSize = Math.min(1000, Math.floor(currentConfig.batchSize * 1.1));
      newConfig.delayMs = Math.max(50, Math.floor(currentConfig.delayMs * 0.9));
    }

    // Adjust based on response time
    if (avgResponseTime > 2000) { // 2 seconds
      // Slow responses - reduce batch size
      newConfig.batchSize = Math.max(10, Math.floor(currentConfig.batchSize * 0.9));
    } else if (avgResponseTime < 500) { // 0.5 seconds
      // Fast responses - increase batch size
      newConfig.batchSize = Math.min(1000, Math.floor(currentConfig.batchSize * 1.1));
    }

    // Only update if configuration changed significantly
    if (this.configChangedSignificantly(currentConfig, newConfig)) {
      this.configs.set(service, newConfig);
      console.log(`🔧 Adjusted ${service} configuration:`, {
        batchSize: `${currentConfig.batchSize} → ${newConfig.batchSize}`,
        concurrency: `${currentConfig.concurrency} → ${newConfig.concurrency}`,
        delayMs: `${currentConfig.delayMs} → ${newConfig.delayMs}`
      });
    }
  }

  private configChangedSignificantly(old: BatchConfig, new_: BatchConfig): boolean {
    return Math.abs(old.batchSize - new_.batchSize) > old.batchSize * 0.1 ||
           Math.abs(old.concurrency - new_.concurrency) > old.concurrency * 0.1 ||
           Math.abs(old.delayMs - new_.delayMs) > old.delayMs * 0.2;
  }

  getMetrics(service: string): BatchMetrics[] {
    return this.metrics.get(service) || [];
  }

  getOptimalConfig(service: string): BatchConfig {
    const metrics = this.getMetrics(service);
    if (metrics.length < 3) return this.getConfig(service);

    // Find configuration with best throughput and success rate
    const bestMetrics = metrics.reduce((best, current) => {
      const bestScore = best.throughput * best.successRate;
      const currentScore = current.throughput * current.successRate;
      return currentScore > bestScore ? current : best;
    });

    // Return configuration that achieved these metrics
    return this.getConfig(service);
  }

  resetMetrics(service?: string): void {
    if (service) {
      this.metrics.delete(service);
    } else {
      this.metrics.clear();
    }
  }

  generateReport(): string {
    let report = '\n📊 Smart Batching Report\n';
    report += '========================\n\n';

    for (const [service, metrics] of this.metrics.entries()) {
      if (metrics.length === 0) continue;

      const recent = metrics.slice(-5);
      const avgSuccessRate = recent.reduce((a, b) => a + b.successRate, 0) / recent.length;
      const avgThroughput = recent.reduce((a, b) => a + b.throughput, 0) / recent.length;
      const avgErrorRate = recent.reduce((a, b) => a + b.errorRate, 0) / recent.length;

      report += `${service.toUpperCase()}:\n`;
      report += `  Success Rate: ${(avgSuccessRate * 100).toFixed(1)}%\n`;
      report += `  Throughput: ${avgThroughput.toFixed(1)} items/sec\n`;
      report += `  Error Rate: ${(avgErrorRate * 100).toFixed(1)}%\n`;
      report += `  Current Config: ${JSON.stringify(this.getConfig(service))}\n\n`;
    }

    return report;
  }
}

// Export singleton instance
export const smartBatching = new SmartBatching();
