#!/bin/bash
# 🔧 Fix Nginx robots.txt and sitemap.xml - Direct file serving
# This adds location blocks to existing Nginx config

set -e

echo "🔧 Fixing Nginx robots.txt and sitemap.xml..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

NGINX_CONFIG="/etc/nginx/sites-enabled/earningstable.com"

# Check if config exists
if [ ! -f "$NGINX_CONFIG" ]; then
    echo -e "${RED}❌ Nginx config not found: $NGINX_CONFIG${NC}"
    echo "Available configs:"
    ls -la /etc/nginx/sites-enabled/ | grep -i earnings || echo "  None found"
    exit 1
fi

echo -e "${BLUE}Using config: $NGINX_CONFIG${NC}"
echo ""

# Backup
BACKUP_FILE="${NGINX_CONFIG}.backup.$(date +%Y%m%d-%H%M%S)"
cp "$NGINX_CONFIG" "$BACKUP_FILE"
echo -e "${GREEN}✅ Backup created: $BACKUP_FILE${NC}"
echo ""

# Check if location blocks already exist
if grep -q "location = /robots.txt" "$NGINX_CONFIG"; then
    echo -e "${YELLOW}⚠️  robots.txt location already exists${NC}"
    echo "Current config:"
    grep -A 5 "location = /robots.txt" "$NGINX_CONFIG"
    echo ""
    read -p "Replace existing? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Skipping..."
        exit 0
    fi
    # Remove existing
    sed -i '/location = \/robots.txt/,/^[[:space:]]*}/d' "$NGINX_CONFIG"
    sed -i '/location = \/sitemap.xml/,/^[[:space:]]*}/d' "$NGINX_CONFIG"
fi

# Find HTTPS server block and insert before "location /"
echo -e "${BLUE}Adding location blocks to HTTPS server block...${NC}"

# Create temp file with location blocks
TEMP_BLOCKS=$(cat << 'EOF'
    # SEO: Serve robots.txt and sitemap.xml directly from disk
    location = /robots.txt {
        alias /var/www/earnings-table/public/robots.txt;
        access_log off;
        default_type text/plain;
        add_header Content-Type text/plain;
    }

    location = /sitemap.xml {
        alias /var/www/earnings-table/public/sitemap.xml;
        access_log off;
        default_type application/xml;
        add_header Content-Type application/xml;
    }

EOF
)

# Insert before first "location /" in HTTPS block (listen 443)
# Use Python for more reliable text insertion
python3 << PYTHON_SCRIPT
import re

with open("$NGINX_CONFIG", "r") as f:
    content = f.read()

# Find HTTPS server block
https_block_pattern = r'(server\s*\{[^}]*listen\s+443[^}]*\{[^}]*)(location\s+/\s*\{)'

# Check if we're in HTTPS block
if 'listen 443' in content:
    # Find the position before first "location /" after "listen 443"
    lines = content.split('\n')
    in_https_block = False
    insert_pos = None
    
    for i, line in enumerate(lines):
        if 'listen 443' in line:
            in_https_block = True
        if in_https_block and re.match(r'\s*location\s+/\s*\{', line):
            insert_pos = i
            break
    
    if insert_pos:
        # Insert location blocks
        blocks = """$TEMP_BLOCKS""".split('\n')
        for j, block_line in enumerate(blocks):
            lines.insert(insert_pos + j, block_line)
        
        with open("$NGINX_CONFIG", "w") as f:
            f.write('\n'.join(lines))
        print("✅ Location blocks added")
    else:
        # Append before closing brace of HTTPS server block
        # Find last } before next server block or end
        lines = content.split('\n')
        in_https = False
        brace_count = 0
        insert_pos = None
        
        for i, line in enumerate(lines):
            if 'listen 443' in line:
                in_https = True
                brace_count = line.count('{') - line.count('}')
            elif in_https:
                brace_count += line.count('{') - line.count('}')
                if brace_count == 0 and '}' in line:
                    insert_pos = i
                    break
        
        if insert_pos:
            blocks = """$TEMP_BLOCKS""".split('\n')
            for j, block_line in enumerate(blocks):
                lines.insert(insert_pos + j, '    ' + block_line.lstrip())
            
            with open("$NGINX_CONFIG", "w") as f:
                f.write('\n'.join(lines))
            print("✅ Location blocks added before closing brace")
        else:
            print("❌ Could not find insertion point")
            exit(1)
else:
    print("❌ No HTTPS server block found")
    exit(1)
PYTHON_SCRIPT

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to add location blocks${NC}"
    echo "Restoring backup..."
    cp "$BACKUP_FILE" "$NGINX_CONFIG"
    exit 1
fi

# Test config
echo ""
echo -e "${BLUE}Testing Nginx configuration...${NC}"
if nginx -t; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    echo "Restoring backup..."
    cp "$BACKUP_FILE" "$NGINX_CONFIG"
    exit 1
fi

# Reload Nginx
echo ""
echo -e "${BLUE}Reloading Nginx...${NC}"
systemctl reload nginx
echo -e "${GREEN}✅ Nginx reloaded${NC}"

echo ""
echo -e "${GREEN}🎉 Nginx SEO fix applied!${NC}"
echo ""
echo -e "${BLUE}Testing endpoints:${NC}"
echo "  curl -k -I https://earningsstable.com/robots.txt"
echo "  curl -k -I https://earningsstable.com/sitemap.xml"
echo ""
echo "Expected: HTTP/2 200 OK"
echo ""

