import express from 'express';
import cors from 'cors';
import path from 'path';
import { getFinalReport, getFinalReportStats, getCompanyData, refreshFinalReport } from './api-routes';

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static(__dirname));

// API Routes
app.get('/api/final-report', getFinalReport);
app.get('/api/final-report/stats', getFinalReportStats);
app.get('/api/final-report/:symbol', getCompanyData);
app.post('/api/final-report/refresh', refreshFinalReport);

// Health check endpoint
app.get('/api/health', (req, res) => {
  res.json({
    status: 'healthy',
    timestamp: new Date().toISOString(),
    uptime: process.uptime()
  });
});

// Serve simple dashboard for all other routes
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, 'simple-dashboard.html'));
});

// Error handling middleware
app.use((err: Error, req: express.Request, res: express.Response, next: express.NextFunction) => {
  console.error('❌ Server error:', err);
  res.status(500).json({
    success: false,
    error: 'Internal server error',
    message: process.env.NODE_ENV === 'development' ? err.message : 'Something went wrong'
  });
});

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Server running on port ${PORT}`);
  console.log(`📊 API endpoints:`);
  console.log(`   GET  /api/final-report - Get all earnings data`);
  console.log(`   GET  /api/final-report/stats - Get summary statistics`);
  console.log(`   GET  /api/final-report/:symbol - Get specific company`);
  console.log(`   POST /api/final-report/refresh - Refresh data`);
  console.log(`   GET  /api/health - Health check`);
  console.log(`🌐 Web interface: http://localhost:${PORT}`);
});

export default app;
