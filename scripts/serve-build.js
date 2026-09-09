// Local production-like server: serves the CRA build/ folder and proxies /api
// to the API server (simple-server.js on :3001). Use after `npm run build`:
//   node scripts/serve-build.js          # API on :3001, build on :3000
// Start the API server separately first.
const path = require('path');
const express = require('express');
const { createProxyMiddleware } = require('http-proxy-middleware');

const API_TARGET = process.env.API_TARGET || 'http://localhost:3001';
const PORT = process.env.PORT || 3000;
const BUILD_DIR = path.resolve(__dirname, '..', 'build');

const app = express();

// Proxy API + logos to the API server so the SPA's relative URLs resolve.
const proxyMiddleware = createProxyMiddleware({
  target: API_TARGET,
  changeOrigin: true,
  secure: false,
  onError(err, _req, res) {
    console.error('[serve-build] proxy error:', err.message);
    if (res && !res.headersSent) res.status(502).json({ success: false, error: 'Bad gateway' });
  },
});
app.use(['/api', '/logos'], proxyMiddleware);
// SEO routes — proxy to API for dynamic sitemap/robots.
app.get('/sitemap.xml', proxyMiddleware);
app.get('/robots.txt', proxyMiddleware);

// Static assets from the build.
app.use(express.static(BUILD_DIR));

// SPA fallback — serve index.html for any non-API, non-static route.
app.use((req, res, next) => {
  if (req.path.startsWith('/api') || req.path.startsWith('/logos')) return next();
  res.sendFile(path.join(BUILD_DIR, 'index.html'), (err) => {
    if (err && !res.headersSent) {
      res.status(500).json({ success: false, error: 'Build not found. Run `npm run build`.' });
    }
  });
});

app.listen(PORT, () => {
  console.log(`[serve-build] Production build served at http://localhost:${PORT}`);
  console.log(`[serve-build] /api and /logos proxied to ${API_TARGET}`);
});
