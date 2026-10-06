-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 24, 2026 at 01:53 AM
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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `balance_loads`
--

INSERT INTO `balance_loads` (`id`, `passenger_id`, `amount`, `reference_no`, `loaded_by`, `load_date`) VALUES
(1, 1, 123.00, 'LOAD-20260823115415690', 1, '2026-08-23 11:54:15'),
(2, 1, 10.00, 'LOAD-20260823120635102', 1, '2026-08-23 12:06:35');

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `collectors`
--

INSERT INTO `collectors` (`id`, `employee_number`, `first_name`, `middle_name`, `last_name`, `password`, `full_name`, `contact`, `status`, `created_by`, `created_at`) VALUES
(1, 'VGL001', 'DAN CARTER', '', 'COLL', '$2y$10$atdGqO8CwjqpAzQiVULUK.pF70Vmh/FhJWfxEG7xuh9lGfq.9E.5q', '', '', 'active', 1, '2026-08-23 02:32:51'),
(2, 'VGL002', 'GRACE', '', 'COLL', '$2y$10$FNeysuqtwkDjB5H86IhCRue.ccO4HakxD31qrBA.WwqDBp85BWNpS', '', '', 'active', 1, '2026-08-23 11:58:15'),
(3, 'VGL003', 'GEFERSON', '', 'COLL', '$2y$10$mCOT18BK5N1rosWlD7m9tuytG7m0m7hJNBMPMrwfX57V4F/1.p7nK', '', '', 'active', 1, '2026-08-23 11:58:25'),
(4, 'VGL004', 'JOHN', '', 'COLL', '$2y$10$HqNJ4G6p3K9UwIpDAvFYY.J8vsetxgYPHxKmmBvZcToGuR4c0xGi2', '', '123', 'inactive', 1, '2026-08-23 11:58:40');

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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `origin`, `destination`, `base_fare`, `is_active`, `created_at`) VALUES
(1, '1', '2', 10.00, 0, '2026-08-23 11:59:18'),
(2, '2', '3', 11.00, 1, '2026-08-23 11:59:33');

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
(1, 'senior', 20.00, 1, '2026-08-22 12:50:02'),
(2, 'pwd', 20.00, 1, '2026-08-22 12:50:02'),
(3, 'student', 10.00, 1, '2026-08-22 12:50:02'),
(4, 'regular', 0.00, 1, '2026-08-22 12:50:02');

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
(1, 0.10, 10.00, NULL, '2026-08-22 12:50:02');

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
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`id`, `rfid_uid`, `first_name`, `middle_name`, `last_name`, `password`, `full_name`, `email`, `contact`, `balance`, `loyalty_points`, `discount_type_id`, `status`, `created_by`, `created_at`, `created_by_role`) VALUES
(1, '3484554904', 'DAN CARTER', '', 'YU', '$2y$10$4D1jwVcdLVkN5j1HfdGEku9GJJWrGjeqHtM7rQZzhM14hiFYnkheu', '', 'afirst562@gmai.com', 'sad', 112.10, 1, 4, 'active', 'admin', '2026-08-23 02:26:16', 'management'),
(2, '3211421460', 'GRACE', '', 'ROMERO', '$2y$10$RkLeRMN9..Is7pPtxyysoOredLUh0bClGmrv055TxMHwjv3G5h3ga', '', 'grace@gmail.com', '123', 0.00, 0, 4, 'active', 'admin', '2026-08-23 11:51:29', 'management'),
(3, '2743368246', 'JOHN', '', 'YTAC', '$2y$10$8jK8V5jWiBTGUccfrm9DiOYiNx/hN0MrtRk2sZF2MzWZvTKiWZazK', '', 'dancarter2.12.04@gmail.com', '123', 0.00, 0, 4, 'active', 'admin', '2026-08-23 11:52:32', 'management'),
(4, '3217705764', 'GEFERSON', '', 'SAMSON', '$2y$10$sQjZGARCeKhpZqvenLPJJO30nY54Snlob3zrVVDvVoq0sBEaRBu2y', '', 'gef@gmail.com', '0666506', 0.00, 0, 4, 'lost', 'admin', '2026-08-23 11:53:10', 'management'),
(5, '3212471044', 'COLL', '', 'TEST', '$2y$10$.s8wfEKNMlCbjgoSDdCkIeJPfhzyBWojNPvqRQdh.5p8AIsKPSrNq', '', 'yu.dan.carter.15@gmail.com', '123', 0.00, 0, 4, 'active', 'VGL001', '2026-08-23 12:02:03', 'collector');

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
(1, 1, '2739603046', '2750407878', 'damaged', 1, '2026-08-23 11:54:49'),
(2, 1, '2750407878', '3484554904', 'lost', 1, '2026-08-23 12:04:10'),
(3, 1, '3484554904', '3484554904', 'lost', 1, '2026-08-23 12:22:40');

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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `receipt_no`, `passenger_id`, `collector_id`, `destination_id`, `base_fare`, `discount_percentage`, `discount_amount`, `net_payment`, `balance_before`, `balance_after`, `loyalty_points_earned`, `transaction_date`) VALUES
(1, 'RC-20260823120119151', 1, 1, 2, 11.00, 0.00, 0.00, 11.00, 123.00, 112.00, 1, '2026-08-23 12:01:19'),
(2, 'RC-20260823122227473', 1, 2, 2, 11.00, 10.00, 1.10, 9.90, 122.00, 112.10, 0, '2026-08-23 12:22:27');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
