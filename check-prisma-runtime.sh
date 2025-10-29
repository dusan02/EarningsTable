#!/bin/bash
# 🔧 Fix Prisma Client Runtime Path
# Updates Prisma client to use correct runtime from modules/shared

set -e

PROJECT_DIR="/var/www/earnings-table"

echo "🔧 Fixing Prisma client runtime path..."

cd "$PROJECT_DIR/modules/shared/node_modules/@prisma/client"

# Check current index.js
echo "📋 Current index.js content:"
head -5 index.js

# The index.js should require from .prisma/client which is in the same directory
# But it might be pointing to root. Let's check:
echo ""
echo "🔍 Checking if .prisma/client exists locally:"
ls -la .prisma/client/ 2>/dev/null | head -5 || echo "❌ .prisma/client not found in @prisma/client directory"

# The .prisma/client should be in the parent directory
PRISMA_CLIENT_DIR="../.prisma/client"
if [ -d "$PRISMA_CLIENT_DIR" ]; then
    echo "✅ Found .prisma/client in parent directory"
    echo "📊 Files in .prisma/client:"
    ls -la "$PRISMA_CLIENT_DIR" | head -10
else
    echo "❌ .prisma/client not found in expected location"
fi

echo ""
echo "🔧 Checking Prisma client package.json:"
cat package.json | grep -A 5 "exports\|main" || echo "No exports/main found"

echo ""
echo "✅ Check complete"
echo ""
echo "If Prisma client is using wrong runtime, we need to create a symlink or update the path"

