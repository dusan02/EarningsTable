import express from 'express';
import path from 'path';
import { fileURLToPath } from 'url';
import { prisma } from '../../shared/src/prismaClient.js';
import { CONFIG } from './config.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 5555;

// Middleware
app.use(express.json());
app.use(express.static(path.join(__dirname, '..', 'public')));

// Health check endpoint
app.get('/health', async (req, res) => {
  try {
    // Test database connection
    const finhubCount = await prisma.finhubData.count();
    const polygonCount = await prisma.polygonData.count();
    const finalCount = await prisma.finalReport.count();
    
    res.json({
      status: 'ok',
      timestamp: new Date().toISOString(),
      database: {
        connected: true,
        tables: {
          finhubData: finhubCount,
          polygonData: polygonCount,
          finalReport: finalCount
        }
      }
    });
  } catch (error) {
    console.error('Health check failed:', error);
    res.status(500).json({
      status: 'error',
      timestamp: new Date().toISOString(),
      error: 'Database connection failed'
    });
  }
});

// API endpoint pre získanie earnings dát
app.get('/api/earnings', async (req, res) => {
  try {
    const { date, symbol, limit = '100' } = req.query;
    
    let whereClause: any = {};
    
    if (date) {
      const targetDate = new Date(date as string);
      whereClause.reportDate = {
        gte: new Date(targetDate.getFullYear(), targetDate.getMonth(), targetDate.getDate()),
        lt: new Date(targetDate.getFullYear(), targetDate.getMonth(), targetDate.getDate() + 1),
      };
    }
    
    if (symbol) {
      whereClause.symbol = {
        contains: symbol as string,
        mode: 'insensitive'
      };
    }
    
    const earnings = await prisma.finhubData.findMany({
      where: whereClause,
      orderBy: [
        { reportDate: 'desc' },
        { symbol: 'asc' }
      ],
      take: parseInt(limit as string),
    });
    
    // Convert BigInt values to strings for JSON serialization
    const serializedEarnings = earnings.map(earning => ({
      ...earning,
      // Convert any BigInt fields to strings
      id: earning.id.toString(),
      reportDate: earning.reportDate.toISOString(),
      // Convert all BigInt fields to strings
      epsActual: earning.epsActual ? earning.epsActual.toString() : null,
      epsEstimate: earning.epsEstimate ? earning.epsEstimate.toString() : null,
      revenueActual: earning.revenueActual ? earning.revenueActual.toString() : null,
      revenueEstimate: earning.revenueEstimate ? earning.revenueEstimate.toString() : null,
      quarter: earning.quarter ? earning.quarter.toString() : null,
      year: earning.year ? earning.year.toString() : null,
    }));

    res.json({
      success: true,
      count: serializedEarnings.length,
      data: serializedEarnings
    });
  } catch (error) {
    console.error('Error fetching earnings:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to fetch earnings data'
    });
  }
});

// API endpoint pre získanie final report dát (kombinované dáta)
app.get('/api/final-report', async (req, res) => {
  try {
    const { symbol, limit = '100' } = req.query;
    
    let whereClause: any = {};
    
    if (symbol) {
      whereClause.symbol = {
        contains: symbol as string,
        mode: 'insensitive'
      };
    }
    
    const finalReports = await prisma.finalReport.findMany({
      where: whereClause,
      orderBy: [
        { marketCap: 'desc' },
        { symbol: 'asc' }
      ],
      take: parseInt(limit as string),
    });
    
    // Convert BigInt values to strings for JSON serialization
    const serializedReports = finalReports.map(report => ({
      ...report,
      // Convert any BigInt fields to strings
      id: report.id.toString(),
      marketCap: report.marketCap ? report.marketCap.toString() : null,
      marketCapDiff: report.marketCapDiff ? report.marketCapDiff.toString() : null,
      revActual: report.revActual ? report.revActual.toString() : null,
      revEst: report.revEst ? report.revEst.toString() : null,
      createdAt: report.createdAt.toISOString(),
      updatedAt: report.updatedAt.toISOString(),
      // Include logo fields
      logoUrl: report.logoUrl,
      logoSource: report.logoSource,
      logoFetchedAt: report.logoFetchedAt ? report.logoFetchedAt.toISOString() : null,
    }));

    res.json({
      success: true,
      count: serializedReports.length,
      data: serializedReports
    });
  } catch (error) {
    console.error('Error fetching final report:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to fetch final report data'
    });
  }
});

// Hlavná HTML stránka - serve simple dashboard directly (no redirect)
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, '../../../simple-dashboard.html'));
});

// Serve simple dashboard
app.get('/simple-dashboard.html', (req, res) => {
  res.sendFile(path.join(__dirname, '../../../simple-dashboard.html'));
});

// Graceful shutdown
process.on('SIGINT', async () => {
  console.log('\n→ Shutting down web server...');
  await prisma.$disconnect();
  process.exit(0);
});

process.on('SIGTERM', async () => {
  console.log('\n→ Shutting down web server...');
  await prisma.$disconnect();
  process.exit(0);
});

// API endpoint pre získanie cron status a posledného timestamp
app.get('/api/cron-status', async (req, res) => {
  try {
    const cronStatuses = await prisma.cronStatus.findMany({
      orderBy: { lastRunAt: 'desc' },
    });

    // Get the most recent successful run (prefer pipeline, then polygon, then finnhub)
    const pipelineStatus = cronStatuses.find(s => s.jobType === 'pipeline' && s.status === 'success');
    const polygonStatus = cronStatuses.find(s => s.jobType === 'polygon' && s.status === 'success');
    const finnhubStatus = cronStatuses.find(s => s.jobType === 'finnhub' && s.status === 'success');
    
    const lastUpdate = pipelineStatus?.lastRunAt || polygonStatus?.lastRunAt || finnhubStatus?.lastRunAt;

    res.json({
      success: true,
      lastUpdate: lastUpdate ? lastUpdate.toISOString() : null,
      cronStatuses: cronStatuses.map(status => ({
        jobType: status.jobType,
        lastRunAt: status.lastRunAt.toISOString(),
        status: status.status,
        recordsProcessed: status.recordsProcessed,
        errorMessage: status.errorMessage,
      }))
    });
  } catch (error) {
    console.error('Error fetching cron status:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to fetch cron status'
    });
  }
});

app.listen(PORT, () => {
  console.log(`🌐 Web server running at http://localhost:${PORT}`);
  console.log(`📊 Earnings table available at http://localhost:${PORT}`);
});
