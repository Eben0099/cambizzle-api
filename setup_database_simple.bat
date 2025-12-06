@echo off
echo ===========================================
echo   CAMBIZZLE API - SETUP DATABASE SIMPLE
echo ===========================================
echo.
echo Version simplifiee qui ne depend pas de
echo CodeIgniter pour la configuration.
echo.
echo Assurez-vous que :
echo - Votre base de donnees MySQL est demarree
echo - La base 'cambizzle-api' existe
echo.
echo Si necessaire, modifiez database_config.php
echo pour configurer votre connexion MySQL.
echo.
pause

echo.
echo Execution du script simplifie...
echo.
cd /d "%~dp0"
php setup_database_simple.php

echo.
echo ===========================================
echo   SETUP TERMINE !
echo ===========================================
echo.
echo Les tables suivantes ont ete creees :
echo - Champs de suspension utilisateurs
echo - Table promotion_packs (avec donnees test)
echo - Table moderation_logs
echo.
echo Vous pouvez maintenant :
echo 1. Importer la collection Postman
echo 2. Demarrer le serveur (php spark serve)
echo 3. Tester les endpoints
echo.
pause
