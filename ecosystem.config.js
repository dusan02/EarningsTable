module.exports = {
  apps: [
    {
      name: "earnings-cron",
      cwd: "./modules/cron",
      script: "src/main.ts",
      interpreter: "tsx",
      args: "start",
      autorestart: true,
      max_restarts: 10,
      restart_delay: 5000,
      max_memory_restart: "300M",
      env: {
        NODE_ENV: "production",
        CRON_TZ: "America/New_York"
      }
    }
  ]
}
