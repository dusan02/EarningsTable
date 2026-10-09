// API smoke test: boots simple-server.js on an ephemeral port and checks the
// public endpoints documented in AGENTS.md. Exits non-zero on failure.
//
//   node scripts/smoke-test.js
//
// Requires the generated Prisma client (npx prisma generate) and DATABASE_URL.
// If DATABASE_URL is unset, it defaults to the local dev.db when present.
const { spawn } = require("child_process");
const path = require("path");
const fs = require("fs");

const PORT = 3100 + Math.floor(Math.random() * 400);
const BASE = `http://127.0.0.1:${PORT}`;
const DEV_DB = path.resolve(__dirname, "..", "modules", "database", "prisma", "dev.db");

const env = { ...process.env, PORT: String(PORT), NODE_ENV: "production" };
if (!env.DATABASE_URL && fs.existsSync(DEV_DB)) {
  env.DATABASE_URL = `file:${DEV_DB}`;
}

let passed = 0;
let failed = 0;

function check(name, ok, detail = "") {
  if (ok) {
    passed++;
    console.log(`  PASS ${name}`);
  } else {
    failed++;
    console.log(`  FAIL ${name} ${detail}`);
  }
}

async function get(pathname) {
  const res = await fetch(`${BASE}${pathname}`);
  let body = null;
  try {
    body = await res.json();
  } catch {
    /* not JSON */
  }
  return { status: res.status, body };
}

async function waitForServer(timeoutMs = 20000) {
  const deadline = Date.now() + timeoutMs;
  while (Date.now() < deadline) {
    try {
      const res = await fetch(`${BASE}/api/health`);
      if (res.ok) return true;
    } catch {
      /* not up yet */
    }
    await new Promise((r) => setTimeout(r, 250));
  }
  return false;
}

async function main() {
  console.log(`[smoke] starting server on :${PORT} (DATABASE_URL=${env.DATABASE_URL ? "set" : "UNSET"})`);
  const child = spawn(process.execPath, [path.resolve(__dirname, "..", "simple-server.js")], {
    env,
    stdio: ["ignore", "pipe", "pipe"],
  });
  const serverLog = [];
  child.stdout.on("data", (d) => serverLog.push(d.toString()));
  child.stderr.on("data", (d) => serverLog.push(d.toString()));

  try {
    const up = await waitForServer();
    check("server boots and /api/health responds", up);
    if (!up) {
      console.log(serverLog.slice(-30).join(""));
      return;
    }

    const health = await get("/api/health");
    check("GET /api/health → 200 + healthy", health.status === 200 && health.body?.status === "healthy");

    const cron = await get("/api/cron-status");
    check("GET /api/cron-status → 200 + success", cron.status === 200 && cron.body?.success === true);

    const dates = await get("/api/final-report/dates");
    check(
      "GET /api/final-report/dates → 200 + data array",
      dates.status === 200 && Array.isArray(dates.body?.data)
    );

    const byDate = await get("/api/final-report/date/2026-10-08");
    check(
      "GET /api/final-report/date/:date → 200 + data array",
      byDate.status === 200 && Array.isArray(byDate.body?.data)
    );

    const badDate = await get("/api/final-report/date/not-a-date");
    check("GET /api/final-report/date/not-a-date → 400", badDate.status === 400);

    const badSymbol = await get("/api/final-report/DROP TABLE");
    check("GET /api/final-report/<invalid symbol> → 400", badSymbol.status === 400);

    const spa = await fetch(`${BASE}/date/2026-10-08`);
    const spaText = await spa.text();
    check(
      "GET /date/2026-10-08 (SPA fallback) → 200 HTML",
      spa.status === 200 && spaText.includes("<div id=\"root\"")
    );

    const missing = await get("/api/does-not-exist");
    check("GET /api/does-not-exist → 404", missing.status === 404);
  } finally {
    child.kill("SIGTERM");
  }
}

main()
  .then(() => {
    console.log(`\n[smoke] ${passed} passed, ${failed} failed`);
    process.exit(failed ? 1 : 0);
  })
  .catch((e) => {
    console.error("[smoke] fatal:", e);
    process.exit(1);
  });
