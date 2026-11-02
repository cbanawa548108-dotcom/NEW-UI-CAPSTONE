@echo off
echo Clearing Laravel caches...
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo.
echo Done! Now restart your development server:
echo php artisan serve
pause
