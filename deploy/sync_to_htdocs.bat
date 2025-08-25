@echo off
echo Syncing project to htdocs...
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: This script must be run as Administrator!
    echo Right-click on this file and select "Run as administrator"
    pause
    exit /b 1
)

echo Running as Administrator - OK
echo.

REM Backup existing htdocs version
echo Creating backup of existing htdocs version...
if exist "D:\xampp\htdocs\earnings-table" (
    xcopy "D:\xampp\htdocs\earnings-table" "D:\xampp\htdocs\earnings-table-backup-%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%" /E /I /H /Y
    echo Backup created.
) else (
    echo No existing version found.
)
echo.

REM Remove existing htdocs version
echo Removing existing htdocs version...
if exist "D:\xampp\htdocs\earnings-table" (
    rmdir /S /Q "D:\xampp\htdocs\earnings-table"
    echo Existing version removed.
)
echo.

REM Copy current project to htdocs
echo Copying current project to htdocs...
xcopy "D:\Projects\EarningsTable" "D:\xampp\htdocs\earnings-table" /E /I /H /Y
if %errorLevel% equ 0 (
    echo ✓ Project copied successfully to htdocs
) else (
    echo ✗ Failed to copy project
    pause
    exit /b 1
)
echo.

REM Set permissions
echo Setting permissions...
icacls "D:\xampp\htdocs\earnings-table" /grant "Everyone:(OI)(CI)F" /T
echo Permissions set.
echo.

echo Sync completed!
echo.
echo Project is now available at: http://localhost/earnings-table/
echo API endpoints should work now.
echo.
pause
