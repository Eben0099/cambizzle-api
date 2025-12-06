@echo off
echo ===========================================
echo   CAMBIZZLE API - PENDING ADS TEST
echo ===========================================
echo.
echo Ce script va tester la recuperation des
echo annonces en attente pour verifier que
echo l'erreur est corrigee.
echo.
pause

echo.
echo Test de recuperation des annonces en attente...
echo.
cd /d "%~dp0"
php test_pending_ads.php

echo.
echo ===========================================
echo   TEST TERMINE !
echo ===========================================
echo.
echo Si le test est OK, l'erreur API est corrigee.
echo Vous pouvez maintenant utiliser l'endpoint
echo GET /api/admin/ads/pending normalement.
echo.
pause










