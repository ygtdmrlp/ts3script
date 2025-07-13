-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 13 Tem 2025, 10:27:25
-- Sunucu sürümü: 9.1.0
-- PHP Sürümü: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `ts3_management`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `activities`
--

DROP TABLE IF EXISTS `activities`;
CREATE TABLE IF NOT EXISTS `activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `activities`
--

INSERT INTO `activities` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'Kullanıcı giriş yaptı', '::1', '2025-07-13 09:25:31'),
(2, 1, 'logout', 'Kullanıcı çıkış yaptı', '::1', '2025-07-13 09:36:34'),
(3, 1, 'login', 'Kullanıcı giriş yaptı', '::1', '2025-07-13 09:36:47'),
(4, 1, 'update_settings', 'Sunucu ayarları güncellendi', '::1', '2025-07-13 09:39:46'),
(5, 1, 'update_settings', 'Sunucu ayarları güncellendi', '::1', '2025-07-13 09:41:43'),
(6, 1, 'logout', 'Kullanıcı çıkış yaptı', '::1', '2025-07-13 09:45:51'),
(7, 1, 'login', 'Kullanıcı giriş yaptı', '::1', '2025-07-13 09:46:36'),
(8, 1, 'logout', 'Kullanıcı çıkış yaptı', '::1', '2025-07-13 09:48:38'),
(9, 1, 'login', 'Kullanıcı giriş yaptı', '::1', '2025-07-13 09:48:44'),
(10, 1, 'logout', 'Kullanıcı çıkış yaptı', '::1', '2025-07-13 09:50:03'),
(11, 1, 'login', 'Kullanıcı giriş yaptı', '::1', '2025-07-13 09:50:23');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'ts3_host', 'oui.lemehost.com', '2025-07-13 09:39:46'),
(2, 'ts3_port', '21219', '2025-07-13 09:39:46'),
(3, 'ts3_username', 'user_643559.2fe50476', '2025-07-13 09:39:46'),
(4, 'ts3_password', '6dAxataAjkfjbhhSznF6-58eGE7a5U4P', '2025-07-13 09:39:46'),
(5, 'ts3_server_port', '10080', '2025-07-13 09:41:43'),
(6, 'site_title', 'TS3 Yönetim Paneli', '2025-07-13 09:24:23'),
(7, 'max_clients', '100', '2025-07-13 09:24:23'),
(8, 'auto_backup', '1', '2025-07-13 09:24:23'),
(9, 'session_timeout', '3600', '2025-07-13 09:39:46'),
(10, 'log_retention_days', '30', '2025-07-13 09:39:46');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ts3_channels`
--

DROP TABLE IF EXISTS `ts3_channels`;
CREATE TABLE IF NOT EXISTS `ts3_channels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `channel_id` int NOT NULL,
  `channel_name` varchar(100) DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `max_clients` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ts3_clients`
--

DROP TABLE IF EXISTS `ts3_clients`;
CREATE TABLE IF NOT EXISTS `ts3_clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `unique_id` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `connected_time` int DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_online` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','moderator','user') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `last_login`, `is_active`) VALUES
(1, 'admin', 'admin@ts3.com', '$2y$10$B6mg4vup4OdfVxj/styoluZOpmqGUht93m1a5PR7crxRQkIXxx7Uu', 'admin', '2025-07-13 09:24:23', '2025-07-13 09:50:23', 1);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
