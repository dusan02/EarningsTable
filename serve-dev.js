const path = require('path');
require('dotenv').config();
const express = require('express');
const { createProxyMiddleware } = require('http-proxy-middleware');
const { createProdCache, handleDatesRoute, handleDateRoute } = require('./lib/devProxyUtils');

const app = express();
const PORT = process.env.PORT || 3001;

const PROD = process.env.PROD_URL || 'https://earningstable.com';
// No hardcoded fallback token — require it from the environment.
const FINNHUB_KEY = process.env.FINNHUB_TOKEN;
if (!FINNHUB_KEY) {
  console.warn('[serve-dev] ⚠️ FINNHUB_TOKEN is not set; /api/final-report/dates and /date/:date will fail.');
}

// Cached production /api/final-report fetcher (60s TTL).
const fetchAllFromProd = createProdCache(PROD);

// Adapter: Express response -> the (status, obj) signature used by the shared handlers.
const expressSend = (res) => (status, obj) => res.status(status).json(obj);

// ── API routes ──

app.get('/api/final-report/dates', (req, res) => handleDatesRoute(FINNHUB_KEY, expressSend(res)));

app.get('/api/final-report/date/:date', (req, res) => {
  const dateStr = req.params.date;
  if (!/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
    return res.status(400).json({ success: false, error: 'Invalid date format. Use YYYY-MM-DD.' });
  }
  handleDateRoute(dateStr, FINNHUB_KEY, fetchAllFromProd, expressSend(res));
});

// Serve static React build
app.use(express.static(path.join(__dirname, 'build')));

// SPA fallback — serve index.html for any non-API, non-static route.
// Guard sendFile with headersSent so a double-write can't crash (C10).
app.use((req, res, next) => {
  if (req.path.startsWith('/api')) {
    return next();
  }
  res.sendFile(path.join(__dirname, 'build', 'index.html'), (err) => {
    if (err && !res.headersSent) {
      console.error('[serve-dev] SPA fallback error:', err.message);
      res.status(500).json({ success: false, error: 'Build not found. Run `npm run build`.' });
    }
  });
});

// Proxy all other API routes to production (after static + SPA fallback attempts).
// Add onError so proxy failures return 502 instead of crashing (M11).
app.use('/api', createProxyMiddleware({
  target: PROD,
  changeOrigin: true,
  secure: false,
  onError(err, req, res) {
    console.error('[serve-dev] proxy error:', err.message);
    if (res && !res.headersSent) {
      res.status(502).json({ success: false, error: 'Bad gateway' });
    } else if (res && res.writable) {
      try { res.end(); } catch (_) { /* ignore */ }
    }
  },
}));

app.listen(PORT, () => {
  console.log(`Dev server running at http://localhost:${PORT}`);
  console.log(`  Static React app from /build`);
  console.log(`  /api/final-report/dates — Finnhub ±3 days`);
  console.log(`  /api/final-report/date/:date — Finnhub + Polygon enrichment`);
  console.log(`  All other /api/* — proxy to ${PROD}`);
});
