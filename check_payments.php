<?php
/**
 * Script pour vérifier les paiements dans la base de données
 */

require 'vendor/autoload.php';

$db = \Config\Database::connect();

echo "=== PAIEMENTS RÉCENTS ===\n\n";

$query = $db->query('SELECT id, reference, status, amount, phone, payment_method, created_at FROM payments ORDER BY id DESC LIMIT 10');
$payments = $query->getResultArray();

if (empty($payments)) {
    echo "❌ Aucun paiement trouvé dans la base de données.\n";
    exit;
}

foreach ($payments as $payment) {
    echo "ID: {$payment['id']}\n";
    echo "Référence: {$payment['reference']}\n";
    echo "Statut: {$payment['status']}\n";
    echo "Montant: {$payment['amount']} XAF\n";
    echo "Téléphone: {$payment['phone']}\n";
    echo "Méthode: {$payment['payment_method']}\n";
    echo "Créé le: {$payment['created_at']}\n";
    echo str_repeat("-", 60) . "\n";
}

echo "\n=== TEST DE VÉRIFICATION CAMPAY ===\n\n";

// Prendre le dernier paiement
$lastPayment = $payments[0];
$reference = $lastPayment['reference'];

echo "Test avec la référence: {$reference}\n\n";

// Appeler l'API Campay
$token = '31d12e057d6586e46a981b5ee64a1bed3d77974b';
$url = "https://demo.campay.net/api/transaction/{$reference}/";

echo "URL: {$url}\n";
echo "Token: {$token}\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Token {$token}",
        "Content-Type: application/json"
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";

if ($error) {
    echo "❌ Erreur cURL: {$error}\n";
} else {
    echo "Réponse Campay:\n";
    $data = json_decode($response, true);
    print_r($data);
    
    if ($httpCode === 200 && isset($data['status'])) {
        echo "\n✅ Statut Campay: {$data['status']}\n";
    } else {
        echo "\n❌ Référence invalide ou erreur Campay\n";
        echo "💡 Cette référence n'existe peut-être pas dans Campay (démo)\n";
        echo "💡 Il faut faire un vrai paiement via POST /collect/ d'abord\n";
    }
}

echo "\n=== SOLUTION ===\n\n";
echo "Pour tester correctement:\n";
echo "1. Faire un POST vers /api/boost/boost-existing-ad/{slug}\n";
echo "2. Cela créera une transaction Campay avec une vraie référence\n";
echo "3. Ensuite utiliser GET /api/boost/check-payment/{id} pour vérifier\n\n";
echo "Note: Les références dans votre BD sont peut-être des tests manuels\n";
echo "      qui n'ont jamais été envoyés à Campay.\n";
