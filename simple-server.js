const express = require("express");
const cors = require("cors");
const path = require("path");
const { PrismaClient } = require("@prisma/client");

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static(__dirname)); // serve static files
app.use(
  "/logos",
  express.static(path.join(__dirname, "modules", "web", "public", "logos"))
); // serve logos

// Prisma client
const prisma = new PrismaClient({
  datasources: {
    db: {
      url:
        process.env.DATABASE_URL ||
        "file:D:/Projects/EarningsTable/modules/database/prisma/dev.db",
    },
  },
});

// API Routes
app.get("/api/final-report", async (req, res) => {
  try {
    console.log("📊 Fetching FinalReport data...");

    const data = await prisma.finalReport.findMany({
      orderBy: { symbol: "asc" },
    });

    console.log(`✅ Found ${data.length} records in FinalReport`);

    // Convert BigInt values to strings for JSON serialization
    const serializedData = data.map((item) => ({
      ...item,
      marketCap: item.marketCap ? item.marketCap.toString() : null,
      marketCapDiff: item.marketCapDiff ? item.marketCapDiff.toString() : null,
      revActual: item.revActual ? item.revActual.toString() : null,
      revEst: item.revEst ? item.revEst.toString() : null,
    }));

    res.json({
      success: true,
      data: serializedData,
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
    uptime: process.uptime(),
  });
});

// Dashboard routes - using simple-dashboard.html (nice UX)
const DASHBOARD = path.resolve(__dirname, "simple-dashboard.html");

app.get("/", (req, res) => res.sendFile(DASHBOARD));
app.get("/dashboard", (req, res) => res.sendFile(DASHBOARD));

// Start server
app.listen(PORT, () => {
  console.log(`🚀 API Server running on port ${PORT}`);
  console.log(`📊 API endpoints:`);
  console.log(`   GET  /api/final-report - Get all earnings data`);
  console.log(`   GET  /api/health - Health check`);
  console.log(`🌐 API URL: http://localhost:${PORT}`);
});

// Graceful shutdown
process.on("SIGINT", async () => {
  console.log("\n🛑 Shutting down server...");
  await prisma.$disconnect();
  process.exit(0);
});
