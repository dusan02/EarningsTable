#!/usr/bin/env node

// Simple build check script that uses tsx to verify our TypeScript files
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

console.log('🔍 Checking TypeScript files with tsx...');

const filesToCheck = [
  'src/cron-scheduler.ts',
  'src/core/DatabaseManager.ts',
  'src/core/logoService.ts',
  'src/jobs/finnhub.ts',
  'src/jobs/polygon.ts'
];

let hasErrors = false;

for (const file of filesToCheck) {
  try {
    console.log(`✓ Checking ${file}...`);
    execSync(`npx tsx --check ${file}`, { 
      cwd: __dirname,
      stdio: 'pipe'
    });
    console.log(`  ✅ ${file} - OK`);
  } catch (error) {
    console.log(`  ❌ ${file} - ERROR`);
    console.log(`     ${error.message}`);
    hasErrors = true;
  }
}

if (hasErrors) {
  console.log('\n❌ Build check failed - some files have errors');
  process.exit(1);
} else {
  console.log('\n✅ Build check passed - all files are valid');
  process.exit(0);
}
