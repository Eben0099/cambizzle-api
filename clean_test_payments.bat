@echo off
REM Script pour nettoyer les paiements de test et recommencer

echo ========================================
echo Nettoyage des paiements de test
echo ========================================
echo.

php -r "require 'vendor/autoload.php'; $db = \Config\Database::connect(); $db->query('DELETE FROM payments WHERE reference LIKE \"AD_BOOST_%%\" OR reference LIKE \"TEMP_%%\"'); echo 'Paiements de test supprimés : ' . $db->affectedRows() . PHP_EOL; $db->query('UPDATE ads SET is_boosted = 0, boost_start = NULL, boost_end = NULL WHERE is_boosted = 1'); echo 'Annonces réinitialisées : ' . $db->affectedRows() . PHP_EOL; $db->query('DELETE FROM ad_promotions WHERE payment_reference IS NULL OR payment_reference LIKE \"AD_BOOST_%%\"'); echo 'Promotions nettoyées : ' . $db->affectedRows() . PHP_EOL;"

echo.
echo ========================================
echo Nettoyage terminé!
echo ========================================
echo.
echo Vous pouvez maintenant tester:
echo 1. POST /api/boost/boost-existing-ad/{slug}
echo 2. Notez la reference Campay dans la reponse
echo 3. GET /api/boost/check-payment/{payment_id}
echo.

pause
