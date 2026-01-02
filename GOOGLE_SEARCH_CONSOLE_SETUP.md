# 📊 Google Search Console - Nastavenie Sitemap

## ✅ Čo je potrebné

**ÁNO, sitemap by mal byť odoslaný do Google Search Console**, ale najprv musí fungovať cez HTTPS.

---

## 🔧 Krok 1: Opraviť Nginx (aktuálny problém)

Sitemap a robots.txt musia fungovať cez HTTPS pred odoslaním do Google Search Console.

**Na serveri spustite:**

```bash
cd /var/www/earnings-table
git pull origin main
chmod +x SSH_DIAGNOSE_NGINX_LOCATION.sh
./SSH_DIAGNOSE_NGINX_LOCATION.sh
```

Tento skript zobrazí detailnú diagnostiku, prečo Nginx location blocks nefungujú.

---

## 📤 Krok 2: Odoslať Sitemap do Google Search Console

**Až po oprave Nginx:**

1. **Otvorte Google Search Console**
   - URL: https://search.google.com/search-console
   - Vyberte property: `https://earningsstable.com/` alebo `https://www.earningstable.com/`

2. **Pridať Sitemap**
   - V ľavom menu: **Indexing** → **Sitemaps**
   - V poli "Add a new sitemap" zadajte: `sitemap.xml`
   - Kliknite na **Submit**

3. **Overiť odoslanie**
   - Po niekoľkých minútach by sa sitemap mal zobraziť v zozname
   - Status by mal byť "Success" alebo "Pending"

---

## 🔍 Krok 3: Overiť dostupnosť Sitemap

**Pred odoslaním do Google Search Console overte:**

```bash
# Test sitemap cez HTTPS
curl -k https://earningsstable.com/sitemap.xml

# Mal by vrátiť XML obsah, nie 404
```

---

## ⚠️ Dôležité poznámky

1. **Sitemap musí byť dostupný cez HTTPS** - Google ho nebude môcť načítať, ak vracia 404
2. **Robots.txt musí fungovať** - Google ho kontroluje pred načítaním sitemap
3. **Čas indexácie** - Po odoslaní sitemap môže trvať niekoľko dní, kým Google stránky indexuje

---

## 📋 Checklist pred odoslaním

- [ ] ✅ robots.txt funguje cez HTTPS (`https://earningsstable.com/robots.txt`)
- [ ] ✅ sitemap.xml funguje cez HTTPS (`https://earningsstable.com/sitemap.xml`)
- [ ] ✅ Sitemap obsahuje `lastmod` tag
- [ ] ✅ Canonical URL je správna (`https://earningsstable.com/`)
- [ ] ✅ www redirect funguje (301)

---

## 🚀 Po oprave Nginx

Keď sitemap bude fungovať cez HTTPS:

1. **Odoslať sitemap** v Google Search Console
2. **Požiadať o indexáciu** hlavnej stránky (`https://earningsstable.com/`)
3. **Počkať 24-48 hodín** na prvú indexáciu
4. **Skontrolovať status** v Search Console

---

## 📚 Ďalšie zdroje

- [Google Search Console Help](https://support.google.com/webmasters)
- [Submit a sitemap](https://support.google.com/webmasters/answer/183668)
