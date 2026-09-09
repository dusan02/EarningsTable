// PM2 configuration.
//
// SECURITY: API tokens (FINNHUB_TOKEN, POLYGON_API_KEY) are NOT committed here.
// They must be provided via a .env file in each app's working directory
// (loaded by dotenv) or injected by your deployment/secret manager.
// Create /srv/EarningsTable/.env and /srv/EarningsTable/modules/cron/.env
// (chmod 600, owned by the service user) with the real values.
module.exports = {
  apps: [
    {
      name: "earnings-table",
      script: "simple-server.js",
      cwd: "./",
      autorestart: true,
      max_restarts: 10, // Bounded to avoid tight crash loops / log exhaustion
      restart_delay: 5000,
      max_memory_restart: "300M",
      min_uptime: "10s", // Process must run for 10s to be considered stable
      kill_timeout: 8000, // Time to wait for graceful shutdown
      listen_timeout: 10000, // Time to wait for process to start listening
      wait_ready: false, // Don't wait for ready event (we don't emit it)
      exp_backoff_restart_delay: 100, // Exponential backoff for restarts
      env: {
        NODE_ENV: "production",
        PORT: "5555",
        DATABASE_URL:
          "file:/srv/EarningsTable/modules/database/prisma/prod.db",
        CRON_TZ: "America/New_York",
        // FINNHUB_TOKEN and POLYGON_API_KEY: provide via .env (dotenv) or secret manager
      },
      env_production: {
        NODE_ENV: "production",
        PORT: "5555",
        DATABASE_URL:
          "file:/srv/EarningsTable/modules/database/prisma/prod.db",
        CRON_TZ: "America/New_York",
      },
    },
    {
      name: "earnings-cron",
      cwd: "./modules/cron",
      script: "node_modules/.bin/tsx",
      args: "src/main.ts start",
      interpreter: "none",
      watch: false,                 // Explicitly disable watch in production
      autorestart: true,
      min_uptime: "10s",            // Process must run for 10s to be considered stable
      max_restarts: 10,
      restart_delay: 5000,
      max_memory_restart: "300M",
      kill_timeout: 8000,
      listen_timeout: 10000,
      env: {
        NODE_ENV: "production",
        CRON_TZ: "America/New_York",
        DATABASE_URL:
          "file:/srv/EarningsTable/modules/database/prisma/prod.db",
        // FINNHUB_TOKEN and POLYGON_API_KEY: provide via .env (dotenv) or secret manager
      },
      env_production: {
        NODE_ENV: "production",
        CRON_TZ: "America/New_York",
        DATABASE_URL:
          "file:/srv/EarningsTable/modules/database/prisma/prod.db",
      },
    },
  ],
};
