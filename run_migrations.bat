@echo off
echo ===========================================
echo   CAMBIZZLE API - MIGRATIONS
echo ===========================================
echo.
cd /d "%~dp0"
echo Démarrage des migrations...
echo.
php run_migrations_simple.php
echo.
pause











