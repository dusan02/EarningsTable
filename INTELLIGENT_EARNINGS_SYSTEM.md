# 🧠 Inteligentný Earnings Systém

## 🎯 **Cieľ:**

Automaticky nájsť a spracovať všetky tickery s earnings reportmi, aj tie ktoré chýbajú v primárnom zdroji.

## 🔄 **Ako to funguje:**

### **Step 1: Získa tickery z Finnhub (s EPS/Revenue dátami)**

- Primárny zdroj s kompletnými EPS/Revenue odhadmi
- Spracuje všetky tickery s úplnými dátami

### **Step 2: Získa tickery z Yahoo Finance**

- Sekundárny zdroj pre kontrolu kompletnosti
- Web scraping z Yahoo Finance earnings calendar

### **Step 3: Porovná a nájde chýbajúce tickery**

- Identifikuje tickery ktoré sú v Yahoo Finance ale chýbajú v Finnhub
- Príklad: BMO, BNS (kanadské banky na US burze)

### **Step 4: Pre chýbajúce tickery získa EPS/Revenue dáta z:**

- **Yahoo Finance** (ak má lepšie dáta)
- **Funkcia je pripravená** pre budúce rozšírenia

### **Step 5: Spracuje všetky tickery s market dátami**

- Získa market cap, ceny, zmeny z Polygon API
- Vloží do databázy s kompletnými informáciami

## 🚀 **Použitie:**

```bash
# Spustite inteligentný master cron
php cron/update_master_cron.php

# Alebo len inteligentný earnings fetch
php cron/intelligent_earnings_fetch.php
```

## ✅ **Výhody:**

- **Automaticky nájde** chýbajúce tickery
- **Získa EPS/Revenue dáta** z viacerých zdrojov
- **Nevyžaduje** manuálne pridávanie
- **Škálovateľné** - môžete pridať ďalšie zdroje

## 🔧 **Konfigurácia:**

Alpha Vantage API kľúč je už nakonfigurovaný v `config/production.env`:

```
# Alpha Vantage API - REMOVED (not reliable)
# ALPHA_VANTAGE_API_KEY=YFO8D5S1D0E4F80C
```

## 📊 **Príklad výstupu:**

```
=== STEP 1: FINNHUB (PRIMARY SOURCE) ===
✅ Finnhub: 25 tickers with EPS/Revenue data

=== STEP 2: YAHOO FINANCE (SECONDARY SOURCE) ===
✅ Yahoo Finance: 28 tickers

=== STEP 3: FINDING MISSING TICKERS ===
Total missing tickers: 0

=== STEP 4: COMBINING ALL TICKERS ===
Total unique tickers: 33

=== STEP 5: GETTING EPS/REVENUE DATA FOR MISSING TICKERS ===
No missing tickers to enhance
```

Tento systém zabezpečuje, že nikdy nepremeškáte dôležité earnings reporty! 🎯
