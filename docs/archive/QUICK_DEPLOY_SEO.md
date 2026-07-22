# 🚀 Rýchly SEO Deploy Guide

## ✅ Commit & Push - HOTOVÉ

Zmeny sú commitnuté a pushnuté na branch `feat/skeleton-loading-etag`.

## 📥 Deploy na Server (SSH)

```bash
# 1. Prihlás sa na server
ssh user@your-server

# 2. Prejdi do projektu
cd /var/www/earnings-table

# 3. Stiahni zmeny
git fetch origin
git checkout feat/skeleton-loading-etag
git pull origin feat/skeleton-loading-etag

# 4. Reštartuj služby
pm2 restart all

# Alebo ak používaš Nginx
sudo systemctl reload nginx
```

## 🧪 Post-Deployment Checks

### Rýchly test (na serveri)

```bash
# Spusti rozšírený SEO check
chmod +x post-deployment-seo-check.sh
./post-deployment-seo-check.sh https://earningsstable.com
```

### Manuálne kontroly

```bash
# 1. Canonical consistency (bez reťazcov)
for u in \
  http://earningsstable.com \
  https://www.earningsstable.com \
  https://earnings-table.com \
  https://www.earnings-table.com
do 
  echo "Testing $u:"
  curl -I -L -s $u | egrep -i 'HTTP/|location:'
  echo ""
done

# 2. Robots / Sitemap / No-noindex
curl -I https://earningsstable.com/robots.txt
curl -I https://earningsstable.com/sitemap.xml
curl -I https://earningsstable.com/ | grep -i robots

# 3. API stability
curl -I https://earningsstable.com/api/final-report

# 4. Googlebot simulation
curl -A "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)" \
  -I https://earningsstable.com/

# 5. Trailing slash consistency
curl -I -L https://earningsstable.com
curl -I -L https://earningsstable.com/

# 6. Sitemap validation (ak máš xmllint)
curl -s https://earningsstable.com/sitemap.xml | xmllint --noout - 2>/dev/null || echo "OK"

# 7. Logy po crawli
pm2 logs --lines 200 | grep -i -E 'googlebot|bot|crawl|5xx|502|503'
```

## 📊 Google Search Console

1. **Pridaj novú vlastnosť**: `https://earningsstable.com`
2. **Odosli sitemap**: `https://earningsstable.com/sitemap.xml`
3. **Požiadaj o indexovanie**: Homepage URL
4. **Overiť opravy**: V "Strany" pri skupinách s chybami klikni "Overiť opravu"

## ⚠️ Ak niečo nefunguje

- **403/5xx len pre Googlebota** → Skontroluj WAF/CDN pravidlá
- **robots.txt 404** → Skontroluj, či je v `/public/robots.txt` a route je pred catch-all
- **Stále earnings-table.com** → Purge CDN cache

## 📝 Výstupy

Po spustení `post-deployment-seo-check.sh` skopíruj výstupy sem - preletím ich a doladíme detaily.

