@echo off
cd /d "D:\Projects\EarningsTable"
D:\xampp\php\php.exe cron\fetch_earnings_tickers.php
echo %date% %time% - Earnings fetch completed >> logs\earnings_fetch.log 