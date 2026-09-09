#!/bin/bash
# Quick redeploy with static.js fix
echo "=== QUICK REDEPLOY $(date) ==="
cd /srv/EarningsTable

echo "=== GIT PULL ==="
git fetch origin main 2>&1
git reset --hard origin/main 2>&1
git --no-pager log --oneline -2 2>&1 | cat

echo "=== BUILD ==="
npm run build 2>&1 | tail -5

echo "=== COPY BUILD -> PUBLIC ==="
cp -r build/* public/ 2>&1
ls -la public/index.html 2>&1

echo "=== RESTART API ==="
systemctl restart earnings-table 2>&1
sleep 3
systemctl is-active earnings-table 2>&1

echo "=== LOCAL CHECK ==="
curl -s -o /dev/null -w "root=%{http_code}\n" http://localhost:5555/ 2>&1
curl -s http://localhost:5555/ 2>&1 | head -c 100
echo ""
curl -s -o /dev/null -w "api/health=%{http_code}\n" http://localhost:5555/api/health 2>&1
curl -s -o /dev/null -w "api/dates=%{http_code}\n" http://localhost:5555/api/final-report/dates 2>&1

echo "=== PUBLIC CHECK ==="
curl -s -o /dev/null -w "public_root=%{http_code}\n" https://earningstable.com/ 2>&1
curl -s -o /dev/null -w "public_api=%{http_code}\n" https://earningstable.com/api/health 2>&1

echo "=== DONE $(date) ==="
