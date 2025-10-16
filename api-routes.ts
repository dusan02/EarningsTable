import { Request, Response } from 'express';
import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient({
  datasources: {
    db: {
      url: process.env.DATABASE_URL || 'file:./modules/database/prisma/dev.db'
    }
  }
});

// GET /api/final-report - Fetch all FinalReport data
export const getFinalReport = async (req: Request, res: Response) => {
  try {
    console.log('📊 Fetching FinalReport data...');
    
    const data = await prisma.finalReport.findMany({
      orderBy: { symbol: 'asc' },
    });

    console.log(`✅ Found ${data.length} records in FinalReport`);
    
    res.json({
      success: true,
      data: data,
      count: data.length,
      timestamp: new Date().toISOString()
    });
    
  } catch (error) {
    console.error('❌ Error fetching FinalReport:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to fetch FinalReport data',
      message: error instanceof Error ? error.message : 'Unknown error'
    });
  }
};

// GET /api/final-report/stats - Get summary statistics
export const getFinalReportStats = async (req: Request, res: Response) => {
  try {
    console.log('📈 Fetching FinalReport statistics...');
    
    const totalCount = await prisma.finalReport.count();
    
    const sizeStats = await prisma.finalReport.groupBy({
      by: ['size'],
      _count: {
        size: true
      }
    });

    const avgChange = await prisma.finalReport.aggregate({
      _avg: {
        change: true
      },
      where: {
        change: { not: null }
      }
    });

    const avgEpsSurp = await prisma.finalReport.aggregate({
      _avg: {
        epsSurp: true
      },
      where: {
        epsSurp: { not: null }
      }
    });

    const avgRevSurp = await prisma.finalReport.aggregate({
      _avg: {
        revSurp: true
      },
      where: {
        revSurp: { not: null }
      }
    });

    const stats = {
      totalCompanies: totalCount,
      sizeDistribution: sizeStats.reduce((acc, item) => {
        acc[item.size || 'Unknown'] = item._count.size;
        return acc;
      }, {} as Record<string, number>),
      averageChange: avgChange._avg.change || 0,
      averageEpsSurprise: avgEpsSurp._avg.epsSurp || 0,
      averageRevSurprise: avgRevSurp._avg.revSurp || 0
    };

    console.log('✅ Statistics calculated successfully');
    
    res.json({
      success: true,
      data: stats,
      timestamp: new Date().toISOString()
    });
    
  } catch (error) {
    console.error('❌ Error fetching statistics:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to fetch statistics',
      message: error instanceof Error ? error.message : 'Unknown error'
    });
  }
};

// GET /api/final-report/:symbol - Get specific company data
export const getCompanyData = async (req: Request, res: Response) => {
  try {
    const { symbol } = req.params;
    
    console.log(`🔍 Fetching data for symbol: ${symbol}`);
    
    const data = await prisma.finalReport.findUnique({
      where: { symbol: symbol.toUpperCase() }
    });

    if (!data) {
      return res.status(404).json({
        success: false,
        error: 'Company not found',
        message: `No data found for symbol: ${symbol}`
      });
    }

    console.log(`✅ Found data for ${symbol}`);
    
    res.json({
      success: true,
      data: data,
      timestamp: new Date().toISOString()
    });
    
  } catch (error) {
    console.error(`❌ Error fetching data for ${req.params.symbol}:`, error);
    res.status(500).json({
      success: false,
      error: 'Failed to fetch company data',
      message: error instanceof Error ? error.message : 'Unknown error'
    });
  }
};

// POST /api/final-report/refresh - Trigger data refresh
export const refreshFinalReport = async (req: Request, res: Response) => {
  try {
    console.log('🔄 Triggering FinalReport refresh...');
    
    // Import your DatabaseManager
    const { db } = await import('../modules/cron/src/core/DatabaseManager.js');
    
    // Generate fresh FinalReport
    await db.generateFinalReport();
    
    // Get updated count
    const count = await prisma.finalReport.count();
    
    console.log(`✅ FinalReport refreshed successfully. ${count} records.`);
    
    res.json({
      success: true,
      message: 'FinalReport refreshed successfully',
      count: count,
      timestamp: new Date().toISOString()
    });
    
  } catch (error) {
    console.error('❌ Error refreshing FinalReport:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to refresh FinalReport',
      message: error instanceof Error ? error.message : 'Unknown error'
    });
  }
};
