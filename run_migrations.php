<?php

echo "🚀 Démarrage des migrations...\n";

// Charger l'autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Charger la configuration CodeIgniter
    require_once __DIR__ . '/app/Config/Paths.php';
    require_once __DIR__ . '/system/Boot.php';

    // Initialiser CodeIgniter
    $paths = new \Config\Paths();
    \CodeIgniter\Boot::init($paths);

    // Obtenir la connexion DB
    $db = \Config\Database::connect();

    // Créer le MigrationRunner
    $runner = new \CodeIgniter\Database\MigrationRunner($db);

    // Lancer les migrations
    echo "📦 Exécution des migrations...\n";
    $runner->latest();

    echo "✅ Toutes les migrations ont été exécutées avec succès !\n";

} catch (Exception $e) {
    echo "❌ Erreur lors des migrations : " . $e->getMessage() . "\n";
    echo "📍 Fichier : " . $e->getFile() . " (ligne " . $e->getLine() . ")\n";
    echo "🔍 Stack trace :\n" . $e->getTraceAsString() . "\n";
}