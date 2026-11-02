@echo off
echo ========================================
echo FreshTrack System Check
echo ========================================
echo.

echo [1/5] Checking PHP...
php -v
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PHP not found!
    goto :error
)
echo ✓ PHP is installed
echo.

echo [2/5] Checking Composer...
composer --version
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Composer not found!
    goto :error
)
echo ✓ Composer is installed
echo.

echo [3/5] Checking Laravel...
php artisan --version
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Laravel not found!
    goto :error
)
echo ✓ Laravel is installed
echo.

echo [4/5] Testing Database Connection...
php -r "try { $pdo = new PDO('mysql:host=127.0.0.1;dbname=capstone_db', 'root', ''); echo '✓ Database connection OK'; echo PHP_EOL; echo 'Database: capstone_db'; } catch(PDOException $e) { echo '✗ Database connection FAILED'; echo PHP_EOL; echo 'Error: ' . $e->getMessage(); exit(1); }"
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: Cannot connect to database!
    echo.
    echo Please check:
    echo - Is MySQL/MariaDB running? (XAMPP/WAMP/Laragon)
    echo - Does 'capstone_db' database exist?
    echo - Are credentials correct in .env file?
    echo.
    echo Open phpMyAdmin: http://localhost/phpmyadmin
    echo Then run: COMPLETE-SETUP.sql
    goto :error
)
echo.

echo [5/5] Checking Database Tables...
php -r "try { $pdo = new PDO('mysql:host=127.0.0.1;dbname=capstone_db', 'root', ''); $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN); echo '✓ Found ' . count($tables) . ' tables'; echo PHP_EOL; $users = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(); echo '✓ Users table has ' . $users . ' records'; } catch(PDOException $e) { echo '✗ Table check failed: ' . $e->getMessage(); exit(1); }"
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: Database tables not found!
    echo Run these commands:
    echo   php artisan migrate
    echo   mysql -u root capstone_db ^< COMPLETE-SETUP.sql
    goto :error
)
echo.

echo ========================================
echo ✓✓✓ ALL CHECKS PASSED! ✓✓✓
echo ========================================
echo.
echo Your FreshTrack system is ready!
echo.
echo Next steps:
echo 1. Run: php artisan serve
echo 2. Visit: http://127.0.0.1:8000
echo 3. Login with:
echo    Email: owner@FreshTrack.ph
echo    Password: password
echo.
pause
exit /b 0

:error
echo.
echo ========================================
echo ✗✗✗ SYSTEM CHECK FAILED ✗✗✗
echo ========================================
echo.
echo Please fix the errors above and try again.
echo.
echo Need help? Check:
echo - VERIFY-DATABASE.md
echo - BACKEND_INTEGRATION_COMPLETE.md
echo.
pause
exit /b 1
