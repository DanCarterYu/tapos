-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 30, 2026 at 12:21 PM
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
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(16, 1, 3950.00, 'LOAD-20260902032706948', 3, '2026-09-02 03:27:06', 'collector'),
(17, 5, 1000.00, 'LOAD-20260926053100182', 2, '2026-09-26 05:31:00', 'collector'),
(18, 6, 1000.00, 'LOAD-20260926114706477', 1, '2026-09-26 11:47:06', 'management');

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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `balance_requests`
--

INSERT INTO `balance_requests` (`id`, `passenger_id`, `amount`, `payment_method`, `reference_no`, `sender_name`, `date_paid`, `receipt_image`, `status`, `rejection_reason`, `request_date`, `approved_by`, `approved_date`) VALUES
(1, 1, 250.00, 'GCash', '5899944566', 'KYLLE', '2026-09-06', 'assets/uploads/requests/receipt_20260906_015033.jpeg', 'rejected', 'waya diskasi', '2026-09-06 01:50:33', 1, '2026-09-06 01:51:06');

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
(1, 'VGL001', 'GEFERSON', '', 'SAMSON', '$2y$10$t6mBubFZQbXUTMAI4ClEA.vLRtI6zt18F5XTy2QZTTsCLC3uegW16', '', '', 'active', 1, '2026-09-01 12:05:27'),
(2, 'VGL002', 'GRACE', '', 'TEST', '$2y$10$i9Ww.qbt11B9RBxu2sE2F.aBk56ugOJ2.911L7843gSjrkEaJHkc2', '', '', 'active', 1, '2026-09-01 12:59:51'),
(3, 'VGL003', 'TRY', '', 'TEST', '$2y$10$59E246gGsqksyVKMFi/7OOSyy1k9UJUBtaDcNLspfkhxAmQ3l8M8W', '', '', 'active', 1, '2026-09-02 02:28:40'),
(4, 'VGL004', 'ONE', '', 'TWO', '$2y$10$R4n4A17S5oqxMoZcH34cbegWcPzoAZOjn9dHcv9E1257hMwHpdWky', '', '', 'active', 1, '2026-09-06 01:32:08');

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
  UNIQUE KEY `unique_destination` (`destination_id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `collector_assignments`
--

INSERT INTO `collector_assignments` (`id`, `collector_id`, `destination_id`, `assigned_at`) VALUES
(13, 1, 7, '2026-09-26 12:35:46'),
(14, 2, 5, '2026-09-26 12:36:19'),
(15, 1, 12, '2026-09-29 05:17:27'),
(16, 2, 11, '2026-09-29 05:17:36');

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
  UNIQUE KEY `unique_route` (`origin`,`destination`,`vessel_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `origin`, `destination`, `vessel_id`, `aircon_fare`, `non_aircon_fare`, `is_active`, `created_at`) VALUES
(1, 'SURIGAO CITY', 'SAN JOSE', 1, 480.00, 450.00, 1, '2026-09-01 12:06:52'),
(2, 'TEST ORIGIN', 'TEST DEST', 2, 0.00, 450.00, 1, '2026-09-01 12:59:33'),
(3, 'SAN JOSE', 'SURIGAO CITY', 3, 450.00, 420.00, 1, '2026-09-02 02:28:25'),
(4, 'SURIGAO CITY', 'DAPA', 4, 480.00, 450.00, 1, '2026-09-06 01:30:43'),
(5, 'DAPA', 'SURIGAO CITY', 4, 480.00, 450.00, 1, '2026-09-06 01:31:41'),
(7, 'ASDASD', 'DASDA', 4, 4.00, 5.00, 1, '2026-09-06 03:58:34'),
(12, 'SC', 'DI', 6, 0.00, 23.00, 1, '2026-09-29 05:17:08'),
(11, 'SC', 'DI', 5, 0.00, 23.00, 1, '2026-09-29 05:15:11');

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
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(17, 7, 0, 'ONE', 21, 'M', 'SC', 'Regular (0.00% off)', 'non_aircon', '2026-09-02 03:30:17'),
(18, 10, 3, 'TEST COLL', 0, 'M', 'SC', 'regular', 'aircon', '2026-09-04 02:08:46'),
(19, 10, 2, 'JOHN YTAC', 16, 'M', 'SC', 'regular', 'non_aircon', '2026-09-04 02:10:05'),
(20, 10, 0, 'ONE', 21, 'F', 'SC', 'Regular (0.00% off)', 'non_aircon', '2026-09-04 02:10:05'),
(21, 10, 4, 'A C', 8, 'M', 'sc', 'infant', 'aircon', '2026-09-04 02:12:10'),
(22, 11, 5, 'GEFERSON S', 16, 'M', 'sc', 'regular', 'aircon', '2026-09-04 02:13:23'),
(23, 12, 5, 'GEFERSON S', 16, 'M', 'sc', 'regular', 'aircon', '2026-09-04 02:14:20'),
(24, 13, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'aircon', '2026-09-06 01:22:59'),
(25, 13, 0, 'ONE', 21, 'M', 'SC', 'Regular (0.00% off)', 'aircon', '2026-09-06 01:22:59'),
(26, 13, 0, 'TWO', 21, 'M', 'SC', 'Regular (0.00% off)', 'aircon', '2026-09-06 01:22:59'),
(27, 13, 0, 'THREE', 60, 'F', 'ZC', 'Senior (20.00% off)', 'aircon', '2026-09-06 01:22:59'),
(28, 13, 5, 'GEFERSON S', 16, 'M', 'sc', 'regular', 'aircon', '2026-09-06 01:24:17'),
(29, 18, 3, 'TEST COLL', 0, 'M', 'SC', 'regular', 'aircon', '2026-09-06 01:35:07'),
(30, 18, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'aircon', '2026-09-06 01:36:32'),
(31, 18, 0, 'ONE', 21, 'M', 'SC', 'Regular (0.00% off)', 'aircon', '2026-09-06 01:36:32'),
(32, 37, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'student', 'aircon', '2026-09-13 12:19:56'),
(33, 44, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'non_aircon', '2026-09-16 02:00:39'),
(34, 45, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'aircon', '2026-09-16 02:05:56'),
(35, 55, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'aircon', '2026-09-19 06:18:29'),
(36, 74, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'non_aircon', '2026-09-26 05:28:47'),
(37, 74, 5, 'GEFERSON S', 16, 'M', 'sc', 'regular', 'non_aircon', '2026-09-26 05:31:18'),
(38, 74, 0, 'ONE', 21, 'M', 'SC', 'Regular (0.00% off)', 'non_aircon', '2026-09-26 05:31:18'),
(39, 96, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'non_aircon', '2026-09-29 13:09:40'),
(40, 95, 1, 'DAN CARTER YU', 22, 'M', 'SC', 'regular', 'aircon', '2026-09-29 13:10:09'),
(41, 95, 5, 'GEFERSON S', 16, 'M', 'sc', 'regular', 'aircon', '2026-09-29 13:10:51'),
(42, 95, 0, 'ONE', 21, 'M', 'SC', 'Regular (0.00% off)', 'aircon', '2026-09-29 13:10:51'),
(43, 95, 4, 'A C', 8, 'M', 'sc', 'regular', 'aircon', '2026-09-29 13:12:02'),
(44, 95, 0, 'SC', 21, 'M', 'SC', 'Regular (0.00% off)', 'aircon', '2026-09-29 13:12:02');

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
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`id`, `rfid_uid`, `first_name`, `middle_name`, `last_name`, `password`, `full_name`, `email`, `contact`, `date_of_birth`, `sex`, `address`, `balance`, `loyalty_points`, `discount_type_id`, `status`, `created_by`, `created_at`, `created_by_role`) VALUES
(1, '3215720212', 'DAN CARTER', '', 'YU', '$2y$10$nz7CQcjBS3oic//TJDPPNuRR1HOzd5ZtfqZ4wXBnE1DZkVUnaemyC', '', 'afirst562@gmai.com', '', '2004-02-12', 'M', 'SC', 909.00, 14, 4, 'active', 'admin', '2026-09-01 12:05:18', 'management'),
(2, '2750631654', 'JOHN', '', 'YTAC', '$2y$10$PeKwYm0degdRcLTMvhPVNO3MbiKl7iRe6fNeNXgcKeBlcbKbnmrhi', '', '', '', '2010-06-01', 'M', 'SC', 3710.00, 8, 4, 'active', 'VGL001', '2026-09-01 12:07:43', 'collector'),
(3, '3212471044', 'TEST', '', 'COLL', '$2y$10$5Pap1k30ErtM7ogk5ZEh1ez7H5/KXtKFOX1F9Q4DuxwKtFgeUg3JO', '', '', '', '2026-09-01', 'M', 'SC', 3620.00, 6, 4, 'active', 'admin', '2026-09-01 13:00:31', 'management'),
(4, '2750407878', 'A', '', 'C', '$2y$10$QDZrpHwit/09ukGB1N2gH.eVFxkefnFF82E85ndjqopC35OlQXeNm', '', '', '', '2018-05-02', 'M', 'sc', 492.00, 4, 4, 'active', 'VGL003', '2026-09-02 02:38:29', 'collector'),
(5, '3217705764', 'GEFERSON', '', 'S', '$2y$10$tUSyFWHQ2IaOb/JUGFCMAeUokGUJ4fWlLtcWswWlDHlB0p0kyebxW', '', '', '', '2010-02-02', 'M', 'sc', 112.00, 8, 4, 'active', 'VGL003', '2026-09-02 02:38:49', 'collector'),
(6, '2739603046', 'TESSASD', '', 'TESSS', '$2y$10$VV08mPShge/L1GGCtzlbR.5NL07nSat5BXJRCUjYWc08o7.ImgQD6', '', '', '', '2008-06-10', 'F', 'SC', 1000.00, 0, 4, 'active', 'admin', '2026-09-26 11:46:30', 'management');

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
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `redemptions`
--

INSERT INTO `redemptions` (`id`, `passenger_id`, `balance_amount`, `points_used`, `status`, `created_at`) VALUES
(1, 1, 5.00, 5, 'completed', '2026-09-01 12:52:06'),
(2, 1, 5.00, 5, 'completed', '2026-09-06 01:17:13'),
(3, 1, 5.00, 5, 'completed', '2026-09-16 02:30:47');

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
(4, 200.00, 240, 1, '2026-09-01 12:44:42', '2026-09-01 12:44:42');

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

DROP TABLE IF EXISTS `refunds`;
CREATE TABLE IF NOT EXISTS `refunds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_id` int NOT NULL,
  `passenger_id` int NOT NULL,
  `trip_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `points_deducted` int NOT NULL DEFAULT '0',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `refunded_by` int DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `passenger_id` (`passenger_id`),
  KEY `trip_id` (`trip_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `refunds`
--

INSERT INTO `refunds` (`id`, `transaction_id`, `passenger_id`, `trip_id`, `amount`, `points_deducted`, `reason`, `refunded_by`, `refunded_at`) VALUES
(1, 26, 1, 44, 450.00, 1, 'Trip cancelled', 2, '2026-09-16 02:01:48'),
(2, 27, 1, 45, 450.00, 1, 'Trip cancelled', 3, '2026-09-16 02:06:06'),
(3, 28, 1, 55, 450.00, 1, 'Trip cancelled', 3, '2026-09-19 06:19:03');

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
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rfid_replacements`
--

INSERT INTO `rfid_replacements` (`id`, `passenger_id`, `old_rfid_uid`, `new_rfid_uid`, `reason`, `replaced_by`, `replacement_date`) VALUES
(1, 6, '2743368246', '2739603046', 'lost', 1, '2026-09-26 11:47:16');

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
  `is_refunded` tinyint(1) DEFAULT '0',
  `refunded_at` timestamp NULL DEFAULT NULL,
  `points_deducted` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `passenger_id` (`passenger_id`),
  KEY `collector_id` (`collector_id`),
  KEY `destination_id` (`destination_id`),
  KEY `trip_id` (`trip_id`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `receipt_no`, `passenger_id`, `collector_id`, `destination_id`, `trip_id`, `base_fare`, `discount_percentage`, `discount_amount`, `net_payment`, `balance_before`, `balance_after`, `loyalty_points_earned`, `is_group`, `passenger_count`, `card_holder_boarding`, `transaction_date`, `is_refunded`, `refunded_at`, `points_deducted`) VALUES
(1, 'RC-20260901121542342', 2, 1, 1, 1, 450.00, 0.00, 0.00, 450.00, 500.00, 50.00, 2, '0', 1, '1', '2026-09-01 12:15:42', 0, NULL, 0),
(2, 'GRP-20260901121621126', 1, 1, 1, 1, 0.00, 0.00, 0.00, 960.00, 1000.00, 40.00, 8, '1', 2, '1', '2026-09-01 12:16:21', 0, NULL, 0),
(3, 'RC-20260901130311916', 3, 2, 2, 2, 450.00, 0.00, 0.00, 450.00, 1000.00, 550.00, 1, '0', 1, '1', '2026-09-01 13:03:11', 0, NULL, 0),
(4, 'GRP-20260901134601141', 1, 2, 2, 2, 0.00, 0.00, 0.00, 900.00, 5000.00, 4100.00, 2, '1', 2, '1', '2026-09-01 13:46:01', 0, NULL, 0),
(5, 'RC-20260902023114770', 3, 3, 3, 5, 420.00, 0.00, 0.00, 420.00, 550.00, 130.00, 1, '0', 1, '1', '2026-09-02 02:31:14', 0, NULL, 0),
(6, 'GRP-20260902023327870', 1, 3, 3, 5, 0.00, 0.00, 0.00, 2700.00, 4100.00, 1400.00, 6, '1', 6, '1', '2026-09-02 02:33:27', 0, NULL, 0),
(7, 'RC-20260902023802299', 2, 3, 3, 5, 450.00, 0.00, 0.00, 450.00, 550.00, 100.00, 1, '0', 1, '1', '2026-09-02 02:38:02', 0, NULL, 0),
(8, 'RC-20260902023907529', 4, 3, 3, 5, 450.00, 0.00, 0.00, 450.00, 500.00, 50.00, 1, '0', 1, '1', '2026-09-02 02:39:07', 0, NULL, 0),
(9, 'RC-20260902023914256', 5, 3, 3, 5, 450.00, 0.00, 0.00, 450.00, 500.00, 50.00, 1, '0', 1, '1', '2026-09-02 02:39:14', 0, NULL, 0),
(10, 'RC-20260902031452903', 1, 3, 3, 6, 450.00, 0.00, 0.00, 450.00, 1500.00, 1050.00, 1, '0', 1, '1', '2026-09-02 03:14:52', 0, NULL, 0),
(11, 'RC-20260902031516412', 3, 3, 3, 6, 450.00, 0.00, 0.00, 450.00, 500.00, 50.00, 1, '0', 1, '1', '2026-09-02 03:15:16', 0, NULL, 0),
(12, 'GRP-20260902031619569', 2, 3, 3, 6, 0.00, 0.00, 0.00, 900.00, 1000.00, 100.00, 2, '1', 2, '1', '2026-09-02 03:16:19', 0, NULL, 0),
(13, 'RC-20260902032714493', 2, 3, 3, 7, 450.00, 0.00, 0.00, 450.00, 5000.00, 4550.00, 1, '0', 1, '1', '2026-09-02 03:27:14', 0, NULL, 0),
(14, 'RC-20260902032729343', 3, 3, 3, 7, 450.00, 0.00, 0.00, 450.00, 5000.00, 4550.00, 1, '0', 1, '1', '2026-09-02 03:27:29', 0, NULL, 0),
(15, 'GRP-20260902033017906', 1, 3, 3, 7, 0.00, 0.00, 0.00, 840.00, 5000.00, 4160.00, 2, '1', 2, '1', '2026-09-02 03:30:17', 0, NULL, 0),
(16, 'RC-20260904020846154', 3, 3, 3, 10, 450.00, 0.00, 0.00, 450.00, 4550.00, 4100.00, 1, '0', 1, '1', '2026-09-04 02:08:46', 0, NULL, 0),
(17, 'GRP-20260904021005221', 2, 3, 3, 10, 0.00, 0.00, 0.00, 840.00, 4550.00, 3710.00, 2, '1', 2, '1', '2026-09-04 02:10:05', 0, NULL, 0),
(18, 'RC-20260904021210102', 4, 3, 3, 10, 450.00, 100.00, 450.00, 0.00, 500.00, 500.00, 1, '0', 1, '1', '2026-09-04 02:12:10', 0, NULL, 0),
(19, 'RC-20260904021323321', 5, 3, 3, 11, 450.00, 0.00, 0.00, 450.00, 500.00, 50.00, 1, '0', 1, '1', '2026-09-04 02:13:23', 0, NULL, 0),
(20, 'RC-20260904021420525', 5, 3, 3, 12, 450.00, 0.00, 0.00, 450.00, 500.00, 50.00, 1, '0', 1, '1', '2026-09-04 02:14:20', 0, NULL, 0),
(21, 'GRP-20260906012259595', 1, 1, 1, 13, 0.00, 0.00, 0.00, 1824.00, 4165.00, 2341.00, 4, '1', 4, '1', '2026-09-06 01:22:59', 0, NULL, 0),
(22, 'RC-20260906012417743', 5, 1, 1, 13, 480.00, 0.00, 0.00, 480.00, 500.00, 20.00, 1, '0', 1, '1', '2026-09-06 01:24:17', 0, NULL, 0),
(23, 'RC-20260906013507716', 3, 4, 4, 18, 480.00, 0.00, 0.00, 480.00, 4100.00, 3620.00, 1, '0', 1, '1', '2026-09-06 01:35:07', 0, NULL, 0),
(24, 'GRP-20260906013632308', 1, 4, 4, 18, 0.00, 0.00, 0.00, 960.00, 2341.00, 1381.00, 2, '1', 2, '1', '2026-09-06 01:36:32', 0, NULL, 0),
(25, 'RC-20260913121956476', 1, 3, 3, 37, 450.00, 20.00, 90.00, 360.00, 1381.00, 1021.00, 1, '0', 1, '1', '2026-09-13 12:19:56', 0, NULL, 0),
(26, 'RC-20260916020039357', 1, 2, 2, 44, 450.00, 0.00, 0.00, 450.00, 1381.00, 931.00, 1, '0', 1, '1', '2026-09-16 02:00:39', 1, '2026-09-16 02:01:48', 1),
(27, 'RC-20260916020556528', 1, 3, 3, 45, 450.00, 0.00, 0.00, 450.00, 1381.00, 931.00, 1, '0', 1, '1', '2026-09-16 02:05:56', 1, '2026-09-16 02:06:06', 1),
(28, 'RC-20260919061829806', 1, 3, 3, 55, 450.00, 0.00, 0.00, 450.00, 1386.00, 936.00, 1, '0', 1, '1', '2026-09-19 06:18:29', 1, '2026-09-19 06:19:03', 1),
(29, 'RC-20260926052847153', 1, 2, 2, 74, 450.00, 0.00, 0.00, 450.00, 1386.00, 936.00, 1, '0', 1, '1', '2026-09-26 05:28:47', 0, NULL, 0),
(30, 'GRP-20260926053118621', 5, 2, 2, 74, 0.00, 0.00, 0.00, 900.00, 1020.00, 120.00, 2, '1', 2, '1', '2026-09-26 05:31:18', 0, NULL, 0),
(31, 'RC-20260929130940329', 1, 1, 12, 96, 23.00, 0.00, 0.00, 23.00, 936.00, 913.00, 1, '0', 1, '1', '2026-09-29 13:09:40', 0, NULL, 0),
(32, 'GRP-20260929131009183', 1, 1, 7, 95, 0.00, 0.00, 0.00, 4.00, 913.00, 909.00, 1, '1', 1, '1', '2026-09-29 13:10:09', 0, NULL, 0),
(33, 'GRP-20260929131051878', 5, 1, 7, 95, 0.00, 0.00, 0.00, 8.00, 120.00, 112.00, 2, '1', 2, '1', '2026-09-29 13:10:51', 0, NULL, 0),
(34, 'GRP-20260929131202362', 4, 1, 7, 95, 0.00, 0.00, 0.00, 8.00, 500.00, 492.00, 2, '1', 2, '1', '2026-09-29 13:12:02', 0, NULL, 0);

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
) ENGINE=MyISAM AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trips`
--

INSERT INTO `trips` (`id`, `schedule_id`, `trip_date`, `status`, `aircon_capacity`, `non_aircon_capacity`, `aircon_count`, `non_aircon_count`, `actual_departure`, `actual_arrival`, `passenger_count`, `created_at`) VALUES
(1, 1, '2026-09-01', 'arrived', 20, 20, 0, 0, '2026-09-01 20:35:21', '2026-09-01 20:49:56', 3, '2026-09-01 12:07:07'),
(2, 2, '2026-09-01', 'arrived', 0, 20, 0, 0, NULL, NULL, 3, '2026-09-01 13:00:59'),
(3, 1, '2026-09-02', 'upcoming', 20, 20, 0, 0, NULL, NULL, 0, '2026-09-02 02:29:48'),
(4, 2, '2026-09-02', 'upcoming', 0, 20, 0, 0, NULL, NULL, 0, '2026-09-02 02:29:48'),
(5, 3, '2026-09-02', 'arrived', 2, 2, 0, 0, '2026-09-02 11:09:53', '2026-09-02 11:09:56', 10, '2026-09-02 02:29:48'),
(6, 4, '2026-09-02', 'arrived', 2, 2, 2, 0, '2026-09-02 11:26:05', '2026-09-02 11:26:09', 4, '2026-09-02 02:29:48'),
(7, 5, '2026-09-02', 'arrived', 2, 2, 2, 2, '2026-09-02 11:41:18', '2026-09-02 11:41:20', 4, '2026-09-02 03:26:05'),
(8, 1, '2026-09-04', 'upcoming', 20, 20, 0, 0, NULL, NULL, 0, '2026-09-04 02:01:03'),
(9, 2, '2026-09-04', 'upcoming', 0, 20, 0, 0, NULL, NULL, 0, '2026-09-04 02:01:03'),
(10, 3, '2026-09-04', 'arrived', 2, 2, 2, 2, '2026-09-04 10:13:14', '2026-09-04 10:13:17', 4, '2026-09-04 02:01:03'),
(11, 4, '2026-09-04', 'cancelled', 2, 2, 1, 0, NULL, NULL, 1, '2026-09-04 02:01:03'),
(12, 5, '2026-09-04', 'cancelled', 2, 2, 1, 0, NULL, NULL, 1, '2026-09-04 02:01:03'),
(13, 1, '2026-09-06', 'arrived', 20, 20, 5, 0, '2026-09-06 11:50:26', '2026-09-06 11:50:35', 5, '2026-09-06 01:17:45'),
(14, 2, '2026-09-06', 'upcoming', 0, 20, 0, 0, NULL, NULL, 0, '2026-09-06 01:17:45'),
(15, 3, '2026-09-06', 'arrived', 2, 2, 0, 0, '2026-09-06 11:42:02', '2026-09-06 11:43:20', 0, '2026-09-06 01:17:45'),
(16, 4, '2026-09-06', 'arrived', 2, 2, 0, 0, '2026-09-06 11:42:09', '2026-09-06 11:43:27', 0, '2026-09-06 01:17:45'),
(17, 5, '2026-09-06', 'arrived', 2, 2, 0, 0, '2026-09-06 11:42:21', '2026-09-06 11:43:32', 0, '2026-09-06 01:17:45'),
(18, 6, '2026-09-06', 'arrived', 5, 5, 3, 0, '2026-09-06 11:37:59', '2026-09-06 11:38:03', 3, '2026-09-06 01:32:49'),
(19, 7, '2026-09-06', 'arrived', 5, 5, 0, 0, '2026-09-06 11:39:12', '2026-09-06 11:39:55', 0, '2026-09-06 01:32:49'),
(20, 8, '2026-09-06', 'arrived', 5, 5, 0, 0, '2026-09-06 11:38:56', '2026-09-06 11:39:50', 0, '2026-09-06 03:37:52'),
(21, 9, '2026-09-06', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-06 03:58:47'),
(22, 10, '2026-09-06', 'arrived', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-06 03:58:47'),
(23, 1, '2026-09-12', 'arrived', 20, 20, 0, 0, '2026-09-12 20:21:03', '2026-09-12 20:21:05', 0, '2026-09-12 12:15:21'),
(24, 2, '2026-09-12', 'upcoming', 0, 20, 0, 0, NULL, NULL, 0, '2026-09-12 12:15:21'),
(25, 3, '2026-09-12', 'arrived', 2, 2, 0, 0, '2026-09-12 20:22:01', '2026-09-12 20:22:04', 0, '2026-09-12 12:15:21'),
(26, 4, '2026-09-12', 'arrived', 2, 2, 0, 0, '2026-09-12 20:23:38', '2026-09-13 09:47:15', 0, '2026-09-12 12:15:21'),
(27, 5, '2026-09-12', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-12 12:15:21'),
(28, 6, '2026-09-12', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-12 12:15:21'),
(29, 7, '2026-09-12', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-12 12:15:21'),
(30, 9, '2026-09-12', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-12 12:15:21'),
(31, 8, '2026-09-12', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-12 12:15:21'),
(32, 10, '2026-09-12', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-12 12:15:21'),
(33, 1, '2026-09-13', 'upcoming', 20, 20, 0, 0, NULL, NULL, 0, '2026-09-13 01:34:55'),
(34, 2, '2026-09-13', 'arrived', 0, 20, 0, 0, '2026-09-13 09:42:02', '2026-09-13 09:42:05', 0, '2026-09-13 01:34:55'),
(35, 3, '2026-09-13', 'arrived', 2, 2, 0, 0, '2026-09-13 09:47:31', '2026-09-13 09:47:33', 0, '2026-09-13 01:34:55'),
(36, 4, '2026-09-13', 'arrived', 2, 2, 0, 0, '2026-09-13 20:11:33', '2026-09-13 20:11:35', 0, '2026-09-13 01:34:55'),
(37, 5, '2026-09-13', 'cancelled', 2, 2, 1, 0, NULL, NULL, 1, '2026-09-13 01:34:55'),
(38, 6, '2026-09-13', 'arrived', 5, 5, 0, 0, '2026-09-13 09:43:18', '2026-09-13 09:43:22', 0, '2026-09-13 01:34:55'),
(39, 7, '2026-09-13', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-13 01:34:55'),
(40, 9, '2026-09-13', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-13 01:34:55'),
(41, 8, '2026-09-13', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-13 01:34:55'),
(42, 10, '2026-09-13', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-13 01:34:55'),
(43, 1, '2026-09-16', 'upcoming', 20, 20, 0, 0, NULL, NULL, 0, '2026-09-16 01:59:52'),
(44, 2, '2026-09-16', 'cancelled', 0, 20, 0, 1, NULL, NULL, 1, '2026-09-16 01:59:52'),
(45, 3, '2026-09-16', 'cancelled', 2, 2, 1, 0, NULL, NULL, 1, '2026-09-16 01:59:52'),
(46, 4, '2026-09-16', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-16 01:59:52'),
(47, 5, '2026-09-16', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-16 01:59:52'),
(48, 6, '2026-09-16', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-16 01:59:52'),
(49, 7, '2026-09-16', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-16 01:59:52'),
(50, 9, '2026-09-16', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-16 01:59:52'),
(51, 8, '2026-09-16', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-16 01:59:52'),
(52, 10, '2026-09-16', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-16 01:59:52'),
(53, 1, '2026-09-19', 'upcoming', 20, 20, 0, 0, NULL, NULL, 0, '2026-09-19 06:10:57'),
(54, 2, '2026-09-19', 'upcoming', 0, 20, 0, 0, NULL, NULL, 0, '2026-09-19 06:10:57'),
(55, 3, '2026-09-19', 'cancelled', 2, 2, 1, 0, NULL, NULL, 1, '2026-09-19 06:10:57'),
(56, 4, '2026-09-19', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-19 06:10:57'),
(57, 5, '2026-09-19', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-19 06:10:57'),
(58, 6, '2026-09-19', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-19 06:10:57'),
(59, 7, '2026-09-19', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-19 06:10:57'),
(60, 9, '2026-09-19', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-19 06:10:57'),
(61, 8, '2026-09-19', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-19 06:10:57'),
(62, 10, '2026-09-19', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-19 06:10:57'),
(63, 1, '2026-09-24', 'upcoming', 20, 20, 0, 0, NULL, NULL, 0, '2026-09-24 11:27:06'),
(64, 2, '2026-09-24', 'cancelled', 0, 20, 0, 0, NULL, NULL, 0, '2026-09-24 11:27:06'),
(65, 3, '2026-09-24', 'arrived', 2, 2, 0, 0, '2026-09-24 20:08:14', '2026-09-24 20:09:59', 0, '2026-09-24 11:27:06'),
(66, 4, '2026-09-24', 'arrived', 2, 2, 0, 0, '2026-09-24 20:25:46', '2026-09-24 20:25:49', 0, '2026-09-24 11:27:06'),
(67, 5, '2026-09-24', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-24 11:27:06'),
(68, 6, '2026-09-24', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-24 11:27:06'),
(69, 7, '2026-09-24', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-24 11:27:06'),
(70, 9, '2026-09-24', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-24 11:27:06'),
(71, 8, '2026-09-24', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-24 11:27:06'),
(72, 10, '2026-09-24', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-24 11:27:06'),
(73, 1, '2026-09-26', 'upcoming', 20, 20, 0, 0, NULL, NULL, 0, '2026-09-26 05:26:32'),
(74, 2, '2026-09-26', 'arrived', 0, 20, 0, 3, '2026-09-26 14:08:28', '2026-09-26 14:08:30', 3, '2026-09-26 05:26:32'),
(75, 3, '2026-09-26', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-26 05:26:32'),
(76, 4, '2026-09-26', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-26 05:26:32'),
(77, 5, '2026-09-26', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-26 05:26:32'),
(78, 6, '2026-09-26', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-26 05:26:32'),
(79, 7, '2026-09-26', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-26 05:26:32'),
(80, 9, '2026-09-26', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-26 05:26:32'),
(81, 8, '2026-09-26', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-26 05:26:32'),
(82, 10, '2026-09-26', 'cancelled', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-26 05:26:32'),
(83, 11, '2026-09-26', 'upcoming', 0, 23, 0, 0, NULL, NULL, 0, '2026-09-26 13:04:38'),
(84, 12, '2026-09-26', 'upcoming', 0, 23, 0, 0, NULL, NULL, 0, '2026-09-26 13:04:38'),
(85, 13, '2026-09-26', 'upcoming', 0, 23, 0, 0, NULL, NULL, 0, '2026-09-26 13:04:38'),
(86, 14, '2026-09-26', 'upcoming', 0, 23, 0, 0, NULL, NULL, 0, '2026-09-26 13:04:38'),
(87, 1, '2026-09-29', 'upcoming', 20, 20, 0, 0, NULL, NULL, 0, '2026-09-29 05:17:44'),
(88, 2, '2026-09-29', 'upcoming', 0, 20, 0, 0, NULL, NULL, 0, '2026-09-29 05:17:44'),
(89, 3, '2026-09-29', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-29 05:17:44'),
(90, 4, '2026-09-29', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-29 05:17:44'),
(91, 5, '2026-09-29', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-29 05:17:44'),
(92, 6, '2026-09-29', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-29 05:17:44'),
(93, 7, '2026-09-29', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-29 05:17:44'),
(94, 9, '2026-09-29', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-29 05:17:44'),
(95, 10, '2026-09-29', 'arrived', 5, 5, 5, 0, '2026-09-29 21:12:37', '2026-09-29 21:12:39', 5, '2026-09-29 05:17:44'),
(96, 20, '2026-09-29', 'arrived', 0, 23, 0, 1, '2026-09-29 21:09:49', '2026-09-29 21:09:52', 1, '2026-09-29 05:17:44'),
(97, 19, '2026-09-29', 'arrived', 0, 23, 0, 0, '2026-09-29 14:57:10', '2026-09-29 14:57:15', 0, '2026-09-29 05:17:44'),
(98, 18, '2026-09-29', 'upcoming', 0, 23, 0, 0, NULL, NULL, 0, '2026-09-29 05:17:44'),
(99, 17, '2026-09-29', 'upcoming', 0, 23, 0, 0, NULL, NULL, 0, '2026-09-29 05:17:44'),
(100, 1, '2026-09-30', 'upcoming', 20, 20, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27'),
(101, 2, '2026-09-30', 'upcoming', 0, 20, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27'),
(102, 3, '2026-09-30', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27'),
(103, 4, '2026-09-30', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27'),
(104, 5, '2026-09-30', 'upcoming', 2, 2, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27'),
(105, 6, '2026-09-30', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27'),
(106, 7, '2026-09-30', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27'),
(107, 9, '2026-09-30', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27'),
(108, 10, '2026-09-30', 'upcoming', 5, 5, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27'),
(109, 20, '2026-09-30', 'upcoming', 0, 23, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27'),
(110, 19, '2026-09-30', 'upcoming', 0, 23, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27'),
(111, 18, '2026-09-30', 'upcoming', 0, 23, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27'),
(112, 17, '2026-09-30', 'upcoming', 0, 23, 0, 0, NULL, NULL, 0, '2026-09-30 11:45:27');

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
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trip_schedules`
--

INSERT INTO `trip_schedules` (`id`, `destination_id`, `departure_time`, `arrival_time`, `is_active`, `created_at`) VALUES
(1, 1, '05:00:00', '07:00:00', 1, '2026-09-01 12:06:52'),
(2, 2, '16:00:00', '18:00:00', 1, '2026-09-01 12:59:33'),
(3, 3, '06:00:00', '08:00:00', 1, '2026-09-02 02:28:25'),
(4, 3, '09:00:00', '11:00:00', 1, '2026-09-02 02:28:25'),
(5, 3, '13:00:00', '14:00:00', 1, '2026-09-02 03:26:02'),
(6, 4, '13:00:00', '14:00:00', 1, '2026-09-06 01:30:43'),
(7, 5, '14:00:00', '15:00:00', 1, '2026-09-06 01:31:41'),
(9, 5, '20:00:00', '21:00:00', 1, '2026-09-06 03:55:48'),
(10, 7, '14:00:00', '15:00:00', 1, '2026-09-06 03:58:34'),
(20, 12, '13:00:00', '15:00:00', 1, '2026-09-29 05:17:08'),
(19, 12, '06:00:00', '08:00:00', 1, '2026-09-29 05:17:08'),
(18, 11, '13:00:00', '15:00:00', 1, '2026-09-29 05:15:11'),
(17, 11, '06:00:00', '08:00:00', 1, '2026-09-29 05:15:11');

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
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vessels`
--

INSERT INTO `vessels` (`id`, `name`, `type`, `aircon_capacity`, `non_aircon_capacity`, `status`, `created_at`) VALUES
(1, 'VINCE GABRIEL 2', 'both', 20, 20, 'active', '2026-09-01 12:06:05'),
(2, 'TEST VESSEL', 'non_aircon', 0, 20, 'active', '2026-09-01 12:58:09'),
(3, 'VINCE GABRIEL 3', 'both', 2, 2, 'active', '2026-09-02 02:26:35'),
(4, 'ONE', 'both', 5, 5, 'active', '2026-09-06 01:28:15'),
(5, 'VG1', 'non_aircon', 0, 23, 'active', '2026-09-26 12:55:03'),
(6, 'VG2', 'non_aircon', 0, 23, 'active', '2026-09-26 12:55:14');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
