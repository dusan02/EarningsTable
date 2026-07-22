# 🚀 Nasadenie SEO zmien na produkciu

## ⚠️ Dôležité

Zmeny, ktoré boli urobené lokálne, musia byť nasadené na produkčný server, aby:
- ✅ Sitemap.xml mal aktuálny dátum
- ✅ Server správne presmerovával www na non-www
- ✅ Všetky SEO nastavenia boli aktívne

---

## 📋 Postup nasadenia

### Krok 1: Commitnúť zmeny lokálne (na Windows)

```powershell
# V PowerShell alebo Git Bash
cd D:\Projects\EarningsTable

# Pridať zmenené súbory
git add public/sitemap.xml server.ts modules/web/src/web.ts

# Commitnúť zmeny
git commit -m "SEO: Add www redirect, update sitemap with lastmod date"

# Pushnúť na GitHub
git push origin main
```

### Krok 2: Nasadiť na produkčný server (SSH)

```bash
# Pripojiť sa na server
ssh root@bardusa
# alebo
ssh your-username@your-server-ip

# Prejsť do projektu
cd /var/www/earnings-table

# Stiahnuť zmeny z GitHubu
git pull origin main

# Restartnúť PM2 služby
pm2 restart all

# Skontrolovať status
pm2 status
pm2 logs earnings-table --lines 20
```

### Krok 3: Overiť, že zmeny fungujú

```bash
# Test www redirect (mal by vrátiť 301 redirect)
curl -I https://www.earningstable.com/

# Test sitemap (mal by obsahovať lastmod tag)
curl https://earningsstable.com/sitemap.xml

# Test robots.txt
curl https://earningsstable.com/robots.txt

# Test hlavnej stránky
curl -I https://earningsstable.com/
```

---

## 🔍 Čo sa zmenilo

### 1. `public/sitemap.xml`
- ✅ Pridaný `<lastmod>2025-12-28</lastmod>` tag

### 2. `server.ts` (port 3000)
- ✅ Pridaný middleware na redirect z www na non-www

### 3. `modules/web/src/web.ts` (port 5555 - hlavný server)
- ✅ Pridaný middleware na redirect z www na non-www

---

## ⚡ Rýchly deploy (ak máte skript)

```bash
# Na SSH serveri
cd /var/www/earnings-table
./quick-pull-and-restart.sh
```

Alebo manuálne:

```bash
cd /var/www/earnings-table
git pull origin main
pm2 restart all
pm2 status
```

---

## ✅ Overenie po nasadení

### 1. Test redirect z www
```bash
curl -I https://www.earningstable.com/
# Očakávaný výsledok:
# HTTP/1.1 301 Moved Permanently
# Location: https://earningsstable.com/
```

### 2. Test sitemap
```bash
curl https://earningsstable.com/sitemap.xml
# Mal by obsahovať:
# <lastmod>2025-12-28</lastmod>
```

### 3. Test v Google Search Console
- Po nasadení počkajte cca 5-10 minút
- V Google Search Console použite "URL Inspection" pre `https://earningsstable.com/`
- Kliknite na "TEST LIVE URL"
- Overte, že canonical URL je správna

---

## 🐛 Riešenie problémov

### Ak git pull zlyhá:
```bash
# Na serveri
cd /var/www/earnings-table
git fetch origin
git reset --hard origin/main
pm2 restart all
```

### Ak PM2 nefunguje:
```bash
# Skontrolovať logy
pm2 logs earnings-table --err

# Kompletný restart
pm2 stop all
pm2 delete all
pm2 start ecosystem.config.js
```

### Ak redirect nefunguje:
- Skontrolujte, či Nginx správne presmerováva www na non-www
- Overte, že server.ts a web.ts sú správne nasadené
- Skontrolujte PM2 logy pre chyby

---

## 📝 Poznámky

- **Čas nasadenia**: Zmeny by sa mali prejaviť okamžite po `pm2 restart`
- **Google indexácia**: Môže trvať niekoľko hodín až dní, kým Google znovu prehľadá stránku
- **Sitemap**: Google automaticky kontroluje sitemap každých pár dní, ale môžete ho odoslať manuálne v Search Console

---

## 🎯 Ďalšie kroky po nasadení

1. ✅ Počkajte 5-10 minút
2. ✅ V Google Search Console použite "URL Inspection" pre `https://earningsstable.com/`
3. ✅ Kliknite na "TEST LIVE URL"
4. ✅ Kliknite na "REQUEST INDEXING" (ak ešte nie je indexovaná)
5. ✅ Odoslať sitemap v Search Console: `https://earningsstable.com/sitemap.xml`
