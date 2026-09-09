// Static + SEO routes: /public, robots.txt, sitemap.xml, /logos, favicon,
// site.webmanifest. Registered before the API routes so SEO files are served
// without hitting the API.
const path = require("path");
const fs = require("fs");
const express = require("express");

function registerStaticRoutes(app) {
  // Serve /public as static files (fallback if Nginx doesn't handle it).
  const PUBLIC_DIR = path.resolve(__dirname, "..", "public");
  app.use(express.static(PUBLIC_DIR, { fallthrough: true }));

  // robots.txt
  app.get("/robots.txt", (req, res) => {
    const possiblePaths = [
      path.resolve(__dirname, "..", "public", "robots.txt"),
      path.resolve(process.cwd(), "public", "robots.txt"),
    ];
    const robotsPath = possiblePaths.find((p) => fs.existsSync(p));
    if (!robotsPath) {
      console.error("[robots] File not found in any of:", possiblePaths);
      return res.status(404).json({ error: "robots.txt not found" });
    }
    res.setHeader("Content-Type", "text/plain");
    res.sendFile(robotsPath, (err) => {
      if (err && !res.headersSent) res.status(500).json({ error: "Error serving robots.txt" });
    });
  });

  // sitemap.xml
  app.get("/sitemap.xml", (req, res) => {
    const possiblePaths = [
      path.resolve(__dirname, "..", "public", "sitemap.xml"),
      path.resolve(process.cwd(), "public", "sitemap.xml"),
    ];
    const sitemapPath = possiblePaths.find((p) => fs.existsSync(p));
    if (!sitemapPath) {
      console.error("[sitemap] File not found in any of:", possiblePaths);
      return res.status(404).json({ error: "sitemap.xml not found" });
    }
    res.setHeader("Content-Type", "application/xml");
    res.sendFile(sitemapPath, (err) => {
      if (err && !res.headersSent) res.status(500).json({ error: "Error serving sitemap.xml" });
    });
  });

  // Logos — robust to working dir (production + dev paths).
  const LOGO_DIR = (() => {
    const possiblePaths = [
      "/var/www/earnings-table/modules/web/public/logos",
      "/srv/EarningsTable/modules/web/public/logos",
      path.resolve(__dirname, "..", "modules", "web", "public", "logos"),
      path.resolve(process.cwd(), "modules", "web", "public", "logos"),
    ];
    console.log("[logos] Checking paths for logo directory...");
    for (const logoPath of possiblePaths) {
      if (fs.existsSync(logoPath)) {
        console.log("[logos] Found logo directory:", logoPath);
        return logoPath;
      }
    }
    const fallback = possiblePaths[0];
    console.warn("[logos] ⚠️ Logo directory not found, using fallback:", fallback);
    return fallback;
  })();
  console.log("[logos] serving from:", LOGO_DIR);
  app.use("/logos", express.static(LOGO_DIR, { maxAge: "7d" }));

  // Favicon (.ico and .svg) with fallbacks.
  app.get(["/favicon.ico", "/favicon.svg"], (req, res) => {
    const candidates = [
      path.join(__dirname, "..", "favicon.svg"),
      path.resolve(process.cwd(), "modules", "web", "public", "logos", "favicon.svg"),
      path.join(__dirname, "..", "favicon.ico"),
    ];
    const candidate = candidates.find((p) => fs.existsSync(p));
    if (!candidate) return res.status(404).end();
    res.sendFile(candidate);
  });

  // site.webmanifest
  app.get("/site.webmanifest", (req, res) => {
    const manifestPath = path.resolve(__dirname, "..", "site.webmanifest");
    if (!fs.existsSync(manifestPath)) {
      console.error("[manifest] File not found at:", manifestPath);
      return res.status(404).json({ error: "Manifest not found" });
    }
    res.setHeader("Content-Type", "application/manifest+json");
    res.sendFile(manifestPath, (err) => {
      if (err && !res.headersSent) res.status(500).json({ error: "Error serving manifest" });
    });
  });
}

module.exports = { registerStaticRoutes };
