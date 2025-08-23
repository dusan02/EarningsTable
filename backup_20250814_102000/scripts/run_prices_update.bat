@echo off
cd /d "D:\Projects\EarningsTable"
D:\xampp\php\php.exe cron\current_prices_mcaps_updates.php
echo %date% %time% - Prices update completed >> logs\prices_update.log 