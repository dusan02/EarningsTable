# ANALÝZA ZOSTÁVAJÚCICH SÚBOROV V ROOT FOLDERI

## 📊 KATEGORIZÁCIA SÚBOROV

### **🗑️ SÚBORY NA VYMAZANIE (NEPOTREBNÉ):**

#### **Git command outputs (dočasné súbory):**
1. `how --name-only ef9e57b` - výstup git log príkazu
2. `tore .` - výstup git status príkazu
3. `et --hard b8a99cb` - výstup git reset príkazu
4. `et --hard 540ba9a` - výstup git reset príkazu
5. `how --format=-h -s -cd --date=relative HEAD` - výstup git log príkazu

#### **Jednorázové utility súbory:**
6. `add_benzinga_guidance_table.php` - jednorázový skript na pridanie tabuľky
7. `add_missing_tickers.php` - jednorázový skript na pridanie tickerov
8. `add_missing_columns.php` - jednorázový skript na pridanie stĺpcov
9. `add_polygon_columns.php` - jednorázový skript na pridanie Polygon stĺpcov
10. `add_polygon_static_columns.sql` - SQL skript na pridanie stĺpcov
11. `delete_sharesoutstanding_table.php` - jednorázový skript na vymazanie tabuľky
12. `create_shares_table.php` - jednorázový skript na vytvorenie tabuľky
13. `fix_market_cap_calculation.php` - jednorázový fix skript
14. `fix_database_schema.php` - jednorázový fix skript
15. `clear_all_data.php` - jednorázový cleanup skript

#### **Test a utility súbory:**
16. `get_polygon_market_data_fixed.php` - test/utility súbor
17. `get_polygon_market_data.php` - test/utility súbor
18. `get_alpha_vantage_earnings_today.php` - test/utility súbor
19. `get_earnings_tickers_only.php` - test/utility súbor
20. `get_finnhub_earnings_today.php` - test/utility súbor
21. `test-runner.ps1` - PowerShell test runner
22. `utilities.js` - JavaScript utility súbor (mal by byť v public/js/)

#### **HTML súbory:**
23. `hboard-fixed.html` - test HTML súbor
24. `dashboard-fixed-export.html` - export HTML súbor

### **📁 SÚBORY NA PRESUNUTIE DO FOLDEROV:**

#### **Do docs/ folderu (dokumentácia):**
1. `HISTORICKA_TABULKA_CRONOV.md` → docs/
2. `SUHRNNY_REPORT_CASOV.md` → docs/
3. `AKTUALNY_ZOZNAM_CRONOV.md` → docs/
4. `POROVNANIE_CASOV_PRED_A_PO_REFACTORINGU.md` → docs/
5. `DALŠIE_OPTIMALIZACIE_REGULAR_DATA_UPDATES_DYNAMIC.md` → docs/
6. `ANALYSIS_REGULAR_DATA_UPDATES_DYNAMIC.md` → docs/
7. `REFACTORING_REGULAR_DATA_UPDATES_DYNAMIC_REPORT.md` → docs/
8. `OPTIMIZATION_IMPLEMENTATION_REPORT.md` → docs/
9. `FINAL_CRON_ANALYSIS.md` → docs/
10. `PARALLEL_PROCESSING_PERFORMANCE.md` → docs/
11. `DAILY_DATA_SETUP_REFACTORING_SUMMARY.md` → docs/
12. `BATCH_API_OPTIMIZATION_SUMMARY.md` → docs/
13. `INTELLIGENT_EARNINGS_SYSTEM.md` → docs/
14. `YAHOO_FINANCE_REMOVAL_SUMMARY.md` → docs/
15. `MANUAL_DATA_REMOVAL_SUMMARY.md` → docs/
16. `ALPHA_VANTAGE_REMOVAL_SUMMARY.md` → docs/
17. `PROBLEMS_SOLVED.md` → docs/
18. `cron_performance_report.md` → docs/
19. `ENHANCED_ARCHITECTURE_DOCUMENTATION.md` → docs/

#### **Do scripts/ folderu (utility skripty):**
20. `report_final_architecture.php` → scripts/
21. `report_4_categories_updated.php` → scripts/
22. `report_4_categories.php` → scripts/
23. `analyze_table_relationships.php` → scripts/

#### **Do sql/ folderu (SQL skripty):**
24. `add_polygon_static_columns.sql` → sql/

#### **Do public/js/ folderu (JavaScript):**
25. `utilities.js` → public/js/

#### **Do archive/ folderu (zastarané súbory):**
26. `get_alpha_vantage_earnings_today.php` → archive/
27. `get_finnhub_earnings_today.php` → archive/

### **📋 SÚBORY NA KONTROLU (MOŽNO POTREBNÉ):**

#### **Check súbory (potrebné analyzovať):**
1. `check_todayearningsmovements_detailed.php`
2. `check_market_cap_data.php`
3. `check_todayearningsmovements_status.php`
4. `check_todayearningsmovements_schema.php`
5. `check_earnings_calendar.php`
6. `check_us_tickers_db.php`
7. `check_us_bmo_bns.php`
8. `check_bmo_bns.php`
9. `check_bmo_bns_db.php`
10. `check_finnhub_tickers.php`

### **🔒 SÚBORY NA ZACHOVANIE (POTREBNÉ):**

#### **Konfiguračné súbory:**
1. `config.php` - hlavná konfigurácia
2. `composer.json` - Composer konfigurácia
3. `composer.lock` - Composer lock súbor
4. `phpstan.neon` - PHPStan konfigurácia
5. `phpcs.xml` - PHP Code Sniffer konfigurácia
6. `.gitignore` - Git ignore súbor
7. `.htaccess` - Apache konfigurácia
8. `web.config` - IIS konfigurácia

#### **Hlavné súbory:**
9. `README.md` - hlavná dokumentácia
10. `LICENSE` - licencia
11. `Makefile` - Makefile pre automatizáciu
12. `start-server.bat` - Windows server starter

#### **Analýza súbory:**
13. `ANALYZA_CHECK_TEST_SUBOROV.md` - analýza check/test súborov
14. `ANALYZA_ZOSTAVAJUCICH_CHECK_SUBOROV.md` - analýza zostávajúcich check súborov
15. `SURNA_SPRAVA_CISTENIA.md` - súhrnná správa o čistení

## 🎯 ODORÚČANIA:

1. **VYMAZAŤ** 23 nepotrebných súborov (Git outputs, jednorázové utility, test súbory)
2. **PRESUNÚŤ** 27 súborov do vhodných folderov (docs/, scripts/, sql/, public/js/, archive/)
3. **ANALYZOVAŤ** 10 check súborov pre potrebnosť
4. **ZACHOVAŤ** 15 potrebných súborov (konfigurácia, hlavné súbory, analýza)
5. **VYČISTIŤ** root folder od nepotrebných súborov
6. **ORGANIZOVAŤ** súbory do logických folderov

## 📈 PRÍNOS ORGANIZÁCIE:

- **Lepšia štruktúra** projektu
- **Jednoduchšia navigácia** v kóde
- **Profesionálnejší vzhľad** projektu
- **Lahšia údržba** a rozvoj
- **Oddelenie** dokumentácie, skriptov a konfigurácie
- **Vyčistenie** root folderu
