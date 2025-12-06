@echo off
REM Script Windows pour tester manuellement l'expiration des boosts
REM Ou pour configurer dans le Planificateur de tâches Windows

echo ========================================
echo Test - Expiration des boosts
echo ========================================
echo.

php expire_boosts.php

echo.
echo ========================================
echo Test termine
echo ========================================
pause
