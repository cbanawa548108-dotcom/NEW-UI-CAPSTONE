@echo off
cls
color 0A
echo.
echo ========================================================
echo          FreshTrack Login Fix - FINAL VERSION
echo ========================================================
echo.
echo This will:
echo   1. Run diagnostics
echo   2. Reset all passwords to "password"
echo   3. Verify everything works
echo.
echo ========================================================
pause
cls

echo.
echo [STEP 1/3] Running diagnostics...
echo ========================================================
php diagnose-auth.php
echo.
echo ========================================================
echo Diagnostics complete. Press any key for password reset...
pause >nul
cls

echo.
echo [STEP 2/3] Resetting passwords...
echo ========================================================
php reset-passwords-now.php
echo.
echo ========================================================
echo Passwords reset. Press any key for final verification...
pause >nul
cls

echo.
echo [STEP 3/3] Final verification...
echo ========================================================
php diagnose-auth.php
echo.

echo.
echo ========================================================
echo                    ALL DONE!
echo ========================================================
echo.
echo You can now login at: http://127.0.0.1:8000/login
echo.
echo Demo Accounts:
echo   - owner@FreshTrack.ph    / password
echo   - manager@FreshTrack.ph  / password
echo   - cashier@FreshTrack.ph  / password
echo.
echo ========================================================
echo.
pause
