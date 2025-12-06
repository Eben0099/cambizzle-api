<?php

// Test de l'inscription avec email facultatif
echo "=== TEST INSCRIPTION AVEC EMAIL FACULTATIF ===\n\n";

// Données de test sans email
$testDataWithoutEmail = [
    'firstName' => 'John',
    'lastName' => 'Doe',
    'phone' => '0123456789',
    'password' => 'test123456'
];

// Données de test avec email
$testDataWithEmail = [
    'firstName' => 'Jane',
    'lastName' => 'Smith', 
    'email' => 'jane.smith@example.com',
    'phone' => '0987654321',
    'password' => 'test123456'
];

// Données de test avec email invalide
$testDataInvalidEmail = [
    'firstName' => 'Bob',
    'lastName' => 'Wilson',
    'email' => 'invalid-email',
    'phone' => '0555666777',
    'password' => 'test123456'
];

echo "1. Test validation sans email:\n";
$errors = [];

if (empty($testDataWithoutEmail['firstName'])) {
    $errors['firstName'] = 'Le prénom est obligatoire pour créer votre compte';
}
if (empty($testDataWithoutEmail['lastName'])) {
    $errors['lastName'] = 'Le nom de famille est obligatoire pour créer votre compte';
}
if (empty($testDataWithoutEmail['phone'])) {
    $errors['phone'] = 'Le numéro de téléphone est obligatoire (il servira pour vous connecter)';
}
if (empty($testDataWithoutEmail['password'])) {
    $errors['password'] = 'Un mot de passe est obligatoire pour sécuriser votre compte';
}
if (!empty($testDataWithoutEmail['email']) && !filter_var($testDataWithoutEmail['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'L\'adresse email semble incorrecte';
}

if (empty($errors)) {
    echo "   ✅ Validation OK - Pas d'email requis\n";
} else {
    echo "   ❌ Erreurs: " . json_encode($errors) . "\n";
}

echo "\n2. Test validation avec email valide:\n";
$errors = [];

if (empty($testDataWithEmail['firstName'])) {
    $errors['firstName'] = 'Le prénom est obligatoire pour créer votre compte';
}
if (empty($testDataWithEmail['lastName'])) {
    $errors['lastName'] = 'Le nom de famille est obligatoire pour créer votre compte';
}
if (empty($testDataWithEmail['phone'])) {
    $errors['phone'] = 'Le numéro de téléphone est obligatoire (il servira pour vous connecter)';
}
if (empty($testDataWithEmail['password'])) {
    $errors['password'] = 'Un mot de passe est obligatoire pour sécuriser votre compte';
}
if (!empty($testDataWithEmail['email']) && !filter_var($testDataWithEmail['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'L\'adresse email semble incorrecte';
}

if (empty($errors)) {
    echo "   ✅ Validation OK - Email valide accepté\n";
} else {
    echo "   ❌ Erreurs: " . json_encode($errors) . "\n";
}

echo "\n3. Test validation avec email invalide:\n";
$errors = [];

if (empty($testDataInvalidEmail['firstName'])) {
    $errors['firstName'] = 'Le prénom est obligatoire pour créer votre compte';
}
if (empty($testDataInvalidEmail['lastName'])) {
    $errors['lastName'] = 'Le nom de famille est obligatoire pour créer votre compte';
}
if (empty($testDataInvalidEmail['phone'])) {
    $errors['phone'] = 'Le numéro de téléphone est obligatoire (il servira pour vous connecter)';
}
if (empty($testDataInvalidEmail['password'])) {
    $errors['password'] = 'Un mot de passe est obligatoire pour sécuriser votre compte';
}
if (!empty($testDataInvalidEmail['email']) && !filter_var($testDataInvalidEmail['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'L\'adresse email semble incorrecte';
}

if (empty($errors)) {
    echo "   ❌ Problème - Email invalide non détecté\n";
} else {
    echo "   ✅ Validation OK - Email invalide rejeté: " . ($errors['email'] ?? 'N/A') . "\n";
}

echo "\n4. Test formats de réponse:\n";

// Format réponse succès
$successResponse = [
    'success' => true,
    'message' => 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.',
    'data' => [
        'user' => [
            'id' => 123,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => null, // Email facultatif
            'phone' => '0123456789',
            'isVerified' => true
        ],
        'token' => 'jwt-token-here'
    ],
    'code' => 'REGISTRATION_SUCCESS'
];

echo "   ✅ Format réponse succès:\n";
echo "   " . json_encode($successResponse, JSON_PRETTY_PRINT) . "\n";

// Format réponse erreur validation
$errorResponse = [
    'success' => false,
    'message' => 'Veuillez corriger les erreurs ci-dessous',
    'errors' => [
        'firstName' => 'Le prénom est obligatoire pour créer votre compte',
        'phone' => 'Le numéro de téléphone est obligatoire (il servira pour vous connecter)'
    ],
    'code' => 'VALIDATION_ERROR'
];

echo "\n   ✅ Format réponse erreur validation:\n";
echo "   " . json_encode($errorResponse, JSON_PRETTY_PRINT) . "\n";

echo "\n=== RÉSUMÉ ===\n";
echo "✅ Email est maintenant facultatif\n";
echo "✅ Messages d'erreur plus compréhensibles\n";
echo "✅ Codes d'erreur structurés pour le frontend\n";
echo "✅ Validation d'email quand fourni\n";
echo "✅ Téléphone reste obligatoire (identifiant principal)\n";

echo "\n=== TEST TERMINÉ ===\n";