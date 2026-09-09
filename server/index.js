// API server entry point: boot logging, middleware, routes, listen, and
// graceful shutdown. `simple-server.js` is now a thin wrapper around this.
try {
  require("dotenv").config();
} catch (_) {
  /* optional in production */
}

const express = require("express");

console.log("[BOOT/web] cwd=" + process.cwd());
// Log only whether DATABASE_URL is configured, never its value (may contain credentials/path).
console.log("[BOOT/web] DATABASE_URL configured:", Boolean(process.env.DATABASE_URL));
if (!process.env.DATABASE_URL) {
  console.warn("[BOOT/web] ⚠️ DATABASE_URL is not set!");
  console.warn("[BOOT/web] Create .env file or set DATABASE_URL environment variable");
  console.warn("[BOOT/web] Example: DATABASE_URL=file:D:/Projects/EarningsTable/modules/database/prisma/dev.db");
}

// Prisma client (loads generated client + sets runtime paths).
const { prisma } = require("./prisma");

const app = express();
const { applyMiddleware, applyErrorMiddleware } = require("./middleware");
applyMiddleware(app);

// Routes — static/SEO first, then API.
const { registerStaticRoutes } = require("./routes/static");
const { registerFinalReportRoutes } = require("./routes/finalReport");
const { registerCronStatusRoutes } = require("./routes/cronStatus");
registerStaticRoutes(app);
registerFinalReportRoutes(app);
registerCronStatusRoutes(app);

// SPA fallback — serve index.html for any non-API, non-static route.
// This makes /date/:date and other client-side routes work when Express
// serves the build directly (without nginx).
const path = require("path");
const fs = require("fs");
const BUILD_DIR = path.resolve(process.cwd(), "public");
app.get("*", (req, res, next) => {
  // Skip API and static asset paths.
  if (req.path.startsWith("/api") || req.path.startsWith("/logos")) return next();
  // Skip requests for files with extensions (e.g. .js, .css, .png).
  if (path.extname(req.path)) return next();
  const indexFile = path.join(BUILD_DIR, "index.html");
  if (fs.existsSync(indexFile)) {
    res.sendFile(indexFile);
  } else {
    next();
  }
});

// 404 + error middleware (after all routes).
applyErrorMiddleware(app);

const PORT = process.env.PORT || 3001;

// Start server
const server = app.listen(PORT, () => {
  console.log(`🚀 API Server running on port ${PORT}`);
  console.log(`API endpoints:`);
  console.log(`   GET  /api/final-report`);
  console.log(`   GET  /api/final-report/dates`);
  console.log(`   GET  /api/final-report/date/:date`);
  console.log(`   GET  /api/final-report/stats`);
  console.log(`   GET  /api/final-report/last-good`);
  console.log(`   GET  /api/final-report/:symbol`);
  console.log(`   GET  /api/cron-status (alias: /api/cron/status)`);
  console.log(`   GET  /api/health`);
  console.log(`🌐 API URL: http://localhost:${PORT}`);
});

// Keep-alive heartbeat (logs every ~5 min). The Express server already keeps
// the event loop alive; this is just an observability heartbeat.
console.log("✅ Keep-alive heartbeat initialized");
const keepAlive = setInterval(() => {
  const now = new Date();
  if (now.getMinutes() % 5 === 0 && now.getSeconds() < 10) {
    console.log(`💓 Keep-alive heartbeat: ${now.toISOString()}, uptime: ${process.uptime()}s`);
  }
}, 60000);

// Global async error handlers — prevent the process from crashing on an
// unhandled promise rejection or a thrown error in a callback. Log and continue.
process.on("unhandledRejection", (reason) => {
  console.error("[fatal] Unhandled promise rejection:", reason);
});
process.on("uncaughtException", (err) => {
  console.error("[fatal] Uncaught exception:", err);
  // Do not exit immediately: log and let the server keep serving. A future
  // SIGTERM/SIGINT will perform graceful shutdown. (Exiting here would cause
  // PM2/systemd restart storms for transient errors.)
});

process.on("beforeExit", (code) => console.error(`beforeExit: ${code}`));
process.on("exit", (code) => console.error(`exit: ${code}`));

// Graceful shutdown
let shuttingDown = false;
async function shutdown(signal) {
  if (shuttingDown) return;
  shuttingDown = true;
  const timestamp = new Date().toISOString();
  console.log(`\n${signal} received at ${timestamp}`);
  clearInterval(keepAlive);
  server.close(() => console.log("[shutdown] HTTP server closed"));
  try {
    await prisma.$disconnect();
    console.log("[shutdown] Prisma disconnected");
  } catch (e) {
    console.error("[shutdown] Prisma disconnect error:", e.message);
  }
  process.exit(0);
}
process.on("SIGINT", () => shutdown("SIGINT"));
process.on("SIGTERM", () => shutdown("SIGTERM"));

module.exports = { app, server, prisma };
