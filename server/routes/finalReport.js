// FinalReport API routes: list, stats, dates, by-date, last-good (disk-cached),
// refresh (disabled), single symbol. Registered in order so special paths
// (/dates, /date/:date, /stats, /last-good) are not shadowed by /:symbol.
const path = require("path");
const fs = require("fs");
const crypto = require("crypto");
const { prisma } = require("../prisma");
const { serializeFinalReport } = require("../serialize");

function registerFinalReportRoutes(app) {
  // GET /api/final-report — full snapshot (bounded to 5000 rows), sorted by
  // marketCap DESC then symbol ASC, with a stable weak ETag for conditional GET.
  app.get("/api/final-report", async (req, res) => {
    try {
      const allData = await prisma.finalReport.findMany({ take: 5000 });

      const data = allData.sort((a, b) => {
        const aCap = a.marketCap != null ? Number(a.marketCap) : null;
        const bCap = b.marketCap != null ? Number(b.marketCap) : null;
        if (aCap != null && bCap != null) return bCap - aCap;
        if (aCap != null) return -1;
        if (bCap != null) return 1;
        return a.symbol.localeCompare(b.symbol);
      });

      console.log(`Found ${data.length} records in FinalReport`);

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
              // Hash the full serialized rows so any field change (even without
              // an updatedAt bump) invalidates the cache.
              p: crypto.createHash("sha1").update(JSON.stringify(serializedData)).digest("hex"),
            })
          )
          .digest("hex") +
        '"';

      // Honor If-None-Match (may contain multiple values); accept weak/strong variants.
      const inm = (req.headers["if-none-match"] || "").toString();
      const candidates = inm.split(",").map((s) => s.trim()).filter(Boolean);
      const strong = etag.replace(/^W\//, "");
      const matches = candidates.some(
        (c) => c === etag || c === strong || "W/" + c === etag || c === "*"
      );
      if (matches) {
        res.status(304).end();
        return;
      }

      if (payload.dataTimestamp) res.setHeader("Last-Modified", payload.dataTimestamp);
      res.setHeader("ETag", etag);
      res.json(payload);
    } catch (error) {
      console.error("Error fetching FinalReport:", error.message);
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

  // GET /api/final-report/stats — summary statistics.
  app.get("/api/final-report/stats", async (req, res) => {
    try {
      const totalCount = await prisma.finalReport.count();
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

      res.json({ success: true, data: stats, timestamp: new Date().toISOString() });
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

  // GET /api/final-report/dates — available earnings dates with counts.
  app.get("/api/final-report/dates", async (req, res) => {
    try {
      // reportDate is required (non-nullable) in the schema, so no `not: null`
      // filter is needed (and would be rejected by Prisma).
      const rows = await prisma.finalReport.findMany({
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

  // GET /api/final-report/date/:date — earnings for one date (YYYY-MM-DD).
  app.get("/api/final-report/date/:date", async (req, res) => {
    try {
      const dateStr = String(req.params.date || "");
      if (!/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        return res.status(400).json({ success: false, error: "Invalid date format. Use YYYY-MM-DD." });
      }

      const dayStart = new Date(`${dateStr}T00:00:00.000Z`);
      // Half-open interval [dayStart, nextDay) — safer than lte:23:59:59.999Z,
      // which would miss the final 0.001ms of the day.
      const nextDay = new Date(dayStart.getTime() + 24 * 60 * 60 * 1000);

      const data = await prisma.finalReport.findMany({
        where: { reportDate: { gte: dayStart, lt: nextDay } },
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

  // ---------------------------------------------------------------------------
  // Last good data endpoint (24h cache) — persisted to disk for restart resilience.
  // Registered BEFORE /api/final-report/:symbol so the static path is not shadowed
  // by the dynamic :symbol route.
  // ---------------------------------------------------------------------------
  const CACHE_FILE = path.resolve(__dirname, "..", "..", ".cache-last-good.json");
  let lastGoodData = null;
  let lastGoodDataTimestamp = null;
  let lastGoodRefreshPromise = null; // in-flight dedup (M9)
  const CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 hours

  // Load cache from disk on startup.
  try {
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
      fs.writeFileSync(CACHE_FILE, JSON.stringify({ data: lastGoodData, timestamp: lastGoodDataTimestamp }));
    } catch (e) {
      console.error("[cache] Failed to save cache to disk:", e.message);
    }
  }

  // Single shared refresh routine so concurrent requests don't race / issue
  // duplicate heavy queries.
  async function refreshLastGood() {
    const freshData = await prisma.finalReport.findMany({
      orderBy: { updatedAt: "desc" },
      take: 1000,
    });
    if (freshData && freshData.length > 0) {
      const serializedFreshData = freshData.map(serializeFinalReport);
      lastGoodData = serializedFreshData;
      lastGoodDataTimestamp = Date.now();
      saveCacheToDisk();
      return serializedFreshData;
    }
    return null;
  }

  app.get("/api/final-report/last-good", async (req, res) => {
    try {
      const now = Date.now();

      if (lastGoodData && lastGoodDataTimestamp && now - lastGoodDataTimestamp < CACHE_DURATION) {
        res.json({
          success: true,
          data: lastGoodData,
          cached: true,
          cachedAt: new Date(lastGoodDataTimestamp).toISOString(),
          age: Math.round((now - lastGoodDataTimestamp) / 60000), // minutes
        });
        return;
      }

      // Dedup concurrent refreshes: reuse an in-flight refresh if one exists.
      if (!lastGoodRefreshPromise) {
        lastGoodRefreshPromise = refreshLastGood().finally(() => {
          lastGoodRefreshPromise = null;
        });
      }
      const fresh = await lastGoodRefreshPromise;

      if (fresh) {
        res.json({
          success: true,
          data: lastGoodData,
          cached: false,
          cachedAt: new Date(lastGoodDataTimestamp).toISOString(),
          age: 0,
        });
      } else if (lastGoodData) {
        // No fresh data, return cached if available.
        res.json({
          success: true,
          data: lastGoodData,
          cached: true,
          cachedAt: new Date(lastGoodDataTimestamp).toISOString(),
          age: Math.round((now - lastGoodDataTimestamp) / 60000),
          warning: "Using cached data (no fresh data found)",
        });
      } else {
        res.status(503).json({
          success: false,
          error: "No data available",
          message: "No earnings data available",
        });
      }
    } catch (error) {
      console.error("Last good data endpoint error:", error);
      console.error("Error stack:", error.stack);

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

  // POST /api/final-report/refresh — disabled (use cron one-shot instead).
  app.post("/api/final-report/refresh", async (_req, res) => {
    res.status(501).json({
      success: false,
      error: "Refresh endpoint is disabled in production. Use cron one-shot instead.",
    });
  });

  // ---------------------------------------------------------------------------
  // Single-symbol endpoint.
  // NOTE: FinalReport's unique constraint is @@unique([symbol, reportDate]) —
  // `symbol` alone is NOT unique, so findUnique({ where: { symbol } }) would
  // throw. Use findFirst instead, and validate the input.
  // ---------------------------------------------------------------------------
  app.get("/api/final-report/:symbol", async (req, res) => {
    try {
      const symbol = String(req.params.symbol || "").toUpperCase().trim();
      if (!symbol || !/^[A-Z.\-]{1,10}$/.test(symbol)) {
        return res.status(400).json({ success: false, error: "Invalid symbol format" });
      }
      const data = await prisma.finalReport.findFirst({ where: { symbol } });
      if (!data) return res.status(404).json({ success: false, error: "Company not found" });
      const serialized = serializeFinalReport(data);
      res.json({ success: true, data: serialized, timestamp: new Date().toISOString() });
    } catch (error) {
      console.error("Error fetching company:", error.message);
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
}

module.exports = { registerFinalReportRoutes };
