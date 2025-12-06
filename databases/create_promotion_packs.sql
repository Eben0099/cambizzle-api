-- Création de la table promotion_packs
CREATE TABLE IF NOT EXISTS `promotion_packs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `duration_days` INT UNSIGNED NOT NULL,
  `price` DECIMAL(15,2) NOT NULL,
  `type` ENUM('boost','urgent','highlighted') NOT NULL DEFAULT 'boost',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des packs par défaut
INSERT INTO `promotion_packs` (`name`, `duration_days`, `price`, `type`, `is_active`, `created_at`, `updated_at`) VALUES
('Boost 7 jours', 7, 7000.00, 'boost', 1, NOW(), NOW()),
('Boost 14 jours', 14, 12000.00, 'boost', 1, NOW(), NOW()),
('Boost 30 jours', 30, 25000.00, 'boost', 1, NOW(), NOW());

-- Ajout des colonnes boost dans la table ads (si elles n'existent pas déjà)
ALTER TABLE `ads`
  ADD COLUMN IF NOT EXISTS `is_boosted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `view_count`,
  ADD COLUMN IF NOT EXISTS `boost_start` DATETIME NULL AFTER `is_boosted`,
  ADD COLUMN IF NOT EXISTS `boost_end` DATETIME NULL AFTER `boost_start`;
