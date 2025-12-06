<?php
/**
 * Script de test pour le polling automatique de statut de paiement
 * Simule le comportement du frontend qui vérifie le statut toutes les 5 secondes
 * 
 * Usage: php test_boost_payment_polling.php <payment_id> <user_token>
 */

// Configuration
$baseUrl = 'http://localhost:8080';
$paymentId = $argv[1] ?? null;
$userToken = $argv[2] ?? null;

if (!$paymentId || !$userToken) {
    echo "❌ Usage: php test_boost_payment_polling.php <payment_id> <user_token>\n";
    echo "Exemple: php test_boost_payment_polling.php 1 eyJ0eXAiOiJKV1QiLCJhbGc...\n";
    exit(1);
}

$maxAttempts = 60; // 5 minutes max (60 * 5s)
$interval = 5; // secondes
$attempt = 0;

echo "🚀 Démarrage du polling pour le paiement #{$paymentId}\n";
echo "⏱️  Intervalle: {$interval}s | Max tentatives: {$maxAttempts}\n";
echo str_repeat("-", 60) . "\n";

while ($attempt < $maxAttempts) {
    $attempt++;
    
    echo "\n[Tentative {$attempt}/{$maxAttempts}] " . date('H:i:s') . "\n";
    
    // Appel API
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "{$baseUrl}/api/boost/check-payment/{$paymentId}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$userToken}",
            "Content-Type: application/json"
        ],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Erreur cURL: {$error}\n";
        sleep($interval);
        continue;
    }
    
    if ($httpCode !== 200) {
        echo "❌ HTTP {$httpCode}: {$response}\n";
        sleep($interval);
        continue;
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        echo "❌ Réponse JSON invalide\n";
        sleep($interval);
        continue;
    }
    
    $status = $data['status'] ?? 'unknown';
    $message = $data['message'] ?? '';
    $updated = $data['updated'] ?? false;
    
    echo "📊 Statut: {$status} " . ($updated ? '(MIS À JOUR)' : '') . "\n";
    echo "💬 Message: {$message}\n";
    
    if (isset($data['campay'])) {
        $campay = $data['campay'];
        echo "📱 Campay:\n";
        echo "   - Référence: {$campay['reference']}\n";
        echo "   - Statut: {$campay['status']}\n";
        echo "   - Montant: {$campay['amount']} {$campay['currency']}\n";
        echo "   - Opérateur: {$campay['operator']}\n";
        if ($campay['operator_reference']) {
            echo "   - Réf opérateur: {$campay['operator_reference']}\n";
        }
    }
    
    // Gérer les statuts finaux
    if ($status === 'paid') {
        echo "\n✅ SUCCÈS: Paiement confirmé et boost activé!\n";
        
        if (isset($data['ad'])) {
            $ad = $data['ad'];
            echo "\n📢 Annonce boostée:\n";
            echo "   - ID: {$ad['id']}\n";
            echo "   - Slug: {$ad['slug']}\n";
            echo "   - Titre: {$ad['title']}\n";
            echo "   - Boost actif: " . ($ad['is_boosted'] ? 'Oui' : 'Non') . "\n";
            echo "   - Début: {$ad['boost_start']}\n";
            echo "   - Fin: {$ad['boost_end']}\n";
        }
        
        echo "\n✨ Polling terminé avec succès!\n";
        exit(0);
    } elseif ($status === 'failed') {
        echo "\n❌ ÉCHEC: Le paiement a échoué\n";
        echo "💡 Vous pouvez relancer le paiement avec l'endpoint /retry-payment\n";
        exit(1);
    } elseif ($status === 'error') {
        echo "\n❌ ERREUR: {$message}\n";
        exit(1);
    }
    
    // Statut pending, continuer
    echo "⏳ En attente... prochaine vérification dans {$interval}s\n";
    sleep($interval);
}

// Timeout
echo "\n⏱️  TIMEOUT: {$maxAttempts} tentatives atteintes (5 minutes)\n";
echo "💡 Le paiement peut encore être en cours. Vérifiez manuellement:\n";
echo "   curl -H \"Authorization: Bearer {$userToken}\" \\\n";
echo "        {$baseUrl}/api/boost/check-payment/{$paymentId}\n";
exit(2);
