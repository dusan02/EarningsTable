const http = require('http');
const path = require('path');
require('dotenv').config();
const { createProxyMiddleware } = require('http-proxy-middleware');
const { createProdCache, handleDatesRoute, handleDateRoute } = require('./lib/devProxyUtils');

const PROD = process.env.PROD_URL || 'https://earningstable.com';
// No hardcoded fallback token — require it from the environment.
const FINNHUB_KEY = process.env.FINNHUB_TOKEN;
if (!FINNHUB_KEY) {
  console.warn('[proxy] ⚠️ FINNHUB_TOKEN is not set; /api/final-report/dates and /date/:date will fail.');
}

// Proxy middleware with explicit error handling so a proxy error returns a
// JSON 502 instead of crashing the process or hanging the socket.
function makeProxy() {
  return createProxyMiddleware({
    target: PROD,
    changeOrigin: true,
    secure: false,
    onError(err, req, res) {
      console.error('[proxy] upstream error:', err.message);
      if (res && !res.headersSent) {
        res.writeHead(502, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: false, error: 'Bad gateway' }));
      } else if (res && res.writable) {
        try { res.end(); } catch (_) { /* ignore */ }
      }
    },
  });
}
const apiProxy = makeProxy();
const logoProxy = makeProxy();

// Cached production /api/final-report fetcher (60s TTL).
const fetchAllFromProd = createProdCache(PROD);

// Helper: strip query string for route matching (H14).
function pathnameOf(u) {
  const q = u.indexOf('?');
  return q === -1 ? u : u.slice(0, q);
}

function sendJson(res, status, obj) {
  if (res.headersSent) { try { res.end(); } catch (_) {} return; }
  res.writeHead(status, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify(obj));
}

const server = http.createServer(async (req, res) => {
  // Wrap the whole handler so an unexpected throw becomes a 500, not an
  // unhandled rejection that crashes the process (C8).
  try {
    const pathname = pathnameOf(req.url);

    // Intercept /api/final-report/dates — fetch from Finnhub for current week ±3 days
    if (pathname === '/api/final-report/dates') {
      await handleDatesRoute(FINNHUB_KEY, (status, obj) => sendJson(res, status, obj));
      return;
    }

    // Intercept /api/final-report/date/:date — fetch from Finnhub + enrich with production data
    const dateMatch = pathname.match(/^\/api\/final-report\/date\/(\d{4}-\d{2}-\d{2})$/);
    if (dateMatch) {
      const dateStr = dateMatch[1];
      await handleDateRoute(dateStr, FINNHUB_KEY, fetchAllFromProd, (status, obj) => sendJson(res, status, obj));
      return;
    }

    // Pass through everything else. Provide a `next` that turns anything not
    // handled by the proxy into a 404, so the middleware never throws due to
    // a missing next (C7).
    const next = () => sendJson(res, 404, { success: false, error: 'Not found' });

    if (pathname.startsWith('/api')) {
      apiProxy(req, res, next);
    } else if (pathname.startsWith('/logos')) {
      logoProxy(req, res, next);
    } else {
      sendJson(res, 404, { success: false, error: 'Not found' });
    }
  } catch (err) {
    console.error('[proxy] Unhandled handler error:', err);
    sendJson(res, 500, { success: false, error: 'Internal proxy error' });
  }
});

// Bound idle sockets so abandoned connections don't pile up (L8).
server.setTimeout(30000);
server.on('clientError', (err, socket) => {
  console.error('[proxy] clientError:', err.message);
  if (socket.writable) {
    socket.end('HTTP/1.1 400 Bad Request\r\n\r\n');
  } else {
    socket.destroy();
  }
});

const PORT = process.env.PORT || 3001;
server.listen(PORT, () => {
  console.log(`Proxy server running on http://localhost:${PORT} -> ${PROD}`);
  console.log(`  /api/final-report/dates — Finnhub ±3 days`);
  console.log(`  /api/final-report/date/:date — Finnhub + Polygon enrichment`);
});
