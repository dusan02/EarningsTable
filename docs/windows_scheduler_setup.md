# Windows Task Scheduler Setup

## Automatické spúšťanie denných úloh

### Čo sa spúšťa:

1. **02:00 AM (NY time)** - Čistenie starých dát
2. **02:05 AM (NY time)** - Načítanie nových tickerov z Finnhubu

### Manuálne nastavenie Task Scheduler:

#### Krok 1: Otvorte Task Scheduler

- Stlačte `Win + R`
- Zadajte `taskschd.msc`
- Stlačte Enter

#### Krok 2: Vytvorte Cleanup Task

1. Kliknite "Create Basic Task" v pravom paneli
2. Názov: `EarningsTable_DailyCleanup`
3. Popis: `Daily cleanup of old earnings data`
4. Trigger: Daily
5. Start time: 02:00 AM
6. Action: Start a program
7. Program: `D:\xampp\php\php.exe`
8. Arguments: `D:\Projects\EarningsTable\cron\clear_old_data.php`
9. Finish

#### Krok 3: Vytvorte Fetch Task

1. Kliknite "Create Basic Task" v pravom paneli
2. Názov: `EarningsTable_FetchTickers`
3. Popis: `Daily fetch of earnings tickers from Finnhub`
4. Trigger: Daily
5. Start time: 02:05 AM
6. Action: Start a program
7. Program: `D:\xampp\php\php.exe`
8. Arguments: `D:\Projects\EarningsTable\cron\fetch_finnhub_earnings_today_tickers.php`
9. Finish

#### Krok 4: Nastavte práva (voliteľné)

1. Kliknite pravým na task
2. Properties
3. General tab
4. Check "Run with highest privileges"
5. OK

### Alternatívne: Jeden Task pre celú sekvenciu

#### Vytvorte Sequence Task

1. Názov: `EarningsTable_DailySequence`
2. Trigger: Daily at 02:00 AM
3. Program: `D:\Projects\EarningsTable\scripts\run_daily_sequence.bat`

### Kontrola logov:

- Log súbor: `D:\Projects\EarningsTable\storage\daily_run.log`
- Last run state: `D:\Projects\EarningsTable\storage\daily_cleanup_last_run.txt`

### Manuálne spustenie:

```cmd
# Cleanup
D:\xampp\php\php.exe D:\Projects\EarningsTable\cron\clear_old_data.php --force

# Fetch
D:\xampp\php\php.exe D:\Projects\EarningsTable\cron\fetch_finnhub_earnings_today_tickers.php

# Celá sekvencia
D:\Projects\EarningsTable\scripts\run_daily_sequence.bat
```

### Odstránenie taskov:

```cmd
schtasks /delete /tn "EarningsTable_DailyCleanup" /f
schtasks /delete /tn "EarningsTable_FetchTickers" /f
schtasks /delete /tn "EarningsTable_DailySequence" /f
```
