module.exports = {
  apps: [
    {
      name: "earnings-table",
      script: "simple-server.js",
      cwd: "./",
      autorestart: true,
      max_restarts: 10,
      restart_delay: 5000,
      max_memory_restart: "300M",
      env: {
        NODE_ENV: "production",
        PORT: "5555",
        DATABASE_URL:
          "file:/srv/EarningsTable/modules/database/prisma/prod.db",
        FINNHUB_TOKEN: "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0",
        POLYGON_API_KEY: "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX",
        CRON_TZ: "America/New_York",
      },
      env_production: {
        NODE_ENV: "production",
        PORT: "5555",
        DATABASE_URL:
          "file:/srv/EarningsTable/modules/database/prisma/prod.db",
        FINNHUB_TOKEN: "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0",
        POLYGON_API_KEY: "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX",
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
        FINNHUB_TOKEN: "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0",
        POLYGON_API_KEY: "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX",
      },
      env_production: {
        NODE_ENV: "production",
        CRON_TZ: "America/New_York",
        DATABASE_URL:
          "file:/srv/EarningsTable/modules/database/prisma/prod.db",
        FINNHUB_TOKEN: "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0",
        POLYGON_API_KEY: "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX",
      },
    },
  ],
};
