@echo off
REM Script de préparation complète pour le déploiement Windows
REM À exécuter avant l'upload sur LWS Panel

echo === PRÉPARATION DU DÉPLOIEMENT CAMBIZZLE ===
echo API + Frontend React sur www.cambizzle.seed-innov.com
echo.

REM 1. Nettoyage des fichiers de développement
echo 1. Nettoyage des fichiers de développement...
php clean_for_production.php

REM 2. Création de la structure de déploiement
echo 2. Création de la structure de déploiement...
if not exist deploy mkdir deploy
if not exist deploy\api mkdir deploy\api
if not exist deploy\root mkdir deploy\root

REM 3. Copie des fichiers API vers le dossier de déploiement
echo 3. Préparation des fichiers API...
xcopy /E /I app deploy\api\app
xcopy /E /I system deploy\api\system
xcopy /E /I vendor deploy\api\vendor
xcopy /E /I writable deploy\api\writable
xcopy /E /I public deploy\api\public
copy composer.json deploy\api\
copy composer.lock deploy\api\
copy spark deploy\api\
copy .env.production deploy\api\.env
copy .htaccess.api deploy\api\.htaccess

REM 4. Préparation des fichiers pour la racine
echo 4. Préparation des fichiers racine...
copy .htaccess.root deploy\root\.htaccess
copy generate_production_keys.php deploy\api\
copy deployment_check.php deploy\api\

REM 5. Instructions finales
echo.
echo === STRUCTURE DE DÉPLOIEMENT CRÉÉE ===
echo.
echo 📁 deploy\
echo ├── 📁 root\ (à uploader à la racine www\)
echo │   └── .htaccess
echo └── 📁 api\ (à uploader dans www\api\)
echo     ├── app\
echo     ├── system\
echo     ├── vendor\
echo     ├── writable\
echo     ├── public\
echo     ├── .env
echo     ├── .htaccess
echo     └── scripts utilitaires
echo.
echo PROCHAINES ÉTAPES :
echo 1. Compilez votre frontend React (npm run build)
echo 2. Uploadez le contenu de build\ à la racine www\
echo 3. Uploadez deploy\root\.htaccess à la racine www\
echo 4. Uploadez deploy\api\ dans www\api\
echo 5. Configurez la base de données dans www\api\.env
echo 6. Exécutez php www\api\generate_production_keys.php
echo 7. Testez : www.cambizzle.seed-innov.com et www.cambizzle.seed-innov.com\api\
echo.
echo ✅ Préparation terminée !
pause
