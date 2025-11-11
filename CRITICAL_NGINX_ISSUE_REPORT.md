# 🔴 Critical Nginx Issue Report - robots.txt/sitemap.xml 404

**Date:** November 11, 2025  
**Status:** 🔴 **CRITICAL** - Location blocks in active config but still returning 404  
**Priority:** URGENT

---

## 📊 Executive Summary

**Problem:** Despite correct Nginx configuration with location blocks in the active server block, `/robots.txt` and `/sitemap.xml` return HTTP 404 with Express headers, indicating requests are being proxied to Express instead of served by Nginx.

**Key Finding:** Location blocks ARE in the compiled Nginx config (`nginx -T` confirms), but Nginx is not matching them - requests fall through to `location /` and get proxied to Express.

---

## ✅ What's Working

1. **Nginx Configuration Structure**
   - ✅ Single HTTPS server block for `earningsstable.com`
   - ✅ Location blocks correctly placed before `location /`
   - ✅ Root directive set: `root /var/www/earnings-table/public;`
   - ✅ No conflicting server names (0 conflicts after cleanup)

2. **Files Exist**
   - ✅ `/var/www/earnings-table/public/robots.txt` exists and is readable
   - ✅ `/var/www/earnings-table/public/sitemap.xml` exists and is readable
   - ✅ File permissions correct (Nginx user can read)

3. **Location Blocks in Active Config**
   - ✅ `location = /robots.txt` is in compiled config (line 71-75)
   - ✅ `location = /sitemap.xml` is in compiled config (line 77-81)
   - ✅ Both use `try_files` (tried) and `alias` (tried) - neither works

4. **Cleanup Completed**
   - ✅ All backup files removed from `sites-enabled/`
   - ✅ Duplicate server blocks removed
   - ✅ Default config commented out
   - ✅ No symlinks causing conflicts

---

## ❌ What's NOT Working

### Critical Issue: Location Blocks Not Matching

**Symptoms:**
- HTTP 404 responses for `/robots.txt` and `/sitemap.xml`
- Response headers show Express (not Nginx): `x-content-type-options: nosniff`
- Requests are proxied to Express (port 5555) instead of served by Nginx

**Evidence:**
```bash
$ curl -k -I https://earningsstable.com/robots.txt
HTTP/2 404 
content-type: text/plain; charset=utf-8
x-content-type-options: nosniff  ← Express header
content-length: 19
```

**What We've Tried:**
1. ✅ `root + try_files` - Location blocks in config, but not matching
2. ✅ `alias` with absolute paths - Location blocks in config, but not matching
3. ✅ Moved location blocks before `location /`
4. ✅ Removed all duplicate server blocks
5. ✅ Full Nginx restart (not just reload)
6. ✅ Verified location blocks in compiled config

**Current Config (Active):**
```nginx
server {
    listen 443 ssl http2;
    server_name earningsstable.com;
    
    root /var/www/earnings-table/public;
    
    location = /robots.txt {
        alias /var/www/earnings-table/public/robots.txt;
        default_type text/plain;
        access_log off;
    }
    
    location = /sitemap.xml {
        alias /var/www/earnings-table/public/sitemap.xml;
        default_type application/xml;
        access_log off;
    }
    
    location / {
        proxy_pass http://127.0.0.1:5555;
        # ... proxy headers ...
    }
}
```

**Compiled Config Verification:**
- `nginx -T` shows location blocks ARE in active config
- Location blocks are correctly positioned before `location /`
- No syntax errors

---

## 🔍 Root Cause Analysis

### Hypothesis #1: Location Block Precedence Issue (MOST LIKELY)

**Theory:** Despite `location =` (exact match) being before `location /`, Nginx might be matching `location /` first due to some internal precedence issue or configuration quirk.

**Evidence:**
- Location blocks are in config
- But requests still go to Express
- Response headers show Express, not Nginx

**Test Needed:**
- Temporarily comment out `location /` block
- Test if `/robots.txt` then works
- This would confirm precedence issue

### Hypothesis #2: Nginx Worker Process Cache

**Theory:** Nginx worker processes might be using cached configuration despite reload/restart.

**Evidence:**
- Multiple reloads/restarts done
- Config changes applied
- But behavior unchanged

**Test Needed:**
- Kill all Nginx workers: `killall -9 nginx`
- Start fresh: `systemctl start nginx`
- Test again

### Hypothesis #3: Location Block Syntax Issue

**Theory:** There might be a subtle syntax issue with `location =` or `alias` that Nginx accepts but doesn't match correctly.

**Evidence:**
- Config test passes (`nginx -t`)
- But location blocks don't match

**Test Needed:**
- Try different location block syntax
- Use `location ^~ /robots.txt` instead of `location =`
- Or use `location ~ ^/robots\.txt$` (regex)

### Hypothesis #4: Request Path Normalization

**Theory:** Nginx might be normalizing the request path in a way that prevents exact match.

**Evidence:**
- `location = /robots.txt` should match exactly
- But it doesn't

**Test Needed:**
- Check Nginx access logs for actual request path
- Check if path is being modified before matching

---

## 🛠️ Recommended Solutions

### Solution 1: Test Location Block Precedence

```bash
# 1. Comment out location / temporarily
sed -i 's/^    location \/ {/#    location \/ {/' /etc/nginx/sites-enabled/earningstable.com
sed -i 's/^    }$/#    }/' /etc/nginx/sites-enabled/earningstable.com

# 2. Test
nginx -t && systemctl reload nginx
curl -k -I https://earningsstable.com/robots.txt

# 3. If it works, uncomment and investigate precedence
# 4. If it doesn't work, location blocks have different issue
```

### Solution 2: Try Different Location Block Syntax

```bash
# Replace location blocks with regex match
sed -i 's/location = \/robots.txt/location ~ ^\/robots\.txt$/' /etc/nginx/sites-enabled/earningstable.com
sed -i 's/location = \/sitemap.xml/location ~ ^\/sitemap\.xml$/' /etc/nginx/sites-enabled/earningstable.com

# Or try prefix match with ^~
sed -i 's/location = \/robots.txt/location ^~ \/robots.txt/' /etc/nginx/sites-enabled/earningstable.com
sed -i 's/location = \/sitemap.xml/location ^~ \/sitemap.xml/' /etc/nginx/sites-enabled/earningstable.com
```

### Solution 3: Check Nginx Error/Access Logs

```bash
# Enable debug logging temporarily
# Add to nginx.conf: error_log /var/log/nginx/error.log debug;

# Check logs during request
tail -f /var/log/nginx/error.log &
curl -k https://earningsstable.com/robots.txt

# Check access logs
tail -f /var/log/nginx/access.log &
curl -k https://earningsstable.com/robots.txt
```

### Solution 4: Serve via Express as Fallback

If Nginx location blocks cannot be made to work, serve via Express (already implemented):

**Current Express Routes:**
- `app.get("/robots.txt", ...)` in `simple-server.js` line 60
- `app.get("/sitemap.xml", ...)` in `simple-server.js` line 91
- `express.static(PUBLIC_DIR)` in `simple-server.js` line 57

**Why It's Not Working:**
- Express routes return 404, suggesting files aren't found
- Or routes aren't being called (check PM2 logs)

**Check PM2 Logs:**
```bash
pm2 logs earnings-table --lines 50 | grep -E "ALL REQUESTS|robots|sitemap"
```

If you see `[ALL REQUESTS] GET /robots.txt` but still 404, Express route has issue.
If you DON'T see the log entry, requests aren't reaching Express.

---

## 📋 Diagnostic Commands

### Check Active Config
```bash
nginx -T 2>/dev/null | grep -A 50 "server_name.*earningsstable.*443"
```

### Check Location Block Matching
```bash
# Enable debug logging
echo "error_log /var/log/nginx/error.log debug;" >> /etc/nginx/nginx.conf
nginx -t && systemctl reload nginx

# Test and watch logs
tail -f /var/log/nginx/error.log &
curl -k https://earningsstable.com/robots.txt
```

### Check File Access
```bash
# Test as Nginx user
sudo -u www-data cat /var/www/earnings-table/public/robots.txt

# Check file permissions
ls -la /var/www/earnings-table/public/robots.txt
```

### Test Location Block Precedence
```bash
# Comment out location / temporarily
# Test if robots.txt works
# This confirms if precedence is the issue
```

---

## 🎯 Next Steps

### Immediate Actions
1. **Test location block precedence** - Comment out `location /` and test
2. **Check Nginx error logs** - Enable debug logging and watch during request
3. **Try different location syntax** - Regex or prefix match instead of exact match
4. **Verify Express fallback** - Check PM2 logs to see if requests reach Express

### If Location Blocks Can't Be Fixed
5. **Serve via Express** - Ensure Express routes work correctly
6. **Use Nginx map directive** - Alternative approach to route specific paths
7. **Use Nginx rewrite** - Rewrite specific paths before proxy

---

## 📊 Current Status

**Configuration:** ✅ Correct
- Location blocks in active config
- Proper syntax
- Correct file paths

**File System:** ✅ Correct
- Files exist
- Permissions correct
- Nginx user can read

**Nginx Behavior:** ❌ Incorrect
- Location blocks don't match
- Requests fall through to proxy
- Returns 404 from Express

**Conclusion:** This is a **location block matching issue**, not a configuration structure issue. The blocks are there, but Nginx isn't matching them for some reason.

---

## 💡 Key Insight

**The Mystery:** Location blocks ARE in the compiled config, files exist, permissions are correct, but Nginx still proxies to Express. This suggests:
1. Location block matching logic issue
2. Nginx internal precedence quirk
3. Request path modification before matching
4. Worker process using stale config (unlikely after restart)

**Most Likely:** Location block precedence or matching logic issue. Testing with `location /` commented out will reveal the truth.

---

## 🔗 Related Files

- Nginx Config: `/etc/nginx/sites-enabled/earningstable.com`
- Files: `/var/www/earnings-table/public/robots.txt`, `/var/www/earnings-table/public/sitemap.xml`
- Express Routes: `simple-server.js` lines 60-130
- Error Logs: `/var/log/nginx/error.log`
- Access Logs: `/var/log/nginx/access.log`

---

**Report Generated:** November 11, 2025  
**Next Critical Step:** Test location block precedence by commenting out `location /`  
**Confidence:** High that this is a location matching issue, not a config structure issue

