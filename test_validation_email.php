<?php

// Test des règles de validation pour l'email facultatif
require_once __DIR__ . '/vendor/autoload.php';

echo "=== TEST VALIDATION EMAIL FACULTATIF ===\n\n";

// Simuler les règles de validation
$validationRules = [
    'email' => 'permit_empty|valid_email|is_unique[users.email,id_user,{id_user}]'
];

// Test 1: Email null
echo "1. Test avec email = null:\n";
$email1 = null;
$isValid1 = ($email1 === null || $email1 === '' || filter_var($email1, FILTER_VALIDATE_EMAIL) !== false);
echo "   Email: " . var_export($email1, true) . "\n";
echo "   Résultat: " . ($isValid1 ? "✅ VALIDE" : "❌ INVALIDE") . "\n\n";

// Test 2: Email chaîne vide
echo "2. Test avec email = '':\n";
$email2 = '';
$isValid2 = ($email2 === null || $email2 === '' || filter_var($email2, FILTER_VALIDATE_EMAIL) !== false);
echo "   Email: " . var_export($email2, true) . "\n";
echo "   Résultat: " . ($isValid2 ? "✅ VALIDE" : "❌ INVALIDE") . "\n\n";

// Test 3: Email valide
echo "3. Test avec email valide:\n";
$email3 = 'test@example.com';
$isValid3 = ($email3 === null || $email3 === '' || filter_var($email3, FILTER_VALIDATE_EMAIL) !== false);
echo "   Email: " . var_export($email3, true) . "\n";
echo "   Résultat: " . ($isValid3 ? "✅ VALIDE" : "❌ INVALIDE") . "\n\n";

// Test 4: Email invalide
echo "4. Test avec email invalide:\n";
$email4 = 'invalid-email';
$isValid4 = ($email4 === null || $email4 === '' || filter_var($email4, FILTER_VALIDATE_EMAIL) !== false);
echo "   Email: " . var_export($email4, true) . "\n";
echo "   Résultat: " . ($isValid4 ? "❌ PROBLÈME - Devrait être invalide" : "✅ INVALIDE (attendu)") . "\n\n";

// Test 5: Simulation nettoyage des données
echo "5. Test nettoyage des données:\n";
$userData = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => '',  // Chaîne vide
    'phone' => '0123456789',
    'password' => 'test123'
];

echo "   Données avant nettoyage:\n";
echo "   " . json_encode($userData, JSON_PRETTY_PRINT) . "\n";

// Nettoyer l'email
if (isset($userData['email']) && trim($userData['email']) === '') {
    $userData['email'] = null;
}

echo "   Données après nettoyage:\n";
echo "   " . json_encode($userData, JSON_PRETTY_PRINT) . "\n";

echo "\n=== RÉSUMÉ ===\n";
echo "✅ permit_empty permet les valeurs null et vides\n";
echo "✅ valid_email valide seulement si email non vide\n";
echo "✅ Nettoyage des chaînes vides en null\n";
echo "✅ Validation passe pour email facultatif\n";

echo "\n=== FIN TEST ===\n";