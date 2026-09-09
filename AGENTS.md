# EarningsTable — agent notes

## Architecture

- **Frontend** (root `src/`): React 18 + TypeScript + CRA + Tailwind.
  - `src/App.tsx` — shell, header, theme, freshness indicator.
  - `src/Calendar.tsx` — month/week earnings calendar.
  - `src/EarningsTable.tsx` — desktop table + mobile cards.
  - `src/components/` — shared `CompanyLogo`, `CompanyCell`, `MetricCell`, `States`.
  - `src/hooks/` — `useTheme`, `useEarningsData`, `useCronStatus`.
  - `src/utils.ts` — date/size/format helpers (shared with components).
- **API** (`server/`): Express 5, split from `simple-server.js` (thin entry).
  - `server/index.js` — boot/assemble/listen/keepalive/shutdown.
  - `server/prisma.js` — Prisma client + runtime engine path.
  - `server/middleware.js` — CORS, compression, cache, 404, error handler.
  - `server/serialize.js` — `serializeFinalReport`.
  - `server/routes/static.js` — public, robots, sitemap, logos, favicon.
  - `server/routes/finalReport.js` — `/api/final-report*`.
  - `server/routes/cronStatus.js` — `/api/cron-status`, `/api/health`.
- **Dev servers**: `serve-dev.js`, `proxy-server.js` (Finnhub-backed dev routes
  via shared `lib/devProxyUtils.js`), `scripts/serve-build.js` (production build).
- **Cron** (`modules/cron/`): unified pipeline Finnhub → Polygon → logos → FinalReport.
  - Run once: `cd modules/cron && npx tsx src/main.ts start --once --date=YYYY-MM-DD --force`
- **Shared** (`modules/shared/`): config, types, utils, prisma client.
- **Database**: Prisma 6.17 + SQLite. Schema: `modules/database/prisma/schema.prisma`.
  - Local DB: `modules/database/prisma/dev.db` (gitignored, `*.db`).
  - After schema changes: `npx prisma db push --schema=modules/database/prisma/schema.prisma --accept-data-loss && npx prisma generate --schema=modules/database/prisma/schema.prisma`
    (stop the API server first — it holds a lock on the query engine dll).

## Required env vars

- `FINNHUB_TOKEN` — Finnhub earnings calendar (cron + dev proxy routes).
- `POLYGON_API_KEY` — prices, fundamentals, logos.
- `DATABASE_URL` — `file:<absolute>/modules/database/prisma/dev.db` (use an
  ABSOLUTE path; a relative path resolves differently per process cwd and
  creates stray nested SQLite files).

## Build & run (local, no deploy)

```bash
# 1. Build the frontend
npm run build

# 2. Start the API (port 3001) — set DATABASE_URL to the absolute dev.db path
$env:DATABASE_URL="file:D:/Projects/EarningsTable/modules/database/prisma/dev.db"
node simple-server.js

# 3. Serve the production build (port 3000) — proxies /api and /logos to :3001
node scripts/serve-build.js
```

Open http://localhost:3000.

## Smoke tests

- `GET /api/health` → `{ status: "healthy", ... }`
- `GET /api/cron-status` → `{ lastUpdate, isFresh, diffMin, recordsProcessed }`
- `GET /api/final-report/dates` → `{ data: [{ date, count }, ...] }`
- `GET /api/final-report/date/YYYY-MM-DD` → `{ data: FinalReportData[] }`

## Common gotchas

- **Relative `DATABASE_URL`** → different processes open different nested DB
  files. Always use an absolute path.
- **`/api/final-report/dates` 500** after `prisma generate`: the old
  `where: { reportDate: { not: null } }` filter is invalid once `reportDate`
  is non-nullable in the schema. Drop the filter (see `server/routes/finalReport.js`).
- **`prisma generate` EPERM on `query_engine-windows.dll.node`**: a running
  API server holds the lock. Stop all `node` processes first.
- **`marketCapDiff`** is an absolute dollar value, not a percentage. Render
  via `formatSignedMarketCap` (not `formatSignedPct`).
- **`change`** (price) is a percentage; render with 2 decimal places.

## Security

- Never commit `.env`, API keys, or `dev.db` (all gitignored).
- The Finnhub token was historically committed in `docs/archive/*` and
  `scripts/ssh/*`; it has been redacted from the working tree but remains in
  git history. **Rotate the token on the Finnhub dashboard** and avoid
  re-adding it.
