<?php

echo "🔍 Vérification des champs utilisateurs...\n\n";

try {
    // Charger la configuration
    $config = require __DIR__ . '/api/database_config.php';
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
                   $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "📊 Connexion réussie\n";

    // Vérifier la structure de la table users
    echo "\n🏗️ Structure de la table 'users' :\n";

    $stmt = $pdo->query("DESCRIBE `users`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $suspensionFields = ['is_suspended', 'suspended_at', 'suspended_by', 'suspension_reason', 'unsuspended_at', 'unsuspended_by'];

    echo "Champs existants :\n";
    foreach ($columns as $column) {
        $field = $column['Field'];
        $type = $column['Type'];
        $null = $column['Null'];
        $default = $column['Default'] ?? 'NULL';

        $marker = in_array($field, $suspensionFields) ? "✅" : "  ";
        echo "   $marker $field ($type) NULL:$null DEFAULT:$default\n";
    }

    // Vérifier quels champs de suspension sont manquants
    $existingSuspensionFields = array_intersect(array_column($columns, 'Field'), $suspensionFields);
    $missingSuspensionFields = array_diff($suspensionFields, $existingSuspensionFields);

    if (!empty($missingSuspensionFields)) {
        echo "\n❌ Champs de suspension manquants :\n";
        foreach ($missingSuspensionFields as $field) {
            echo "   - $field\n";
        }

        echo "\n🔧 Ajout des champs manquants...\n";

        // Ajouter les champs manquants un par un
        $fieldDefinitions = [
            'is_suspended' => "ALTER TABLE `users` ADD COLUMN `is_suspended` TINYINT(1) DEFAULT 0 AFTER `deleted`",
            'suspended_at' => "ALTER TABLE `users` ADD COLUMN `suspended_at` DATETIME NULL AFTER `is_suspended`",
            'suspended_by' => "ALTER TABLE `users` ADD COLUMN `suspended_by` INT(11) UNSIGNED NULL AFTER `suspended_at`",
            'suspension_reason' => "ALTER TABLE `users` ADD COLUMN `suspension_reason` TEXT NULL AFTER `suspended_by`",
            'unsuspended_at' => "ALTER TABLE `users` ADD COLUMN `unsuspended_at` DATETIME NULL AFTER `suspension_reason`",
            'unsuspended_by' => "ALTER TABLE `users` ADD COLUMN `unsuspended_by` INT(11) UNSIGNED NULL AFTER `unsuspended_at`"
        ];

        foreach ($missingSuspensionFields as $field) {
            if (isset($fieldDefinitions[$field])) {
                try {
                    $pdo->exec($fieldDefinitions[$field]);
                    echo "✅ Champ '$field' ajouté\n";
                } catch (Exception $e) {
                    echo "❌ Erreur ajout '$field': " . $e->getMessage() . "\n";
                }
            }
        }

        // Ajouter les indexes
        echo "\n🔧 Ajout des indexes...\n";
        $indexes = [
            "ALTER TABLE `users` ADD KEY `users_is_suspended_idx` (`is_suspended`)",
            "ALTER TABLE `users` ADD KEY `users_suspended_at_idx` (`suspended_at`)",
            "ALTER TABLE `users` ADD KEY `users_suspended_by_idx` (`suspended_by`)"
        ];

        foreach ($indexes as $indexSql) {
            try {
                $pdo->exec($indexSql);
                echo "✅ Index ajouté\n";
            } catch (Exception $e) {
                // Ignorer les erreurs d'index déjà existant
                if (strpos($e->getMessage(), 'Duplicate key') === false) {
                    echo "⚠️ Erreur index: " . $e->getMessage() . "\n";
                }
            }
        }

        // Ajouter les contraintes de clé étrangère
        echo "\n🔧 Ajout des contraintes de clé étrangère...\n";
        $constraints = [
            "ALTER TABLE `users` ADD CONSTRAINT `users_suspended_by_foreign` FOREIGN KEY (`suspended_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL",
            "ALTER TABLE `users` ADD CONSTRAINT `users_unsuspended_by_foreign` FOREIGN KEY (`unsuspended_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL"
        ];

        foreach ($constraints as $constraintSql) {
            try {
                $pdo->exec($constraintSql);
                echo "✅ Contrainte ajoutée\n";
            } catch (Exception $e) {
                // Ignorer les erreurs de contrainte déjà existante
                if (strpos($e->getMessage(), 'Duplicate key') === false &&
                    strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠️ Erreur contrainte: " . $e->getMessage() . "\n";
                }
            }
        }

    } else {
        echo "\n✅ Tous les champs de suspension sont présents\n";
    }

    // Tester un utilisateur
    echo "\n👤 Test avec un utilisateur existant :\n";

    $stmt = $pdo->query("SELECT id_user, first_name, last_name, is_suspended FROM `users` WHERE deleted IS NULL AND is_suspended = 0 LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "Utilisateur trouvé : {$user['first_name']} {$user['last_name']} (ID: {$user['id_user']})\n";

        // Tester la suspension
        echo "\n🧪 Test de suspension...\n";

        $testData = [
            'is_suspended' => 1,
            'suspended_at' => date('Y-m-d H:i:s'),
            'suspended_by' => 1, // Admin par défaut
            'suspension_reason' => 'Test de diagnostic'
        ];

        $stmt = $pdo->prepare("UPDATE `users` SET is_suspended = ?, suspended_at = ?, suspended_by = ?, suspension_reason = ? WHERE id_user = ?");
        $result = $stmt->execute([
            $testData['is_suspended'],
            $testData['suspended_at'],
            $testData['suspended_by'],
            $testData['suspension_reason'],
            $user['id_user']
        ]);

        if ($result) {
            echo "✅ Suspension réussie en base de données\n";

            // Vérifier que ça a marché
            $stmt = $pdo->prepare("SELECT is_suspended, suspended_at, suspended_by, suspension_reason FROM `users` WHERE id_user = ?");
            $stmt->execute([$user['id_user']]);
            $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);

            echo "Données après suspension :\n";
            echo "   - is_suspended: {$updatedUser['is_suspended']}\n";
            echo "   - suspended_at: {$updatedUser['suspended_at']}\n";
            echo "   - suspended_by: {$updatedUser['suspended_by']}\n";
            echo "   - suspension_reason: {$updatedUser['suspension_reason']}\n";

            // Remettre à l'état initial
            $stmt = $pdo->prepare("UPDATE `users` SET is_suspended = 0, suspended_at = NULL, suspended_by = NULL, suspension_reason = NULL WHERE id_user = ?");
            $stmt->execute([$user['id_user']]);
            echo "✅ Test nettoyé - utilisateur remis à l'état actif\n";

        } else {
            echo "❌ Échec de la suspension en base de données\n";
        }

    } else {
        echo "❌ Aucun utilisateur actif trouvé pour le test\n";
        echo "💡 Créez d'abord des utilisateurs via l'inscription\n";
    }

    echo "\n🎉 Diagnostic terminé !\n";

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Stack trace : " . $e->getTraceAsString() . "\n";
}










