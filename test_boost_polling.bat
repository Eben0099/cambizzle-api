@echo off
REM Script de test du polling de paiement boost
REM Usage: test_boost_polling.bat <payment_id> <user_token>

setlocal

if "%~1"=="" (
    echo Usage: test_boost_polling.bat ^<payment_id^> ^<user_token^>
    echo Exemple: test_boost_polling.bat 1 eyJ0eXAiOiJKV1Qi...
    exit /b 1
)

if "%~2"=="" (
    echo Erreur: Token utilisateur manquant
    echo Usage: test_boost_polling.bat ^<payment_id^> ^<user_token^>
    exit /b 1
)

set PAYMENT_ID=%~1
set USER_TOKEN=%~2

echo ========================================
echo Test de polling - Paiement boost
echo ========================================
echo Payment ID: %PAYMENT_ID%
echo Token: %USER_TOKEN:~0,20%...
echo ========================================

php test_boost_payment_polling.php %PAYMENT_ID% %USER_TOKEN%

endlocal
