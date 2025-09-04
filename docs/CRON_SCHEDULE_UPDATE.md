# ⏰ Cron Schedule Update - Benzinga 5-Minute Updates

## 🚀 **NOVÁ ARCHITEKTÚRA CRONOV**

### **DENNÉ CRONY (02:00 NY time):**

| Cron                     | Súbor                                | Frekvencia | Účel                                       |
| ------------------------ | ------------------------------------ | ---------- | ------------------------------------------ |
| **1. Master**            | `1_enhanced_master_cron.php`         | Denné      | Orchestrácia všetkých cronov               |
| **2. Clear Data**        | `2_clear_old_data.php`               | Denné      | Čistenie starých dát                       |
| **3. Daily Setup**       | `3_daily_data_setup_static.php`      | Denné      | Statické dáta (tickery, market cap)        |
| **4. Regular Updates**   | `4_regular_data_updates_dynamic.php` | Denné      | Dynamické dáta (ceny, zmeny)               |
| **5. Benzinga Guidance** | `5_benzinga_guidance_updates.php`    | Denné      | Corporate guidance dáta + consensus porovnanie |

### **5-MINÚTOVÉ CRONY:**

| Cron                   | Súbor                                | Frekvencia    | Účel                                  |
| ---------------------- | ------------------------------------ | ------------- | ------------------------------------- |
| **4. Regular Updates** | `4_regular_data_updates_dynamic.php` | Každých 5 min | Aktualizácia cien a zmien             |


## 🔧 **NASTAVENIE V CPANEL:**

### **Denné crony (02:00 NY time):**

```bash
0 2 * * * php /home/username/public_html/cron/1_enhanced_master_cron.php
```

### **5-minútové crony:**

```bash
# Regular data updates (ceny, zmeny)
*/5 * * * * php /home/username/public_html/cron/4_regular_data_updates_dynamic.php
```

## 📈 **VÝHODY NOVEJ ARCHITEKTÚRY:**



## 🎯 **ZÁVER:**

**Nové nastavenie:**

- **Benzinga guidance** - denne (cez master cron)
- **Consensus porovnanie** - integrované do Benzinga guidance (používa estimates z Cron 3)
- **Lepšia synchronizácia** medzi všetkými dátami

**Výsledok:** Presnejšie corporate guidance dáta s consensus porovnaním!
