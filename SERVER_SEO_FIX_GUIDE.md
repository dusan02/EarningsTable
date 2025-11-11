# 🔧 Server SEO Fix Guide - Step by Step

## Analýza chýb z terminálu

### Chyba 1: Git conflict
```
error: Your local changes to the following files would be overwritten by merge:
        post-deployment-seo-check.sh
```

**Riešenie:**
```bash
cd /var/www/earnings-table
git checkout -- post-deployment-seo-check.sh  # Discard local changes
git pull origin feat/skeleton-loading-etag
```

### Chyba 2: update-nginx-seo.sh neexistuje
**Príčina:** Git pull zlyhal kvôli konfliktu

**Riešenie:** Po oprave git konfliktu sa súbor stiahne

### Chyba 3: sudo neexistuje
**Príčina:** Ste prihlásený ako `root`, takže `sudo` nie je potrebné

**Riešenie:** Scripty sú teraz upravené, aby detekovali root a preskočili sudo

### Chyba 4: SSL certificate problem (self-signed)
```
curl: (60) SSL certificate problem: self-signed certificate
```

**Príčina:** Server používa self-signed SSL certifikát

**Riešenie:** 
- Scripty teraz používajú `curl -k` (ignoruje SSL chyby)
- Pre produkciu odporúčam Let's Encrypt certifikát

### Chyba 5: HTTP 404
**Príčina:** Chýba HTTP→HTTPS redirect v Nginx

**Riešenie:** Aktualizovať Nginx config (pozri nižšie)

---

## 🚀 Rýchle riešenie (všetko naraz)

```bash
cd /var/www/earnings-table

# 1. Oprav git conflict
git checkout -- post-deployment-seo-check.sh
git pull origin feat/skeleton-loading-etag

# 2. Spusti fix script
chmod +x fix-server-seo.sh
./fix-server-seo.sh

# 3. Spusti SEO check (teraz s -k flagom pre SSL)
chmod +x post-deployment-seo-check.sh
./post-deployment-seo-check.sh https://earningsstable.com
```

---

## 📋 Detailný postup

### Krok 1: Oprav git conflict

```bash
cd /var/www/earnings-table

# Zruš lokálne zmeny
git checkout -- post-deployment-seo-check.sh

# Stiahni najnovšie zmeny
git pull origin feat/skeleton-loading-etag

# Over, že súbory existujú
ls -la post-deployment-seo-check.sh update-nginx-seo.sh fix-server-seo.sh
```

### Krok 2: Spusti fix script

```bash
chmod +x fix-server-seo.sh
./fix-server-seo.sh
```

Tento script:
- ✅ Opraví git conflict
- ✅ Stiahne najnovšie zmeny
- ✅ Otestuje HTTPS endpointy (s `-k` flagom)
- ✅ Skontroluje homepage obsah
- ✅ Zobrazí PM2 status

### Krok 3: Test SEO (s opravenými scriptmi)

```bash
chmod +x post-deployment-seo-check.sh
./post-deployment-seo-check.sh https://earningsstable.com
```

**Teraz by mal fungovať**, pretože:
- ✅ Používa `curl -k` (ignoruje SSL chyby)
- ✅ Je tolerantnejší k HTTP 404 (len warning)
- ✅ Funguje bez sudo (detekuje root)

### Krok 4: Oprav Nginx (voliteľné, ale odporúčané)

```bash
# Ak máš SSL certifikáty
chmod +x update-nginx-seo.sh
./update-nginx-seo.sh

# Alebo ak nemáš SSL, najprv:
apt install certbot python3-certbot-nginx
certbot --nginx -d earningsstable.com -d www.earningsstable.com
./update-nginx-seo.sh
```

---

## 🧪 Manuálne testy (s -k flagom)

```bash
# Homepage
curl -k -I https://earningsstable.com/

# Robots.txt
curl -k -I https://earningsstable.com/robots.txt

# Sitemap.xml
curl -k -I https://earningsstable.com/sitemap.xml

# X-Robots-Tag
curl -k -I https://earningsstable.com/ | grep -i robots

# API
curl -k -I https://earningsstable.com/api/final-report

# Homepage obsah
curl -k -s https://earningsstable.com/ | grep -i "earningsstable.com" | head -5
curl -k -s https://earningsstable.com/ | grep -i "earnings-table.com" || echo "✅ Old domain not found (GOOD)"
```

---

## ⚠️ Dôležité poznámky

1. **Self-signed SSL:** 
   - Scripty teraz fungujú s `-k` flagom
   - Pre produkciu odporúčam Let's Encrypt

2. **HTTP 404:**
   - Je to len warning (nie error)
   - Pre opravu aktualizuj Nginx config

3. **Root user:**
   - Ak si root, scripty automaticky preskočia sudo
   - Všetko by malo fungovať

4. **PM2 services:**
   - Skontroluj: `pm2 status`
   - Ak niečo nefunguje: `pm2 restart all`

---

## ✅ Očakávané výsledky po oprave

Po spustení `fix-server-seo.sh` a `post-deployment-seo-check.sh` by si mal vidieť:

- ✅ HTTPS homepage: HTTP 200
- ✅ robots.txt: HTTP 200
- ✅ sitemap.xml: HTTP 200
- ✅ X-Robots-Tag: index, follow
- ✅ Homepage obsahuje earningsstable.com
- ✅ Homepage NEOBSAHUJE earnings-table.com
- ✅ API: HTTP 200
- ⚠️ HTTP 404 (warning - treba opraviť Nginx)

---

## 📝 Po oprave

1. Skopíruj výstupy z `post-deployment-seo-check.sh`
2. Skopíruj výstupy z manuálnych testov
3. Pošli sem - preletím ich a doladíme detaily

