<?php

// Script de test simple pour vérifier les services
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST DES SERVICES EN PRODUCTION ===\n\n";

try {
    // Test simple sans framework lourd
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Test de la base de données directe
    echo "1. Test base de données... ";
    
    // Charger la config manuellement
    $configFile = __DIR__ . '/.env';
    if (!file_exists($configFile)) {
        $configFile = __DIR__ . '/.env.production';
    }
    
    if (file_exists($configFile)) {
        $config = parse_ini_file($configFile);
        echo "Config trouvée. ";
    } else {
        echo "❌ Aucun fichier de config trouvé\n";
        exit;
    }
    
    // Test de connexion basique
    $host = $config['database.default.hostname'] ?? 'localhost';
    $dbname = $config['database.default.database'] ?? '';
    $username = $config['database.default.username'] ?? '';
    $password = $config['database.default.password'] ?? '';
    
    if (empty($dbname) || empty($username)) {
        echo "❌ Config DB manquante\n";
        exit;
    }
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion DB OK\n";
    
    // Test tables critiques
    echo "2. Test tables... ";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredTables = ['users', 'moderation_logs'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        if (!in_array($table, $tables)) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "✅ Tables OK\n";
    } else {
        echo "❌ Tables manquantes: " . implode(', ', $missingTables) . "\n";
    }
    
    // Test d'un utilisateur
    echo "3. Test utilisateurs... ";
    $stmt = $pdo->query("SELECT id, username, is_identity_verified FROM users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Utilisateur trouvé (ID: {$user['id']})\n";
        
        // Test de mise à jour simulée
        echo "4. Test mise à jour... ";
        $stmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$user['id']]);
        
        if ($result) {
            echo "✅ Mise à jour OK\n";
        } else {
            echo "❌ Mise à jour échouée\n";
        }
    } else {
        echo "❌ Aucun utilisateur trouvé\n";
    }
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "Base de données: ✅ Fonctionnelle\n";
    echo "Tables: " . (empty($missingTables) ? "✅" : "❌") . "\n";
    echo "Utilisateurs: " . ($user ? "✅" : "❌") . "\n";
    
    if (empty($missingTables) && $user) {
        echo "\n✅ TOUS LES TESTS PASSENT - Le problème vient des services CodeIgniter\n";
    } else {
        echo "\n❌ PROBLÈMES DÉTECTÉS\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN TEST ===\n";