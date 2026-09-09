// Express middleware registration for the API server.
// CORS and compression are registered BEFORE routes so all responses get the
// headers. The request logger only logs non-2xx (errors) to keep logs quiet.
const express = require("express");
const cors = require("cors");
const compression = require("compression");

function applyMiddleware(app) {
  // Disable Express default ETag generation; routes set stable custom ETags.
  app.set("etag", false);

  // CORS — restrict to configured origins in production (comma-separated env),
  // permissive in dev. Same-origin / no-origin (curl) always allowed.
  const ALLOWED_ORIGINS = process.env.CORS_ALLOWED_ORIGINS
    ? process.env.CORS_ALLOWED_ORIGINS.split(",").map((s) => s.trim()).filter(Boolean)
    : null;
  app.use(
    cors({
      origin(origin, cb) {
        if (!origin || !ALLOWED_ORIGINS || ALLOWED_ORIGINS.includes(origin)) {
          return cb(null, true);
        }
        return cb(null, false);
      },
      methods: ["GET", "POST", "OPTIONS"],
      credentials: false,
    })
  );

  // Compression before any response-writing handlers so static + SEO routes
  // are compressed too.
  app.use(compression());

  // Request logger — only log non-200 responses (errors).
  app.use((req, res, next) => {
    res.on("finish", () => {
      if (res.statusCode >= 400) {
        console.error(`[${res.statusCode}] ${req.method} ${req.path}`);
      }
    });
    next();
  });

  // Disable caching for all API responses. Use `no-cache, must-revalidate`
  // (not `no-store`) so conditional GETs with our custom ETag can still
  // return 304 and save bandwidth.
  app.use("/api", (_req, res, next) => {
    res.setHeader("Cache-Control", "no-cache, must-revalidate");
    res.setHeader("Pragma", "no-cache");
    res.setHeader("Expires", "0");
    next();
  });

  app.use(express.json());
  // X-Robots-Tag for all responses.
  app.use((req, res, next) => {
    res.setHeader("X-Robots-Tag", "index, follow");
    next();
  });
}

// 404 + error middleware (must be registered AFTER all routes).
function applyErrorMiddleware(app) {
  app.use((req, res) => {
    res.status(404).json({ success: false, error: "Not found" });
  });
  // eslint-disable-next-line no-unused-vars
  app.use((err, req, res, _next) => {
    console.error("[express] Unhandled route error:", err);
    if (res.headersSent) return; // defer to Express default handler
    res.status(500).json({
      success: false,
      error: "Internal server error",
      message:
        process.env.NODE_ENV === "production" ? null : err?.message || String(err),
    });
  });
}

// `express` is required at the top of this module.

module.exports = { applyMiddleware, applyErrorMiddleware };
