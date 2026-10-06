-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 22, 2026 at 09:16 AM
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
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `balance_loads`
--

INSERT INTO `balance_loads` (`id`, `passenger_id`, `amount`, `reference_no`, `loaded_by`, `load_date`) VALUES
(1, 1, 100.00, 'LOAD-20260613055256297', 1, '2026-06-13 05:52:56'),
(2, 2, 1000.00, 'LOAD-20260613060406806', 1, '2026-06-13 06:04:06'),
(3, 2, 10.00, 'LOAD-20260705040247650', NULL, '2026-07-05 04:02:47'),
(4, 7, 100.00, 'LOAD-20260708025640980', 1, '2026-07-08 02:56:40'),
(5, 8, 500.00, 'LOAD-20260709054709943', 1, '2026-07-09 05:47:09'),
(6, 10, 1000.00, 'LOAD-20260718114534757', 1, '2026-07-18 11:45:34'),
(7, 18, 500.00, 'LOAD-20260720115611211', 1, '2026-07-20 11:56:11'),
(8, 20, 100.00, 'LOAD-20260808065628581', 1, '2026-08-08 06:56:28'),
(9, 20, 99999999.99, 'LOAD-20260808065701278', 1, '2026-08-08 06:57:01'),
(10, 17, 23.00, 'LOAD-20260808115251810', 1, '2026-08-08 11:52:51'),
(11, 18, 989.00, 'LOAD-20260815031234600', 1, '2026-08-15 03:12:34'),
(12, 21, 100.00, 'LOAD-20260816060754598', 1, '2026-08-16 06:07:54'),
(13, 23, 100.00, 'LOAD-20260822091439964', 14, '2026-08-22 09:14:39');

-- --------------------------------------------------------

--
-- Table structure for table `balance_requests`
--

DROP TABLE IF EXISTS `balance_requests`;
CREATE TABLE IF NOT EXISTS `balance_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `passenger_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_paid` date NOT NULL,
  `receipt_image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `request_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` int DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_no` (`reference_no`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_balance_requests_status` (`status`),
  KEY `idx_balance_requests_passenger` (`passenger_id`),
  KEY `idx_balance_requests_date` (`request_date`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `balance_requests`
--

INSERT INTO `balance_requests` (`id`, `passenger_id`, `amount`, `payment_method`, `reference_no`, `sender_name`, `date_paid`, `receipt_image`, `status`, `rejection_reason`, `request_date`, `approved_by`, `approved_date`) VALUES
(1, 18, 100.00, 'GCash', '564564', 'SADASSDA', '2026-08-15', 'assets/uploads/requests/receipt_20260815_132608.png', 'approved', NULL, '2026-08-15 13:26:08', 1, '2026-08-15 13:29:00'),
(2, 18, 104.00, 'Maya', '56456452', 'SADAS', '2026-08-15', 'assets/uploads/requests/receipt_20260815_132644.png', 'approved', NULL, '2026-08-15 13:26:44', 1, '2026-08-15 13:29:08'),
(3, 10, 103.00, 'Maya', '56456455', 'ASDSDA', '2026-08-15', 'assets/uploads/requests/receipt_20260815_132722.png', 'rejected', 'fsdfsdf', '2026-08-15 13:27:22', 1, '2026-08-15 13:45:47'),
(4, 10, 100.00, 'Maya', '564564522', 'SADASSDA', '2026-08-16', 'assets/uploads/requests/receipt_20260816_063929.png', 'pending', NULL, '2026-08-16 06:39:29', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `collectors`
--

DROP TABLE IF EXISTS `collectors`;
CREATE TABLE IF NOT EXISTS `collectors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middle_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_number` (`employee_number`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `collectors`
--

INSERT INTO `collectors` (`id`, `employee_number`, `first_name`, `middle_name`, `last_name`, `password`, `full_name`, `contact`, `status`, `created_by`, `created_at`) VALUES
(15, 'VGL003', 'GEFERSON SA', '', 'SAMSON', '$2y$10$./eI4E3IOb3XKKgoyTS.LOjlVTfi38DpbJUL8N3WwGhorTXY7wA86', '', '066650621312', 'active', 1, '2026-07-20 05:48:48'),
(13, 'VGL001', 'JUAN LUNAASD', 'GEOTINA', 'DELA CRUZ', '$2y$10$1gLKxvFMyng2/jkIozA3gOc8NfvfFF5xb8u5nzqZadM9coKCUNCBC', '', '0666506', 'active', 1, '2026-07-20 05:48:24'),
(14, 'VGL002', 'DAN CSASD', '', 'YU', '$2y$10$sLzJOKxMMor3kRiHuy5SCOQYTE7Z94PdHgt9CXbRc2DLj778HRepW', '', 'sadsad', 'active', 1, '2026-07-20 05:48:37');

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

DROP TABLE IF EXISTS `destinations`;
CREATE TABLE IF NOT EXISTS `destinations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `origin` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_fare` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_route` (`origin`,`destination`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `origin`, `destination`, `base_fare`, `is_active`, `created_at`) VALUES
(1, 'SURIGAO CITY', 'DINAGAT', 12.00, 1, '2026-06-13 05:33:04'),
(3, 'SURIGAO CITY', 'NEMCO', 12.00, 1, '2026-06-13 05:33:04'),
(4, 'SURIGAO CITY', 'SDASD', 12.00, 1, '2026-06-13 05:33:04'),
(5, 'SURIGAO CITY', 'BOULEVARD', 12.00, 1, '2026-06-13 05:33:04'),
(7, 'SURIGAO CITY', '231', 10.00, 0, '2026-07-08 02:58:33'),
(11, '1', '2', 10.00, 1, '2026-08-16 06:23:53');

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
(1, 0.10, 10.00, 1, '2026-08-03 06:17:46');

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
  `created_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by_role` enum('management','collector') COLLATE utf8mb4_unicode_ci DEFAULT 'management',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rfid_uid` (`rfid_uid`),
  KEY `discount_type_id` (`discount_type_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`id`, `rfid_uid`, `first_name`, `middle_name`, `last_name`, `password`, `full_name`, `email`, `contact`, `balance`, `loyalty_points`, `discount_type_id`, `status`, `created_by`, `created_at`, `created_by_role`) VALUES
(16, '3213920020', 'JOHN ANGEL', '', 'YTAC', '$2y$10$LGGxPHqSSdzPg3/G62hAReCqht1/Lhe6BrS0v4RgdMuL7ILfXtBby', '', 'john@gmail.com', '066650654543232', 0.00, 0, 4, 'active', '1', '2026-07-18 10:36:48', 'management'),
(17, '2750631654', 'GEFERSON', '', 'SAMSON', '$2y$10$vRzZJWQ4U30a4/7fYpQgPuhCumvJDiQfEViilX7z23P.39cAhX3P6', '', 'gef@gmail.com', '22222', 6.00, 0, 4, 'active', '1', '2026-07-18 10:37:19', 'management'),
(10, '3217705764', 'DAN CA', '', 'YU', '$2y$10$FlWmbR.K15B5uY48Y1D00OpqOC1MBtTADwDmlDk38YRdsYAJ.a7E.', '', 'dancarter.yuu@gmail.comas', '0666506ssad', 163.00, 79, 4, 'active', '1', '2026-07-18 09:50:21', 'management'),
(18, '3484554904', 'GRACE', '', 'ROMERO', '$2y$10$96SbziABonA3dS3TolbKpOjB4HUwc4dF7dozYZob6LLPJUbFI6C5K', '', 'grace@gmail.com', '0666506s', 1548.00, 13, 4, 'active', '1', '2026-07-18 10:37:53', 'management'),
(22, '2739603046', 'PASSENGER TAP', '', 'TTEST', '$2y$10$ivezBzDSPCzftuTUfbs.9eYSxScXEEZ3xhhyQ4L5lG9aGfMwmTg6W', '', 'yu.dan.carter.15@gmail.com', '22222', 0.00, 0, 4, 'active', 'VGL003', '2026-08-22 08:55:13', 'collector'),
(23, '3215720212', 'TEAP', '', 'TEST', '$2y$10$MuPl5ZBpkoBBgmqP6XiLEuDFb1K7PR0V753xd9Py8ftgK31vapPge', '', 'afirst562@gmai.com', '22222', 100.00, 0, 4, 'active', 'VGL002', '2026-08-22 09:02:11', 'collector');

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
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rfid_replacements`
--

INSERT INTO `rfid_replacements` (`id`, `passenger_id`, `old_rfid_uid`, `new_rfid_uid`, `reason`, `replaced_by`, `replacement_date`) VALUES
(1, 1, '3484554904', '2750407878', 'lost', 1, '2026-06-13 05:53:23'),
(2, 7, '3213920020', '`3211421460', 'lost', 1, '2026-07-08 02:56:10'),
(3, 7, '`3211421460', '3213920020', 'lost', 1, '2026-07-08 02:57:12'),
(4, 20, '3212471044', '2750407878', 'lost', 1, '2026-08-08 06:57:38'),
(5, 16, '3211421460', '3213920020', 'damaged', 1, '2026-08-15 03:14:05'),
(6, 21, '3211421460', '2739603046', 'lost', 1, '2026-08-16 06:08:40');

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
) ENGINE=MyISAM AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(18, 'RC-20260718121855250', 10, 6, 7, 10.00, 20.00, 2.00, 8.00, 928.00, 920.00, 0, '2026-07-18 12:18:55'),
(19, 'RC-20260720061913439', 10, 15, 4, 45.00, 20.00, 9.00, 36.00, 920.00, 884.00, 3, '2026-07-20 06:19:13'),
(20, 'RC-20260720115619927', 18, 15, 3, 65.00, 0.00, 0.00, 65.00, 500.00, 435.00, 6, '2026-07-20 11:56:19'),
(21, 'RC-20260803022411200', 10, 14, 5, 55.00, 0.00, 0.00, 55.00, 884.00, 829.00, 5, '2026-08-03 02:24:11'),
(22, 'RC-20260803023111417', 18, 14, 4, 45.00, 20.00, 9.00, 36.00, 435.00, 399.00, 3, '2026-08-03 02:31:11'),
(23, 'RC-20260803045718466', 18, 14, 7, 10.00, 0.00, 0.00, 10.00, 399.00, 389.00, 1, '2026-08-03 04:57:18'),
(24, 'RC-20260803045721669', 10, 14, 7, 10.00, 0.00, 0.00, 10.00, 829.00, 819.00, 1, '2026-08-03 04:57:21'),
(25, 'RC-20260803045728940', 18, 14, 7, 10.00, 0.00, 0.00, 10.00, 389.00, 379.00, 1, '2026-08-03 04:57:28'),
(26, 'RC-20260803045732372', 10, 14, 7, 10.00, 0.00, 0.00, 10.00, 819.00, 809.00, 1, '2026-08-03 04:57:32'),
(27, 'RC-20260803061629660', 10, 14, 4, 45.00, 0.00, 0.00, 45.00, 809.00, 764.00, 0, '2026-08-03 06:16:29'),
(28, 'RC-20260808115117454', 10, 14, 4, 45.00, 0.00, 0.00, 45.00, 764.00, 719.00, 4, '2026-08-08 11:51:17'),
(29, 'RC-20260808115139450', 10, 14, 7, 10.00, 0.00, 0.00, 10.00, 719.00, 709.00, 1, '2026-08-08 11:51:39'),
(30, 'RC-20260808115149411', 10, 14, 4, 45.00, 0.00, 0.00, 45.00, 709.00, 664.00, 4, '2026-08-08 11:51:49'),
(31, 'RC-20260808115351198', 10, 14, 7, 10.00, 10.00, 1.00, 9.00, 664.00, 655.00, 0, '2026-08-08 11:53:51'),
(32, 'RC-20260808115433535', 10, 14, 7, 10.00, 10.00, 1.00, 9.00, 655.00, 646.00, 0, '2026-08-08 11:54:33'),
(33, 'RC-20260808115439775', 10, 14, 4, 45.00, 10.00, 4.50, 40.50, 646.00, 605.50, 4, '2026-08-08 11:54:39'),
(34, 'RC-20260809024644151', 10, 14, 4, 45.00, 10.00, 4.50, 40.50, 605.50, 565.00, 4, '2026-08-09 02:46:44'),
(35, 'RC-20260810050635583', 10, 15, 4, 45.00, 0.00, 0.00, 45.00, 565.00, 520.00, 4, '2026-08-10 05:06:35'),
(36, 'RC-20260810051847602', 10, 15, 7, 10.00, 0.00, 0.00, 10.00, 520.00, 510.00, 1, '2026-08-10 05:18:47'),
(37, 'RC-20260810052114562', 10, 15, 1, 50.00, 0.00, 0.00, 50.00, 510.00, 460.00, 5, '2026-08-10 05:21:14'),
(38, 'RC-20260810052309581', 10, 15, 5, 55.00, 0.00, 0.00, 55.00, 460.00, 405.00, 5, '2026-08-10 05:23:09'),
(39, 'RC-20260810053457427', 10, 15, 5, 55.00, 0.00, 0.00, 55.00, 405.00, 350.00, 5, '2026-08-10 05:34:57'),
(40, 'RC-20260810053625409', 10, 15, 5, 55.00, 0.00, 0.00, 55.00, 350.00, 295.00, 5, '2026-08-10 05:36:25'),
(41, 'RC-20260810053649448', 10, 15, 5, 55.00, 0.00, 0.00, 55.00, 295.00, 240.00, 5, '2026-08-10 05:36:49'),
(42, 'RC-20260810053715283', 10, 15, 5, 55.00, 0.00, 0.00, 55.00, 240.00, 185.00, 5, '2026-08-10 05:37:15'),
(43, 'RC-20260810053733594', 10, 15, 5, 55.00, 0.00, 0.00, 55.00, 185.00, 130.00, 5, '2026-08-10 05:37:33'),
(44, 'RC-20260810053752408', 10, 15, 5, 55.00, 0.00, 0.00, 55.00, 130.00, 75.00, 5, '2026-08-10 05:37:52'),
(45, 'RC-20260816061356135', 18, 20, 1, 12.00, 0.00, 0.00, 12.00, 1572.00, 1560.00, 1, '2026-08-16 06:13:56'),
(46, 'RC-20260816063257472', 17, 14, 11, 10.00, 20.00, 2.00, 8.00, 23.00, 15.00, 0, '2026-08-16 06:32:57'),
(47, 'RC-20260816063301267', 17, 14, 11, 10.00, 10.00, 1.00, 9.00, 15.00, 6.00, 0, '2026-08-16 06:33:01'),
(48, 'RC-20260816063348558', 10, 14, 4, 12.00, 0.00, 0.00, 12.00, 175.00, 163.00, 1, '2026-08-16 06:33:48'),
(49, 'RC-20260820105648714', 18, 15, 1, 12.00, 0.00, 0.00, 12.00, 1560.00, 1548.00, 1, '2026-08-20 10:56:48');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
