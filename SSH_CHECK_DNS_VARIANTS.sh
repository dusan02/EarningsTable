#!/bin/bash
# 🔍 Check DNS for both domain variants

echo "🔍 Checking DNS for domain variants..."
echo ""

echo "1️⃣  earningstable.com (ONE 's' - CORRECT):"
DNS_ONE=$(dig +short earningstable.com)
if [ -n "$DNS_ONE" ]; then
    echo "   ✅ Resolves to: $DNS_ONE"
else
    echo "   ❌ Not found"
fi
echo ""

echo "2️⃣  www.earningstable.com (ONE 's' - CORRECT):"
DNS_WWW_ONE=$(dig +short www.earningstable.com)
if [ -n "$DNS_WWW_ONE" ]; then
    echo "   ✅ Resolves to: $DNS_WWW_ONE"
else
    echo "   ❌ Not found"
fi
echo ""

echo "3️⃣  earningsstable.com (TWO 's' - INCORRECT):"
DNS_TWO=$(dig +short earningsstable.com)
if [ -n "$DNS_TWO" ]; then
    echo "   ⚠️  Resolves to: $DNS_TWO (but this domain should NOT be used)"
else
    echo "   ✅ Not found (correct - this domain doesn't exist)"
fi
echo ""

echo "4️⃣  www.earningsstable.com (TWO 's' - INCORRECT):"
DNS_WWW_TWO=$(dig +short www.earningsstable.com)
if [ -n "$DNS_WWW_TWO" ]; then
    echo "   ⚠️  Resolves to: $DNS_WWW_TWO (but this domain should NOT be used)"
else
    echo "   ✅ Not found (correct - this domain doesn't exist)"
fi
echo ""

echo "📝 Summary:"
echo "   ✅ Use: https://earningstable.com (ONE 's')"
echo "   ✅ Use: https://www.earningstable.com (ONE 's' - redirects to non-www)"
echo "   ❌ Do NOT use: earningsstable.com (TWO 's') - no DNS records"
echo ""
