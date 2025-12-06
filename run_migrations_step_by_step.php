<?php

echo "🚀 Exécution des migrations Cambizzle étape par étape...\n\n";

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

    echo "📊 Vérification des migrations existantes...\n";

    // Vérifier quelles migrations ont déjà été exécutées
    $migrationsTable = $db->table('migrations');
    $executedMigrations = [];

    if ($migrationsTable) {
        $executedMigrations = $migrationsTable->select('version')->get()->getResultArray();
        $executedMigrations = array_column($executedMigrations, 'version');
    }

    $migrations = [
        '2025-10-11-000001' => 'AddUserSuspensionFields',
        '2025-10-11-000002' => 'CreatePromotionPacksTable',
        '2025-10-11-000003' => 'AddModerationLogsTable'
    ];

    foreach ($migrations as $version => $className) {
        if (in_array($version, $executedMigrations)) {
            echo "⏭️  Migration $className déjà exécutée (version: $version)\n";
            continue;
        }

        echo "📦 Exécution de $className...\n";

        try {
            // Instancier et exécuter la migration manuellement
            $class = "\\App\\Database\\Migrations\\$className";
            $migration = new $class();

            $db->transStart();
            $migration->up();
            $db->transComplete();

            // Enregistrer la migration comme exécutée
            if ($migrationsTable) {
                $db->table('migrations')->insert([
                    'version' => $version,
                    'class' => $class,
                    'group' => 'default',
                    'namespace' => 'App',
                    'time' => time(),
                    'batch' => 1
                ]);
            }

            echo "✅ $className exécutée avec succès\n";

        } catch (Exception $e) {
            echo "❌ Erreur dans $className : " . $e->getMessage() . "\n";
            echo "🔄 Tentative de rollback...\n";

            try {
                $migration->down();
                echo "✅ Rollback réussi\n";
            } catch (Exception $rollbackError) {
                echo "❌ Erreur lors du rollback : " . $rollbackError->getMessage() . "\n";
            }

            exit(1);
        }
    }

    echo "\n🎉 Toutes les migrations ont été exécutées avec succès !\n";
    echo "\n📁 Collection Postman : postman/Cambizzle_API_Complete.postman_collection.json\n";
    echo "🌐 Pour démarrer le serveur : php spark serve\n";

} catch (Exception $e) {
    echo "❌ Erreur générale : " . $e->getMessage() . "\n";
    echo "💡 Vérifiez votre configuration .env et votre base de données.\n";
}











