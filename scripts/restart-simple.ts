import { execa } from "execa";

async function run(cmd: string, args: string[] = []) {
  try {
    await execa(cmd, args, { stdio: "inherit" });
  } catch (error) {
    console.warn(`⚠️ Command failed: ${cmd} ${args.join(' ')} - ${error.message}`);
  }
}

async function main() {
  console.log("🔄 Simple Restart - Stopping services, clearing data, restarting...");
  
  // 1) Stopni iba tvoje procesy
  console.log("🛑 Stopping services...");
  await run("pm2", ["stop", "earnings-web"]);
  await run("pm2", ["stop", "earnings-cron"]);
  
  // 2) Soft clear dát (centralizovaný skript v root)
  console.log("🗑️ Clearing database...");
  await run("node", ["clear-all-data.js"]);
  
  // 3) Štart
  console.log("🚀 Starting services...");
  await run("pm2", ["start", "dist/web/server.js", "--name", "earnings-web", "--", "--port", "3001"]);
  await run("pm2", ["start", "dist/cron/main.js", "--name", "earnings-cron"]);
  
  console.log("✅ Restart complete.");
}

main()
  .catch((e) => {
    console.error("❌ Restart failed:", e);
    process.exit(1);
  });
