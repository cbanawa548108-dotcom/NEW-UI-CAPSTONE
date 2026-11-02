@echo off
echo === FreshTrack RBAC Setup ===
echo.

echo Step 1: Running migrations...
php artisan migrate --force
if errorlevel 1 (
    echo Migration failed! Check your database connection.
    pause
    exit /b 1
)
echo.

echo Step 2: Creating demo accounts...
php setup-accounts.php
if errorlevel 1 (
    echo Account creation failed!
    pause
    exit /b 1
)
echo.

echo Step 3: Clearing cache...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
echo.

echo === All Done! ===
echo.
pause
