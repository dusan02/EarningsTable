#!/bin/bash
# 🔍 Debug Express Routes - Find why robots.txt/sitemap.xml routes don't work

echo "🔍 Debugging Express Routes..."
echo ""

cd /var/www/earnings-table

# 1. Check if routes are in the file
echo "1. Checking if routes exist in simple-server.js:"
echo "-----------------------------------------------"
grep -n "app.get.*robots.txt" simple-server.js || echo "  ❌ robots.txt route NOT FOUND"
grep -n "app.get.*sitemap.xml" simple-server.js || echo "  ❌ sitemap.xml route NOT FOUND"
echo ""

# 2. Check for express.static that might catch these
echo "2. Checking for express.static middleware:"
echo "------------------------------------------"
grep -n "express.static" simple-server.js | head -5
echo ""

# 3. Check route order (what comes before robots.txt)
echo "3. Routes defined BEFORE robots.txt:"
echo "------------------------------------"
ROBOTS_LINE=$(grep -n "app.get.*robots.txt" simple-server.js | cut -d: -f1)
if [ -n "$ROBOTS_LINE" ]; then
    echo "  robots.txt route at line: $ROBOTS_LINE"
    echo "  Routes before it:"
    sed -n "1,$((ROBOTS_LINE-1))p" simple-server.js | grep -E "app\.(get|use|post|put|delete)" | tail -10
else
    echo "  ❌ robots.txt route not found"
fi
echo ""

# 4. Add test route to simple-server.js (temporary)
echo "4. Testing route registration:"
echo "-------------------------------"
echo "  Adding test route to verify Express works..."
echo ""

# Create a test version with a simple test route
cat > /tmp/test-route.js << 'EOF'
const express = require("express");
const app = express();

// Test route - should work
app.get("/test-express", (req, res) => {
  res.send("Express routes work!");
});

// robots.txt route
app.get("/robots.txt", (req, res) => {
  console.log("[robots] Route called!");
  res.send("User-agent: *\nDisallow:\n");
});

app.listen(5556, () => {
  console.log("Test server on port 5556");
});
EOF

echo "  Test server created at /tmp/test-route.js"
echo "  To test: node /tmp/test-route.js"
echo "  Then: curl http://localhost:5556/test-express"
echo "  And: curl http://localhost:5556/robots.txt"
echo ""

# 5. Check PM2 process
echo "5. PM2 Process Info:"
echo "-------------------"
pm2 describe earnings-table | grep -E "script|exec cwd|status" | head -5
echo ""

# 6. Check if there's a catch-all route
echo "6. Checking for catch-all routes:"
echo "---------------------------------"
grep -n "app.get.*'\*'" simple-server.js || grep -n "app.get.*\"\*\"" simple-server.js || echo "  No catch-all found"
echo ""

# 7. Check for any middleware that might return 404
echo "7. Checking for 404 handlers:"
echo "------------------------------"
grep -n -i "404\|not found" simple-server.js | head -5
echo ""

echo "=========================================="
echo "Summary:"
echo "  - If routes exist but don't work, check:"
echo "    1. Route order (must be before catch-all)"
echo "    2. Express static middleware"
echo "    3. PM2 cache (try: pm2 delete earnings-table && pm2 start ecosystem.config.js)"
echo "    4. Add global request logger to see all requests"
echo ""

