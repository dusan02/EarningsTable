/**
 * Logo Metrics and Monitoring System
 * 
 * Features:
 * - Real-time metrics tracking
 * - Performance monitoring
 * - Error categorization
 * - Success rate calculation
 * - Source effectiveness analysis
 */

interface LogoMetrics {
  total: number;
  success: number;
  failed: number;
  sources: {
    finnhub: number;
    polygon: number;
    clearbit: number;
    cached: number;
  };
  errors: {
    timeout: number;
    network: number;
    processing: number;
    invalid: number;
    notFound: number;
  };
  performance: {
    avgFetchTime: number;
    totalFetchTime: number;
    fastestFetch: number;
    slowestFetch: number;
  };
  lastUpdated: Date;
}

class LogoMetricsCollector {
  private metrics: LogoMetrics = {
    total: 0,
    success: 0,
    failed: 0,
    sources: {
      finnhub: 0,
      polygon: 0,
      clearbit: 0,
      cached: 0,
    },
    errors: {
      timeout: 0,
      network: 0,
      processing: 0,
      invalid: 0,
      notFound: 0,
    },
    performance: {
      avgFetchTime: 0,
      totalFetchTime: 0,
      fastestFetch: Infinity,
      slowestFetch: 0,
    },
    lastUpdated: new Date(),
  };

  private fetchTimes: number[] = [];

  recordAttempt(): void {
    this.metrics.total++;
    this.metrics.lastUpdated = new Date();
  }

  recordSuccess(source: string, fetchTime: number): void {
    this.metrics.success++;
    this.metrics.sources[source as keyof typeof this.metrics.sources]++;
    this.recordFetchTime(fetchTime);
  }

  recordFailure(errorType: string): void {
    this.metrics.failed++;
    this.metrics.errors[errorType as keyof typeof this.metrics.errors]++;
  }

  private recordFetchTime(fetchTime: number): void {
    this.fetchTimes.push(fetchTime);
    this.metrics.performance.totalFetchTime += fetchTime;
    this.metrics.performance.avgFetchTime = 
      this.metrics.performance.totalFetchTime / this.fetchTimes.length;
    this.metrics.performance.fastestFetch = Math.min(this.metrics.performance.fastestFetch, fetchTime);
    this.metrics.performance.slowestFetch = Math.max(this.metrics.performance.slowestFetch, fetchTime);
  }

  getMetrics(): LogoMetrics {
    return { ...this.metrics };
  }

  getSuccessRate(): number {
    return this.metrics.total > 0 ? (this.metrics.success / this.metrics.total) * 100 : 0;
  }

  getSourceEffectiveness(): { source: string; successRate: number; count: number }[] {
    const total = this.metrics.success;
    return Object.entries(this.metrics.sources).map(([source, count]) => ({
      source,
      successRate: total > 0 ? (count / total) * 100 : 0,
      count,
    }));
  }

  getErrorBreakdown(): { errorType: string; count: number; percentage: number }[] {
    const totalErrors = Object.values(this.metrics.errors).reduce((sum, count) => sum + count, 0);
    return Object.entries(this.metrics.errors).map(([errorType, count]) => ({
      errorType,
      count,
      percentage: totalErrors > 0 ? (count / totalErrors) * 100 : 0,
    }));
  }

  reset(): void {
    this.metrics = {
      total: 0,
      success: 0,
      failed: 0,
      sources: {
        finnhub: 0,
        polygon: 0,
        clearbit: 0,
        cached: 0,
      },
      errors: {
        timeout: 0,
        network: 0,
        processing: 0,
        invalid: 0,
        notFound: 0,
      },
      performance: {
        avgFetchTime: 0,
        totalFetchTime: 0,
        fastestFetch: Infinity,
        slowestFetch: 0,
      },
      lastUpdated: new Date(),
    };
    this.fetchTimes = [];
  }

  generateReport(): string {
    const successRate = this.getSuccessRate();
    const sourceEffectiveness = this.getSourceEffectiveness();
    const errorBreakdown = this.getErrorBreakdown();

    return `
📊 LOGO METRICS REPORT
=====================
📅 Last Updated: ${this.metrics.lastUpdated.toISOString()}

📈 OVERALL STATISTICS
  Total Attempts: ${this.metrics.total}
  Successful: ${this.metrics.success} (${successRate.toFixed(1)}%)
  Failed: ${this.metrics.failed} (${(100 - successRate).toFixed(1)}%)

🎯 SOURCE EFFECTIVENESS
${sourceEffectiveness.map(s => `  ${s.source}: ${s.count} (${s.successRate.toFixed(1)}%)`).join('\n')}

❌ ERROR BREAKDOWN
${errorBreakdown.map(e => `  ${e.errorType}: ${e.count} (${e.percentage.toFixed(1)}%)`).join('\n')}

⚡ PERFORMANCE METRICS
  Average Fetch Time: ${this.metrics.performance.avgFetchTime.toFixed(0)}ms
  Fastest Fetch: ${this.metrics.performance.fastestFetch === Infinity ? 'N/A' : this.metrics.performance.fastestFetch.toFixed(0) + 'ms'}
  Slowest Fetch: ${this.metrics.performance.slowestFetch.toFixed(0)}ms
  Total Fetch Time: ${this.metrics.performance.totalFetchTime.toFixed(0)}ms
`;
  }
}

// Global instance
const logoMetrics = new LogoMetricsCollector();

// Export functions
export function recordLogoAttempt(): void {
  logoMetrics.recordAttempt();
}

export function recordLogoSuccess(source: string, fetchTime: number): void {
  logoMetrics.recordSuccess(source, fetchTime);
}

export function recordLogoFailure(errorType: string): void {
  logoMetrics.recordFailure(errorType);
}

export function getLogoMetrics(): LogoMetrics {
  return logoMetrics.getMetrics();
}

export function getLogoSuccessRate(): number {
  return logoMetrics.getSuccessRate();
}

export function getLogoSourceEffectiveness(): { source: string; successRate: number; count: number }[] {
  return logoMetrics.getSourceEffectiveness();
}

export function getLogoErrorBreakdown(): { errorType: string; count: number; percentage: number }[] {
  return logoMetrics.getErrorBreakdown();
}

export function generateLogoReport(): string {
  return logoMetrics.generateReport();
}

export function resetLogoMetrics(): void {
  logoMetrics.reset();
}

// Make available globally for debugging
if (typeof global !== 'undefined') {
  (global as any).logoMetrics = logoMetrics;
}
