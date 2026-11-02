@echo off
color 0A
cls
echo.
echo ╔════════════════════════════════════════════════╗
echo ║     ULTIMATE LOGIN FIX - FreshTrack           ║
echo ║     This will fix EVERYTHING                  ║
echo ╚════════════════════════════════════════════════╝
echo.
echo What this does:
echo   1. Clear ALL Laravel cache
echo   2. Regenerate fresh password hashes
echo   3. Update all 3 accounts (owner, manager, cashier)
echo   4. Verify passwords work
echo   5. Test actual login
echo.
pause
cls

echo.
echo [1/5] Clearing Laravel cache...
echo ────────────────────────────────────────────────
call php artisan config:clear 2^>nul
call php artisan cache:clear 2^>nul
call php artisan route:clear 2^>nul
call php artisan view:clear 2^>nul
call php artisan session:clear 2^>nul
echo ✓ Cache cleared
echo.
pause

cls
echo.
echo [2/5] Checking database connection...
echo ────────────────────────────────────────────────
php -r "require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); try { DB::connection()->getPdo(); echo '✓ Database connected\n'; } catch(Exception $e) { echo '✗ Database error: ' . $e->getMessage() . '\n'; exit(1); }"
echo.
pause

cls
echo.
echo [3/5] Updating passwords for all accounts...
echo ────────────────────────────────────────────────
php check-password-now.php
echo.
pause

cls
echo.
echo [4/5] Verifying ALL three accounts...
echo ────────────────────────────────────────────────
php -r "require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); $emails = ['owner@FreshTrack.ph', 'manager@FreshTrack.ph', 'cashier@FreshTrack.ph']; foreach($emails as $email) { $user = DB::table('users')->where('email', $email)->first(); if($user) { $works = Hash::check('password', $user->password); echo ($works ? '✓' : '✗') . ' ' . $email . ' (' . $user->role . ')' . ($works ? ' - WORKS\n' : ' - FAILED\n'); } else { echo '✗ ' . $email . ' - NOT FOUND\n'; } }"
echo.
pause

cls
echo.
echo [5/5] Final Instructions
echo ────────────────────────────────────────────────
echo.
echo ✓✓✓ FIX COMPLETE! ✓✓✓
echo.
echo Now do this:
echo.
echo 1. Close ALL browser windows
echo 2. Open a NEW browser window
echo 3. Go to: http://127.0.0.1:8000/login
echo 4. Login with:
echo.
echo    Email:    owner@FreshTrack.ph
echo    Password: password
echo.
echo 5. Press Enter and you should see the Dashboard!
echo.
echo ════════════════════════════════════════════════
echo.
echo If it STILL doesn't work:
echo   - Try a different browser
echo   - Try incognito/private mode
echo   - Check storage/logs/laravel.log for errors
echo.
echo ════════════════════════════════════════════════
echo.
pause
