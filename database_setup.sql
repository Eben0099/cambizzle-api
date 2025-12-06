-- Script d'initialisation de la base de données Cambizzle
-- Exécutez ce script dans votre serveur MySQL/MariaDB

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS `cambizzle_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cambizzle_db`;

-- Table des rôles
CREATE TABLE IF NOT EXISTS `roles` (
    `id_role` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `description` text,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_role`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les rôles de base
INSERT INTO `roles` (`id_role`, `name`, `description`) VALUES
(1, 'admin', 'Administrateur système'),
(2, 'user', 'Utilisateur standard');

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS `users` (
    `id_user` int(11) NOT NULL AUTO_INCREMENT,
    `role_id` int(11) NOT NULL DEFAULT 2,
    `slug` varchar(255) DEFAULT NULL,
    `first_name` varchar(100) NOT NULL,
    `last_name` varchar(100) NOT NULL,
    `email` varchar(255) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `password_hash` varchar(255) NOT NULL,
    `photo_url` varchar(500) DEFAULT NULL,
    `otp_code` varchar(10) DEFAULT NULL,
    `otp_expires_at` timestamp NULL DEFAULT NULL,
    `is_verified` tinyint(1) DEFAULT 0,
    `verification_token` varchar(255) DEFAULT NULL,
    `google_id` varchar(255) DEFAULT NULL,
    `facebook_id` varchar(255) DEFAULT NULL,
    `is_identity_verified` tinyint(1) DEFAULT 0,
    `identity_document_type` varchar(50) DEFAULT NULL,
    `identity_document_number` varchar(100) DEFAULT NULL,
    `identity_document_url` varchar(500) DEFAULT NULL,
    `identity_verified_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id_user`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `phone` (`phone`),
    UNIQUE KEY `slug` (`slug`),
    KEY `role_id` (`role_id`),
    CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des localisations
CREATE TABLE IF NOT EXISTS `locations` (
    `id_location` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `type` enum('country','region','city') NOT NULL,
    `parent_id` int(11) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_location`),
    KEY `parent_id` (`parent_id`),
    CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `locations` (`id_location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer quelques localisations de base
INSERT INTO `locations` (`id_location`, `name`, `type`, `parent_id`) VALUES
(1, 'France', 'country', NULL),
(2, 'Île-de-France', 'region', 1),
(3, 'Paris', 'city', 2),
(4, 'Marseille', 'city', 1);

-- Table des catégories
CREATE TABLE IF NOT EXISTS `categories` (
    `id_category` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `icon` varchar(255) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer quelques catégories de base
INSERT INTO `categories` (`id_category`, `name`, `description`) VALUES
(1, 'Électronique', 'Appareils électroniques et gadgets'),
(2, 'Vêtements', 'Vêtements et accessoires de mode'),
(3, 'Maison & Jardin', 'Articles pour la maison et le jardin');

-- Table des sous-catégories
CREATE TABLE IF NOT EXISTS `subcategories` (
    `id_subcategory` int(11) NOT NULL AUTO_INCREMENT,
    `category_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `description` text,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_subcategory`),
    KEY `category_id` (`category_id`),
    CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer quelques sous-catégories
INSERT INTO `subcategories` (`id_subcategory`, `category_id`, `name`) VALUES
(1, 1, 'Smartphones'),
(2, 1, 'Ordinateurs'),
(3, 2, 'Homme'),
(4, 2, 'Femme');

-- Table des annonces
CREATE TABLE IF NOT EXISTS `ads` (
    `id_ad` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `category_id` int(11) NOT NULL,
    `subcategory_id` int(11) DEFAULT NULL,
    `location_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `price` decimal(10,2) NOT NULL,
    `condition` varchar(50) DEFAULT NULL,
    `is_negotiable` tinyint(1) DEFAULT 0,
    `status` enum('draft','pending','active','sold','expired','rejected') DEFAULT 'pending',
    `views_count` int(11) DEFAULT 0,
    `is_promoted` tinyint(1) DEFAULT 0,
    `promoted_until` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id_ad`),
    KEY `user_id` (`user_id`),
    KEY `category_id` (`category_id`),
    KEY `subcategory_id` (`subcategory_id`),
    KEY `location_id` (`location_id`),
    CONSTRAINT `ads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_user`),
    CONSTRAINT `ads_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id_category`),
    CONSTRAINT `ads_ibfk_3` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id_subcategory`),
    CONSTRAINT `ads_ibfk_4` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id_location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des photos d'annonces
CREATE TABLE IF NOT EXISTS `ad_photos` (
    `id_photo` int(11) NOT NULL AUTO_INCREMENT,
    `ad_id` int(11) NOT NULL,
    `photo_url` varchar(500) NOT NULL,
    `is_primary` tinyint(1) DEFAULT 0,
    `order_position` int(11) DEFAULT 0,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_photo`),
    KEY `ad_id` (`ad_id`),
    CONSTRAINT `ad_photos_ibfk_1` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id_ad`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Créer un utilisateur admin par défaut
INSERT INTO `users` (
    `role_id`, `first_name`, `last_name`, `email`, `password_hash`, `is_verified`, `slug`
) VALUES (
    1, 'Admin', 'System', 'admin@cambizzle.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    1, 'admin-system'
);

-- Table des profils vendeurs
CREATE TABLE IF NOT EXISTS `seller_profiles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `business_name` varchar(255) NOT NULL,
    `business_description` text,
    `business_address` varchar(500) DEFAULT NULL,
    `business_phone` varchar(20) DEFAULT NULL,
    `business_email` varchar(255) DEFAULT NULL,
    `opening_hours` json DEFAULT NULL,
    `delivery_options` json DEFAULT NULL,
    `website_url` varchar(500) DEFAULT NULL,
    `facebook_url` varchar(500) DEFAULT NULL,
    `instagram_url` varchar(500) DEFAULT NULL,
    `logo_url` varchar(500) DEFAULT NULL,
    `is_verified` tinyint(1) DEFAULT 0,
    `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
    `rejection_reason` text,
    `verified_at` timestamp NULL DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`),
    KEY `is_active` (`is_active`),
    CONSTRAINT `seller_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des marques
CREATE TABLE IF NOT EXISTS `brands` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `subcategory_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `description` text,
    `logo_url` varchar(500) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `subcategory_brand` (`subcategory_id`, `name`),
    KEY `subcategory_id` (`subcategory_id`),
    KEY `is_active` (`is_active`),
    CONSTRAINT `brands_ibfk_1` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id_subcategory`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Afficher un message de confirmation
SELECT 'Base de données Cambizzle initialisée avec succès!' as message;
