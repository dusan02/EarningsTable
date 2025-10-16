import cron from 'node-cron';
import { CONFIG } from './config.js';
import { main as runOnce } from './run-once.js';

// Spustíme jeden fetch pri štarte (môžeš odstrániť ak nechceš)
console.log('🚀 Starting earnings cron app...');
console.log('→ Running initial fetch...');
await runOnce();

// Nastavíme cron job
cron.schedule(CONFIG.CRON_EXPR, async () => {
  console.log('⏰ Cron tick → running fetch...');
  try {
    await runOnce();
  } catch (error) {
    console.error('✗ Cron job failed:', error);
  }
}, { 
  timezone: CONFIG.CRON_TZ,
  scheduled: true 
});

console.log(`✓ Cron scheduled at "${CONFIG.CRON_EXPR}" in timezone ${CONFIG.CRON_TZ}`);
console.log('→ App is running. Press Ctrl+C to stop.');

// Graceful shutdown
process.on('SIGINT', () => {
  console.log('\n→ Shutting down gracefully...');
  process.exit(0);
});

process.on('SIGTERM', () => {
  console.log('\n→ Shutting down gracefully...');
  process.exit(0);
});
