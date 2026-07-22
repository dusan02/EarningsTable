# 📋 SEO Deployment Checklist

## ✅ Pre-deployment (v Cursor-e)

- [x] Nahradené všetky `earnings-table.com` → `earningsstable.com`
- [x] Aktualizované meta tagy (canonical, og:url, twitter:url, JSON-LD)
- [x] Vytvorený `/public/robots.txt`
- [x] Vytvorený `/public/sitemap.xml`
- [x] Pridaný X-Robots-Tag middleware do serverov
- [x] Pridané explicitné route pre robots.txt a sitemap.xml

## 🚀 Deployment

### 1. Commit & Push

```bash
git add .
git commit -m "SEO: Update domain to earningsstable.com, add robots.txt and sitemap.xml"
git push origin main
```

### 2. Deploy na server

```bash
# SSH na server
ssh user@your-server

# Pull changes
cd /var/www/earnings-table
git pull origin main

# Restart services
pm2 restart all

# Alebo ak používaš Nginx
sudo systemctl reload nginx
```

### 3. Purge CDN Cache (ak používaš Cloudflare/NGINX cache)

```bash
# Cloudflare
curl -X POST "https://api.cloudflare.com/client/v4/zones/{zone_id}/purge_cache" \
  -H "Authorization: Bearer {api_token}" \
  -H "Content-Type: application/json" \
  --data '{"purge_everything":true}'

# Alebo cez Cloudflare dashboard: Caching → Purge Everything
```

## 🧪 Post-deployment Smoke Test

### Rýchly test (lokálne)

```bash
# Spusti SEO smoke test
./seo-smoke-test.sh https://earningsstable.com
```

### Manuálny test

```bash
# 1. Homepage - 200 OK + bez noindex
curl -I https://earningsstable.com/
# Očakávaný výsledok: HTTP/2 200, X-Robots-Tag: index, follow

# 2. Robots.txt
curl -I https://earningsstable.com/robots.txt
# Očakávaný výsledok: HTTP/2 200, Content-Type: text/plain

# 3. Sitemap.xml
curl -I https://earningsstable.com/sitemap.xml
# Očakávaný výsledok: HTTP/2 200, Content-Type: application/xml

# 4. X-Robots-Tag header
curl -I https://earningsstable.com/ | grep -i robots
# Očakávaný výsledok: x-robots-tag: index, follow

# 5. Homepage zdroják - kontrola, že už nie je earnings-table.com
# V prehliadači: view-source:https://earningsstable.com/
# Skontroluj, že sa nikde nevyskytuje "earnings-table.com"

# 6. Health check
curl https://earningsstable.com/api/health
# Očakávaný výsledok: JSON s status: "healthy" alebo "ok"
```

## 🔧 Server Configuration (301 Redirects)

### Nginx Configuration

Pridaj do `/etc/nginx/sites-available/earningsstable.com`:

```nginx
# Force HTTPS + redirect old domains
server {
    listen 80;
    listen [::]:80;
    server_name www.earningsstable.com earnings-table.com www.earnings-table.com;
    return 301 https://earningsstable.com$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.earningsstable.com earnings-table.com www.earnings-table.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    return 301 https://earningsstable.com$request_uri;
}

# Main server
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name earningsstable.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # ... rest of your config ...
    
    # Static files (robots.txt, sitemap.xml)
    location = /robots.txt {
        alias /var/www/earnings-table/public/robots.txt;
        access_log off;
    }
    
    location = /sitemap.xml {
        alias /var/www/earnings-table/public/sitemap.xml;
        access_log off;
    }
}
```

Po úprave:

```bash
sudo nginx -t  # Test configuration
sudo systemctl reload nginx  # Reload
```

## 📊 Google Search Console

### 1. Pridať novú vlastnosť

1. Otvor [Google Search Console](https://search.google.com/search-console)
2. Klikni **"Pridať vlastnosť"**
3. Vyber **"Predpona URL"**
4. Zadaj: `https://earningsstable.com`
5. Over doménu (DNS alebo HTML súbor)

### 2. Odoslať sitemap

1. V GSC → **Sitemaps**
2. Zadaj: `https://earningsstable.com/sitemap.xml`
3. Klikni **"Odoslať"**

### 3. Požiadať o indexovanie

1. V GSC → **Kontrola webovej adresy**
2. Zadaj: `https://earningsstable.com/`
3. Klikni **"Požiadať o indexovanie"**

### 4. Overiť opravy

1. V GSC → **Indexovanie → Strany**
2. Skontroluj skupiny:
   - ❌ 404 chyby
   - ❌ Blokované robots.txt
   - ❌ 5xx chyby
   - ❌ Presmerovania
   - ✅ "Alternatívna stránka so správnym canonical" → klikni **"Overiť opravu"**

## 🔍 Monitoring

### Čo sledovať po oprave

1. **GSC → Indexovanie stránok**
   - Malo by sa postupne zlepšovať
   - "Alternatívna stránka so správnou kanonickou značkou" by mala zmiznúť

2. **Výkon**
   - Core Web Vitals / PageSpeed
   - Homepage by nemala byť pomalá (< 3s)

3. **Logs**
   - `pm2 logs` - či Googlebot už nevidí 5xx/403
   - Nginx `access.log` / `error.log`

### Rýchle kontroly

```bash
# PM2 logs
pm2 logs earnings-table --lines 50

# Nginx logs
sudo tail -f /var/log/nginx/access.log | grep -i googlebot
sudo tail -f /var/log/nginx/error.log

# Server status
pm2 status
curl https://earningsstable.com/api/health
```

## ⚠️ Troubleshooting

### Ak robots.txt alebo sitemap.xml vracia 404

1. Skontroluj, či súbory existujú:
   ```bash
   ls -la /var/www/earnings-table/public/robots.txt
   ls -la /var/www/earnings-table/public/sitemap.xml
   ```

2. Skontroluj server route (server.ts alebo modules/web/src/web.ts)

3. Skontroluj Nginx config (ak používaš Nginx)

### Ak X-Robots-Tag chýba

1. Skontroluj middleware v server.ts a modules/web/src/web.ts
2. Reštartuj server: `pm2 restart all`

### Ak sa stále zobrazuje earnings-table.com

1. Purge CDN cache
2. Skontroluj, či sú všetky súbory commitnuté a pushnuté
3. Skontroluj view-source v prehliadači (Ctrl+U)

## ✅ Final Verification

Po všetkých krokoch skontroluj:

- [ ] `curl -I https://earningsstable.com/` → 200, X-Robots-Tag: index, follow
- [ ] `curl -I https://earningsstable.com/robots.txt` → 200
- [ ] `curl -I https://earningsstable.com/sitemap.xml` → 200
- [ ] `view-source:https://earningsstable.com/` → žiadne "earnings-table.com"
- [ ] GSC → sitemap odoslaný
- [ ] GSC → homepage požiadaná o indexovanie
- [ ] 301 redirecty fungujú (earnings-table.com → earningsstable.com)

---

**Hotovo!** 🎉 Stránka by sa mala čoskoro vrátiť do indexu.

