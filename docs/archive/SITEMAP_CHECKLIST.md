# 📋 Sitemap Checklist for earningstable.com

## ✅ Čo už je hotové:

1. ✅ **Sitemap.xml existuje** - `/public/sitemap.xml`
2. ✅ **Nginx správne servuje sitemap** - location block funguje
3. ✅ **robots.txt odkazuje na sitemap** - `Sitemap: https://earningstable.com/sitemap.xml`
4. ✅ **Správna doména** - používa `earningstable.com` (jedno 's')

## 🔄 Čo treba dokončiť:

### 1. Aktualizovať lastmod dátum
- Aktuálny: `2025-12-28`
- Mal by byť: `2026-01-02` (alebo aktuálny dátum)

### 2. Google Search Console
- [ ] Pridať property: `https://earningstable.com`
- [ ] Odošlieť sitemap: `https://earningstable.com/sitemap.xml`
- [ ] Overiť vlastníctvo domény

### 3. Automatická aktualizácia sitemapy (voliteľné)
- Možno vytvoriť skript, ktorý automaticky aktualizuje `lastmod` dátum
- Alebo dynamickú sitemapu cez API endpoint

### 4. Pridať ďalšie URL (ak existujú)
- Homepage: ✅ už je v sitemape
- API endpointy: ❌ NEMAJÚ byť v sitemape (sú to API, nie stránky)
- Ak existujú stránky pre jednotlivé spoločnosti, pridať ich

## 📝 Aktuálna sitemapa obsahuje:
- Homepage (`/`) - priority 1.0, daily updates

## 🎯 Ďalšie kroky:
1. Aktualizovať dátum v sitemape
2. Odošlieť sitemapu do Google Search Console
3. (Voliteľné) Vytvoriť automatickú aktualizáciu
