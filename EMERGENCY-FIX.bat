@echo off
cls
echo.
echo ================================================
echo   EMERGENCY PASSWORD FIX - FreshTrack
echo ================================================
echo.
echo Clearing Laravel cache...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo.
echo Checking and fixing password...
echo.

php check-password-now.php

echo.
echo ================================================
pause
