try {
  require("dotenv").config();
} catch (_) {
  /* optional in production */
}
console.log("[BOOT/web] cwd=" + process.cwd());
console.log("[BOOT/web] DATABASE_URL=" + process.env.DATABASE_URL);
const express = require("express");
const cors = require("cors");
const path = require("path");
const compression = require("compression");
const crypto = require("crypto");

// Try to use Prisma client from modules/shared first (where it's generated)
// This is the ONLY source we use - no fallback to root
let PrismaClient;
try {
  // Try to load from the generated location
  const sharedPrismaPath = path.resolve(
    __dirname,
    "modules",
    "shared",
    "node_modules",
    "@prisma",
    "client"
  );

  // Check if file exists first
  const fs = require("fs");
  if (!fs.existsSync(sharedPrismaPath)) {
    throw new Error(`Prisma client not found at: ${sharedPrismaPath}`);
  }

  PrismaClient = require(sharedPrismaPath).PrismaClient;
  console.log("[Prisma] ✅ Using client from modules/shared");
  console.log("[Prisma] Path:", sharedPrismaPath);
} catch (e) {
  console.error("[Prisma] ❌ Failed to load from modules/shared:", e.message);
  console.error("[Prisma] Error:", e);
  throw new Error(
    `Prisma client not found in modules/shared. Run: cd modules/shared && npm install @prisma/client && cd ../database && npx prisma generate --schema=prisma/schema.prisma`
  );
}

const app = express();
// Disable Express default ETag generation; we'll set a stable custom ETag
app.set("etag", false);

// SEO: Serve robots.txt and sitemap.xml FIRST - before any other routes or middleware
app.get("/robots.txt", (req, res) => {
  console.log("[robots] ✅ Route handler called!");
  const fs = require("fs");
  // Try multiple possible paths (__dirname and process.cwd())
  const possiblePaths = [
    path.resolve(__dirname, "public", "robots.txt"),
    path.resolve(process.cwd(), "public", "robots.txt"),
    path.join(__dirname, "public", "robots.txt"),
    path.join(process.cwd(), "public", "robots.txt"),
  ];

  let robotsPath = null;
  for (const p of possiblePaths) {
    if (fs.existsSync(p)) {
      robotsPath = p;
      break;
    }
  }

  console.log("[robots] Requested");
  console.log("[robots] __dirname:", __dirname);
  console.log("[robots] process.cwd():", process.cwd());
  console.log("[robots] Trying paths:", possiblePaths);
  console.log("[robots] Found at:", robotsPath);

  if (!robotsPath) {
    console.error("[robots] File not found in any of:", possiblePaths);
    return res.status(404).json({ error: "robots.txt not found" });
  }

  res.setHeader("Content-Type", "text/plain");
  res.sendFile(robotsPath, (err) => {
    if (err) {
      console.error("[robots] Error serving robots.txt:", err);
      res.status(500).json({ error: "Error serving robots.txt" });
    } else {
      console.log("[robots] ✅ Successfully served robots.txt");
    }
  });
});

app.get("/sitemap.xml", (req, res) => {
  console.log("[sitemap] ✅ Route handler called!");
  const fs = require("fs");
  // Try multiple possible paths (__dirname and process.cwd())
  const possiblePaths = [
    path.resolve(__dirname, "public", "sitemap.xml"),
    path.resolve(process.cwd(), "public", "sitemap.xml"),
    path.join(__dirname, "public", "sitemap.xml"),
    path.join(process.cwd(), "public", "sitemap.xml"),
  ];

  let sitemapPath = null;
  for (const p of possiblePaths) {
    if (fs.existsSync(p)) {
      sitemapPath = p;
      break;
    }
  }

  console.log("[sitemap] Requested");
  console.log("[sitemap] __dirname:", __dirname);
  console.log("[sitemap] process.cwd():", process.cwd());
  console.log("[sitemap] Trying paths:", possiblePaths);
  console.log("[sitemap] Found at:", sitemapPath);

  if (!sitemapPath) {
    console.error("[sitemap] File not found in any of:", possiblePaths);
    return res.status(404).json({ error: "sitemap.xml not found" });
  }

  res.setHeader("Content-Type", "application/xml");
  res.sendFile(sitemapPath, (err) => {
    if (err) {
      console.error("[sitemap] Error serving sitemap.xml:", err);
      res.status(500).json({ error: "Error serving sitemap.xml" });
    } else {
      console.log("[sitemap] ✅ Successfully served sitemap.xml");
    }
  });
});

// Enable gzip/br compression
app.use(compression());
// Disable caching for all API responses to avoid stale data in browsers/CDNs
app.use("/api", (_req, res, next) => {
  res.setHeader("Cache-Control", "no-store");
  res.setHeader("Pragma", "no-cache");
  res.setHeader("Expires", "0");
  next();
});
// CRON status endpoint (basic)
async function handleCronStatus(_req, res) {
  // Minimal, DB-free status to avoid runtime dependency issues
  try {
    const nyNowISO = new Date(
      new Date().toLocaleString("en-US", { timeZone: "America/New_York" })
    ).toISOString();
    return res.json({
      success: true,
      nyNowISO,
      lastRunAt: null,
      diffMin: null,
      isFresh: false,
      status: "unavailable",
      recordsProcessed: null,
      error: null,
    });
  } catch (e) {
    return res.json({ success: true, status: "unavailable" });
  }
}
app.get("/api/cron/status", handleCronStatus);
// Backward compatible alias for frontend calling /api/cron-status
app.get("/api/cron-status", handleCronStatus);

const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());

// SEO: Set X-Robots-Tag header for all responses
app.use((req, res, next) => {
  res.setHeader("X-Robots-Tag", "index, follow");
  next();
});

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

// Serve favicon (.ico and .svg) with fallbacks
app.get(["/favicon.ico", "/favicon.svg"], (req, res) => {
  const fs = require("fs");
  const svgInRepo = path.join(__dirname, "favicon.svg");
  const svgInWeb = path.resolve(
    process.cwd(),
    "modules",
    "web",
    "public",
    "logos",
    "favicon.svg"
  );
  const icoInRepo = path.join(__dirname, "favicon.ico");
  const candidate = [svgInRepo, svgInWeb, icoInRepo].find((p) =>
    fs.existsSync(p)
  );
  if (!candidate) return res.status(404).end();
  res.sendFile(candidate);
});

// Serve site.webmanifest
app.get("/site.webmanifest", (req, res) => {
  const manifestPath = path.resolve(__dirname, "site.webmanifest");
  const fs = require("fs");

  console.log("[manifest] Requested, checking path:", manifestPath);
  console.log("[manifest] __dirname:", __dirname);
  console.log("[manifest] File exists:", fs.existsSync(manifestPath));

  if (!fs.existsSync(manifestPath)) {
    console.error("[manifest] File not found at:", manifestPath);
    return res.status(404).json({ error: "Manifest not found" });
  }

  res.setHeader("Content-Type", "application/manifest+json");
  res.sendFile(manifestPath, (err) => {
    if (err) {
      console.error("[manifest] Error serving manifest:", err);
      res.status(500).json({ error: "Error serving manifest" });
    }
  });
});


// Prisma client
// Set environment variables to force using Prisma runtime from modules/shared
const sharedPrismaRuntimePath = path.resolve(
  __dirname,
  "modules",
  "shared",
  "node_modules",
  ".prisma",
  "client"
);
const fs = require("fs");

console.log("[Prisma] Checking runtime path:", sharedPrismaRuntimePath);
console.log(
  "[Prisma] Runtime path exists:",
  fs.existsSync(sharedPrismaRuntimePath)
);

// Try to find and set Prisma runtime paths dynamically
if (fs.existsSync(sharedPrismaRuntimePath)) {
  const files = fs.readdirSync(sharedPrismaRuntimePath);
  console.log("[Prisma] Runtime files:", files);

  const queryEngine = files.find(
    (f) => f.includes("query_engine") && f.endsWith(".so.node")
  );
  const schemaEngine = files.find(
    (f) => f.includes("schema-engine") && !f.includes(".so.node")
  );

  if (queryEngine) {
    process.env.PRISMA_QUERY_ENGINE_LIBRARY = path.join(
      sharedPrismaRuntimePath,
      queryEngine
    );
    console.log(
      "[Prisma] ✅ Runtime library set:",
      process.env.PRISMA_QUERY_ENGINE_LIBRARY
    );
  } else {
    console.log("[Prisma] ⚠️ Query engine not found in runtime files");
  }
  if (schemaEngine) {
    process.env.PRISMA_SCHEMA_ENGINE_BINARY = path.join(
      sharedPrismaRuntimePath,
      schemaEngine
    );
    console.log(
      "[Prisma] ✅ Schema engine set:",
      process.env.PRISMA_SCHEMA_ENGINE_BINARY
    );
  } else {
    console.log("[Prisma] ⚠️ Schema engine not found in runtime files");
  }
} else {
  console.error(
    "[Prisma] ❌ Runtime path does not exist:",
    sharedPrismaRuntimePath
  );
}

const prisma = new PrismaClient({
  datasources: {
    db: {
      url:
        process.env.DATABASE_URL ||
        "file:D:/Projects/EarningsTable/modules/database/prisma/dev.db",
    },
  },
});

// Helper function to serialize FinalReport data for JSON
function serializeFinalReport(item) {
  return {
    ...item,
    marketCap: item.marketCap ? item.marketCap.toString() : null,
    marketCapDiff: item.marketCapDiff ? item.marketCapDiff.toString() : null,
    revActual: item.revActual ? item.revActual.toString() : null,
    revEst: item.revEst ? item.revEst.toString() : null,
    createdAt: item.createdAt ? item.createdAt.toISOString() : null,
    updatedAt: item.updatedAt ? item.updatedAt.toISOString() : null,
    logoFetchedAt: item.logoFetchedAt ? item.logoFetchedAt.toISOString() : null,
  };
}

// API Routes
app.get("/api/final-report", async (req, res) => {
  try {
    console.log("📊 Fetching FinalReport data...");
    console.log("[DB] DATABASE_URL:", process.env.DATABASE_URL);

    // Test database connection first
    await prisma.$connect();
    console.log("[DB] Connection successful");

    const data = await prisma.finalReport.findMany({
      orderBy: { symbol: "asc" },
    });

    console.log(`✅ Found ${data.length} records in FinalReport`);

    // Convert BigInt and Date values to strings for JSON serialization
    const serializedData = data.map(serializeFinalReport);
    const dataTimestamp = serializedData.reduce((max, it) => {
      const t = it.updatedAt ? Date.parse(it.updatedAt) : 0;
      return t > max ? t : max;
    }, 0);
    const payload = {
      success: true,
      data: serializedData,
      count: data.length,
      timestamp: new Date().toISOString(),
      dataTimestamp: dataTimestamp
        ? new Date(dataTimestamp).toISOString()
        : null,
    };
    const etag =
      'W/"' +
      crypto
        .createHash("sha1")
        .update(
          JSON.stringify({
            c: payload.count,
            dt: payload.dataTimestamp,
            h: serializedData.map((i) => i.symbol),
          })
        )
        .digest("hex") +
      '"';

    // Honor If-None-Match (may contain multiple values); accept weak/strong variants
    const inm = (req.headers["if-none-match"] || "").toString();
    const candidates = inm
      .split(",")
      .map((s) => s.trim())
      .filter(Boolean);
    const strong = etag.replace(/^W\//, "");
    const matches = candidates.some((c) => c === etag || c === strong || ("W/" + c) === etag || c === "*");
    if (matches) {
      res.status(304).end();
      return;
    }

    if (payload.dataTimestamp)
      res.setHeader("Last-Modified", payload.dataTimestamp);
    res.setHeader("ETag", etag);
    res.json(payload);
  } catch (error) {
    console.error("❌ Error fetching FinalReport:", error);
    console.error("Error name:", error.name);
    console.error("Error message:", error.message);
    console.error("Error stack:", error.stack);
    console.error("[DB] DATABASE_URL:", process.env.DATABASE_URL);

    // In production, still log full error but return generic message
    const errorMessage =
      process.env.NODE_ENV === "production"
        ? "Internal server error"
        : `${error.name}: ${error.message}`;

    res.status(500).json({
      success: false,
      error: "Failed to fetch FinalReport data",
      message: errorMessage,
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
    console.error("Error stack:", error.stack);
    res.status(500).json({
      success: false,
      error: "Failed to fetch statistics",
      message:
        process.env.NODE_ENV === "production"
          ? "Internal server error"
          : error.message || String(error),
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
    const serialized = serializeFinalReport(data);
    res.json({
      success: true,
      data: serialized,
      timestamp: new Date().toISOString(),
    });
  } catch (error) {
    console.error("❌ Error fetching company:", error);
    console.error("Error stack:", error.stack);
    res.status(500).json({
      success: false,
      error: "Failed to fetch company",
      message:
        process.env.NODE_ENV === "production"
          ? "Internal server error"
          : error.message || String(error),
    });
  }
});

// Refresh FinalReport snapshot on-demand (disabled in production to avoid ESM import issues)
app.post("/api/final-report/refresh", async (_req, res) => {
  res.status(501).json({
    success: false,
    error:
      "Refresh endpoint is disabled in production. Use cron one-shot instead.",
  });
});

// Last good data endpoint (24h cache)
let lastGoodData = null;
let lastGoodDataTimestamp = null;
const CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 hours

app.get("/api/final-report/last-good", async (req, res) => {
  try {
    const now = Date.now();

    // Check if we have cached data and it's still fresh
    if (
      lastGoodData &&
      lastGoodDataTimestamp &&
      now - lastGoodDataTimestamp < CACHE_DURATION
    ) {
      res.json({
        success: true,
        data: lastGoodData,
        cached: true,
        cachedAt: new Date(lastGoodDataTimestamp).toISOString(),
        age: Math.round((now - lastGoodDataTimestamp) / 60000), // minutes
      });
      return;
    }

    // Try to get fresh data
    const freshData = await prisma.finalReport.findMany({
      orderBy: { updatedAt: "desc" },
      take: 1000,
    });

    if (freshData && freshData.length > 0) {
      // Serialize data before caching
      const serializedFreshData = freshData.map(serializeFinalReport);
      // Update cache
      lastGoodData = serializedFreshData;
      lastGoodDataTimestamp = now;

      res.json({
        success: true,
        data: serializedFreshData,
        cached: false,
        cachedAt: new Date(now).toISOString(),
        age: 0,
      });
    } else {
      // No fresh data, return cached if available
      if (lastGoodData) {
        res.json({
          success: true,
          data: lastGoodData,
          cached: true,
          cachedAt: new Date(lastGoodDataTimestamp).toISOString(),
          age: Math.round((now - lastGoodDataTimestamp) / 60000),
          warning: "Using cached data - no fresh data available",
        });
      } else {
        res.status(503).json({
          success: false,
          error: "No data available",
          message: "No earnings data available",
        });
      }
    }
  } catch (error) {
    console.error("Last good data endpoint error:", error);
    console.error("Error stack:", error.stack);

    // Return cached data if available
    if (lastGoodData) {
      res.json({
        success: true,
        data: lastGoodData,
        cached: true,
        cachedAt: new Date(lastGoodDataTimestamp).toISOString(),
        age: Math.round((Date.now() - lastGoodDataTimestamp) / 60000),
        warning: "Using cached data due to error",
      });
    } else {
      res.status(500).json({
        success: false,
        error: "Database error",
        message:
          process.env.NODE_ENV === "production"
            ? "Unable to fetch earnings data"
            : error.message || String(error),
      });
    }
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
  console.log(`   GET  /api/cron-status (alias: /api/cron/status)`);
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
