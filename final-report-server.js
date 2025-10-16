const express = require("express");
const cors = require("cors");
const fs = require("fs");
const path = require("path");

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());

// Mock data based on FinalReport structure
const mockFinalReportData = [
  {
    id: 1,
    symbol: "AAPL",
    name: "Apple Inc.",
    size: "Mega",
    marketCap: "2500000000000",
    marketCapDiff: "50000000000",
    price: 150.25,
    change: 2.15,
    epsActual: 1.52,
    epsEst: 1.43,
    epsSurp: 6.29,
    revActual: "89498000000",
    revEst: "88500000000",
    revSurp: 1.13,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 2,
    symbol: "MSFT",
    name: "Microsoft Corporation",
    size: "Mega",
    marketCap: "2300000000000",
    marketCapDiff: "-30000000000",
    price: 295.8,
    change: -1.02,
    epsActual: 2.93,
    epsEst: 2.85,
    epsSurp: 2.81,
    revActual: "52857000000",
    revEst: "52500000000",
    revSurp: 0.68,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 3,
    symbol: "GOOGL",
    name: "Alphabet Inc.",
    size: "Mega",
    marketCap: "1800000000000",
    marketCapDiff: "25000000000",
    price: 135.45,
    change: 1.85,
    epsActual: 1.55,
    epsEst: 1.45,
    epsSurp: 6.9,
    revActual: "76093000000",
    revEst: "75000000000",
    revSurp: 1.46,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 4,
    symbol: "AMZN",
    name: "Amazon.com Inc.",
    size: "Mega",
    marketCap: "1600000000000",
    marketCapDiff: "-20000000000",
    price: 145.8,
    change: -0.9,
    epsActual: 0.98,
    epsEst: 1.05,
    epsSurp: -6.67,
    revActual: "143100000000",
    revEst: "142500000000",
    revSurp: 0.42,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
  {
    id: 5,
    symbol: "TSLA",
    name: "Tesla Inc.",
    size: "Large",
    marketCap: "750000000000",
    marketCapDiff: "45000000000",
    price: 245.3,
    change: 4.2,
    epsActual: 1.12,
    epsEst: 0.95,
    epsSurp: 17.89,
    revActual: "25500000000",
    revEst: "24800000000",
    revSurp: 2.82,
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  },
];

// API Routes
app.get("/api/final-report", async (req, res) => {
  try {
    console.log("📊 Fetching FinalReport data...");

    // For now, return mock data that matches FinalReport structure
    // TODO: Replace with real database query when Prisma is fixed
    const data = mockFinalReportData;

    console.log(`✅ Found ${data.length} records in FinalReport`);

    res.json({
      success: true,
      data: data,
      count: data.length,
      timestamp: new Date().toISOString(),
    });
  } catch (error) {
    console.error("❌ Error fetching FinalReport:", error);
    res.status(500).json({
      success: false,
      error: "Failed to fetch FinalReport data",
      message: error.message,
    });
  }
});

// Health check endpoint
app.get("/api/health", (req, res) => {
  res.json({
    status: "healthy",
    timestamp: new Date().toISOString(),
    message: "FinalReport API server is running",
  });
});

// Start server
app.listen(PORT, () => {
  console.log(`🚀 FinalReport API Server running on port ${PORT}`);
  console.log(`📊 API endpoints:`);
  console.log(`   GET  /api/final-report - Get all FinalReport data`);
  console.log(`   GET  /api/health - Health check`);
  console.log(`🌐 API URL: http://localhost:${PORT}`);
  console.log(
    `📝 Note: Using mock FinalReport data (structure matches real DB)`
  );
});

// Graceful shutdown
process.on("SIGINT", async () => {
  console.log("\n🛑 Shutting down server...");
  process.exit(0);
});
