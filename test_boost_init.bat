@echo off
REM Script pour tester l'initialisation d'un boost
REM Usage: test_boost_init.bat

setlocal

echo ========================================
echo Test d'initialisation de boost
echo ========================================
echo.

REM Variables à configurer
set API_URL=http://localhost:8080
set USER_TOKEN=YOUR_TOKEN_HERE
set AD_SLUG=titre-mis-a-jour-2-1759660959

echo API URL: %API_URL%
echo Token: %USER_TOKEN:~0,20%...
echo Ad Slug: %AD_SLUG%
echo.
echo ========================================
echo.

REM Test POST boost
echo [1/2] Initialisation du boost...
curl -X POST "%API_URL%/api/boost/boost-existing-ad/%AD_SLUG%" ^
  -H "Authorization: Bearer %USER_TOKEN%" ^
  -H "Content-Type: application/json" ^
  -d "{\"pack_id\": 1, \"phone\": \"237699028745\", \"payment_method\": \"orange_money\"}"

echo.
echo.
echo ========================================
echo.

REM Si succès, demander le payment_id pour vérifier
set /p PAYMENT_ID="Entrez le payment_id reçu (ou appuyez sur Entrée pour ignorer): "

if "%PAYMENT_ID%"=="" (
    echo Test terminé.
    exit /b 0
)

echo.
echo [2/2] Vérification du statut...
curl "%API_URL%/api/boost/check-payment/%PAYMENT_ID%" ^
  -H "Authorization: Bearer %USER_TOKEN%"

echo.
echo.
echo ========================================
echo Test terminé
echo ========================================

endlocal
