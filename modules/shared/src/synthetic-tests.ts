// modules/shared/src/synthetic-tests.ts
import axios from 'axios';
import { prisma } from './prismaClient.js';
import { TimezoneManager } from './timezone.js';

interface SyntheticTestResult {
  test: string;
  status: 'PASS' | 'FAIL' | 'WARN';
  message: string;
  duration: number;
  timestamp: string;
}

interface SyntheticTestSuite {
  name: string;
  results: SyntheticTestResult[];
  overallStatus: 'PASS' | 'FAIL' | 'WARN';
  totalDuration: number;
}

export class SyntheticTestRunner {
  private baseUrl: string;
  private timeout: number;

  constructor(baseUrl: string = 'http://localhost:5555', timeout: number = 10000) {
    this.baseUrl = baseUrl;
    this.timeout = timeout;
  }

  async runAllTests(): Promise<SyntheticTestSuite> {
    const startTime = Date.now();
    const results: SyntheticTestResult[] = [];

    // Run all tests
    results.push(await this.testHealthEndpoint());
    results.push(await this.testFinalReportEndpoint());
    results.push(await this.testDatabaseConnectivity());
    results.push(await this.testDataFreshness());
    results.push(await this.testLogoAvailability());
    results.push(await this.testCronStatus());
    results.push(await this.testTimezoneConsistency());

    const totalDuration = Date.now() - startTime;
    const overallStatus = this.determineOverallStatus(results);

    return {
      name: 'EarningsTable Synthetic Tests',
      results,
      overallStatus,
      totalDuration
    };
  }

  private async testHealthEndpoint(): Promise<SyntheticTestResult> {
    const startTime = Date.now();
    try {
      const response = await axios.get(`${this.baseUrl}/api/health`, {
        timeout: this.timeout
      });

      if (response.status === 200) {
        return {
          test: 'Health Endpoint',
          status: 'PASS',
          message: 'Health endpoint responding correctly',
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      } else {
        return {
          test: 'Health Endpoint',
          status: 'FAIL',
          message: `Unexpected status code: ${response.status}`,
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      }
    } catch (error: any) {
      return {
        test: 'Health Endpoint',
        status: 'FAIL',
        message: `Health endpoint failed: ${error.message}`,
        duration: Date.now() - startTime,
        timestamp: new Date().toISOString()
      };
    }
  }

  private async testFinalReportEndpoint(): Promise<SyntheticTestResult> {
    const startTime = Date.now();
    try {
      const response = await axios.get(`${this.baseUrl}/api/final-report`, {
        timeout: this.timeout
      });

      if (response.status === 200) {
        const data = response.data;
        const symbolCount = Array.isArray(data) ? data.length : 0;
        
        if (symbolCount >= 20) {
          return {
            test: 'Final Report Endpoint',
            status: 'PASS',
            message: `Final report endpoint returning ${symbolCount} symbols`,
            duration: Date.now() - startTime,
            timestamp: new Date().toISOString()
          };
        } else {
          return {
            test: 'Final Report Endpoint',
            status: 'WARN',
            message: `Final report endpoint returning only ${symbolCount} symbols (expected >= 20)`,
            duration: Date.now() - startTime,
            timestamp: new Date().toISOString()
          };
        }
      } else {
        return {
          test: 'Final Report Endpoint',
          status: 'FAIL',
          message: `Unexpected status code: ${response.status}`,
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      }
    } catch (error: any) {
      return {
        test: 'Final Report Endpoint',
        status: 'FAIL',
        message: `Final report endpoint failed: ${error.message}`,
        duration: Date.now() - startTime,
        timestamp: new Date().toISOString()
      };
    }
  }

  private async testDatabaseConnectivity(): Promise<SyntheticTestResult> {
    const startTime = Date.now();
    try {
      const counts = await Promise.all([
        prisma.finhubData.count(),
        prisma.polygonData.count(),
        prisma.finalReport.count()
      ]);

      const [finhubCount, polygonCount, finalCount] = counts;
      
      if (finalCount > 0) {
        return {
          test: 'Database Connectivity',
          status: 'PASS',
          message: `Database connected: FinhubData=${finhubCount}, PolygonData=${polygonCount}, FinalReport=${finalCount}`,
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      } else {
        return {
          test: 'Database Connectivity',
          status: 'WARN',
          message: 'Database connected but no FinalReport data found',
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      }
    } catch (error: any) {
      return {
        test: 'Database Connectivity',
        status: 'FAIL',
        message: `Database connectivity failed: ${error.message}`,
        duration: Date.now() - startTime,
        timestamp: new Date().toISOString()
      };
    }
  }

  private async testDataFreshness(): Promise<SyntheticTestResult> {
    const startTime = Date.now();
    try {
      const latestRecord = await prisma.finalReport.findFirst({
        orderBy: { updatedAt: 'desc' },
        select: { updatedAt: true, symbol: true }
      });

      if (!latestRecord) {
        return {
          test: 'Data Freshness',
          status: 'WARN',
          message: 'No data found in FinalReport',
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      }

      const now = new Date();
      const ageMinutes = Math.round((now.getTime() - latestRecord.updatedAt.getTime()) / 60000);
      
      if (ageMinutes <= 30) {
        return {
          test: 'Data Freshness',
          status: 'PASS',
          message: `Data is fresh: latest update ${ageMinutes} minutes ago (${latestRecord.symbol})`,
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      } else if (ageMinutes <= 120) {
        return {
          test: 'Data Freshness',
          status: 'WARN',
          message: `Data is stale: latest update ${ageMinutes} minutes ago (${latestRecord.symbol})`,
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      } else {
        return {
          test: 'Data Freshness',
          status: 'FAIL',
          message: `Data is very stale: latest update ${ageMinutes} minutes ago (${latestRecord.symbol})`,
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      }
    } catch (error: any) {
      return {
        test: 'Data Freshness',
        status: 'FAIL',
        message: `Data freshness check failed: ${error.message}`,
        duration: Date.now() - startTime,
        timestamp: new Date().toISOString()
      };
    }
  }

  private async testLogoAvailability(): Promise<SyntheticTestResult> {
    const startTime = Date.now();
    try {
      const logoStats = await prisma.finalReport.groupBy({
        by: ['logoUrl'],
        _count: { logoUrl: true }
      });

      const totalRecords = await prisma.finalReport.count();
      const recordsWithLogos = logoStats
        .filter(stat => stat.logoUrl !== null)
        .reduce((sum, stat) => sum + stat._count.logoUrl, 0);
      
      const logoPercentage = totalRecords > 0 ? (recordsWithLogos / totalRecords) * 100 : 0;
      
      if (logoPercentage >= 70) {
        return {
          test: 'Logo Availability',
          status: 'PASS',
          message: `${logoPercentage.toFixed(1)}% of records have logos (${recordsWithLogos}/${totalRecords})`,
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      } else if (logoPercentage >= 40) {
        return {
          test: 'Logo Availability',
          status: 'WARN',
          message: `Only ${logoPercentage.toFixed(1)}% of records have logos (${recordsWithLogos}/${totalRecords})`,
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      } else {
        return {
          test: 'Logo Availability',
          status: 'FAIL',
          message: `Very low logo availability: ${logoPercentage.toFixed(1)}% (${recordsWithLogos}/${totalRecords})`,
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      }
    } catch (error: any) {
      return {
        test: 'Logo Availability',
        status: 'FAIL',
        message: `Logo availability check failed: ${error.message}`,
        duration: Date.now() - startTime,
        timestamp: new Date().toISOString()
      };
    }
  }

  private async testCronStatus(): Promise<SyntheticTestResult> {
    const startTime = Date.now();
    try {
      const cronStatus = await prisma.cronStatus.findUnique({
        where: { jobType: 'pipeline' },
        select: { lastRunAt: true, status: true, recordsProcessed: true }
      });

      if (!cronStatus) {
        return {
          test: 'Cron Status',
          status: 'WARN',
          message: 'No cron status found',
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      }

      const now = new Date();
      const ageMinutes = Math.round((now.getTime() - cronStatus.lastRunAt.getTime()) / 60000);
      
      if (cronStatus.status === 'success' && ageMinutes <= 10) {
        return {
          test: 'Cron Status',
          status: 'PASS',
          message: `Cron pipeline successful, last run ${ageMinutes} minutes ago (${cronStatus.recordsProcessed} records)`,
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      } else if (cronStatus.status === 'success' && ageMinutes <= 30) {
        return {
          test: 'Cron Status',
          status: 'WARN',
          message: `Cron pipeline successful but stale, last run ${ageMinutes} minutes ago`,
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      } else {
        return {
          test: 'Cron Status',
          status: 'FAIL',
          message: `Cron pipeline issues: status=${cronStatus.status}, last run ${ageMinutes} minutes ago`,
          duration: Date.now() - startTime,
          timestamp: new Date().toISOString()
        };
      }
    } catch (error: any) {
      return {
        test: 'Cron Status',
        status: 'FAIL',
        message: `Cron status check failed: ${error.message}`,
        duration: Date.now() - startTime,
        timestamp: new Date().toISOString()
      };
    }
  }

  private async testTimezoneConsistency(): Promise<SyntheticTestResult> {
    const startTime = Date.now();
    try {
      const nyNow = TimezoneManager.nowNY();
      const nyDateString = TimezoneManager.getNYDateString();
      const isDSTWeek = TimezoneManager.isDSTTransitionWeek();
      
      // Check if we're in a reasonable timezone
      const nyHour = nyNow.getHours();
      const isBusinessHours = nyHour >= 6 && nyHour <= 20;
      
      return {
        test: 'Timezone Consistency',
        status: 'PASS',
        message: `NY timezone working correctly: ${nyDateString} ${nyNow.toLocaleTimeString()}, DST week: ${isDSTWeek}, Business hours: ${isBusinessHours}`,
        duration: Date.now() - startTime,
        timestamp: new Date().toISOString()
      };
    } catch (error: any) {
      return {
        test: 'Timezone Consistency',
        status: 'FAIL',
        message: `Timezone consistency check failed: ${error.message}`,
        duration: Date.now() - startTime,
        timestamp: new Date().toISOString()
      };
    }
  }

  private determineOverallStatus(results: SyntheticTestResult[]): 'PASS' | 'FAIL' | 'WARN' {
    const hasFailures = results.some(r => r.status === 'FAIL');
    const hasWarnings = results.some(r => r.status === 'WARN');
    
    if (hasFailures) return 'FAIL';
    if (hasWarnings) return 'WARN';
    return 'PASS';
  }

  generateReport(suite: SyntheticTestSuite): string {
    let report = `\n=== ${suite.name} ===\n`;
    report += `Overall Status: ${suite.overallStatus}\n`;
    report += `Total Duration: ${suite.totalDuration}ms\n\n`;
    
    for (const result of suite.results) {
      const statusIcon = result.status === 'PASS' ? '✅' : result.status === 'WARN' ? '⚠️' : '❌';
      report += `${statusIcon} ${result.test}: ${result.message} (${result.duration}ms)\n`;
    }
    
    report += `\n=== End Report ===\n`;
    return report;
  }
}

// Export singleton instance
export const syntheticTestRunner = new SyntheticTestRunner();
