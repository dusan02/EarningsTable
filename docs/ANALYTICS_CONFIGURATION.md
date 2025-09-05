# Google Analytics Configuration - EarningsTable

## 📊 Vaše Google Analytics Údaje

### **Stream Information:**

- **Názov streamu:** EarningsTable.com
- **Webová adresa streamu:** https://earningstable.com
- **Identifikátor streamu:** 12120280480
- **Identifikátor merania:** G-E6DJ7N6W1L

### **Konfiguračné súbory:**

- `config/analytics.php` - Hlavná konfigurácia
- `public/includes/analytics.php` - Include súbor pre HTML
- `public/js/analytics-events.js` - Custom event tracking

## 🎯 Sledované Eventy

### **Automatické eventy:**

1. **page_load** - Načítanie stránky
   - Parametre: page_title, page_location, timestamp
2. **data_refresh** - Obnovenie dát z API

   - Parametre: event_category, event_label, timestamp

3. **api_error** - Chyby API
   - Parametre: api_name, error_type, timestamp

### **User interaction eventy:**

1. **ticker_click** - Klik na ticker v tabuľke
   - Parametre: ticker, event_category, event_label
2. **search_performed** - Vyhľadávanie spoločností
   - Parametre: search_term, event_category, event_label
3. **filter_used** - Použitie filtrov
   - Parametre: filter_type, filter_value, event_category
4. **view_toggle** - Prepínanie medzi EPS/Revenue a Guidance
   - Parametre: view_type, event_category, event_label

### **Earnings eventy:**

1. **earnings_beat** - EPS/Revenue beat/miss
   - Parametre: ticker, beat_type, percentage
2. **market_cap_change** - Zmeny market cap
   - Parametre: ticker, change_type, change_value

## 🔧 Aktuálna Konfigurácia

```php
// config/analytics.php
define('GA_MEASUREMENT_ID', 'G-E6DJ7N6W1L');
define('GA_ENABLED', true);
define('GA_DEBUG_MODE', false);
define('GA_TRACK_EVENTS', true);
define('GA_ANONYMIZE_IP', true);
define('GA_COOKIE_CONSENT', true);
```

## 📈 Čo sledujeme

### **Geografické dáta:**

- Krajina návštevníka
- Mesto návštevníka
- Časové pásmo

### **Správanie návštevníkov:**

- Čas na stránke
- Počet zobrazených stránok
- Bounce rate
- Returning vs new visitors

### **Technické dáta:**

- Zariadenie (desktop/mobile/tablet)
- Browser a verzia
- Operačný systém
- Rozlíšenie obrazovky

### **Custom eventy:**

- Kliky na tickery
- Vyhľadávanie
- Použitie filtrov
- API chyby

## 🛠️ Testovanie

### **Test súbory:**

- `public/test-analytics.html` - Základný test
- `public/test-ga-verification.html` - Kompletný test s vašimi údajmi

### **Testovanie v Google Analytics:**

1. Otvor [Google Analytics](https://analytics.google.com/)
2. Vyber projekt **EarningsTable.com**
3. Choď na **Realtime → Overview**
4. Klikni na test tlačidlá
5. Sleduj real-time eventy

## 📊 Reporting

### **Základné reporty:**

- **Audience** - demografické dáta návštevníkov
- **Acquisition** - odkiaľ prichádzajú návštevníci
- **Behavior** - ako sa správajú na stránke
- **Conversions** - custom eventy a ciele

### **Custom reporty:**

- **Earnings Table Usage** - ako používatelia používajú tabuľku
- **Ticker Popularity** - najčastejšie kliknuté tickery
- **Search Analytics** - najčastejšie vyhľadávané spoločnosti
- **Error Tracking** - API chyby a problémy

## 🚀 Deployment

### **Lokálny vývoj:**

- Analytics je aktívne s ID: G-E6DJ7N6W1L
- Debug mód: vypnutý
- Test súbory: dostupné

### **Produkcia (VPS):**

- Rovnaké ID: G-E6DJ7N6W1L
- Debug mód: vypnutý
- SSL: povinné pre GA4

## 🔒 GDPR Compliance

### **Implementované ochrany:**

- **IP anonymization** - IP adresy sa anonymizujú
- **Cookie consent** - možnosť implementovať cookie consent
- **Data retention** - nastaviteľné obdobie uchovávania dát

### **Cookie consent implementácia:**

```javascript
// Ak používate cookie consent banner
if (cookieConsentGiven) {
  gtag("consent", "update", {
    analytics_storage: "granted",
  });
}
```

## 📞 Podpora

### **Ak máte problémy:**

1. Skontrolujte [Google Analytics Help Center](https://support.google.com/analytics/)
2. Použite [Google Analytics Debugger](https://chrome.google.com/webstore/detail/google-analytics-debugger/jnkmfdileelhofjcijamephohjechhna)
3. Skontrolujte console pre JavaScript chyby
4. Otestujte s debug módom zapnutým

### **Debug informácie:**

```php
// Zapnúť debug mód
define('GA_DEBUG_MODE', true);

// Skontrolovať konfiguráciu
var_dump([
    'GA_ENABLED' => GA_ENABLED,
    'GA_MEASUREMENT_ID' => GA_MEASUREMENT_ID,
    'GA_TRACK_EVENTS' => GA_TRACK_EVENTS
]);
```

## ✅ Status

**Google Analytics je plne nakonfigurované a pripravené na použitie!**

- ✅ Measurement ID: G-E6DJ7N6W1L
- ✅ Stream: EarningsTable.com
- ✅ Custom eventy: implementované
- ✅ GDPR compliance: aktívne
- ✅ Test súbory: dostupné
- ✅ Dokumentácia: kompletná
