<?php
/**
 * Script de nettoyage et vérification des paiements
 */

require 'vendor/autoload.php';

$db = \Config\Database::connect();

echo "========================================\n";
echo "   NETTOYAGE DES PAIEMENTS DE TEST\n";
echo "========================================\n\n";

// 1. Supprimer les paiements de test avec anciennes références
echo "1. Suppression des paiements de test...\n";
$db->query("DELETE FROM payments WHERE reference LIKE 'AD_BOOST_%' OR reference LIKE 'TEMP_%'");
echo "   ✓ " . $db->affectedRows() . " paiements supprimés\n\n";

// 2. Réinitialiser les annonces boostées
echo "2. Réinitialisation des annonces boostées...\n";
$db->query("UPDATE ads SET is_boosted = 0, boost_start = NULL, boost_end = NULL WHERE is_boosted = 1");
echo "   ✓ " . $db->affectedRows() . " annonces réinitialisées\n\n";

// 3. Nettoyer les promotions sans référence valide
echo "3. Nettoyage des promotions...\n";
$db->query("DELETE FROM ad_promotions WHERE payment_reference IS NULL OR payment_reference LIKE 'AD_BOOST_%' OR payment_reference LIKE 'TEMP_%'");
echo "   ✓ " . $db->affectedRows() . " promotions supprimées\n\n";

echo "========================================\n";
echo "   VÉRIFICATION DES DONNÉES\n";
echo "========================================\n\n";

// Afficher les paiements restants
$query = $db->query("SELECT id, reference, status, amount, created_at FROM payments ORDER BY id DESC LIMIT 5");
$payments = $query->getResultArray();

echo "Paiements restants: " . count($payments) . "\n";
if (!empty($payments)) {
    foreach ($payments as $payment) {
        echo "  - ID: {$payment['id']} | Ref: {$payment['reference']} | Status: {$payment['status']} | {$payment['created_at']}\n";
    }
} else {
    echo "  ✓ Aucun paiement (BD propre)\n";
}

echo "\n========================================\n";
echo "   NETTOYAGE TERMINÉ!\n";
echo "========================================\n\n";

echo "📝 Instructions pour tester:\n\n";
echo "1. POST /api/boost/boost-existing-ad/{slug}\n";
echo "   Body: {\"pack_id\": 1, \"phone\": \"237690000000\", \"payment_method\": \"mobile_money\"}\n\n";
echo "2. La réponse contiendra:\n";
echo "   - paymentId: ID du paiement en BD\n";
echo "   - reference: Référence Campay (ex: 056768ee-b632-4d91-997f-6adb2c6a7023)\n\n";
echo "3. GET /api/boost/check-payment/{paymentId}\n";
echo "   Répéter toutes les 5 secondes\n\n";
echo "4. La référence en BD sera maintenant celle de Campay ✓\n\n";

echo "🔍 Vérifier une référence Campay:\n";
echo "   curl 'https://demo.campay.net/api/transaction/{reference}/' \\\n";
echo "        -H 'Authorization: Token 31d12e057d6586e46a981b5ee64a1bed3d77974b'\n\n";
