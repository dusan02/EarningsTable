# Import Cron Úloh do Task Scheduler

## Rýchle riešenie - Import XML súborov

Vytvoril som pre vás XML súbory, ktoré môžete priamo importovať do Task Scheduler.

### Krok 1: Otvorte Task Scheduler

- Stlačte `Win + R`
- Napíšte `taskschd.msc`
- Stlačte Enter

### Krok 2: Importujte úlohy

Pre každú úlohu:

1. Kliknite pravým tlačidlom na "Task Scheduler Library"
2. Vyberte "Import Task..."
3. Prejdite do priečinka `D:\Projects\EarningsTable\tasks\`
4. Vyberte príslušný XML súbor:

**Úlohy na import:**

- `EarningsTable_FetchTickers.xml` - denné spúšťanie o 02:15
- `EarningsTable_UpdatePrices.xml` - každých 5 minút
- `EarningsTable_UpdateEPS.xml` - každých 5 minút
- `EarningsTable_CacheShares.xml` - denné spúšťanie o 06:00

### Krok 3: Potvrďte import

- Kliknite "OK" na import
- Ak sa zobrazí dialóg pre heslo, zadajte svoje heslo

## Alternatívne riešenie - Batch súbor

Ak preferujete automatické vytvorenie:

1. Kliknite pravým tlačidlom na `create_cron_tasks.bat`
2. Vyberte "Spustiť ako správca"
3. Počkajte na dokončenie

## Kontrola úloh

Po importe skontrolujte:

1. V Task Scheduler by ste mali vidieť 4 nové úlohy
2. Stav by mal byť "Ready"
3. Next Run Time by malo byť nastavené

## Testovanie

Pre testovanie:

1. Kliknite pravým tlačidlom na úlohu
2. Vyberte "Run"
3. Skontrolujte logy v `logs\` priečinku

## Vytvorené súbory

- `create_cron_tasks.bat` - automatický skript
- `tasks/EarningsTable_FetchTickers.xml` - XML pre import
- `tasks/EarningsTable_UpdatePrices.xml` - XML pre import
- `tasks/EarningsTable_UpdateEPS.xml` - XML pre import
- `tasks/EarningsTable_CacheShares.xml` - XML pre import

## Poznámky

- Všetky úlohy majú nastavené **Working Directory** na `D:\Projects\EarningsTable`
- Používajú PHP z `D:\xampp\php\php.exe`
- Majú najvyššie práva (Run with highest privileges)
- Spúšťajú sa bez ohľadu na prihlásenie používateľa
