@echo off
REM Script de test pour la réinitialisation de mot de passe
REM Usage: test_password_reset.bat

setlocal enabledelayedexpansion

set BASE_URL=http://localhost:8000
set PHONE=+237677123456
set NEW_PASSWORD=newPassword123

echo.
echo ===============================================
echo Test - Reinitialisation de Mot de Passe
echo ===============================================
echo.

REM Test 1: Forgot Password
echo 1. Testing forgot-password endpoint...

for /f "tokens=*" %%a in (
  'powershell -Command "Invoke-WebRequest -Uri '!BASE_URL!/api/auth/forgot-password -Method POST -Headers @{\"Content-Type\"=\"application/json\"} -Body '{\"phone\": \"!PHONE!\"}'| ConvertTo-Json"'
) do set FORGOT_RESPONSE=%%a

echo Response:
echo !FORGOT_RESPONSE!
echo.

REM Extract token using PowerShell
for /f "delims=" %%a in (
  'powershell -Command "$json = '!FORGOT_RESPONSE! | ConvertFrom-Json; $json.data.token"'
) do set RESET_TOKEN=%%a

for /f "delims=" %%a in (
  'powershell -Command "$json = '!FORGOT_RESPONSE! | ConvertFrom-Json; $json.data.code"'
) do set RESET_CODE=%%a

if "!RESET_TOKEN!"=="" (
  echo ERROR: No reset token in response!
  exit /b 1
)

echo Token de reset: !RESET_TOKEN:~0,20!...
echo Code (dev only): !RESET_CODE!
echo.

REM Test 2: Reset Password with valid token
echo 2. Testing reset-password endpoint with valid token...

for /f "tokens=*" %%a in (
  'powershell -Command "Invoke-WebRequest -Uri '!BASE_URL!/api/auth/reset-password -Method POST -Headers @{\"Content-Type\"=\"application/json\"} -Body '{\"token\": \"!RESET_TOKEN!\", \"password\": \"!NEW_PASSWORD!\"}'| ConvertTo-Json"'
) do set RESET_RESPONSE=%%a

echo Response:
echo !RESET_RESPONSE!
echo.

echo Test de reinitialisation de mot de passe complete!
echo.
echo Note: Pour les tests complets, utilisez Postman avec la collection:
echo postman/PASSWORD_RESET_COLLECTION.json
echo.
