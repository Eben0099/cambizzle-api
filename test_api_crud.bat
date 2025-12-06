@echo off
echo === TEST API CRUD ADMIN ===
echo Test de creation de categorie avec les champs automatiques

echo.
echo Test 1: Creation d'une categorie
curl -X POST "http://localhost:8080/api/admin/referentials/categories" ^
-H "Content-Type: application/json" ^
-H "Authorization: Bearer YOUR_ADMIN_TOKEN" ^
-d "{\"name\":\"Test Category %TIME%\",\"slug\":\"test-category-%RANDOM%\",\"description\":\"Categorie de test\"}"

echo.
echo.
echo Test 2: Affichage des categories pour verifier les champs automatiques
curl -X GET "http://localhost:8080/api/admin/referentials/categories" ^
-H "Content-Type: application/json" ^
-H "Authorization: Bearer YOUR_ADMIN_TOKEN"

echo.
echo.
echo === Remplacez YOUR_ADMIN_TOKEN par un vrai token d'admin ===
pause