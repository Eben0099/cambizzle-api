-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 07 oct. 2025 à 19:30
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `cambizzle-api`
--

DELIMITER $$
--
-- Procédures
--
DROP PROCEDURE IF EXISTS `ModifySellerProfilesTable`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ModifySellerProfilesTable` ()   BEGIN
    -- Vérifier si la table existe
    IF NOT EXISTS (SELECT * FROM information_schema.tables 
                   WHERE table_schema = DATABASE() AND table_name = 'seller_profiles') THEN
        -- Créer la table si elle n'existe pas
        CREATE TABLE `seller_profiles` (
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
        
        SELECT 'Table seller_profiles créée avec succès' AS result;
    ELSE
        -- La table existe, vérifier et ajouter les colonnes manquantes
        IF NOT EXISTS (SELECT * FROM information_schema.columns 
                       WHERE table_schema = DATABASE() AND table_name = 'seller_profiles' 
                       AND column_name = 'logo_url') THEN
            ALTER TABLE `seller_profiles` ADD COLUMN `logo_url` varchar(500) DEFAULT NULL AFTER `instagram_url`;
        END IF;
        
        IF NOT EXISTS (SELECT * FROM information_schema.columns 
                       WHERE table_schema = DATABASE() AND table_name = 'seller_profiles' 
                       AND column_name = 'is_verified') THEN
            ALTER TABLE `seller_profiles` ADD COLUMN `is_verified` tinyint(1) DEFAULT 0 AFTER `logo_url`;
        END IF;
        
        IF NOT EXISTS (SELECT * FROM information_schema.columns 
                       WHERE table_schema = DATABASE() AND table_name = 'seller_profiles' 
                       AND column_name = 'verification_status') THEN
            ALTER TABLE `seller_profiles` ADD COLUMN `verification_status` enum('pending','verified','rejected') DEFAULT 'pending' AFTER `is_verified`;
        END IF;
        
        IF NOT EXISTS (SELECT * FROM information_schema.columns 
                       WHERE table_schema = DATABASE() AND table_name = 'seller_profiles' 
                       AND column_name = 'rejection_reason') THEN
            ALTER TABLE `seller_profiles` ADD COLUMN `rejection_reason` text AFTER `verification_status`;
        END IF;
        
        IF NOT EXISTS (SELECT * FROM information_schema.columns 
                       WHERE table_schema = DATABASE() AND table_name = 'seller_profiles' 
                       AND column_name = 'verified_at') THEN
            ALTER TABLE `seller_profiles` ADD COLUMN `verified_at` timestamp NULL DEFAULT NULL AFTER `rejection_reason`;
        END IF;
        
        -- Vérifier et modifier les contraintes si nécessaire
        IF NOT EXISTS (SELECT * FROM information_schema.table_constraints 
                       WHERE table_schema = DATABASE() AND table_name = 'seller_profiles' 
                       AND constraint_name = 'seller_profiles_ibfk_1') THEN
            ALTER TABLE `seller_profiles` ADD CONSTRAINT `seller_profiles_ibfk_1` 
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;
        END IF;
        
        SELECT 'Table seller_profiles modifiée avec succès' AS result;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `ads`
--

DROP TABLE IF EXISTS `ads`;
CREATE TABLE IF NOT EXISTS `ads` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `location_id` int UNSIGNED NOT NULL,
  `subcategory_id` int UNSIGNED NOT NULL,
  `brand_id` int UNSIGNED DEFAULT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `price` decimal(15,2) DEFAULT NULL,
  `original_price` decimal(15,2) DEFAULT NULL,
  `discount_percentage` int DEFAULT NULL,
  `has_discount` tinyint(1) NOT NULL DEFAULT '0',
  `is_negotiable` tinyint(1) NOT NULL DEFAULT '0',
  `referral_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','inactive','expired','deleted') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `moderation_status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `moderation_notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `moderated_at` datetime DEFAULT NULL,
  `moderator_id` int UNSIGNED DEFAULT NULL,
  `view_count` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `ads_user_id_foreign` (`user_id`),
  KEY `ads_location_id_foreign` (`location_id`),
  KEY `ads_subcategory_id_foreign` (`subcategory_id`),
  KEY `ads_brand_id_foreign` (`brand_id`),
  KEY `ads_moderator_id_foreign` (`moderator_id`)
) ENGINE=MyISAM AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ads`
--

INSERT INTO `ads` (`id`, `user_id`, `location_id`, `subcategory_id`, `brand_id`, `slug`, `title`, `description`, `price`, `original_price`, `discount_percentage`, `has_discount`, `is_negotiable`, `referral_code`, `status`, `moderation_status`, `moderation_notes`, `moderated_at`, `moderator_id`, `view_count`, `created_at`, `updated_at`, `expires_at`, `deleted_at`) VALUES
(1, 12, 1, 1, 1, 'samsung-galaxy-s23-yaounde', 'Samsung Galaxy S23 - Neuf sous garantie', 'Smartphone Samsung Galaxy S23 128Go, neuf sous garantie internationale. Livré avec accessoires d\'origine.', 350000.00, 400000.00, 13, 1, 1, NULL, 'active', 'approved', 'Annonce conforme', '2025-01-10 15:30:00', 1, 45, '2025-01-10 10:00:00', '2025-01-10 15:30:00', '2025-02-10 10:00:00', '0000-00-00 00:00:00'),
(2, 13, 2, 6, 11, 'toyota-corolla-2018-douala', 'Toyota Corolla 2018 - Bon état', 'Toyota Corolla 2018, 75000 km, essence, boîte automatique. Entretien régulier chez concessionnaire.', 4500000.00, NULL, NULL, 0, 1, NULL, 'active', 'approved', 'Véhicule en bon état', '2025-01-11 11:00:00', 1, 120, '2025-01-11 09:00:00', '2025-01-11 11:00:00', '2025-02-11 09:00:00', '0000-00-00 00:00:00'),
(3, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758973901', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, NULL, NULL, NULL, '0000-00-00 00:00:00'),
(4, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758973982', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, NULL, NULL, NULL, '0000-00-00 00:00:00'),
(5, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758974080', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, NULL, NULL, NULL, '0000-00-00 00:00:00'),
(6, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758974194', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, NULL, NULL, NULL, '0000-00-00 00:00:00'),
(7, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758974365', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, NULL, NULL, NULL, '0000-00-00 00:00:00'),
(8, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758974851', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, NULL, NULL, NULL, '0000-00-00 00:00:00'),
(9, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758974891', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, NULL, NULL, NULL, '0000-00-00 00:00:00'),
(10, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758980061', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, NULL, NULL, NULL, '0000-00-00 00:00:00'),
(11, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758980083', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, NULL, NULL, NULL, '0000-00-00 00:00:00'),
(12, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758986156', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:15:56', '2025-09-27 15:15:56', NULL, '0000-00-00 00:00:00'),
(13, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758986279', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:17:59', '2025-09-27 15:17:59', NULL, '0000-00-00 00:00:00'),
(14, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758986644', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:24:04', '2025-09-27 15:24:04', NULL, '0000-00-00 00:00:00'),
(15, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758986675', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:24:35', '2025-09-27 15:24:35', NULL, '0000-00-00 00:00:00'),
(16, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758986990', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:29:50', '2025-09-27 15:29:50', NULL, '0000-00-00 00:00:00'),
(17, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758987226', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:33:46', '2025-09-27 15:33:46', NULL, '0000-00-00 00:00:00'),
(18, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758987411', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:36:51', '2025-09-27 15:36:51', NULL, '0000-00-00 00:00:00'),
(19, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758987429', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:37:09', '2025-09-27 15:37:09', NULL, '0000-00-00 00:00:00'),
(20, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758987573', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:39:33', '2025-09-27 15:39:33', NULL, NULL),
(21, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758987589', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:39:49', '2025-09-27 15:39:49', NULL, NULL),
(22, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758987792', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:43:12', '2025-09-27 15:43:12', NULL, NULL),
(23, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758988340', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, '', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:52:21', '2025-09-27 15:52:21', NULL, NULL),
(24, 33, 4, 6, NULL, 'superbe-toyota-yaris-2019-1758988364', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 4500000.00, NULL, NULL, 0, 0, NULL, '', 'pending', NULL, NULL, NULL, 0, '2025-09-27 15:52:44', '2025-09-27 15:52:44', NULL, NULL),
(25, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758990587', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 16:29:47', '2025-09-27 16:29:47', NULL, NULL),
(26, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758990713', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 16:31:53', '2025-09-27 16:31:53', NULL, NULL),
(27, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758990810', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 16:33:30', '2025-09-27 16:33:30', NULL, NULL),
(28, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758993094', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 17:11:34', '2025-09-27 17:11:34', NULL, NULL),
(29, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758993244', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 17:14:04', '2025-09-27 17:14:04', NULL, NULL),
(30, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758993297', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 17:14:57', '2025-09-27 17:14:57', NULL, NULL),
(31, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758994484', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 17:34:44', '2025-09-27 17:34:44', NULL, NULL),
(32, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758994573', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 17:36:13', '2025-09-27 17:36:13', NULL, NULL),
(33, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758995341', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 17:49:01', '2025-09-27 17:49:01', NULL, NULL),
(34, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758996567', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 18:09:27', '2025-09-27 18:09:27', NULL, NULL),
(35, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758996961', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 18:16:01', '2025-09-27 18:16:01', NULL, NULL),
(36, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1758997395', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 18:23:15', '2025-09-27 18:23:15', NULL, NULL),
(37, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1759000730', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 19:18:50', '2025-09-27 19:18:50', NULL, NULL),
(38, 33, 4, 6, 11, 'toyota-yaris-2019-parfaite-état-prix-réduit-1759082410', 'Toyota Yaris 2019 - Parfaite état - Prix réduit', 'Toyota Yaris 2019 en parfaite état, très faible kilométrage, climatisation d\'origine. Prix négociable. Entretien chez concessionnaire Toyota.', 3200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 20:04:32', '2025-09-28 18:00:10', NULL, NULL),
(39, 33, 1, 6, 11, 'toyota-yaris-2019-excellente-état-1759005627', 'Toyota Yaris 2019 - Excellente état', 'Toyota Yaris 2019 en excellent état, faible kilométrage, climatisation d\'origine. Entretien régulier chez concessionnaire Toyota.', 3500000.00, 4500000.00, 22, 1, 1, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 20:40:27', '2025-09-27 20:40:27', NULL, NULL),
(40, 33, 1, 6, 11, 'toyota-yaris-2019-excellente-état-1759005739', 'Toyota Yaris 2019 - Excellente état', 'Toyota Yaris 2019 en excellent état, faible kilométrage, climatisation d\'origine. Entretien régulier chez concessionnaire Toyota.', 3500000.00, 4500000.00, 22, 1, 1, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 20:42:19', '2025-09-27 20:42:19', NULL, NULL),
(41, 33, 1, 6, 11, 'toyota-yaris-2019-excellente-état-1759005825', 'Toyota Yaris 2019 - Excellente état', 'Toyota Yaris 2019 en excellent état, faible kilométrage, climatisation d\'origine. Entretien régulier chez concessionnaire Toyota.', 3500000.00, 4500000.00, 22, 1, 1, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 20:43:45', '2025-09-27 20:43:45', NULL, NULL),
(42, 33, 1, 1, 1, 'iphone-13-pro-max-256gb-neuf-1759005996', 'iPhone 13 Pro Max 256GB - Neuf', 'iPhone 13 Pro Max 256GB en excellent état, acheté en décembre 2023. Garantie Apple restante.', 650000.00, 700000.00, 10, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-27 20:46:36', '2025-09-27 20:46:36', NULL, NULL),
(43, 33, 1, 1, 1, 'iphone-13-pro-max-256gb-neuf-1759081240', 'iPhone 13 Pro Max 256GB - Neuf', 'iPhone 13 Pro Max 256GB en excellent état, acheté en décembre 2023. Garantie Apple restante.', 650000.00, 700000.00, 10, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-28 17:40:40', '2025-09-28 17:40:40', NULL, NULL),
(44, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1759081337', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-28 17:42:17', '2025-09-28 17:42:17', NULL, NULL),
(45, 33, 1, 1, 1, 'iphone-13-pro-max-256gb-neuf-1759081384', 'iPhone 13 Pro Max 256GB - Neuf', 'iPhone 13 Pro Max 256GB en excellent état, acheté en décembre 2023. Garantie Apple restante.', 650000.00, 700000.00, 10, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-28 17:43:04', '2025-09-28 17:43:04', NULL, NULL),
(46, 33, 1, 1, 1, 'iphone-13-pro-max-256gb-neuf-1759081411', 'iPhone 13 Pro Max 256GB - Neuf', 'iPhone 13 Pro Max 256GB en excellent état, acheté en décembre 2023. Garantie Apple restante.', 650000.00, 700000.00, 10, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-28 17:43:31', '2025-09-28 17:43:31', NULL, NULL),
(47, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1759081466', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-28 17:44:26', '2025-09-28 17:44:26', NULL, NULL),
(48, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1759081631', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-28 17:47:11', '2025-09-28 17:47:11', NULL, NULL),
(49, 33, 1, 1, 1, 'iphone-13-pro-max-256gb-neuf-1759081671', 'iPhone 13 Pro Max 256GB - Neuf', 'iPhone 13 Pro Max 256GB en excellent état, acheté en décembre 2023. Garantie Apple restante.', 650000.00, 700000.00, 10, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-28 17:47:51', '2025-09-28 17:47:51', NULL, NULL),
(50, 33, 1, 1, 1, 'iphone-13-pro-max-256gb-neuf-1759082066', 'iPhone 13 Pro Max 256GB - Neuf', 'iPhone 13 Pro Max 256GB en excellent état, acheté en décembre 2023. Garantie Apple restante.', 650000.00, 700000.00, 10, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-28 17:54:26', '2025-09-28 17:54:26', NULL, NULL),
(51, 33, 1, 1, 1, 'iphone-13-pro-max-256gb-neuf-1759082352', 'iPhone 13 Pro Max 256GB - Neuf', 'iPhone 13 Pro Max 256GB en excellent état, acheté en décembre 2023. Garantie Apple restante.', 650000.00, 700000.00, 10, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-28 17:59:12', '2025-09-28 17:59:12', NULL, NULL),
(52, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1759082375', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-28 17:59:35', '2025-09-28 17:59:35', NULL, NULL),
(53, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1759147242', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-09-29 12:00:42', '2025-09-29 12:00:42', NULL, NULL),
(54, 33, 4, 6, 11, 'superbe-toyota-yaris-2019-1759486966', 'Superbe Toyota Yaris 2019', 'Voiture en excellent état, faible kilométrage, idéale pour la ville. Climatisation d\'origine.', 200000.00, 4500000.00, 50, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-10-03 10:22:46', '2025-10-03 10:22:46', NULL, NULL),
(55, 33, 2, 1, 2, 'iphone-14-pro-max-1759488737', 'Iphone 14 pro max', 'ceci est la description', 25000.00, 30000.00, 17, 0, 0, NULL, 'active', 'pending', NULL, NULL, NULL, 0, '2025-10-03 10:52:17', '2025-10-03 10:52:17', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `ad_filter_values`
--

DROP TABLE IF EXISTS `ad_filter_values`;
CREATE TABLE IF NOT EXISTS `ad_filter_values` (
  `ad_id` int UNSIGNED NOT NULL,
  `filter_id` int UNSIGNED NOT NULL,
  `value` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`ad_id`,`filter_id`),
  KEY `ad_filter_values_filter_id_foreign` (`filter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ad_filter_values`
--

INSERT INTO `ad_filter_values` (`ad_id`, `filter_id`, `value`) VALUES
(38, 5, '2019'),
(38, 6, '85000'),
(38, 7, 'Essence'),
(48, 5, '2019'),
(48, 6, '85000'),
(48, 7, 'Essence'),
(52, 5, '2019'),
(52, 6, '85000'),
(52, 7, 'Essence'),
(53, 5, '2019'),
(53, 6, '85000'),
(53, 7, 'Essence'),
(54, 5, '2019'),
(54, 6, '85000'),
(54, 7, 'Essence'),
(55, 1, 'Neuf'),
(55, 2, '8 Go'),
(55, 3, '256 Go'),
(55, 4, 'Noir');

-- --------------------------------------------------------

--
-- Structure de la table `ad_photos`
--

DROP TABLE IF EXISTS `ad_photos`;
CREATE TABLE IF NOT EXISTS `ad_photos` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id` int UNSIGNED NOT NULL,
  `original_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `thumbnail_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `alt_text` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ad_photos_ad_id_foreign` (`ad_id`)
) ENGINE=MyISAM AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ad_photos`
--

INSERT INTO `ad_photos` (`id`, `ad_id`, `original_url`, `thumbnail_url`, `display_order`, `alt_text`, `created_at`) VALUES
(1, 1, 'https://example.com/photos/samsung1.jpg', 'https://example.com/thumbs/samsung1.jpg', 1, 'Samsung Galaxy S23 face avant', '2025-01-10 10:05:00'),
(2, 1, 'https://example.com/photos/samsung2.jpg', 'https://example.com/thumbs/samsung2.jpg', 2, 'Samsung Galaxy S23 dos', '2025-01-10 10:05:00'),
(3, 33, 'http://localhost:8080/uploads/ads/1758995341_ea9ee8b7c32d1b5254e1.png', NULL, 0, 'Photo 1', '2025-09-27 17:49:05'),
(4, 33, 'http://localhost:8080/uploads/ads/1758995345_b0b5d79498da611ee1de.jpg', NULL, 1, 'Photo 2', '2025-09-27 17:49:06'),
(5, 33, 'http://localhost:8080/uploads/ads/1758995346_05d673c5bbe6f03045e6.jpg', NULL, 2, 'Photo 3', '2025-09-27 17:49:06'),
(6, 33, 'http://localhost:8080/uploads/ads/1758995346_b80ceff3f23130bd33dd.jpg', NULL, 3, 'Photo 4', '2025-09-27 17:49:09'),
(7, 34, 'http://localhost:8080/uploads/ads/1758996567_1370a508900da6600b8f.png', NULL, 0, 'Photo 1', '2025-09-27 18:09:31'),
(8, 34, 'http://localhost:8080/uploads/ads/1758996571_c070cc31a123b8745438.jpg', NULL, 1, 'Photo 2', '2025-09-27 18:09:31'),
(9, 34, 'http://localhost:8080/uploads/ads/1758996571_b0e33aa26920a96d1968.jpg', NULL, 2, 'Photo 3', '2025-09-27 18:09:31'),
(10, 34, 'http://localhost:8080/uploads/ads/1758996571_0aa1b73cdf6c8baaf77f.jpg', NULL, 3, 'Photo 4', '2025-09-27 18:09:34'),
(11, 35, 'http://localhost:8080/uploads/ads/1758996961_be166a63705eaae8947b.png', NULL, 0, 'Photo 1', '2025-09-27 18:16:09'),
(12, 35, 'http://localhost:8080/uploads/ads/1758996969_7076ed590095ee3df3a1.jpg', NULL, 1, 'Photo 2', '2025-09-27 18:16:10'),
(13, 35, 'http://localhost:8080/uploads/ads/1758996970_cb2768b0724254514818.jpg', NULL, 2, 'Photo 3', '2025-09-27 18:16:11'),
(14, 35, 'http://localhost:8080/uploads/ads/1758996971_a690e565882d403b39ac.jpg', NULL, 3, 'Photo 4', '2025-09-27 18:16:16'),
(15, 36, 'http://localhost:8080/uploads/ads/1758997395_dfe8a3eabcbb95e4f656.png', NULL, 0, 'Photo 1', '2025-09-27 18:23:22'),
(16, 36, 'http://localhost:8080/uploads/ads/1758997402_8e99abb42a2eee25e01d.jpg', NULL, 1, 'Photo 2', '2025-09-27 18:23:23'),
(17, 36, 'http://localhost:8080/uploads/ads/1758997403_f4504b439d610d503439.jpg', NULL, 2, 'Photo 3', '2025-09-27 18:23:23'),
(18, 36, 'http://localhost:8080/uploads/ads/1758997403_e13093e82ecfe479be1f.jpg', NULL, 3, 'Photo 4', '2025-09-27 18:23:26'),
(19, 37, 'http://localhost:8080/uploads/ads/1759000730_1ffc2feb90169a66d3b6.png', NULL, 0, 'Photo 1', '2025-09-27 19:18:57'),
(20, 37, 'http://localhost:8080/uploads/ads/1759000737_7721561822978dabe2cf.jpg', NULL, 1, 'Photo 2', '2025-09-27 19:18:58'),
(21, 37, 'http://localhost:8080/uploads/ads/1759000738_d9b17c36543477114be9.jpg', NULL, 2, 'Photo 3', '2025-09-27 19:18:59'),
(22, 37, 'http://localhost:8080/uploads/ads/1759000739_37bfa747f69fdebde4c0.jpg', NULL, 3, 'Photo 4', '2025-09-27 19:19:01'),
(23, 38, 'http://localhost:8080/uploads/ads/1759003472_7252d9a3a7080c69f76c.png', NULL, 0, 'Photo 1', '2025-09-27 20:04:33'),
(24, 38, 'http://localhost:8080/uploads/ads/1759003473_1ed550186cea086ba034.jpg', NULL, 1, 'Photo 2', '2025-09-27 20:04:34'),
(25, 38, 'http://localhost:8080/uploads/ads/1759003474_bb85e9af494cd8235cb0.jpg', NULL, 2, 'Photo 3', '2025-09-27 20:04:34'),
(26, 38, 'http://localhost:8080/uploads/ads/1759003474_54656f2ca0715171dc85.jpg', NULL, 3, 'Photo 4', '2025-09-27 20:04:34'),
(27, 40, 'http://localhost:8080/uploads/ads/1759005739_6e6e1fa28051f250f123.png', NULL, 0, 'Photo 1', '2025-09-27 20:42:27'),
(28, 41, 'http://localhost:8080/uploads/ads/1759005825_397c525416cbe5a3ae01.png', NULL, 0, 'Photo 1', '2025-09-27 20:43:46'),
(29, 41, 'http://localhost:8080/uploads/ads/1759005826_924d6ecc094e7e85be05.jpg', NULL, 1, 'Photo 2', '2025-09-27 20:43:46'),
(30, 41, 'http://localhost:8080/uploads/ads/1759005826_72ea133f272514bd8619.jpg', NULL, 2, 'Photo 3', '2025-09-27 20:43:46'),
(31, 41, 'http://localhost:8080/uploads/ads/1759005826_f090d207fab5002bf4ea.jpg', NULL, 3, 'Photo 4', '2025-09-27 20:43:46'),
(32, 42, 'http://localhost:8080/uploads/ads/1759005996_af692f2c8840052c6e7c.png', NULL, 0, 'Photo 1', '2025-09-27 20:46:37'),
(33, 42, 'http://localhost:8080/uploads/ads/1759005997_0889b0283571c93fb749.png', NULL, 1, 'Photo 2', '2025-09-27 20:46:38'),
(34, 42, 'http://localhost:8080/uploads/ads/1759005998_ee3d27edd94b8f8f8bdf.png', NULL, 2, 'Photo 3', '2025-09-27 20:46:39'),
(35, 43, 'http://localhost:8080/uploads/ads/1759081240_a550f3e255d28e2f9e0a.png', NULL, 0, 'Photo 1', '2025-09-28 17:40:43'),
(36, 43, 'http://localhost:8080/uploads/ads/1759081243_127f609cecb5b4aa8c47.png', NULL, 1, 'Photo 2', '2025-09-28 17:40:45'),
(37, 43, 'http://localhost:8080/uploads/ads/1759081245_2bc2cf7888da28a1b0eb.png', NULL, 2, 'Photo 3', '2025-09-28 17:40:48'),
(38, 44, 'http://localhost:8080/uploads/ads/1759081337_9f0e3e488de339f43fd6.png', NULL, 0, 'Photo 1', '2025-09-28 17:42:18'),
(39, 44, 'http://localhost:8080/uploads/ads/1759081338_5787206516b5ac8a9ca9.jpg', NULL, 1, 'Photo 2', '2025-09-28 17:42:19'),
(40, 44, 'http://localhost:8080/uploads/ads/1759081339_8e8aa785035cb1bd9dd2.jpg', NULL, 2, 'Photo 3', '2025-09-28 17:42:19'),
(41, 44, 'http://localhost:8080/uploads/ads/1759081339_d4fa38476020ca14529f.jpg', NULL, 3, 'Photo 4', '2025-09-28 17:42:19'),
(42, 45, 'http://localhost:8080/uploads/ads/1759081384_1e589b360bba5b9c263d.png', NULL, 0, 'Photo 1', '2025-09-28 17:43:05'),
(43, 45, 'http://localhost:8080/uploads/ads/1759081385_3235319ebcca541953a8.png', NULL, 1, 'Photo 2', '2025-09-28 17:43:06'),
(44, 45, 'http://localhost:8080/uploads/ads/1759081386_e1b72e02d1957d883f5b.png', NULL, 2, 'Photo 3', '2025-09-28 17:43:08'),
(45, 46, 'http://localhost:8080/uploads/ads/1759081411_3b1d8a6d0c65e92b7dba.png', NULL, 0, 'Photo 1', '2025-09-28 17:43:32'),
(46, 46, 'http://localhost:8080/uploads/ads/1759081412_1e760621662747cb70e7.png', NULL, 1, 'Photo 2', '2025-09-28 17:43:33'),
(47, 46, 'http://localhost:8080/uploads/ads/1759081413_3548eb140f4e5504a227.png', NULL, 2, 'Photo 3', '2025-09-28 17:43:35'),
(48, 47, 'http://localhost:8080/uploads/ads/1759081466_05563aa355849d10cdf4.png', NULL, 0, 'Photo 1', '2025-09-28 17:44:28'),
(49, 47, 'http://localhost:8080/uploads/ads/1759081468_0fbad9784df8d22d9942.jpg', NULL, 1, 'Photo 2', '2025-09-28 17:44:28'),
(50, 47, 'http://localhost:8080/uploads/ads/1759081468_50a1f762e82f63d1d27b.jpg', NULL, 2, 'Photo 3', '2025-09-28 17:44:28'),
(51, 47, 'http://localhost:8080/uploads/ads/1759081468_ae96783f8ffbf8f0baa0.jpg', NULL, 3, 'Photo 4', '2025-09-28 17:44:29'),
(52, 48, 'http://localhost:8080/uploads/ads/1759081631_366b01c7f01332bba554.png', NULL, 0, 'Photo 1', '2025-09-28 17:47:12'),
(53, 48, 'http://localhost:8080/uploads/ads/1759081632_43a9553e9b95cf161b67.jpg', NULL, 1, 'Photo 2', '2025-09-28 17:47:13'),
(54, 48, 'http://localhost:8080/uploads/ads/1759081633_6926b2a47b213edb58b5.jpg', NULL, 2, 'Photo 3', '2025-09-28 17:47:13'),
(55, 48, 'http://localhost:8080/uploads/ads/1759081633_7c840e3d2461af561cef.jpg', NULL, 3, 'Photo 4', '2025-09-28 17:47:15'),
(56, 49, 'http://localhost:8080/uploads/ads/1759081671_89a4ab9d24b98bcdffc0.png', NULL, 0, 'Photo 1', '2025-09-28 17:47:52'),
(57, 49, 'http://localhost:8080/uploads/ads/1759081672_4aacda1096bfcf95dce2.png', NULL, 1, 'Photo 2', '2025-09-28 17:47:53'),
(58, 49, 'http://localhost:8080/uploads/ads/1759081673_9f2bf0c69a0292b2ff94.png', NULL, 2, 'Photo 3', '2025-09-28 17:47:56'),
(59, 50, 'http://localhost:8080/uploads/ads/1759082066_53eb9152e3d5bb812759.png', NULL, 0, 'Photo 1', '2025-09-28 17:54:27'),
(60, 50, 'http://localhost:8080/uploads/ads/1759082067_aac82cf31e2eef66bf95.png', NULL, 1, 'Photo 2', '2025-09-28 17:54:29'),
(61, 50, 'http://localhost:8080/uploads/ads/1759082069_f50d1c10b5137dff394d.png', NULL, 2, 'Photo 3', '2025-09-28 17:54:31'),
(62, 51, 'http://localhost:8080/uploads/ads/1759082352_f22e74c8d7bc2634b903.png', NULL, 0, 'Photo 1', '2025-09-28 17:59:15'),
(63, 51, 'http://localhost:8080/uploads/ads/1759082355_ceabbede0ddeaf1ad829.png', NULL, 1, 'Photo 2', '2025-09-28 17:59:16'),
(64, 51, 'http://localhost:8080/uploads/ads/1759082356_2098db2ec831184a6da7.png', NULL, 2, 'Photo 3', '2025-09-28 17:59:18'),
(65, 52, 'http://localhost:8080/uploads/ads/1759082375_6ee174d2d43a40c8a44c.png', NULL, 0, 'Photo 1', '2025-09-28 17:59:37'),
(66, 52, 'http://localhost:8080/uploads/ads/1759082377_99ba639050a5d40d12f3.jpg', NULL, 1, 'Photo 2', '2025-09-28 17:59:37'),
(67, 52, 'http://localhost:8080/uploads/ads/1759082377_776f7831c1c1f926ef75.jpg', NULL, 2, 'Photo 3', '2025-09-28 17:59:37'),
(68, 52, 'http://localhost:8080/uploads/ads/1759082377_adcae308bcb1264cd132.jpg', NULL, 3, 'Photo 4', '2025-09-28 17:59:38'),
(69, 53, 'http://localhost:8080/uploads/ads/1759147243_f68acc3726bc136d90e7.png', NULL, 0, 'Photo 1', '2025-09-29 12:00:44'),
(70, 53, 'http://localhost:8080/uploads/ads/1759147244_b2e51134d1df06f03f55.jpg', NULL, 1, 'Photo 2', '2025-09-29 12:00:44'),
(71, 53, 'http://localhost:8080/uploads/ads/1759147244_202c3e80d81836700af3.jpg', NULL, 2, 'Photo 3', '2025-09-29 12:00:44'),
(72, 53, 'http://localhost:8080/uploads/ads/1759147244_de2ae6029c67c4ea5098.jpg', NULL, 3, 'Photo 4', '2025-09-29 12:00:44'),
(73, 54, 'http://localhost:8080/uploads/ads/1759486966_c9ff9dc7f3cf33d4358a.png', NULL, 0, 'Photo 1', '2025-10-03 10:22:47'),
(74, 54, 'http://localhost:8080/uploads/ads/1759486967_27bba7e463d526beff05.jpg', NULL, 1, 'Photo 2', '2025-10-03 10:22:48'),
(75, 54, 'http://localhost:8080/uploads/ads/1759486968_d4980ca218907225eb47.jpg', NULL, 2, 'Photo 3', '2025-10-03 10:22:48'),
(76, 54, 'http://localhost:8080/uploads/ads/1759486968_db536f34f7ffb3e1e19e.jpg', NULL, 3, 'Photo 4', '2025-10-03 10:22:49'),
(77, 55, 'http://localhost:8080/uploads/ads/1759488737_a1917838d4698f17ad40.png', NULL, 0, 'Photo 1', '2025-10-03 10:52:17'),
(78, 55, 'http://localhost:8080/uploads/ads/1759488737_f480c699019879136df7.jpg', NULL, 1, 'Photo 2', '2025-10-03 10:52:17'),
(79, 55, 'http://localhost:8080/uploads/ads/1759488737_5f52431c2b5466773eb4.jpg', NULL, 2, 'Photo 3', '2025-10-03 10:52:17'),
(80, 55, 'http://localhost:8080/uploads/ads/1759488737_bf77ec4431a6aaace86d.png', NULL, 3, 'Photo 4', '2025-10-03 10:52:17');

-- --------------------------------------------------------

--
-- Structure de la table `ad_promotions`
--

DROP TABLE IF EXISTS `ad_promotions`;
CREATE TABLE IF NOT EXISTS `ad_promotions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id` int UNSIGNED NOT NULL,
  `promotion_type` enum('featured','urgent','highlighted') COLLATE utf8mb4_general_ci NOT NULL,
  `starts_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `price_paid` decimal(15,2) NOT NULL,
  `payment_reference` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ad_promotions_ad_id_foreign` (`ad_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `brands`
--

DROP TABLE IF EXISTS `brands`;
CREATE TABLE IF NOT EXISTS `brands` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `subcategory_id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `logo_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subcategory_brand` (`subcategory_id`,`name`)
) ENGINE=MyISAM AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `brands`
--

INSERT INTO `brands` (`id`, `subcategory_id`, `name`, `description`, `logo_url`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Samsung', 'Marque coréenne de smartphones', 'logos/samsung.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(2, 1, 'Apple', 'Marque américaine d\'iPhone', 'logos/apple.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(3, 1, 'Tecno', 'Marque chinoise populaire en Afrique', 'logos/tecno.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(4, 1, 'Infinix', 'Marque de smartphones abordables', 'logos/infinix.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(5, 1, 'Huawei', 'Marque chinoise de smartphones', 'logos/huawei.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(6, 2, 'Dell', 'Ordinateurs portables et de bureau', 'logos/dell.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(7, 2, 'HP', 'Ordinateurs et imprimantes', 'logos/hp.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(8, 2, 'Lenovo', 'Ordinateurs portables professionnels', 'logos/lenovo.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(9, 2, 'Asus', 'Ordinateurs gaming et grand public', 'logos/asus.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(10, 2, 'Acer', 'Ordinateurs abordables', 'logos/acer.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(11, 6, 'Toyota', 'Voitures fiables et économiques', 'logos/toyota.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(12, 6, 'Mercedes', 'Voitures de luxe', 'logos/mercedes.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(13, 6, 'BMW', 'Voitures sportives allemandes', 'logos/bmw.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(14, 6, 'Renault', 'Voitures françaises', 'logos/renault.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(15, 6, 'Peugeot', 'Voitures françaises populaires', 'logos/peugeot.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(16, 3, 'Samsung', 'Tablettes Samsung', 'logos/samsung-tablet.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(17, 3, 'Apple', 'iPad Apple', 'logos/ipad.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(18, 4, 'LG', 'Téléviseurs LG', 'logos/lg.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(19, 4, 'Sony', 'Téléviseurs Sony', 'logos/sony.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(20, 5, 'Canon', 'Appareils photo Canon', 'logos/canon.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(21, 5, 'Nikon', 'Appareils photo Nikon', 'logos/nikon.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(22, 7, 'Yamaha', 'Motos Yamaha', 'logos/yamaha.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(23, 7, 'Honda', 'Motos Honda', 'logos/honda.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(24, 8, 'Giant', 'Vélos Giant', 'logos/giant.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(25, 8, 'Scott', 'Vélos Scott', 'logos/scott.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(26, 9, 'Bosch', 'Pièces auto Bosch', 'logos/bosch.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(27, 9, 'Valeo', 'Pièces auto Valeo', 'logos/valeo.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(28, 10, 'Volvo', 'Camions Volvo', 'logos/volvo.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(29, 10, 'Scania', 'Camions Scania', 'logos/scania.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(30, 26, 'Nike', 'Vêtements sportswear', 'logos/nike.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(31, 26, 'Adidas', 'Vêtements et chaussures', 'logos/adidas.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(32, 27, 'Zara', 'Mode féminine', 'logos/zara.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(33, 27, 'H&M', 'Mode abordable', 'logos/hm.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(34, 28, 'Nike', 'Chaussures de sport', 'logos/nike-shoes.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(35, 28, 'Adidas', 'Chaussures sport', 'logos/adidas-shoes.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(36, 29, 'Louis Vuitton', 'Sacs de luxe', 'logos/lv.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(37, 29, 'Gucci', 'Sacs et accessoires', 'logos/gucci.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(38, 30, 'Cartier', 'Bijoux de luxe', 'logos/cartier.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(39, 30, 'Swarovski', 'Bijoux cristal', 'logos/swarovski.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(40, 31, 'IKEA', 'Meubles design', 'logos/ikea.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(41, 31, 'Roche Bobois', 'Meubles haut de gamme', 'logos/roche-bobois.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(42, 32, 'Samsung', 'Électroménager', 'logos/samsung-appliances.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(43, 32, 'LG', 'Électroménager', 'logos/lg-appliances.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(44, 36, 'Nike', 'Équipement sportif', 'logos/nike-sports.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(45, 36, 'Adidas', 'Équipement fitness', 'logos/adidas-sports.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(46, 41, 'Royal Canin', 'Nourriture pour chiens', 'logos/royal-canin.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(47, 41, 'Pedigree', 'Nourriture pour chiens', 'logos/pedigree.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(48, 42, 'Whiskas', 'Nourriture pour chats', 'logos/whiskas.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(49, 42, 'Friskies', 'Nourriture pour chats', 'logos/friskies.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(50, 46, 'John Deere', 'Machines agricoles', 'logos/john-deere.png', 1, '2025-09-22 13:52:52', '2025-09-22 13:52:52'),
(51, 6, 'Mercedes-Benz', 'Voitures de luxe allemandes', 'https://example.com/logos/mercedes.png', 1, '2025-09-22 13:52:53', '2025-09-22 13:52:53');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `icon_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `slug`, `name`, `icon_path`, `is_active`, `display_order`) VALUES
(1, 'electronique', 'Électronique', 'icons/electronics.svg', 1, 1),
(2, 'vehicules', 'Véhicules', 'icons/vehicles.svg', 1, 2),
(3, 'immobilier', 'Immobilier', 'icons/real-estate.svg', 1, 3),
(4, 'emplois', 'Emplois', 'icons/jobs.svg', 1, 4),
(5, 'services', 'Services', 'icons/services.svg', 1, 5),
(6, 'mode', 'Mode & Beauté', 'icons/fashion.svg', 1, 6),
(7, 'maison', 'Maison & Jardin', 'icons/home.svg', 1, 7),
(8, 'sports', 'Sports & Loisirs', 'icons/sports.svg', 1, 8),
(9, 'animaux', 'Animaux', 'icons/animals.svg', 1, 9),
(10, 'agriculture', 'Agriculture', 'icons/agriculture.svg', 1, 10);

-- --------------------------------------------------------

--
-- Structure de la table `filters`
--

DROP TABLE IF EXISTS `filters`;
CREATE TABLE IF NOT EXISTS `filters` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `subcategory_id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('text','number','select','checkbox','radio') COLLATE utf8mb4_general_ci NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `filters_subcategory_id_foreign` (`subcategory_id`)
) ENGINE=MyISAM AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `filters`
--

INSERT INTO `filters` (`id`, `subcategory_id`, `name`, `type`, `is_required`, `display_order`, `is_active`) VALUES
(1, 1, 'État', 'select', 1, 1, 1),
(2, 1, 'Mémoire RAM', 'select', 0, 2, 1),
(3, 1, 'Stockage', 'select', 0, 3, 1),
(4, 1, 'Couleur', 'select', 0, 4, 1),
(5, 6, 'Année', 'number', 1, 1, 1),
(6, 6, 'Kilométrage', 'number', 1, 2, 1),
(7, 6, 'Carburant', 'select', 1, 3, 1),
(8, 6, 'Boîte de vitesse', 'select', 1, 4, 1),
(9, 11, 'Surface', 'number', 1, 1, 1),
(10, 11, 'Nombre de pièces', 'number', 1, 2, 1),
(11, 11, 'Meublé', 'checkbox', 0, 3, 1),
(12, 16, 'Type de contrat', 'select', 1, 1, 1),
(13, 16, 'Salaire', 'number', 0, 2, 1),
(14, 16, 'Expérience requise', 'select', 0, 3, 1),
(15, 26, 'Taille', 'select', 1, 1, 1),
(16, 26, 'Couleur', 'select', 0, 2, 1),
(17, 26, 'Matériau', 'select', 0, 3, 1),
(18, 2, 'Processeur', 'select', 0, 1, 1),
(19, 2, 'Disque dur', 'select', 0, 2, 1),
(20, 3, 'Taille écran', 'select', 0, 1, 1),
(21, 4, 'Taille écran', 'select', 0, 1, 1),
(22, 5, 'Résolution', 'select', 0, 1, 1),
(23, 7, 'Cylindrée', 'select', 1, 1, 1),
(24, 8, 'Type de vélo', 'select', 1, 1, 1),
(25, 12, 'Surface terrain', 'number', 1, 1, 1),
(26, 13, 'Type de terrain', 'select', 1, 1, 1),
(27, 14, 'Surface bureau', 'number', 1, 1, 1),
(28, 15, 'Durée location', 'select', 1, 1, 1),
(29, 17, 'Secteur', 'select', 0, 1, 1),
(30, 18, 'Type cuisine', 'select', 0, 1, 1),
(31, 19, 'Qualification', 'select', 0, 1, 1),
(32, 20, 'Spécialité', 'select', 0, 1, 1),
(33, 21, 'Type réparation', 'select', 1, 1, 1),
(34, 22, 'Type nettoyage', 'select', 1, 1, 1),
(35, 23, 'Matière', 'select', 1, 1, 1),
(36, 24, 'Type transport', 'select', 1, 1, 1),
(37, 25, 'Type soin', 'select', 1, 1, 1),
(38, 27, 'Taille', 'select', 1, 1, 1),
(39, 28, 'Pointure', 'select', 1, 1, 1),
(40, 29, 'Type sac', 'select', 1, 1, 1),
(41, 30, 'Type bijou', 'select', 1, 1, 1),
(42, 31, 'Type meuble', 'select', 1, 1, 1),
(43, 32, 'Type appareil', 'select', 1, 1, 1),
(44, 33, 'Style décoration', 'select', 1, 1, 1),
(45, 34, 'Type plante', 'select', 1, 1, 1),
(46, 35, 'Type outil', 'select', 1, 1, 1),
(47, 36, 'Type équipement', 'select', 1, 1, 1),
(48, 41, 'Race', 'select', 1, 1, 1),
(49, 46, 'Type produit', 'select', 1, 1, 1),
(50, 47, 'Type machine', 'select', 1, 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `filter_options`
--

DROP TABLE IF EXISTS `filter_options`;
CREATE TABLE IF NOT EXISTS `filter_options` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `filter_id` int UNSIGNED NOT NULL,
  `value` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `filter_options_filter_id_foreign` (`filter_id`)
) ENGINE=MyISAM AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `filter_options`
--

INSERT INTO `filter_options` (`id`, `filter_id`, `value`, `display_order`, `is_active`) VALUES
(1, 1, 'Neuf', 1, 1),
(2, 1, 'Comme neuf', 2, 1),
(3, 1, 'Bon état', 3, 1),
(4, 1, 'État moyen', 4, 1),
(5, 1, 'À réparer', 5, 1),
(6, 2, '2 Go', 1, 1),
(7, 2, '4 Go', 2, 1),
(8, 2, '6 Go', 3, 1),
(9, 2, '8 Go', 4, 1),
(10, 2, '12 Go', 5, 1),
(11, 3, '32 Go', 1, 1),
(12, 3, '64 Go', 2, 1),
(13, 3, '128 Go', 3, 1),
(14, 3, '256 Go', 4, 1),
(15, 3, '512 Go', 5, 1),
(16, 7, 'Essence', 1, 1),
(17, 7, 'Diesel', 2, 1),
(18, 7, 'Électrique', 3, 1),
(19, 7, 'Hybride', 4, 1),
(20, 8, 'Manuelle', 1, 1),
(21, 8, 'Automatique', 2, 1),
(22, 12, 'CDI', 1, 1),
(23, 12, 'CDD', 2, 1),
(24, 12, 'Freelance', 3, 1),
(25, 12, 'Stage', 4, 1),
(26, 14, 'Débutant', 1, 1),
(27, 14, '1-3 ans', 2, 1),
(28, 14, '3-5 ans', 3, 1),
(29, 14, '5+ ans', 4, 1),
(30, 15, 'S', 1, 1),
(31, 15, 'M', 2, 1),
(32, 15, 'L', 3, 1),
(33, 15, 'XL', 4, 1),
(34, 15, 'XXL', 5, 1),
(35, 4, 'Noir', 1, 1),
(36, 4, 'Blanc', 2, 1),
(37, 4, 'Bleu', 3, 1),
(38, 4, 'Rouge', 4, 1),
(39, 4, 'Or', 5, 1),
(40, 16, 'Noir', 1, 1),
(41, 16, 'Blanc', 2, 1),
(42, 16, 'Bleu', 3, 1),
(43, 16, 'Rouge', 4, 1),
(44, 16, 'Vert', 5, 1),
(45, 17, 'Coton', 1, 1),
(46, 17, 'Polyester', 2, 1),
(47, 17, 'Laine', 3, 1),
(48, 17, 'Soie', 4, 1),
(49, 17, 'Cuir', 5, 1),
(50, 18, 'Intel i3', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `locations`
--

DROP TABLE IF EXISTS `locations`;
CREATE TABLE IF NOT EXISTS `locations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `city` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `region` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `country` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `coordinates` point DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `locations`
--

INSERT INTO `locations` (`id`, `city`, `region`, `country`, `coordinates`, `is_active`) VALUES
(1, 'Yaoundé', 'Centre', 'Cameroun', 0x00000000010100000062105839b4c80e40a9a44e4013012740, 1),
(2, 'Douala', 'Littoral', 'Cameroun', 0x0000000001010000006a4df38e53341040613255302a892340, 1),
(3, 'Garoua', 'Nord', 'Cameroun', 0x0000000001010000005f07ce19519a2240d3dee00b93c92a40, 1),
(4, 'Bamenda', 'Nord-Ouest', 'Cameroun', 0x00000000010100000082e2c798bbd61740986e1283c04a2440, 1),
(5, 'Maroua', 'Extrême-Nord', 'Cameroun', 0x000000000101000000c5feb27bf2302540bc0512143fa62c40, 1),
(6, 'Bafoussam', 'Ouest', 'Cameroun', 0x000000000101000000454772f90fe91540849ecdaacfd52440, 1),
(7, 'Ngaoundéré', 'Adamaoua', 'Cameroun', 0x000000000101000000a3923a014d441d407b832f4ca62a2b40, 1),
(8, 'Bertoua', 'Est', 'Cameroun', 0x00000000010100000057ec2fbb274f1240cac342ad695e2b40, 1),
(9, 'Loum', 'Littoral', 'Cameroun', 0x0000000001010000003d2cd49ae6dd12404850fc1873772340, 1),
(10, 'Kumba', 'Sud-Ouest', 'Cameroun', 0x0000000001010000003411363cbd9212401b0de02d90e02240, 1),
(11, 'Edéa', 'Littoral', 'Cameroun', 0x0000000001010000006666666666660e40151dc9e53f442440, 1),
(12, 'Foumban', 'Ouest', 'Cameroun', 0x0000000001010000003d2cd49ae6dd1640857cd0b359d52540, 1),
(13, 'Dschang', 'Ouest', 'Cameroun', 0x000000000101000000cdcccccccccc154052499d8026222440, 1),
(14, 'Mbouda', 'Ouest', 'Cameroun', 0x0000000001010000002a3a92cb7f8816400000000000802440, 1),
(15, 'Ebolowa', 'Sud', 'Cameroun', 0x0000000001010000003333333333330740cdcccccccc4c2640, 1),
(16, 'Kousséri', 'Extrême-Nord', 'Cameroun', 0x0000000001010000007b832f4ca62a2840e2e995b20c112e40, 1),
(17, 'Guider', 'Nord', 'Cameroun', 0x000000000101000000aeb6627fd9dd23406666666666e62b40, 1),
(18, 'Figuil', 'Nord', 'Cameroun', 0x000000000101000000b8af03e78c882340151dc9e53fc42b40, 1),
(19, 'Mbalmayo', 'Centre', 'Cameroun', 0x000000000101000000e0be0e9c33220c400000000000002740, 1),
(20, 'Eseka', 'Centre', 'Cameroun', 0x0000000001010000003333333333330d40b8af03e78c882540, 1),
(21, 'Mfou', 'Centre', 'Cameroun', 0x00000000010100000013f241cf66550f40151dc9e53f442740, 1),
(22, 'Nkongsamba', 'Littoral', 'Cameroun', 0x0000000001010000003d2cd49ae6dd1340aeb6627fd9dd2340, 1),
(23, 'Tiko', 'Sud-Ouest', 'Cameroun', 0x000000000101000000c6dcb5847c501040b1506b9a77bc2240, 1),
(24, 'Limbe', 'Sud-Ouest', 'Cameroun', 0x000000000101000000705f07ce191110401e166a4df36e2240, 1),
(25, 'Buea', 'Sud-Ouest', 'Cameroun', 0x0000000001010000001f85eb51b89e104011363cbd52762240, 1),
(26, 'Kribi', 'Sud', 'Cameroun', 0x000000000101000000bada8afd65770740857cd0b359d52340, 1),
(27, 'Sangmélima', 'Sud', 'Cameroun', 0x000000000101000000bada8afd657707404850fc1873f72740, 1),
(28, 'Yagoua', 'Extrême-Nord', 'Cameroun', 0x0000000001010000003333333333b324404850fc1873772e40, 1),
(29, 'Mokolo', 'Extrême-Nord', 'Cameroun', 0x000000000101000000000000000080254052499d8026a22b40, 1),
(30, 'Bafia', 'Centre', 'Cameroun', 0x00000000010100000000000000000013404850fc1873772640, 1),
(31, 'Wum', 'Nord-Ouest', 'Cameroun', 0x0000000001010000002a3a92cb7f88194052499d8026222440, 1),
(32, 'Fundong', 'Nord-Ouest', 'Cameroun', 0x0000000001010000000000000000001940b8af03e78c882440, 1),
(33, 'Banyo', 'Adamaoua', 'Cameroun', 0x0000000001010000000000000000001b4052499d8026a22740, 1),
(34, 'Tibati', 'Adamaoua', 'Cameroun', 0x0000000001010000003d2cd49ae6dd1940151dc9e53f442940, 1),
(35, 'Meiganga', 'Adamaoua', 'Cameroun', 0x000000000101000000705f07ce19111a409a99999999992c40, 1),
(36, 'Abong Mbang', 'Est', 'Cameroun', 0x0000000001010000002041f163ccdd0f40aeb6627fd95d2a40, 1),
(37, 'Batouri', 'Est', 'Cameroun', 0x0000000001010000005d6dc5feb2bb1140ebe2361ac0bb2c40, 1),
(38, 'Belabo', 'Est', 'Cameroun', 0x0000000001010000005d6dc5feb2bb13409a99999999992a40, 1),
(39, 'Nanga Eboko', 'Centre', 'Cameroun', 0x0000000001010000005d6dc5feb2bb1240ebe2361ac0bb2840, 1),
(40, 'Obala', 'Centre', 'Cameroun', 0x00000000010100000009f9a067b3aa1040e2e995b20c112740, 1),
(41, 'Monatélé', 'Centre', 'Cameroun', 0x000000000101000000f7065f984c5512407b832f4ca62a2740, 1),
(42, 'Bangangté', 'Ouest', 'Cameroun', 0x0000000001010000009a99999999991440e2e995b20c112540, 1),
(43, 'Bafang', 'Ouest', 'Cameroun', 0x0000000001010000009a99999999991440aeb6627fd95d2440, 1),
(44, 'Foumbot', 'Ouest', 'Cameroun', 0x000000000101000000705f07ce19111640151dc9e53f442540, 1),
(45, 'Penja', 'Littoral', 'Cameroun', 0x0000000001010000002a3a92cb7f881240857cd0b359552340, 1),
(46, 'Manjo', 'Littoral', 'Cameroun', 0x000000000101000000666666666666134052499d8026a22340, 1),
(47, 'Melong', 'Littoral', 'Cameroun', 0x000000000101000000d6c56d34807714406666666666e62340, 1),
(48, 'Muyuka', 'Sud-Ouest', 'Cameroun', 0x0000000001010000003333333333331140857cd0b359d52240, 1),
(49, 'Idenao', 'Sud-Ouest', 'Cameroun', 0x000000000101000000000000000000114052499d8026a22240, 1),
(50, 'Mamfe', 'Sud-Ouest', 'Cameroun', 0x000000000101000000705f07ce19111740e2e995b20c912240, 1);

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `ad_id` int UNSIGNED NOT NULL,
  `parent_id` int UNSIGNED DEFAULT NULL,
  `type` enum('comment','question','answer','review') COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `rating` tinyint DEFAULT NULL,
  `images` json DEFAULT NULL,
  `status` enum('visible','hidden','deleted') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'visible',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_user_id_foreign` (`user_id`),
  KEY `messages_ad_id_foreign` (`ad_id`),
  KEY `messages_parent_id_foreign` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `migrations`
--

INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
(1, '2025-09-19-000023', 'App\\Database\\Migrations\\CreateUsersTableAligned', 'default', 'App', 1758284330, 1),
(2, '2025-09-19-000024', 'App\\Database\\Migrations\\CreateRolesTableAligned', 'default', 'App', 1758285202, 2),
(3, '2025-09-19-000025', 'App\\Database\\Migrations\\CreateLocationsTableAligned', 'default', 'App', 1758285202, 2),
(4, '2025-09-19-000026', 'App\\Database\\Migrations\\CreateCategoriesTableAligned', 'default', 'App', 1758285306, 3),
(5, '2025-09-19-000027', 'App\\Database\\Migrations\\CreateSubcategoriesTableAligned', 'default', 'App', 1758285306, 3),
(6, '2025-09-19-000028', 'App\\Database\\Migrations\\CreateBrandsTableAligned', 'default', 'App', 1758285306, 3),
(7, '2025-09-19-000029', 'App\\Database\\Migrations\\CreateAdsTableAligned', 'default', 'App', 1758285306, 3),
(8, '2025-09-19-000030', 'App\\Database\\Migrations\\CreateAdPromotionsTableAligned', 'default', 'App', 1758285306, 3),
(9, '2025-09-19-000031', 'App\\Database\\Migrations\\CreateAdPhotosTableAligned', 'default', 'App', 1758285306, 3),
(10, '2025-09-19-000032', 'App\\Database\\Migrations\\CreateFiltersTableAligned', 'default', 'App', 1758285306, 3),
(11, '2025-09-19-000033', 'App\\Database\\Migrations\\CreateFilterOptionsTableAligned', 'default', 'App', 1758285306, 3),
(12, '2025-09-19-000034', 'App\\Database\\Migrations\\CreateAdFilterValuesTableAligned', 'default', 'App', 1758285306, 3),
(13, '2025-09-19-000035', 'App\\Database\\Migrations\\CreatePaymentsTableAligned', 'default', 'App', 1758285337, 4),
(14, '2025-09-19-000036', 'App\\Database\\Migrations\\CreateMessagesTableAligned', 'default', 'App', 1758285337, 4),
(15, '2025-09-19-000037', 'App\\Database\\Migrations\\CreateReferralCodesTableAligned', 'default', 'App', 1758285361, 5),
(16, '2025-09-19-000038', 'App\\Database\\Migrations\\CreateReferralUsesTableAligned', 'default', 'App', 1758285361, 5),
(17, '2025-09-19-000039', 'App\\Database\\Migrations\\CreateSellerProfilesTableAligned', 'default', 'App', 1758285361, 5),
(18, '2025-09-19-000040', 'App\\Database\\Migrations\\CreateReportsTableAligned', 'default', 'App', 1758285361, 5),
(19, '2025-09-22-135933', 'App\\Database\\Migrations\\CreateUsersTable', 'default', 'App', 1758549599, 6);

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `ad_id` int UNSIGNED NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','paid','failed','refunded') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `payments_user_id_foreign` (`user_id`),
  KEY `payments_ad_id_foreign` (`ad_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `referral_codes`
--

DROP TABLE IF EXISTS `referral_codes`;
CREATE TABLE IF NOT EXISTS `referral_codes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `max_uses` int NOT NULL DEFAULT '0',
  `current_uses` int NOT NULL DEFAULT '0',
  `bonus_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `referral_codes_user_id_foreign` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `referral_uses`
--

DROP TABLE IF EXISTS `referral_uses`;
CREATE TABLE IF NOT EXISTS `referral_uses` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `referral_code_id` int UNSIGNED NOT NULL,
  `referrer_id` int UNSIGNED NOT NULL,
  `referred_user_id` int UNSIGNED NOT NULL,
  `ad_id` int UNSIGNED NOT NULL,
  `bonus_earned` decimal(15,2) NOT NULL DEFAULT '0.00',
  `used_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `referral_uses_referral_code_id_foreign` (`referral_code_id`),
  KEY `referral_uses_referrer_id_foreign` (`referrer_id`),
  KEY `referral_uses_referred_user_id_foreign` (`referred_user_id`),
  KEY `referral_uses_ad_id_foreign` (`ad_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reports`
--

DROP TABLE IF EXISTS `reports`;
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporter_id` int UNSIGNED NOT NULL,
  `reported_user_id` int UNSIGNED NOT NULL,
  `reported_ad_id` int UNSIGNED NOT NULL,
  `report_type` enum('user','ad') COLLATE utf8mb4_general_ci NOT NULL,
  `report_reason` enum('spam','fraud','abuse','other') COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `evidence_files` json DEFAULT NULL,
  `status` enum('pending','handled','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `admin_notes` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `handled_by` int UNSIGNED DEFAULT NULL,
  `handled_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `reports_reporter_id_foreign` (`reporter_id`),
  KEY `reports_reported_user_id_foreign` (`reported_user_id`),
  KEY `reports_reported_ad_id_foreign` (`reported_ad_id`),
  KEY `reports_handled_by_foreign` (`handled_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `permissions` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `name`, `permissions`) VALUES
(1, 'admin', '[\"all\"]'),
(2, 'vendeur', '[\"create_ads\", \"edit_ads\", \"delete_ads\", \"message_users\"]'),
(3, 'acheteur', '[\"view_ads\", \"message_sellers\", \"add_favorites\"]');

-- --------------------------------------------------------

--
-- Structure de la table `seller_profiles`
--

DROP TABLE IF EXISTS `seller_profiles`;
CREATE TABLE IF NOT EXISTS `seller_profiles` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `business_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `business_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `business_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `business_phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `business_email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `opening_hours` json DEFAULT NULL,
  `delivery_options` json DEFAULT NULL,
  `website_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `facebook_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instagram_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logo_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `verification_status` enum('pending','verified','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_general_ci,
  `verified_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_profiles_user_id_foreign` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `seller_profiles`
--

INSERT INTO `seller_profiles` (`id`, `user_id`, `business_name`, `business_description`, `business_address`, `business_phone`, `business_email`, `opening_hours`, `delivery_options`, `website_url`, `facebook_url`, `instagram_url`, `logo_url`, `is_verified`, `verification_status`, `rejection_reason`, `verified_at`, `is_active`, `created_at`, `updated_at`) VALUES
(7, 33, 'Electronic Store', 'produits fiables et de qualité', 'Yaoundé Avenue Kennedy', '+237699111112', 'electronic@gmail.com', '{\"friday\": {\"open\": \"09:00\", \"close\": \"18:00\", \"closed\": false}, \"monday\": {\"open\": \"09:00\", \"close\": \"18:00\", \"closed\": false}, \"sunday\": {\"open\": \"10:00\", \"close\": \"16:00\", \"closed\": true}, \"tuesday\": {\"open\": \"09:00\", \"close\": \"18:00\", \"closed\": false}, \"saturday\": {\"open\": \"09:00\", \"close\": \"17:00\", \"closed\": true}, \"thursday\": {\"open\": \"09:00\", \"close\": \"18:00\", \"closed\": false}, \"wednesday\": {\"open\": \"09:00\", \"close\": \"18:00\", \"closed\": false}}', '{\"pickup\": true, \"delivery\": true, \"shipping\": false}', '', '', '', NULL, 0, 'pending', NULL, NULL, 1, '2025-09-27 08:09:28', '2025-09-27 08:09:28'),
(2, 12, 'ACME Store', 'Vente d\'électronique', NULL, '+237600000000', NULL, '\"{\\\"monday\\\":\\\"9-17\\\",\\\"tuesday\\\":\\\"9-17\\\"},\"', NULL, NULL, NULL, NULL, 'uploads/seller_logos/1758550115_9c37bb8e0df980fc2d38.png', 0, 'pending', NULL, NULL, 1, '2025-09-22 14:08:35', '2025-09-22 14:08:35'),
(6, 32, 'Mon Super Business', 'Description mise à jour', 'douala', '+33123456789', 'email@gmail.com', '{\"friday\": {\"open\": \"09:00\", \"close\": \"18:00\", \"closed\": false}, \"monday\": {\"open\": \"09:00\", \"close\": \"18:00\", \"closed\": false}, \"sunday\": {\"open\": \"10:00\", \"close\": \"16:00\", \"closed\": true}, \"tuesday\": {\"open\": \"09:00\", \"close\": \"18:00\", \"closed\": false}, \"saturday\": {\"open\": \"09:00\", \"close\": \"17:00\", \"closed\": false}, \"thursday\": {\"open\": \"09:00\", \"close\": \"18:00\", \"closed\": false}, \"wednesday\": {\"open\": \"09:00\", \"close\": \"18:00\", \"closed\": false}}', '{\"pickup\": true, \"delivery\": true, \"shipping\": false}', 'https://monsite.com', '', '', NULL, 0, 'pending', NULL, NULL, 1, '2025-09-22 15:42:35', '2025-09-23 08:58:34');

-- --------------------------------------------------------

--
-- Structure de la table `subcategories`
--

DROP TABLE IF EXISTS `subcategories`;
CREATE TABLE IF NOT EXISTS `subcategories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int UNSIGNED NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `icon_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `subcategories_category_id_foreign` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `subcategories`
--

INSERT INTO `subcategories` (`id`, `category_id`, `slug`, `name`, `icon_path`, `is_active`, `display_order`) VALUES
(1, 1, 'smartphones', 'Smartphones', 'icons/smartphones.svg', 1, 1),
(2, 1, 'ordinateurs', 'Ordinateurs', 'icons/computers.svg', 1, 2),
(3, 1, 'tablettes', 'Tablettes', 'icons/tablets.svg', 1, 3),
(4, 1, 'tv-video', 'TV & Vidéo', 'icons/tv.svg', 1, 4),
(5, 1, 'photo-camera', 'Photo & Caméras', 'icons/camera.svg', 1, 5),
(6, 2, 'voitures', 'Voitures', 'icons/cars.svg', 1, 1),
(7, 2, 'motos', 'Motos', 'icons/motorcycles.svg', 1, 2),
(8, 2, 'velos', 'Vélos', 'icons/bikes.svg', 1, 3),
(9, 2, 'pieces-auto', 'Pièces auto', 'icons/car-parts.svg', 1, 4),
(10, 2, 'camions', 'Camions', 'icons/trucks.svg', 1, 5),
(11, 3, 'appartements', 'Appartements', 'icons/apartments.svg', 1, 1),
(12, 3, 'maisons', 'Maisons', 'icons/houses.svg', 1, 2),
(13, 3, 'terrains', 'Terrains', 'icons/land.svg', 1, 3),
(14, 3, 'bureaux', 'Bureaux', 'icons/offices.svg', 1, 4),
(15, 3, 'locations', 'Locations', 'icons/rentals.svg', 1, 5),
(16, 4, 'informatique', 'Informatique', 'icons/it-jobs.svg', 1, 1),
(17, 4, 'commerce', 'Commerce', 'icons/sales-jobs.svg', 1, 2),
(18, 4, 'restauration', 'Restauration', 'icons/restaurant-jobs.svg', 1, 3),
(19, 4, 'batiment', 'Bâtiment', 'icons/construction-jobs.svg', 1, 4),
(20, 4, 'sante', 'Santé', 'icons/health-jobs.svg', 1, 5),
(21, 5, 'reparation', 'Réparation', 'icons/repair.svg', 1, 1),
(22, 5, 'nettoyage', 'Nettoyage', 'icons/cleaning.svg', 1, 2),
(23, 5, 'cours', 'Cours', 'icons/teaching.svg', 1, 3),
(24, 5, 'transport', 'Transport', 'icons/transport.svg', 1, 4),
(25, 5, 'beaute', 'Beauté', 'icons/beauty.svg', 1, 5),
(26, 6, 'vetements-hommes', 'Vêtements Hommes', 'icons/men-clothing.svg', 1, 1),
(27, 6, 'vetements-femmes', 'Vêtements Femmes', 'icons/women-clothing.svg', 1, 2),
(28, 6, 'chaussures', 'Chaussures', 'icons/shoes.svg', 1, 3),
(29, 6, 'sacs', 'Sacs', 'icons/bags.svg', 1, 4),
(30, 6, 'bijoux', 'Bijoux', 'icons/jewelry.svg', 1, 5),
(31, 7, 'meubles', 'Meubles', 'icons/furniture.svg', 1, 1),
(32, 7, 'electromenager', 'Électroménager', 'icons/appliances.svg', 1, 2),
(33, 7, 'decoration', 'Décoration', 'icons/decor.svg', 1, 3),
(34, 7, 'jardinage', 'Jardinage', 'icons/gardening.svg', 1, 4),
(35, 7, 'bricolage', 'Bricolage', 'icons/diy.svg', 1, 5),
(36, 8, 'fitness', 'Fitness', 'icons/fitness.svg', 1, 1),
(37, 8, 'football', 'Football', 'icons/football.svg', 1, 2),
(38, 8, 'basketball', 'Basketball', 'icons/basketball.svg', 1, 3),
(39, 8, 'tennis', 'Tennis', 'icons/tennis.svg', 1, 4),
(40, 8, 'natation', 'Natation', 'icons/swimming.svg', 1, 5),
(41, 9, 'chiens', 'Chiens', 'icons/dogs.svg', 1, 1),
(42, 9, 'chats', 'Chats', 'icons/cats.svg', 1, 2),
(43, 9, 'oiseaux', 'Oiseaux', 'icons/birds.svg', 1, 3),
(44, 9, 'poissons', 'Poissons', 'icons/fish.svg', 1, 4),
(45, 9, 'accessoires-animaux', 'Accessoires', 'icons/pet-accessories.svg', 1, 5),
(46, 10, 'produits-agricoles', 'Produits Agricoles', 'icons/farm-products.svg', 1, 1),
(47, 10, 'machines-agricoles', 'Machines Agricoles', 'icons/farm-machines.svg', 1, 2),
(48, 10, 'engrais', 'Engrais', 'icons/fertilizers.svg', 1, 3),
(49, 10, 'semences', 'Semences', 'icons/seeds.svg', 1, 4),
(50, 10, 'elevage', 'Élevage', 'icons/livestock.svg', 1, 5);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` int UNSIGNED NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `photo_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `otp_code` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verification_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `google_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `facebook_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_identity_verified` tinyint(1) NOT NULL DEFAULT '0',
  `identity_document_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `identity_document_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `identity_document_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `identity_verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`),
  KEY `users_role_id_foreign` (`role_id`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id_user`, `role_id`, `slug`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `photo_url`, `otp_code`, `otp_expires_at`, `is_verified`, `verification_token`, `google_id`, `facebook_id`, `is_identity_verified`, `identity_document_type`, `identity_document_number`, `identity_document_url`, `identity_verified_at`, `created_at`, `updated_at`, `deleted`) VALUES
(12, 2, 'john-updated-doe-updated', 'John Updated', 'Doe Updated', 'john.doe@example.com', '+33111222334', '$2y$10$aY8HLqttKAQOxd0N6e6KWu3hQulmKewY6SS5OpFAj1Tm0MLRQNDtW', '', NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-09-21 21:19:53', '2025-09-22 12:57:42', NULL),
(13, 2, 'marc-dupond', 'Marc', 'Dupond', 'marc.dupond@example.com', '+237699028745', '$2y$12$TD4WqPM6K2A22UGbWdVFnuY3cVm9cn1McFN0HuQckSJ3fboB8UHgK', '', NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-09-21 21:34:57', '2025-09-21 21:34:57', NULL),
(14, 2, 'marco-duponda', 'Marco', 'Duponda', 'marco@gmail.com', '+33699857412', '$2y$12$boIeyEQr7rMvvZRvUCSxN.gvO1jF7VWmNyGTtbfzbrMo4TEM1/mS.', '', NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-09-22 10:54:25', '2025-09-22 10:54:25', NULL),
(15, 2, 'jean-dupont', 'Jean', 'Dupont', 'jean.dupont@example.com', '+237655443322', '$2y$10$hashedpassword1', 'photos/jean.jpg', NULL, NULL, 1, NULL, NULL, NULL, 1, 'CNI', '123456789', 'docs/cni_jean.jpg', '2025-01-15 10:00:00', '2025-01-01 09:00:00', '2025-01-15 10:00:00', NULL),
(16, 2, 'marie-curie', 'Marie', 'Curie', 'marie.curie@example.com', '+237688776655', '$2y$10$hashedpassword2', 'photos/marie.jpg', NULL, NULL, 1, NULL, NULL, NULL, 1, 'Passeport', 'P987654321', 'docs/pass_marie.jpg', '2025-01-20 14:30:00', '2025-01-02 10:00:00', '2025-01-20 14:30:00', NULL),
(17, 2, 'test-user', 'Test', 'User', 'test@example.com', '', '$2y$12$DyU5CYIPqayU2UoIQiX.keMpD1dWZntCJUBp3IUzyQKeKRI5LOKte', NULL, NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-09-22 14:30:18', '2025-09-22 14:30:18', NULL),
(18, 2, 'franck-doe', 'Franck', 'Doe', 'franck@gmail.com', '+33699208745', '$2y$10$rSHRphSGVE2KxI3SrIsE6OAB3xh6dTuXIxmNwU2EaZY5DnWrNyjCq', '', NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-09-22 14:34:01', '2025-09-22 14:34:01', NULL),
(19, 2, 'franck-1-doe', 'Franck 1', 'Doe', 'franck1@gmail.com', '+33699021455', '$2y$10$7vn1gryLbwhfkSaYedvbqOXqa9STzcwS4HY44p58t2qj0jKD6RuWG', '', NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-09-22 14:37:57', '2025-09-22 14:37:57', NULL),
(20, 2, 'marco1-dupond', 'Marco1', 'Dupond', 'marco@cambizzle.com', '+33699021111', '$2y$10$0CKXXKg6Qd3OF3Fe3hdsM.l4zY8nJw9CkoNsw3NigeAfv8YF5Rq7y', '', NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-09-22 14:39:27', '2025-09-22 14:39:27', NULL),
(32, 2, 'franck-test-put', 'Franck', 'Test PUT', 'vendeur@cambizzle.com', '+237699111111', '$2y$12$RVDhxNIfjgQDtWmCJssTjuBWf9ROnt3tSIciMDLneAhqreg0LTvyi', '', NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-09-22 15:42:08', '2025-09-23 12:21:30', NULL),
(24, 2, 'johny-doe', 'Johny', 'Doe', 'johny.doe@example.com', '+33123456789', '$2y$12$Iuf8hdrZ0F1ku3SWU9TEHOiRRx6v4dvOBkee.f7I8IRf3k8fztgSi', NULL, NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-09-22 14:59:16', '2025-09-22 14:59:16', NULL),
(33, 2, 'franck-stephane', 'Franck', 'Stephane', 'franck.stephane@gmail.com', '+237699111112', '$2y$12$2z3M72dIR2ks1HpYQWfezeRYXE1Kuqm3sVnc0WiPtnzkc6VDXX48y', 'uploads/avatars/1758965908_80b48347c872636c8ab2.jpg', NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-09-27 08:08:09', '2025-09-27 09:38:56', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
