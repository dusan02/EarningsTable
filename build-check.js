#!/usr/bin/env node

// Build check script that verifies our code works
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import { dirname } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

console.log('🔍 Running build check...');

try {
  // Test if our main cron scheduler can be imported and run
  console.log('✓ Testing cron scheduler import...');
  
  // Change to cron directory and test
  process.chdir('modules/cron');
  
  // Test with --once flag to verify it works
  console.log('✓ Testing cron scheduler execution...');
  execSync('npx tsx src/cron-scheduler.ts --once', { 
    stdio: 'pipe',
    timeout: 30000 // 30 second timeout
  });
  
  console.log('✅ Build check passed - cron scheduler works correctly');
  process.exit(0);
  
} catch (error) {
  console.log('❌ Build check failed');
  console.log(`   Error: ${error.message}`);
  process.exit(1);
}
