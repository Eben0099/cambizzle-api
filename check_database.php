<?php

echo "🔍 Diagnostic de la base de données Cambizzle...\n\n";

// Configuration de la base de données
$config = require __DIR__ . '/api/database_config.php';
$host = $config['host'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "📊 Connexion à la base de données établie\n";

    // Vérifier les champs de suspension dans la table users
    echo "\n🔍 Vérification des champs de suspension dans la table 'users' :\n";

    $stmt = $pdo->query("DESCRIBE `users`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $requiredColumns = ['is_suspended', 'suspended_at', 'suspended_by', 'suspension_reason', 'unsuspended_at', 'unsuspended_by'];
    $existingColumns = [];

    foreach ($columns as $column) {
        if (in_array($column['Field'], $requiredColumns)) {
            $existingColumns[] = $column['Field'];
            echo "✅ Colonne '{$column['Field']}' existe\n";
        }
    }

    $missingColumns = array_diff($requiredColumns, $existingColumns);
    if (!empty($missingColumns)) {
        echo "\n❌ Colonnes manquantes :\n";
        foreach ($missingColumns as $col) {
            echo "   - $col\n";
        }
        echo "\n💡 Lancez 'php setup_database_simple.php' pour ajouter ces colonnes\n";
    } else {
        echo "\n✅ Toutes les colonnes de suspension sont présentes\n";
    }

    // Vérifier les tables
    echo "\n🏗️ Vérification des tables :\n";

    $tables = ['promotion_packs', 'moderation_logs'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table '$table' existe\n";
            } else {
                echo "❌ Table '$table' n'existe pas\n";
            }
        } catch (Exception $e) {
            echo "⚠️ Impossible de vérifier '$table'\n";
        }
    }

    // Vérifier quelques utilisateurs
    echo "\n👥 Vérification des utilisateurs :\n";

    $stmt = $pdo->query("SELECT id_user, first_name, last_name, is_suspended FROM `users` WHERE deleted IS NULL LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo "❌ Aucun utilisateur trouvé\n";
        echo "💡 Vous devez créer des utilisateurs d'abord\n";
    } else {
        echo "✅ Utilisateurs trouvés :\n";
        foreach ($users as $user) {
            $status = ($user['is_suspended'] ?? 0) ? 'Suspendu' : 'Actif';
            echo "   - ID {$user['id_user']}: {$user['first_name']} {$user['last_name']} ($status)\n";
        }
    }

    // Test d'une suspension
    if (!empty($users)) {
        $testUser = $users[0];
        if (($testUser['is_suspended'] ?? 0) == 0) {
            echo "\n🧪 Test de suspension d'utilisateur :\n";
            echo "   Utilisateur de test : {$testUser['first_name']} {$testUser['last_name']} (ID: {$testUser['id_user']})\n";

            $updateData = [
                'is_suspended' => 1,
                'suspended_at' => date('Y-m-d H:i:s'),
                'suspended_by' => 1,
                'suspension_reason' => 'Test automatique'
            ];

            $stmt = $pdo->prepare("UPDATE `users` SET is_suspended = ?, suspended_at = ?, suspended_by = ?, suspension_reason = ? WHERE id_user = ?");
            $result = $stmt->execute([
                $updateData['is_suspended'],
                $updateData['suspended_at'],
                $updateData['suspended_by'],
                $updateData['suspension_reason'],
                $testUser['id_user']
            ]);

            if ($result) {
                echo "✅ Test réussi : utilisateur suspendu\n";

                // Remettre à l'état initial
                $stmt = $pdo->prepare("UPDATE `users` SET is_suspended = 0, suspended_at = NULL, suspended_by = NULL, suspension_reason = NULL WHERE id_user = ?");
                $stmt->execute([$testUser['id_user']]);
                echo "✅ Test nettoyé : utilisateur remis à l'état actif\n";
            } else {
                echo "❌ Test échoué : impossible de suspendre l'utilisateur\n";
            }
        } else {
            echo "⏭️ Utilisateur déjà suspendu, test ignoré\n";
        }
    }

    echo "\n🎉 Diagnostic terminé !\n";

} catch (Exception $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "\n\n";
    echo "💡 Vérifiez :\n";
    echo "   - Que MySQL est démarré\n";
    echo "   - Les paramètres dans database_config.php\n";
    echo "   - Que la base '$database' existe\n";
}










