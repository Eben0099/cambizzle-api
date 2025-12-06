@echo off
echo =====================================================
echo    PREPARATION AUTOMATIQUE DEPLOIEMENT CAMBIZZLE
echo =====================================================
echo.

:: Vérifier si nous sommes dans le bon répertoire
if not exist "deploy\api" (
    echo ERREUR: Veuillez executer ce script depuis la racine du projet
    echo Le dossier deploy\api doit exister
    pause
    exit /b 1
)

echo [1/6] Verification de l'environnement...
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ATTENTION: PHP n'est pas trouve dans le PATH
    echo Assurez-vous que PHP est installe
) else (
    echo ✅ PHP trouve
)

where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo ATTENTION: Composer n'est pas trouve dans le PATH
    echo L'installation des dependances devra etre faite manuellement
) else (
    echo ✅ Composer trouve
)

echo.
echo [2/6] Installation des dependances Composer...
cd deploy\api
if exist composer.json (
    composer install --no-dev --optimize-autoloader 2>nul
    if %errorlevel% eq 0 (
        echo ✅ Dependances installees
    ) else (
        echo ⚠️ Probleme avec Composer - verifiez manuellement
    )
) else (
    echo ⚠️ composer.json non trouve
)
cd ..\..

echo.
echo [3/6] Verification des permissions et dossiers...
if not exist "deploy\api\writable\cache" mkdir "deploy\api\writable\cache"
if not exist "deploy\api\writable\logs" mkdir "deploy\api\writable\logs" 
if not exist "deploy\api\writable\uploads" mkdir "deploy\api\writable\uploads"
if not exist "deploy\api\public\uploads" mkdir "deploy\api\public\uploads"
echo ✅ Dossiers crees

echo.
echo [4/6] Copie du template d'environnement...
if exist "deploy\api\env_template.txt" (
    copy "deploy\api\env_template.txt" "deploy\api\.env.template" >nul
    echo ✅ Template .env pret
) else (
    echo ⚠️ Template environnement non trouve
)

echo.
echo [5/6] Verification des fichiers critiques...
set "missing_files="
if not exist "deploy\api\app\Config\Database.php" set "missing_files=%missing_files% Database.php"
if not exist "deploy\api\app\Config\JWT.php" set "missing_files=%missing_files% JWT.php"
if not exist "deploy\api\app\Config\Cors.php" set "missing_files=%missing_files% Cors.php"
if not exist "deploy\api\public\index.php" set "missing_files=%missing_files% index.php"
if not exist "deploy\api\public\.htaccess" set "missing_files=%missing_files% .htaccess"

if "%missing_files%"=="" (
    echo ✅ Tous les fichiers critiques sont presents
) else (
    echo ❌ Fichiers manquants:%missing_files%
)

echo.
echo [6/6] Creation du ZIP de deploiement...
if exist "deploy\cambizzle-api-production.zip" del "deploy\cambizzle-api-production.zip"
cd deploy
powershell -command "Compress-Archive -Path 'api\*' -DestinationPath 'cambizzle-api-production.zip' -Force"
if %errorlevel% eq 0 (
    echo ✅ Archive ZIP creee: deploy\cambizzle-api-production.zip
) else (
    echo ⚠️ Probleme creation ZIP - faites-le manuellement
)
cd ..

echo.
echo =====================================================
echo                    PREPARATION TERMINEE
echo =====================================================
echo.
echo PROCHAINES ETAPES:
echo 1. Uploadez le contenu de deploy\api\ sur votre serveur
echo 2. Configurez le fichier .env avec vos vraies donnees
echo 3. Executez: php generate_production_keys.php
echo 4. Testez: votre-domaine.com/api/verification_complete.php
echo.
echo FICHIERS PRETS DANS: deploy\api\
echo ARCHIVE ZIP: deploy\cambizzle-api-production.zip
echo.
echo Appuyez sur une touche pour continuer...
pause >nul