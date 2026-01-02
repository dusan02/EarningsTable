# ✅ Indexácia pre earningsstable.com - Dokončené

**Dátum:** 28. december 2025  
**Status:** ✅ Všetky zmeny dokončené

---

## 📋 Čo bolo vykonané

### 1. ✅ Aktualizovaný sitemap.xml
- Pridaný `lastmod` tag s aktuálnym dátumom (2025-12-28)
- Canonical URL: `https://earningsstable.com/`
- Changefreq: `daily`
- Priority: `1.0`

### 2. ✅ Server-side redirect z www na non-www
- Pridaný middleware v `server.ts` (port 3000)
- Pridaný middleware v `modules/web/src/web.ts` (port 5555)
- Všetky požiadavky na `www.earningstable.com` sa teraz presmerujú na `earningsstable.com` (301 redirect)

### 3. ✅ Konzistentné canonical tagy
- Všetky HTML súbory už majú správne canonical tagy: `https://earningsstable.com/`
- Meta tagy (OG, Twitter) sú konzistentné
- JSON-LD structured data používa správnu URL

### 4. ✅ Robots.txt
- Súbor je správne nakonfigurovaný
- Sitemap URL: `https://earningsstable.com/sitemap.xml`
- Povolené indexovanie: `Allow: /`

### 5. ✅ X-Robots-Tag header
- Server nastavuje `X-Robots-Tag: index, follow` pre všetky odpovede

---

## 🚀 Ďalšie kroky pre indexáciu

### 1. Požiadať o indexáciu v Google Search Console

1. **Otvorte Google Search Console**
   - URL: https://search.google.com/search-console
   - Vyberte property: `https://www.earningstable.com/` alebo `https://earningsstable.com/`

2. **Použite nástroj "URL Inspection"**
   - Vložte URL: `https://earningsstable.com/`
   - Kliknite na "TEST LIVE URL"
   - Po úspešnom teste kliknite na "REQUEST INDEXING"

3. **Odoslať sitemap**
   - V ľavom menu: **Indexing** → **Sitemaps**
   - Pridajte sitemap URL: `https://earningsstable.com/sitemap.xml`
   - Kliknite na **Submit**

### 2. Overiť dostupnosť súborov

Po nasadení na server overte, že sú dostupné:

```bash
# Robots.txt
curl -I https://earningsstable.com/robots.txt

# Sitemap.xml
curl -I https://earningsstable.com/sitemap.xml

# Hlavná stránka
curl -I https://earningsstable.com/
```

### 3. Overiť redirect z www

```bash
# Test www redirect
curl -I https://www.earningstable.com/

# Mal by vrátiť:
# HTTP/1.1 301 Moved Permanently
# Location: https://earningsstable.com/
```

### 4. Overiť canonical tagy

```bash
# Získajte HTML a skontrolujte canonical tag
curl -s https://earningsstable.com/ | grep -i canonical

# Mal by vrátiť:
# <link rel="canonical" href="https://earningsstable.com/" />
```

---

## 📝 Zmenené súbory

1. **public/sitemap.xml** - Pridaný `lastmod` tag
2. **server.ts** - Pridaný www → non-www redirect middleware
3. **modules/web/src/web.ts** - Pridaný www → non-www redirect middleware

---

## ⚠️ Dôležité poznámky

1. **Nginx konfigurácia**: Na serveri musí byť správne nakonfigurovaný Nginx, aby:
   - Presmerovával www na non-www (ak ešte nie je)
   - Správne servoval robots.txt a sitemap.xml

2. **DNS nastavenia**: Uistite sa, že obe verzie domény (www aj non-www) smerujú na správny server

3. **SSL certifikáty**: Musia byť nastavené pre obe verzie domény

4. **Čas indexácie**: Google môže trvať niekoľko dní až týždňov, kým stránku indexuje. Požiadanie o indexáciu môže proces urýchliť.

---

## 🔍 Diagnostika

Ak stránka stále nie je indexovaná po niekoľkých dňoch:

1. Skontrolujte Google Search Console pre chyby
2. Overte, že robots.txt neblokuje indexovanie
3. Skontrolujte, či canonical tagy sú správne
4. Overte, že sitemap.xml je dostupný a validný
5. Skontrolujte server logs pre chyby

---

## 📚 Ďalšie zdroje

- [Google Search Console Help](https://support.google.com/webmasters)
- [Google Search Central - Indexing](https://developers.google.com/search/docs/crawling-indexing)
- [Canonical URLs](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls)
