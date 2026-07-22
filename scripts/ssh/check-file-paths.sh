#!/bin/bash
# 🔍 Check file paths and __dirname in production

echo "🔍 Checking file paths..."
echo ""

cd /var/www/earnings-table

# 1. Check if files exist
echo "1. File locations:"
echo "-----------------"
if [ -f "public/robots.txt" ]; then
    echo "✅ public/robots.txt exists"
    ls -la public/robots.txt
else
    echo "❌ public/robots.txt NOT found"
fi

if [ -f "public/sitemap.xml" ]; then
    echo "✅ public/sitemap.xml exists"
    ls -la public/sitemap.xml
else
    echo "❌ public/sitemap.xml NOT found"
fi

echo ""

# 2. Check PM2 working directory
echo "2. PM2 working directory:"
echo "-------------------------"
pm2 describe earnings-table | grep -E "cwd|script|exec cwd" || echo "  Cannot get PM2 info"

echo ""

# 3. Check PM2 logs for robots/sitemap errors
echo "3. Recent PM2 logs (robots/sitemap):"
echo "-----------------------------------"
pm2 logs earnings-table --lines 50 --nostream | grep -i -E "robots|sitemap|__dirname|File exists|File not found" | tail -20 || echo "  No relevant logs found"

echo ""

# 4. Test what __dirname would be
echo "4. Testing __dirname:"
echo "-------------------"
node -e "console.log('__dirname would be:', require('path').resolve(__dirname))" 2>/dev/null || echo "  Cannot test"

echo ""

# 5. Check actual file paths
echo "5. Actual file paths:"
echo "-------------------"
find . -name "robots.txt" -o -name "sitemap.xml" 2>/dev/null | head -10

echo ""

