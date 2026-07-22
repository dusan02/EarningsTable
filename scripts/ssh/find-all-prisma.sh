#!/bin/bash
# 🔍 Find ALL Prisma clients on the system
# This helps identify where old Prisma clients might be hiding

set -e

PROJECT_DIR="/var/www/earnings-table"

echo "🔍 Searching for ALL Prisma clients..."

cd "$PROJECT_DIR"

echo ""
echo "📊 Root node_modules:"
find node_modules -name ".prisma" -type d 2>/dev/null | head -10 || echo "  None found"
find node_modules -name "@prisma" -type d 2>/dev/null | head -10 || echo "  None found"

echo ""
echo "📊 modules/shared/node_modules:"
find modules/shared/node_modules -name ".prisma" -type d 2>/dev/null | head -10 || echo "  None found"
find modules/shared/node_modules -name "@prisma" -type d 2>/dev/null | head -10 || echo "  None found"

echo ""
echo "📊 Checking for runtime/library.js files:"
find . -path "*/node_modules/.prisma/client/runtime/library.js" 2>/dev/null | head -10 || echo "  None found"

echo ""
echo "📊 Checking for runtime/library.js in root:"
ls -la node_modules/.prisma/client/runtime/library.js 2>/dev/null || echo "  ✅ Not found in root"

echo ""
echo "✅ Search complete"

