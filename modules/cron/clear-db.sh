#!/bin/bash
# Script na jednorazové vymazanie dát z tabuliek
# Použitie: ./clear-db.sh

cd "$(dirname "$0")"

echo "🧹 Clearing all database tables..."
ALLOW_CLEAR=true node -e "
const { execSync } = require('child_process');
execSync('npx tsx -e \"import(\\'./src/core/DatabaseManager.js\\').then(async ({ db }) => { process.env.ALLOW_CLEAR = \\'true\\'; await db.clearAllTables(); await db.disconnect(); console.log(\\'✅ Done\\'); process.exit(0); }).catch(e => { console.error(e); process.exit(1); })\"', { stdio: 'inherit', env: { ...process.env, ALLOW_CLEAR: 'true' } });
"

