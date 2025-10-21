module.exports = {
  apps: [
    {
      name: "earnings-cron",
      cwd: "./modules/cron",
      interpreter: "/usr/bin/tsx",
      script: "src/cron-scheduler.ts",
      exec_mode: "fork",
      instances: 1,
      autorestart: true,
      max_memory_restart: "300M",
      watch: false,
      time: true,
      env_production: {
        NODE_ENV: "production",
        TZ: "America/New_York",
        DATABASE_URL: "file:/var/www/earnings-table/modules/database/prisma/prod.db",
        REDIS_URL: "redis://127.0.0.1:6379",
        USE_REDIS_LOCK: "1",
        SKIP_RESET_CHECK: "0",
        FORCE_RUN: "0"
      },
    },
  ],
};
