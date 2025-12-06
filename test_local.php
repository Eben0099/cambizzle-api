<?php
/**
 * Script de test local rapide
 * Lancez avec : php test_local.php
 */

echo "=== TEST LOCAL DE L'API CAMBIZZLE ===\n\n";

// 1. Vérifier les fichiers essentiels
echo "1. VÉRIFICATION DES FICHIERS :\n";
$files = [
    'public/index.php' => 'Point d\'entrée',
    'app/Config/Routes.php' => 'Routes API',
    'app/Config/Paths.php' => 'Configuration chemins',
    'vendor/autoload.php' => 'Autoloader Composer'
];

foreach ($files as $file => $desc) {
    if (file_exists($file)) {
        echo "✅ $desc ($file)\n";
    } else {
        echo "❌ $file MANQUANT\n";
    }
}

echo "\n";

// 2. Test des chemins
echo "2. VÉRIFICATION DES CHEMINS :\n";
try {
    require 'app/Config/Paths.php';
    $paths = new Config\Paths();

    $testPaths = [
        'systemDirectory' => $paths->systemDirectory,
        'appDirectory' => $paths->appDirectory,
        'writableDirectory' => $paths->writableDirectory,
    ];

    foreach ($testPaths as $name => $path) {
        if (is_dir($path)) {
            echo "✅ $name : " . basename($path) . "\n";
        } else {
            echo "❌ $name : $path (INEXISTANT)\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erreur de chargement Paths.php : " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Instructions de test
echo "3. TESTS À FAIRE :\n";
echo "🔸 Test serveur local :\n";
echo "   php -S localhost:8080 -t public/\n\n";

echo "🔸 Test des routes :\n";
echo "   curl http://localhost:8080/ads/creation-data\n";
echo "   curl http://localhost:8080/api/ads/creation-data\n\n";

echo "🔸 Test diagnostic :\n";
echo "   curl http://localhost:8080/check_api.php\n\n";

echo "=== FIN DU TEST ===\n";
?>













