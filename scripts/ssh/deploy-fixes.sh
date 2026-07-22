#!/bin/bash

# 🚀 Deployment script for critical fixes
# This script deploys the DateTime and database timeout fixes

echo "🚀 Starting deployment of critical fixes..."

# 1. Build the project
echo "📦 Building project..."
cd modules/cron
npm run build

# 2. Run database cleanup
echo "🧹 Running database cleanup..."
npx tsx src/cleanup-database.ts

# 3. Restart PM2 processes
echo "🔄 Restarting PM2 processes..."
pm2 restart earnings-cron
pm2 restart earnings-table

# 4. Wait for processes to start
echo "⏳ Waiting for processes to start..."
sleep 10

# 5. Check PM2 status
echo "📊 Checking PM2 status..."
pm2 status

# 6. Run smoke test
echo "🧪 Running smoke test..."
npx tsx src/smoke-test.ts

# 7. Check logs
echo "📋 Checking recent logs..."
pm2 logs --lines 20

echo "✅ Deployment completed!"
echo ""
echo "🎯 Next steps:"
echo "1. Monitor PM2 logs: pm2 logs"
echo "2. Check web app: curl -s https://www.earningstable.com/api/final-report"
echo "3. Verify no more DateTime errors in logs"
echo ""
echo "🔍 If issues persist:"
echo "- Check PM2 status: pm2 status"
echo "- Check logs: pm2 logs --lines 50"
echo "- Run smoke test: npx tsx modules/cron/src/smoke-test.ts"
