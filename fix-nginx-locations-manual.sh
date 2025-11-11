#!/bin/bash
# 🔧 Manual fix for Nginx locations - uses root instead of alias

set -e

echo "🔧 Fixing Nginx locations with root directive..."
echo ""

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"

if [ ! -f "$NGINX_CONFIG" ]; then
    echo "❌ Config not found: $NGINX_CONFIG"
    exit 1
fi

# Backup
BACKUP_FILE="${NGINX_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"
cp "$NGINX_CONFIG" "$BACKUP_FILE"
echo "✅ Backup: $BACKUP_FILE"
echo ""

# Remove existing robots.txt and sitemap.xml locations
sed -i '/location = \/robots.txt/,/^[[:space:]]*}/d' "$NGINX_CONFIG"
sed -i '/location = \/sitemap.xml/,/^[[:space:]]*}/d' "$NGINX_CONFIG"

# Find HTTPS server block and insert before "location /"
# Use a simpler approach: find the line with "location /" in HTTPS block and insert before it
python3 << 'PYTHON_SCRIPT'
import re

config_file = "/etc/nginx/sites-enabled/earningstable.com"

with open(config_file, "r") as f:
    lines = f.readlines()

# Find HTTPS server block (listen 443)
in_https = False
https_start = None
location_slash_line = None
brace_count = 0

for i, line in enumerate(lines):
    if "listen 443" in line:
        in_https = True
        https_start = i
        brace_count = line.count("{") - line.count("}")
    elif in_https:
        brace_count += line.count("{") - line.count("}")
        if re.match(r'\s*location\s+/\s*\{', line):
            location_slash_line = i
            break
        if brace_count == 0 and "}" in line:
            # End of server block, insert before closing brace
            location_slash_line = i
            break

if location_slash_line is None:
    print("❌ Could not find insertion point")
    exit(1)

# Insert location blocks
locations = [
    "    # SEO: Serve robots.txt and sitemap.xml directly from disk\n",
    "    location = /robots.txt {\n",
    "        root /var/www/earnings-table/public;\n",
    "        try_files /robots.txt =404;\n",
    "        access_log off;\n",
    "        default_type text/plain;\n",
    "        add_header Content-Type text/plain;\n",
    "    }\n",
    "\n",
    "    location = /sitemap.xml {\n",
    "        root /var/www/earnings-table/public;\n",
    "        try_files /sitemap.xml =404;\n",
    "        access_log off;\n",
    "        default_type application/xml;\n",
    "        add_header Content-Type application/xml;\n",
    "    }\n",
    "\n",
]

# Insert before location /
for i, loc_line in enumerate(locations):
    lines.insert(location_slash_line + i, loc_line)

with open(config_file, "w") as f:
    f.writelines(lines)

print("✅ Location blocks added before location /")
PYTHON_SCRIPT

if [ $? -ne 0 ]; then
    echo "❌ Failed to add locations"
    cp "$BACKUP_FILE" "$NGINX_CONFIG"
    exit 1
fi

# Test config
echo ""
echo "Testing Nginx configuration..."
if nginx -t 2>&1 | grep -q "successful"; then
    echo "✅ Nginx config valid"
    systemctl reload nginx
    echo "✅ Nginx reloaded"
else
    echo "❌ Nginx config invalid"
    cp "$BACKUP_FILE" "$NGINX_CONFIG"
    exit 1
fi

echo ""
echo "🎉 Done! Test with:"
echo "  curl -k -I https://earningsstable.com/robots.txt"
echo "  curl -k -I https://earningsstable.com/sitemap.xml"

