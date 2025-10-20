module.exports = {
  apps: [
    {
      name: "earnings-cron",
      cwd: "./modules/cron",
      // Run directly with tsx (no build step). Alternatively, build to dist and use node.
      script: "node_modules/tsx/dist/cli.js",
      args: "src/cron-scheduler.ts",
      instances: 1,
      autorestart: true,
      max_memory_restart: "300M",
      watch: false,
      env_production: {
        NODE_ENV: "production",
        TZ: "America/New_York",
      },
    },
  ],
};
