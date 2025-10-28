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
          "file:/var/www/earnings-table/modules/database/prisma/prod.db",
        FINNHUB_TOKEN: "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0",
        POLYGON_API_KEY: "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX",
        CRON_TZ: "America/New_York",
      },
      env_production: {
        NODE_ENV: "production",
        PORT: "5555",
        DATABASE_URL:
          "file:/var/www/earnings-table/modules/database/prisma/prod.db",
        FINNHUB_TOKEN: "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0",
        POLYGON_API_KEY: "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX",
        CRON_TZ: "America/New_York",
      },
    },
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
        CRON_TZ: "America/New_York",
        DATABASE_URL:
          "file:/var/www/earnings-table/modules/database/prisma/prod.db",
        FINNHUB_TOKEN: "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0",
        POLYGON_API_KEY: "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX",
      },
      env_production: {
        NODE_ENV: "production",
        CRON_TZ: "America/New_York",
        DATABASE_URL:
          "file:/var/www/earnings-table/modules/database/prisma/prod.db",
        FINNHUB_TOKEN: "d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0",
        POLYGON_API_KEY: "Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX",
      },
    },
  ],
};
