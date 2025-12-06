<?php

/**
 * Script de test pour vérifier le fonctionnement du CRUD admin
 * Test des fonctionnalités de création, lecture, mise à jour et suppression
 * pour les catégories, sous-catégories, filtres, marques et codes de parrainage
 */

// Définir FCPATH pour CodeIgniter
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);

// Charger le bootstrap de CodeIgniter
require_once __DIR__ . '/app/Config/Paths.php';

$paths = new Config\Paths();
require $paths->systemDirectory . '/bootstrap.php';

// Initialiser l'application CodeIgniter
$app = \CodeIgniter\Config\Services::codeigniter();
$app->initialize();

use App\Models\CategoryModel;
use App\Models\SubcategoryModel;
use App\Models\FilterModel;
use App\Models\BrandModel;
use App\Models\ReferralCodeModel;

echo "=== TEST CRUD ADMIN ===\n";
echo "Test des fonctionnalités CRUD pour les référentiels et codes de parrainage\n\n";

// Test des Catégories
echo "📂 TEST CATEGORIES\n";
$categoryModel = new CategoryModel();

// Créer une catégorie de test
$testCategory = [
    'name' => 'Test Catégorie ' . date('Y-m-d H:i:s'),
    'slug' => 'test-categorie-' . time(),
    'description' => 'Catégorie de test créée automatiquement',
    'icon_path' => '/icons/test.svg'
];

$categoryId = $categoryModel->insert($testCategory);
if ($categoryId) {
    echo "✅ Catégorie créée avec ID: $categoryId\n";
    
    // Vérifier que les champs automatiques sont bien renseignés
    $createdCategory = $categoryModel->find($categoryId);
    echo "   - is_active: " . ($createdCategory['is_active'] ? 'true' : 'false') . "\n";
    echo "   - display_order: " . $createdCategory['display_order'] . "\n";
    echo "   - created_at: " . $createdCategory['created_at'] . "\n";
    echo "   - updated_at: " . $createdCategory['updated_at'] . "\n";
    
    // Mettre à jour la catégorie
    $updateResult = $categoryModel->update($categoryId, ['name' => 'Catégorie Modifiée']);
    if ($updateResult) {
        echo "✅ Catégorie mise à jour avec succès\n";
        $updatedCategory = $categoryModel->find($categoryId);
        echo "   - Nouveau nom: " . $updatedCategory['name'] . "\n";
        echo "   - updated_at modifié: " . ($updatedCategory['updated_at'] !== $createdCategory['updated_at'] ? 'true' : 'false') . "\n";
    } else {
        echo "❌ Erreur lors de la mise à jour de la catégorie\n";
    }
} else {
    echo "❌ Erreur lors de la création de la catégorie: " . implode(', ', $categoryModel->errors()) . "\n";
}

// Test des Sous-catégories
echo "\n📁 TEST SOUS-CATEGORIES\n";
$subcategoryModel = new SubcategoryModel();

if ($categoryId) {
    $testSubcategory = [
        'category_id' => $categoryId,
        'name' => 'Test Sous-catégorie ' . date('Y-m-d H:i:s'),
        'slug' => 'test-sous-categorie-' . time(),
        'description' => 'Sous-catégorie de test'
    ];

    $subcategoryId = $subcategoryModel->insert($testSubcategory);
    if ($subcategoryId) {
        echo "✅ Sous-catégorie créée avec ID: $subcategoryId\n";
        
        $createdSubcategory = $subcategoryModel->find($subcategoryId);
        echo "   - is_active: " . ($createdSubcategory['is_active'] ? 'true' : 'false') . "\n";
        echo "   - display_order: " . $createdSubcategory['display_order'] . "\n";
        echo "   - created_at: " . $createdSubcategory['created_at'] . "\n";
    } else {
        echo "❌ Erreur lors de la création de la sous-catégorie: " . implode(', ', $subcategoryModel->errors()) . "\n";
    }
}

// Test des Filtres
echo "\n🔍 TEST FILTRES\n";
$filterModel = new FilterModel();

if (!empty($subcategoryId)) {
    $testFilter = [
        'subcategory_id' => $subcategoryId,
        'name' => 'Test Filtre ' . date('Y-m-d H:i:s'),
        'type' => 'text'
    ];

    $filterId = $filterModel->insert($testFilter);
    if ($filterId) {
        echo "✅ Filtre créé avec ID: $filterId\n";
        
        $createdFilter = $filterModel->find($filterId);
        echo "   - is_required: " . ($createdFilter['is_required'] ? 'true' : 'false') . "\n";
        echo "   - is_active: " . ($createdFilter['is_active'] ? 'true' : 'false') . "\n";
        echo "   - display_order: " . $createdFilter['display_order'] . "\n";
        echo "   - created_at: " . $createdFilter['created_at'] . "\n";
    } else {
        echo "❌ Erreur lors de la création du filtre: " . implode(', ', $filterModel->errors()) . "\n";
    }
}

// Test des Marques
echo "\n🏷️ TEST MARQUES\n";
$brandModel = new BrandModel();

if (!empty($subcategoryId)) {
    $testBrand = [
        'subcategory_id' => $subcategoryId,
        'name' => 'Test Marque ' . date('Y-m-d H:i:s'),
        'description' => 'Marque de test créée automatiquement'
    ];

    $brandId = $brandModel->insert($testBrand);
    if ($brandId) {
        echo "✅ Marque créée avec ID: $brandId\n";
        
        $createdBrand = $brandModel->find($brandId);
        echo "   - is_active: " . ($createdBrand['is_active'] ? 'true' : 'false') . "\n";
        echo "   - created_at: " . $createdBrand['created_at'] . "\n";
    } else {
        echo "❌ Erreur lors de la création de la marque: " . implode(', ', $brandModel->errors()) . "\n";
    }
}

// Test des Codes de Parrainage
echo "\n🎁 TEST CODES DE PARRAINAGE\n";
$referralCodeModel = new ReferralCodeModel();

$testReferralCode = [
    'user_id' => 1, // Assumer qu'il existe un utilisateur avec ID 1
    'description' => 'Code de test créé automatiquement',
    'bonus_amount' => 10.00,
    'max_uses' => 5
];

$referralCodeId = $referralCodeModel->insert($testReferralCode);
if ($referralCodeId) {
    echo "✅ Code de parrainage créé avec ID: $referralCodeId\n";
    
    $createdReferralCode = $referralCodeModel->find($referralCodeId);
    echo "   - code généré: " . $createdReferralCode['code'] . "\n";
    echo "   - is_active: " . ($createdReferralCode['is_active'] ? 'true' : 'false') . "\n";
    echo "   - current_uses: " . $createdReferralCode['current_uses'] . "\n";
    echo "   - created_at: " . $createdReferralCode['created_at'] . "\n";
} else {
    echo "❌ Erreur lors de la création du code de parrainage: " . implode(', ', $referralCodeModel->errors()) . "\n";
}

// Nettoyage : Supprimer les données de test
echo "\n🧹 NETTOYAGE\n";

if (!empty($referralCodeId)) {
    if ($referralCodeModel->delete($referralCodeId)) {
        echo "✅ Code de parrainage de test supprimé\n";
    }
}

if (!empty($brandId)) {
    if ($brandModel->delete($brandId)) {
        echo "✅ Marque de test supprimée\n";
    }
}

if (!empty($filterId)) {
    if ($filterModel->delete($filterId)) {
        echo "✅ Filtre de test supprimé\n";
    }
}

if (!empty($subcategoryId)) {
    if ($subcategoryModel->delete($subcategoryId)) {
        echo "✅ Sous-catégorie de test supprimée\n";
    }
}

if (!empty($categoryId)) {
    if ($categoryModel->delete($categoryId)) {
        echo "✅ Catégorie de test supprimée\n";
    }
}

echo "\n=== FIN DES TESTS ===\n";
echo "Les tests CRUD ont été exécutés. Vérifiez les résultats ci-dessus.\n";

?>