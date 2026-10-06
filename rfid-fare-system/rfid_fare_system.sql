-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 29, 2026 at 09:14 AM
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
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `balance_loads`
--

INSERT INTO `balance_loads` (`id`, `passenger_id`, `amount`, `reference_no`, `loaded_by`, `load_date`, `loaded_by_role`) VALUES
(1, 1, 100.00, 'LOAD-20260824042818795', 1, '2026-08-24 04:28:18', 'collector'),
(2, 3, 12.00, 'LOAD-20260824043319923', 1, '2026-08-24 04:33:19', 'collector'),
(3, 6, 20.00, 'LOAD-20260824043612884', 1, '2026-08-24 04:36:12', 'collector'),
(4, 1, 50.00, 'LOAD-20260824061458105', 2, '2026-08-24 06:14:58', 'collector'),
(5, 2, 123.00, 'LOAD-20260824062435614', 1, '2026-08-24 06:24:35', 'collector'),
(6, 8, 10.00, 'LOAD-20260824062622632', 1, '2026-08-24 06:26:22', 'collector'),
(7, 2, 12.00, 'LOAD-20260824064108122', 1, '2026-08-24 06:41:08', 'management'),
(8, 2, 20.00, 'LOAD-20260824065327193', 2, '2026-08-24 06:53:27', 'collector'),
(9, 9, 100.00, 'LOAD-20260825061025269', 1, '2026-08-25 06:10:25', 'management'),
(10, 6, 50.00, 'LOAD-20260825061950851', 1, '2026-08-25 06:19:50', 'collector');

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
(1, 1, 100.00, 'GCash', '564564', 'SADAS', '2026-08-24', 'assets/uploads/requests/receipt_20260824_060455.png', 'approved', NULL, '2026-08-24 06:04:55', 1, '2026-08-24 06:06:22'),
(2, 6, 100.00, 'Maya', '56456452', 'SADASSDA', '2026-08-25', 'assets/uploads/requests/receipt_20260825_061621.png', 'approved', NULL, '2026-08-25 06:16:21', 1, '2026-08-25 06:16:43'),
(3, 8, 100.00, 'GCash', '564564666666', 'SADAS', '2026-08-25', 'assets/uploads/requests/receipt_20260825_113118.png', 'approved', NULL, '2026-08-25 11:31:18', 1, '2026-08-25 11:31:40'),
(4, 8, 1000.00, 'Maya', '123', 'NINO', '2026-08-25', 'assets/uploads/requests/receipt_20260825_121630.png', 'approved', NULL, '2026-08-25 12:16:30', 1, '2026-08-25 12:16:46');

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
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `collectors`
--

INSERT INTO `collectors` (`id`, `employee_number`, `first_name`, `middle_name`, `last_name`, `password`, `full_name`, `contact`, `status`, `created_by`, `created_at`) VALUES
(1, 'VGL001', 'DAN', '', 'COLL', '$2y$10$PHQjWgmmThxyTyhFzmcDV./gBCBKHuGQwp.AHZE6VxkGDVr2dtFbK', '', '', 'active', 1, '2026-08-24 04:24:16'),
(2, 'VGL002', 'GEFERSON', '', 'COLL', '$2y$10$4taVKx5B1sra1NffzG1rS.343XEJx/MWyW3djujwCwCJeq/cNWCp2', '', '', 'active', 1, '2026-08-24 04:30:21'),
(3, 'VGL003', 'GRACE', '', 'COLL', '$2y$10$uGN6ZaNYFNTrL.hYTaCbaeWaZ9PF/5vnw6ACnDBY5WG.CTeSTYvXe', '', '', 'inactive', 1, '2026-08-24 04:30:27'),
(4, 'VGL004', 'JOHN', '', 'COLL', '$2y$10$QpmR6fEQQ0z/RG36/2lvR./WZ3tj9dpFvTRgUj4Gn44WFLXojfWX6', '', '', 'active', 1, '2026-08-24 04:30:38'),
(6, 'VGL005', 'JUAN LUNA', '', 'TEST', '$2y$10$nnEbNc5I3fLmConacstpl.EcOK.JmMK3KwdPlmzh9Wbd303yjQYz2', '', '', 'active', 1, '2026-08-25 06:11:41');

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
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `origin`, `destination`, `base_fare`, `is_active`, `created_at`) VALUES
(1, '1', '2', 23.00, 1, '2026-08-24 04:31:29'),
(2, '2', '1', 12.00, 0, '2026-08-24 04:31:35'),
(3, '3', '4', 34.00, 1, '2026-08-24 04:31:56'),
(5, 'SURIGAO CITY', 'SAN JOSE', 20.00, 0, '2026-08-25 06:13:48');

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
(1, 'senior', 20.00, 1, '2026-08-24 04:23:18'),
(2, 'pwd', 20.00, 1, '2026-08-24 04:23:18'),
(3, 'student', 10.00, 1, '2026-08-24 04:23:18'),
(4, 'regular', 0.00, 1, '2026-08-24 04:23:18');

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
(1, 0.10, 10.00, NULL, '2026-08-24 04:23:18');

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
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`id`, `rfid_uid`, `first_name`, `middle_name`, `last_name`, `password`, `full_name`, `email`, `contact`, `balance`, `loyalty_points`, `discount_type_id`, `status`, `created_by`, `created_at`, `created_by_role`) VALUES
(1, '3213920020', 'JUAN LUNA', '', 'ROMERO', '$2y$10$cedX902N7bOpwiCAuG.Rou6pOhx7ci1f0XmjLZsVAINaorQQ2dIZm', '', 'yu.dan.carter.15@gmail.com', '123', 227.00, 2, 4, 'active', 'VGL001', '2026-08-24 04:25:04', 'collector'),
(2, '3211421460', 'GRACE', '', 'ROMEROS', '$2y$10$HzM7wWjRmsKhmzeFFDxMZ.dNMlbUmCQfLVZkzj7uHzCIyc2U.UP42', '', 'grace@gmail.com', '0666506', 155.00, 0, 4, 'active', 'admin', '2026-08-24 04:26:42', 'management'),
(3, '3484554904', 'GEFERSON', '', 'SAMSON', '$2y$10$HVD/TDNGgYTEJ3LX4uPI0.wVZ9z4.0I/3/RUesBr/va5452t1jMmW', '', 'gef@gmail.com', '', 12.00, 0, 4, 'blocked', 'admin', '2026-08-24 04:27:10', 'management'),
(4, '3217705764', 'JOHN', '', 'YTAC', '$2y$10$XC0R.SrzUX8z5uZul/JHZOGXE3qVkx3IEwFamXIWmP9yY/tV3a0Jy', '', 'john@gmail.com', '123', 0.00, 0, 4, 'lost', 'admin', '2026-08-24 04:27:45', 'management'),
(6, '2743368246', 'TRY', '', 'TEST', '$2y$10$MgQW4bcS8IPD8nyOXPX13uBMrqxOtVV.A7nA0DJmJF6jyutLF5JGy', '', 'afirst562@gmai.com', '0666506', 147.00, 2, 4, 'active', 'VGL001', '2026-08-24 04:36:03', 'collector'),
(7, '2739603046', 'TRY', '', 'TEST2', '$2y$10$WZqZcJ42ynQplsxjux89Ke2WKbUWeqNyWR1XxU.yb92Y0BxV8OIkm', '', 'dancarter.yuu@gmail.com', '1231', 0.00, 0, 4, 'active', 'VGL001', '2026-08-24 06:11:22', 'collector'),
(8, '2750407878', 'TRY TRA', '', 'TEST', '$2y$10$c4dvCq8DYFhG7bSr7CIM2OtuRu198lQE4LJEzr68wITfxl/ubVSFq', '', 'dancarter.yuu@gmail.com', '123', 1041.00, 6, 4, 'active', 'VGL002', '2026-08-24 06:12:25', 'collector'),
(9, '2750631654', 'JUAN LUNA', '', 'RA', '$2y$10$s5veWa5dt9EIy/x9VeavBOJLIRNRcMaQ6lSZ7YgfpCVVkmtA90wdG', '', 'dancarter2.12.04@gmail.com', '22222', 100.00, 0, 4, 'lost', 'admin', '2026-08-25 06:09:50', 'management');

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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rfid_replacements`
--

INSERT INTO `rfid_replacements` (`id`, `passenger_id`, `old_rfid_uid`, `new_rfid_uid`, `reason`, `replaced_by`, `replacement_date`) VALUES
(1, 3, '2743368246', '3484554904', 'damaged', 1, '2026-08-24 04:28:45'),
(2, 10, '3212471044', '3215720212', 'lost', 1, '2026-08-25 11:25:18');

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
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `receipt_no`, `passenger_id`, `collector_id`, `destination_id`, `base_fare`, `discount_percentage`, `discount_amount`, `net_payment`, `balance_before`, `balance_after`, `loyalty_points_earned`, `transaction_date`) VALUES
(1, 'RC-20260824060303989', 1, 1, 1, 23.00, 0.00, 0.00, 23.00, 100.00, 77.00, 2, '2026-08-24 06:03:03'),
(2, 'RC-20260825061817648', 6, 1, 1, 23.00, 0.00, 0.00, 23.00, 120.00, 97.00, 2, '2026-08-25 06:18:17'),
(3, 'RC-20260825113525388', 8, 2, 1, 23.00, 0.00, 0.00, 23.00, 110.00, 87.00, 2, '2026-08-25 11:35:25'),
(4, 'RC-20260825114205696', 8, 2, 1, 23.00, 0.00, 0.00, 23.00, 87.00, 64.00, 2, '2026-08-25 11:42:05'),
(5, 'RC-20260825115454657', 8, 2, 1, 23.00, 0.00, 0.00, 23.00, 64.00, 41.00, 2, '2026-08-25 11:54:54');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
