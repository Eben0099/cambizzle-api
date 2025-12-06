<?php

echo "🚀 Configuration simplifiée de la base de données Cambizzle...\n\n";

// Chargement de la configuration de base de données
$config = require __DIR__ . '/database_config.php';
$host = $config['host'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "📊 Connexion à la base de données établie\n";
    echo "🔗 Configuration utilisée : $username@$host/$database\n";

    // Fonction pour vérifier si une colonne existe
    function columnExists($pdo, $table, $column) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    // Fonction pour vérifier si un index existe
    function indexExists($pdo, $table, $index) {
        try {
            $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
            $stmt->execute([$index]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    // Fonction pour vérifier si une table existe
    function tableExists($pdo, $table) {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    // Tables à créer/modifier
    $operations = [
        // 1. Champs de suspension utilisateurs
        [
            'description' => 'Ajout des champs de suspension aux utilisateurs',
            'check' => function($pdo) {
                return columnExists($pdo, 'users', 'is_suspended');
            },
            'sql' => "
                ALTER TABLE `users`
                ADD COLUMN `is_suspended` TINYINT(1) DEFAULT 0 AFTER `deleted`,
                ADD COLUMN `suspended_at` DATETIME NULL AFTER `is_suspended`,
                ADD COLUMN `suspended_by` INT(11) UNSIGNED NULL AFTER `suspended_at`,
                ADD COLUMN `suspension_reason` TEXT NULL AFTER `suspended_by`,
                ADD COLUMN `unsuspended_at` DATETIME NULL AFTER `suspension_reason`,
                ADD COLUMN `unsuspended_by` INT(11) UNSIGNED NULL AFTER `unsuspended_at`
            "
        ],

        // 2. Indexes pour les champs de suspension
        [
            'description' => 'Création des indexes pour la suspension',
            'check' => function($pdo) {
                return indexExists($pdo, 'users', 'users_is_suspended_idx');
            },
            'sql' => "
                ALTER TABLE `users`
                ADD KEY `users_is_suspended_idx` (`is_suspended`),
                ADD KEY `users_suspended_at_idx` (`suspended_at`),
                ADD KEY `users_suspended_by_idx` (`suspended_by`)
            "
        ],

        // 3. Table promotion_packs
        [
            'description' => 'Création de la table promotion_packs',
            'check' => function($pdo) {
                return tableExists($pdo, 'promotion_packs');
            },
            'sql' => "
                CREATE TABLE `promotion_packs` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(100) NOT NULL,
                    `slug` VARCHAR(120) NOT NULL,
                    `description` TEXT NULL,
                    `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
                    `duration_days` INT(11) NOT NULL DEFAULT 7,
                    `features` JSON NULL,
                    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `display_order` INT(11) NOT NULL DEFAULT 0,
                    `created_at` DATETIME NULL,
                    `updated_at` DATETIME NULL,
                    PRIMARY KEY (`id`),
                    KEY `is_active` (`is_active`),
                    KEY `is_featured` (`is_featured`),
                    KEY `display_order` (`display_order`)
                ) DEFAULT CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci
            "
        ],

        // 4. Clé unique pour promotion_packs
        [
            'description' => 'Ajout de la clé unique slug pour promotion_packs',
            'check' => function($pdo) {
                return indexExists($pdo, 'promotion_packs', 'promotion_packs_slug_unique');
            },
            'sql' => "
                ALTER TABLE `promotion_packs`
                ADD UNIQUE KEY `promotion_packs_slug_unique` (`slug`)
            "
        ],

        // 5. Table moderation_logs
        [
            'description' => 'Création de la table moderation_logs',
            'check' => function($pdo) {
                return tableExists($pdo, 'moderation_logs');
            },
            'sql' => "
                CREATE TABLE `moderation_logs` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `moderator_id` INT(11) UNSIGNED NOT NULL,
                    `action_type` ENUM('ad_approve', 'ad_reject', 'ad_suspend', 'user_suspend', 'user_unsuspend', 'user_delete', 'identity_verify', 'identity_reject') NOT NULL,
                    `target_type` ENUM('ad', 'user') NOT NULL,
                    `target_id` INT(11) UNSIGNED NOT NULL,
                    `old_status` VARCHAR(50) NULL,
                    `new_status` VARCHAR(50) NOT NULL,
                    `reason` TEXT NULL,
                    `notes` TEXT NULL,
                    `ip_address` VARCHAR(45) NULL,
                    `user_agent` TEXT NULL,
                    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `moderation_logs_moderator_created_idx` (`moderator_id`, `created_at`),
                    KEY `moderation_logs_target_idx` (`target_type`, `target_id`),
                    KEY `moderation_logs_action_type_idx` (`action_type`),
                    KEY `moderation_logs_created_at_idx` (`created_at`)
                ) DEFAULT CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci
            "
        ],

        // 6. Clé étrangère pour moderation_logs
        [
            'description' => 'Ajout de la clé étrangère pour moderation_logs',
            'check' => function($pdo) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT CONSTRAINT_NAME
                        FROM information_schema.TABLE_CONSTRAINTS
                        WHERE TABLE_NAME = 'moderation_logs'
                        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                        AND CONSTRAINT_NAME = 'moderation_logs_moderator_id_foreign'
                    ");
                    $stmt->execute();
                    return $stmt->rowCount() > 0;
                } catch (Exception $e) {
                    return false;
                }
            },
            'sql' => "
                ALTER TABLE `moderation_logs`
                ADD CONSTRAINT `moderation_logs_moderator_id_foreign`
                FOREIGN KEY (`moderator_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
            "
        ]
    ];

    foreach ($operations as $operation) {
        echo "📦 {$operation['description']}...\n";

        // Vérifier si l'opération est nécessaire
        if (isset($operation['check'])) {
            $exists = $operation['check']($pdo);
            if ($exists) {
                echo "⏭️  Déjà existant, ignoré\n";
                continue;
            }
        }

        try {
            $pdo->exec($operation['sql']);
            echo "✅ Opération réussie\n";
        } catch (Exception $e) {
            echo "❌ Erreur : " . $e->getMessage() . "\n";

            // Pour les erreurs d'éléments qui existent déjà, continuer
            if (strpos($e->getMessage(), 'Duplicate column name') !== false ||
                strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Duplicate key') !== false ||
                strpos($e->getMessage(), 'Duplicate entry') !== false ||
                strpos($e->getMessage(), 'Cannot add foreign key constraint') !== false) {
                echo "⏭️  Élément déjà existant, continuation...\n";
                continue;
            }

            // Pour les autres erreurs, arrêter
            throw $e;
        }
    }

    // Insérer des données de test pour promotion_packs
    echo "📦 Insertion de données de test...\n";

    $testData = [
        [
            'name' => 'Pack Premium 30 Jours',
            'slug' => 'pack-premium-30',
            'description' => 'Visibilité maximale avec badge premium pendant 30 jours',
            'price' => 24.99,
            'duration_days' => 30,
            'features' => '["badge_premium", "top_recherche", "stats_detaillees", "support_prioritaire"]',
            'is_featured' => 1,
            'is_active' => 1,
            'display_order' => 1
        ],
        [
            'name' => 'Pack Essentiel 7 Jours',
            'slug' => 'pack-essentiel-7',
            'description' => 'Visibilité améliorée pendant 7 jours',
            'price' => 9.99,
            'duration_days' => 7,
            'features' => '["top_recherche", "stats_basiques"]',
            'is_featured' => 0,
            'is_active' => 1,
            'display_order' => 2
        ]
    ];

    foreach ($testData as $pack) {
        try {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO `promotion_packs`
                (name, slug, description, price, duration_days, features, is_featured, is_active, display_order, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $pack['name'], $pack['slug'], $pack['description'], $pack['price'],
                $pack['duration_days'], $pack['features'], $pack['is_featured'],
                $pack['is_active'], $pack['display_order']
            ]);
            echo "✅ Pack '{$pack['name']}' ajouté\n";
        } catch (Exception $e) {
            echo "⚠️  Pack '{$pack['name']}' déjà existant\n";
        }
    }

    echo "\n🎉 Configuration de la base de données terminée avec succès !\n\n";

    // Vérification des tables créées
    echo "📊 Vérification des tables créées :\n";
    $tables = ['users', 'promotion_packs', 'moderation_logs'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table '$table' existe\n";
            } else {
                echo "❌ Table '$table' n'existe pas\n";
            }
        } catch (Exception $e) {
            echo "⚠️  Impossible de vérifier '$table'\n";
        }
    }

    echo "\n🚀 Prêt à utiliser l'API Cambizzle !\n";
    echo "📁 Collection Postman : postman/Cambizzle_API_Complete.postman_collection.json\n";
    echo "🌐 Commande pour démarrer : php spark serve\n";

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n\n";
    echo "💡 Solutions possibles :\n";
    echo "1. Vérifiez que votre base de données MySQL est démarrée\n";
    echo "2. Modifiez les variables de connexion en haut du fichier\n";
    echo "3. Créez la base de données '$database' si elle n'existe pas\n";
    echo "4. Vérifiez les permissions de l'utilisateur '$username'\n\n";
}
