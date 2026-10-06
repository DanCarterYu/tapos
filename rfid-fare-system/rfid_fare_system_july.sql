-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 18, 2026 at 12:33 PM
-- Server version: 8.4.7
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rfid_fare_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `full_name`, `created_at`) VALUES
(1, 'admin', '$2y$10$fVctwfCikWOVb.877PFRt.ZVmS2SUrb//nzw.KDlfFZHmw.ZpslM.', 'System Administrator', '2026-06-13 05:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `balance_loads`
--

DROP TABLE IF EXISTS `balance_loads`;
CREATE TABLE IF NOT EXISTS `balance_loads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `passenger_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `loaded_by` int DEFAULT NULL,
  `load_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `passenger_id` (`passenger_id`),
  KEY `loaded_by` (`loaded_by`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `balance_loads`
--

INSERT INTO `balance_loads` (`id`, `passenger_id`, `amount`, `reference_no`, `loaded_by`, `load_date`) VALUES
(1, 1, 100.00, 'LOAD-20260613055256297', 1, '2026-06-13 05:52:56'),
(2, 2, 1000.00, 'LOAD-20260613060406806', 1, '2026-06-13 06:04:06'),
(3, 2, 10.00, 'LOAD-20260705040247650', NULL, '2026-07-05 04:02:47'),
(4, 7, 100.00, 'LOAD-20260708025640980', 1, '2026-07-08 02:56:40'),
(5, 8, 500.00, 'LOAD-20260709054709943', 1, '2026-07-09 05:47:09'),
(6, 10, 1000.00, 'LOAD-20260718114534757', 1, '2026-07-18 11:45:34');

-- --------------------------------------------------------

--
-- Table structure for table `collectors`
--

DROP TABLE IF EXISTS `collectors`;
CREATE TABLE IF NOT EXISTS `collectors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_number` (`employee_number`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `collectors`
--

INSERT INTO `collectors` (`id`, `employee_number`, `password`, `full_name`, `contact`, `status`, `created_by`, `created_at`) VALUES
(6, 'VGL-002', '$2y$10$dN7KokaE5MYwl1P5IZ47R.Tphx5D95ZcA7R3m34tog2M3GGID0Jfy', 'test', '066650654545444', 'active', 1, '2026-06-15 11:14:22'),
(5, 'VGL-001', '$2y$10$sUficJMmW8aDkKmeLcH6UezX5eT36A84DkE5x27sT6ivjI3Lq6A72', 'dan', '0666506s', 'active', 1, '2026-06-15 11:14:14'),
(8, 'VGL-003', '$2y$10$AaN7EnqVbHIoveZ5yt9bwOYHgBPlSrj4sF06nkk94MI17cIMqsUiC', 'dan carter yu', '', 'active', 1, '2026-07-09 05:44:28');

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

DROP TABLE IF EXISTS `destinations`;
CREATE TABLE IF NOT EXISTS `destinations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_fare` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `base_fare`, `is_active`, `created_at`) VALUES
(1, 'Dinagat Island', 50.00, 1, '2026-06-13 05:33:04'),
(2, 'San Jose', 35.00, 0, '2026-06-13 05:33:04'),
(3, 'Loreto', 65.00, 1, '2026-06-13 05:33:04'),
(4, 'Cagdianao', 45.00, 1, '2026-06-13 05:33:04'),
(5, 'Tubajon', 55.00, 1, '2026-06-13 05:33:04'),
(6, 'Siargao', 250.00, 0, '2026-06-13 06:35:58'),
(7, 'tokonh', 10.00, 1, '2026-07-08 02:58:33');

-- --------------------------------------------------------

--
-- Table structure for table `discount_types`
--

DROP TABLE IF EXISTS `discount_types`;
CREATE TABLE IF NOT EXISTS `discount_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `percentage` decimal(5,2) DEFAULT '0.00',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `discount_types`
--

INSERT INTO `discount_types` (`id`, `name`, `percentage`, `is_active`, `created_at`) VALUES
(1, 'senior', 20.00, 1, '2026-06-13 05:33:04'),
(2, 'pwd', 20.00, 1, '2026-06-13 05:33:04'),
(3, 'student', 10.00, 1, '2026-06-13 05:33:04'),
(4, 'regular', 0.00, 1, '2026-06-13 05:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_settings`
--

DROP TABLE IF EXISTS `loyalty_settings`;
CREATE TABLE IF NOT EXISTS `loyalty_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `points_per_peso` decimal(5,2) DEFAULT '0.10',
  `min_fare_for_points` decimal(10,2) DEFAULT '10.00',
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `updated_by` (`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loyalty_settings`
--

INSERT INTO `loyalty_settings` (`id`, `points_per_peso`, `min_fare_for_points`, `updated_by`, `updated_at`) VALUES
(1, 0.10, 10.00, NULL, '2026-06-13 05:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `passengers`
--

DROP TABLE IF EXISTS `passengers`;
CREATE TABLE IF NOT EXISTS `passengers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rfid_uid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middle_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT '0.00',
  `loyalty_points` int DEFAULT '0',
  `discount_type_id` int DEFAULT '4',
  `status` enum('active','lost','blocked') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rfid_uid` (`rfid_uid`),
  KEY `discount_type_id` (`discount_type_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`id`, `rfid_uid`, `first_name`, `middle_name`, `last_name`, `password`, `full_name`, `email`, `contact`, `balance`, `loyalty_points`, `discount_type_id`, `status`, `created_by`, `created_at`) VALUES
(16, '3211421460', 'JOHN ANGEL', '', 'YTAC', '$2y$10$LGGxPHqSSdzPg3/G62hAReCqht1/Lhe6BrS0v4RgdMuL7ILfXtBby', '', 'john@gmail.com', '066650654543232', 0.00, 0, 4, 'active', 1, '2026-07-18 10:36:48'),
(17, '2750631654', 'GEFERSON', '', 'SAMSON', '$2y$10$vRzZJWQ4U30a4/7fYpQgPuhCumvJDiQfEViilX7z23P.39cAhX3P6', '', 'gef@gmail.com', '22222', 0.00, 0, 4, 'active', 1, '2026-07-18 10:37:19'),
(10, '3217705764', 'DAN CARTER', 'GEOTINA', 'YU', '$2y$10$IZg7wvx7vAhhtHoU3kiROOzmX6saOZ1Qq9dHQP2FkDWC1.wdyt0Zu', '', 'dancarter.yuu@gmail.com', '0666506s', 920.00, 6, 4, 'active', 1, '2026-07-18 09:50:21'),
(18, '3484554904', 'GRACE', '', 'ROMERO', '$2y$10$96SbziABonA3dS3TolbKpOjB4HUwc4dF7dozYZob6LLPJUbFI6C5K', '', 'grace@gmail.com', '0666506s', 0.00, 0, 4, 'active', 1, '2026-07-18 10:37:53'),
(19, '3215720212', 'JUAN LUNA', '', 'DELA CRUZ', '$2y$10$QC3/uAE2U8CQBMvfQusmPe2631Cs.8AAc11B94llAUk/4OE7.cd0G', '', 'juanss@gmail.com', '0666506', 0.00, 0, 4, 'active', 1, '2026-07-18 10:39:33');

-- --------------------------------------------------------

--
-- Table structure for table `rfid_replacements`
--

DROP TABLE IF EXISTS `rfid_replacements`;
CREATE TABLE IF NOT EXISTS `rfid_replacements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `passenger_id` int NOT NULL,
  `old_rfid_uid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_rfid_uid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'lost',
  `replaced_by` int DEFAULT NULL,
  `replacement_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `passenger_id` (`passenger_id`),
  KEY `replaced_by` (`replaced_by`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rfid_replacements`
--

INSERT INTO `rfid_replacements` (`id`, `passenger_id`, `old_rfid_uid`, `new_rfid_uid`, `reason`, `replaced_by`, `replacement_date`) VALUES
(1, 1, '3484554904', '2750407878', 'lost', 1, '2026-06-13 05:53:23'),
(2, 7, '3213920020', '`3211421460', 'lost', 1, '2026-07-08 02:56:10'),
(3, 7, '`3211421460', '3213920020', 'lost', 1, '2026-07-08 02:57:12');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `passenger_id` int NOT NULL,
  `collector_id` int NOT NULL,
  `destination_id` int NOT NULL,
  `base_fare` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `net_payment` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `loyalty_points_earned` int DEFAULT '0',
  `transaction_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `passenger_id` (`passenger_id`),
  KEY `collector_id` (`collector_id`),
  KEY `destination_id` (`destination_id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `receipt_no`, `passenger_id`, `collector_id`, `destination_id`, `base_fare`, `discount_percentage`, `discount_amount`, `net_payment`, `balance_before`, `balance_after`, `loyalty_points_earned`, `transaction_date`) VALUES
(1, 'RC-20260615112838653', 2, 6, 1, 50.00, 0.00, 0.00, 50.00, 1000.00, 950.00, 5, '2026-06-15 11:28:38'),
(2, 'RC-20260615112929350', 2, 6, 1, 50.00, 0.00, 0.00, 50.00, 950.00, 900.00, 5, '2026-06-15 11:29:29'),
(3, 'RC-20260615112936269', 2, 6, 1, 50.00, 0.00, 0.00, 50.00, 900.00, 850.00, 5, '2026-06-15 11:29:36'),
(4, 'RC-20260615113026171', 2, 6, 1, 50.00, 0.00, 0.00, 50.00, 850.00, 800.00, 5, '2026-06-15 11:30:26'),
(5, 'RC-20260615113514195', 2, 6, 3, 65.00, 0.00, 0.00, 65.00, 800.00, 735.00, 6, '2026-06-15 11:35:14'),
(6, 'RC-20260615113521123', 2, 6, 3, 65.00, 0.00, 0.00, 65.00, 735.00, 670.00, 6, '2026-06-15 11:35:21'),
(7, 'RC-20260615113915925', 2, 6, 5, 55.00, 0.00, 0.00, 55.00, 670.00, 615.00, 5, '2026-06-15 11:39:15'),
(8, 'RC-20260615114239699', 2, 6, 1, 50.00, 20.00, 10.00, 40.00, 615.00, 575.00, 4, '2026-06-15 11:42:39'),
(9, 'RC-20260615114309915', 2, 6, 5, 55.00, 20.00, 11.00, 44.00, 575.00, 531.00, 4, '2026-06-15 11:43:09'),
(10, 'RC-20260622115913693', 2, 6, 4, 45.00, 10.00, 4.50, 40.50, 531.00, 490.50, 4, '2026-06-22 11:59:13'),
(11, 'RC-20260707045023750', 2, 6, 3, 65.00, 10.00, 6.50, 58.50, 500.50, 442.00, 5, '2026-07-07 04:50:23'),
(12, 'RC-20260707045109934', 2, 6, 3, 65.00, 0.00, 0.00, 65.00, 442.00, 377.00, 6, '2026-07-07 04:51:09'),
(13, 'RC-20260708015303836', 2, 6, 1, 50.00, 20.00, 10.00, 40.00, 377.00, 337.00, 4, '2026-07-08 01:53:03'),
(14, 'RC-20260708030357178', 2, 6, 7, 10.00, 0.00, 0.00, 10.00, 337.00, 327.00, 1, '2026-07-08 03:03:57'),
(15, 'RC-20260709054858775', 8, 6, 1, 50.00, 20.00, 10.00, 40.00, 500.00, 460.00, 4, '2026-07-09 05:48:58'),
(16, 'RC-20260718114546234', 10, 6, 4, 45.00, 20.00, 9.00, 36.00, 1000.00, 964.00, 3, '2026-07-18 11:45:46'),
(17, 'RC-20260718120201157', 10, 6, 4, 45.00, 20.00, 9.00, 36.00, 964.00, 928.00, 3, '2026-07-18 12:02:01'),
(18, 'RC-20260718121855250', 10, 6, 7, 10.00, 20.00, 2.00, 8.00, 928.00, 920.00, 0, '2026-07-18 12:18:55');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
