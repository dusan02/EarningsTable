# ANALÝZA CHECK_XXX.PHP A TEST_XXX.PHP SÚBOROV

## 📊 SÚHRN ANALÝZY

### **🗑️ SÚBORY NA VYMAZANIE (NEPOTREBNÉ):**

#### **1. debug_polygon_response.php**
- **Účel:** Debug Polygon batch response pre konkrétne tickery
- **Stav:** Nepotrebný - problém vyriešený
- **Dôvod:** Používal sa na debugovanie chýb, ktoré sú už vyriešené
- **Akcia:** VYMAZAŤ

#### **2. check_current_prices.php**
- **Účel:** Kontrola current_price, previous_close, market_cap pre konkrétne tickery
- **Stav:** Nepotrebný - jednorázová kontrola
- **Dôvod:** Používal sa na debugovanie chýb, ktoré sú už vyriešené
- **Akcia:** VYMAZAŤ

#### **3. check_db_counts.php**
- **Účel:** Kontrola počtu records v databázových tabuľkách
- **Stav:** Nepotrebný - jednorázová kontrola
- **Dôvod:** Používal sa na debugovanie chýb, ktoré sú už vyriešené
- **Akcia:** VYMAZAŤ

#### **4. analyze_failures.php**
- **Účel:** Analýza zlyhaní tickerov pri spracovaní
- **Stav:** Nepotrebný - problém vyriešený
- **Dôvod:** Používal sa na analýzu chýb, ktoré sú už vyriešené
- **Akcia:** VYMAZAŤ

#### **5. list_tickers.php**
- **Účel:** Zobrazenie zoznamu všetkých tickerov a ich market cap
- **Stav:** Nepotrebný - duplicitný s check_all_tickers.php
- **Dôvod:** Duplicitná funkcionalita
- **Akcia:** VYMAZAŤ

#### **6. check_all_tickers.php**
- **Účel:** Kontrola všetkých tickerov a ich market cap
- **Stav:** Nepotrebný - jednorázová kontrola
- **Dôvod:** Používal sa na debugovanie chýb, ktoré sú už vyriešené
- **Akcia:** VYMAZAŤ

#### **7. check_market_caps.php**
- **Účel:** Kontrola tickerov s market cap > 0
- **Stav:** Nepotrebný - jednorázová kontrola
- **Dôvod:** Používal sa na debugovanie chýb, ktoré sú už vyriešené
- **Akcia:** VYMAZAŤ

#### **8. check_table_structure.php**
- **Účel:** Kontrola štruktúry tabuľky todayearningsmovements
- **Stav:** Nepotrebný - jednorázová kontrola
- **Dôvod:** Používal sa na debugovanie chýb, ktoré sú už vyriešené
- **Akcia:** VYMAZAŤ

#### **9. check_today_earnings.php**
- **Účel:** Kontrola earnings dát a market cap
- **Stav:** Nepotrebný - jednorázová kontrola
- **Dôvod:** Používal sa na debugovanie chýb, ktoré sú už vyriešené
- **Akcia:** VYMAZAŤ

#### **10. test_market_cap_timing.php**
- **Účel:** Test market cap timing analýzy pre NVDA
- **Stav:** Nepotrebný - jednorázový test
- **Dôvod:** Používal sa na testovanie, ktoré už nie je potrebné
- **Akcia:** VYMAZAŤ

### **📁 SÚBORY NA PRESUNUTIE DO TESTS/ FOLDERU:**

#### **1. test_market_cap_diff_final.php**
- **Účel:** Test market cap difference calculation
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **2. test_market_cap_diff_calculation.php**
- **Účel:** Test market cap difference calculation
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **3. test_earnings_data_sources.php**
- **Účel:** Test earnings data sources
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **4. test_corrected_market_cap_diff.php**
- **Účel:** Test corrected market cap difference
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **5. test_polygon_static_data.php**
- **Účel:** Test Polygon static data
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **6. test_data_sources.php**
- **Účel:** Test data sources
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **7. test_finnhub_shares.php**
- **Účel:** Test Finnhub shares
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **8. test_finnhub_earnings.php**
- **Účel:** Test Finnhub earnings
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **9. test_polygon_api_key.php**
- **Účel:** Test Polygon API key
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **10. test_finnhub_ry_data.php**
- **Účel:** Test Finnhub revenue data
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **11. test_alpha_vantage_eps_revenue.php**
- **Účel:** Test Alpha Vantage EPS revenue
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **12. test_polygon_news_nvda.php**
- **Účel:** Test Polygon news for NVDA
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **13. test_polygon_news_dci.php**
- **Účel:** Test Polygon news for DCI
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **14. test_alpha_vantage_atat.php**
- **Účel:** Test Alpha Vantage ATAT
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **15. test_alpha_vantage_timing.php**
- **Účel:** Test Alpha Vantage timing
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **16. test_alpha_vantage_bmo_detailed.php**
- **Účel:** Test Alpha Vantage BMO detailed
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **17. test_finnhub_bmo.php**
- **Účel:** Test Finnhub BMO
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **18. test_alpha_vantage_today.php**
- **Účel:** Test Alpha Vantage today
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

#### **19. test_alpha_vantage_detailed.php**
- **Účel:** Test Alpha Vantage detailed
- **Stav:** Potrebný pre testovanie
- **Akcia:** PRESUNÚŤ DO Tests/

### **📋 SÚBORY NA KONTROLU (MOŽNO POTREBNÉ):**

#### **1. check_all_tables.php**
- **Účel:** Kontrola všetkých tabuliek
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **2. check_todayearningsmovements_detailed.php**
- **Účel:** Detailná kontrola todayearningsmovements
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **3. check_market_cap_data.php**
- **Účel:** Kontrola market cap dát
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **4. check_todayearningsmovements_status.php**
- **Účel:** Kontrola statusu todayearningsmovements
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **5. check_todayearningsmovements_schema.php**
- **Účel:** Kontrola schemy todayearningsmovements
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **6. check_earnings_calendar.php**
- **Účel:** Kontrola earnings calendar
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **7. check_us_tickers_db.php**
- **Účel:** Kontrola US tickerov v databáze
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **8. check_us_bmo_bns.php**
- **Účel:** Kontrola US BMO BNS
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **9. check_bmo_bns.php**
- **Účel:** Kontrola BMO BNS
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

#### **10. check_bmo_bns_db.php**
- **Účel:** Kontrola BMO BNS v databáze
- **Stav:** Potrebné skontrolovať
- **Akcia:** ANALYZOVAŤ

## 🎯 ODORÚČANIA:

1. **VYMAZAŤ** všetky debug a jednorázové kontrolné súbory
2. **PRESUNÚŤ** všetky test súbory do Tests/ folderu
3. **ANALYZOVAŤ** zostávajúce check súbory pre potrebnosť
4. **VYČISTIŤ** root folder od nepotrebných súborov
5. **ORGANIZOVAŤ** test súbory do logických skupín v Tests/ folderi
