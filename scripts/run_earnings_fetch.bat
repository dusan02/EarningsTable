@echo off
cd /d "D:\Projects\EarningsTable"
D:\xampp\php\php.exe cron\fetch_finnhub_earnings_today_tickers.php
echo %date% %time% - Earnings fetch completed >> logs\earnings_fetch.log 