# Google Analytics Setup Guide

## 📊 Prehľad

Google Analytics je implementované do EarningsTable projektu pre sledovanie návštevníkov, ich správania a geografického rozloženia.

## 🚀 Rýchle nastavenie

### 1. Vytvorenie Google Analytics účtu

1. Choďte na [Google Analytics](https://analytics.google.com/)
2. Kliknite na "Začať meranie"
3. Vytvorte nový účet a vlastnosť
4. Vyberte "Web" ako platformu
5. Zadajte názov webu: "Earnings Table"
6. Vyberte časové pásmo a menu
7. Súhlaste s podmienkami

### 2. Získanie Measurement ID

1. Po vytvorení účtu získate **Measurement ID**
2. Formát: `G-XXXXXXXXXX` (napr. `G-E6DJ7N6W1L`)
3. Skopírujte toto ID

### 3. Konfigurácia v projekte

1. Otvorte súbor `config/analytics.php`
2. Nahraďte `GA_MEASUREMENT_ID` s vaším skutočným ID:

```php
define('GA_MEASUREMENT_ID', 'G-E6DJ7N6W1L'); // Váš skutočný ID
```

**✅ Vaše Google Analytics údaje:**

- **Názov streamu:** EarningsTable.com
- **Webová adresa:** https://earningstable.com
- **Identifikátor streamu:** 12120280480
- **Measurement ID:** G-E6DJ7N6W1L

### 4. Aktivácia

1. Nastavte `GA_ENABLED` na `true`:

```php
define('GA_ENABLED', true);
```

2. Pre produkciu nastavte `GA_DEBUG_MODE` na `false`:

```php
define('GA_DEBUG_MODE', false);
```

## 📈 Sledované eventy

### Automatické eventy:

- **page_load** - Načítanie stránky
- **data_refresh** - Obnovenie dát z API
- **api_error** - Chyby API

### User interaction eventy:

- **ticker_click** - Klik na ticker v tabuľke
- **search_performed** - Vyhľadávanie spoločností
- **filter_used** - Použitie filtrov
- **view_toggle** - Prepínanie medzi EPS/Revenue a Guidance

### Earnings eventy:

- **earnings_beat** - EPS/Revenue beat/miss
- **market_cap_change** - Zmeny market cap

## 🔧 Konfigurácia

### Základné nastavenia:

```php
// Povoliť/zakázať Google Analytics
define('GA_ENABLED', true);

// Debug mód (len pre vývoj)
define('GA_DEBUG_MODE', false);

// E-commerce tracking
define('GA_ENHANCED_ECOMMERCE', false);

// Custom events tracking
define('GA_TRACK_EVENTS', true);
```

### Privacy nastavenia (GDPR):

```php
// Anonymizovať IP adresy
define('GA_ANONYMIZE_IP', true);

// Cookie consent
define('GA_COOKIE_CONSENT', true);
```

## 📊 Čo sledujeme

### Geografické dáta:

- **Krajina** návštevníka
- **Mesto** návštevníka
- **Časové pásmo**

### Správanie návštevníkov:

- **Čas na stránke**
- **Počet zobrazených stránok**
- **Bounce rate**
- **Returning vs new visitors**

### Technické dáta:

- **Zariadenie** (desktop/mobile/tablet)
- **Browser** a verzia
- **Operačný systém**
- **Rozlíšenie obrazovky**

### Custom eventy:

- **Kliky na tickery**
- **Vyhľadávanie**
- **Použitie filtrov**
- **API chyby**

## 🛠️ Testovanie

### 1. Debug mód:

```php
define('GA_DEBUG_MODE', true);
```

### 2. Google Analytics Debugger:

- Nainštalujte [Google Analytics Debugger](https://chrome.google.com/webstore/detail/google-analytics-debugger/jnkmfdileelhofjcijamephohjechhna) pre Chrome
- Otvorte Developer Tools (F12)
- Prejdite na tab "Console"
- Načítajte stránku a sledujte GA eventy

### 3. Real-time reporting:

1. Choďte do Google Analytics
2. Kliknite na "Realtime" v ľavom menu
3. Otvorte váš web v novom okne
4. Sledujte real-time návštevníkov

## 📱 Mobile tracking

Analytics automaticky sleduje:

- **Mobile vs Desktop** návštevníkov
- **Touch events** na mobile zariadeniach
- **Screen orientation** (portrait/landscape)
- **Connection speed**

## 🔒 GDPR Compliance

### Implementované ochrany:

- **IP anonymization** - IP adresy sa anonymizujú
- **Cookie consent** - možnosť implementovať cookie consent
- **Data retention** - nastaviteľné obdobie uchovávania dát

### Cookie consent implementácia:

```javascript
// Ak používate cookie consent banner
if (cookieConsentGiven) {
  // Povoliť Google Analytics
  gtag("consent", "update", {
    analytics_storage: "granted",
  });
}
```

## 📊 Reporting

### Základné reporty:

1. **Audience** - demografické dáta návštevníkov
2. **Acquisition** - odkiaľ prichádzajú návštevníci
3. **Behavior** - ako sa správajú na stránke
4. **Conversions** - custom eventy a ciele

### Custom reporty:

- **Earnings Table Usage** - ako používatelia používajú tabuľku
- **Ticker Popularity** - najčastejšie kliknuté tickery
- **Search Analytics** - najčastejšie vyhľadávané spoločnosti
- **Error Tracking** - API chyby a problémy

## 🚨 Troubleshooting

### Analytics sa nezobrazuje:

1. Skontrolujte `GA_MEASUREMENT_ID` v `config/analytics.php`
2. Skontrolujte `GA_ENABLED` je nastavené na `true`
3. Skontrolujte, či sa súbor `public/includes/analytics.php` načítava
4. Skontrolujte Developer Tools pre JavaScript chyby

### Eventy sa nesledujú:

1. Skontrolujte `GA_TRACK_EVENTS` je nastavené na `true`
2. Skontrolujte, či sa `analytics-events.js` načítava
3. Skontrolujte console pre JavaScript chyby

### Debug informácie:

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

## 📞 Podpora

Ak máte problémy s Google Analytics:

1. Skontrolujte [Google Analytics Help Center](https://support.google.com/analytics/)
2. Použite [Google Analytics Debugger](https://chrome.google.com/webstore/detail/google-analytics-debugger/jnkmfdileelhofjcijamephohjechhna)
3. Skontrolujte console pre JavaScript chyby
4. Otestujte s debug módom zapnutým

## 🎯 Ďalšie možnosti

### Enhanced E-commerce:

```php
define('GA_ENHANCED_ECOMMERCE', true);
```

### Custom dimensions:

```javascript
gtag("config", "GA_MEASUREMENT_ID", {
  custom_map: {
    custom_parameter_1: "user_type",
  },
});
```

### Goals a Conversions:

1. Choďte do Google Analytics
2. Admin → Goals
3. Vytvorte nové ciele pre:
   - Ticker clicks
   - Search usage
   - Data refreshes
