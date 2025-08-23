@echo off
cd /d "D:\Projects\EarningsTable"
D:\xampp\php\php.exe cron\update_finnhub_data_5min.php
echo %date% %time% - EPS update completed >> logs\eps_update.log 