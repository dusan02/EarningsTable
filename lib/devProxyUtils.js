// Shared utilities for the local dev proxy servers (proxy-server.js + serve-dev.js).
// Extracted to eliminate duplication of fetchJson / computeSize / Finnhub earnings
// parsing / production-data caching between the two dev entry points.

const https = require('https');

/**
 * Fetch JSON from a URL with request error + timeout handling.
 * Rejects on HTTP request errors, timeouts, and invalid JSON bodies.
 */
function fetchJson(url, timeoutMs = 8000) {
  return new Promise((resolve, reject) => {
    const req = https.get(url, (res) => {
      let body = '';
      res.on('data', (chunk) => { body += chunk; });
      res.on('end', () => {
        try { resolve(JSON.parse(body)); }
        catch (e) { reject(new Error(`Invalid JSON from ${url.split('?')[0]}: ${e.message}`)); }
      });
    });
    req.on('error', reject);
    req.setTimeout(timeoutMs, () => {
      req.destroy(new Error(`Request timed out after ${timeoutMs}ms`));
    });
  });
}

/**
 * Compute a market-size bucket from a market cap value.
 * Thresholds MUST stay in sync with the frontend (src/utils.ts computeSizeFromMarketCap).
 *   mega  >= 100B
 *   large >= 10B
 *   mid   >= 2B
 *   small <  2B
 */
function computeSize(marketCap) {
  if (marketCap === null || marketCap === undefined || marketCap === '') return null;
  const num = Number(marketCap);
  if (!isFinite(num)) return null;
  const abs = Math.abs(num);
  if (abs >= 100e9) return 'mega';
  if (abs >= 10e9) return 'large';
  if (abs >= 2e9) return 'mid';
  return 'small';
}

/**
 * Fetch Finnhub earnings calendar entries for a single date and normalize them
 * into the row shape consumed by the frontend. Requires FINNHUB_TOKEN.
 */
function fetchFinnhubEarnings(dateStr, finnhubKey) {
  const url = `https://finnhub.io/api/v1/calendar/earnings?from=${dateStr}&to=${dateStr}&token=${finnhubKey}`;
  return fetchJson(url).then(data => {
    const rows = data.earningsCalendar || [];
    return rows.map(e => ({
      symbol: (e.symbol || '').trim().toUpperCase(),
      name: e.company || e.symbol || null,
      reportDate: e.date ? `${e.date}T00:00:00.000Z` : null,
      hour: e.hour || null,
      epsActual: e.epsActual ?? null,
      epsEst: e.epsEstimate ?? null,
      revActual: e.revenueActual ?? null,
      revEst: e.revenueEstimate ?? null,
      quarter: e.quarter ?? null,
      year: e.year ?? null,
    })).filter(r => r.symbol);
  });
}

/**
 * Create a cached producer-data fetcher bound to a production base URL.
 * Caches the /api/final-report response for `ttlMs` (default 60s) so concurrent
 * date requests don't all hammer production.
 */
function createProdCache(prodBaseUrl, ttlMs = 60000) {
  let cachedData = null;
  let cachedAt = 0;
  return function fetchAllFromProd() {
    const now = Date.now();
    if (cachedData && (now - cachedAt) < ttlMs) {
      return Promise.resolve(cachedData);
    }
    return fetchJson(`${prodBaseUrl}/api/final-report`).then(json => {
      cachedData = json;
      cachedAt = now;
      return json;
    });
  };
}

// ---------------------------------------------------------------------------
// Shared route handlers for the dev servers.
// Both proxy-server.js and serve-dev.js expose the same two API routes
// (/api/final-report/dates and /api/final-report/date/:date). The handler
// bodies used to be copy-pasted, which caused a behavioural divergence in the
// `size` field priority. They now live here so both servers stay in sync.
// ---------------------------------------------------------------------------

/**
 * GET /api/final-report/dates — Finnhub earnings calendar counts for ±3 days
 * around today. `send` is a function(status, obj) so this is independent of
 * whether the host uses Express (`res.json`) or raw `http` (`sendJson`).
 */
async function handleDatesRoute(finnhubKey, send) {
  try {
    const now = new Date();
    const from = new Date(now);
    from.setDate(now.getDate() - 3);
    const to = new Date(now);
    to.setDate(now.getDate() + 3);
    const fromStr = from.toISOString().split('T')[0];
    const toStr = to.toISOString().split('T')[0];

    const data = await fetchJson(
      `https://finnhub.io/api/v1/calendar/earnings?from=${fromStr}&to=${toStr}&token=${finnhubKey}`
    );
    const rows = data.earningsCalendar || [];

    const dateMap = {};
    for (const r of rows) {
      if (r.date) dateMap[r.date] = (dateMap[r.date] || 0) + 1;
    }
    const dates = Object.entries(dateMap)
      .map(([date, count]) => ({ date, count }))
      .sort((a, b) => a.date.localeCompare(b.date));

    send(200, { success: true, data: dates, timestamp: new Date().toISOString() });
  } catch (e) {
    send(500, { success: false, error: 'Failed to fetch dates: ' + e.message });
  }
}

/**
 * GET /api/final-report/date/:date — Finnhub earnings for one date, enriched
 * with producer (Polygon) data fetched via `fetchAllFromProd()`.
 *
 * `size` priority: computed-from-marketCap first (matches the frontend's
 * getSizeBadge, which overrides a stale API size when marketCap disagrees),
 * then the API-provided size, then null.
 */
async function handleDateRoute(dateStr, finnhubKey, fetchAllFromProd, send) {
  try {
    const finnhubEntries = await fetchFinnhubEarnings(dateStr, finnhubKey);
    const prodJson = await fetchAllFromProd();
    const prodData = prodJson.data || [];
    const prodMap = new Map(prodData.map(item => [item.symbol, item]));

    const merged = finnhubEntries.map(fin => {
      const prod = prodMap.get(fin.symbol);
      return {
        symbol: fin.symbol,
        name: prod?.name || fin.name || null,
        size: computeSize(prod?.marketCap) || prod?.size || null,
        marketCap: prod?.marketCap ?? null,
        marketCapDiff: prod?.marketCapDiff ?? null,
        price: prod?.price ?? null,
        change: prod?.change ?? null,
        epsActual: fin.epsActual ?? prod?.epsActual ?? null,
        epsEst: fin.epsEst ?? prod?.epsEst ?? null,
        epsSurp: (fin.epsActual != null && fin.epsEst != null && fin.epsEst !== 0)
          ? Math.round(((fin.epsActual - fin.epsEst) / Math.abs(fin.epsEst)) * 10000) / 100
          : prod?.epsSurp ?? null,
        revActual: fin.revActual ?? prod?.revActual ?? null,
        revEst: fin.revEst ?? prod?.revEst ?? null,
        revSurp: prod?.revSurp ?? null,
        logoUrl: prod?.logoUrl ?? null,
        logoSource: prod?.logoSource ?? null,
        logoFetchedAt: prod?.logoFetchedAt ?? null,
        reportDate: fin.reportDate,
        snapshotDate: prod?.snapshotDate ?? null,
        createdAt: prod?.createdAt ?? null,
        updatedAt: prod?.updatedAt ?? null,
      };
    });

    send(200, {
      success: true,
      data: merged,
      count: merged.length,
      date: dateStr,
      source: 'finnhub+polygon',
      timestamp: new Date().toISOString(),
    });
  } catch (e) {
    send(500, { success: false, error: 'Failed to fetch data: ' + e.message });
  }
}

module.exports = {
  fetchJson,
  computeSize,
  fetchFinnhubEarnings,
  createProdCache,
  handleDatesRoute,
  handleDateRoute,
};
