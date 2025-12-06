<?php

// Test simple de création d'annonce avec des filtres multiples
require_once __DIR__ . '/vendor/autoload.php';

echo "=== TEST GESTION FILTRES MULTIPLES ===\n\n";

// Simuler les données problématiques des logs
$testData = [
    'filters' => [
        '6' => 'Other',
        '7' => 'Local Used', 
        '154' => ['4X4', 'Airbags'],  // Valeur problématique
        '531' => '2500'
    ]
];

echo "Données de test:\n";
print_r($testData);

echo "\nTraitement des filtres:\n";

$filtersFound = [];

if (isset($testData['filters']) && is_array($testData['filters'])) {
    foreach ($testData['filters'] as $filterId => $value) {
        if (is_numeric($filterId) && !empty($value)) {
            if (is_array($value)) {
                // Traitement des valeurs multiples
                $processedValue = implode(',', array_map('trim', $value));
                $filtersFound[$filterId] = $processedValue;
                echo "✅ Filtre $filterId (array): " . json_encode($value) . " → '$processedValue'\n";
            } else {
                $filtersFound[$filterId] = $value;
                echo "✅ Filtre $filterId (string): '$value'\n";
            }
        }
    }
}

echo "\nRésultat final:\n";
print_r($filtersFound);

echo "\nTest de l'insertion en base (simulation):\n";
foreach ($filtersFound as $filterId => $value) {
    $filterData = [
        'ad_id' => 999,
        'filter_id' => (int) $filterId,
        'value' => is_array($value) ? implode(',', array_map('trim', $value)) : trim((string) $value)
    ];
    
    echo "Filtre $filterId: " . json_encode($filterData) . "\n";
}

echo "\n=== TEST TERMINÉ ===\n";