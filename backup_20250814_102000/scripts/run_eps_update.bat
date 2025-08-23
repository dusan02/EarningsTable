@echo off
cd /d "D:\Projects\EarningsTable"
D:\xampp\php\php.exe cron\update_earnings_eps_revenues.php
echo %date% %time% - EPS update completed >> logs\eps_update.log 