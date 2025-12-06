@echo off
echo ===========================================
echo   CAMBIZZLE API - DATABASE DIAGNOSTIC
echo ===========================================
echo.
echo Ce script va diagnostiquer l'etat de votre
echo base de donnees Cambizzle.
echo.
pause

echo.
echo Execution du diagnostic...
echo.
cd /d "%~dp0"
php check_database.php

echo.
pause










