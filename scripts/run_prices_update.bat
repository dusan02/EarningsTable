@echo off
cd /d "D:\Projects\EarningsTable"
D:\xampp\php\php.exe cron\fetch_polygon_batch_earnings.php
echo %date% %time% - Prices update completed >> logs\prices_update.log 