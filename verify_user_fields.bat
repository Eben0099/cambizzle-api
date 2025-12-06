@echo off
echo ===========================================
echo   CAMBIZZLE API - USER FIELDS VERIFICATION
echo ===========================================
echo.
echo Ce script va verifier les champs de suspension
echo dans la table users et les ajouter si necessaire.
echo.
pause

echo.
echo Verification et ajout des champs utilisateurs...
echo.
cd /d "%~dp0"
php verify_user_fields.php

echo.
echo ===========================================
echo   VERIFICATION TERMINEE !
echo ===========================================
echo.
echo Si tout est OK, vous pouvez maintenant :
echo 1. Tester la suspension d'utilisateur
echo 2. Utiliser l'API normalement
echo.
pause










