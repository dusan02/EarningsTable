import cron from "node-cron";
import { setTimeout as sleep } from "timers/promises";
import { runFinnhubJob } from "./jobs/finnhub.js";
import { runPolygonJob } from "./jobs/polygon.js";
import { db } from "./core/DatabaseManager.js";

const TZ = "America/New_York";

// --- jednoduchý mutex, nech sa úlohy nebijú ---
let running = false;
async function withLock<T>(name: string, fn: () => Promise<T>): Promise<T | null> {
  if (running) {
    console.log(`⏸️  Skip ${name}: another task is running`);
    return null;
  }
  running = true;
  const started = Date.now();
  console.log(`▶️  ${name} started`);
  try {
    const res = await fn();
    console.log(`✅ ${name} finished in ${Date.now() - started}ms`);
    return res;
  } catch (e) {
    console.error(`❌ ${name} failed:`, e);
    return null;
  } finally {
    running = false;
  }
}

// --- clearAllData funkcia ---
async function clearAllData(): Promise<void> {
  console.log("🗑️  Clearing all database tables...");
  
  try {
    // Use the centralized DatabaseManager method
// await db.clearAllTables(); // disabled: run only in daily clear job
    console.log('✅ All tables cleared successfully');
    
  } catch (error) {
    console.error('❌ Error clearing database:', error);
    throw error;
  }
}

// --- 1) Denný reset dát o 07:00 NY ---
// Disabled: 03:00 NY clear in main scheduler is the single source of truth
// cron.schedule("0 7 * * *", () =>
//   withLock("DailyReset(07:00 NY)", async () => {
//     await clearAllData();
//     await sleep(500);
//   }),
//   { scheduled: true, timezone: TZ }
// );

// --- 2) Periodické spúšťanie úloh každé 4 minúty (striedavo) ---
// minúty: 0,4,8,12,… → striedanie: párne = Finnhub, nepárne = Polygon
cron.schedule("*/4 * * * *", () =>
  withLock("Cycle(*/4)", async () => {
    const minute = new Date().toLocaleString("en-US", { timeZone: TZ, minute: "2-digit" });
    const m = Number(minute);
    // Striedanie: m/4 párne = Finnhub, m/4 nepárne = Polygon
    const turn = Math.floor(m / 4) % 2;
    if (turn === 0) {
      // Finnhub → potom krátky odstup → Polygon (voliteľne)
      await runFinnhubJob();
      // (voliteľné) nechaj 30–60s odstup, ak máš rate-limit
      // await sleep(30000);
    } else {
      await runPolygonJob();
    }
  }),
  { scheduled: true, timezone: TZ }
);

// --- 3) Jednorazové manuálne spustenie cez flagy (--reset, --finnhub, --polygon) ---
(async function bootstrap() {
  const args = process.argv.slice(2);
  console.log(`🧭 Supervisor up. TZ=${TZ}. Flags: ${args.join(" ") || "(none)"}`);

  if (args.includes("--reset")) {
    await withLock("ManualReset", async () => clearAllData());
  }
  if (args.includes("--finnhub")) {
    await withLock("ManualFinnhub", async () => runFinnhubJob());
  }
  if (args.includes("--polygon")) {
    await withLock("ManualPolygon", async () => runPolygonJob());
  }

  console.log("🕒 Schedules active: */4min alternating jobs. Daily clear is managed at 03:00 NY in main scheduler.");
  // keep-alive
  await new Promise<void>(() => {});
})().catch((e) => {
  console.error("Bootstrap error:", e);
  process.exit(1);
});

// --- graceful shutdown ---
async function shutdown(sig: string) {
  console.log(`↩️  ${sig}: shutting down...`);
  try { 
    await db.disconnect(); 
  } catch (e) {
    console.error("Error during shutdown:", e);
  }
  process.exit(0);
}
process.on("SIGINT",  () => shutdown("SIGINT"));
process.on("SIGTERM", () => shutdown("SIGTERM"));
