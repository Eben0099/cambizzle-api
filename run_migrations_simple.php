<?php

echo "🚀 Démarrage des migrations Cambizzle...\n";

try {
    // Charger l'autoloader
    require_once __DIR__ . '/vendor/autoload.php';

    // Initialiser CodeIgniter
    require_once __DIR__ . '/app/Config/Paths.php';
    require_once __DIR__ . '/system/Boot.php';

    $paths = new \Config\Paths();
    \CodeIgniter\Boot::init($paths);

    // Connexion DB
    $db = \Config\Database::connect();

    // MigrationRunner
    $runner = new \CodeIgniter\Database\MigrationRunner($db);

    echo "📦 Exécution des migrations...\n";

    // Lancer les migrations
    $runner->latest();

    echo "✅ Migrations terminées avec succès !\n";
    echo "\n🎉 Votre API Cambizzle est maintenant prête !\n";
    echo "📁 Collection Postman : postman/Cambizzle_API_Complete.postman_collection.json\n";
    echo "🌐 Serveur : php spark serve (port 8080)\n";

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "💡 Vérifiez votre configuration .env et votre base de données.\n";
}











