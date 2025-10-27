### Logos Troubleshooting Runbook (Production)

Purpose: Precise, copy-paste steps to recover when logos are not visible on the site. Includes root causes we hit and the exact commands that fixed them.

---

#### Symptoms we saw

- UI shows initials instead of logos.
- Files exist under `/srv/EarningsTable/modules/web/public/logos`, but API response has `logoUrl: null` for all items.
- Cron logs show logo step running, but DB isn’t updated.

---

#### Fast checks

```bash
# API health
curl -s https://www.earningstable.com/api/health

# Static serving works (check one webp and one svg)
curl -I https://www.earningstable.com/logos/AAPL.webp
curl -I https://www.earningstable.com/logos/SBUX.svg

# Files on disk
LOGO_DIR=/srv/EarningsTable/modules/web/public/logos
find "$LOGO_DIR" -type f | wc -l
ls -1 "$LOGO_DIR" | head

# DB status (SQLite)
cd /srv/EarningsTable
sqlite3 modules/database/prisma/dev.db "select count(*) from finnhub_data where logoUrl is not null;"
sqlite3 modules/database/prisma/dev.db "select count(*) from final_report  where logoUrl is not null;"

# Cron logs (logo step + prisma errors)
pm2 logs earnings-cron --lines 200 | egrep -n "Processing logos|🖼|@prisma/client|batchUpdateL?LogoInfo|p-limit|error|fail"

# API static mount path (should show the absolute logo dir at startup)
pm2 logs earnings-table --lines 120 | egrep -n "\[logos\] serving from|API Server running|PORT"
```

---

#### Root causes we encountered

1. Prisma client not generated in production

   - Symptom: `Error: @prisma/client did not initialize yet. Please run "prisma generate" ...` in cron logs.
   - Fix:

   ```bash
   cd /srv/EarningsTable
   export DATABASE_URL="file:/srv/EarningsTable/modules/database/prisma/dev.db"
   npx -y prisma generate --schema ./modules/database/prisma/schema.prisma
   pm2 restart earnings-cron --update-env
   ```

2. Logo DB write-back skipped due to missing method in older build

   - Symptom: `db.batchUpdateLLogoInfo is not a function` (typo/older build); logo files saved, but `logoUrl` not written to DB.
   - Code fixes (already committed):
     - `logoService.ts`: safe fallback to direct Prisma `updateMany` when `batchUpdateLogoInfo` is unavailable.
     - Correct import to live TS `DatabaseManager.ts`.
     - Safe defaults for `p-limit` (`batch=16`, `concurrency=6`) to avoid `concurrency NaN/0` errors.
     - `polygon-fast.ts`: always call `processLogosInBatches` before `generateFinalReport` with safe defaults.
   - Deploy: `git pull --ff-only && pm2 restart earnings-cron --update-env`.

3. `final_report.logoUrl` empty while `finnhub_data.logoUrl` is populated

   - Symptom: API shows no logos although files exist and `finnhub_data` has URLs.
   - Quick recovery (one-time backfill):

   ```bash
   cd /srv/EarningsTable
   sqlite3 modules/database/prisma/dev.db "
   UPDATE final_report
   SET logoUrl = (
     SELECT fd.logoUrl FROM finnhub_data fd
     WHERE fd.symbol = final_report.symbol AND fd.logoUrl IS NOT NULL
     LIMIT 1
   )
   WHERE EXISTS (
     SELECT 1 FROM finnhub_data fd
     WHERE fd.symbol = final_report.symbol AND fd.logoUrl IS NOT NULL
   );
   "
   ```

4. Mixed file formats and FE expecting only `.webp`

   - Symptom: Some symbols only have `.svg` (e.g., SBUX), FE tries `.webp` → 404.
   - FE fallback snippet (optional):

   ```tsx
   <img
     src={`/logos/${symbol}.webp`}
     onError={(e) => {
       e.currentTarget.onerror = null;
       e.currentTarget.src = `/logos/${symbol}.svg`;
     }}
     alt={name}
   />
   ```

5. Wrong static mount or path resolution for logos
   - Ensure API serves from repo-root path (robust to cwd):
   ```js
   // simple-server.js (already committed)
   const LOGO_DIR = require("path").resolve(
     process.cwd(),
     "modules",
     "web",
     "public",
     "logos"
   );
   console.log("[logos] serving from:", LOGO_DIR);
   app.use("/logos", express.static(LOGO_DIR));
   ```

---

#### Resolution we executed (chronological)

1. Stopped cron to prevent immediate overwrites.
   ```bash
   pm2 stop earnings-cron || true
   ```
2. Backfilled `final_report.logoUrl` from `finnhub_data.logoUrl` (instant FE recovery).
3. Regenerated Prisma client and restarted cron with env.
   ```bash
   cd /srv/EarningsTable
   export DATABASE_URL="file:/srv/EarningsTable/modules/database/prisma/dev.db"
   npx -y prisma generate --schema ./modules/database/prisma/schema.prisma
   pm2 restart earnings-cron --update-env
   ```
4. Pulled latest code (logo fallback + safe defaults + correct imports) and restarted processes.
   ```bash
   git pull --ff-only
   pm2 restart earnings-table --update-env
   pm2 restart earnings-cron   --update-env
   ```
5. Verified: DB counts > 0 in `final_report.logoUrl`, API returns `logoUrl`, files served 200.

---

#### One-time backfill from filesystem (if DB is empty but files exist)

Use when you have files in `modules/web/public/logos` but `finnhub_data`/`final_report` lack `logoUrl`.

```bash
cd /srv/EarningsTable
set -a; source .env 2>/dev/null || true; set +a

cat > /tmp/backfill_from_fs.ts <<'TS'
import fs from 'fs/promises';
import path from 'path';
import { prisma } from './modules/shared/src/prismaClient.js';
import { db } from './modules/cron/src/core/DatabaseManager.ts';

const LOGO_DIR = path.resolve(process.cwd(), 'modules', 'web', 'public', 'logos');

async function main() {
  const files = await fs.readdir(LOGO_DIR);
  const bySym = new Map<string, string>();
  for (const f of files) {
    const m = /^([A-Z0-9._-]+)\.(webp|svg)$/i.exec(f);
    if (!m) continue;
    const sym = m[1].toUpperCase(); const ext = m[2].toLowerCase();
    const curr = bySym.get(sym);
    if (!curr || curr.endsWith('.svg')) bySym.set(sym, `${sym}.${ext}`); // prefer webp
  }

  const entries = Array.from(bySym.entries()).map(([symbol, file]) => ({
    symbol, logoUrl: `/logos/${file}`, logoSource: 'fs-backfill',
  }));

  const chunk = 50;
  for (let i = 0; i < entries.length; i += chunk) {
    const part = entries.slice(i, i + chunk);
    await prisma.$transaction(
      part.map(u => prisma.finhubData.updateMany({
        where: { symbol: u.symbol },
        data: { logoUrl: u.logoUrl, logoSource: u.logoSource, logoFetchedAt: new Date() },
      }))
    );
  }

  await db.generateFinalReport();
  await db.disconnect();
}
main().catch(e => { console.error(e); process.exit(1); });
TS

npx tsx /tmp/backfill_from_fs.ts
```

---

#### Hardening checklist (prevent regression)

- Add to deploy: `npx -y prisma generate --schema ./modules/database/prisma/schema.prisma` before starting cron/API.
- Ensure `polygon-fast.ts` calls `processLogosInBatches(symbols, 16, 6)` before `generateFinalReport`.
- Keep safe defaults in `logoService.ts` for batch/concurrency and fallback DB write when `batchUpdateLogoInfo` is missing.
- Monitor: alert if `final_report.logoUrl` count drops to 0; grep cron logs for `@prisma/client did not initialize` and `batchUpdateL?LogoInfo`.
- FE: use `.svg` fallback when `.webp` 404s.
