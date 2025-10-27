require("dotenv").config();
console.log("[BOOT/web] cwd=" + process.cwd());
console.log("[BOOT/web] DATABASE_URL=" + process.env.DATABASE_URL);
const express = require("express");
const cors = require("cors");
const path = require("path");
const { PrismaClient } = require("@prisma/client");

const app = express();
// Disable caching for all API responses to avoid stale data in browsers/CDNs
app.use("/api", (_req, res, next) => {
  res.setHeader("Cache-Control", "no-store");
  res.setHeader("Pragma", "no-cache");
  res.setHeader("Expires", "0");
  next();
});
// CRON status endpoint (basic)
app.get("/api/cron/status", async (_req, res) => {
  let prisma;
  try {
    const { PrismaClient } = await import("@prisma/client");
    prisma = new PrismaClient();
    const status = await prisma.cronStatus.findUnique({
      where: { jobType: "pipeline" },
      select: {
        lastRunAt: true,
        status: true,
        recordsProcessed: true,
        errorMessage: true,
      },
    });
    const nyNow = new Date(
      new Date().toLocaleString("en-US", { timeZone: "America/New_York" })
    );
    const last = status && status.lastRunAt ? new Date(status.lastRunAt) : null;
    const diffMin = last
      ? Math.round((nyNow.getTime() - last.getTime()) / 60000)
      : null;
    res.json({
      success: true,
      nyNowISO: nyNow.toISOString(),
      lastRunAt: last ? last.toISOString() : null,
      diffMin,
      isFresh: diffMin != null && diffMin <= 6,
      status: status ? status.status : null,
      recordsProcessed: status ? status.recordsProcessed : null,
      error: status ? status.errorMessage : null,
    });
  } catch (e) {
    res
      .status(500)
      .json({ success: false, error: e && e.message ? e.message : String(e) });
  } finally {
    try { if (prisma) await prisma.$disconnect(); } catch {}
  }
});

const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());

// Serve logos from the correct directory (robust to working dir)
const LOGO_DIR = path.resolve(
  process.cwd(),
  "modules",
  "web",
  "public",
  "logos"
);
console.log("[logos] serving from:", LOGO_DIR);
app.use("/logos", express.static(LOGO_DIR));

// Serve favicon
app.get("/favicon.ico", (req, res) => {
  res.sendFile(path.join(__dirname, "favicon.svg"));
});

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

// Summary statistics
app.get("/api/final-report/stats", async (req, res) => {
  try {
    const totalCount = await prisma.finalReport.count();

    // size distribution
    const sizeStats = await prisma.finalReport.groupBy({
      by: ["size"],
      _count: { size: true },
    });

    const avgChange = await prisma.finalReport.aggregate({
      _avg: { change: true },
      where: { change: { not: null } },
    });

    const avgEpsSurp = await prisma.finalReport.aggregate({
      _avg: { epsSurp: true },
      where: { epsSurp: { not: null } },
    });

    const avgRevSurp = await prisma.finalReport.aggregate({
      _avg: { revSurp: true },
      where: { revSurp: { not: null } },
    });

    const stats = {
      totalCompanies: totalCount,
      sizeDistribution: sizeStats.reduce((acc, item) => {
        acc[item.size || "Unknown"] = item._count.size;
        return acc;
      }, {}),
      averageChange: avgChange._avg.change || 0,
      averageEpsSurprise: avgEpsSurp._avg.epsSurp || 0,
      averageRevSurprise: avgRevSurp._avg.revSurp || 0,
    };

    res.json({
      success: true,
      data: stats,
      timestamp: new Date().toISOString(),
    });
  } catch (error) {
    console.error("❌ Error fetching statistics:", error);
    res.status(500).json({
      success: false,
      error: "Failed to fetch statistics",
      message: error.message,
    });
  }
});

// Get single symbol
app.get("/api/final-report/:symbol", async (req, res) => {
  try {
    const symbol = String(req.params.symbol || "").toUpperCase();
    const data = await prisma.finalReport.findUnique({ where: { symbol } });
    if (!data)
      return res
        .status(404)
        .json({ success: false, error: "Company not found" });
    res.json({ success: true, data, timestamp: new Date().toISOString() });
  } catch (error) {
    console.error("❌ Error fetching company:", error);
    res.status(500).json({
      success: false,
      error: "Failed to fetch company",
      message: error.message,
    });
  }
});

// Refresh FinalReport snapshot on-demand
app.post("/api/final-report/refresh", async (req, res) => {
  try {
    const modPath = path.join(
      __dirname,
      "modules",
      "cron",
      "src",
      "core",
      "DatabaseManager.js"
    );
    const { db } = await import(modPath);
    await db.generateFinalReport();
    const count = await prisma.finalReport.count();
    res.json({
      success: true,
      message: "FinalReport refreshed",
      count,
      timestamp: new Date().toISOString(),
    });
  } catch (error) {
    console.error("❌ Error refreshing FinalReport:", error);
    res.status(500).json({
      success: false,
      error: "Failed to refresh FinalReport",
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
app.get(["/", "/dashboard"], (req, res) => res.sendFile(DASHBOARD));
app.get("/test-logos", (req, res) =>
  res.sendFile(path.join(__dirname, "test-logos.html"))
);
app.get("/test-logo-display", (req, res) =>
  res.sendFile(path.join(__dirname, "test-logo-display.html"))
);

// Start server
app.listen(PORT, () => {
  console.log(`🚀 API Server running on port ${PORT}`);
  console.log(`📊 API endpoints:`);
  console.log(`   GET  /api/final-report`);
  console.log(`   GET  /api/final-report/stats`);
  console.log(`   GET  /api/final-report/:symbol`);
  console.log(`   POST /api/final-report/refresh`);
  console.log(`   GET  /api/health`);
  console.log(`🌐 API URL: http://localhost:${PORT}`);
});

// Graceful shutdown
process.on("SIGINT", async () => {
  console.log("\n🛑 Shutting down server...");
  await prisma.$disconnect();
  process.exit(0);
});

// Remove duplicate root + duplicate listen (cleaned)
