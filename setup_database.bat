@echo off
echo ===========================================
echo   CAMBIZZLE API - SETUP DATABASE
echo ===========================================
echo.
echo Ce script va creer directement les tables
echo necessaires pour l'API Cambizzle.
echo.
echo Assurez-vous que :
echo - Votre base de donnees MySQL est demarree
echo - Le fichier .env est configure
echo.
pause

echo.
echo Execution du script de creation des tables...
echo.
cd /d "%~dp0"
php create_tables_directly.php

echo.
echo ===========================================
echo   SETUP TERMINE !
echo ===========================================
echo.
echo Les tables suivantes ont ete creees :
echo - Champs de suspension utilisateurs
echo - Table promotion_packs
echo - Table moderation_logs
echo.
echo Vous pouvez maintenant :
echo 1. Importer la collection Postman
echo 2. Demarrer le serveur (php spark serve)
echo 3. Tester les endpoints
echo.
pause










