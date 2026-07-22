#!/bin/bash
# 🔍 Check Prisma Schema in Generated Client
# Verify that the Prisma client has the correct schema

set -e

PROJECT_DIR="/var/www/earnings-table"

echo "🔍 Checking Prisma schema in generated client..."

RUNTIME_SCHEMA="$PROJECT_DIR/modules/shared/node_modules/.prisma/client/schema.prisma"

if [ -f "$RUNTIME_SCHEMA" ]; then
    echo "✅ Found schema.prisma in runtime"
    echo ""
    echo "📋 Checking for FinalReport model:"
    grep -A 30 "model FinalReport" "$RUNTIME_SCHEMA" || echo "❌ FinalReport model not found!"
    echo ""
    echo "📋 Checking table mapping:"
    grep "@@map" "$RUNTIME_SCHEMA" | grep -i final || echo "❌ FinalReport table mapping not found!"
else
    echo "❌ Schema file not found at: $RUNTIME_SCHEMA"
fi

echo ""
echo "🔍 Checking runtime/library.js for references:"
RUNTIME_LIB="$PROJECT_DIR/modules/shared/node_modules/.prisma/client/runtime/library.js"
if [ -f "$RUNTIME_LIB" ]; then
    echo "📊 File size: $(du -h "$RUNTIME_LIB" | cut -f1)"
    echo "📊 First 20 lines:"
    head -20 "$RUNTIME_LIB" | grep -E "final_report|FinalReport|main\.final_report" || echo "  No references found in first 20 lines"
else
    echo "❌ Runtime library not found"
fi

echo ""
echo "✅ Check complete"

