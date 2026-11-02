@echo off
cls
echo.
echo =============================================
echo    EMERGENCY LOGIN FIX - FreshTrack
echo =============================================
echo.
echo This will fix your login RIGHT NOW.
echo.
pause

php fix-login-now.php

echo.
echo =============================================
echo    DONE! Try logging in now.
echo =============================================
echo.
echo Login at: http://127.0.0.1:8000/login
echo Email: owner@FreshTrack.ph
echo Password: password
echo.
pause
