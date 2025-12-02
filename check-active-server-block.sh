#!/bin/bash
# 🔍 Check what's actually in the active server block

echo "🔍 Checking active server block..."
echo ""

# Get full compiled config
FULL_CONFIG=$(nginx -T 2>/dev/null)

# Find HTTPS server block for earningsstable.com
echo "=== Searching for HTTPS server block with earningsstable.com ==="
echo "$FULL_CONFIG" | awk '
/server_name.*earningsstable.*443/ || /server_name.*earningsstable/ {
    if (/listen 443/) {
        in_block=1
        print "Found HTTPS server block:"
    }
}
in_block {
    print
    if (/^[[:space:]]*}/ && brace_count == 0) {
        exit
    }
    brace_count += gsub(/{/, "")
    brace_count -= gsub(/}/, "")
}
' | head -80

echo ""
echo "=== Checking for location blocks ==="
if echo "$FULL_CONFIG" | grep -q "location = /robots.txt"; then
    echo "✅ robots.txt location found in compiled config"
    echo "$FULL_CONFIG" | grep -A 5 "location = /robots.txt" | head -6
else
    echo "❌ robots.txt location NOT found in compiled config"
fi

if echo "$FULL_CONFIG" | grep -q "location = /sitemap.xml"; then
    echo "✅ sitemap.xml location found in compiled config"
    echo "$FULL_CONFIG" | grep -A 5 "location = /sitemap.xml" | head -6
else
    echo "❌ sitemap.xml location NOT found in compiled config"
fi

echo ""
echo "=== All server blocks with earningsstable.com ==="
echo "$FULL_CONFIG" | awk '
/server {/ { 
    in_server=1
    server_start=NR
    server_content=""
}
in_server {
    server_content = server_content $0 "\n"
    if (/server_name.*earningsstable/) {
        has_earningsstable=1
    }
    if (/listen 443/) {
        has_https=1
    }
    if (/^}/) {
        if (has_earningsstable && has_https) {
            print "=== HTTPS Server Block ==="
            print server_content
            print ""
        }
        in_server=0
        has_earningsstable=0
        has_https=0
        server_content=""
    }
}
' | head -100

