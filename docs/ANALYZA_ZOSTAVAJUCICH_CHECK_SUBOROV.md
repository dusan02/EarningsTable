# ANALÝZA ZOSTÁVAJÚCICH CHECK_XXX.PHP SÚBOROV

## 📊 SÚHRN ANALÝZY

### **🗑️ SÚBORY NA VYMAZANIE (NEPOTREBNÉ):**

#### **1. check_all_tables.php**
- **Účel:** Kontrola všetkých tabuliek a ich record count
- **Stav:** Nepotrebný - jednorázová kontrola
- **Dôvod:** Používal sa na debugovanie, už nie je potrebný
- **Akcia:** VYMAZAŤ

#### **2. check_current_data.php**
- **Účel:** Kontrola aktuálnych dát a ich freshness
- **Stav:** Nepotrebný - jednorázová kontrola
- **Dôvod:** Používal sa na debugovanie, už nie je potrebný
- **Akcia:** VYMAZAŤ

#### **3. check_db.php**
- **Účel:** Základná kontrola databázy
- **Stav:** Nepotrebný - jednorázová kontrola
- **Dôvod:** Používal sa na debugovanie, už nie je potrebný
- **Akcia:** VYMAZAŤ

#### **4. check_tables.php**
- **Účel:** Kontrola tabuliek
- **Stav:** Nepotrebný - jednorázová kontrola
- **Dôvod:** Používal sa na debugovanie, už nie je potrebný
- **Akcia:** VYMAZAŤ

#### **5. check_shares.php**
- **Účel:** Kontrola shares tabuliek
- **Stav:** Nepotrebný - jednorázová kontrola
- **Dôvod:** Používal sa na debugovanie, už nie je potrebný
- **Akcia:** VYMAZAŤ

#### **6. check_movements.php**
- **Účel:** Kontrola movements tabuliek
- **Stav:** Nepotrebný - jednorázová kontrola
- **Dôvod:** Používal sa na debugovanie, už nie je potrebný
- **Akcia:** VYMAZAŤ

### **📋 SÚBORY NA KONTROLU (MOŽNO POTREBNÉ):**

#### **1. check_bmo_bns.php**
- **Účel:** Kontrola BMO BNS dát
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **2. check_bmo_bns_db.php**
- **Účel:** Kontrola BMO BNS v databáze
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **3. check_earnings_calendar.php**
- **Účel:** Kontrola earnings calendar
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **4. check_finnhub_tickers.php**
- **Účel:** Kontrola Finnhub tickerov
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **5. check_market_cap_data.php**
- **Účel:** Kontrola market cap dát
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **6. check_todayearningsmovements_detailed.php**
- **Účel:** Detailná kontrola todayearningsmovements
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **7. check_todayearningsmovements_schema.php**
- **Účel:** Kontrola schemy todayearningsmovements
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **8. check_todayearningsmovements_status.php**
- **Účel:** Kontrola statusu todayearningsmovements
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **9. check_us_bmo_bns.php**
- **Účel:** Kontrola US BMO BNS
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **10. check_us_tickers_db.php**
- **Účel:** Kontrola US tickerov v databáze
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

## 🎯 ODORÚČANIA:

1. **VYMAZAŤ** všetky jednorázové kontrolné súbory
2. **ANALYZOVAŤ** zostávajúce check súbory pre potrebnosť
3. **VYČISTIŤ** root folder od nepotrebných súborov
4. **ORGANIZOVAŤ** zostávajúce súbory podľa účelu
