@echo off
cls
echo.
echo ================================================
echo   FreshTrack - Emergency Password Reset
echo ================================================
echo.
echo This will reset all passwords to: password
echo.
pause
echo.

php reset-passwords-now.php

echo.
echo ================================================
echo   ALL DONE! Try logging in now.
echo ================================================
echo.
pause
