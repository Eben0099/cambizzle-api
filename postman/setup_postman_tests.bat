@echo off
echo ===========================================
echo   CAMBIZZLE API - POSTMAN SETUP
echo ===========================================
echo.
echo Ce script va :
echo 1. Executer les migrations de base de donnees
echo 2. Demarrer le serveur de developpement
echo 3. Vous guider pour importer la collection Postman
echo.
echo Assurez-vous que :
echo - Votre base de donnees MySQL/MariaDB est demarree
echo - Le fichier .env est configure avec vos parametres DB
echo - PHP et Composer sont installes
echo.
pause

echo.
echo ===========================================
echo   ETAPE 1: Installation des dependances
echo ===========================================
composer install

echo.
echo ===========================================
echo   ETAPE 2: Execution des migrations
echo ===========================================
echo Les migrations suivantes seront executees :
echo - AddUserSuspensionFields.php
echo - CreatePromotionPacksTable.php
echo - AddModerationLogsTable.php
echo.
php spark migrate

echo.
echo ===========================================
echo   ETAPE 3: Demarrage du serveur
echo ===========================================
echo Le serveur va demarrer sur http://localhost:8080
echo.
echo Dans un nouveau terminal, executez :
echo php spark serve
echo.
echo Puis ouvrez Postman et importez :
echo - Cambizzle_API_Complete.postman_collection.json
echo - Cambizzle_Environment.postman_environment.json
echo.
echo Variables a configurer dans Postman :
echo - base_url: http://localhost:8080
echo.
pause

echo.
echo ===========================================
echo   SETUP TERMINE !
echo ===========================================
echo.
echo Documentation disponible dans :
echo - postman/README.md
echo - postman/API_Endpoints_Reference.md
echo - postman/Test_Data_Examples.json
echo.
echo Bonne utilisation de l'API Cambizzle !
echo.
