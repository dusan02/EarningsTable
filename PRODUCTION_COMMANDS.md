# 🔍 Production Verification Commands
# Quick commands to verify production is running correctly

## 1. Basic Health Checks

# Check PM2 processes
pm2 list

# Check PM2 logs (last 50 lines)
pm2 logs earnings-table --lines 50

# Check API health endpoint
curl -s https://www.earningstable.com/api/health | jq .

# Check final report endpoint
curl -s https://www.earningstable.com/api/final-report | jq '.success, .count'

# Check site.webmanifest
curl -s https://www.earningstable.com/site.webmanifest | jq .

## 2. Prisma & Database Checks

cd /var/www/earnings-table

# Check Prisma client location
ls -la modules/shared/node_modules/@prisma/client 2>/dev/null || echo "❌ Prisma client not found"
ls -la modules/shared/node_modules/.prisma/client 2>/dev/null || echo "❌ Prisma runtime not found"

# Verify root Prisma is removed
ls -la node_modules/.prisma 2>/dev/null && echo "⚠️  Root Prisma still exists" || echo "✅ Root Prisma removed"

# Check database
sqlite3 modules/database/prisma/prod.db "SELECT COUNT(*) FROM final_report;"

# Check database schema
sqlite3 modules/database/prisma/prod.db ".schema final_report"

## 3. PM2 Process Management

# Restart earnings-table
pm2 restart earnings-table

# Stop and start fresh
pm2 stop earnings-table
pm2 delete earnings-table
pm2 start simple-server.js --name earnings-table

# View real-time logs
pm2 logs earnings-table --lines 100

# Monitor processes
pm2 monit

## 4. File System Checks

# Check project directory
cd /var/www/earnings-table
ls -la

# Check for latest changes
git log --oneline -5

# Check if files are up to date
git status

# Pull latest changes
git pull origin main

## 5. Network & Port Checks

# Check if port 5555 is listening
netstat -tuln | grep 5555
# or
ss -tuln | grep 5555

# Check process using port 5555
lsof -i :5555

# Test local connection
curl -s http://localhost:5555/api/health

## 6. Error Checking

# Check for errors in logs
pm2 logs earnings-table --err --lines 50

# Check for Prisma errors specifically
pm2 logs earnings-table --lines 100 | grep -i "prisma\|error\|failed"

# Check for 500 errors
pm2 logs earnings-table --lines 200 | grep "500\|Internal Server Error"

## 7. Performance Checks

# Check memory usage
pm2 monit
# or
free -h

# Check CPU usage
top -bn1 | grep "earnings-table"

# Check disk space
df -h /var/www

## 8. Quick Test Script

# Run comprehensive check
cd /var/www/earnings-table
chmod +x check-production.sh
./check-production.sh

## 9. Manual API Tests

# Test final-report endpoint
curl -s https://www.earningstable.com/api/final-report | jq '.success, .count, .data[0]'

# Test final-report with symbol
curl -s https://www.earningstable.com/api/final-report/AAPL | jq .

# Test cron status
curl -s https://www.earningstable.com/api/cron-status | jq .

## 10. Prisma Client Verification

# Check Prisma client version
cd /var/www/earnings-table/modules/shared/node_modules/@prisma/client
cat package.json | grep version

# Check Prisma runtime files
ls -la /var/www/earnings-table/modules/shared/node_modules/.prisma/client/

# Verify schema.prisma in runtime
cat /var/www/earnings-table/modules/shared/node_modules/.prisma/client/schema.prisma | grep -A 20 "model FinalReport"

## 11. Database Verification

# Check database file
ls -lh /var/www/earnings-table/modules/database/prisma/prod.db

# Count records
sqlite3 /var/www/earnings-table/modules/database/prisma/prod.db "SELECT COUNT(*) FROM final_report;"

# Check latest records
sqlite3 /var/www/earnings-table/modules/database/prisma/prod.db "SELECT symbol, name, updatedAt FROM final_report ORDER BY updatedAt DESC LIMIT 5;"

## 12. Frontend Checks

# Check if index.html exists
ls -la /var/www/earnings-table/index.html

# Check site.webmanifest
curl -s https://www.earningstable.com/site.webmanifest

# Test frontend loading
curl -s https://www.earningstable.com/ | head -50

