<?php
/**
 * Script pour corriger les icon_path des catégories et sous-catégories
 * Convertit les chemins absolus et les chemins sans dossier en chemins relatifs corrects
 */

require_once __DIR__ . '/vendor/autoload.php';

// Charger l'environnement
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configuration base de données
$host = $_ENV['DB_HOST'] ?? 'localhost';
$database = $_ENV['DB_NAME'] ?? 'cambizzle';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connexion à la base de données réussie.\n\n";
    
    // ===== CORRECTION DES CATÉGORIES =====
    echo "=== CORRECTION DES CATÉGORIES ===\n";
    
    // Récupérer toutes les catégories avec icon_path
    $stmt = $pdo->query("SELECT id, name, icon_path FROM categories WHERE icon_path IS NOT NULL AND icon_path != ''");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $categoriesUpdated = 0;
    
    foreach ($categories as $category) {
        $oldPath = $category['icon_path'];
        $newPath = $oldPath;
        
        // Cas 1: URL absolue (http://localhost:8080/api/uploads/categories/xxx)
        if (strpos($oldPath, 'http') === 0 && strpos($oldPath, '/api/uploads/categories/') !== false) {
            $pos = strpos($oldPath, '/uploads/categories/');
            $newPath = substr($oldPath, $pos);
        }
        // Cas 2: Chemin avec /api/ (localhost:8080/api/uploads/categories/xxx sans http)
        elseif (strpos($oldPath, '/api/uploads/categories/') !== false) {
            $pos = strpos($oldPath, '/uploads/categories/');
            $newPath = substr($oldPath, $pos);
        }
        // Cas 3: Déjà correct (/uploads/categories/xxx)
        elseif (strpos($oldPath, '/uploads/categories/') === 0) {
            continue; // Déjà correct
        }
        // Cas 4: Juste le nom de fichier ou /uploads/xxx (sans le dossier categories)
        elseif (strpos($oldPath, '/uploads/categories/') === false) {
            $filename = basename($oldPath);
            $newPath = '/uploads/categories/' . $filename;
        }
        
        if ($oldPath !== $newPath) {
            $updateStmt = $pdo->prepare("UPDATE categories SET icon_path = ? WHERE id = ?");
            $updateStmt->execute([$newPath, $category['id']]);
            $categoriesUpdated++;
            echo "✅ Catégorie #{$category['id']} '{$category['name']}'\n";
            echo "   Ancien: $oldPath\n";
            echo "   Nouveau: $newPath\n\n";
        }
    }
    
    echo "Catégories mises à jour: $categoriesUpdated / " . count($categories) . "\n\n";
    
    // ===== CORRECTION DES SOUS-CATÉGORIES =====
    echo "=== CORRECTION DES SOUS-CATÉGORIES ===\n";
    
    // Récupérer toutes les sous-catégories avec icon_path
    $stmt = $pdo->query("SELECT id, name, icon_path FROM subcategories WHERE icon_path IS NOT NULL AND icon_path != ''");
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $subcategoriesUpdated = 0;
    
    foreach ($subcategories as $subcategory) {
        $oldPath = $subcategory['icon_path'];
        $newPath = $oldPath;
        
        // Cas 1: URL absolue (http://localhost:8080/api/uploads/subcategories/xxx)
        if (strpos($oldPath, 'http') === 0 && strpos($oldPath, '/api/uploads/subcategories/') !== false) {
            $pos = strpos($oldPath, '/uploads/subcategories/');
            $newPath = substr($oldPath, $pos);
        }
        // Cas 2: Chemin avec /api/ (localhost:8080/api/uploads/subcategories/xxx sans http)
        elseif (strpos($oldPath, '/api/uploads/subcategories/') !== false) {
            $pos = strpos($oldPath, '/uploads/subcategories/');
            $newPath = substr($oldPath, $pos);
        }
        // Cas 3: Déjà correct (/uploads/subcategories/xxx)
        elseif (strpos($oldPath, '/uploads/subcategories/') === 0) {
            continue; // Déjà correct
        }
        // Cas 4: Juste le nom de fichier ou /uploads/xxx (sans le dossier subcategories)
        elseif (strpos($oldPath, '/uploads/subcategories/') === false) {
            $filename = basename($oldPath);
            $newPath = '/uploads/subcategories/' . $filename;
        }
        
        if ($oldPath !== $newPath) {
            $updateStmt = $pdo->prepare("UPDATE subcategories SET icon_path = ? WHERE id = ?");
            $updateStmt->execute([$newPath, $subcategory['id']]);
            $subcategoriesUpdated++;
            echo "✅ Sous-catégorie #{$subcategory['id']} '{$subcategory['name']}'\n";
            echo "   Ancien: $oldPath\n";
            echo "   Nouveau: $newPath\n\n";
        }
    }
    
    echo "Sous-catégories mises à jour: $subcategoriesUpdated / " . count($subcategories) . "\n\n";
    
    // ===== RÉSUMÉ =====
    echo "=== RÉSUMÉ ===\n";
    echo "✅ Catégories corrigées: $categoriesUpdated\n";
    echo "✅ Sous-catégories corrigées: $subcategoriesUpdated\n";
    echo "✅ Total: " . ($categoriesUpdated + $subcategoriesUpdated) . " entrées corrigées\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
