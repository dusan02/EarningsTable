// modules/cron/src/performance-monitor.ts
import { prisma } from '../../shared/src/prismaClient.js';

/**
 * Performance Monitor for Cron Pipeline
 * 
 * Tracks and analyzes performance metrics to identify bottlenecks
 * and suggest optimizations.
 */

interface PerformanceSnapshot {
  timestamp: Date;
  pipelineDuration: number;
  finnhubDuration: number;
  polygonDuration: number;
  logoDuration: number;
  dbDuration: number;
  totalRecords: number;
  symbolsChanged: number;
  memoryUsage: NodeJS.MemoryUsage;
  cpuUsage: NodeJS.CpuUsage;
}

interface PerformanceTrends {
  averageDuration: number;
  medianDuration: number;
  p95Duration: number;
  p99Duration: number;
  trend: 'improving' | 'degrading' | 'stable';
  bottlenecks: string[];
  recommendations: string[];
}

export class PerformanceMonitor {
  private snapshots: PerformanceSnapshot[] = [];
  private readonly maxSnapshots = 100; // Keep last 100 runs

  recordSnapshot(metrics: {
    pipelineDuration: number;
    finnhubDuration: number;
    polygonDuration: number;
    logoDuration: number;
    dbDuration: number;
    totalRecords: number;
    symbolsChanged: number;
  }): void {
    const snapshot: PerformanceSnapshot = {
      timestamp: new Date(),
      ...metrics,
      memoryUsage: process.memoryUsage(),
      cpuUsage: process.cpuUsage()
    };

    this.snapshots.push(snapshot);
    
    // Keep only last N snapshots
    if (this.snapshots.length > this.maxSnapshots) {
      this.snapshots = this.snapshots.slice(-this.maxSnapshots);
    }

    // Log performance warnings
    this.checkPerformanceWarnings(snapshot);
  }

  private checkPerformanceWarnings(snapshot: PerformanceSnapshot): void {
    const warnings: string[] = [];

    // Duration warnings
    if (snapshot.pipelineDuration > 300000) { // 5 minutes
      warnings.push(`Pipeline duration ${Math.round(snapshot.pipelineDuration / 1000)}s exceeds 5min threshold`);
    }

    if (snapshot.polygonDuration > 180000) { // 3 minutes
      warnings.push(`Polygon processing ${Math.round(snapshot.polygonDuration / 1000)}s exceeds 3min threshold`);
    }

    if (snapshot.logoDuration > 120000) { // 2 minutes
      warnings.push(`Logo processing ${Math.round(snapshot.logoDuration / 1000)}s exceeds 2min threshold`);
    }

    // Memory warnings
    const memoryMB = snapshot.memoryUsage.heapUsed / 1024 / 1024;
    if (memoryMB > 500) { // 500MB
      warnings.push(`Memory usage ${Math.round(memoryMB)}MB exceeds 500MB threshold`);
    }

    // Record count warnings
    if (snapshot.totalRecords > 10000) {
      warnings.push(`Processing ${snapshot.totalRecords} records exceeds 10k threshold`);
    }

    if (warnings.length > 0) {
      console.log('⚠️  Performance Warnings:');
      warnings.forEach(warning => console.log(`   - ${warning}`));
    }
  }

  analyzeTrends(): PerformanceTrends {
    if (this.snapshots.length < 5) {
      return {
        averageDuration: 0,
        medianDuration: 0,
        p95Duration: 0,
        p99Duration: 0,
        trend: 'stable',
        bottlenecks: [],
        recommendations: ['Need more data points for analysis']
      };
    }

    const durations = this.snapshots.map(s => s.pipelineDuration).sort((a, b) => a - b);
    const recent = this.snapshots.slice(-10);
    const older = this.snapshots.slice(-20, -10);

    const averageDuration = durations.reduce((a, b) => a + b, 0) / durations.length;
    const medianDuration = durations[Math.floor(durations.length / 2)];
    const p95Duration = durations[Math.floor(durations.length * 0.95)];
    const p99Duration = durations[Math.floor(durations.length * 0.99)];

    // Calculate trend
    const recentAvg = recent.reduce((a, b) => a + b.pipelineDuration, 0) / recent.length;
    const olderAvg = older.length > 0 ? older.reduce((a, b) => a + b.pipelineDuration, 0) / older.length : recentAvg;
    
    let trend: 'improving' | 'degrading' | 'stable' = 'stable';
    if (recentAvg < olderAvg * 0.9) trend = 'improving';
    else if (recentAvg > olderAvg * 1.1) trend = 'degrading';

    // Identify bottlenecks
    const bottlenecks: string[] = [];
    const avgPolygon = recent.reduce((a, b) => a + b.polygonDuration, 0) / recent.length;
    const avgLogo = recent.reduce((a, b) => a + b.logoDuration, 0) / recent.length;
    const avgDb = recent.reduce((a, b) => a + b.dbDuration, 0) / recent.length;

    if (avgPolygon > averageDuration * 0.6) {
      bottlenecks.push('Polygon API processing');
    }
    if (avgLogo > averageDuration * 0.3) {
      bottlenecks.push('Logo processing');
    }
    if (avgDb > averageDuration * 0.2) {
      bottlenecks.push('Database operations');
    }

    // Generate recommendations
    const recommendations: string[] = [];
    
    if (bottlenecks.includes('Polygon API processing')) {
      recommendations.push('Consider increasing Polygon batch size and concurrency');
      recommendations.push('Implement caching for frequently accessed symbols');
    }
    
    if (bottlenecks.includes('Logo processing')) {
      recommendations.push('Optimize logo processing with better batching');
      recommendations.push('Implement logo caching and TTL');
    }
    
    if (bottlenecks.includes('Database operations')) {
      recommendations.push('Optimize database queries and indexing');
      recommendations.push('Consider connection pooling');
    }

    if (trend === 'degrading') {
      recommendations.push('Investigate recent performance degradation');
      recommendations.push('Check for memory leaks or resource exhaustion');
    }

    return {
      averageDuration,
      medianDuration,
      p95Duration,
      p99Duration,
      trend,
      bottlenecks,
      recommendations
    };
  }

  generateReport(): string {
    const trends = this.analyzeTrends();
    const latest = this.snapshots[this.snapshots.length - 1];
    
    let report = '\n📊 Performance Analysis Report\n';
    report += '================================\n\n';
    
    if (latest) {
      report += `Latest Run (${latest.timestamp.toISOString()}):\n`;
      report += `  Duration: ${Math.round(latest.pipelineDuration / 1000)}s\n`;
      report += `  Records: ${latest.totalRecords}\n`;
      report += `  Memory: ${Math.round(latest.memoryUsage.heapUsed / 1024 / 1024)}MB\n\n`;
    }
    
    report += `Trends (${this.snapshots.length} runs):\n`;
    report += `  Average: ${Math.round(trends.averageDuration / 1000)}s\n`;
    report += `  Median: ${Math.round(trends.medianDuration / 1000)}s\n`;
    report += `  P95: ${Math.round(trends.p95Duration / 1000)}s\n`;
    report += `  P99: ${Math.round(trends.p99Duration / 1000)}s\n`;
    report += `  Trend: ${trends.trend}\n\n`;
    
    if (trends.bottlenecks.length > 0) {
      report += 'Bottlenecks:\n';
      trends.bottlenecks.forEach(bottleneck => {
        report += `  - ${bottleneck}\n`;
      });
      report += '\n';
    }
    
    if (trends.recommendations.length > 0) {
      report += 'Recommendations:\n';
      trends.recommendations.forEach(rec => {
        report += `  - ${rec}\n`;
      });
    }
    
    return report;
  }

  async saveToDatabase(): Promise<void> {
    if (this.snapshots.length === 0) return;

    const latest = this.snapshots[this.snapshots.length - 1];
    const trends = this.analyzeTrends();

    try {
      await prisma.cronStatus.upsert({
        where: { jobType: 'performance-monitor' },
        update: {
          lastRunAt: latest.timestamp,
          status: 'success',
          recordsProcessed: this.snapshots.length,
          errorMessage: JSON.stringify({
            averageDuration: trends.averageDuration,
            trend: trends.trend,
            bottlenecks: trends.bottlenecks
          })
        },
        create: {
          jobType: 'performance-monitor',
          lastRunAt: latest.timestamp,
          status: 'success',
          recordsProcessed: this.snapshots.length,
          errorMessage: JSON.stringify({
            averageDuration: trends.averageDuration,
            trend: trends.trend,
            bottlenecks: trends.bottlenecks
          })
        }
      });
    } catch (error) {
      console.error('Failed to save performance data to database:', error);
    }
  }

  getSnapshots(): PerformanceSnapshot[] {
    return [...this.snapshots];
  }

  clearSnapshots(): void {
    this.snapshots = [];
  }
}

// Export singleton instance
export const performanceMonitor = new PerformanceMonitor();
