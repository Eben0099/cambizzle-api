<?php

echo "🚀 Création directe des tables Cambizzle...\n\n";

try {
    // Déterminer le répertoire de l'API (peut être appelé depuis api/ ou depuis la racine)
    $apiDir = __DIR__;
    if (basename($apiDir) !== 'cambizzle-api') {
        $apiDir .= '/api/cambizzle-api';
    }

    // Charger l'autoloader
    require_once $apiDir . '/vendor/autoload.php';

    // Initialiser CodeIgniter pour la connexion DB
    require_once $apiDir . '/app/Config/Paths.php';
    require_once $apiDir . '/system/Boot.php';

    $paths = new \Config\Paths();
    \CodeIgniter\Boot::init($paths);

    // Connexion DB
    $db = \Config\Database::connect();

    echo "📊 Connexion à la base de données établie\n";

    // Tables à créer
    $tables = [
        // 1. Champs de suspension utilisateurs
        [
            'name' => 'user_suspension_fields',
            'sql' => "
                ALTER TABLE `users`
                ADD COLUMN `is_suspended` TINYINT(1) DEFAULT 0 AFTER `deleted`,
                ADD COLUMN `suspended_at` DATETIME NULL AFTER `is_suspended`,
                ADD COLUMN `suspended_by` INT(11) UNSIGNED NULL AFTER `suspended_at`,
                ADD COLUMN `suspension_reason` TEXT NULL AFTER `suspended_by`,
                ADD COLUMN `unsuspended_at` DATETIME NULL AFTER `suspension_reason`,
                ADD COLUMN `unsuspended_by` INT(11) UNSIGNED NULL AFTER `unsuspended_at`
            ",
            'indexes' => [
                "ALTER TABLE `users` ADD KEY `users_is_suspended_idx` (`is_suspended`)",
                "ALTER TABLE `users` ADD KEY `users_suspended_at_idx` (`suspended_at`)",
                "ALTER TABLE `users` ADD KEY `users_suspended_by_idx` (`suspended_by`)",
                "ALTER TABLE `users` ADD CONSTRAINT `users_suspended_by_foreign` FOREIGN KEY (`suspended_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL",
                "ALTER TABLE `users` ADD CONSTRAINT `users_unsuspended_by_foreign` FOREIGN KEY (`unsuspended_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL"
            ]
        ],

        // 2. Table promotion_packs
        [
            'name' => 'promotion_packs',
            'sql' => "
                CREATE TABLE IF NOT EXISTS `promotion_packs` (
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
            ",
            'indexes' => [
                "ALTER TABLE `promotion_packs` ADD UNIQUE KEY `promotion_packs_slug_unique` (`slug`)"
            ]
        ],

        // 3. Table moderation_logs
        [
            'name' => 'moderation_logs',
            'sql' => "
                CREATE TABLE IF NOT EXISTS `moderation_logs` (
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
            ",
            'indexes' => [
                "ALTER TABLE `moderation_logs` ADD CONSTRAINT `moderation_logs_moderator_id_foreign` FOREIGN KEY (`moderator_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE"
            ]
        ]
    ];

    foreach ($tables as $table) {
        echo "📦 Création de {$table['name']}...\n";

        try {
            // Créer la table ou modifier
            $db->query($table['sql']);
            echo "✅ Table {$table['name']} créée/modifiée\n";

            // Ajouter les indexes si nécessaire
            if (isset($table['indexes'])) {
                foreach ($table['indexes'] as $indexSql) {
                    try {
                        $db->query($indexSql);
                        echo "✅ Index ajouté pour {$table['name']}\n";
                    } catch (Exception $e) {
                        echo "⚠️  Index déjà existant ou erreur : " . $e->getMessage() . "\n";
                    }
                }
            }

        } catch (Exception $e) {
            echo "❌ Erreur pour {$table['name']} : " . $e->getMessage() . "\n";
            echo "🔄 Continuation avec les autres tables...\n";
        }
    }

    echo "\n🎉 Toutes les tables ont été créées avec succès !\n";
    echo "\n📁 Collection Postman : postman/Cambizzle_API_Complete.postman_collection.json\n";
    echo "🌐 Pour démarrer le serveur : php spark serve\n";

} catch (Exception $e) {
    echo "❌ Erreur générale : " . $e->getMessage() . "\n";
    echo "💡 Vérifiez votre configuration .env et votre base de données.\n";
}
