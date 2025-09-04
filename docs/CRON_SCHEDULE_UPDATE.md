# ⏰ Cron Schedule Update - Benzinga 5-Minute Updates

## 🚀 **NOVÁ ARCHITEKTÚRA CRONOV**

### **DENNÉ CRONY (02:00 NY time):**

| Cron                     | Súbor                                | Frekvencia | Účel                                       |
| ------------------------ | ------------------------------------ | ---------- | ------------------------------------------ |
| **1. Master**            | `1_enhanced_master_cron.php`         | Denné      | Orchestrácia všetkých cronov               |
| **2. Clear Data**        | `2_clear_old_data.php`               | Denné      | Čistenie starých dát                       |
| **3. Daily Setup**       | `3_daily_data_setup_static.php`      | Denné      | Statické dáta (tickery, market cap)        |
| **4. Regular Updates**   | `4_regular_data_updates_dynamic.php` | Denné      | Dynamické dáta (ceny, zmeny)               |
| **5. Benzinga Guidance** | `5_benzinga_guidance_updates.php`    | Denné      | Corporate guidance dáta                    |
| **6. Consensus**         | `6_estimates_consensus_updates.php`  | Denné      | Estimates consensus (analyst estimates)    |

### **5-MINÚTOVÉ CRONY:**

| Cron                   | Súbor                                | Frekvencia    | Účel                                  |
| ---------------------- | ------------------------------------ | ------------- | ------------------------------------- |
| **4. Regular Updates** | `4_regular_data_updates_dynamic.php` | Každých 5 min | Aktualizácia cien a zmien             |
| **5. Benzinga 5min**   | `5_benzinga_guidance_5min.php`       | Každých 5 min | **NOVÝ** - Corporate guidance updates |
| **6. Consensus 5min**  | `6_estimates_consensus_updates.php`  | Každých 5 min | **NOVÝ** - Analyst estimates updates   |


## 🔧 **NASTAVENIE V CPANEL:**

### **Denné crony (02:00 NY time):**

```bash
0 2 * * * php /home/username/public_html/cron/1_enhanced_master_cron.php
```

### **5-minútové crony:**

```bash
# Regular data updates (ceny, zmeny) + Estimates consensus (analyst estimates)
*/5 * * * * php /home/username/public_html/cron/4_regular_data_updates_dynamic.php

# Benzinga guidance updates (NOVÝ)
*/5 * * * * php /home/username/public_html/cron/5_benzinga_guidance_5min.php
```

## 📈 **VÝHODY NOVEJ ARCHITEKTÚRY:**

### **Benzinga 5-minútové updates:**

- ✅ **Aktuálnejšie guidance** - každých 5 minút namiesto denne
- ✅ **Lepšia synchronizácia** s trhovými dátami
- ✅ **Rýchlejšie odhalenie** zmen v corporate guidance


## 🎯 **ZÁVER:**

**Nové nastavenie:**

- **Benzinga guidance** - každých 5 minút (namiesto denne)
- **Analyst estimates** - každých 5 minút (nová funkcionalita)
- **Lepšia synchronizácia** medzi všetkými dátami

**Výsledok:** Aktuálnejšie a presnejšie corporate guidance dáta s analyst estimates!
