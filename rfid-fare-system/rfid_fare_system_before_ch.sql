-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 04, 2026 at 01:50 AM
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
  `loaded_by_role` enum('management','collector') COLLATE utf8mb4_unicode_ci DEFAULT 'collector',
  PRIMARY KEY (`id`),
  KEY `passenger_id` (`passenger_id`),
  KEY `loaded_by` (`loaded_by`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `balance_loads`
--

INSERT INTO `balance_loads` (`id`, `passenger_id`, `amount`, `reference_no`, `loaded_by`, `load_date`, `loaded_by_role`) VALUES
(1, 1, 1000.00, 'LOAD-20260901120719525', 1, '2026-09-01 12:07:19', 'collector'),
(2, 2, 500.00, 'LOAD-20260901120811733', 1, '2026-09-01 12:08:11', 'collector'),
(3, 3, 1000.00, 'LOAD-20260901130128218', 2, '2026-09-01 13:01:28', 'collector'),
(4, 1, 4955.00, 'LOAD-20260901133037684', 2, '2026-09-01 13:30:37', 'collector'),
(5, 2, 500.00, 'LOAD-20260902023757254', 3, '2026-09-02 02:37:57', 'collector'),
(6, 4, 500.00, 'LOAD-20260902023855972', 3, '2026-09-02 02:38:55', 'collector'),
(7, 5, 500.00, 'LOAD-20260902023901257', 3, '2026-09-02 02:39:01', 'collector'),
(8, 5, 450.00, 'LOAD-20260902031354355', 1, '2026-09-02 03:13:54', 'management'),
(9, 4, 450.00, 'LOAD-20260902031402217', 1, '2026-09-02 03:14:02', 'management'),
(10, 3, 370.00, 'LOAD-20260902031417359', 1, '2026-09-02 03:14:17', 'management'),
(11, 2, 400.00, 'LOAD-20260902031425758', 1, '2026-09-02 03:14:25', 'management'),
(12, 1, 100.00, 'LOAD-20260902031433756', 1, '2026-09-02 03:14:33', 'management'),
(13, 2, 500.00, 'LOAD-20260902031605818', 3, '2026-09-02 03:16:05', 'collector'),
(14, 2, 4900.00, 'LOAD-20260902032640137', 3, '2026-09-02 03:26:40', 'collector'),
(15, 3, 4950.00, 'LOAD-20260902032650492', 3, '2026-09-02 03:26:50', 'collector'),
(16, 1, 3950.00, 'LOAD-20260902032706948', 3, '2026-09-02 03:27:06', 'collector');

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
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `collectors`
--

INSERT INTO `collectors` (`id`, `employee_number`, `first_name`, `middle_name`, `last_name`, `password`, `full_name`, `contact`, `status`, `created_by`, `created_at`) VALUES
(1, 'VGL001', 'GEFERSON', '', 'SAMSON', '$2y$10$t6mBubFZQbXUTMAI4ClEA.vLRtI6zt18F5XTy2QZTTsCLC3uegW16', '', '', 'active', 1, '2026-09-01 12:05:27'),
(2, 'VGL002', 'GRACE', '', 'TEST', '$2y$10$i9Ww.qbt11B9RBxu2sE2F.aBk56ugOJ2.911L7843gSjrkEaJHkc2', '', '', 'active', 1, '2026-09-01 12:59:51'),
(3, 'VGL003', 'TRY', '', 'TEST', '$2y$10$59E246gGsqksyVKMFi/7OOSyy1k9UJUBtaDcNLspfkhxAmQ3l8M8W', '', '', 'active', 1, '2026-09-02 02:28:40');

-- --------------------------------------------------------

--
-- Table structure for table `collector_assignments`
--

DROP TABLE IF EXISTS `collector_assignments`;
CREATE TABLE IF NOT EXISTS `collector_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `collector_id` int NOT NULL,
  `destination_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collector_id` (`collector_id`,`destination_id`),
  KEY `destination_id` (`destination_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `collector_assignments`
--

INSERT INTO `collector_assignments` (`id`, `collector_id`, `destination_id`, `assigned_at`) VALUES
(1, 1, 1, '2026-09-01 12:06:57'),
(2, 2, 2, '2026-09-01 13:00:02'),
(3, 3, 3, '2026-09-02 02:28:59');

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

DROP TABLE IF EXISTS `destinations`;
CREATE TABLE IF NOT EXISTS `destinations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `origin` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vessel_id` int DEFAULT NULL,
  `aircon_fare` decimal(10,2) DEFAULT '0.00',
  `non_aircon_fare` decimal(10,2) DEFAULT '0.00',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_route` (`origin`,`destination`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `origin`, `destination`, `vessel_id`, `aircon_fare`, `non_aircon_fare`, `is_active`, `created_at`) VALUES
(1, 'SURIGAO CITY', 'SAN JOSE', 1, 480.00, 450.00, 1, '2026-09-01 12:06:52'),
(2, 'TEST ORIGIN', 'TEST DEST', 2, 0.00, 450.00, 1, '2026-09-01 12:59:33'),
(3, 'SAN JOSE', 'SURIGAO CITY', 3, 450.00, 420.00, 1, '2026-09-02 02:28:25');

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
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `discount_types`
--

INSERT INTO `discount_types` (`id`, `name`, `percentage`, `is_active`, `created_at`) VALUES
(11, 'infant', 100.00, 1, '2026-09-01 12:27:13'),
(12, 'child', 50.00, 1, '2026-09-01 12:27:13'),
(13, 'student', 20.00, 1, '2026-09-01 12:27:13'),
(14, 'senior', 20.00, 1, '2026-09-01 12:27:13'),
(15, 'pwd', 20.00, 1, '2026-09-01 12:27:13'),
(16, 'regular', 0.00, 1, '2026-09-01 12:27:13');

-- --------------------------------------------------------

--
-- Table structure for table `group_passengers`
--

DROP TABLE IF EXISTS `group_passengers`;
CREATE TABLE IF NOT EXISTS `group_passengers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_id` int NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` enum('M','F') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `passenger_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'regular',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_settings`
--

DROP TABLE IF EXISTS `loyalty_settings`;
CREATE TABLE IF NOT EXISTS `loyalty_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `points_per_peso` decimal(5,2) DEFAULT '0.10',
  `points_per_travel` int DEFAULT '1',
  `min_fare_for_points` decimal(10,2) DEFAULT '10.00',
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_enabled` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `updated_by` (`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loyalty_settings`
--

INSERT INTO `loyalty_settings` (`id`, `points_per_peso`, `points_per_travel`, `min_fare_for_points`, `updated_by`, `updated_at`, `is_enabled`) VALUES
(1, 0.10, 1, 10.00, 1, '2026-09-01 12:32:53', 1);

-- --------------------------------------------------------

--
-- Table structure for table `manifests`
--

DROP TABLE IF EXISTS `manifests`;
CREATE TABLE IF NOT EXISTS `manifests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trip_id` int NOT NULL,
  `passenger_id` int NOT NULL,
  `passenger_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `age` int DEFAULT NULL,
  `sex` enum('M','F') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `passenger_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'regular',
  `section` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'aircon',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  KEY `passenger_id` (`passenger_id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `manifests`
--

INSERT INTO `manifests` (`id`, `trip_id`, `passenger_id`, `passenger_name`, `age`, `sex`, `address`, `passenger_type`, `section`, `created_at`) VALUES
(1, 1, 2, 'JOHN YTAC', 16, 'M', 'SC', 'regular', 'non_aircon', '2026-09-01 12:15:42'),
(2, 1, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'aircon', '2026-09-01 12:16:21'),
(3, 1, 0, 'ONE', 22, 'M', 'SC', 'Regular (0.00% off)', 'aircon', '2026-09-01 12:16:21'),
(4, 2, 3, 'TEST COLL', 0, 'M', 'SC', 'regular', 'non_aircon', '2026-09-01 13:03:11'),
(5, 2, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'non_aircon', '2026-09-01 13:46:01'),
(6, 5, 3, 'TEST COLL', 0, 'M', 'SC', 'regular', 'non_aircon', '2026-09-02 02:31:14'),
(7, 5, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'aircon', '2026-09-02 02:33:27'),
(8, 5, 2, 'JOHN YTAC', 16, 'M', 'SC', 'regular', 'aircon', '2026-09-02 02:38:02'),
(9, 5, 4, 'A C', 8, 'M', 'sc', 'regular', 'aircon', '2026-09-02 02:39:07'),
(10, 5, 5, 'GEFERSON S', 16, 'M', 'sc', 'regular', 'aircon', '2026-09-02 02:39:14'),
(11, 6, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'aircon', '2026-09-02 03:14:52'),
(12, 6, 3, 'TEST COLL', 0, 'M', 'SC', 'regular', 'aircon', '2026-09-02 03:15:16'),
(13, 6, 2, 'JOHN YTAC', 16, 'M', 'SC', 'regular', 'aircon', '2026-09-02 03:16:19'),
(14, 7, 2, 'JOHN YTAC', 16, 'M', 'SC', 'regular', 'aircon', '2026-09-02 03:27:14'),
(15, 7, 3, 'TEST COLL', 0, 'M', 'SC', 'regular', 'aircon', '2026-09-02 03:27:29'),
(16, 7, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'non_aircon', '2026-09-02 03:30:17'),
(17, 7, 0, 'ONE', 21, 'M', 'SC', 'Regular (0.00% off)', 'non_aircon', '2026-09-02 03:30:17');

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
  `date_of_birth` date DEFAULT NULL,
  `sex` enum('M','F') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
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
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`id`, `rfid_uid`, `first_name`, `middle_name`, `last_name`, `password`, `full_name`, `email`, `contact`, `date_of_birth`, `sex`, `address`, `balance`, `loyalty_points`, `discount_type_id`, `status`, `created_by`, `created_at`, `created_by_role`) VALUES
(1, '3215720212', 'DAN CARTER', '', 'YU', '$2y$10$nz7CQcjBS3oic//TJDPPNuRR1HOzd5ZtfqZ4wXBnE1DZkVUnaemyC', '', '', '', '2004-02-12', 'M', 'SC', 4160.00, 14, 4, 'active', 'admin', '2026-09-01 12:05:18', 'management'),
(2, '2750631654', 'JOHN', '', 'YTAC', '$2y$10$PeKwYm0degdRcLTMvhPVNO3MbiKl7iRe6fNeNXgcKeBlcbKbnmrhi', '', '', '', '2010-06-01', 'M', 'SC', 4550.00, 6, 4, 'active', 'VGL001', '2026-09-01 12:07:43', 'collector'),
(3, '3212471044', 'TEST', '', 'COLL', '$2y$10$5Pap1k30ErtM7ogk5ZEh1ez7H5/KXtKFOX1F9Q4DuxwKtFgeUg3JO', '', '', '', '2026-09-01', 'M', 'SC', 4550.00, 4, 4, 'active', 'admin', '2026-09-01 13:00:31', 'management'),
(4, '2750407878', 'A', '', 'C', '$2y$10$QDZrpHwit/09ukGB1N2gH.eVFxkefnFF82E85ndjqopC35OlQXeNm', '', '', '', '2018-05-02', 'M', 'sc', 500.00, 1, 4, 'active', 'VGL003', '2026-09-02 02:38:29', 'collector'),
(5, '3217705764', 'GEFERSON', '', 'S', '$2y$10$tUSyFWHQ2IaOb/JUGFCMAeUokGUJ4fWlLtcWswWlDHlB0p0kyebxW', '', '', '', '2010-02-02', 'M', 'sc', 500.00, 1, 4, 'active', 'VGL003', '2026-09-02 02:38:49', 'collector');

-- --------------------------------------------------------

--
-- Table structure for table `redemptions`
--

DROP TABLE IF EXISTS `redemptions`;
CREATE TABLE IF NOT EXISTS `redemptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `passenger_id` int NOT NULL,
  `balance_amount` decimal(10,2) NOT NULL,
  `points_used` int NOT NULL,
  `status` enum('completed','pending','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `passenger_id` (`passenger_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `redemptions`
--

INSERT INTO `redemptions` (`id`, `passenger_id`, `balance_amount`, `points_used`, `status`, `created_at`) VALUES
(1, 1, 5.00, 5, 'completed', '2026-09-01 12:52:06');

-- --------------------------------------------------------

--
-- Table structure for table `redemption_settings`
--

DROP TABLE IF EXISTS `redemption_settings`;
CREATE TABLE IF NOT EXISTS `redemption_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `balance_amount` decimal(10,2) NOT NULL,
  `points_required` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `redemption_settings`
--

INSERT INTO `redemption_settings` (`id`, `balance_amount`, `points_required`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 20.00, 25, 1, '2026-09-01 12:44:42', '2026-09-01 12:44:42'),
(2, 50.00, 60, 1, '2026-09-01 12:44:42', '2026-09-01 12:44:42'),
(3, 100.00, 120, 1, '2026-09-01 12:44:42', '2026-09-01 12:44:42'),
(4, 200.00, 240, 1, '2026-09-01 12:44:42', '2026-09-01 12:44:42'),
(5, 5.00, 5, 1, '2026-09-01 12:51:43', '2026-09-01 12:51:43');

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `trip_id` int DEFAULT NULL,
  `base_fare` decimal(10,2) NOT NULL,
  `discount_percentage` decimal(5,2) DEFAULT '0.00',
  `discount_amount` decimal(10,2) DEFAULT '0.00',
  `net_payment` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `loyalty_points_earned` int DEFAULT '0',
  `is_group` enum('0','1') COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `passenger_count` int DEFAULT '1',
  `card_holder_boarding` enum('0','1') COLLATE utf8mb4_unicode_ci DEFAULT '1',
  `transaction_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `passenger_id` (`passenger_id`),
  KEY `collector_id` (`collector_id`),
  KEY `destination_id` (`destination_id`),
  KEY `trip_id` (`trip_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `receipt_no`, `passenger_id`, `collector_id`, `destination_id`, `trip_id`, `base_fare`, `discount_percentage`, `discount_amount`, `net_payment`, `balance_before`, `balance_after`, `loyalty_points_earned`, `is_group`, `passenger_count`, `card_holder_boarding`, `transaction_date`) VALUES
(1, 'RC-20260901121542342', 2, 1, 1, 1, 450.00, 0.00, 0.00, 450.00, 500.00, 50.00, 2, '0', 1, '1', '2026-09-01 12:15:42'),
(2, 'GRP-20260901121621126', 1, 1, 1, 1, 0.00, 0.00, 0.00, 960.00, 1000.00, 40.00, 8, '1', 2, '1', '2026-09-01 12:16:21'),
(3, 'RC-20260901130311916', 3, 2, 2, 2, 450.00, 0.00, 0.00, 450.00, 1000.00, 550.00, 1, '0', 1, '1', '2026-09-01 13:03:11'),
(4, 'GRP-20260901134601141', 1, 2, 2, 2, 0.00, 0.00, 0.00, 900.00, 5000.00, 4100.00, 2, '1', 2, '1', '2026-09-01 13:46:01'),
(5, 'RC-20260902023114770', 3, 3, 3, 5, 420.00, 0.00, 0.00, 420.00, 550.00, 130.00, 1, '0', 1, '1', '2026-09-02 02:31:14'),
(6, 'GRP-20260902023327870', 1, 3, 3, 5, 0.00, 0.00, 0.00, 2700.00, 4100.00, 1400.00, 6, '1', 6, '1', '2026-09-02 02:33:27'),
(7, 'RC-20260902023802299', 2, 3, 3, 5, 450.00, 0.00, 0.00, 450.00, 550.00, 100.00, 1, '0', 1, '1', '2026-09-02 02:38:02'),
(8, 'RC-20260902023907529', 4, 3, 3, 5, 450.00, 0.00, 0.00, 450.00, 500.00, 50.00, 1, '0', 1, '1', '2026-09-02 02:39:07'),
(9, 'RC-20260902023914256', 5, 3, 3, 5, 450.00, 0.00, 0.00, 450.00, 500.00, 50.00, 1, '0', 1, '1', '2026-09-02 02:39:14'),
(10, 'RC-20260902031452903', 1, 3, 3, 6, 450.00, 0.00, 0.00, 450.00, 1500.00, 1050.00, 1, '0', 1, '1', '2026-09-02 03:14:52'),
(11, 'RC-20260902031516412', 3, 3, 3, 6, 450.00, 0.00, 0.00, 450.00, 500.00, 50.00, 1, '0', 1, '1', '2026-09-02 03:15:16'),
(12, 'GRP-20260902031619569', 2, 3, 3, 6, 0.00, 0.00, 0.00, 900.00, 1000.00, 100.00, 2, '1', 2, '1', '2026-09-02 03:16:19'),
(13, 'RC-20260902032714493', 2, 3, 3, 7, 450.00, 0.00, 0.00, 450.00, 5000.00, 4550.00, 1, '0', 1, '1', '2026-09-02 03:27:14'),
(14, 'RC-20260902032729343', 3, 3, 3, 7, 450.00, 0.00, 0.00, 450.00, 5000.00, 4550.00, 1, '0', 1, '1', '2026-09-02 03:27:29'),
(15, 'GRP-20260902033017906', 1, 3, 3, 7, 0.00, 0.00, 0.00, 840.00, 5000.00, 4160.00, 2, '1', 2, '1', '2026-09-02 03:30:17');

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

DROP TABLE IF EXISTS `trips`;
CREATE TABLE IF NOT EXISTS `trips` (
  `id` int NOT NULL AUTO_INCREMENT,
  `schedule_id` int NOT NULL,
  `trip_date` date NOT NULL,
  `status` enum('upcoming','accepting','sailed','arrived','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'upcoming',
  `aircon_capacity` int DEFAULT '0',
  `non_aircon_capacity` int DEFAULT '0',
  `aircon_count` int DEFAULT '0',
  `non_aircon_count` int DEFAULT '0',
  `actual_departure` datetime DEFAULT NULL,
  `actual_arrival` datetime DEFAULT NULL,
  `passenger_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `schedule_id` (`schedule_id`,`trip_date`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trips`
--

INSERT INTO `trips` (`id`, `schedule_id`, `trip_date`, `status`, `aircon_capacity`, `non_aircon_capacity`, `aircon_count`, `non_aircon_count`, `actual_departure`, `actual_arrival`, `passenger_count`, `created_at`) VALUES
(1, 1, '2026-09-01', 'arrived', 20, 20, 0, 0, '2026-09-01 20:35:21', '2026-09-01 20:49:56', 3, '2026-09-01 12:07:07'),
(2, 2, '2026-09-01', 'accepting', 0, 20, 0, 0, NULL, NULL, 3, '2026-09-01 13:00:59'),
(3, 1, '2026-09-02', 'upcoming', 20, 20, 0, 0, NULL, NULL, 0, '2026-09-02 02:29:48'),
(4, 2, '2026-09-02', 'upcoming', 0, 20, 0, 0, NULL, NULL, 0, '2026-09-02 02:29:48'),
(5, 3, '2026-09-02', 'arrived', 2, 2, 0, 0, '2026-09-02 11:09:53', '2026-09-02 11:09:56', 10, '2026-09-02 02:29:48'),
(6, 4, '2026-09-02', 'arrived', 2, 2, 2, 0, '2026-09-02 11:26:05', '2026-09-02 11:26:09', 4, '2026-09-02 02:29:48'),
(7, 5, '2026-09-02', 'arrived', 2, 2, 2, 2, '2026-09-02 11:41:18', '2026-09-02 11:41:20', 4, '2026-09-02 03:26:05');

-- --------------------------------------------------------

--
-- Table structure for table `trip_schedules`
--

DROP TABLE IF EXISTS `trip_schedules`;
CREATE TABLE IF NOT EXISTS `trip_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `destination_id` int NOT NULL,
  `departure_time` time NOT NULL,
  `arrival_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `destination_id` (`destination_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trip_schedules`
--

INSERT INTO `trip_schedules` (`id`, `destination_id`, `departure_time`, `arrival_time`, `is_active`, `created_at`) VALUES
(1, 1, '05:00:00', '07:00:00', 1, '2026-09-01 12:06:52'),
(2, 2, '16:00:00', '18:00:00', 1, '2026-09-01 12:59:33'),
(3, 3, '06:00:00', '08:00:00', 1, '2026-09-02 02:28:25'),
(4, 3, '09:00:00', '11:00:00', 1, '2026-09-02 02:28:25'),
(5, 3, '13:00:00', '14:00:00', 1, '2026-09-02 03:26:02');

-- --------------------------------------------------------

--
-- Table structure for table `vessels`
--

DROP TABLE IF EXISTS `vessels`;
CREATE TABLE IF NOT EXISTS `vessels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('aircon','non_aircon','both') COLLATE utf8mb4_unicode_ci DEFAULT 'both',
  `aircon_capacity` int DEFAULT '0',
  `non_aircon_capacity` int DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vessels`
--

INSERT INTO `vessels` (`id`, `name`, `type`, `aircon_capacity`, `non_aircon_capacity`, `status`, `created_at`) VALUES
(1, 'VINCE GABRIEL 2', 'both', 20, 20, 'active', '2026-09-01 12:06:05'),
(2, 'TEST VESSEL', 'non_aircon', 0, 20, 'active', '2026-09-01 12:58:09'),
(3, 'VINCE GABRIEL 3', 'both', 2, 2, 'active', '2026-09-02 02:26:35');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
