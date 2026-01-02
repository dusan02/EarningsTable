#!/bin/bash
# 🔄 Update sitemap lastmod date to today

echo "🔄 Updating sitemap lastmod date..."

SITEMAP="/var/www/earnings-table/public/sitemap.xml"
TODAY=$(date +%Y-%m-%d)

if [ ! -f "$SITEMAP" ]; then
    echo "❌ Sitemap not found at $SITEMAP"
    exit 1
fi

# Backup
cp "$SITEMAP" "${SITEMAP}.backup.$(date +%Y%m%d-%H%M%S)"

# Update lastmod date
sed -i "s/<lastmod>.*<\/lastmod>/<lastmod>$TODAY<\/lastmod>/" "$SITEMAP"

echo "✅ Updated lastmod to: $TODAY"
echo ""
echo "📋 Sitemap content:"
cat "$SITEMAP"
echo ""
echo "✅ Done!"
