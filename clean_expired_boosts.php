<?php
/**
 * Script pour nettoyer automatiquement les boosts expirés
 * À exécuter via CRON toutes les heures ou quotidiennement
 * 
 * Usage:
 * php clean_expired_boosts.php
 * 
 * CRON example (toutes les heures):
 * 0 * * * * cd /path/to/api && php clean_expired_boosts.php
 */

// Charger le framework CodeIgniter
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap CodeIgniter
$app = require_once FCPATH . '../app/Config/Paths.php';
$app = new \CodeIgniter\Config\Services();

// Connexion à la base de données
$db = \Config\Database::connect();

try {
    echo "[" . date('Y-m-d H:i:s') . "] Début du nettoyage des boosts expirés...\n";
    
    // Compter les annonces avec boost expiré
    $countQuery = $db->query("
        SELECT COUNT(*) as count 
        FROM ads 
        WHERE is_boosted = 1 
        AND boost_end < NOW()
    ");
    $result = $countQuery->getRow();
    $expiredCount = $result->count;
    
    if ($expiredCount > 0) {
        echo "   → Trouvé {$expiredCount} annonce(s) avec boost expiré\n";
        
        // Réinitialiser les boosts expirés
        $updateQuery = $db->query("
            UPDATE ads 
            SET is_boosted = 0 
            WHERE is_boosted = 1 
            AND boost_end < NOW()
        ");
        
        echo "   → {$expiredCount} annonce(s) nettoyée(s) avec succès\n";
        echo "[" . date('Y-m-d H:i:s') . "] ✓ Nettoyage terminé avec succès\n";
    } else {
        echo "   → Aucun boost expiré trouvé\n";
        echo "[" . date('Y-m-d H:i:s') . "] ✓ Nettoyage terminé (aucune action nécessaire)\n";
    }
    
    exit(0);
    
} catch (\Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ✗ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
