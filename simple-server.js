try {
  require("dotenv").config();
} catch (_) {
  /* optional in production */
}

console.log("[BOOT/web] cwd=" + process.cwd());
console.log("[BOOT/web] DATABASE_URL=" + process.env.DATABASE_URL);

// Warn if DATABASE_URL is not set
if (!process.env.DATABASE_URL) {
  console.warn("[BOOT/web] ⚠️ DATABASE_URL is not set!");
  console.warn(
    "[BOOT/web] Create .env file or set DATABASE_URL environment variable"
  );
  console.warn(
    "[BOOT/web] Example: DATABASE_URL=file:D:/Projects/EarningsTable/modules/database/prisma/dev.db"
  );
}
const express = require("express");
const cors = require("cors");
const path = require("path");
const compression = require("compression");
const crypto = require("crypto");

// Try to use Prisma client from modules/shared first (where it's generated)
// If not present, fall back to root node_modules (so we can start locally even
// when the shared client hasn't been generated yet).
let PrismaClient;
try {
  const fs = require("fs");
  const sharedPrismaPath = path.resolve(
    __dirname,
    "modules",
    "shared",
    "node_modules",
    "@prisma",
    "client"
  );

  if (fs.existsSync(sharedPrismaPath)) {
    PrismaClient = require(sharedPrismaPath).PrismaClient;
    console.log("[Prisma] ✅ Using client from modules/shared");
    console.log("[Prisma] Path:", sharedPrismaPath);
  } else {
    // Fallback to root node_modules
    PrismaClient = require("@prisma/client").PrismaClient;
    console.log(
      "[Prisma] ⚠️ Shared Prisma client missing, using root @prisma/client"
    );
  }
} catch (e) {
  console.error("[Prisma] ❌ Failed to load Prisma client:", e.message);
  console.error("[Prisma] Error:", e);
  throw new Error(
    `Prisma client not found. Try: npm install && npx prisma generate --schema=modules/database/prisma/schema.prisma`
  );
}

const app = express();
// Disable Express default ETag generation; we'll set a stable custom ETag
app.set("etag", false);

// Request logger — only log non-200 responses (errors)
app.use((req, res, next) => {
  res.on('finish', () => {
    if (res.statusCode >= 400) {
      console.error(`[${res.statusCode}] ${req.method} ${req.path}`);
    }
  });
  next();
});

// Serve /public directory as static files (fallback if Nginx doesn't handle it)
const PUBLIC_DIR = path.resolve(__dirname, "public");
app.use(express.static(PUBLIC_DIR, { fallthrough: true }));

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
// CRON status endpoint (reads from database)
async function handleCronStatus(_req, res) {
  try {
    // Use FinalReport.updatedAt as primary source (more reliable, faster query)
    let lastUpdate = null;
    let recordsProcessed = null;

    try {
      const latestReport = await Promise.race([
        prisma.finalReport.findFirst({
          orderBy: { updatedAt: "desc" },
          select: { updatedAt: true },
        }),
        new Promise((_, reject) =>
          setTimeout(() => reject(new Error("Timeout")), 3000)
        ),
      ]);

      if (latestReport?.updatedAt) {
        lastUpdate = latestReport.updatedAt;
        console.log(
          "[cron-status] Got lastUpdate from Prisma:",
          lastUpdate.toISOString()
        );
      } else {
        console.log(
          "[cron-status] Prisma query succeeded but no updatedAt found"
        );
      }

      // Get record count (quick query)
      recordsProcessed = await Promise.race([
        prisma.finalReport.count(),
        new Promise((_, reject) =>
          setTimeout(() => reject(new Error("Timeout")), 2000)
        ),
      ]).catch(() => null);
    } catch (reportError) {
      console.error(
        "[cron-status] FinalReport query failed:",
        reportError.message
      );
      // Try raw SQL as fallback
      try {
        const sqlite3 = require("sqlite3").verbose();
        const dbPath =
          process.env.DATABASE_URL?.replace("file:", "") ||
          path.resolve(__dirname, "modules", "database", "prisma", "prod.db");
        const db = new sqlite3.Database(dbPath);
        const row = await new Promise((resolve, reject) => {
          db.get(
            "SELECT MAX(updatedAt) as lastUpdate, COUNT(*) as count FROM final_report",
            (err, row) => {
              db.close();
              if (err) reject(err);
              else resolve(row);
            }
          );
        });
        if (row?.lastUpdate) {
          // updatedAt is stored as ISO string in SQLite
          lastUpdate = new Date(row.lastUpdate);
          recordsProcessed = row.count;
        }
      } catch (sqlError) {
        console.error("[cron-status] SQL fallback failed:", sqlError.message);
      }
    }

    const nyNow = new Date();
    const nyNowISO = new Date(
      nyNow.toLocaleString("en-US", { timeZone: "America/New_York" })
    ).toISOString();

    if (lastUpdate) {
      const lastRun = new Date(lastUpdate);
      const diffMs = nyNow - lastRun;
      const diffMin = Math.floor(diffMs / 60000);

      return res.json({
        success: true,
        nyNowISO,
        lastUpdate: lastUpdate.toISOString(), // Frontend expects 'lastUpdate'
        lastRunAt: lastUpdate.toISOString(),
        diffMin,
        isFresh: diffMin < 10, // Consider fresh if less than 10 minutes
        status: "success",
        recordsProcessed: recordsProcessed,
        error: null,
      });
    }

    // No status found - return current time as fallback
    return res.json({
      success: true,
      nyNowISO,
      lastUpdate: nyNowISO, // Frontend expects 'lastUpdate'
      lastRunAt: null,
      diffMin: null,
      isFresh: false,
      status: "unknown",
      recordsProcessed: null,
      error: null,
    });
  } catch (e) {
    console.error("[cron-status] Error:", e);
    // Fallback to current time
    const nyNowISO = new Date(
      new Date().toLocaleString("en-US", { timeZone: "America/New_York" })
    ).toISOString();
    return res.json({
      success: true,
      nyNowISO,
      lastUpdate: nyNowISO, // Frontend expects 'lastUpdate'
      lastRunAt: null,
      diffMin: null,
      isFresh: false,
      status: "error",
      recordsProcessed: null,
      error: e.message,
    });
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
// Try multiple paths to handle different execution contexts
const LOGO_DIR = (() => {
  const fs = require("fs");
  const possiblePaths = [
    // Production paths first (most likely to exist)
    "/var/www/earnings-table/modules/web/public/logos",
    "/srv/EarningsTable/modules/web/public/logos",
    // Relative paths (for development and different execution contexts)
    path.resolve(__dirname, "modules", "web", "public", "logos"),
    path.resolve(process.cwd(), "modules", "web", "public", "logos"),
    path.join(__dirname, "modules", "web", "public", "logos"),
    path.join(process.cwd(), "modules", "web", "public", "logos"),
  ];

  console.log("[logos] Checking paths for logo directory...");

  for (const logoPath of possiblePaths) {
    if (fs.existsSync(logoPath)) {
      console.log("[logos] Found logo directory:", logoPath);
      return logoPath;
    }
  }

  // Fallback to first path even if it doesn't exist (will log error when accessed)
  const fallback = possiblePaths[0];
  console.warn(
    "[logos] ⚠️ Logo directory not found, using fallback:",
    fallback
  );
  console.warn("[logos] Tried paths:", possiblePaths);
  return fallback;
})();
console.log("[logos] serving from:", LOGO_DIR);
app.use(
  "/logos",
  express.static(LOGO_DIR, {
    maxAge: "7d",
  })
);

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

  // Find query engine - Windows uses .dll.node, Linux/Mac use .so.node
  const queryEngine = files.find(
    (f) =>
      f.includes("query_engine") &&
      (f.endsWith(".so.node") || f.endsWith(".dll.node"))
  );
  // Schema engine is a binary executable (no extension on Unix, .exe on Windows)
  // On Windows it might be schema-engine.exe, on Unix just schema-engine
  const schemaEngine = files.find((f) => {
    const isSchemaEngine = f.includes("schema-engine");
    const isNotNode = !f.includes(".node");
    const isNotJs = !f.endsWith(".js");
    const isNotDts = !f.endsWith(".d.ts");
    return isSchemaEngine && isNotNode && isNotJs && isNotDts;
  });

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
      url: process.env.DATABASE_URL,
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
    reportDate: item.reportDate ? item.reportDate.toISOString() : null,
    snapshotDate: item.snapshotDate ? item.snapshotDate.toISOString() : null,
    createdAt: item.createdAt ? item.createdAt.toISOString() : null,
    updatedAt: item.updatedAt ? item.updatedAt.toISOString() : null,
    logoFetchedAt: item.logoFetchedAt ? item.logoFetchedAt.toISOString() : null,
  };
}

// API Routes
app.get("/api/final-report", async (req, res) => {
  try {
    const allData = await prisma.finalReport.findMany();

    // Sort: non-null marketCap DESC, then null marketCap at end, then by symbol ASC
    const data = allData.sort((a, b) => {
      // Convert BigInt to Number for comparison
      const aCap = a.marketCap != null ? Number(a.marketCap) : null;
      const bCap = b.marketCap != null ? Number(b.marketCap) : null;

      // If both have marketCap, sort by marketCap DESC
      if (aCap != null && bCap != null) {
        return bCap - aCap;
      }
      // If only a has marketCap, a comes first
      if (aCap != null) return -1;
      // If only b has marketCap, b comes first
      if (bCap != null) return 1;
      // Both null, sort by symbol ASC
      return a.symbol.localeCompare(b.symbol);
    });

    console.log(`Found ${data.length} records in FinalReport`);

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
    const matches = candidates.some(
      (c) => c === etag || c === strong || "W/" + c === etag || c === "*"
    );
    if (matches) {
      res.status(304).end();
      return;
    }

    if (payload.dataTimestamp)
      res.setHeader("Last-Modified", payload.dataTimestamp);
    res.setHeader("ETag", etag);
    res.json(payload);
  } catch (error) {
    console.error("Error fetching FinalReport:", error.message);

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

// Get available earnings dates with counts
app.get("/api/final-report/dates", async (req, res) => {
  try {
    const rows = await prisma.finalReport.findMany({
      where: { reportDate: { not: null } },
      select: { reportDate: true, symbol: true },
    });

    const dateMap = {};
    for (const row of rows) {
      if (!row.reportDate) continue;
      const dateStr = row.reportDate.toISOString().split("T")[0];
      dateMap[dateStr] = (dateMap[dateStr] || 0) + 1;
    }

    const dates = Object.entries(dateMap)
      .map(([date, count]) => ({ date, count }))
      .sort((a, b) => a.date.localeCompare(b.date));

    res.json({ success: true, data: dates, timestamp: new Date().toISOString() });
  } catch (error) {
    console.error("Error fetching dates:", error.message);
    res.status(500).json({ success: false, error: "Failed to fetch dates" });
  }
});

// Get earnings for a specific date (YYYY-MM-DD)
app.get("/api/final-report/date/:date", async (req, res) => {
  try {
    const dateStr = String(req.params.date || "");
    if (!/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
      return res.status(400).json({ success: false, error: "Invalid date format. Use YYYY-MM-DD." });
    }

    const dayStart = new Date(`${dateStr}T00:00:00.000Z`);
    const dayEnd = new Date(`${dateStr}T23:59:59.999Z`);

    const data = await prisma.finalReport.findMany({
      where: {
        reportDate: { gte: dayStart, lte: dayEnd },
      },
      orderBy: { symbol: "asc" },
    });

    const serializedData = data.map(serializeFinalReport);
    res.json({
      success: true,
      data: serializedData,
      count: serializedData.length,
      date: dateStr,
      timestamp: new Date().toISOString(),
    });
  } catch (error) {
    console.error("Error fetching date data:", error.message);
    res.status(500).json({ success: false, error: "Failed to fetch data for date" });
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

// Last good data endpoint (24h cache) — persisted to disk for restart resilience
const CACHE_FILE = path.resolve(__dirname, ".cache-last-good.json");
let lastGoodData = null;
let lastGoodDataTimestamp = null;
const CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 hours

// Load cache from disk on startup
try {
  const fs = require("fs");
  if (fs.existsSync(CACHE_FILE)) {
    const raw = fs.readFileSync(CACHE_FILE, "utf-8");
    const parsed = JSON.parse(raw);
    lastGoodData = parsed.data;
    lastGoodDataTimestamp = parsed.timestamp;
    console.log(`[cache] Loaded last-good data from disk (age: ${Math.round((Date.now() - lastGoodDataTimestamp) / 60000)}min)`);
  }
} catch (e) {
  console.error("[cache] Failed to load cache from disk:", e.message);
}

function saveCacheToDisk() {
  try {
    const fs = require("fs");
    fs.writeFileSync(CACHE_FILE, JSON.stringify({ data: lastGoodData, timestamp: lastGoodDataTimestamp }));
  } catch (e) {
    console.error("[cache] Failed to save cache to disk:", e.message);
  }
}

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
      saveCacheToDisk();

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

// Start server
app.listen(PORT, () => {
  console.log(`🚀 API Server running on port ${PORT}`);
  console.log(`API endpoints:`);
  console.log(`   GET  /api/final-report`);
  console.log(`   GET  /api/final-report/dates`);
  console.log(`   GET  /api/final-report/date/:date`);
  console.log(`   GET  /api/final-report/stats`);
  console.log(`   GET  /api/final-report/:symbol`);
  console.log(`   GET  /api/cron-status (alias: /api/cron/status)`);
  console.log(`   GET  /api/health`);
  console.log(`🌐 API URL: http://localhost:${PORT}`);
});

// Keep-alive mechanism to prevent process from exiting
// Express server should keep event loop alive, but add explicit keep-alive as safety
console.log("✅ Keep-alive mechanism initialized");
const keepAlive = setInterval(() => {
  // Heartbeat to keep event loop active - log every 5 minutes to confirm it's running
  const now = new Date();
  if (now.getMinutes() % 5 === 0 && now.getSeconds() < 10) {
    console.log(
      `💓 Keep-alive heartbeat: ${now.toISOString()}, uptime: ${process.uptime()}s`
    );
  }
}, 60000);

// Log process events for debugging
process.on("beforeExit", (code) => {
  console.error(`beforeExit: ${code}`);
});

process.on("exit", (code) => {
  console.error(`exit: ${code}`);
});

// Graceful shutdown
process.on("SIGINT", async () => {
  const timestamp = new Date().toISOString();

  console.log(`\nSIGINT received at ${timestamp}`);
  clearInterval(keepAlive);
  await prisma.$disconnect();
  process.exit(0);
});

process.on("SIGTERM", async () => {
  const timestamp = new Date().toISOString();
  console.log(`\nSIGTERM received at ${timestamp}`);
  clearInterval(keepAlive);
  await prisma.$disconnect();
  process.exit(0);
});

// Remove duplicate root + duplicate listen (cleaned)
