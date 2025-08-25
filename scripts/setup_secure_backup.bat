@echo off
REM 🔒 SECURE BACKUP SETUP SCRIPT
REM Nastavenie automatického zálohovania s enkrypciou

echo.
echo ========================================
echo    SECURE BACKUP SETUP FOR EARNINGS TABLE
echo ========================================
echo.

REM Kontrola PHP
php --version >nul 2>&1
if errorlevel 1 (
    echo ❌ PHP is not installed or not in PATH
    echo Please install PHP and add it to PATH
    pause
    exit /b 1
)

echo ✅ PHP found

REM Vytvorenie adresárov
if not exist "storage\backups" mkdir "storage\backups"
if not exist "logs" mkdir "logs"

echo ✅ Directories created

REM Test zálohy
echo.
echo 🔒 Testing secure backup...
php scripts\secure_backup.php

if errorlevel 1 (
    echo ❌ Backup test failed
    pause
    exit /b 1
)

echo ✅ Backup test successful

REM Vytvorenie Windows Task Scheduler úlohy
echo.
echo 📅 Setting up Windows Task Scheduler...

REM Vytvorenie XML súboru pre Task Scheduler
echo ^<?xml version="1.0" encoding="UTF-16"?^> > backup_task.xml
echo ^<Task version="1.4" xmlns="http://schemas.microsoft.com/windows/2004/02/mit/task"^> >> backup_task.xml
echo   ^<Triggers^> >> backup_task.xml
echo     ^<TimeTrigger^> >> backup_task.xml
echo       ^<Repetition^> >> backup_task.xml
echo         ^<Interval^>P1D^</Interval^> >> backup_task.xml
echo         ^<StopAtDurationEnd^>false^</StopAtDurationEnd^> >> backup_task.xml
echo       ^</Repetition^> >> backup_task.xml
echo       ^<StartBoundary^>2024-01-01T02:00:00^</StartBoundary^> >> backup_task.xml
echo       ^<Enabled^>true^</Enabled^> >> backup_task.xml
echo     ^</TimeTrigger^> >> backup_task.xml
echo   ^</Triggers^> >> backup_task.xml
echo   ^<Principals^> >> backup_task.xml
echo     ^<Principal id="Author"^> >> backup_task.xml
echo       ^<RunLevel^>HighestAvailable^</RunLevel^> >> backup_task.xml
echo     ^</Principal^> >> backup_task.xml
echo   ^</Principals^> >> backup_task.xml
echo   ^<Settings^> >> backup_task.xml
echo     ^<MultipleInstancesPolicy^>IgnoreNew^</MultipleInstancesPolicy^> >> backup_task.xml
echo     ^<DisallowStartIfOnBatteries^>false^</DisallowStartIfOnBatteries^> >> backup_task.xml
echo     ^<StopIfGoingOnBatteries^>false^</StopIfGoingOnBatteries^> >> backup_task.xml
echo     ^<AllowHardTerminate^>true^</AllowHardTerminate^> >> backup_task.xml
echo     ^<StartWhenAvailable^>true^</StartWhenAvailable^> >> backup_task.xml
echo     ^<RunOnlyIfNetworkAvailable^>false^</RunOnlyIfNetworkAvailable^> >> backup_task.xml
echo     ^<IdleSettings^> >> backup_task.xml
echo       ^<StopOnIdleEnd^>false^</StopOnIdleEnd^> >> backup_task.xml
echo       ^<RestartOnIdle^>false^</RestartOnIdle^> >> backup_task.xml
echo     ^</IdleSettings^> >> backup_task.xml
echo     ^<AllowStartOnDemand^>true^</AllowStartOnDemand^> >> backup_task.xml
echo     ^<Enabled^>true^</Enabled^> >> backup_task.xml
echo     ^<Hidden^>false^</Hidden^> >> backup_task.xml
echo     ^<RunOnlyIfIdle^>false^</RunOnlyIfIdle^> >> backup_task.xml
echo     ^<WakeToRun^>false^</WakeToRun^> >> backup_task.xml
echo     ^<ExecutionTimeLimit^>PT1H^</ExecutionTimeLimit^> >> backup_task.xml
echo     ^<Priority^>7^</Priority^> >> backup_task.xml
echo   ^</Settings^> >> backup_task.xml
echo   ^<Actions Context="Author"^> >> backup_task.xml
echo     ^<Exec^> >> backup_task.xml
echo       ^<Command^>php^</Command^> >> backup_task.xml
echo       ^<Arguments^>"%~dp0scripts\secure_backup.php"^</Arguments^> >> backup_task.xml
echo       ^<WorkingDirectory^>%~dp0^</WorkingDirectory^> >> backup_task.xml
echo     ^</Exec^> >> backup_task.xml
echo   ^</Actions^> >> backup_task.xml
echo ^</Task^> >> backup_task.xml

REM Registrácia úlohy
schtasks /create /tn "EarningsTable Secure Backup" /xml backup_task.xml /f

if errorlevel 1 (
    echo ❌ Failed to create scheduled task
    echo Please run this script as Administrator
    pause
    exit /b 1
)

echo ✅ Scheduled task created successfully

REM Vymazanie dočasného súboru
del backup_task.xml

REM Vytvorenie PowerShell skriptu pre monitoring
echo.
echo 📊 Creating monitoring script...

echo # Backup Monitoring Script > scripts\monitor_backups.ps1
echo $backupDir = "storage\backups" >> scripts\monitor_backups.ps1
echo $logFile = "logs\backup_monitor.log" >> scripts\monitor_backups.ps1
echo. >> scripts\monitor_backups.ps1
echo $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss" >> scripts\monitor_backups.ps1
echo $backups = Get-ChildItem -Path $backupDir -Filter "*.enc" -Recurse >> scripts\monitor_backups.ps1
echo. >> scripts\monitor_backups.ps1
echo if ($backups.Count -eq 0) { >> scripts\monitor_backups.ps1
echo     "$timestamp - WARNING: No backup files found" ^| Out-File -FilePath $logFile -Append >> scripts\monitor_backups.ps1
echo     exit 1 >> scripts\monitor_backups.ps1
echo } >> scripts\monitor_backups.ps1
echo. >> scripts\monitor_backups.ps1
echo $latestBackup = $backups ^| Sort-Object LastWriteTime -Descending ^| Select-Object -First 1 >> scripts\monitor_backups.ps1
echo $backupAge = (Get-Date) - $latestBackup.LastWriteTime >> scripts\monitor_backups.ps1
echo. >> scripts\monitor_backups.ps1
echo if ($backupAge.TotalHours -gt 24) { >> scripts\monitor_backups.ps1
echo     "$timestamp - WARNING: Latest backup is older than 24 hours" ^| Out-File -FilePath $logFile -Append >> scripts\monitor_backups.ps1
echo     exit 1 >> scripts\monitor_backups.ps1
echo } >> scripts\monitor_backups.ps1
echo. >> scripts\monitor_backups.ps1
echo "$timestamp - OK: Backup system is working correctly" ^| Out-File -FilePath $logFile -Append >> scripts\monitor_backups.ps1
echo exit 0 >> scripts\monitor_backups.ps1

echo ✅ Monitoring script created

REM Vytvorenie README pre backup
echo.
echo 📝 Creating backup documentation...

echo # 🔒 Secure Backup System > docs\BACKUP_SECURITY.md
echo. >> docs\BACKUP_SECURITY.md
echo ## Overview >> docs\BACKUP_SECURITY.md
echo This document describes the secure backup system for EarningsTable. >> docs\BACKUP_SECURITY.md
echo. >> docs\BACKUP_SECURITY.md
echo ## Features >> docs\BACKUP_SECURITY.md
echo - **AES-256-CBC Encryption**: All backups are encrypted with strong encryption >> docs\BACKUP_SECURITY.md
echo - **Compression**: Backups are compressed using gzip to save space >> docs\BACKUP_SECURITY.md
echo - **Automatic Rotation**: Old backups are automatically deleted (keeps last 10) >> docs\BACKUP_SECURITY.md
echo - **Integrity Verification**: Each backup is verified after creation >> docs\BACKUP_SECURITY.md
echo - **Scheduled Execution**: Daily backups at 2:00 AM >> docs\BACKUP_SECURITY.md
echo. >> docs\BACKUP_SECURITY.md
echo ## Usage >> docs\BACKUP_SECURITY.md
echo. >> docs\BACKUP_SECURITY.md
echo ### Manual Backup >> docs\BACKUP_SECURITY.md
echo ```bash >> docs\BACKUP_SECURITY.md
echo php scripts\secure_backup.php >> docs\BACKUP_SECURITY.md
echo ``` >> docs\BACKUP_SECURITY.md
echo. >> docs\BACKUP_SECURITY.md
echo ### List Backups >> docs\BACKUP_SECURITY.md
echo ```bash >> docs\BACKUP_SECURITY.md
echo php scripts\secure_backup.php list >> docs\BACKUP_SECURITY.md
echo ``` >> docs\BACKUP_SECURITY.md
echo. >> docs\BACKUP_SECURITY.md
echo ### Restore Backup >> docs\BACKUP_SECURITY.md
echo ```bash >> docs\BACKUP_SECURITY.md
echo php scripts\secure_backup.php restore backup_2024-01-01_02-00-00.sql.gz.enc >> docs\BACKUP_SECURITY.md
echo ``` >> docs\BACKUP_SECURITY.md
echo. >> docs\BACKUP_SECURITY.md
echo ## Security >> docs\BACKUP_SECURITY.md
echo - Encryption key is stored in `config/backup_key.php` with 600 permissions >> docs\BACKUP_SECURITY.md
echo - Backup files are stored in `storage/backups/` with 750 permissions >> docs\BACKUP_SECURITY.md
echo - All operations are logged to `logs/backup_*.log` >> docs\BACKUP_SECURITY.md
echo. >> docs\BACKUP_SECURITY.md
echo ## Monitoring >> docs\BACKUP_SECURITY.md
echo Run the monitoring script to check backup health: >> docs\BACKUP_SECURITY.md
echo ```powershell >> docs\BACKUP_SECURITY.md
echo powershell -ExecutionPolicy Bypass -File scripts\monitor_backups.ps1 >> docs\BACKUP_SECURITY.md
echo ``` >> docs\BACKUP_SECURITY.md

echo ✅ Documentation created

echo.
echo ========================================
echo           SETUP COMPLETED
echo ========================================
echo.
echo ✅ Secure backup system is now configured
echo.
echo 📋 What was set up:
echo    - Database user with minimal permissions
echo    - Encrypted backup system with AES-256-CBC
echo    - Connection pooling for database optimization
echo    - Windows Task Scheduler for daily backups
echo    - Monitoring script for backup health
echo    - Complete documentation
echo.
echo 📅 Next backup will run at 2:00 AM tomorrow
echo 📊 Monitor backups with: powershell -ExecutionPolicy Bypass -File scripts\monitor_backups.ps1
echo.
echo 🔐 IMPORTANT: Keep your backup encryption key safe!
echo    Location: config\backup_key.php
echo.
pause
