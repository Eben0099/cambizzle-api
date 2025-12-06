<?php
/**
 * Cron Job - Désactiver les boosts expirés
 * À exécuter toutes les heures ou toutes les 5 minutes
 * 
 * Commande cron Linux:
 * */5 * * * * php /path/to/cambizzle-api/expire_boosts.php >> /path/to/logs/cron.log 2>&1
 * 
 * Commande Windows Task Scheduler:
 * Action: php.exe
 * Arguments: C:\path\to\cambizzle-api\expire_boosts.php
 * Trigger: Repeat every 5 minutes
 */

require 'vendor/autoload.php';

use App\Models\AdModel;
use App\Models\AdPromotionModel;

$db = \Config\Database::connect();

echo "[" . date('Y-m-d H:i:s') . "] Démarrage du cron - Expiration des boosts\n";

try {
    $adModel = new AdModel();
    $adPromotionModel = new AdPromotionModel();
    
    // 1. Trouver les annonces dont le boost a expiré
    $expiredAds = $adModel->where('is_boosted', 1)
                          ->where('boost_end <', date('Y-m-d H:i:s'))
                          ->findAll();
    
    $count = count($expiredAds);
    echo "Nombre d'annonces avec boost expiré: {$count}\n";
    
    if ($count === 0) {
        echo "Aucun boost à expirer.\n";
        exit(0);
    }
    
    // 2. Désactiver le boost pour chaque annonce
    foreach ($expiredAds as $ad) {
        echo "\n[Annonce #{$ad['id']}] {$ad['title']}\n";
        echo "  - Boost expiré le: {$ad['boost_end']}\n";
        
        // Mettre à jour l'annonce
        $adModel->update($ad['id'], [
            'is_boosted' => 0,
            'boost_start' => null,
            'boost_end' => null
        ]);
        
        echo "  ✓ Annonce désactivée\n";
        
        // Désactiver les promotions associées
        $adPromotionModel->where('ad_id', $ad['id'])
                        ->where('is_active', 1)
                        ->where('expires_at <', date('Y-m-d H:i:s'))
                        ->set(['is_active' => 0])
                        ->update();
        
        echo "  ✓ Promotions désactivées\n";
        
        // Log pour audit
        log_message('info', "Boost expiré pour l'annonce #{$ad['id']} - {$ad['title']}");
    }
    
    echo "\n========================================\n";
    echo "✅ Cron terminé avec succès\n";
    echo "   {$count} boost(s) expiré(s)\n";
    echo "========================================\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    log_message('error', '[CRON EXPIRE_BOOSTS] ' . $e->getMessage());
    exit(1);
}
