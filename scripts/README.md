# 🔧 Scripts Directory

Táto zložka obsahuje utility skripty pre správu a údržbu EarningsTable projektu.

## 📁 Obsah zložky:

### **🗄️ Databázové skripty:**
- `clear_tables.php` - Vyčistenie tabuliek
- `reset_tables.php` - Resetovanie tabuliek
- `create_shares_table.php` - Vytvorenie tabuľky shares

### **🔧 Opravné skripty:**
- `apply_sql_fix.php` - Aplikovanie SQL oprav
- `recalculate_all_sizes.php` - Prepočet veľkostí
- `update_ph_gild.php` - Aktualizácia PH/GILD dát

### **➕ Doplňovacie skripty:**
- `add_missing_to_earnings.php` - Pridanie chýbajúcich earnings

## 🚀 Ako spúšťať:

```bash
# Vyčistenie tabuliek
php scripts/clear_tables.php

# Resetovanie tabuliek
php scripts/reset_tables.php

# Prepočet veľkostí
php scripts/recalculate_all_sizes.php
```

## ⚠️ Pozor:
- Tieto skripty môžu modifikovať databázu
- Vždy zálohujte dáta pred spustením
- Testujte na development prostredí
