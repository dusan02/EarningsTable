// Cron status + health endpoints.
const { prisma } = require("../prisma");

// Build a NY-time ISO string without parsing a locale-formatted string back
// into a Date (which is not guaranteed to be parseable across Node/ICU versions).
function nyNowISO() {
  const parts = new Intl.DateTimeFormat("en-CA", {
    timeZone: "America/New_York",
    year: "numeric", month: "2-digit", day: "2-digit",
    hour: "2-digit", minute: "2-digit", second: "2-digit",
    hour12: false,
  }).formatToParts(new Date());
  const get = (t) => parts.find((p) => p.type === t)?.value || "00";
  // hour12:false may yield "24" for midnight in some environments; normalize.
  let h = get("hour");
  if (h === "24") h = "00";
  return `${get("year")}-${get("month")}-${get("day")}T${h}:${get("minute")}:${get("second")}Z`;
}

async function handleCronStatus(_req, res) {
  try {
    let lastUpdate = null;
    let recordsProcessed = null;

    try {
      // Race Prisma queries against a timeout. Attach .catch so a late rejection
      // after the timeout wins is not unhandled.
      const latestReport = await Promise.race([
        prisma.finalReport
          .findFirst({ orderBy: { updatedAt: "desc" }, select: { updatedAt: true } })
          .catch((e) => { console.error("[cron-status] findFirst failed:", e.message); return null; }),
        new Promise((_, reject) => setTimeout(() => reject(new Error("Timeout")), 3000)),
      ]);

      if (latestReport?.updatedAt) {
        lastUpdate = latestReport.updatedAt;
        console.log("[cron-status] Got lastUpdate from Prisma:", lastUpdate.toISOString());
      } else {
        console.log("[cron-status] Prisma query succeeded but no updatedAt found");
      }

      recordsProcessed = await Promise.race([
        prisma.finalReport.count().catch((e) => { console.error("[cron-status] count failed:", e.message); return null; }),
        new Promise((_, reject) => setTimeout(() => reject(new Error("Timeout")), 2000)),
      ]).catch(() => null);
    } catch (reportError) {
      console.error("[cron-status] FinalReport query failed:", reportError.message);
      // Raw-SQL fallback removed: a second sqlite3 handle on the Prisma-managed
      // file risks SQLITE_BUSY and unhandled 'error' events that can crash the
      // process. Rely on Prisma only.
    }

    const nowMs = Date.now();
    const iso = nyNowISO();

    if (lastUpdate) {
      const lastRunMs = new Date(lastUpdate).getTime();
      const diffMin = Math.floor((nowMs - lastRunMs) / 60000);
      return res.json({
        success: true,
        nyNowISO: iso,
        lastUpdate: new Date(lastUpdate).toISOString(), // Frontend expects 'lastUpdate'
        lastRunAt: new Date(lastUpdate).toISOString(),
        diffMin,
        // Only consider fresh for non-future timestamps (negative diffMin = clock skew).
        isFresh: diffMin >= 0 && diffMin < 10,
        status: "success",
        recordsProcessed: recordsProcessed,
        error: null,
      });
    }

    // No status found — return current time as fallback.
    return res.json({
      success: true,
      nyNowISO: iso,
      lastUpdate: iso, // Frontend expects 'lastUpdate'
      lastRunAt: null,
      diffMin: null,
      isFresh: false,
      status: "unknown",
      recordsProcessed: null,
      error: null,
    });
  } catch (e) {
    console.error("[cron-status] Error:", e);
    const iso = nyNowISO();
    return res.status(500).json({
      success: false,
      nyNowISO: iso,
      lastUpdate: iso,
      lastRunAt: null,
      diffMin: null,
      isFresh: false,
      status: "error",
      recordsProcessed: null,
      error: e.message,
    });
  }
}

function registerCronStatusRoutes(app) {
  app.get("/api/cron/status", handleCronStatus);
  // Backward-compatible alias for frontend calling /api/cron-status.
  app.get("/api/cron-status", handleCronStatus);

  // Health check.
  app.get("/api/health", (_req, res) => {
    res.json({
      status: "healthy",
      timestamp: new Date().toISOString(),
      uptime: process.uptime(),
    });
  });
}

module.exports = { registerCronStatusRoutes, nyNowISO };
