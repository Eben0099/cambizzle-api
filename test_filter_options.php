<?php
/**
 * Script de test pour vérifier la gestion des options de filtres
 * 
 * Ce script teste la création d'un filtre avec ses options
 */

// Configuration de base
$baseUrl = 'http://localhost:8080/api'; // Ajustez selon votre configuration
$adminToken = 'YOUR_ADMIN_TOKEN_HERE'; // Remplacez par un vrai token admin

// Données de test
$filterData = [
    "subcategory_id" => 5,
    "name" => "Couleur",
    "type" => "select",
    "is_required" => true,
    "display_order" => 1,
    "is_active" => true,
    "options" => [
        [
            "value" => "Rouge",
            "display_order" => 1,
            "is_active" => true
        ],
        [
            "value" => "Bleu", 
            "display_order" => 2,
            "is_active" => true
        ],
        [
            "value" => "Vert",
            "display_order" => 3,
            "is_active" => true
        ]
    ]
];

function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

echo "=== TEST DE CRÉATION DE FILTRE AVEC OPTIONS ===\n\n";

// 1. Créer le filtre avec options
echo "1. Création du filtre avec options...\n";
$response = makeRequest(
    $baseUrl . '/admin/referentials/filters',
    'POST',
    $filterData,
    $adminToken
);

echo "Code HTTP: " . $response['code'] . "\n";
echo "Réponse: " . json_encode($response['data'], JSON_PRETTY_PRINT) . "\n\n";

if ($response['code'] === 201 && isset($response['data']['data']['id'])) {
    $filterId = $response['data']['data']['id'];
    echo "✅ Filtre créé avec succès (ID: $filterId)\n\n";
    
    // 2. Vérifier que les options ont été créées
    echo "2. Vérification des options créées...\n";
    $optionsResponse = makeRequest(
        $baseUrl . "/admin/referentials/filter-options/$filterId",
        'GET',
        null,
        $adminToken
    );
    
    echo "Code HTTP: " . $optionsResponse['code'] . "\n";
    echo "Options récupérées: " . json_encode($optionsResponse['data'], JSON_PRETTY_PRINT) . "\n\n";
    
    // 3. Tester la récupération du filtre complet
    echo "3. Récupération du filtre complet avec options...\n";
    $filterResponse = makeRequest(
        $baseUrl . "/admin/referentials/filters/$filterId",
        'GET',
        null,
        $adminToken
    );
    
    echo "Code HTTP: " . $filterResponse['code'] . "\n";
    echo "Filtre complet: " . json_encode($filterResponse['data'], JSON_PRETTY_PRINT) . "\n\n";
    
    // 4. Test de mise à jour avec nouvelles options
    echo "4. Test de mise à jour avec nouvelles options...\n";
    $updateData = $filterData;
    $updateData['name'] = 'Couleur mise à jour';
    $updateData['options'] = [
        [
            "value" => "Rouge",
            "display_order" => 1,
            "is_active" => true
        ],
        [
            "value" => "Bleu",
            "display_order" => 2, 
            "is_active" => true
        ],
        [
            "value" => "Noir",
            "display_order" => 3,
            "is_active" => true
        ],
        [
            "value" => "Blanc",
            "display_order" => 4,
            "is_active" => true
        ]
    ];
    
    $updateResponse = makeRequest(
        $baseUrl . "/admin/referentials/filters/$filterId",
        'PUT',
        $updateData,
        $adminToken
    );
    
    echo "Code HTTP: " . $updateResponse['code'] . "\n";
    echo "Filtre mis à jour: " . json_encode($updateResponse['data'], JSON_PRETTY_PRINT) . "\n\n";
    
    // 5. Nettoyage - Supprimer le filtre de test
    echo "5. Nettoyage - Suppression du filtre de test...\n";
    $deleteResponse = makeRequest(
        $baseUrl . "/admin/referentials/filters/$filterId",
        'DELETE',
        null,
        $adminToken
    );
    
    echo "Code HTTP: " . $deleteResponse['code'] . "\n";
    echo "Réponse suppression: " . json_encode($deleteResponse['data'], JSON_PRETTY_PRINT) . "\n\n";
    
} else {
    echo "❌ Erreur lors de la création du filtre\n";
    echo "Vérifiez que:\n";
    echo "- Le serveur API est démarré\n";
    echo "- Le token admin est valide\n";
    echo "- La sous-catégorie ID 5 existe\n";
}

echo "=== FIN DU TEST ===\n";
?>








