<?php

echo "🧪 Test de récupération des annonces en attente...\n\n";

try {
    // Charger l'autoloader
    require_once __DIR__ . '/api/vendor/autoload.php';

    // Initialiser CodeIgniter
    require_once __DIR__ . '/api/app/Config/Paths.php';
    require_once __DIR__ . '/api/system/Boot.php';

    $paths = new \Config\Paths();
    \CodeIgniter\Boot::init($paths);

    // Connexion DB
    $db = \Config\Database::connect();

    echo "📊 Connexion à la base de données établie\n";

    // Tester la récupération des annonces en attente
    echo "\n🔍 Test de récupération des annonces en attente...\n";

    $adModel = new \App\Models\AdModel();
    $ads = $adModel->where('moderation_status', 'pending')
                   ->orderBy('created_at', 'DESC')
                   ->findAll();

    echo "✅ Requête exécutée avec succès\n";
    echo "📊 Nombre d'annonces trouvées : " . count($ads) . "\n";

    if (!empty($ads)) {
        echo "\n📋 Détails des annonces :\n";
        foreach ($ads as $index => $ad) {
            echo "   " . ($index + 1) . ". ID {$ad['id']}: {$ad['title']}\n";
            echo "      - Prix: " . ($ad['price'] ?? 'N/A') . "\n";
            echo "      - Prix original: " . ($ad['original_price'] ?? 'N/A') . "\n";
            echo "      - Remise: " . ($ad['discount_percentage'] ?? 'N/A') . "%\n";
            echo "      - Marque ID: " . ($ad['brand_id'] ?? 'N/A') . "\n";
            echo "      - Modérateur ID: " . ($ad['moderator_id'] ?? 'N/A') . "\n";
            echo "      - Créé le: {$ad['created_at']}\n\n";
        }
    } else {
        echo "\nℹ️ Aucune annonce en attente trouvée\n";
        echo "💡 Créez d'abord des annonces via l'API pour les tester\n";
    }

    // Tester la récupération de toutes les annonces
    echo "\n🔍 Test de récupération de toutes les annonces...\n";

    $allAds = $adModel->findAll();
    echo "✅ Requête exécutée avec succès\n";
    echo "📊 Nombre total d'annonces : " . count($allAds) . "\n";

    if (!empty($allAds)) {
        // Compter par statut de modération
        $stats = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0
        ];

        foreach ($allAds as $ad) {
            $status = $ad['moderation_status'] ?? 'unknown';
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        echo "\n📊 Statistiques par statut :\n";
        echo "   - En attente: {$stats['pending']}\n";
        echo "   - Approuvées: {$stats['approved']}\n";
        echo "   - Rejetées: {$stats['rejected']}\n";
    }

    echo "\n🎉 Test terminé avec succès !\n";
    echo "💡 L'API devrait maintenant fonctionner correctement\n";

} catch (Exception $e) {
    echo "❌ Erreur lors du test : " . $e->getMessage() . "\n";
    echo "🔍 Détails de l'erreur :\n";
    echo $e->getTraceAsString() . "\n";
}










