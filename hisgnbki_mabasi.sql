-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 21, 2026 at 02:07 PM
-- Server version: 11.4.13-MariaDB-cll-lve
-- PHP Version: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hisgnbki_mabasi`
--

-- --------------------------------------------------------

--
-- Table structure for table `access`
--

CREATE TABLE `access` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `link` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `access`
--

INSERT INTO `access` (`id`, `user_id`, `link`, `status`, `created_at`, `updated_at`) VALUES
(1, 15, 'bus-schedule', 'active', '2026-07-17 16:13:05', '2026-07-17 16:13:05'),
(2, 15, 'bus-operators', 'active', '2026-07-17 16:13:19', '2026-07-17 16:13:19'),
(3, 15, 'vendors', 'active', '2026-07-17 16:15:14', '2026-07-17 16:15:14'),
(4, 15, 'cities', 'active', '2026-07-17 16:15:31', '2026-07-17 16:15:31'),
(5, 15, 'buses', 'active', '2026-07-17 16:15:58', '2026-07-17 16:15:58'),
(6, 15, 'discounts', 'active', '2026-07-17 16:16:45', '2026-07-17 16:16:45'),
(7, 15, 'insurance', 'active', '2026-07-17 16:17:20', '2026-07-17 16:17:20'),
(8, 15, 'booking-history', 'active', '2026-07-17 16:17:37', '2026-07-17 16:17:37'),
(9, 15, 'refunds', 'active', '2026-07-17 16:19:01', '2026-07-17 16:19:01'),
(10, 15, 'system-income', 'active', '2026-07-17 16:19:33', '2026-07-17 16:19:33'),
(11, 15, 'cards', 'active', '2026-07-17 16:20:26', '2026-07-17 16:20:26'),
(12, 15, 'special-hire', 'active', '2026-07-17 16:21:47', '2026-07-17 16:21:47'),
(13, 35, 'index', 'active', '2026-07-18 11:03:57', '2026-08-18 01:33:06'),
(14, 35, 'buses', 'active', '2026-07-18 11:04:29', '2026-07-18 11:04:29'),
(15, 35, 'routes', 'active', '2026-07-18 11:04:46', '2026-07-18 11:04:46'),
(16, 35, 'erning', 'active', '2026-07-18 11:06:55', '2026-07-18 11:06:55'),
(17, 35, 'logout', 'active', '2026-07-18 11:12:32', '2026-07-18 11:12:55'),
(18, 35, 'schedules', 'active', '2026-07-18 11:14:02', '2026-07-18 11:14:02');

-- --------------------------------------------------------

--
-- Table structure for table `admin_transactions`
--

CREATE TABLE `admin_transactions` (
  `id` int(11) NOT NULL,
  `trans_ref_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_number` int(11) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_wallet`
--

CREATE TABLE `admin_wallet` (
  `id` int(11) NOT NULL,
  `service_balance` decimal(10,2) DEFAULT 0.00,
  `commision_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin_wallet`
--

INSERT INTO `admin_wallet` (`id`, `service_balance`, `commision_balance`, `balance`, `vat`, `created_at`, `updated_at`) VALUES
(4, 0.00, 0.00, 96166.00, 0.00, '2026-07-18 20:28:16', '2026-09-21 08:32:28');

-- --------------------------------------------------------

--
-- Table structure for table `balances`
--

CREATE TABLE `balances` (
  `id` int(11) NOT NULL,
  `campany_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL,
  `fees` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `balances`
--

INSERT INTO `balances` (`id`, `campany_id`, `amount`, `created_at`, `updated_at`, `fees`) VALUES
(7, 4, 0, '2026-07-17 15:29:05', '2026-08-16 18:26:58', 0),
(6, 3, 1395388, '2026-07-17 03:14:33', '2026-09-21 08:32:28', 0),
(8, 5, 81000, '2026-07-31 15:47:05', '2026-08-16 20:26:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `bima`
--

CREATE TABLE `bima` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bima_vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bima`
--

INSERT INTO `bima` (`id`, `booking_id`, `start_date`, `end_date`, `amount`, `bima_vat`, `created_at`, `updated_at`) VALUES
(19, 219, '2026-08-18', '2026-08-18', 200.00, 30.51, '2026-08-16 22:30:33', '2026-08-16 22:30:33'),
(20, 234, '2026-09-07', '2026-09-09', 600.00, 91.53, '2026-09-01 13:50:16', '2026-09-01 13:50:16'),
(21, 236, '2026-09-07', '2026-09-11', 1000.00, 152.54, '2026-09-03 12:51:54', '2026-09-03 12:51:54'),
(22, 240, '2026-09-20', '2026-09-21', 400.00, 61.02, '2026-09-17 16:39:19', '2026-09-17 16:39:19'),
(23, 241, '2026-09-20', '2026-09-20', 200.00, 30.51, '2026-09-19 11:47:11', '2026-09-19 11:47:11'),
(24, 242, '2026-09-22', '2026-09-26', 1000.00, 152.54, '2026-09-20 18:02:17', '2026-09-20 18:02:17'),
(25, 243, '2026-09-22', '2026-09-26', 1000.00, 152.54, '2026-09-21 12:02:47', '2026-09-21 12:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_code` varchar(50) DEFAULT NULL,
  `campany_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bus_id` bigint(20) UNSIGNED DEFAULT NULL,
  `route_id` bigint(20) UNSIGNED DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `customer_phone` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `passengers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`passengers`)),
  `gender` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `infant_child` tinyint(1) NOT NULL DEFAULT 0,
  `age_group` varchar(255) DEFAULT NULL,
  `has_excess_luggage` tinyint(1) NOT NULL DEFAULT 0,
  `excess_luggage_fee` int(11) NOT NULL DEFAULT 0,
  `excess_luggage_description` varchar(500) DEFAULT NULL,
  `estimated_weight` decimal(8,2) DEFAULT NULL,
  `actual_weight` decimal(8,2) DEFAULT NULL,
  `actual_length` decimal(8,2) DEFAULT NULL,
  `actual_height` decimal(8,2) DEFAULT NULL,
  `actual_width` decimal(8,2) DEFAULT NULL,
  `luggage_refund_amount` decimal(10,2) DEFAULT NULL,
  `luggage_weight_verdict` varchar(32) DEFAULT NULL,
  `luggage_status` varchar(32) DEFAULT NULL,
  `luggage_payment_ref` varchar(100) DEFAULT NULL,
  `luggage_payment_status` varchar(32) DEFAULT NULL,
  `luggage_weighed_at` timestamp NULL DEFAULT NULL,
  `luggage_weighed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `luggage_assigned_at` timestamp NULL DEFAULT NULL,
  `luggage_assigned_by` bigint(20) UNSIGNED DEFAULT NULL,
  `luggage_retrieved_at` timestamp NULL DEFAULT NULL,
  `luggage_retrieved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `pickup_point` varchar(255) DEFAULT NULL,
  `dropping_point` varchar(255) DEFAULT NULL,
  `travel_date` date DEFAULT NULL,
  `seat` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `customer_paid_total` decimal(15,2) DEFAULT NULL,
  `payment_status` varchar(255) DEFAULT NULL,
  `resaved_until` timestamp NULL DEFAULT NULL,
  `trans_status` varchar(255) DEFAULT NULL,
  `transaction_ref_id` varchar(255) DEFAULT NULL,
  `external_ref_id` varchar(255) DEFAULT NULL,
  `mfs_id` varchar(255) DEFAULT NULL,
  `verification_code` varchar(255) DEFAULT NULL,
  `bima` int(11) DEFAULT NULL,
  `bima_amount` int(11) DEFAULT NULL,
  `insuranceDate` varchar(255) DEFAULT NULL,
  `vender_id` varchar(255) DEFAULT NULL,
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `service` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vender_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vender_service` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `government_levy` decimal(10,2) NOT NULL DEFAULT 0.00,
  `system_service_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` varchar(255) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `distance` int(11) NOT NULL DEFAULT 0,
  `busFee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fee_vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `service_vat` decimal(10,2) DEFAULT 0.00,
  `bima_vat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(255) DEFAULT NULL,
  `booking_channel` varchar(20) DEFAULT NULL,
  `trans_token` varchar(255) DEFAULT NULL,
  `tra_status` varchar(255) DEFAULT 'pending',
  `tra_rct_num` varchar(255) DEFAULT NULL,
  `tra_z_num` varchar(255) DEFAULT NULL,
  `tra_vnum` varchar(255) DEFAULT NULL,
  `tra_qr_url` text DEFAULT NULL,
  `tra_response` text DEFAULT NULL,
  `tra_error` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`created_at`, `updated_at`, `id`, `booking_code`, `campany_id`, `bus_id`, `route_id`, `schedule_id`, `customer_phone`, `customer_email`, `customer_name`, `passengers`, `gender`, `age`, `infant_child`, `age_group`, `has_excess_luggage`, `excess_luggage_fee`, `excess_luggage_description`, `estimated_weight`, `actual_weight`, `actual_length`, `actual_height`, `actual_width`, `luggage_refund_amount`, `luggage_weight_verdict`, `luggage_status`, `luggage_payment_ref`, `luggage_payment_status`, `luggage_weighed_at`, `luggage_weighed_by`, `luggage_assigned_at`, `luggage_assigned_by`, `luggage_retrieved_at`, `luggage_retrieved_by`, `user_id`, `pickup_point`, `dropping_point`, `travel_date`, `seat`, `amount`, `customer_paid_total`, `payment_status`, `resaved_until`, `trans_status`, `transaction_ref_id`, `external_ref_id`, `mfs_id`, `verification_code`, `bima`, `bima_amount`, `insuranceDate`, `vender_id`, `fee`, `service`, `vender_fee`, `vender_service`, `vat`, `government_levy`, `system_service_fee`, `discount`, `discount_amount`, `distance`, `busFee`, `fee_vat`, `service_vat`, `bima_vat`, `payment_method`, `booking_channel`, `trans_token`, `tra_status`, `tra_rct_num`, `tra_z_num`, `tra_vnum`, `tra_qr_url`, `tra_response`, `tra_error`) VALUES
('2026-08-16 22:30:33', '2026-08-16 18:36:41', 219, 'SH42945199', 3, 7, 7, 305, '255715020945', 'chizithomas@gmail.com', 'Anna Assenga', '[{\"seat\":\"A7\",\"name\":\"Anna Assenga\",\"phone\":\"0715020945\",\"age_group\":\"Adult\"}]', 'Male', 25, 0, 'Adult', 1, 25000, 'With Extra kilos', 10.00, NULL, NULL, NULL, NULL, 0.00, 'correct', 'retrieved', NULL, 'none_required', '2026-08-16 22:34:03', 20, '2026-08-16 22:34:24', 20, '2026-08-16 22:36:41', 20, NULL, 'General', 'Mbezi maguguli', '2026-08-18', 'A7', 36000.00, 66100.00, 'Paid', NULL, 'success', 'TEST-6A81D7992B2CA6268', NULL, NULL, NULL, 1, 200, '2026-08-18', '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 450, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100219', '20260816', 'T100219', 'https://virtual.tra.go.tz/efdmsrctverify/T100219_183033', 'test_mode_mock', NULL),
('2026-08-16 22:42:23', '2026-08-17 09:33:44', 220, 'AY26333944', 3, 6, 6, 412, '255715020945', 'chizithomas@gmail.com', 'Abdallah Mohammed Milanzi', '[{\"seat\":\"C1\",\"name\":\"Abdallah Mohammed Milanzi\",\"phone\":\"0715020945\",\"age_group\":\"Senior\"}]', 'Male', 25, 0, 'Senior', 1, 25000, 'Sanduku kubwa sana', 7.00, 10.00, NULL, NULL, NULL, 7500.00, 'underestimated', 'retrieved', 'TESTXLUG220895171', 'paid', '2026-08-16 22:45:21', 20, '2026-08-16 22:46:38', 20, '2026-08-16 22:47:46', 20, 34, 'Shekilango', 'General', '2026-08-18', 'C1', 36000.00, 65900.00, 'Paid', NULL, 'success', 'TEST-6A81DA5F5D82F3888', NULL, NULL, NULL, 0, 0, NULL, '', 2000.00, 900.00, 0.00, 0.00, 0.00, 2000.00, 900.00, '', 0.00, 69, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'online', NULL, 'success', '100220', '20260816', 'T100220', 'https://virtual.tra.go.tz/efdmsrctverify/T100220_184223', 'test_mode_mock', NULL),
('2026-08-16 23:18:45', '2026-08-16 19:33:05', 221, 'RE45390407', 3, 6, 6, 412, '255789473209', 'chizithomas@gmail.com', 'Neema John Chambo', '[{\"seat\":\"C2\",\"name\":\"Neema John Chambo\",\"phone\":\"0789473209\",\"age_group\":\"Adult\"}]', 'Male', 25, 0, 'Adult', 1, 17500, 'extra kilos', 10.00, 7.00, NULL, NULL, NULL, -7500.00, 'overestimated', 'retrieved', 'RE4539XLUGREF897386', 'refunded', '2026-08-16 23:21:37', 20, NULL, NULL, '2026-08-16 23:28:02', 20, NULL, 'Shekilango', 'General', '2026-08-18', 'C2', 36000.00, 58400.00, 'Paid', NULL, 'success', 'TEST-6A81E2E58F9B64693', NULL, NULL, NULL, 0, 0, NULL, '', 2000.00, 900.00, 0.00, 0.00, 0.00, 2000.00, 900.00, '', 0.00, 69, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'online', NULL, 'success', '100221', '20260816', 'T100221', 'https://virtual.tra.go.tz/efdmsrctverify/T100221_191845', 'test_mode_mock', NULL),
('2026-08-17 00:26:00', '2026-08-16 20:26:00', 222, 'EU42334306', 5, 11, 11, 346, '255718191919', NULL, 'Assh', '[{\"seat\":\"A4\",\"name\":\"Assh\",\"phone\":\"0718191919\",\"age_group\":\"Adult\"}]', 'Male', 25, 0, 'Adult', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Dar es salaam', 'Mwanza', '2026-08-16', 'A4', 81000.00, 91900.00, 'Paid', NULL, 'success', 'TEST-6A81F2A863C0D9880', NULL, NULL, NULL, 0, 0, NULL, '41', 4050.00, 1710.00, 450.00, 190.00, 0.00, 4500.00, 1900.00, '', 0.00, 1153, 90000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100222', '20260816', 'T100222', 'https://virtual.tra.go.tz/efdmsrctverify/T100222_202600', 'test_mode_mock', NULL),
('2026-08-19 16:04:41', '2026-08-20 12:04:42', 223, 'HZ71246298', 3, 6, 6, 413, '255789473209', NULL, 'Jumanne Rashid Msasa', '[{\"seat\":\"B1\",\"name\":\"Jumanne Rashid Msasa\",\"phone\":\"0789473209\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 1, 25000, 'extra kilos of rice', 10.00, NULL, NULL, NULL, NULL, NULL, NULL, 'declared', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'General', 'Shekilango', '2026-08-19', 'B1', 65900.00, NULL, 'Fail', '2026-08-20 16:04:41', NULL, 'TEST-6A8571A9874982449', NULL, NULL, NULL, 0, 0, NULL, '36', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, '', 0.00, 69, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
('2026-08-20 12:27:43', '2026-08-20 08:27:43', 224, 'CG63508255', 3, 7, 7, 307, '255789473209', 'chizithomas@gmail.com', 'Joyce Ninahaja', '[{\"seat\":\"A23\",\"name\":\"Joyce Ninahaja\",\"phone\":\"0789473209\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nanenane', 'Kimara temboni', '2026-08-20', 'A23', 36000.00, 40900.00, 'Paid', NULL, 'success', 'TEST-6A86904F8AEFD4217', NULL, NULL, NULL, 0, 0, NULL, '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 450, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100224', '20260820', 'T100224', 'https://virtual.tra.go.tz/efdmsrctverify/T100224_082743', 'test_mode_mock', NULL),
('2026-08-21 21:24:24', '2026-08-21 17:24:25', 225, 'GS75925755', 3, 7, 7, 308, '255749343101', NULL, 'Assenga', '[{\"seat\":\"A21\",\"name\":\"Assenga\",\"phone\":\"0749343101\",\"age_group\":\"Adult\"}]', 'Male', 25, 0, 'Adult', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Kimara temboni', 'Job Ndugai Bus Station', '2026-08-21', 'A21', 36000.00, 40900.00, 'Paid', NULL, 'success', 'TEST-6A885F98B09F91614', NULL, NULL, NULL, 0, 0, NULL, '', 2000.00, 900.00, 0.00, 0.00, 0.00, 2000.00, 900.00, '', 0.00, 450, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'online', NULL, 'success', '100225', '20260821', 'T100225', 'https://virtual.tra.go.tz/efdmsrctverify/T100225_172425', 'test_mode_mock', NULL),
('2026-08-26 01:49:15', '2026-08-25 21:49:16', 226, 'QF63950131', 3, 6, 6, 432, '255788474747', 'chizithomas@gmail.com', 'Sarah Moses Kiria', '[{\"seat\":\"A2\",\"name\":\"Sarah Moses Kiria\",\"phone\":\"0788474747\",\"age_group\":\"Adult\"}]', 'Male', 25, 0, 'Adult', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Shekilango', 'General', '2026-09-07', 'A2', 36000.00, 40900.00, 'Paid', NULL, 'success', 'TEST-6A8DE3ABF2BBE6423', NULL, NULL, NULL, 0, 0, NULL, '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 69, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100226', '20260825', 'T100226', 'https://virtual.tra.go.tz/efdmsrctverify/T100226_214916', 'test_mode_mock', NULL),
('2026-08-26 09:15:46', '2026-08-26 05:15:46', 227, 'CU91995088', 3, 6, 6, 432, '255715020945', 'chizithomas@gmail.com', 'Eliada Samson Mayunga', '[{\"seat\":\"A3\",\"name\":\"Eliada Samson Mayunga\",\"phone\":\"0715020945\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Shekilango', 'General', '2026-09-07', 'A3', 36000.00, 40900.00, 'Paid', NULL, 'success', 'TEST-6A8E4C52414C08766', NULL, NULL, NULL, 0, 0, NULL, '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 69, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100227', '20260826', 'T100227', 'https://virtual.tra.go.tz/efdmsrctverify/T100227_051546', 'test_mode_mock', NULL),
('2026-08-26 09:39:38', '2026-08-26 05:39:39', 228, 'DS82734278', 3, 6, 6, 420, '255789473209', 'chizithomas@gmail.com', 'Maimuna Salum', '[{\"seat\":\"B1\",\"name\":\"Maimuna Salum\",\"phone\":\"0789473209\",\"age_group\":\"Senior\"}]', 'Male', 25, 0, 'Senior', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Shekilango', 'General', '2026-08-26', 'B1', 36000.00, 40900.00, 'Paid', NULL, 'success', 'TEST-6A8E51EADD6976571', NULL, NULL, NULL, 0, 0, NULL, '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 69, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100228', '20260826', 'T100228', 'https://virtual.tra.go.tz/efdmsrctverify/T100228_053939', 'test_mode_mock', NULL),
('2026-08-27 00:18:49', '2026-08-26 23:02:40', 229, 'LB02341704', 3, 6, 6, 432, '255715020945', 'chizithomas@gmail.com', 'Jacqueline Maliamungu Mnyasi', '[{\"seat\":\"A1\",\"name\":\"Jacqueline Maliamungu Mnyasi\",\"phone\":\"0715020945\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 1, 25000, 'Extra laggage', 10.00, NULL, NULL, NULL, NULL, 0.00, 'correct', 'ready', NULL, 'none_required', '2026-08-27 03:02:40', 20, NULL, NULL, NULL, NULL, NULL, 'Shekilango', 'General', '2026-09-07', 'A1', 36000.00, 65900.00, 'Paid', NULL, 'success', 'TEST-6A8F1FF99D22C9949', NULL, NULL, NULL, 0, 0, NULL, '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 69, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100229', '20260826', 'T100229', 'https://virtual.tra.go.tz/efdmsrctverify/T100229_201850', 'test_mode_mock', NULL),
('2026-08-27 16:39:55', '2026-08-27 16:34:31', 230, 'NA86148158', 3, 6, 6, 432, '255788888888', 'chizithomas@gmail.com', 'Penina Lameck Mtenga', '[{\"seat\":\"D1\",\"name\":\"Penina Lameck Mtenga\",\"phone\":\"0788888888\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 1, 75000, 'Extra kgs', 20.00, 30.00, NULL, NULL, NULL, 25000.00, 'underestimated', 'ready', 'TESTXLUG230837671', 'paid', '2026-08-27 20:30:31', 20, NULL, NULL, NULL, NULL, NULL, 'Shekilango', 'General', '2026-09-07', 'D1', 36000.00, 115900.00, 'Paid', NULL, 'success', 'TEST-6A9005EBCA59B2354', NULL, NULL, NULL, 0, 0, NULL, '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 69, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100230', '20260827', 'T100230', 'https://virtual.tra.go.tz/efdmsrctverify/T100230_123956', 'test_mode_mock', NULL),
('2026-08-31 02:16:18', '2026-08-30 22:19:36', 231, 'MF61171230', 3, 6, 6, 432, '255788888811', 'chizithomas@gmail.com', 'Asha Simon Marandu', '[{\"seat\":\"B4\",\"name\":\"Asha Simon Marandu\",\"phone\":\"0788888811\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 1, 50000, 'Extra kilos of rice', 20.00, 15.00, NULL, NULL, NULL, -12500.00, 'overestimated', 'ready', NULL, 'refund_noted', '2026-08-31 02:19:36', 20, NULL, NULL, NULL, NULL, NULL, 'Shekilango', 'General', '2026-09-07', 'B4', 36000.00, 90900.00, 'Paid', NULL, 'success', 'TEST-6A94818284C435769', NULL, NULL, NULL, 0, 0, NULL, '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 69, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100231', '20260830', 'T100231', 'https://virtual.tra.go.tz/efdmsrctverify/T100231_221619', 'test_mode_mock', NULL),
('2026-08-31 03:24:45', '2026-08-30 23:24:46', 232, 'UW91157873', 3, 6, 6, 432, '255789999991', 'chizithomas@gmail.com', 'Samson Madhambi', '[{\"seat\":\"F2\",\"name\":\"Samson Madhambi\",\"phone\":\"0789999991\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 1, 25000, 'extra kilos', 10.00, NULL, NULL, NULL, NULL, NULL, NULL, 'declared', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Shekilango', 'General', '2026-09-07', 'F2', 32400.00, 61820.00, 'Paid', NULL, 'success', 'TEST-6A94918DC4AF13951', NULL, NULL, NULL, 0, 0, NULL, '36', 1620.00, 738.00, 180.00, 82.00, 0.00, 1800.00, 820.00, '1234', 4000.00, 69, 36000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100232', '20260830', 'T100232', 'https://virtual.tra.go.tz/efdmsrctverify/T100232_232446', 'test_mode_mock', NULL),
('2026-08-31 04:30:25', '2026-09-01 00:30:29', 233, 'BG23942532', 3, 6, 6, 432, '255773333444', NULL, 'James Kubatu', '[{\"seat\":\"B2\",\"name\":\"James Kubatu\",\"phone\":\"0773333444\",\"age_group\":\"Adult\"}]', 'Male', 25, 0, 'Adult', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Shekilango', 'Dodoma', '2026-09-07', 'B2', 40900.00, NULL, 'Fail', '2026-09-01 04:30:25', NULL, 'TEST-6A94A0F1A47549328', NULL, NULL, NULL, 0, 0, NULL, '36', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, '', 0.00, 446, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
('2026-09-01 13:49:22', '2026-09-01 09:50:16', 234, 'UA35686570', 3, 6, 6, 432, '255779779779', NULL, 'Diana Kasole', '[{\"seat\":\"C1\",\"name\":\"Diana Kasole\",\"phone\":\"0779779779\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 1, 30000, 'few extra kilos of rice', 12.00, NULL, NULL, NULL, NULL, NULL, NULL, 'declared', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Shekilango', 'General', '2026-09-07', 'C1', 36000.00, 71500.00, 'Paid', '2026-09-02 13:49:22', 'success', 'TEST-6A967572D6E337199', NULL, NULL, NULL, 1, 600, '2026-09-09', '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 452, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100234', '20260901', 'T100234', 'https://virtual.tra.go.tz/efdmsrctverify/T100234_095016', 'test_mode_mock', NULL),
('2026-09-01 13:55:45', '2026-09-01 09:55:46', 235, 'XC15355170', 3, 6, 6, 426, '255773773773', NULL, 'Simon Kundecha', '[{\"seat\":\"B1\",\"name\":\"Simon Kundecha\",\"phone\":\"0773773773\",\"age_group\":\"Adult\"}]', 'Male', 25, 0, 'Adult', 1, 25000, 'few extra kilos of rice', 10.00, NULL, NULL, NULL, NULL, NULL, NULL, 'declared', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Shekilango', 'Dodoma', '2026-09-01', 'B1', 36000.00, 65900.00, 'Paid', NULL, 'success', 'TEST-6A9676F16CB4A9817', NULL, NULL, NULL, 0, 0, NULL, '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 446, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100235', '20260901', 'T100235', 'https://virtual.tra.go.tz/efdmsrctverify/T100235_095546', 'test_mode_mock', NULL),
('2026-09-03 12:47:51', '2026-09-03 11:23:48', 236, 'OB39756277', 3, 6, 6, 432, '255788667766', 'chizithomas@gmail.com', 'Salum Issa Nanganga', '[{\"seat\":\"E3\",\"name\":\"Salum Issa Nanganga\",\"phone\":\"0788667766\",\"age_group\":\"Adult\"}]', 'Male', 25, 0, 'Adult', 1, 25000, 'one extra bag', 5.00, 10.00, NULL, NULL, NULL, 12500.00, 'underestimated', 'ready', 'TESTXLUG236423828', 'paid', '2026-09-03 15:23:25', 20, NULL, NULL, NULL, NULL, NULL, 'Shekilango', 'General', '2026-09-07', 'E3', 36000.00, 66900.00, 'Paid', '2026-09-04 12:47:51', 'success', 'TEST-6A990A07003297885', NULL, NULL, NULL, 1, 1000, '2026-09-11', '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 452, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100236', '20260903', 'T100236', 'https://virtual.tra.go.tz/efdmsrctverify/T100236_085154', 'test_mode_mock', NULL),
('2026-09-14 13:00:10', '2026-09-14 09:03:50', 237, 'DM82681856', 3, 6, 6, 463, '255715020945', 'chizithomas@gmail.com', 'John Kilabo', '[{\"seat\":\"C1\",\"name\":\"John Kilabo\",\"phone\":\"0715020945\",\"age_group\":\"Adult\"}]', 'Male', 25, 0, 'Adult', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 34, 'Moshi mjini', 'Shekilango', '2026-09-14', 'C1', 36000.00, 40900.00, 'Paid', NULL, 'success', 'TEST-6AA78D6A59F152165', NULL, NULL, NULL, 0, 0, NULL, '', 2000.00, 900.00, 0.00, 0.00, 0.00, 2000.00, 900.00, '', 0.00, 540, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'online', NULL, 'success', '100237', '20260914', 'T100237', 'https://virtual.tra.go.tz/efdmsrctverify/T100237_090010', 'test_mode_mock', NULL),
('2026-09-14 16:00:51', '2026-09-14 15:41:27', 238, 'PH43046616', 3, 6, 6, 463, '255715020945', 'chizithomas@gmail.com', 'Rhoda Peter', '[{\"seat\":\"D1\",\"name\":\"Rhoda Peter\",\"phone\":\"0715020945\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 1, 12500, 'Extra laggage', 10.00, 5.00, NULL, NULL, NULL, -12500.00, 'overestimated', 'ready', 'PH4304XLUGREF378546', 'refunded', '2026-09-14 16:30:12', 20, NULL, NULL, NULL, NULL, 34, 'Kiboriloni', 'Bunju', '2026-09-14', 'D1', 36000.00, 53400.00, 'Paid', NULL, 'success', 'TEST-6AA7B7C310F236452', NULL, NULL, NULL, 0, 0, NULL, '', 2000.00, 900.00, 0.00, 0.00, 0.00, 2000.00, 900.00, '', 0.00, 509, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'online', NULL, 'success', '100238', '20260914', 'T100238', 'https://virtual.tra.go.tz/efdmsrctverify/T100238_120051', 'test_mode_mock', NULL),
('2026-09-16 16:21:19', '2026-09-16 13:00:33', 239, 'LA98894418', 3, 6, 6, 465, '255788888999', 'chizithomas@gmail.com', 'Joyce Mangu', '[{\"seat\":\"B1\",\"name\":\"Joyce Mangu\",\"phone\":\"0788888999\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 1, 12500, 'extra kilos', 10.00, 5.00, NULL, NULL, NULL, -12500.00, 'overestimated', 'ready', 'LA9889XLUGREF552502', 'refunded', '2026-09-16 16:54:51', 20, NULL, NULL, NULL, NULL, NULL, 'Kia', 'Shekilango', '2026-09-16', 'B1', 36000.00, 53400.00, 'Paid', NULL, 'success', 'TEST-6AAA5F8F6CCBE5940', NULL, NULL, NULL, 0, 0, NULL, '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 452, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100239', '20260916', 'T100239', 'https://virtual.tra.go.tz/efdmsrctverify/T100239_122120', 'test_mode_mock', NULL),
('2026-09-17 16:39:18', '2026-09-17 12:54:10', 240, 'MZ76668262', 3, 6, 6, 469, '255755555666', 'chizithomas@gmail.com', 'Domina Albert Mabena', '[{\"seat\":\"B3\",\"name\":\"Domina Albert Mabena\",\"phone\":\"0755555666\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 1, 25000, 'with excess luggage', 10.00, 8.00, NULL, NULL, NULL, -5000.00, 'overestimated', 'ready', NULL, 'refund_noted', '2026-09-17 16:54:10', 20, NULL, NULL, NULL, NULL, 34, 'Moshi mjini', 'Dar es salaam', '2026-09-20', 'B3', 36000.00, 66300.00, 'Paid', NULL, 'success', 'TEST-6AABB546DD2FC9341', NULL, NULL, NULL, 1, 400, '2026-09-21', '', 2000.00, 900.00, 0.00, 0.00, 0.00, 2000.00, 900.00, '', 0.00, 547, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'online', NULL, 'success', '100240', '20260917', 'T100240', 'https://virtual.tra.go.tz/efdmsrctverify/T100240_123919', 'test_mode_mock', NULL),
('2026-09-19 11:47:11', '2026-09-19 08:06:34', 241, 'LD41269645', 3, 6, 6, 469, '255655565176', 'customer-bus@hisgc.co.tz', 'Aneth Jason Makota', '[{\"seat\":\"B2\",\"name\":\"Aneth Jason Makota\",\"phone\":\"0655565176\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 1, 30000, 'Sanduku kubwa sana', 10.00, 12.00, NULL, NULL, NULL, 5000.00, 'underestimated', 'ready', 'TESTXLUG241794394', 'paid', '2026-09-19 12:00:22', 20, NULL, NULL, NULL, NULL, 34, 'Njiapanda ya Himo', 'Bunju', '2026-09-20', 'B2', 32400.00, 67020.00, 'Paid', NULL, 'success', 'TEST-6AAE13CF2EF373975', NULL, NULL, NULL, 1, 200, '2026-09-20', '', 1800.00, 820.00, 0.00, 0.00, 0.00, 1800.00, 820.00, '9705', 4000.00, 626, 36000.00, 0.00, 0.00, 0.00, 'test_mode', 'online', NULL, 'success', '100241', '20260919', 'T100241', 'https://virtual.tra.go.tz/efdmsrctverify/T100241_074711', 'test_mode_mock', NULL),
('2026-09-20 18:02:17', '2026-09-20 14:24:27', 242, 'DL04227794', 3, 6, 6, 471, '255715020945', 'chizithomas@gmail.com', 'Rebeka Kandambili', '[{\"seat\":\"A1\",\"name\":\"Rebeka Kandambili\",\"phone\":\"0715020945\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 1, 50000, 'Uzito umezidi kwenye mzigo wangu', 20.00, 15.00, NULL, NULL, NULL, -12500.00, 'overestimated', 'ready', 'DL0422XLUGREF903467', 'refund_pending', '2026-09-20 18:23:46', 20, NULL, NULL, NULL, NULL, NULL, 'Kiboriloni', 'Mbezi Africana', '2026-09-22', 'A1', 36000.00, 91900.00, 'Paid', NULL, 'success', 'TEST-6AAFBD3963CC24183', NULL, NULL, NULL, 1, 1000, '2026-09-26', '', 2000.00, 900.00, 0.00, 0.00, 0.00, 2000.00, 900.00, '', 0.00, 626, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'online', NULL, 'success', '100242', '20260920', 'T100242', 'https://virtual.tra.go.tz/efdmsrctverify/T100242_140217', 'test_mode_mock', NULL),
('2026-09-21 12:02:47', '2026-09-21 08:36:39', 243, 'OC55008115', 3, 6, 6, 471, '255655556556', NULL, 'Kisa Mwaisoba', '[{\"seat\":\"B1\",\"name\":\"Kisa Mwaisoba\",\"phone\":\"0655556556\",\"age_group\":\"Adult\"}]', 'Male', 25, 1, 'Adult', 1, 20000, 'nina mzigo uliozidi kilo 20', 8.00, 6.00, NULL, NULL, NULL, -5000.00, 'overestimated', 'ready', 'OC5500XLUGREF968999', 'refund_pending', '2026-09-21 12:32:28', 20, NULL, NULL, NULL, NULL, NULL, 'Kia', 'Mbezi magufuli', '2026-09-22', 'B1', 36000.00, 61900.00, 'Paid', NULL, 'success', 'TEST-6AB0BA7719BCC9715', NULL, NULL, NULL, 1, 1000, '2026-09-26', '36', 1800.00, 810.00, 200.00, 90.00, 0.00, 2000.00, 900.00, '', 0.00, 626, 40000.00, 0.00, 0.00, 0.00, 'test_mode', 'in_person', NULL, 'success', '100243', '20260921', 'T100243', 'https://virtual.tra.go.tz/efdmsrctverify/T100243_080247', 'test_mode_mock', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `id` int(11) NOT NULL,
  `campany_id` int(11) DEFAULT NULL,
  `bus_number` varchar(255) NOT NULL,
  `route_id` int(11) DEFAULT NULL,
  `bus_features` text DEFAULT NULL,
  `bus_type` int(11) NOT NULL,
  `total_seats` int(11) NOT NULL,
  `accept_parcels` tinyint(1) NOT NULL DEFAULT 1,
  `max_parcel_weight_kg` decimal(10,2) DEFAULT NULL,
  `conductor` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `driver_contact` varchar(255) DEFAULT NULL,
  `driver_name_2` varchar(255) DEFAULT NULL,
  `driver_contact_2` varchar(255) DEFAULT NULL,
  `conductor_name` varchar(255) DEFAULT NULL,
  `customer_service_name_1` varchar(255) DEFAULT NULL,
  `customer_service_contact_1` varchar(255) DEFAULT NULL,
  `customer_service_name_2` varchar(255) DEFAULT NULL,
  `customer_service_contact_2` varchar(255) DEFAULT NULL,
  `bus_model` varchar(255) DEFAULT NULL,
  `seate_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`seate_json`)),
  `customer_service_name_3` varchar(255) DEFAULT NULL,
  `customer_service_contact_3` varchar(255) DEFAULT NULL,
  `customer_service_name_4` varchar(255) DEFAULT NULL,
  `customer_service_contact_4` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`id`, `campany_id`, `bus_number`, `route_id`, `bus_features`, `bus_type`, `total_seats`, `accept_parcels`, `max_parcel_weight_kg`, `conductor`, `created_at`, `updated_at`, `driver_name`, `driver_contact`, `driver_name_2`, `driver_contact_2`, `conductor_name`, `customer_service_name_1`, `customer_service_contact_1`, `customer_service_name_2`, `customer_service_contact_2`, `bus_model`, `seate_json`, `customer_service_name_3`, `customer_service_contact_3`, `customer_service_name_4`, `customer_service_contact_4`) VALUES
(8, 4, 'T001AAA', NULL, NULL, 10, 40, 1, NULL, '255786948007', '2026-07-17 19:14:09', '2026-07-22 11:02:07', 'Mpogole Driver', '0786948007', NULL, NULL, 'Kevoo Conductor', 'Gabriel', '0786948007', 'Joyney', '0786948007', 'YTONG C12', '{\"id\":null,\"name\":\"Untitled Layout\",\"rows\":10,\"cols\":4,\"aisles\":[],\"seats\":[{\"id\":\"f7b40362-87a8-4bf7-967c-e69bd839739b\",\"label\":\"A1\",\"row\":1,\"col\":1},{\"id\":\"e128225f-371a-4c84-95d9-4bcc322824e6\",\"label\":\"A2\",\"row\":1,\"col\":2},{\"id\":\"b3813e03-727d-4cc1-80a1-a109db5a2c53\",\"label\":\"A3\",\"row\":1,\"col\":3},{\"id\":\"62a966b4-2f05-47f0-b0c7-b4f0302d44dd\",\"label\":\"A4\",\"row\":1,\"col\":4},{\"id\":\"62739c3b-6467-4b37-84a2-65f79eb039e7\",\"label\":\"A5\",\"row\":2,\"col\":1},{\"id\":\"4cf5099e-c945-4f9a-86d8-dfb0af5bc232\",\"label\":\"A6\",\"row\":2,\"col\":2},{\"id\":\"91dface8-b03c-4b4a-8381-df626327e17c\",\"label\":\"A7\",\"row\":2,\"col\":3},{\"id\":\"ee929be8-979f-4295-b35f-bec547d898cd\",\"label\":\"A8\",\"row\":2,\"col\":4},{\"id\":\"02127b07-8f68-46cf-8980-436cfca3d9cb\",\"label\":\"A9\",\"row\":3,\"col\":1},{\"id\":\"743d6c55-ac0c-4a61-a878-eda0bd302e1b\",\"label\":\"A10\",\"row\":3,\"col\":2},{\"id\":\"059a6221-6aae-45ff-8fbb-5026411a340e\",\"label\":\"A11\",\"row\":3,\"col\":3},{\"id\":\"b806ead6-0cb3-488e-b072-64cb5ea0d2d7\",\"label\":\"A12\",\"row\":3,\"col\":4},{\"id\":\"88d7bd12-7b79-4d25-ba78-b48cd21247ad\",\"label\":\"A13\",\"row\":4,\"col\":1},{\"id\":\"7227e729-946b-4663-b690-bb9b874bdfb1\",\"label\":\"A14\",\"row\":4,\"col\":2},{\"id\":\"6af3d590-75ca-40e2-9afe-1a5eab99b6c1\",\"label\":\"A15\",\"row\":4,\"col\":3},{\"id\":\"d917c67c-3782-418b-b373-4342f143b3f4\",\"label\":\"A16\",\"row\":4,\"col\":4},{\"id\":\"8174bf52-6c77-42c9-81d5-2061025a51ec\",\"label\":\"A17\",\"row\":5,\"col\":1},{\"id\":\"e97ec993-1dc5-4e7d-b434-eed098e7e26a\",\"label\":\"A18\",\"row\":5,\"col\":2},{\"id\":\"e60f89ec-75a0-425b-890b-ea57b61838ee\",\"label\":\"A19\",\"row\":5,\"col\":3},{\"id\":\"27c992ae-5d3d-4724-9d68-76df648a26fe\",\"label\":\"A20\",\"row\":5,\"col\":4},{\"id\":\"382e4680-b90b-4068-9291-d6f22ea3da36\",\"label\":\"A21\",\"row\":6,\"col\":1},{\"id\":\"a624b9fb-f9e6-4e1d-ad21-66e29c365bdf\",\"label\":\"A22\",\"row\":6,\"col\":2},{\"id\":\"2fdcfc25-b274-4061-9ffe-eb529b7dd05f\",\"label\":\"A23\",\"row\":6,\"col\":3},{\"id\":\"0232c530-c8e4-4782-a66e-53820d22d836\",\"label\":\"A24\",\"row\":6,\"col\":4},{\"id\":\"ec7e85c7-350f-4739-bcdd-bb9c1eaf9d3d\",\"label\":\"A25\",\"row\":7,\"col\":1},{\"id\":\"d0e70193-d6b2-4f07-9781-5f14eefe768f\",\"label\":\"A26\",\"row\":7,\"col\":2},{\"id\":\"f34650c1-239b-436c-b3e6-810e87828a9b\",\"label\":\"A27\",\"row\":7,\"col\":3},{\"id\":\"ae41bed4-b6b4-4ebf-9706-bcb547f9d870\",\"label\":\"A28\",\"row\":7,\"col\":4},{\"id\":\"e3eb35f0-c9cc-4247-9381-1866b8bef2da\",\"label\":\"A29\",\"row\":8,\"col\":1},{\"id\":\"022b1d5e-8dfc-4d27-b2c7-fe61775f10bc\",\"label\":\"A30\",\"row\":8,\"col\":2},{\"id\":\"5b1260a3-fec5-4e0f-808e-250da123c665\",\"label\":\"A31\",\"row\":8,\"col\":3},{\"id\":\"f9676988-ad69-4221-8afa-30fdb10497ee\",\"label\":\"A32\",\"row\":8,\"col\":4},{\"id\":\"45d36451-2c73-4831-997f-c81d88fa2a78\",\"label\":\"A33\",\"row\":9,\"col\":1},{\"id\":\"af0517a3-cbca-4c5d-887f-fe8bac2a127d\",\"label\":\"A34\",\"row\":9,\"col\":2},{\"id\":\"1e0fc6a3-011d-403a-ad82-36512f3f6a83\",\"label\":\"A35\",\"row\":9,\"col\":3},{\"id\":\"fd38dc1e-3120-4cb7-9879-1471aaf09282\",\"label\":\"A36\",\"row\":9,\"col\":4},{\"id\":\"5bbac908-043f-486c-ae38-3665724d6ad6\",\"label\":\"A37\",\"row\":10,\"col\":1},{\"id\":\"fe213db0-dae5-40ce-b885-8696d2cc9dcd\",\"label\":\"A38\",\"row\":10,\"col\":2},{\"id\":\"30d209df-019a-4d03-bd3c-e67d335a19f0\",\"label\":\"A39\",\"row\":10,\"col\":3},{\"id\":\"ceb41bd9-03ae-4ef3-a436-d1f880984b16\",\"label\":\"A40\",\"row\":10,\"col\":4}]}', 'NONE', '0000000000', 'NONE', '0000000000'),
(7, 3, 'T 777 EMM', NULL, NULL, 20, 49, 1, NULL, '+255753020945', '2026-07-17 13:15:42', '2026-09-15 14:16:34', 'Johnson Gabba', '0628042409', NULL, NULL, 'Salim juma', 'Stella Yohana', '0684123433', NULL, NULL, 'yutong D14', '{\"id\":null,\"name\":\"2 by 2 Coach\",\"rows\":13,\"cols\":5,\"aisles\":[{\"row\":1,\"col\":3},{\"row\":2,\"col\":3},{\"row\":3,\"col\":3},{\"row\":4,\"col\":3},{\"row\":5,\"col\":3},{\"row\":6,\"col\":3},{\"row\":7,\"col\":3},{\"row\":8,\"col\":3},{\"row\":9,\"col\":3},{\"row\":10,\"col\":3},{\"row\":11,\"col\":3},{\"row\":12,\"col\":3},{\"row\":7,\"col\":2},{\"row\":7,\"col\":1},{\"row\":8,\"col\":2},{\"row\":8,\"col\":1}],\"seats\":[{\"id\":\"8ed69c64-ab42-4ee8-b0fa-07cf0f0bebe7\",\"label\":\"A1\",\"row\":1,\"col\":1},{\"id\":\"1ed40459-d423-49d4-98d8-79ed05b4390d\",\"label\":\"A2\",\"row\":1,\"col\":2},{\"id\":\"40778f3c-56e3-40f8-bc14-16480b70d420\",\"label\":\"A3\",\"row\":1,\"col\":4},{\"id\":\"7bf34c9e-4af1-4691-8414-df97396e779a\",\"label\":\"A4\",\"row\":1,\"col\":5},{\"id\":\"1425b8c1-cbe2-4fd4-9d6e-55dbd30577a1\",\"label\":\"A5\",\"row\":2,\"col\":1},{\"id\":\"cb1c0f7f-37ce-4dc3-b558-e626ba1b33f6\",\"label\":\"A6\",\"row\":2,\"col\":2},{\"id\":\"9789da73-448f-4c84-9034-29e238f46c34\",\"label\":\"A7\",\"row\":2,\"col\":4},{\"id\":\"f84f2487-37bb-46b1-b9d5-de855db8d677\",\"label\":\"A8\",\"row\":2,\"col\":5},{\"id\":\"ca05b1c4-4683-4934-93e2-a16b0e841be3\",\"label\":\"A9\",\"row\":3,\"col\":1},{\"id\":\"2ba4fcc6-0f99-4682-a8d5-3de773031842\",\"label\":\"A10\",\"row\":3,\"col\":2},{\"id\":\"883d7406-8ae1-47b7-a3e5-36582b0e702a\",\"label\":\"A11\",\"row\":3,\"col\":4},{\"id\":\"c072aa60-ec5b-44ee-af57-1920c7ffa647\",\"label\":\"A12\",\"row\":3,\"col\":5},{\"id\":\"6ba5d7ab-d042-4b4c-af4c-c941e9bcf5e1\",\"label\":\"A13\",\"row\":4,\"col\":1},{\"id\":\"f4fa6306-68cc-4240-947a-484e19165914\",\"label\":\"A14\",\"row\":4,\"col\":2},{\"id\":\"746800f3-cf41-4cd4-bf7a-fef074e1ed06\",\"label\":\"A15\",\"row\":4,\"col\":4},{\"id\":\"d27aa346-2346-4b2c-8b0e-cc3ac64cbb8d\",\"label\":\"A16\",\"row\":4,\"col\":5},{\"id\":\"7132d8c0-41e4-4c0b-ad6f-ff460abec010\",\"label\":\"A17\",\"row\":5,\"col\":1},{\"id\":\"4c6e5cf6-cf90-4462-87f6-8ecda2ba3832\",\"label\":\"A18\",\"row\":5,\"col\":2},{\"id\":\"2ce9e920-031a-4f82-b36a-9e044807b3c3\",\"label\":\"A19\",\"row\":5,\"col\":4},{\"id\":\"bab01a4d-89d0-42c9-b2b3-3c22c26ef0a9\",\"label\":\"A20\",\"row\":5,\"col\":5},{\"id\":\"9db1ec2a-1471-460f-8e68-4e7d269db269\",\"label\":\"A21\",\"row\":6,\"col\":1},{\"id\":\"38dc909a-54ad-48fb-a096-5dda27537a27\",\"label\":\"A22\",\"row\":6,\"col\":2},{\"id\":\"97b941a4-6f7b-4db1-b697-9ea7b6d29b1c\",\"label\":\"A23\",\"row\":6,\"col\":4},{\"id\":\"e3e618a1-180e-4e90-ae4a-402f53e07fac\",\"label\":\"A24\",\"row\":6,\"col\":5},{\"id\":\"69a197bc-5de3-44cd-8979-08262f498b61\",\"label\":\"A25\",\"row\":7,\"col\":4},{\"id\":\"d8bc2cb3-68e3-4c39-b582-2757cadeeb52\",\"label\":\"A26\",\"row\":7,\"col\":5},{\"id\":\"1373418b-1fec-4d06-b57b-0ad42fa200fd\",\"label\":\"A27\",\"row\":8,\"col\":4},{\"id\":\"a12ef95f-c938-49fc-a422-d560a14a46b0\",\"label\":\"A28\",\"row\":8,\"col\":5},{\"id\":\"94e59074-8cb9-47de-bf31-93139b158ff3\",\"label\":\"A29\",\"row\":9,\"col\":1},{\"id\":\"2fd217c9-629c-4c4a-a012-806749843071\",\"label\":\"A30\",\"row\":9,\"col\":2},{\"id\":\"4064d198-c9b0-495c-bd0d-9bc6a8aaae6f\",\"label\":\"A31\",\"row\":9,\"col\":4},{\"id\":\"6419dda9-bdd7-406c-b8fb-92a924c9454f\",\"label\":\"A32\",\"row\":9,\"col\":5},{\"id\":\"1b495924-a073-4354-9e3b-abe762a213b9\",\"label\":\"A33\",\"row\":10,\"col\":1},{\"id\":\"2bbc7c42-32fc-42cf-8a34-ee75952cffa8\",\"label\":\"A34\",\"row\":10,\"col\":2},{\"id\":\"3d99220a-decb-45f4-ad08-a6d10c6f3afc\",\"label\":\"A35\",\"row\":10,\"col\":4},{\"id\":\"a865b179-fe91-40ef-99d7-f870a494ec65\",\"label\":\"A36\",\"row\":10,\"col\":5},{\"id\":\"24c013df-4711-4deb-93f5-9999f22f3049\",\"label\":\"A37\",\"row\":11,\"col\":1},{\"id\":\"72264715-8e58-4bb7-a524-88c33a0bd675\",\"label\":\"A38\",\"row\":11,\"col\":2},{\"id\":\"5b4731e4-874e-467f-a7bf-0ffbcb3283e1\",\"label\":\"A39\",\"row\":11,\"col\":4},{\"id\":\"4c82611a-ed60-4d4c-bf00-a6384ec4fbf8\",\"label\":\"A40\",\"row\":11,\"col\":5},{\"id\":\"fbed83eb-d61c-46dd-b749-4c67a266c55f\",\"label\":\"A41\",\"row\":12,\"col\":1},{\"id\":\"4688ceb1-ecf2-444c-a463-98140dbef082\",\"label\":\"A42\",\"row\":12,\"col\":2},{\"id\":\"9e14218b-3b78-4a63-88a8-1fc81db67808\",\"label\":\"A43\",\"row\":12,\"col\":4},{\"id\":\"57583cdb-4ad6-420e-8c25-c55d78fba41b\",\"label\":\"A44\",\"row\":12,\"col\":5},{\"id\":\"3e91af00-3d8f-433b-93e0-47a56980b562\",\"label\":\"A45\",\"row\":13,\"col\":1},{\"id\":\"befe2082-3ab1-484a-aa08-532c7a641735\",\"label\":\"A46\",\"row\":13,\"col\":2},{\"id\":\"0a494d8d-9c2c-49ba-b153-8e83b9220924\",\"label\":\"A47\",\"row\":13,\"col\":3},{\"id\":\"93c57ff5-fabf-4ec0-873e-f29ffac64f85\",\"label\":\"A48\",\"row\":13,\"col\":4},{\"id\":\"c2901d5c-366f-402a-a086-aad13ee3e534\",\"label\":\"A49\",\"row\":13,\"col\":5}]}', NULL, NULL, NULL, NULL),
(6, 3, 'T 344 DFM', NULL, NULL, 20, 49, 1, NULL, '+255753020945', '2026-07-17 13:07:22', '2026-09-14 04:13:45', 'Masabu Silayo', '0789473209', NULL, NULL, 'Misosi Milanzi', 'Salama Mahita', '0745565176', NULL, NULL, 'yutong D14', '{\"id\":null,\"name\":\"2 by 2 Coach\",\"rows\":13,\"cols\":5,\"aisles\":[{\"row\":1,\"col\":3},{\"row\":2,\"col\":3},{\"row\":3,\"col\":3},{\"row\":4,\"col\":3},{\"row\":5,\"col\":3},{\"row\":6,\"col\":3},{\"row\":7,\"col\":3},{\"row\":8,\"col\":3},{\"row\":9,\"col\":3},{\"row\":10,\"col\":3},{\"row\":11,\"col\":3},{\"row\":12,\"col\":3},{\"row\":8,\"col\":1},{\"row\":7,\"col\":1},{\"row\":7,\"col\":2},{\"row\":8,\"col\":2}],\"seats\":[{\"id\":\"e2056776-c989-4054-a2cf-5ec932b41880\",\"label\":\"A3\",\"row\":1,\"col\":1},{\"id\":\"e3d6bf73-b01d-4ddd-9c1a-08db401fb1e4\",\"label\":\"A4\",\"row\":1,\"col\":2},{\"id\":\"09b7337c-b703-45dc-b87e-25e9d73bed5b\",\"label\":\"A2\",\"row\":1,\"col\":4},{\"id\":\"2a81c890-20ae-4ab2-a65e-cdfd3f913ce9\",\"label\":\"A1\",\"row\":1,\"col\":5},{\"id\":\"6211553f-0378-4227-b013-158552b42ed0\",\"label\":\"B3\",\"row\":2,\"col\":1},{\"id\":\"2b3de2c6-bb0c-499d-bf77-0ff99dc7d232\",\"label\":\"B4\",\"row\":2,\"col\":2},{\"id\":\"7e3bb5ea-7cfe-432f-8292-08af6d1088ee\",\"label\":\"B2\",\"row\":2,\"col\":4},{\"id\":\"1c6b2ead-991c-4999-947d-a3500ec34477\",\"label\":\"B1\",\"row\":2,\"col\":5},{\"id\":\"d5487da6-fcd9-4669-9c6b-e169cd3a6e78\",\"label\":\"C3\",\"row\":3,\"col\":1},{\"id\":\"b34c6a0c-76cb-4210-8fab-83db5f15ac4d\",\"label\":\"C4\",\"row\":3,\"col\":2},{\"id\":\"20cb98f1-e111-4c84-8b81-bed236e70096\",\"label\":\"C2\",\"row\":3,\"col\":4},{\"id\":\"812ae7ae-a7b3-468e-90a7-ac79d3cdf3d7\",\"label\":\"C1\",\"row\":3,\"col\":5},{\"id\":\"47a8e542-4709-4cf1-bfc3-e4a5f79518d4\",\"label\":\"D3\",\"row\":4,\"col\":1},{\"id\":\"247e9452-c0d3-4974-ae36-845903084238\",\"label\":\"D4\",\"row\":4,\"col\":2},{\"id\":\"289a5ca3-1025-4c32-8410-f2ddb0321cf1\",\"label\":\"D2\",\"row\":4,\"col\":4},{\"id\":\"493d4fe1-928c-49f4-803f-7fb5d44f901d\",\"label\":\"D1\",\"row\":4,\"col\":5},{\"id\":\"47d0eb08-d7a1-4b69-90d2-402138852ed4\",\"label\":\"E3\",\"row\":5,\"col\":1},{\"id\":\"6cdc21ed-6885-4726-9cd8-a99e96aeb24e\",\"label\":\"E4\",\"row\":5,\"col\":2},{\"id\":\"98b38a30-cdda-4f4f-a94a-3b961812a066\",\"label\":\"E2\",\"row\":5,\"col\":4},{\"id\":\"66214acc-a4be-4a11-908d-9c51ea8f322a\",\"label\":\"E1\",\"row\":5,\"col\":5},{\"id\":\"b8fca5d8-2e05-4dee-9936-3700b5fe5c60\",\"label\":\"F3\",\"row\":6,\"col\":1},{\"id\":\"73fe0ced-2cda-4963-977a-128b50e48459\",\"label\":\"F4\",\"row\":6,\"col\":2},{\"id\":\"182107ae-a177-4a0a-8b5f-77d73ccec4c7\",\"label\":\"F2\",\"row\":6,\"col\":4},{\"id\":\"b9d57265-a4c8-4b49-a804-6bfffaa0c89d\",\"label\":\"F1\",\"row\":6,\"col\":5},{\"id\":\"9fd5b891-a0b8-4a68-ac53-223d66122500\",\"label\":\"G2\",\"row\":7,\"col\":4},{\"id\":\"b64ad0ad-4c81-42bc-a51e-cfa09972997b\",\"label\":\"G1\",\"row\":7,\"col\":5},{\"id\":\"fab2e6a5-beac-49a2-af81-8e8054afe532\",\"label\":\"H2\",\"row\":8,\"col\":4},{\"id\":\"05b1c953-8d1a-43d8-84ac-d3a595b93be0\",\"label\":\"H1\",\"row\":8,\"col\":5},{\"id\":\"b1092356-b284-4ad2-ad09-20d7559b9db9\",\"label\":\"I3\",\"row\":9,\"col\":1},{\"id\":\"56ca45c9-e41f-452e-8fad-28936a0bc28f\",\"label\":\"I4\",\"row\":9,\"col\":2},{\"id\":\"cbfc5f5c-7046-4175-8486-afbd01a3aeb5\",\"label\":\"I2\",\"row\":9,\"col\":4},{\"id\":\"839364ca-5174-492e-b92f-044426078d02\",\"label\":\"I1\",\"row\":9,\"col\":5},{\"id\":\"cfce833b-ad66-4272-b9e3-31e132b29c3d\",\"label\":\"J3\",\"row\":10,\"col\":1},{\"id\":\"7b984eda-c643-46fc-a3dc-854720bb8fb0\",\"label\":\"J4\",\"row\":10,\"col\":2},{\"id\":\"de73e7d4-facf-42b2-a638-76baf1dc1497\",\"label\":\"J2\",\"row\":10,\"col\":4},{\"id\":\"e446ba24-2931-46af-a083-4b801402f3ff\",\"label\":\"J1\",\"row\":10,\"col\":5},{\"id\":\"57392615-a770-4a9f-9786-6fa0166cfa81\",\"label\":\"K3\",\"row\":11,\"col\":1},{\"id\":\"d41a85a2-5d81-430d-ba62-ef7d7e22d87d\",\"label\":\"K4\",\"row\":11,\"col\":2},{\"id\":\"e9d41ac8-a322-452f-ada5-bdfa6d200536\",\"label\":\"K2\",\"row\":11,\"col\":4},{\"id\":\"075ddce5-8dd1-4c64-9d0a-9018c57f265c\",\"label\":\"K1\",\"row\":11,\"col\":5},{\"id\":\"64450a7a-2c01-4c29-80d9-dee25fcf80a1\",\"label\":\"L3\",\"row\":12,\"col\":1},{\"id\":\"0979943e-6427-4205-9a08-526ab89de3b0\",\"label\":\"L4\",\"row\":12,\"col\":2},{\"id\":\"706b5f78-2ed1-4735-bebe-ff3e37f336fd\",\"label\":\"L2\",\"row\":12,\"col\":4},{\"id\":\"080e2003-1400-4181-a095-ca58958412f3\",\"label\":\"L1\",\"row\":12,\"col\":5},{\"id\":\"31a5746b-735f-48f2-b609-4e7d3364dfbc\",\"label\":\"M3\",\"row\":13,\"col\":1},{\"id\":\"a822d2ba-5331-4f2b-8488-2b8f2ca9d747\",\"label\":\"M4\",\"row\":13,\"col\":2},{\"id\":\"5f6b2a44-b565-48cf-ad98-89b41fa63796\",\"label\":\"M2\",\"row\":13,\"col\":4},{\"id\":\"8106cace-b831-4be4-8428-f0e04e40d77d\",\"label\":\"M1\",\"row\":13,\"col\":5},{\"id\":\"53b8e683-f31c-4cac-b863-110cc832902e\",\"label\":\"N1\",\"row\":13,\"col\":3}]}', NULL, NULL, NULL, NULL),
(9, 4, 'T709EEB', NULL, NULL, 10, 40, 1, NULL, '255758632896', '2026-07-22 15:19:30', '2026-07-22 11:19:30', 'Daudi', '0767567899', NULL, NULL, 'Fredy', 'Fatuma', '0767686858', NULL, NULL, 'Yutong D14', '{\"id\":null,\"name\":\"Untitled Layout\",\"rows\":10,\"cols\":4,\"aisles\":[],\"seats\":[{\"id\":\"5322e17a-9100-4917-8072-504f462fb0c5\",\"label\":\"A1\",\"row\":1,\"col\":1},{\"id\":\"360c29a6-a71c-43b0-a401-8ba973398905\",\"label\":\"A2\",\"row\":1,\"col\":2},{\"id\":\"a2bcec07-e69f-4a36-907a-b47df7d4ef27\",\"label\":\"A3\",\"row\":1,\"col\":3},{\"id\":\"844b09eb-a89e-49c0-b607-1700bddf9c5f\",\"label\":\"A4\",\"row\":1,\"col\":4},{\"id\":\"99ae635b-281f-4bd4-8f62-103b27195efe\",\"label\":\"A5\",\"row\":2,\"col\":1},{\"id\":\"02de713d-aefa-4a81-bc5b-30f3f0a1b9be\",\"label\":\"A6\",\"row\":2,\"col\":2},{\"id\":\"4f771bdc-09cf-4fb0-aa21-bd4b03691a69\",\"label\":\"A7\",\"row\":2,\"col\":3},{\"id\":\"16eda8c2-5e74-42e4-adcb-aa1fae46d2bf\",\"label\":\"A8\",\"row\":2,\"col\":4},{\"id\":\"f41b200f-593a-43da-8643-7af6af24d99d\",\"label\":\"A9\",\"row\":3,\"col\":1},{\"id\":\"a9a7825b-6973-4dd3-8f6a-4cdb0999dc6d\",\"label\":\"A10\",\"row\":3,\"col\":2},{\"id\":\"ab1796ba-1d9c-4942-80be-0359040b5197\",\"label\":\"A11\",\"row\":3,\"col\":3},{\"id\":\"6429ee37-ff00-451e-8980-d7f7dc9d3b6e\",\"label\":\"A12\",\"row\":3,\"col\":4},{\"id\":\"4cfa3790-356d-4dba-90b7-bd1af63f035a\",\"label\":\"A13\",\"row\":4,\"col\":1},{\"id\":\"59f605a7-52dc-45f8-ba73-c82ebe634732\",\"label\":\"A14\",\"row\":4,\"col\":2},{\"id\":\"9995c84b-daea-4a70-a06d-24510930f779\",\"label\":\"A15\",\"row\":4,\"col\":3},{\"id\":\"a2166e67-863e-4f5b-87d4-8e773c3221ec\",\"label\":\"A16\",\"row\":4,\"col\":4},{\"id\":\"a24563d9-4b59-4536-ae20-06fb76622241\",\"label\":\"A17\",\"row\":5,\"col\":1},{\"id\":\"37b39889-aa2d-4326-92f7-904e2bf468fb\",\"label\":\"A18\",\"row\":5,\"col\":2},{\"id\":\"7f2dcb0d-a652-477c-8467-c0bb626c7bb7\",\"label\":\"A19\",\"row\":5,\"col\":3}]}', NULL, NULL, NULL, NULL),
(10, 4, 'T709EEC', NULL, NULL, 30, 40, 1, NULL, '255758632896', '2026-07-22 16:00:06', '2026-07-22 12:00:06', 'Juma ally', '0767675461', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Scanning', '{\"id\":null,\"name\":\"Untitled Layout\",\"rows\":10,\"cols\":4,\"aisles\":[],\"seats\":[{\"id\":\"b97699b6-b2aa-4415-84b9-8dd5baa09fdf\",\"label\":\"A1\",\"row\":1,\"col\":1}]}', NULL, NULL, NULL, NULL),
(11, 5, 'T 571 DPH', NULL, NULL, 10, 40, 1, NULL, '255770100100', '2026-07-31 16:28:59', '2026-08-09 14:20:44', 'Thomas Chizi', '0715553803', NULL, NULL, 'Thomas Paul', NULL, NULL, NULL, NULL, 'Yutong F14', '{\"id\":null,\"name\":\"Untitled Layout\",\"rows\":10,\"cols\":4,\"aisles\":[],\"seats\":[{\"id\":\"f32a4fbe-405b-4199-8094-bd16eb577a53\",\"label\":\"A1\",\"row\":1,\"col\":1},{\"id\":\"eea5ab59-b482-446e-b2be-789ad3645f5b\",\"label\":\"A2\",\"row\":1,\"col\":2},{\"id\":\"7ff3986b-4069-4434-a3e1-3e7275cacbf7\",\"label\":\"A3\",\"row\":1,\"col\":3},{\"id\":\"d8d28b9e-c051-48f9-b966-e42fa55ab3a3\",\"label\":\"A4\",\"row\":1,\"col\":4},{\"id\":\"99737521-b6b0-4ebb-9933-37fb8843abbd\",\"label\":\"A5\",\"row\":2,\"col\":1},{\"id\":\"928c20f8-1509-4fab-a402-219bd170ce97\",\"label\":\"A6\",\"row\":2,\"col\":2},{\"id\":\"5f005ee7-23f1-48db-ba3d-686f1995c88e\",\"label\":\"A7\",\"row\":2,\"col\":3},{\"id\":\"4deb4b8b-ba6c-4fd3-9c39-8edcca9aa56f\",\"label\":\"A8\",\"row\":2,\"col\":4},{\"id\":\"de8d8c30-b7c4-4f8c-8509-da1a8209d992\",\"label\":\"A9\",\"row\":3,\"col\":1},{\"id\":\"1f8ed700-640b-43bf-8d6c-a1ce1c7d23dc\",\"label\":\"A10\",\"row\":3,\"col\":2},{\"id\":\"8d13a22a-8dc7-491f-b352-d54852109c3d\",\"label\":\"A11\",\"row\":3,\"col\":3},{\"id\":\"c0cd34b2-f8fe-4d10-ab12-0e780e4a7df8\",\"label\":\"A12\",\"row\":3,\"col\":4},{\"id\":\"1281f839-034d-4620-9d56-bb619c2c74de\",\"label\":\"A13\",\"row\":4,\"col\":1},{\"id\":\"b7e10332-54a8-442e-b88c-77fcd478414b\",\"label\":\"A14\",\"row\":4,\"col\":2},{\"id\":\"5d85b0b4-8577-4c8b-bec7-4e0cda9d4da0\",\"label\":\"A15\",\"row\":4,\"col\":3},{\"id\":\"60e76ad8-7323-48f3-b955-1a4fe780805c\",\"label\":\"A16\",\"row\":4,\"col\":4},{\"id\":\"cddf435d-abd8-40d0-86bc-fd552e37d1c9\",\"label\":\"A17\",\"row\":5,\"col\":1},{\"id\":\"629e0337-3ede-43f9-92ea-a3518e497273\",\"label\":\"A18\",\"row\":5,\"col\":2},{\"id\":\"5389a698-4cf6-404f-a1cf-4d61924fd066\",\"label\":\"A19\",\"row\":5,\"col\":3},{\"id\":\"345d31a3-d501-4803-8a5a-3b474b50ebc3\",\"label\":\"A20\",\"row\":5,\"col\":4},{\"id\":\"c53cfc78-0b1e-4de8-ba2d-bbb284d8bf55\",\"label\":\"A21\",\"row\":6,\"col\":1},{\"id\":\"65eee1e8-7c47-41a1-8814-d6d5c3c0ef62\",\"label\":\"A22\",\"row\":6,\"col\":2},{\"id\":\"6edc00a0-68a3-4f6e-8822-5b424cc73829\",\"label\":\"A23\",\"row\":6,\"col\":3},{\"id\":\"81ba47f7-02c2-43fc-9ca2-4f3d2840438e\",\"label\":\"A24\",\"row\":6,\"col\":4},{\"id\":\"409972d4-c9d2-4eb3-b537-b0849fd626b8\",\"label\":\"A25\",\"row\":7,\"col\":1},{\"id\":\"7bad8c65-d868-4b27-8698-84d933151964\",\"label\":\"A26\",\"row\":7,\"col\":2},{\"id\":\"efa5edf1-d78c-4fb5-952a-b34e9b93512b\",\"label\":\"A27\",\"row\":7,\"col\":3},{\"id\":\"f4ad3c9d-5453-4207-a6cc-d138f9048ce9\",\"label\":\"A28\",\"row\":7,\"col\":4},{\"id\":\"59988c13-715c-4d9b-9408-ebf2c94e360a\",\"label\":\"A29\",\"row\":8,\"col\":1},{\"id\":\"9a035bb4-2b27-4073-a6ac-d12448b6b6e3\",\"label\":\"A30\",\"row\":8,\"col\":2},{\"id\":\"14048677-af74-4bdb-bd59-a734afe1fd0e\",\"label\":\"A31\",\"row\":8,\"col\":3},{\"id\":\"7a4a55e4-b50b-4740-9414-009f3d70faeb\",\"label\":\"A32\",\"row\":8,\"col\":4},{\"id\":\"1ebe4d5c-495f-4be5-89fd-0cb2a566a154\",\"label\":\"A33\",\"row\":9,\"col\":1},{\"id\":\"cb724158-2be9-4c5b-aa53-ca174deb87cc\",\"label\":\"A34\",\"row\":9,\"col\":2},{\"id\":\"0032a08c-1cc9-4916-b4bb-f7f1d00466d2\",\"label\":\"A35\",\"row\":9,\"col\":3},{\"id\":\"0230c78f-5b02-4e3c-afcc-136a12ef33e5\",\"label\":\"A36\",\"row\":9,\"col\":4},{\"id\":\"0219ebbc-0015-47c4-bfbd-590bd151b90b\",\"label\":\"A37\",\"row\":10,\"col\":1},{\"id\":\"fbf925be-13a0-4bfc-9df8-722269ea346f\",\"label\":\"A38\",\"row\":10,\"col\":2},{\"id\":\"4cd10be3-408d-43b3-8c05-ecbd1e3f79be\",\"label\":\"A39\",\"row\":10,\"col\":3},{\"id\":\"9b80264d-cd5c-499b-b1d2-b37a5926edeb\",\"label\":\"A40\",\"row\":10,\"col\":4}]}', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bus_owner_account`
--

CREATE TABLE `bus_owner_account` (
  `id` int(11) NOT NULL,
  `campany_id` int(11) DEFAULT NULL,
  `registration_number` varchar(255) DEFAULT NULL,
  `tin` varchar(255) DEFAULT NULL,
  `vrn` varchar(255) DEFAULT NULL,
  `office_number` varchar(255) DEFAULT NULL,
  `box` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `town` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `bank_number` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `whatsapp_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bus_owner_account`
--

INSERT INTO `bus_owner_account` (`id`, `campany_id`, `registration_number`, `tin`, `vrn`, `office_number`, `box`, `street`, `town`, `city`, `region`, `country`, `bank_number`, `bank_name`, `whatsapp_number`, `created_at`, `updated_at`) VALUES
(2, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-07-17 15:30:53', '2026-07-17 11:30:53'),
(3, 5, '901ABS900', '1234567', 'A09876543', '0765553953', '90100', 'Ubungo', 'Dar Es Salaam', 'Dar Es Salaam', 'Dar Es Salaam', NULL, '2261778261', 'NMB', '0715553803', '2026-07-31 19:26:03', '2026-07-31 15:26:03');

-- --------------------------------------------------------

--
-- Table structure for table `campanies`
--

CREATE TABLE `campanies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `payment_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT 0,
  `percentage` int(11) DEFAULT 0,
  `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `campanies`
--

INSERT INTO `campanies` (`id`, `name`, `user_id`, `payment_number`, `created_at`, `updated_at`, `status`, `percentage`, `commission_amount`) VALUES
(4, 'Mpogole', 23, '0786948007', '2026-07-17 15:29:05', '2026-07-22 15:21:01', 1, 0, 3000.00),
(3, 'Thomas Express', 20, '0715020945', '2026-07-17 03:14:33', '2026-07-17 10:35:42', 1, 5, 0.00),
(5, 'Bish Bus', 46, '0715553803', '2026-07-31 15:47:05', '2026-07-31 12:05:20', 1, 5, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `cancelled_bookings`
--

CREATE TABLE `cancelled_bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `campany_id` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `name`, `created_at`, `updated_at`) VALUES
(5, 'Dodoma', '2026-07-17 11:26:53', '2026-07-17 07:26:53'),
(4, 'Dar es salaam', '2026-07-17 11:26:32', '2026-07-17 07:26:32'),
(6, 'Mbeya', '2026-07-18 06:48:56', '2026-07-18 02:48:56'),
(7, 'Arusha', '2026-07-18 06:49:50', '2026-07-18 02:49:50'),
(8, 'Kilimanjaro', '2026-07-18 06:50:22', '2026-07-18 02:50:22'),
(9, 'Mwanza', '2026-07-18 06:50:48', '2026-07-18 02:50:48'),
(10, 'Mtwara', '2026-07-18 06:51:14', '2026-07-18 02:51:14'),
(11, 'Lindi', '2026-07-18 06:51:48', '2026-07-18 02:51:48'),
(12, 'Kigoma', '2026-07-18 06:52:28', '2026-07-18 02:52:28'),
(13, 'Njombe', '2026-07-18 06:52:50', '2026-07-18 02:52:50'),
(14, 'Kahama', '2026-07-18 06:54:22', '2026-07-18 02:54:22'),
(15, 'Tanga', '2026-07-18 06:56:55', '2026-07-18 02:56:55'),
(16, 'Morogoro', '2026-07-18 06:57:17', '2026-07-18 02:57:17'),
(17, 'Singida', '2026-07-18 06:58:08', '2026-07-18 02:58:08'),
(18, 'Iringa', '2026-07-18 06:59:16', '2026-07-18 02:59:16'),
(19, 'Masasi', '2026-07-18 07:02:05', '2026-07-18 03:02:05'),
(20, 'Nachingwea', '2026-07-18 07:02:53', '2026-07-18 03:02:53'),
(21, 'Tabora', '2026-07-22 16:01:26', '2026-07-22 12:01:26'),
(22, 'Songea', '2026-07-22 16:02:01', '2026-07-22 12:02:01'),
(23, 'Bukoba', '2026-07-22 16:02:18', '2026-07-22 12:02:18'),
(24, 'Nairobi', '2026-07-26 08:08:05', '2026-07-26 04:08:05'),
(25, 'Mnazi mmoja', '2026-08-25 08:21:27', '2026-08-25 04:21:27'),
(26, 'Gongo la mboto', '2026-08-25 08:22:06', '2026-08-25 04:22:06');

-- --------------------------------------------------------

--
-- Table structure for table `coasters`
--

CREATE TABLE `coasters` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `driver_user_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_user_id` int(11) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 30,
  `model` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `status` enum('available','on_hire','maintenance') NOT NULL DEFAULT 'available',
  `image` varchar(255) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_contact` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `last_location_update` timestamp NULL DEFAULT NULL,
  `features` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coasters`
--

INSERT INTO `coasters` (`id`, `user_id`, `driver_user_id`, `customer_user_id`, `name`, `plate_number`, `capacity`, `model`, `color`, `status`, `image`, `driver_name`, `driver_contact`, `latitude`, `longitude`, `last_location_update`, `features`, `created_at`, `updated_at`) VALUES
(1, 18, 19, NULL, 'msangi', 'T 514 ECH', 30, 'A04', 'gray', 'available', 'coasters/MFpcuJKCE54mR4P5mHIAr70n99N6SPU9n8dTEZSr.jpg', 'ibrahim', '0628042409', -6.90836700, 39.19113560, '2026-08-21 18:02:29', 'iednihjwb h', '2026-07-17 02:22:40', '2026-08-21 18:02:29'),
(2, 25, 26, NULL, 'Thomas minibus services', 'T 257 UTT', 30, 'Toyota Coaster 2025', 'White', 'available', 'coasters/IOX0oDrTwoJK7XRom43TFkEqS266vJLDYVmMRDX4.jpg', 'Masabu Silayo', '0628042409', NULL, NULL, NULL, 'Free WiFi,', '2026-07-17 19:16:44', '2026-07-17 19:16:44'),
(3, 24, 28, NULL, 'Mligo', 'T010', 30, 'Toyota Coaster', 'Black', 'available', 'coasters/k67KolT46gAJE5zdmzriFfm8Ql3VflJeDg6DdhYE.png', 'John Doe', '0786948007', NULL, NULL, NULL, 'Ac, tv, azam, charging', '2026-07-17 19:31:09', '2026-07-17 19:31:09'),
(4, 29, 30, NULL, 'Kidinilo', 'T 571 DPA', 30, 'Toyota Coaster 2018', 'Blue', 'available', 'coasters/bH1FHZWSSNIxGwnYt3AT1gqwa6jns2z0KgXiqcdq.png', 'Thomas Chizi', '0715553803', -6.78275310, 39.25957460, '2026-07-18 23:35:58', 'Wifi', '2026-07-17 20:16:54', '2026-07-18 23:35:58'),
(5, 29, 31, NULL, 'Golden Deer', 'T 571 DPH', 30, 'Toyota Coaster 2018', 'Black', 'available', NULL, 'Alnas Abdul', '+255765553953', NULL, NULL, NULL, 'TV', '2026-07-17 20:39:28', '2026-07-17 20:39:55'),
(6, 22, 37, NULL, 'Sumaye 2026', 'T435DUU', 30, 'Toyota coast 2005', 'White', 'available', NULL, 'Daniel john', '0758523652', NULL, NULL, NULL, 'Air condition, wifi,', '2026-07-19 20:32:46', '2026-07-22 14:40:13'),
(7, 22, 39, NULL, 'Sumaye ten', 'T567DHG', 30, 'Toyota coast 2005', 'Black', 'available', NULL, 'Juma ally', '0758523652', NULL, NULL, NULL, 'Air condition, wifi,', '2026-07-19 20:33:08', '2026-07-22 14:39:46'),
(8, 22, 38, NULL, 'Sumaye two', 'T567EBH', 30, 'Toyota coast 2012', 'White', 'available', NULL, 'Fredy kelvin', '0767567899', NULL, NULL, NULL, 'Air conditioner, wifi, Tv', '2026-07-19 22:32:05', '2026-07-22 14:39:17'),
(9, 22, 40, NULL, 'Sumaye one', 'T699EHK', 30, 'Toyota coast 2023', 'Black', 'available', NULL, 'Saidi  david', '0678899345', NULL, NULL, NULL, 'Air condition,Wifi, TV', '2026-07-20 14:06:52', '2026-07-22 14:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `device_tokens`
--

CREATE TABLE `device_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(512) NOT NULL,
  `platform` varchar(20) NOT NULL DEFAULT 'android',
  `app` varchar(40) DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `device_tokens`
--

INSERT INTO `device_tokens` (`id`, `user_id`, `token`, `platform`, `app`, `last_used_at`, `created_at`, `updated_at`) VALUES
(1, 19, 'f6X5HC65QPqdv0zKLeV3dh:APA91bGSbPpswq1J8k85IpCa7CbFLUQ4hgnHzpOM4N7JoaHHCl1Cv-7i6p7icas59E8NEnCJ3S_nCnji849GR_ioB6VYXByDRxYzI-2WfBYAz7lgAEPGofM', 'android', 'bushire_driver', '2026-07-18 01:33:42', '2026-07-17 23:15:08', '2026-07-18 01:33:42'),
(2, 19, 'dj5ULReyQO6623sxib0H_0:APA91bHmq41IxgVGruPcYMyOnJsOfdp7tKC2bkQh8YPcD8G34CUOl1fIRRgEoCsAHNoDe5tLp_UZZbfBcMl5ej6_apjE-8EI9hbXIxdnIQbbFnXqSwjmYw8', 'android', 'bushire_driver', '2026-08-21 17:58:20', '2026-07-18 02:02:27', '2026-08-21 17:58:20'),
(4, 30, 'dPev9lQDQMyKgBCdJuqXMy:APA91bGdLTcaIpoZz0TFuNBAdurzlJyFmeHpLYwfAkC7pFRlrAqc9ribc9NopRUL_x-gWe-mMRVjayDDN5Wje_Js5pQ6JI_VAvovmfFwx6BgI-mv8IKGr4I', 'android', 'bushire_driver', '2026-07-18 03:33:36', '2026-07-18 03:33:36', '2026-07-18 03:33:36');

-- --------------------------------------------------------

--
-- Table structure for table `discount`
--

CREATE TABLE `discount` (
  `id` int(11) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `used` int(11) DEFAULT NULL,
  `percentage` int(11) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `applies_to_ticket` tinyint(1) NOT NULL DEFAULT 1,
  `applies_to_luggage` tinyint(1) NOT NULL DEFAULT 0,
  `applies_to_parcel` tinyint(1) NOT NULL DEFAULT 0,
  `applies_to_special_hire` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `discount`
--

INSERT INTO `discount` (`id`, `code`, `used`, `percentage`, `expires_at`, `applies_to_ticket`, `applies_to_luggage`, `applies_to_parcel`, `applies_to_special_hire`, `created_at`, `updated_at`) VALUES
(1, '9705', 5, 10, NULL, 1, 0, 0, 0, '2026-08-20 16:11:16', '2026-08-20 12:11:16'),
(2, '1234', 3, 10, NULL, 1, 0, 0, 0, '2026-08-30 16:54:33', '2026-08-30 12:54:33');

-- --------------------------------------------------------

--
-- Table structure for table `excess_luggage_escrow`
--

CREATE TABLE `excess_luggage_escrow` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `booking_code` varchar(64) NOT NULL,
  `estimated_weight` decimal(10,2) DEFAULT NULL,
  `estimated_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `held_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `actual_weight` decimal(10,2) DEFAULT NULL,
  `actual_fee` decimal(14,2) DEFAULT NULL,
  `delta_amount` decimal(14,2) DEFAULT NULL,
  `weight_verdict` varchar(32) DEFAULT NULL,
  `released_fee` decimal(14,2) DEFAULT NULL,
  `surplus_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `admin_share` decimal(14,2) NOT NULL DEFAULT 0.00,
  `government_share` decimal(14,2) NOT NULL DEFAULT 0.00,
  `owner_share` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` varchar(32) NOT NULL DEFAULT 'held',
  `refund_amount` decimal(14,2) DEFAULT NULL,
  `weighed_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `refund_requested_at` timestamp NULL DEFAULT NULL,
  `refund_approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `excess_luggage_escrow`
--

INSERT INTO `excess_luggage_escrow` (`id`, `booking_id`, `booking_code`, `estimated_weight`, `estimated_fee`, `held_amount`, `actual_weight`, `actual_fee`, `delta_amount`, `weight_verdict`, `released_fee`, `surplus_amount`, `admin_share`, `government_share`, `owner_share`, `status`, `refund_amount`, `weighed_at`, `released_at`, `refund_requested_at`, `refund_approved_at`, `created_at`, `updated_at`) VALUES
(29, 219, 'SH42945199', 10.00, 25000.00, 25000.00, NULL, 25000.00, 0.00, 'correct', 25000.00, 0.00, 1250.00, 1250.00, 22500.00, 'released', NULL, '2026-08-16 22:34:03', '2026-08-16 22:34:03', NULL, NULL, '2026-08-16 22:30:33', '2026-08-16 22:34:03'),
(30, 220, 'AY26333944', 7.00, 17500.00, 25000.00, 10.00, 25000.00, 7500.00, 'underestimated', 25000.00, 0.00, 1250.00, 1250.00, 22500.00, 'released', NULL, '2026-08-16 22:45:21', '2026-08-16 22:46:11', NULL, NULL, '2026-08-16 22:42:23', '2026-08-16 22:46:11'),
(31, 221, 'RE45390407', 10.00, 25000.00, 17500.00, 7.00, 17500.00, -7500.00, 'overestimated', 17500.00, 0.00, 875.00, 875.00, 15750.00, 'refunded', 7500.00, '2026-08-16 23:21:37', '2026-08-16 23:21:37', '2026-08-16 23:23:06', '2026-08-16 23:33:05', '2026-08-16 23:18:45', '2026-08-16 23:33:05'),
(32, 229, 'LB02341704', 10.00, 25000.00, 25000.00, NULL, 25000.00, 0.00, 'correct', 25000.00, 0.00, 1250.00, 1250.00, 22500.00, 'released', NULL, '2026-08-27 03:02:40', '2026-08-27 03:02:40', NULL, NULL, '2026-08-27 00:18:50', '2026-08-27 03:02:40'),
(33, 230, 'NA86148158', 20.00, 50000.00, 75000.00, 30.00, 75000.00, 25000.00, 'underestimated', 75000.00, 0.00, 3750.00, 3750.00, 67500.00, 'released', NULL, '2026-08-27 20:30:31', '2026-08-27 20:34:31', NULL, NULL, '2026-08-27 16:39:56', '2026-08-27 20:34:31'),
(34, 231, 'MF61171230', 20.00, 50000.00, 50000.00, 15.00, 37500.00, -12500.00, 'overestimated', 37500.00, 12500.00, 1875.00, 1875.00, 33750.00, 'surplus_held', NULL, '2026-08-31 02:19:36', '2026-08-31 02:19:36', NULL, NULL, '2026-08-31 02:16:19', '2026-08-31 02:19:36'),
(35, 232, 'UW91157873', 10.00, 25000.00, 25000.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 22500.00, 'held', NULL, NULL, NULL, NULL, NULL, '2026-08-31 03:24:46', '2026-08-31 03:24:46'),
(36, 234, 'UA35686570', 12.00, 30000.00, 30000.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 27000.00, 'held', NULL, NULL, NULL, NULL, NULL, '2026-09-01 13:50:16', '2026-09-01 13:50:16'),
(37, 235, 'XC15355170', 10.00, 25000.00, 25000.00, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 22500.00, 'held', NULL, NULL, NULL, NULL, NULL, '2026-09-01 13:55:46', '2026-09-01 13:55:46'),
(38, 236, 'OB39756277', 5.00, 12500.00, 25000.00, 10.00, 25000.00, 12500.00, 'underestimated', 25000.00, 0.00, 1250.00, 1250.00, 22500.00, 'released', NULL, '2026-09-03 15:23:25', '2026-09-03 15:23:48', NULL, NULL, '2026-09-03 12:51:54', '2026-09-03 15:23:48'),
(39, 238, 'PH43046616', 10.00, 25000.00, 12500.00, 5.00, 12500.00, -12500.00, 'overestimated', 12500.00, 0.00, 625.00, 625.00, 11250.00, 'refunded', 12500.00, '2026-09-14 16:30:12', '2026-09-14 16:30:12', '2026-09-14 16:35:46', '2026-09-14 19:41:27', '2026-09-14 16:00:51', '2026-09-14 19:41:27'),
(40, 239, 'LA98894418', 10.00, 25000.00, 12500.00, 5.00, 12500.00, -12500.00, 'overestimated', 12500.00, 0.00, 625.00, 625.00, 11250.00, 'refunded', 12500.00, '2026-09-16 16:54:51', '2026-09-16 16:54:51', '2026-09-16 16:55:02', '2026-09-16 17:00:33', '2026-09-16 16:21:19', '2026-09-16 17:00:33'),
(41, 240, 'MZ76668262', 10.00, 25000.00, 25000.00, 8.00, 20000.00, -5000.00, 'overestimated', 20000.00, 5000.00, 1000.00, 1000.00, 18000.00, 'surplus_held', NULL, '2026-09-17 16:54:10', '2026-09-17 16:54:10', NULL, NULL, '2026-09-17 16:39:19', '2026-09-17 16:54:10'),
(42, 241, 'LD41269645', 10.00, 25000.00, 30000.00, 12.00, 30000.00, 5000.00, 'underestimated', 30000.00, 0.00, 1500.00, 1500.00, 27000.00, 'released', NULL, '2026-09-19 12:00:22', '2026-09-19 12:06:34', NULL, NULL, '2026-09-19 11:47:11', '2026-09-19 12:06:34'),
(43, 242, 'DL04227794', 20.00, 50000.00, 50000.00, 15.00, 37500.00, -12500.00, 'overestimated', 37500.00, 12500.00, 1875.00, 1875.00, 33750.00, 'refund_pending', 12500.00, '2026-09-20 18:23:46', '2026-09-20 18:23:46', '2026-09-20 18:24:27', NULL, '2026-09-20 18:02:17', '2026-09-20 18:24:27'),
(44, 243, 'OC55008115', 8.00, 20000.00, 20000.00, 6.00, 15000.00, -5000.00, 'overestimated', 15000.00, 5000.00, 750.00, 750.00, 13500.00, 'refund_pending', 5000.00, '2026-09-21 12:32:28', '2026-09-21 12:32:28', '2026-09-21 12:36:39', NULL, '2026-09-21 12:02:47', '2026-09-21 12:36:39');

-- --------------------------------------------------------

--
-- Table structure for table `excess_luggage_escrow_transactions`
--

CREATE TABLE `excess_luggage_escrow_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `escrow_id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(32) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `excess_luggage_escrow_transactions`
--

INSERT INTO `excess_luggage_escrow_transactions` (`id`, `escrow_id`, `booking_id`, `type`, `amount`, `reference`, `meta`, `created_at`, `updated_at`) VALUES
(61, 29, 219, 'deposit', 25000.00, 'SH42945199', '{\"source\":\"booking_settlement\"}', '2026-08-16 22:30:33', '2026-08-16 22:30:33'),
(62, 29, 219, 'release_owner', 22500.00, NULL, '{\"target_owner_share\":22500,\"delta\":22500}', '2026-08-16 22:30:33', '2026-08-16 22:30:33'),
(63, 29, 219, 'release_admin', 1250.00, NULL, '{\"released_fee\":25000}', '2026-08-16 22:34:03', '2026-08-16 22:34:03'),
(64, 29, 219, 'release_government', 1250.00, NULL, '{\"released_fee\":25000}', '2026-08-16 22:34:03', '2026-08-16 22:34:03'),
(65, 30, 220, 'deposit', 17500.00, 'AY26333944', '{\"source\":\"booking_settlement\"}', '2026-08-16 22:42:23', '2026-08-16 22:42:23'),
(66, 30, 220, 'release_owner', 15750.00, NULL, '{\"target_owner_share\":15750,\"delta\":15750}', '2026-08-16 22:42:23', '2026-08-16 22:42:23'),
(67, 30, 220, 'top_up', 7500.00, 'TESTXLUG220895171', '{\"source\":\"clickpesa_top_up\"}', '2026-08-16 22:46:11', '2026-08-16 22:46:11'),
(68, 30, 220, 'release_admin', 1250.00, NULL, '{\"released_fee\":25000}', '2026-08-16 22:46:11', '2026-08-16 22:46:11'),
(69, 30, 220, 'release_government', 1250.00, NULL, '{\"released_fee\":25000}', '2026-08-16 22:46:11', '2026-08-16 22:46:11'),
(70, 30, 220, 'release_owner', 6750.00, NULL, '{\"target_owner_share\":22500,\"delta\":6750}', '2026-08-16 22:46:11', '2026-08-16 22:46:11'),
(71, 31, 221, 'deposit', 25000.00, 'RE45390407', '{\"source\":\"booking_settlement\"}', '2026-08-16 23:18:45', '2026-08-16 23:18:45'),
(72, 31, 221, 'release_owner', 22500.00, NULL, '{\"target_owner_share\":22500,\"delta\":22500}', '2026-08-16 23:18:45', '2026-08-16 23:18:45'),
(73, 31, 221, 'release_admin', 875.00, NULL, '{\"released_fee\":17500}', '2026-08-16 23:21:37', '2026-08-16 23:21:37'),
(74, 31, 221, 'release_government', 875.00, NULL, '{\"released_fee\":17500}', '2026-08-16 23:21:37', '2026-08-16 23:21:37'),
(75, 31, 221, 'release_owner', 6750.00, NULL, '{\"target_owner_share\":15750,\"delta\":-6750}', '2026-08-16 23:21:37', '2026-08-16 23:21:37'),
(76, 31, 221, 'refund', 7500.00, 'RE4539XLUGREF897386', '{\"approved_by\":1}', '2026-08-16 23:33:05', '2026-08-16 23:33:05'),
(77, 32, 229, 'deposit', 25000.00, 'LB02341704', '{\"source\":\"booking_settlement\"}', '2026-08-27 00:18:50', '2026-08-27 00:18:50'),
(78, 32, 229, 'release_owner', 22500.00, NULL, '{\"target_owner_share\":22500,\"delta\":22500}', '2026-08-27 00:18:50', '2026-08-27 00:18:50'),
(79, 32, 229, 'release_admin', 1250.00, NULL, '{\"released_fee\":25000}', '2026-08-27 03:02:40', '2026-08-27 03:02:40'),
(80, 32, 229, 'release_government', 1250.00, NULL, '{\"released_fee\":25000}', '2026-08-27 03:02:40', '2026-08-27 03:02:40'),
(81, 33, 230, 'deposit', 50000.00, 'NA86148158', '{\"source\":\"booking_settlement\"}', '2026-08-27 16:39:56', '2026-08-27 16:39:56'),
(82, 33, 230, 'release_owner', 45000.00, NULL, '{\"target_owner_share\":45000,\"delta\":45000}', '2026-08-27 16:39:56', '2026-08-27 16:39:56'),
(83, 33, 230, 'top_up', 25000.00, 'TESTXLUG230837671', '{\"source\":\"clickpesa_top_up\"}', '2026-08-27 20:34:31', '2026-08-27 20:34:31'),
(84, 33, 230, 'release_admin', 3750.00, NULL, '{\"released_fee\":75000}', '2026-08-27 20:34:31', '2026-08-27 20:34:31'),
(85, 33, 230, 'release_government', 3750.00, NULL, '{\"released_fee\":75000}', '2026-08-27 20:34:31', '2026-08-27 20:34:31'),
(86, 33, 230, 'release_owner', 22500.00, NULL, '{\"target_owner_share\":67500,\"delta\":22500}', '2026-08-27 20:34:31', '2026-08-27 20:34:31'),
(87, 34, 231, 'deposit', 50000.00, 'MF61171230', '{\"source\":\"booking_settlement\"}', '2026-08-31 02:16:19', '2026-08-31 02:16:19'),
(88, 34, 231, 'release_owner', 45000.00, NULL, '{\"target_owner_share\":45000,\"delta\":45000}', '2026-08-31 02:16:19', '2026-08-31 02:16:19'),
(89, 34, 231, 'release_admin', 1875.00, NULL, '{\"released_fee\":37500}', '2026-08-31 02:19:36', '2026-08-31 02:19:36'),
(90, 34, 231, 'release_government', 1875.00, NULL, '{\"released_fee\":37500}', '2026-08-31 02:19:36', '2026-08-31 02:19:36'),
(91, 34, 231, 'release_owner', 11250.00, NULL, '{\"target_owner_share\":33750,\"delta\":-11250}', '2026-08-31 02:19:36', '2026-08-31 02:19:36'),
(92, 35, 232, 'deposit', 25000.00, 'UW91157873', '{\"source\":\"booking_settlement\"}', '2026-08-31 03:24:46', '2026-08-31 03:24:46'),
(93, 35, 232, 'release_owner', 22500.00, NULL, '{\"target_owner_share\":22500,\"delta\":22500}', '2026-08-31 03:24:46', '2026-08-31 03:24:46'),
(94, 36, 234, 'deposit', 30000.00, 'UA35686570', '{\"source\":\"booking_settlement\"}', '2026-09-01 13:50:16', '2026-09-01 13:50:16'),
(95, 36, 234, 'release_owner', 27000.00, NULL, '{\"target_owner_share\":27000,\"delta\":27000}', '2026-09-01 13:50:16', '2026-09-01 13:50:16'),
(96, 37, 235, 'deposit', 25000.00, 'XC15355170', '{\"source\":\"booking_settlement\"}', '2026-09-01 13:55:46', '2026-09-01 13:55:46'),
(97, 37, 235, 'release_owner', 22500.00, NULL, '{\"target_owner_share\":22500,\"delta\":22500}', '2026-09-01 13:55:46', '2026-09-01 13:55:46'),
(98, 38, 236, 'deposit', 12500.00, 'OB39756277', '{\"source\":\"booking_settlement\"}', '2026-09-03 12:51:54', '2026-09-03 12:51:54'),
(99, 38, 236, 'release_owner', 11250.00, NULL, '{\"target_owner_share\":11250,\"delta\":11250}', '2026-09-03 12:51:54', '2026-09-03 12:51:54'),
(100, 38, 236, 'top_up', 12500.00, 'TESTXLUG236423828', '{\"source\":\"clickpesa_top_up\"}', '2026-09-03 15:23:48', '2026-09-03 15:23:48'),
(101, 38, 236, 'release_admin', 1250.00, NULL, '{\"released_fee\":25000}', '2026-09-03 15:23:48', '2026-09-03 15:23:48'),
(102, 38, 236, 'release_government', 1250.00, NULL, '{\"released_fee\":25000}', '2026-09-03 15:23:48', '2026-09-03 15:23:48'),
(103, 38, 236, 'release_owner', 11250.00, NULL, '{\"target_owner_share\":22500,\"delta\":11250}', '2026-09-03 15:23:48', '2026-09-03 15:23:48'),
(104, 39, 238, 'deposit', 25000.00, 'PH43046616', '{\"source\":\"booking_settlement\"}', '2026-09-14 16:00:51', '2026-09-14 16:00:51'),
(105, 39, 238, 'release_owner', 22500.00, NULL, '{\"target_owner_share\":22500,\"delta\":22500}', '2026-09-14 16:00:51', '2026-09-14 16:00:51'),
(106, 39, 238, 'release_admin', 625.00, NULL, '{\"released_fee\":12500}', '2026-09-14 16:30:12', '2026-09-14 16:30:12'),
(107, 39, 238, 'release_government', 625.00, NULL, '{\"released_fee\":12500}', '2026-09-14 16:30:12', '2026-09-14 16:30:12'),
(108, 39, 238, 'release_owner', 11250.00, NULL, '{\"target_owner_share\":11250,\"delta\":-11250}', '2026-09-14 16:30:12', '2026-09-14 16:30:12'),
(109, 39, 238, 'refund', 12500.00, 'PH4304XLUGREF378546', '{\"approved_by\":1}', '2026-09-14 19:41:27', '2026-09-14 19:41:27'),
(110, 40, 239, 'deposit', 25000.00, 'LA98894418', '{\"source\":\"booking_settlement\"}', '2026-09-16 16:21:19', '2026-09-16 16:21:19'),
(111, 40, 239, 'release_owner', 22500.00, NULL, '{\"target_owner_share\":22500,\"delta\":22500}', '2026-09-16 16:21:19', '2026-09-16 16:21:19'),
(112, 40, 239, 'release_admin', 625.00, NULL, '{\"released_fee\":12500}', '2026-09-16 16:54:51', '2026-09-16 16:54:51'),
(113, 40, 239, 'release_government', 625.00, NULL, '{\"released_fee\":12500}', '2026-09-16 16:54:51', '2026-09-16 16:54:51'),
(114, 40, 239, 'release_owner', 11250.00, NULL, '{\"target_owner_share\":11250,\"delta\":-11250}', '2026-09-16 16:54:51', '2026-09-16 16:54:51'),
(115, 40, 239, 'refund', 12500.00, 'LA9889XLUGREF552502', '{\"approved_by\":1}', '2026-09-16 17:00:33', '2026-09-16 17:00:33'),
(116, 41, 240, 'deposit', 25000.00, 'MZ76668262', '{\"source\":\"booking_settlement\"}', '2026-09-17 16:39:19', '2026-09-17 16:39:19'),
(117, 41, 240, 'release_owner', 22500.00, NULL, '{\"target_owner_share\":22500,\"delta\":22500}', '2026-09-17 16:39:19', '2026-09-17 16:39:19'),
(118, 41, 240, 'release_admin', 1000.00, NULL, '{\"released_fee\":20000}', '2026-09-17 16:54:10', '2026-09-17 16:54:10'),
(119, 41, 240, 'release_government', 1000.00, NULL, '{\"released_fee\":20000}', '2026-09-17 16:54:10', '2026-09-17 16:54:10'),
(120, 41, 240, 'release_owner', 4500.00, NULL, '{\"target_owner_share\":18000,\"delta\":-4500}', '2026-09-17 16:54:10', '2026-09-17 16:54:10'),
(121, 42, 241, 'deposit', 25000.00, 'LD41269645', '{\"source\":\"booking_settlement\"}', '2026-09-19 11:47:11', '2026-09-19 11:47:11'),
(122, 42, 241, 'release_owner', 22500.00, NULL, '{\"target_owner_share\":22500,\"delta\":22500}', '2026-09-19 11:47:11', '2026-09-19 11:47:11'),
(123, 42, 241, 'top_up', 5000.00, 'TESTXLUG241794394', '{\"source\":\"clickpesa_top_up\"}', '2026-09-19 12:06:34', '2026-09-19 12:06:34'),
(124, 42, 241, 'release_admin', 1500.00, NULL, '{\"released_fee\":30000}', '2026-09-19 12:06:34', '2026-09-19 12:06:34'),
(125, 42, 241, 'release_government', 1500.00, NULL, '{\"released_fee\":30000}', '2026-09-19 12:06:34', '2026-09-19 12:06:34'),
(126, 42, 241, 'release_owner', 4500.00, NULL, '{\"target_owner_share\":27000,\"delta\":4500}', '2026-09-19 12:06:34', '2026-09-19 12:06:34'),
(127, 43, 242, 'deposit', 50000.00, 'DL04227794', '{\"source\":\"booking_settlement\"}', '2026-09-20 18:02:17', '2026-09-20 18:02:17'),
(128, 43, 242, 'release_owner', 45000.00, NULL, '{\"target_owner_share\":45000,\"delta\":45000}', '2026-09-20 18:02:17', '2026-09-20 18:02:17'),
(129, 43, 242, 'release_admin', 1875.00, NULL, '{\"released_fee\":37500}', '2026-09-20 18:23:46', '2026-09-20 18:23:46'),
(130, 43, 242, 'release_government', 1875.00, NULL, '{\"released_fee\":37500}', '2026-09-20 18:23:46', '2026-09-20 18:23:46'),
(131, 43, 242, 'release_owner', 11250.00, NULL, '{\"target_owner_share\":33750,\"delta\":-11250}', '2026-09-20 18:23:46', '2026-09-20 18:23:46'),
(132, 44, 243, 'deposit', 20000.00, 'OC55008115', '{\"source\":\"booking_settlement\"}', '2026-09-21 12:02:47', '2026-09-21 12:02:47'),
(133, 44, 243, 'release_owner', 18000.00, NULL, '{\"target_owner_share\":18000,\"delta\":18000}', '2026-09-21 12:02:47', '2026-09-21 12:02:47'),
(134, 44, 243, 'release_admin', 750.00, NULL, '{\"released_fee\":15000}', '2026-09-21 12:32:28', '2026-09-21 12:32:28'),
(135, 44, 243, 'release_government', 750.00, NULL, '{\"released_fee\":15000}', '2026-09-21 12:32:28', '2026-09-21 12:32:28'),
(136, 44, 243, 'release_owner', 4500.00, NULL, '{\"target_owner_share\":13500,\"delta\":-4500}', '2026-09-21 12:32:28', '2026-09-21 12:32:28');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `government_levies`
--

CREATE TABLE `government_levies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `campany_id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `government_levies`
--

INSERT INTO `government_levies` (`id`, `campany_id`, `booking_id`, `amount`, `created_at`, `updated_at`) VALUES
(176, 3, 'SH42945199', 45.00, '2026-08-16 22:30:33', '2026-08-16 22:30:33'),
(177, 3, 'SH42945199', 1250.00, '2026-08-16 22:34:03', '2026-08-16 22:34:03'),
(178, 3, 'AY26333944', 45.00, '2026-08-16 22:42:23', '2026-08-16 22:42:23'),
(179, 3, 'AY26333944', 1250.00, '2026-08-16 22:46:11', '2026-08-16 22:46:11'),
(180, 3, 'RE45390407', 45.00, '2026-08-16 23:18:45', '2026-08-16 23:18:45'),
(181, 3, 'RE45390407', 875.00, '2026-08-16 23:21:37', '2026-08-16 23:21:37'),
(182, 5, 'EU42334306', 95.00, '2026-08-17 00:26:00', '2026-08-17 00:26:00'),
(183, 3, 'CG63508255', 45.00, '2026-08-20 12:27:43', '2026-08-20 12:27:43'),
(184, 3, 'GS75925755', 45.00, '2026-08-21 21:24:25', '2026-08-21 21:24:25'),
(185, 3, 'QF63950131', 45.00, '2026-08-26 01:49:16', '2026-08-26 01:49:16'),
(186, 3, 'CU91995088', 45.00, '2026-08-26 09:15:46', '2026-08-26 09:15:46'),
(187, 3, 'DS82734278', 45.00, '2026-08-26 09:39:39', '2026-08-26 09:39:39'),
(188, 3, 'LB02341704', 45.00, '2026-08-27 00:18:50', '2026-08-27 00:18:50'),
(189, 3, 'LB02341704', 1250.00, '2026-08-27 03:02:40', '2026-08-27 03:02:40'),
(190, 3, 'NA86148158', 45.00, '2026-08-27 16:39:56', '2026-08-27 16:39:56'),
(191, 3, 'NA86148158', 3750.00, '2026-08-27 20:34:31', '2026-08-27 20:34:31'),
(192, 3, 'MF61171230', 45.00, '2026-08-31 02:16:19', '2026-08-31 02:16:19'),
(193, 3, 'MF61171230', 1875.00, '2026-08-31 02:19:36', '2026-08-31 02:19:36'),
(194, 3, 'UW91157873', 41.00, '2026-08-31 03:24:46', '2026-08-31 03:24:46'),
(195, 3, 'UA35686570', 45.00, '2026-09-01 13:50:16', '2026-09-01 13:50:16'),
(196, 3, 'XC15355170', 45.00, '2026-09-01 13:55:45', '2026-09-01 13:55:45'),
(197, 3, 'OB39756277', 45.00, '2026-09-03 12:51:54', '2026-09-03 12:51:54'),
(198, 3, 'OB39756277', 1250.00, '2026-09-03 15:23:48', '2026-09-03 15:23:48'),
(199, 3, 'DM82681856', 45.00, '2026-09-14 13:00:10', '2026-09-14 13:00:10'),
(200, 3, 'PH43046616', 45.00, '2026-09-14 16:00:51', '2026-09-14 16:00:51'),
(201, 3, 'PH43046616', 625.00, '2026-09-14 16:30:12', '2026-09-14 16:30:12'),
(202, 3, 'LA98894418', 45.00, '2026-09-16 16:21:19', '2026-09-16 16:21:19'),
(203, 3, 'LA98894418', 625.00, '2026-09-16 16:54:51', '2026-09-16 16:54:51'),
(204, 3, 'MZ76668262', 45.00, '2026-09-17 16:39:19', '2026-09-17 16:39:19'),
(205, 3, 'MZ76668262', 1000.00, '2026-09-17 16:54:10', '2026-09-17 16:54:10'),
(206, 3, 'LD41269645', 41.00, '2026-09-19 11:47:11', '2026-09-19 11:47:11'),
(207, 3, 'LD41269645', 1500.00, '2026-09-19 12:06:34', '2026-09-19 12:06:34'),
(208, 3, 'DL04227794', 45.00, '2026-09-20 18:02:17', '2026-09-20 18:02:17'),
(209, 3, 'DL04227794', 1875.00, '2026-09-20 18:23:46', '2026-09-20 18:23:46'),
(210, 3, 'OC55008115', 45.00, '2026-09-21 12:02:47', '2026-09-21 12:02:47'),
(211, 3, 'OC55008115', 750.00, '2026-09-21 12:32:28', '2026-09-21 12:32:28');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(24, '2026_07_21_000004_add_excess_luggage_fee_per_kg_to_settings_table', 9),
(23, '2026_07_21_000003_add_luggage_weight_fields_to_bookings_table', 8),
(22, '2026_07_21_000002_add_excess_luggage_description_to_bookings_table', 7),
(21, '2026_07_21_000001_add_parcel_commission_percentage_to_settings_table', 6),
(20, '2026_07_17_000002_reopen_wrongly_completed_special_hire_orders', 5),
(19, '2026_07_17_000001_create_special_hire_payment_intents_table', 4),
(18, '2026_07_17_000001_create_device_tokens_table', 3),
(17, '2026_07_16_221800_change_users_contact_to_string', 2),
(16, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(15, '2014_10_12_200000_add_two_factor_columns_to_users_table', 1),
(25, '2026_07_21_000005_add_receipt_fields_to_parcels_table', 10),
(26, '2026_07_21_000006_add_parcel_fee_per_kg_to_settings_table', 11),
(27, '2026_07_21_000007_add_actual_dimensions_to_bookings_table', 12),
(28, '2026_08_02_000001_add_sms_gateway_settings_to_settings_table', 13),
(29, '2026_08_02_000002_create_sms_logs_table', 14),
(30, '2026_08_04_190000_add_excess_luggage_flow_fields_to_bookings_table', 15),
(31, '2026_08_04_200000_add_parcel_flow_fields', 16),
(32, '2026_08_06_000001_make_parcels_vender_id_nullable', 17),
(33, '2026_08_07_160000_add_luggage_weight_verdict_to_bookings_table', 18),
(34, '2026_08_15_000001_create_vender_wallet_deposits_table', 19),
(35, '2026_08_08_000001_extend_discounts_for_multi_product', 20),
(36, '2026_08_16_000001_create_excess_luggage_escrow_tables', 21),
(37, '2026_08_16_000002_backfill_excess_luggage_escrow', 22),
(38, '2026_08_16_000003_credit_backfilled_luggage_owner_wallets', 23),
(39, '2026_08_16_200000_add_received_at_to_parcels_table', 24),
(40, '2026_08_21_000001_add_refund_pending_to_special_hire_orders', 25),
(41, '2026_08_31_000001_add_parcel_vendor_commission_percentage_to_settings_table', 26);

-- --------------------------------------------------------

--
-- Table structure for table `parcels`
--

CREATE TABLE `parcels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parcel_number` varchar(255) NOT NULL,
  `parcel_type` varchar(255) NOT NULL,
  `sender_name` varchar(255) DEFAULT NULL,
  `sender_contact` varchar(255) DEFAULT NULL,
  `parcel_instructions` varchar(255) DEFAULT NULL,
  `receiver_name` varchar(255) DEFAULT NULL,
  `receiver_contact_1` varchar(255) DEFAULT NULL,
  `receiver_contact_2` varchar(255) DEFAULT NULL,
  `receiver_delivery_address` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `discount_code` varchar(64) DEFAULT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_before_discount` decimal(12,2) DEFAULT NULL,
  `payment_status` varchar(32) NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(40) DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `height` decimal(8,2) DEFAULT NULL,
  `width` decimal(8,2) DEFAULT NULL,
  `length` decimal(8,2) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `settled_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `departed_at` timestamp NULL DEFAULT NULL,
  `arrived_at` timestamp NULL DEFAULT NULL,
  `collected_at` timestamp NULL DEFAULT NULL,
  `bus_id` bigint(20) UNSIGNED NOT NULL,
  `vender_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `receiving_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `receiving_agent_name` varchar(150) DEFAULT NULL,
  `receiving_agent_phone` varchar(40) DEFAULT NULL,
  `delivery_rider_name` varchar(150) DEFAULT NULL,
  `delivery_rider_phone` varchar(40) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tra_status` varchar(255) DEFAULT 'pending',
  `tra_rct_num` varchar(255) DEFAULT NULL,
  `tra_z_num` varchar(255) DEFAULT NULL,
  `tra_vnum` varchar(255) DEFAULT NULL,
  `tra_qr_url` text DEFAULT NULL,
  `tra_response` text DEFAULT NULL,
  `tra_error` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `parcels`
--

INSERT INTO `parcels` (`id`, `parcel_number`, `parcel_type`, `sender_name`, `sender_contact`, `parcel_instructions`, `receiver_name`, `receiver_contact_1`, `receiver_contact_2`, `receiver_delivery_address`, `description`, `amount_paid`, `discount_code`, `discount_amount`, `amount_before_discount`, `payment_status`, `payment_method`, `payment_ref`, `weight`, `height`, `width`, `length`, `status`, `settled_at`, `received_at`, `departed_at`, `arrived_at`, `collected_at`, `bus_id`, `vender_id`, `created_by`, `receiving_user_id`, `receiving_agent_name`, `receiving_agent_phone`, `delivery_rider_name`, `delivery_rider_phone`, `created_at`, `updated_at`, `tra_status`, `tra_rct_num`, `tra_z_num`, `tra_vnum`, `tra_qr_url`, `tra_response`, `tra_error`) VALUES
(11, 'PCL-ART0TN', 'Box', 'Joseph Mpondomoko', '+255715020945', 'collection', 'Masanja Ntebela', '+255765553953', NULL, 'Thomas Express office, Dodoma.', NULL, 35000.00, NULL, 0.00, 35000.00, 'paid', 'test_mode', 'TESTPCL11898512', 10.00, NULL, NULL, NULL, 'completed', '2026-08-16 23:41:52', NULL, NULL, NULL, '2026-08-16 23:46:34', 6, NULL, 20, NULL, NULL, NULL, NULL, NULL, '2026-08-16 23:41:52', '2026-08-16 23:46:34', 'success', '100011', '20260816', 'T100011', 'https://virtual.tra.go.tz/efdmsrctverify/T100011_194152', 'test_mode_mock', NULL),
(12, 'PCL-8UWXER', 'Box', 'Abdul Bunju', '0715553803', 'collection', 'Alnas Abdul', '+255765553953', '+255765553953', 'Kibangu, External', 'Box la computer', 10000.00, NULL, 0.00, 10000.00, 'paid', 'test_mode', 'TESTPCL12899462', 10.00, NULL, NULL, NULL, 'completed', '2026-08-16 23:57:42', NULL, '2026-08-16 23:59:57', '2026-08-17 20:17:21', '2026-08-17 20:19:02', 7, 41, 41, NULL, NULL, NULL, NULL, NULL, '2026-08-16 23:57:42', '2026-08-17 20:19:02', 'success', '100012', '20260816', 'T100012', 'https://virtual.tra.go.tz/efdmsrctverify/T100012_195742', 'test_mode_mock', NULL),
(13, 'PCL-YL0QBS', 'Bag', 'Abdul Bunju', '071553803', 'collection', 'Abdul Bunju', '+33071553803', NULL, 'Abdul Bunju', NULL, 10000.00, NULL, 0.00, 10000.00, 'paid', 'test_mode', 'TESTPCL13900709', 10.00, NULL, NULL, NULL, 'completed', '2026-08-17 00:18:29', '2026-08-17 00:40:35', '2026-08-17 00:40:49', '2026-08-17 00:40:54', '2026-08-17 00:41:52', 6, 41, 41, NULL, NULL, NULL, NULL, NULL, '2026-08-17 00:18:29', '2026-08-17 00:41:52', 'success', '100013', '20260816', 'T100013', 'https://virtual.tra.go.tz/efdmsrctverify/T100013_201829', 'test_mode_mock', NULL),
(14, 'PCL-VEUYNM', 'Box', 'Thomas Chizi', '+255715020945', 'delivery', 'Abdu Juma Bunju', '+255765553953', NULL, 'Dar es salaam', 'Please receive your parcel', 35000.00, NULL, 0.00, 35000.00, 'paid', 'test_mode', 'TESTPCL14848331', 30.00, NULL, NULL, NULL, 'arrived', '2026-08-27 23:32:11', '2026-08-27 23:34:24', '2026-08-27 23:34:44', '2026-08-27 23:34:55', NULL, 7, 36, 36, NULL, 'Husein Maliga', '+255715020945', 'Raim Mussa', '+255715020945', '2026-08-27 23:32:11', '2026-08-27 23:34:55', 'success', '100014', '20260827', 'T100014', 'https://virtual.tra.go.tz/efdmsrctverify/T100014_193211', 'test_mode_mock', NULL),
(15, 'PCL-ED1R6F', 'Box', 'Thomas Chizi', '+255715020945', 'delivery', 'Jackson Malabo', '+255715020945', NULL, 'kariakoo', NULL, 50000.00, NULL, 0.00, 50000.00, 'paid', 'test_mode', 'TESTPCL15118025', 100.00, NULL, NULL, NULL, 'arrived', '2026-08-31 02:27:05', '2026-08-31 02:27:41', '2026-08-31 02:27:48', '2026-08-31 02:29:45', NULL, 6, NULL, 20, NULL, 'Jerome Said', '0657652216', 'John Kubatu', '0657652216', '2026-08-31 02:27:05', '2026-08-31 02:29:45', 'success', '100015', '20260830', 'T100015', 'https://virtual.tra.go.tz/efdmsrctverify/T100015_222705', 'test_mode_mock', NULL),
(16, 'PCL-DZISTD', 'Bag', 'Thomas Chizi', '+255715020945', 'collection', 'Mahindi Abdalah', '0789777668', '+255715020945', 'kariakoo', NULL, 30000.00, NULL, 0.00, 30000.00, 'paid', 'test_mode', 'TESTPCL16123473', NULL, NULL, NULL, NULL, 'pending', '2026-08-31 03:57:53', NULL, NULL, NULL, NULL, 6, 36, 36, NULL, NULL, NULL, NULL, NULL, '2026-08-31 03:57:53', '2026-09-18 20:47:56', 'success', '100016', '20260830', 'T100016', 'https://virtual.tra.go.tz/efdmsrctverify/T100016_235753', 'test_mode_mock', NULL),
(17, 'PCL-O4FE3K', 'Box', 'Thomas chizi', '+255715020945', 'collection', 'Baraka Ruge', '+255742036431', '+255715020945', 'Ubungo Kibangu', NULL, 40000.00, NULL, 0.00, 40000.00, 'paid', 'test_mode', 'TESTPCL17125072', NULL, NULL, NULL, NULL, 'pending', '2026-08-31 04:24:32', NULL, NULL, NULL, NULL, 7, 36, 36, NULL, NULL, NULL, NULL, NULL, '2026-08-31 04:24:32', '2026-09-18 08:40:32', 'success', '100017', '20260831', 'T100017', 'https://virtual.tra.go.tz/efdmsrctverify/T100017_002432', 'test_mode_mock', NULL),
(18, 'PCL-OU9RFI', 'Envelope', 'Abdu Juma Bunju', '+255765553953', 'collection', 'James Wanhu', '0715020945', '+255715020945', 'Shekilango, office no. 56', NULL, 10000.00, NULL, 0.00, 10000.00, 'paid', 'test_mode', 'TESTPCL18699329', NULL, NULL, NULL, NULL, 'completed', '2026-09-18 09:42:09', '2026-09-18 10:56:39', '2026-09-18 11:15:11', '2026-09-18 11:15:48', '2026-09-18 12:48:01', 6, 36, 36, NULL, NULL, NULL, NULL, NULL, '2026-09-18 09:42:09', '2026-09-18 12:48:01', 'success', '100018', '20260918', 'T100018', 'https://virtual.tra.go.tz/efdmsrctverify/T100018_054209', 'test_mode_mock', NULL),
(19, 'PCL-M4JN5Q', 'Other', 'Justina Edward Simba', '+255715020945', 'delivery', 'Abdi Likonga', '+255715020945', NULL, 'No. 45, Mbaba Street, Mbezi Africana, Dar es salaam', NULL, 12000.00, NULL, 0.00, 12000.00, 'paid', 'test_mode', 'TESTPCL19744019', 100.00, NULL, NULL, NULL, 'registered', '2026-09-18 22:06:59', NULL, NULL, NULL, NULL, 6, NULL, 20, NULL, 'James Kudumu', '0715020945', 'John Kubatu', '0715020945', '2026-09-18 22:06:59', '2026-09-18 22:06:59', 'success', '100019', '20260918', 'T100019', 'https://virtual.tra.go.tz/efdmsrctverify/T100019_180659', 'test_mode_mock', NULL),
(20, 'PCL-PJTCLB', 'Box', 'Thomas Chizi', '+255715020945', 'collection', 'David King Thomas', '+255715020945', NULL, 'Thomas Express offices, Shekilango, ofice no 09', NULL, 20000.00, NULL, 0.00, 20000.00, 'paid', 'test_mode', 'TESTPCL20744410', 25.00, NULL, NULL, NULL, 'registered', '2026-09-18 22:13:30', NULL, NULL, NULL, NULL, 6, NULL, 20, NULL, NULL, NULL, NULL, NULL, '2026-09-18 22:13:30', '2026-09-18 22:13:30', 'success', '100020', '20260918', 'T100020', 'https://virtual.tra.go.tz/efdmsrctverify/T100020_181330', 'test_mode_mock', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_fees`
--

CREATE TABLE `payment_fees` (
  `id` int(11) NOT NULL,
  `campany_id` int(11) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `booking_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_fees`
--

INSERT INTO `payment_fees` (`id`, `campany_id`, `amount`, `booking_id`, `created_at`, `updated_at`) VALUES
(175, 3, 765, 'SH42945199', '2026-08-16 22:30:33', '2026-08-16 18:30:33'),
(176, 3, 855, 'AY26333944', '2026-08-16 22:42:23', '2026-08-16 18:42:23'),
(177, 3, 855, 'RE45390407', '2026-08-16 23:18:45', '2026-08-16 19:18:45'),
(178, 5, 1615, 'EU42334306', '2026-08-17 00:26:00', '2026-08-16 20:26:00'),
(179, 3, 765, 'CG63508255', '2026-08-20 12:27:43', '2026-08-20 08:27:43'),
(180, 3, 855, 'GS75925755', '2026-08-21 21:24:25', '2026-08-21 17:24:25'),
(181, 3, 765, 'QF63950131', '2026-08-26 01:49:16', '2026-08-25 21:49:16'),
(182, 3, 765, 'CU91995088', '2026-08-26 09:15:46', '2026-08-26 05:15:46'),
(183, 3, 765, 'DS82734278', '2026-08-26 09:39:39', '2026-08-26 05:39:39'),
(184, 3, 765, 'LB02341704', '2026-08-27 00:18:50', '2026-08-26 20:18:50'),
(185, 3, 765, 'NA86148158', '2026-08-27 16:39:56', '2026-08-27 12:39:56'),
(186, 3, 765, 'MF61171230', '2026-08-31 02:16:19', '2026-08-30 22:16:19'),
(187, 3, 697, 'UW91157873', '2026-08-31 03:24:46', '2026-08-30 23:24:46'),
(188, 3, 765, 'UA35686570', '2026-09-01 13:50:16', '2026-09-01 09:50:16'),
(189, 3, 765, 'XC15355170', '2026-09-01 13:55:45', '2026-09-01 09:55:45'),
(190, 3, 765, 'OB39756277', '2026-09-03 12:51:54', '2026-09-03 08:51:54'),
(191, 3, 855, 'DM82681856', '2026-09-14 13:00:10', '2026-09-14 09:00:10'),
(192, 3, 855, 'PH43046616', '2026-09-14 16:00:51', '2026-09-14 12:00:51'),
(193, 3, 765, 'LA98894418', '2026-09-16 16:21:19', '2026-09-16 12:21:19'),
(194, 3, 855, 'MZ76668262', '2026-09-17 16:39:19', '2026-09-17 12:39:19'),
(195, 3, 779, 'LD41269645', '2026-09-19 11:47:11', '2026-09-19 07:47:11'),
(196, 3, 855, 'DL04227794', '2026-09-20 18:02:17', '2026-09-20 14:02:17'),
(197, 3, 765, 'OC55008115', '2026-09-21 12:02:47', '2026-09-21 08:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 17, 'customer-token', 'd9453fb8486e18795bc4de6e5996ab8be48af89d2c9befece92c1115f23d11f8', '[\"*\"]', '2026-07-17 02:23:55', NULL, '2026-07-17 02:12:19', '2026-07-17 02:23:55'),
(2, 'App\\Models\\User', 17, 'customer-token', '3d98daa1309116cc65b2d5b42c7582a6036d33e9025a4da92788aaa93ec58201', '[\"*\"]', '2026-07-17 10:51:35', NULL, '2026-07-17 03:25:01', '2026-07-17 10:51:35'),
(3, 'App\\Models\\User', 19, 'driver-token', '63f06ba35c6b78aff7d2b2458407c7e5f3b9d17dec65e753a928267ceded6088', '[\"*\"]', '2026-07-17 10:50:18', NULL, '2026-07-17 04:01:41', '2026-07-17 10:50:18'),
(4, 'App\\Models\\User', 19, 'driver-token', '5a9d64b3a66fba3520afe6fffd5dbf5bb8144701d00fa0eb178c862b1c0664af', '[\"*\"]', '2026-07-17 11:00:06', NULL, '2026-07-17 10:59:51', '2026-07-17 11:00:06'),
(5, 'App\\Models\\User', 17, 'customer-token', '7e1f6a714444feca38590d8c9ffd2d3008709b85aae2df0fbf59d8eb5351a877', '[\"*\"]', '2026-07-18 01:17:06', NULL, '2026-07-17 11:09:45', '2026-07-18 01:17:06'),
(6, 'App\\Models\\User', 19, 'driver-token', '3513c2fb08a34e60e1b91a7ee5a3bd9985ade7adb4c3376cbe9bdf4ff692dac2', '[\"*\"]', '2026-07-18 01:44:54', NULL, '2026-07-17 11:13:41', '2026-07-18 01:44:54'),
(7, 'App\\Models\\User', 17, 'customer-token', '33611b33170f72fa48ca0d4507f9f770af9d22a03e55cb9a53cfed310dba00fe', '[\"*\"]', '2026-09-14 12:12:02', NULL, '2026-07-18 01:48:21', '2026-09-14 12:12:02'),
(8, 'App\\Models\\User', 19, 'driver-token', '625918d55014ec1535dfe63fc4b195650dd63959c3478b6429a060fc13196acd', '[\"*\"]', '2026-08-21 18:02:29', NULL, '2026-07-18 02:02:26', '2026-08-21 18:02:29'),
(9, 'App\\Models\\User', 17, 'customer-token', 'f4e36dc09f6441e63f2f4ec7a1f02efcb0e2724fea59852be40d8b1569602a09', '[\"*\"]', '2026-07-18 03:37:53', NULL, '2026-07-18 02:51:34', '2026-07-18 03:37:53'),
(11, 'App\\Models\\User', 33, 'customer-token', '37c4185ac857b19aaac08c226ab998e8750ea861fdb8a0e8ab6548e389145249', '[\"*\"]', '2026-07-29 03:08:18', NULL, '2026-07-18 03:05:23', '2026-07-29 03:08:18'),
(12, 'App\\Models\\User', 30, 'driver-token', '0895e044c96e494334b1ae86917a46f641de20a4c94277e4f4b4695c666b7037', '[\"*\"]', '2026-07-18 23:35:58', NULL, '2026-07-18 03:33:36', '2026-07-18 23:35:58'),
(13, 'App\\Models\\User', 34, 'customer-token', 'ff616112cdb2b4920f6933ecd635ed40f4bf6ec05e1800d523458873e937b5d0', '[\"*\"]', '2026-09-01 10:21:09', NULL, '2026-07-18 07:52:11', '2026-09-01 10:21:09'),
(14, 'App\\Models\\User', 43, 'customer-token', '9d52cc087fba7e1ff1c872780a4f5325b50408f32db469d4ca2b0ec6ce3db6b4', '[\"*\"]', '2026-08-30 00:47:40', NULL, '2026-07-22 14:34:38', '2026-08-30 00:47:40'),
(15, 'App\\Models\\User', 44, 'customer-token', 'f29ef6b6ca7fe96137c0eb5988ae30ec1e4ed19d64c391232a1f2e930da57cbc', '[\"*\"]', '2026-08-31 13:45:11', NULL, '2026-07-22 20:25:33', '2026-08-31 13:45:11');

-- --------------------------------------------------------

--
-- Table structure for table `points`
--

CREATE TABLE `points` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `point_mode` int(11) DEFAULT NULL,
  `point` varchar(255) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `state` varchar(255) NOT NULL DEFAULT 'no',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `points`
--

INSERT INTO `points` (`id`, `bus_id`, `route_id`, `point_mode`, `point`, `amount`, `state`, `created_at`, `updated_at`) VALUES
(278, 6, 6, 2, 'Kiboriloni', 40000, 'yes', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(277, 6, 6, 1, 'Bagamoyo', 0, 'yes', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(276, 6, 6, 2, 'Njiapanda ya Himo', 40000, 'yes', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(67, 8, 8, 1, 'MBEZI MBT', 50000, 'no', '2026-07-17 19:20:07', '2026-07-17 15:20:07'),
(72, 11, 11, 1, 'Shekilango', 90000, 'no', '2026-07-31 16:31:01', '2026-07-31 12:31:01'),
(73, 11, 11, 1, 'Mbezi Luis', 90000, 'no', '2026-07-31 16:31:01', '2026-07-31 12:31:01'),
(74, 11, 11, 1, 'Dodoma', 70000, 'no', '2026-07-31 16:31:01', '2026-07-31 12:31:01'),
(75, 11, 11, 1, 'Morogoro', 80000, 'no', '2026-07-31 16:31:01', '2026-07-31 12:31:01'),
(76, 11, 11, 2, 'Dodoma', 20000, 'no', '2026-07-31 16:31:01', '2026-07-31 12:31:01'),
(77, 11, 11, 2, 'Shinyanga', 40000, 'no', '2026-07-31 16:31:01', '2026-07-31 12:31:01'),
(235, 7, 7, 2, 'Mbezi magufuli', 40000, 'yes', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(232, 7, 7, 1, 'Moshi mjini', 0, 'yes', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(233, 7, 7, 2, 'Mbezi Africana', 40000, 'yes', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(234, 7, 7, 2, 'Shekilango', 40000, 'yes', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(231, 7, 7, 1, 'Kia', 0, 'yes', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(230, 7, 7, 1, 'Arusha bus stand', 0, 'yes', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(228, 7, 7, 2, 'Kia', 40000, 'no', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(229, 7, 7, 2, 'Arusha bus stand', 40000, 'no', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(227, 7, 7, 2, 'Moshi mjini', 40000, 'no', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(226, 7, 7, 2, 'Kiboriloni', 40000, 'no', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(225, 7, 7, 2, 'Njia panda ya himo', 40000, 'no', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(223, 7, 7, 1, 'Mapinga', 0, 'no', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(224, 7, 7, 1, 'Bagamoyo', 0, 'no', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(222, 7, 7, 1, 'Bunju', 0, 'no', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(220, 7, 7, 1, 'Shekilango', 0, 'no', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(221, 7, 7, 1, 'Mbezi Africana', 0, 'no', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(219, 7, 7, 1, 'Mbezi maguguli', 0, 'no', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(218, 7, 7, 1, 'Kiboriloni', 0, 'yes', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(217, 7, 7, 1, 'Njiapanda ya Himo', 0, 'yes', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(275, 6, 6, 1, 'Mapinga', 0, 'yes', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(274, 6, 6, 1, 'Bunju', 0, 'yes', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(272, 6, 6, 1, 'Shekilango', 0, 'yes', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(273, 6, 6, 1, 'Mbezi africana', 0, 'yes', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(271, 6, 6, 1, 'Mbezi magufuli', 0, 'yes', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(268, 6, 6, 2, 'Bunju', 40000, 'no', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(269, 6, 6, 2, 'Shekilango', 40000, 'no', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(270, 6, 6, 2, 'Mbezi magufuli', 40000, 'no', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(267, 6, 6, 2, 'Mbezi Africana', 40000, 'no', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(266, 6, 6, 2, 'Bagamoyo', 40000, 'no', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(265, 6, 6, 2, 'Mapinga', 40000, 'no', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(264, 6, 6, 1, 'Njiapanda ya Himo', 0, 'no', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(263, 6, 6, 1, 'Kiboriloni', 0, 'no', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(261, 6, 6, 1, 'Kia', 0, 'no', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(262, 6, 6, 1, 'Arusha bus stand', 0, 'no', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(260, 6, 6, 2, 'Moshi mjini', 40000, 'yes', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(259, 6, 6, 1, 'Moshi mjini', 0, 'no', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(216, 7, 7, 2, 'Bagamoyo', 40000, 'yes', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(215, 7, 7, 2, 'Mapinga', 40000, 'yes', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(214, 7, 7, 2, 'Bunju', 40000, 'yes', '2026-09-14 10:51:14', '2026-09-14 06:51:14'),
(258, 6, 6, 2, 'Kia', 40000, 'yes', '2026-09-14 10:52:29', '2026-09-14 06:52:29'),
(279, 6, 6, 2, 'Arusha bus stand', 40000, 'yes', '2026-09-14 10:52:29', '2026-09-14 06:52:29');

-- --------------------------------------------------------

--
-- Table structure for table `refund`
--

CREATE TABLE `refund` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_code` varchar(255) DEFAULT NULL,
  `amount` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `phone` varchar(255) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refund_percentages`
--

CREATE TABLE `refund_percentages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_code` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roundtrip`
--

CREATE TABLE `roundtrip` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(100) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `route_start` varchar(255) DEFAULT NULL,
  `route_end` varchar(255) DEFAULT NULL,
  `price` int(11) NOT NULL,
  `distance` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `bus_id`, `from`, `to`, `route_start`, `route_end`, `price`, `distance`, `created_at`, `updated_at`) VALUES
(6, 6, 'Arusha', 'Dar es salaam', '', '', 40000, 452, '2026-07-17 13:07:22', '2026-09-14 04:13:45'),
(7, 7, 'Arusha', 'Dar es salaam', '', '', 40000, 450, '2026-07-17 13:15:42', '2026-09-15 14:16:34'),
(8, 8, 'Dar es salaam', 'Dodoma', '', '', 50000, 452, '2026-07-17 19:14:09', '2026-07-17 15:14:09'),
(9, 9, 'Dodoma', 'Arusha', '', '', 45000, 0, '2026-07-22 15:19:30', '2026-07-22 11:19:30'),
(10, 10, 'Dodoma', 'Arusha', '', '', 65000, 0, '2026-07-22 16:00:06', '2026-07-22 12:00:06'),
(11, 11, 'Dar es salaam', 'Mwanza', '', '', 90000, 1152, '2026-07-31 16:28:59', '2026-07-31 12:28:59');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bus_id` bigint(20) UNSIGNED NOT NULL,
  `route_id` int(11) NOT NULL,
  `from` varchar(255) DEFAULT NULL,
  `to` varchar(255) DEFAULT NULL,
  `schedule_date` date DEFAULT NULL,
  `start` time DEFAULT NULL,
  `end` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `bus_id`, `route_id`, `from`, `to`, `schedule_date`, `start`, `end`, `created_at`, `updated_at`) VALUES
(432, 6, 6, 'Dar es salaam', 'Dodoma', '2026-09-07', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(431, 6, 6, 'Dodoma', 'Dar es salaam', '2026-09-06', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(430, 6, 6, 'Dar es salaam', 'Dodoma', '2026-09-05', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(429, 6, 6, 'Dodoma', 'Dar es salaam', '2026-09-04', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(428, 6, 6, 'Dar es salaam', 'Dodoma', '2026-09-03', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(427, 6, 6, 'Dodoma', 'Dar es salaam', '2026-09-02', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(426, 6, 6, 'Dar es salaam', 'Dodoma', '2026-09-01', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(425, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-31', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(424, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-30', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(423, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-29', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(422, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-28', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(421, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-27', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(420, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-26', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(419, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-25', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(418, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-24', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(417, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-23', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(416, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-22', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(415, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-21', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(414, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-20', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(413, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-19', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(412, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-18', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(411, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-17', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(410, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-16', '22:00:00', '06:30:00', '2026-08-11 20:10:44', '2026-08-11 20:10:44'),
(409, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-26', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(408, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-25', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(407, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-24', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(406, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-23', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(405, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-22', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(404, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-21', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(403, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-20', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(402, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-19', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(401, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-18', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(400, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-17', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(399, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-16', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(398, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-15', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(397, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-14', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(396, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-13', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(395, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-12', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(394, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-11', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(393, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-10', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(392, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-09', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(391, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-08', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(390, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-07', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(389, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-06', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(388, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-05', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(387, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-04', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(386, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-03', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(385, 7, 7, 'Dar es salaam', 'Dodoma', '2026-10-02', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(384, 7, 7, 'Dodoma', 'Dar es salaam', '2026-10-01', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(383, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-30', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(382, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-29', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(381, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-28', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(380, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-27', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(379, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-26', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(378, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-25', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(377, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-24', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(376, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-23', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(375, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-22', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(374, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-21', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(373, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-20', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(372, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-19', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(371, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-18', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(370, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-17', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(369, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-16', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(368, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-15', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(367, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-14', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(366, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-13', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(365, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-12', '22:00:00', '06:30:00', '2026-08-11 20:09:01', '2026-08-11 20:09:01'),
(364, 11, 11, 'Dar es salaam', 'Mwanza', '2026-09-03', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(363, 11, 11, 'Mwanza', 'Dar es salaam', '2026-09-02', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(362, 11, 11, 'Dar es salaam', 'Mwanza', '2026-09-01', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(361, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-31', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(360, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-30', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(359, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-29', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(358, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-28', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(357, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-27', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(356, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-26', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(355, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-25', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(354, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-24', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(353, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-23', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(352, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-22', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(351, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-21', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(350, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-20', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(349, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-19', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(348, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-18', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(347, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-17', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(346, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-16', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(345, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-15', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(344, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-14', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(343, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-13', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(342, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-12', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(341, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-11', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(340, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-10', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(339, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-09', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(338, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-08', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(337, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-07', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(336, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-06', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(335, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-05', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(334, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-04', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(333, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-03', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(332, 11, 11, 'Dar es salaam', 'Mwanza', '2026-08-02', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(331, 11, 11, 'Mwanza', 'Dar es salaam', '2026-08-01', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(330, 11, 11, 'Dar es salaam', 'Mwanza', '2026-07-31', '06:00:00', '23:30:00', '2026-07-31 16:32:54', '2026-07-31 16:32:54'),
(329, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-11', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(328, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-10', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(327, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-09', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(326, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-08', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(325, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-07', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(324, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-06', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(323, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-05', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(322, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-04', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(321, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-03', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(320, 7, 7, 'Dar es salaam', 'Dodoma', '2026-09-02', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(319, 7, 7, 'Dodoma', 'Dar es salaam', '2026-09-01', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(318, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-31', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(317, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-30', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(316, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-29', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(315, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-28', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(314, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-27', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(313, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-26', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(312, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-25', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(311, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-24', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(310, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-23', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(309, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-22', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(308, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-21', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(307, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-20', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(306, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-19', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(305, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-18', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(304, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-17', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(303, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-16', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(302, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-15', '22:00:00', '06:30:00', '2026-07-17 21:32:48', '2026-07-17 21:32:48'),
(301, 8, 8, 'Dodoma', 'Dar es salaam', '2026-07-19', '03:23:00', '15:23:00', '2026-07-17 19:23:27', '2026-07-17 19:23:27'),
(300, 8, 8, 'Dar es salaam', 'Dodoma', '2026-07-18', '03:23:00', '15:23:00', '2026-07-17 19:23:27', '2026-07-17 19:23:27'),
(299, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-15', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(298, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-14', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(297, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-13', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(296, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-12', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(295, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-11', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(294, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-10', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(293, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-09', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(292, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-08', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(291, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-07', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(290, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-06', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(289, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-05', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(288, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-04', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(287, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-03', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(286, 6, 6, 'Dar es salaam', 'Dodoma', '2026-08-02', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(285, 6, 6, 'Dodoma', 'Dar es salaam', '2026-08-01', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(284, 6, 6, 'Dar es salaam', 'Dodoma', '2026-07-31', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(283, 6, 6, 'Dodoma', 'Dar es salaam', '2026-07-30', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(282, 6, 6, 'Dar es salaam', 'Dodoma', '2026-07-29', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(281, 6, 6, 'Dodoma', 'Dar es salaam', '2026-07-28', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(280, 6, 6, 'Dar es salaam', 'Dodoma', '2026-07-27', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(279, 6, 6, 'Dodoma', 'Dar es salaam', '2026-07-26', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(278, 6, 6, 'Dar es salaam', 'Dodoma', '2026-07-25', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(277, 6, 6, 'Dodoma', 'Dar es salaam', '2026-07-24', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(276, 6, 6, 'Dar es salaam', 'Dodoma', '2026-07-23', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(275, 6, 6, 'Dodoma', 'Dar es salaam', '2026-07-22', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(274, 6, 6, 'Dar es salaam', 'Dodoma', '2026-07-21', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(273, 6, 6, 'Dodoma', 'Dar es salaam', '2026-07-20', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(272, 6, 6, 'Dar es salaam', 'Dodoma', '2026-07-19', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(271, 6, 6, 'Dodoma', 'Dar es salaam', '2026-07-18', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(270, 6, 6, 'Dar es salaam', 'Dodoma', '2026-07-17', '22:00:00', '06:30:00', '2026-07-17 13:49:28', '2026-07-17 13:49:28'),
(269, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-14', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(268, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-13', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(267, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-12', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(266, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-11', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(265, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-10', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(264, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-09', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(263, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-08', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(262, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-07', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(261, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-06', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(260, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-05', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(259, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-04', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(258, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-03', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(257, 7, 7, 'Dodoma', 'Dar es salaam', '2026-08-02', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(256, 7, 7, 'Dar es salaam', 'Dodoma', '2026-08-01', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(255, 7, 7, 'Dodoma', 'Dar es salaam', '2026-07-31', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(254, 7, 7, 'Dar es salaam', 'Dodoma', '2026-07-30', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(253, 7, 7, 'Dodoma', 'Dar es salaam', '2026-07-29', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(252, 7, 7, 'Dar es salaam', 'Dodoma', '2026-07-28', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(251, 7, 7, 'Dodoma', 'Dar es salaam', '2026-07-27', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(250, 7, 7, 'Dar es salaam', 'Dodoma', '2026-07-26', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(249, 7, 7, 'Dodoma', 'Dar es salaam', '2026-07-25', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(248, 7, 7, 'Dar es salaam', 'Dodoma', '2026-07-24', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(247, 7, 7, 'Dodoma', 'Dar es salaam', '2026-07-23', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(246, 7, 7, 'Dar es salaam', 'Dodoma', '2026-07-22', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(245, 7, 7, 'Dodoma', 'Dar es salaam', '2026-07-21', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(244, 7, 7, 'Dar es salaam', 'Dodoma', '2026-07-20', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(243, 7, 7, 'Dodoma', 'Dar es salaam', '2026-07-19', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(242, 7, 7, 'Dar es salaam', 'Dodoma', '2026-07-18', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(241, 7, 7, 'Dodoma', 'Dar es salaam', '2026-07-17', '22:00:00', '06:30:00', '2026-07-17 13:47:26', '2026-07-17 13:47:26'),
(433, 7, 7, 'Dar es salaam', 'Arusha', '2026-10-26', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(434, 7, 7, 'Arusha', 'Dar es salaam', '2026-10-27', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(435, 7, 7, 'Dar es salaam', 'Arusha', '2026-10-28', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(436, 7, 7, 'Arusha', 'Dar es salaam', '2026-10-29', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(437, 7, 7, 'Dar es salaam', 'Arusha', '2026-10-30', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(438, 7, 7, 'Arusha', 'Dar es salaam', '2026-10-31', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(439, 7, 7, 'Dar es salaam', 'Arusha', '2026-11-01', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(440, 7, 7, 'Arusha', 'Dar es salaam', '2026-11-02', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(441, 7, 7, 'Dar es salaam', 'Arusha', '2026-11-03', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(442, 7, 7, 'Arusha', 'Dar es salaam', '2026-11-04', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(443, 7, 7, 'Dar es salaam', 'Arusha', '2026-11-05', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(444, 7, 7, 'Arusha', 'Dar es salaam', '2026-11-06', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(445, 7, 7, 'Dar es salaam', 'Arusha', '2026-11-07', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(446, 7, 7, 'Arusha', 'Dar es salaam', '2026-11-08', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(447, 7, 7, 'Dar es salaam', 'Arusha', '2026-11-09', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(448, 7, 7, 'Arusha', 'Dar es salaam', '2026-11-10', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(449, 7, 7, 'Dar es salaam', 'Arusha', '2026-11-11', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(450, 7, 7, 'Arusha', 'Dar es salaam', '2026-11-12', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(451, 7, 7, 'Dar es salaam', 'Arusha', '2026-11-13', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(452, 7, 7, 'Arusha', 'Dar es salaam', '2026-11-14', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(453, 7, 7, 'Dar es salaam', 'Arusha', '2026-11-15', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(454, 7, 7, 'Arusha', 'Dar es salaam', '2026-11-16', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(455, 7, 7, 'Dar es salaam', 'Arusha', '2026-11-17', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(456, 7, 7, 'Arusha', 'Dar es salaam', '2026-11-18', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(457, 7, 7, 'Dar es salaam', 'Arusha', '2026-11-19', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(458, 7, 7, 'Arusha', 'Dar es salaam', '2026-11-20', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(459, 7, 7, 'Dar es salaam', 'Arusha', '2026-11-21', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(460, 7, 7, 'Arusha', 'Dar es salaam', '2026-11-22', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(461, 7, 7, 'Dar es salaam', 'Arusha', '2026-11-23', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(462, 7, 7, 'Arusha', 'Dar es salaam', '2026-11-24', '22:00:00', '07:30:00', '2026-09-14 09:11:38', '2026-09-14 09:11:38'),
(463, 6, 6, 'Arusha', 'Dar es salaam', '2026-09-14', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(464, 6, 6, 'Dar es salaam', 'Arusha', '2026-09-15', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(465, 6, 6, 'Arusha', 'Dar es salaam', '2026-09-16', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(466, 6, 6, 'Dar es salaam', 'Arusha', '2026-09-17', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(467, 6, 6, 'Arusha', 'Dar es salaam', '2026-09-18', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(468, 6, 6, 'Dar es salaam', 'Arusha', '2026-09-19', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(469, 6, 6, 'Arusha', 'Dar es salaam', '2026-09-20', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(470, 6, 6, 'Dar es salaam', 'Arusha', '2026-09-21', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(471, 6, 6, 'Arusha', 'Dar es salaam', '2026-09-22', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(472, 6, 6, 'Dar es salaam', 'Arusha', '2026-09-23', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(473, 6, 6, 'Arusha', 'Dar es salaam', '2026-09-24', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(474, 6, 6, 'Dar es salaam', 'Arusha', '2026-09-25', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(475, 6, 6, 'Arusha', 'Dar es salaam', '2026-09-26', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(476, 6, 6, 'Dar es salaam', 'Arusha', '2026-09-27', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(477, 6, 6, 'Arusha', 'Dar es salaam', '2026-09-28', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(478, 6, 6, 'Dar es salaam', 'Arusha', '2026-09-29', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(479, 6, 6, 'Arusha', 'Dar es salaam', '2026-09-30', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(480, 6, 6, 'Dar es salaam', 'Arusha', '2026-10-01', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(481, 6, 6, 'Arusha', 'Dar es salaam', '2026-10-02', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(482, 6, 6, 'Dar es salaam', 'Arusha', '2026-10-03', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(483, 6, 6, 'Arusha', 'Dar es salaam', '2026-10-04', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(484, 6, 6, 'Dar es salaam', 'Arusha', '2026-10-05', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(485, 6, 6, 'Arusha', 'Dar es salaam', '2026-10-06', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(486, 6, 6, 'Dar es salaam', 'Arusha', '2026-10-07', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(487, 6, 6, 'Arusha', 'Dar es salaam', '2026-10-08', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(488, 6, 6, 'Dar es salaam', 'Arusha', '2026-10-09', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(489, 6, 6, 'Arusha', 'Dar es salaam', '2026-10-10', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(490, 6, 6, 'Dar es salaam', 'Arusha', '2026-10-11', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(491, 6, 6, 'Arusha', 'Dar es salaam', '2026-10-12', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48'),
(492, 6, 6, 'Dar es salaam', 'Arusha', '2026-10-13', '10:00:00', '07:30:00', '2026-09-14 09:13:48', '2026-09-14 09:13:48');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(100) NOT NULL,
  `payload` varchar(255) NOT NULL,
  `last_activity` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `user_agent` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `international` decimal(10,2) NOT NULL,
  `local` decimal(10,2) NOT NULL,
  `insurance_company` varchar(255) DEFAULT NULL,
  `insurance_policy_local` varchar(255) DEFAULT NULL,
  `insurance_policy_foreign` varchar(255) DEFAULT NULL,
  `service` decimal(10,2) DEFAULT 0.00,
  `service_percentage` int(11) NOT NULL DEFAULT 0,
  `parcel_commission_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `parcel_vendor_commission_percentage` decimal(5,2) NOT NULL DEFAULT 25.00,
  `excess_luggage_fee_per_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `parcel_fee_per_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `enable_customer_sms_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `enable_customer_email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `enable_conductor_sms_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `enable_conductor_email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `test_mode` tinyint(1) NOT NULL DEFAULT 0,
  `enforce_2fa` tinyint(1) NOT NULL DEFAULT 1,
  `enforce_customer_email_verification` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL,
  `sms_driver` varchar(32) NOT NULL DEFAULT 'smscotz',
  `sms_sender_id` varchar(32) DEFAULT NULL,
  `at_username` varchar(100) DEFAULT NULL,
  `at_api_key` text DEFAULT NULL,
  `at_sandbox` tinyint(1) NOT NULL DEFAULT 1,
  `cotz_username` varchar(100) DEFAULT NULL,
  `cotz_password` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `international`, `local`, `insurance_company`, `insurance_policy_local`, `insurance_policy_foreign`, `service`, `service_percentage`, `parcel_commission_percentage`, `parcel_vendor_commission_percentage`, `excess_luggage_fee_per_kg`, `parcel_fee_per_kg`, `enable_customer_sms_notifications`, `enable_customer_email_notifications`, `enable_conductor_sms_notifications`, `enable_conductor_email_notifications`, `test_mode`, `enforce_2fa`, `enforce_customer_email_verification`, `created_at`, `updated_at`, `sms_driver`, `sms_sender_id`, `at_username`, `at_api_key`, `at_sandbox`, `cotz_username`, `cotz_password`) VALUES
(1, 100.00, 200.00, 'G.A Insurance Tanzania Limited', 'Safiri salama - Domestic', 'Safiri salama - Foreign', 100.00, 2, 5.00, 5.00, 2500.00, 1500.00, 1, 1, 0, 1, 1, 0, 0, '2026-05-04 01:05:40', '2026-08-31 00:16:57', 'africastalking', 'HIGHLINKSMS', 'HIGHLINKSMSPORTAL', 'eyJpdiI6ImZIWDVPUkpaSU1zOWpwWXgxb1VrMUE9PSIsInZhbHVlIjoiTDZNQVEzS0xzZUZtZlJlaEdQcG9Qa3ExdGQwc3drOWU0YmFRQklQYzEweml1TTd2c3BGZWtLRUR0K0FFKzYyRGVvbGlNRVkrMHAwem5CYlhPMktYZDN4dWY4R2E0dWtVNFk0WENQS0p0KzA9IiwibWFjIjoiNDhhZjI0OGNmZGEyNzI3NTYyYzllZmUwYWFiMjAxY2Q5N2I2NGI2YmM2NjhkOGY3NjlhZGI3NTFlNjNmY2Q4NyIsInRhZyI6IiJ9', 0, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `driver` varchar(32) NOT NULL,
  `destination` varchar(32) NOT NULL,
  `message` text NOT NULL,
  `message_id` varchar(191) DEFAULT NULL,
  `status` varchar(64) DEFAULT NULL,
  `failure_reason` varchar(191) DEFAULT NULL,
  `cost` decimal(10,4) DEFAULT NULL,
  `currency` varchar(8) DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_logs`
--

INSERT INTO `sms_logs` (`id`, `driver`, `destination`, `message`, `message_id`, `status`, `failure_reason`, `cost`, `currency`, `delivered_at`, `created_at`, `updated_at`) VALUES
(1, 'africastalking', '+255628042409', 'hellow', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-03 02:50:17', '2026-08-03 02:50:17'),
(2, 'africastalking', '+255628042409', 'hellow', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-03 04:16:35', '2026-08-03 04:16:35'),
(3, 'africastalking', '+255628042409', 'hellow', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-03 04:16:58', '2026-08-03 04:16:58'),
(4, 'africastalking', '+255628042409', 'hellow', NULL, 'failed', 'HTTP 401: The supplied authentication is invalid Check Username matches the AT dashboard (not the app display name), re-paste the API key and Save, and keep Sandbox off for live keys.', NULL, NULL, NULL, '2026-08-03 04:19:32', '2026-08-03 04:19:32'),
(5, 'africastalking', '+255765553953', 'Dear Abdul, Karibu Bish Bus, Utasafiri na basi namba T 571 DPH Linalotoka Dar es salaam Kwenda Dodoma Tarehe 2026-08-06. tafadhali wasili Dar es salaam angalau mapema saa 05:30 AM tayari kwa safari. Namba ya kiti chako ni A4 na namba yako ya safari ni LB48332057. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'HTTP 401: The supplied authentication is invalid Check Username matches the AT dashboard (not the app display name), re-paste the API key and Save, and keep Sandbox off for live keys.', NULL, NULL, NULL, '2026-08-03 23:15:49', '2026-08-03 23:15:49'),
(6, 'africastalking', '+255767890890', 'Mpendwa Thomas, Mzigo wako nambari PCL-MA55VN umepokelewa katika ofisi za Bish Bus hapa Dar es salaam tayari kusafirishwa kuelekea Arusha wa kupokelea. Utapokea taarifa kutoka Bish Bus mara baada ya mzigo wako utakapowasili.', NULL, 'failed', 'HTTP 401: The supplied authentication is invalid Check Username matches the AT dashboard (not the app display name), re-paste the API key and Save, and keep Sandbox off for live keys.', NULL, NULL, NULL, '2026-08-04 00:10:01', '2026-08-04 00:10:01'),
(7, 'africastalking', '+255715020945', 'Dear Jackson Mbilizi, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-08-08. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni F1 na namba yako ya safari ni YF93204432. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'HTTP 401: The supplied authentication is invalid Check Username matches the AT dashboard (not the app display name), re-paste the API key and Save, and keep Sandbox off for live keys.', NULL, NULL, NULL, '2026-08-05 13:07:31', '2026-08-05 13:07:31'),
(8, 'africastalking', '+255715020945', 'Dear Samson Edson Manyota, Karibu Thomas Express, Utasafiri na basi namba T 777 EMM Linalotoka Job Ndugai Bus Station Kwenda Kimara temboni Tarehe 2026-08-08. tafadhali wasili Job Ndugai Bus Station angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A16,A15 na namba yako ya safari ni SN56049903. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'HTTP 401: The supplied authentication is invalid Check Username matches the AT dashboard (not the app display name), re-paste the API key and Save, and keep Sandbox off for live keys.', NULL, NULL, NULL, '2026-08-07 11:10:14', '2026-08-07 11:10:14'),
(9, 'africastalking', '+255628042409', 'hellow', NULL, 'failed', 'HTTP 401: The supplied authentication is invalid Check Username matches the AT dashboard (not the app display name), re-paste the API key and Save, and keep Sandbox off for live keys.', NULL, NULL, NULL, '2026-08-08 18:40:41', '2026-08-08 18:40:41'),
(10, 'africastalking', '+255628042409', 'hellow', NULL, 'failed', 'HTTP 401: The supplied authentication is invalid Check Username matches the AT dashboard (not the app display name), re-paste the API key and Save, and keep Sandbox off for live keys.', NULL, NULL, NULL, '2026-08-08 18:40:42', '2026-08-08 18:40:42'),
(11, 'africastalking', '+255628042409', 'hellow', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-08 18:45:22', '2026-08-08 18:45:22'),
(12, 'africastalking', '+255765553953', 'Dear Jacob Malele, Karibu Thomas Express, Utasafiri na basi namba T 777 EMM Linalotoka Kimara temboni Kwenda Nanenane Tarehe 2026-08-09. tafadhali wasili Kimara temboni angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A23 na namba yako ya safari ni EJ56730964. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-09 10:12:50', '2026-08-09 10:12:50'),
(13, 'africastalking', '+255628042409', 'Dear werwaer, Karibu Thomas Express, Utasafiri na basi namba T 777 EMM Linalotoka Shekilango Kwenda Nanenane Tarehe 2026-08-09. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A16 na namba yako ya safari ni ZW03463489. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-09 16:27:43', '2026-08-09 16:27:43'),
(14, 'africastalking', '+255765553953', 'Dear Abdul, Karibu Bish Bus, Utasafiri na basi namba T 571 DPH Linalotoka Dar es salaam Kwenda Dodoma Tarehe 2026-08-10. tafadhali wasili Dar es salaam angalau mapema saa 05:30 AM tayari kwa safari. Namba ya kiti chako ni A4 na namba yako ya safari ni EU13719343. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-10 15:16:28', '2026-08-10 15:16:28'),
(15, 'africastalking', '+255788979379', 'Dear Dr Joseph Kimweri, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-08-14. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni B1 na namba yako ya safari ni IP86159280. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-12 14:20:59', '2026-08-12 14:20:59'),
(16, 'africastalking', '+255789473209', 'Dear Kamillus Shishanga, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-08-14. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni C1 na namba yako ya safari ni WF73515189. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-12 15:34:38', '2026-08-12 15:34:38'),
(17, 'africastalking', '+255765553953', 'test message', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-12 16:25:16', '2026-08-12 16:25:16'),
(18, 'africastalking', '+255765553953', 'Dear Juma, Karibu Bish Bus, Utasafiri na basi namba T 571 DPH Linalotoka Dar es salaam Kwenda Mwanza Tarehe 2026-08-12. tafadhali wasili Dar es salaam angalau mapema saa 05:30 AM tayari kwa safari. Namba ya kiti chako ni A8 na namba yako ya safari ni LM68936889. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-12 16:28:25', '2026-08-12 16:28:25'),
(19, 'africastalking', '+255789473209', 'Dear Amina Mandali, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka General Kwenda Shekilango Tarehe 2026-08-13. tafadhali wasili General angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni L3 na namba yako ya safari ni OF66460960. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-13 08:03:25', '2026-08-13 08:03:25'),
(20, 'africastalking', '+255677123123', 'Dear Jesca Robert Nyagali, Karibu Thomas Express, Utasafiri na basi namba T 777 EMM Linalotoka Kimara temboni Kwenda Job Ndugai Bus Station Tarehe 2026-08-13. tafadhali wasili Kimara temboni angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A25 na namba yako ya safari ni SY13263100. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-13 16:31:56', '2026-08-13 16:31:56'),
(21, 'africastalking', '+255788999000', 'Dear Thoda peter Madeleka, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka General Kwenda Dar es salaam Tarehe 2026-08-13. tafadhali wasili General angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni C2 na namba yako ya safari ni SG24660569. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-13 17:07:08', '2026-08-13 17:07:08'),
(22, 'africastalking', '+255789200200', 'Dear Josephine Mapunda, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-08-14. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni D1 na namba yako ya safari ni FK27765707. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-14 11:13:39', '2026-08-14 11:13:39'),
(23, 'africastalking', '+255715020945', 'Dear Habiba Mohammed, Karibu Thomas Express, Utasafiri na basi namba T 777 EMM Linalotoka Nanenane Kwenda Kimara temboni Tarehe 2026-08-16. tafadhali wasili Nanenane angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A31 na namba yako ya safari ni IB78451360. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-14 16:22:53', '2026-08-14 16:22:53'),
(24, 'africastalking', '+255789555444', 'Habari Peter George Masihara, mzigo wako PCL-FK87MP umesajiliwa na Thomas Express. Mpokeaji: Thomas Chizi. Kufuatilia: PCL-FK87MP.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-15 03:50:20', '2026-08-15 03:50:20'),
(25, 'africastalking', '+255677666777', 'Dear Joyce Mlembuka, Karibu Thomas Express, Utasafiri na basi namba T 777 EMM Linalotoka Nanenane Kwenda Kimara temboni Tarehe 2026-08-16. tafadhali wasili Nanenane angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A12 na namba yako ya safari ni TI46460916. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-15 05:39:23', '2026-08-15 05:39:23'),
(26, 'africastalking', '+255789555444', 'Mzigo PCL-FK87MP umeondoka (Thomas Express). Dereva: Johnson Gabba, simu: 0628042409.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 15:02:59', '2026-08-16 15:02:59'),
(27, 'africastalking', '+33071553803', 'Mpendwa Abdul Bunju, mzigo nambari PCL-H3PZND umepokelewa na Bish Bus hapa Dar es salaam tayari kusafirishwa kuelekea Abdul Bunju. Utapokea taarifa baada ya kuwasili.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 19:51:03', '2026-08-16 19:51:03'),
(28, 'africastalking', '+33071553803', 'Habari Abdul Bunju, mzigo wako PCL-H3PZND umesajiliwa na Bish Bus. Mpokeaji: Abdul Bunju. Kufuatilia: PCL-H3PZND.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 19:51:03', '2026-08-16 19:51:03'),
(29, 'africastalking', '+33071553803', 'Mzigo PCL-H3PZND umeondoka (Bish Bus). Dereva: Thomas Chizi, simu: 0715553803.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 19:53:01', '2026-08-16 19:53:01'),
(30, 'africastalking', '+33071553803', 'Mzigo PCL-H3PZND umeondoka (Bish Bus). Dereva: Thomas Chizi, simu: 0715553803.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 19:53:01', '2026-08-16 19:53:01'),
(31, 'africastalking', '+33071553803', 'Mpendwa Abdul Bunju, mzigo PCL-H3PZND umefika. Tafadhali uje ukachukue kwa Bish Bus. Lete nambari ya ufuatiliaji.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 19:53:23', '2026-08-16 19:53:23'),
(32, 'africastalking', '+255628042409', 'Dear ibrahim, Karibu Thomas Express, Utasafiri na basi namba T 777 EMM Linalotoka Dodoma Kwenda Shekilango Tarehe 2026-08-18. tafadhali wasili Dodoma angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A12 na namba yako ya safari ni VA26144457. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 19:56:17', '2026-08-16 19:56:17'),
(33, 'africastalking', '+33071553803', 'Mzigo PCL-H3PZND umepokelewa na Abdul Bunju.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 19:57:16', '2026-08-16 19:57:16'),
(34, 'africastalking', '+255765553953', 'Dear Abdul Bunju, Karibu Bish Bus, Utasafiri na basi namba T 571 DPH Linalotoka Dar es salaam Kwenda Mwanza Tarehe 2026-08-16. tafadhali wasili Dar es salaam angalau mapema saa 05:30 AM tayari kwa safari. Namba ya kiti chako ni A4 na namba yako ya safari ni VW01352867. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 20:13:58', '2026-08-16 20:13:58'),
(35, 'africastalking', '+255715020945', 'Dear Rhoda Peter Kadadaa, Karibu Thomas Express, Utasafiri na basi namba T 777 EMM Linalotoka Kimara temboni Kwenda Job Ndugai Bus Station Tarehe 2026-08-19. tafadhali wasili Kimara temboni angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A21 na namba yako ya safari ni JK90434811. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 20:19:30', '2026-08-16 20:19:30'),
(36, 'africastalking', '+255715020945', 'Dear Prison Kabelega, Karibu Thomas Express, Utasafiri na basi namba T 777 EMM Linalotoka Shekilango Kwenda General Tarehe 2026-08-17. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A8 na namba yako ya safari ni IX46423683. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 20:26:18', '2026-08-16 20:26:18'),
(37, 'africastalking', '+255765553953', 'Dear Juma Abdul, Karibu Bish Bus, Utasafiri na basi namba T 571 DPH Linalotoka Dar es salaam Kwenda Mwanza Tarehe 2026-08-16. tafadhali wasili Dar es salaam angalau mapema saa 05:30 AM tayari kwa safari. Namba ya kiti chako ni A3 na namba yako ya safari ni RP59814852. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 20:30:29', '2026-08-16 20:30:29'),
(38, 'africastalking', '+255789555444', 'Habari John Said Madaraka, mzigo wako PCL-AV0YUC umesajiliwa na Thomas Express. Mpokeaji: Thomas Chizi. Kufuatilia: PCL-AV0YUC.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 20:39:09', '2026-08-16 20:39:09'),
(39, 'africastalking', '+255789555444', 'Mzigo PCL-AV0YUC umeondoka (Thomas Express). Dereva: Johnson Gabba, simu: 0628042409.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 20:39:45', '2026-08-16 20:39:45'),
(40, 'africastalking', '+255789555444', 'Mzigo PCL-AV0YUC umepokelewa na Thomas Chizi.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 20:48:14', '2026-08-16 20:48:14'),
(41, 'africastalking', '+255765553953', 'Mpendwa Alnas Abdul, mzigo nambari PCL-2GAD8E umepokelewa na Bish Bus hapa Dar es salaam tayari kusafirishwa kuelekea Kibangu. Utapokea taarifa baada ya kuwasili.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 21:07:40', '2026-08-16 21:07:40'),
(42, 'africastalking', '+255715553803', 'Habari Abdul Bunju, mzigo wako PCL-2GAD8E umesajiliwa na Bish Bus. Mpokeaji: Alnas Abdul. Kufuatilia: PCL-2GAD8E.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 21:07:41', '2026-08-16 21:07:41'),
(43, 'africastalking', '+255715553803', 'Mzigo PCL-2GAD8E umepokelewa na Alnas Abdul.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 21:08:13', '2026-08-16 21:08:13'),
(44, 'africastalking', '+255710909090', 'Dear Ashfaina Abdul, Karibu Bish Bus, Utasafiri na basi namba T 571 DPH Linalotoka Dar es salaam Kwenda Mwanza Tarehe 2026-08-16. tafadhali wasili Dar es salaam angalau mapema saa 05:30 AM tayari kwa safari. Namba ya kiti chako ni A1 na namba yako ya safari ni NI47110897. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 22:04:14', '2026-08-16 22:04:14'),
(45, 'africastalking', '+255715020945', 'Dear Anna Assenga, Karibu Thomas Express, Utasafiri na basi namba T 777 EMM Linalotoka General Kwenda Mbezi maguguli Tarehe 2026-08-18. tafadhali wasili General angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A7 na namba yako ya safari ni SH42945199. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 22:30:35', '2026-08-16 22:30:35'),
(46, 'africastalking', '+255715020945', 'Dear Abdallah Mohammed Milanzi, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-08-18. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni C1 na namba yako ya safari ni AY26333944. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 22:42:24', '2026-08-16 22:42:24'),
(47, 'africastalking', '+255789473209', 'Dear Neema John Chambo, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-08-18. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni C2 na namba yako ya safari ni RE45390407. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 23:18:46', '2026-08-16 23:18:46'),
(48, 'africastalking', '+255765553953', 'Mpendwa Masanja Ntebela, mzigo nambari PCL-ART0TN umepokelewa na Thomas Express hapa Dar es salaam tayari kusafirishwa kuelekea Thomas Express office, Dodoma.. Utapokea taarifa baada ya kuwasili.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 23:41:53', '2026-08-16 23:41:53'),
(49, 'africastalking', '+255715020945', 'Habari Joseph Mpondomoko, mzigo wako PCL-ART0TN umesajiliwa na Thomas Express. Mpokeaji: Masanja Ntebela. Kufuatilia: PCL-ART0TN.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 23:41:53', '2026-08-16 23:41:53'),
(50, 'africastalking', '+255715020945', 'Mzigo PCL-ART0TN umepokelewa na Masanja Ntebela.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 23:46:34', '2026-08-16 23:46:34'),
(51, 'africastalking', '+255765553953', 'Mpendwa Alnas Abdul, mzigo nambari PCL-8UWXER umepokelewa na Thomas Express hapa Dodoma tayari kusafirishwa kuelekea Kibangu, External. Utapokea taarifa baada ya kuwasili.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 23:57:42', '2026-08-16 23:57:42'),
(52, 'africastalking', '+255715553803', 'Habari Abdul Bunju, mzigo wako PCL-8UWXER umesajiliwa na Thomas Express. Mpokeaji: Alnas Abdul. Kufuatilia: PCL-8UWXER.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 23:57:43', '2026-08-16 23:57:43'),
(53, 'africastalking', '+255765553953', 'Mzigo PCL-8UWXER umeondoka (Thomas Express). Dereva: Johnson Gabba, simu: 0628042409.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 23:59:56', '2026-08-16 23:59:56'),
(54, 'africastalking', '+255715553803', 'Mzigo PCL-8UWXER umeondoka (Thomas Express). Dereva: Johnson Gabba, simu: 0628042409.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-16 23:59:57', '2026-08-16 23:59:57'),
(55, 'africastalking', '+255718191919', 'Dear Assh, Karibu Bish Bus, Utasafiri na basi namba T 571 DPH Linalotoka Dar es salaam Kwenda Mwanza Tarehe 2026-08-16. tafadhali wasili Dar es salaam angalau mapema saa 05:30 AM tayari kwa safari. Namba ya kiti chako ni A4 na namba yako ya safari ni EU42334306. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-17 00:26:01', '2026-08-17 00:26:01'),
(56, 'africastalking', '+33071553803', 'Mpendwa Abdul Bunju, mzigo nambari PCL-YL0QBS umepokelewa na Thomas Express hapa Dar es salaam tayari kusafirishwa kuelekea Abdul Bunju. Utapokea taarifa baada ya kuwasili.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-17 00:40:35', '2026-08-17 00:40:35'),
(57, 'africastalking', '+25571553803', 'Habari Abdul Bunju, mzigo wako PCL-YL0QBS umesajiliwa na Thomas Express. Mpokeaji: Abdul Bunju. Kufuatilia: PCL-YL0QBS.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-17 00:40:37', '2026-08-17 00:40:37'),
(58, 'africastalking', '+33071553803', 'Mzigo PCL-YL0QBS umeondoka (Thomas Express). Dereva: Masabu Silayo, simu: 0789473209.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-17 00:40:48', '2026-08-17 00:40:48'),
(59, 'africastalking', '+25571553803', 'Mzigo PCL-YL0QBS umeondoka (Thomas Express). Dereva: Masabu Silayo, simu: 0789473209.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-17 00:40:49', '2026-08-17 00:40:49'),
(60, 'africastalking', '+33071553803', 'Mpendwa Abdul Bunju, mzigo PCL-YL0QBS umefika. Tafadhali uje ukachukue kwa Thomas Express. Lete nambari ya ufuatiliaji.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-17 00:40:55', '2026-08-17 00:40:55'),
(61, 'africastalking', '+25571553803', 'Mzigo PCL-YL0QBS umepokelewa na Abdul Bunju.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-17 00:41:52', '2026-08-17 00:41:52'),
(62, 'africastalking', '+255765553953', 'Mpendwa Alnas Abdul, mzigo PCL-8UWXER umefika. Tafadhali uje ukachukue kwa Thomas Express. Lete nambari ya ufuatiliaji.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-17 20:17:22', '2026-08-17 20:17:22'),
(63, 'africastalking', '+255715553803', 'Mzigo PCL-8UWXER umepokelewa na Alnas Abdul.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-17 20:19:02', '2026-08-17 20:19:02'),
(64, 'africastalking', '+255789473209', 'Dear Joyce Ninahaja, Karibu Thomas Express, Utasafiri na basi namba T 777 EMM Linalotoka Nanenane Kwenda Kimara temboni Tarehe 2026-08-20. tafadhali wasili Nanenane angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A23 na namba yako ya safari ni CG63508255. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-20 12:27:44', '2026-08-20 12:27:44'),
(65, 'africastalking', '+255715020945', 'Dear customer, we are pleased to inform you that we have created a discount coupon for you. Use code: 9705 to enjoy a discount of 10% on your next booking. Thank you for choosing our service!', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-20 16:11:16', '2026-08-20 16:11:16'),
(66, 'africastalking', '+255749343101', 'Dear Assenga, Karibu Thomas Express, Utasafiri na basi namba T 777 EMM Linalotoka Kimara temboni Kwenda Job Ndugai Bus Station Tarehe 2026-08-21. tafadhali wasili Kimara temboni angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A21 na namba yako ya safari ni GS75925755. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-21 21:24:25', '2026-08-21 21:24:25'),
(67, 'africastalking', '+255788474747', 'Dear Sarah Moses Kiria, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-09-07. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A2 na namba yako ya safari ni QF63950131. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-26 01:49:16', '2026-08-26 01:49:16'),
(68, 'africastalking', '+255715020945', 'Dear Eliada Samson Mayunga, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-09-07. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A3 na namba yako ya safari ni CU91995088. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-26 09:15:47', '2026-08-26 09:15:47'),
(69, 'africastalking', '+255789473209', 'Dear Maimuna Salum, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-08-26. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni B1 na namba yako ya safari ni DS82734278. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-26 09:39:39', '2026-08-26 09:39:39'),
(70, 'africastalking', '+255715020945', 'Dear Jacqueline Maliamungu Mnyasi, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-09-07. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni A1 na namba yako ya safari ni LB02341704. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-27 00:18:50', '2026-08-27 00:18:50'),
(71, 'africastalking', '+255788888888', 'Dear Penina Lameck Mtenga, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-09-07. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni D1 na namba yako ya safari ni NA86148158. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-27 16:39:56', '2026-08-27 16:39:56'),
(72, 'africastalking', '+255765553953', 'Mpendwa Abdu Juma Bunju, mzigo nambari PCL-VEUYNM umepokelewa na Thomas Express hapa Dodoma tayari kusafirishwa kuelekea Dar es salaam. Utapokea taarifa baada ya kuwasili.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-27 23:34:24', '2026-08-27 23:34:24'),
(73, 'africastalking', '+255715020945', 'Habari Thomas Chizi, mzigo wako PCL-VEUYNM umesajiliwa na Thomas Express. Mpokeaji: Abdu Juma Bunju. Kufuatilia: PCL-VEUYNM.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-27 23:34:25', '2026-08-27 23:34:25'),
(74, 'africastalking', '+255765553953', 'Mzigo PCL-VEUYNM umeondoka (Thomas Express). Dereva: Johnson Gabba, simu: 0628042409.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-27 23:34:44', '2026-08-27 23:34:44'),
(75, 'africastalking', '+255715020945', 'Mzigo PCL-VEUYNM umeondoka (Thomas Express). Dereva: Johnson Gabba, simu: 0628042409.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-27 23:34:44', '2026-08-27 23:34:44'),
(76, 'africastalking', '+255715020945', 'Delivery: mzigo PCL-VEUYNM kwa Abdu Juma Bunju, anwani: Dar es salaam, simu: +255765553953.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-27 23:34:55', '2026-08-27 23:34:55'),
(77, 'africastalking', '+255765553953', 'Mzigo PCL-VEUYNM umefika na unawasili kwa uwasilishaji. Rider: Raim Mussa +255715020945', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-27 23:34:56', '2026-08-27 23:34:56'),
(78, 'africastalking', '+255715020945', 'Dear customer, we are pleased to inform you that we have created a discount coupon for you. Use code: 1234 to enjoy a discount of 10% on your next booking. Thank you for choosing our service!', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-30 16:54:34', '2026-08-30 16:54:34'),
(79, 'africastalking', '+255788888811', 'Dear Asha Simon Marandu, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-09-07. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni B4 na namba yako ya safari ni MF61171230. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-31 02:16:19', '2026-08-31 02:16:19'),
(80, 'africastalking', '+255715020945', 'Mpendwa Jackson Malabo, mzigo nambari PCL-ED1R6F umepokelewa na Thomas Express hapa Dar es salaam tayari kusafirishwa kuelekea kariakoo. Utapokea taarifa baada ya kuwasili.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-31 02:27:42', '2026-08-31 02:27:42'),
(81, 'africastalking', '+255715020945', 'Habari Thomas Chizi, mzigo wako PCL-ED1R6F umesajiliwa na Thomas Express. Mpokeaji: Jackson Malabo. Kufuatilia: PCL-ED1R6F.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-31 02:27:42', '2026-08-31 02:27:42'),
(82, 'africastalking', '+255715020945', 'Mzigo PCL-ED1R6F umeondoka (Thomas Express). Dereva: Masabu Silayo, simu: 0789473209.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-31 02:27:47', '2026-08-31 02:27:47'),
(83, 'africastalking', '+255715020945', 'Mzigo PCL-ED1R6F umeondoka (Thomas Express). Dereva: Masabu Silayo, simu: 0789473209.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-31 02:27:48', '2026-08-31 02:27:48'),
(84, 'africastalking', '+255657652216', 'Delivery: mzigo PCL-ED1R6F kwa Jackson Malabo, anwani: kariakoo, simu: +255715020945.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-31 02:29:46', '2026-08-31 02:29:46'),
(85, 'africastalking', '+255715020945', 'Mzigo PCL-ED1R6F umefika na unawasili kwa uwasilishaji. Rider: John Kubatu 0657652216', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-31 02:29:47', '2026-08-31 02:29:47'),
(86, 'africastalking', '+255789999991', 'Dear Samson Madhambi, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-09-07. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni F2 na namba yako ya safari ni UW91157873. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-08-31 03:24:47', '2026-08-31 03:24:47'),
(87, 'africastalking', '+255628042409', 'HISGC: New special hire SH-20260901-002 on Thomas minibus services. Open the Driver app to Accept or Decline.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-01 10:03:27', '2026-09-01 10:03:27'),
(88, 'africastalking', '+255715020945', 'Mpendwa Thomas Paul Chizi, Karibu Thomas Paul Chizi, wewe na wenzako mtasafiri na basi namba T 257 UTT kutoka Morogoro, Coastal Zone, Tanzania Kwenda Moshi, Moshi Urban, Kilimanjaro, Northern Zone, 25107, Tanzania Tarehe 01/09/2026. Abiria wote mnaombwa kuwasili Morogoro, Coastal Zone, Tanzania angalau saa 05:31 AM tayari kwa safari. namba yako ni SH-20260901-002. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-01 10:03:28', '2026-09-01 10:03:28'),
(89, 'africastalking', '+255628042409', 'HISGC: New special hire SH-20260901-003 on Thomas minibus services. Open the Driver app to Accept or Decline.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-01 10:17:26', '2026-09-01 10:17:26'),
(90, 'africastalking', '+255715020945', 'Mpendwa Thomas Paul Chizi, Karibu Thomas Paul Chizi, wewe na wenzako mtasafiri na basi namba T 257 UTT kutoka Dar es Salaam, Coastal Zone, Tanzania Kwenda Kahama, Kahama Urban, Shinyanga, Lake Zone, Tanzania Tarehe 02/09/2026. Abiria wote mnaombwa kuwasili Dar es Salaam, Coastal Zone, Tanzania angalau saa 05:35 AM tayari kwa safari. namba yako ni SH-20260901-003. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-01 10:17:26', '2026-09-01 10:17:26'),
(91, 'africastalking', '+255779779779', 'Dear Diana Kasole, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-09-07. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni C1 na namba yako ya safari ni UA35686570. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-01 13:50:17', '2026-09-01 13:50:17'),
(92, 'africastalking', '+255773773773', 'Dear Simon Kundecha, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda Dodoma Tarehe 2026-09-01. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni B1 na namba yako ya safari ni XC15355170. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-01 13:55:46', '2026-09-01 13:55:46'),
(93, 'africastalking', '+255788667766', 'Dear Salum Issa Nanganga, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Shekilango Kwenda General Tarehe 2026-09-07. tafadhali wasili Shekilango angalau mapema saa 09:30 PM tayari kwa safari. Namba ya kiti chako ni E3 na namba yako ya safari ni OB39756277. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-03 12:51:55', '2026-09-03 12:51:55'),
(94, 'africastalking', '+255715020945', 'Dear John Kilabo, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Moshi mjini Kwenda Shekilango Tarehe 2026-09-14. tafadhali wasili Moshi mjini angalau mapema saa 09:30 AM tayari kwa safari. Namba ya kiti chako ni C1 na namba yako ya safari ni DM82681856. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-14 13:00:11', '2026-09-14 13:00:11'),
(95, 'africastalking', '+255715020945', 'Dear Rhoda Peter, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Kiboriloni Kwenda Bunju Tarehe 2026-09-14. tafadhali wasili Kiboriloni angalau mapema saa 09:30 AM tayari kwa safari. Namba ya kiti chako ni D1 na namba yako ya safari ni PH43046616. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-14 16:00:51', '2026-09-14 16:00:51'),
(96, 'africastalking', '+255788888999', 'Dear Joyce Mangu, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Kia Kwenda Shekilango Tarehe 2026-09-16. tafadhali wasili Kia angalau mapema saa 09:30 AM tayari kwa safari. Namba ya kiti chako ni B1 na namba yako ya safari ni LA98894418. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-16 16:21:20', '2026-09-16 16:21:20'),
(97, 'africastalking', '+255755555666', 'Dear Domina Albert Mabena, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Moshi mjini Kwenda Dar es salaam Tarehe 2026-09-20. tafadhali wasili Moshi mjini angalau mapema saa 09:30 AM tayari kwa safari. Namba ya kiti chako ni B3 na namba yako ya safari ni MZ76668262. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-17 16:39:19', '2026-09-17 16:39:19'),
(98, 'africastalking', '+255715020945', 'Mpendwa James Wanhu, mzigo nambari PCL-OU9RFI umepokelewa na Thomas Express hapa Arusha tayari kusafirishwa kuelekea Shekilango, office no. 56. Utapokea taarifa baada ya kuwasili.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-18 10:56:39', '2026-09-18 10:56:39'),
(99, 'africastalking', '+255765553953', 'Habari Abdu Juma Bunju, mzigo wako PCL-OU9RFI umesajiliwa na Thomas Express. Mpokeaji: James Wanhu. Kufuatilia: PCL-OU9RFI.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-18 10:56:40', '2026-09-18 10:56:40'),
(100, 'africastalking', '+255715020945', 'Mzigo PCL-OU9RFI umeondoka (Thomas Express). Dereva: Masabu Silayo, simu: 0789473209.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-18 11:15:10', '2026-09-18 11:15:10'),
(101, 'africastalking', '+255765553953', 'Mzigo PCL-OU9RFI umeondoka (Thomas Express). Dereva: Masabu Silayo, simu: 0789473209.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-18 11:15:11', '2026-09-18 11:15:11'),
(102, 'africastalking', '+255715020945', 'Mpendwa James Wanhu, mzigo PCL-OU9RFI umefika. Tafadhali uje ukachukue kwa Thomas Express. Lete nambari ya ufuatiliaji.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-18 11:15:48', '2026-09-18 11:15:48'),
(103, 'africastalking', '+255765553953', 'Mzigo PCL-OU9RFI umepokelewa na James Wanhu.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-18 12:48:01', '2026-09-18 12:48:01'),
(104, 'africastalking', '+255655565176', 'Dear Aneth Jason Makota, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Njiapanda ya Himo Kwenda Bunju Tarehe 2026-09-20. tafadhali wasili Njiapanda ya Himo angalau mapema saa 09:30 AM tayari kwa safari. Namba ya kiti chako ni B2 na namba yako ya safari ni LD41269645. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-19 11:47:12', '2026-09-19 11:47:12'),
(105, 'africastalking', '+255715020945', 'Dear Rebeka Kandambili, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Kiboriloni Kwenda Mbezi Africana Tarehe 2026-09-22. tafadhali wasili Kiboriloni angalau mapema saa 09:30 AM tayari kwa safari. Namba ya kiti chako ni A1 na namba yako ya safari ni DL04227794. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-20 18:02:18', '2026-09-20 18:02:18'),
(106, 'africastalking', '+255655556556', 'Dear Kisa Mwaisoba, Karibu Thomas Express, Utasafiri na basi namba T 344 DFM Linalotoka Kia Kwenda Mbezi magufuli Tarehe 2026-09-22. tafadhali wasili Kia angalau mapema saa 09:30 AM tayari kwa safari. Namba ya kiti chako ni B1 na namba yako ya safari ni OC55008115. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.', NULL, 'failed', 'InvalidSenderId', NULL, NULL, NULL, '2026-09-21 12:02:48', '2026-09-21 12:02:48');

-- --------------------------------------------------------

--
-- Table structure for table `special_hire_orders`
--

CREATE TABLE `special_hire_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_code` varchar(20) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `coaster_id` bigint(20) UNSIGNED NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `pickup_location` varchar(255) NOT NULL,
  `pickup_latitude` decimal(10,8) DEFAULT NULL,
  `pickup_longitude` decimal(11,8) DEFAULT NULL,
  `dropoff_location` varchar(255) NOT NULL,
  `dropoff_latitude` decimal(10,8) DEFAULT NULL,
  `dropoff_longitude` decimal(11,8) DEFAULT NULL,
  `hire_date` date NOT NULL,
  `hire_time` time NOT NULL,
  `return_date` date DEFAULT NULL,
  `return_time` time DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `customer_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `passengers_count` int(11) NOT NULL DEFAULT 1,
  `distance_km` decimal(10,2) NOT NULL DEFAULT 0.00,
  `base_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `price_per_km` decimal(10,2) NOT NULL DEFAULT 0.00,
  `km_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `surcharge_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `surcharge_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_code` varchar(64) DEFAULT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_before_discount` decimal(12,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','refunded','refund_pending') NOT NULL DEFAULT 'pending',
  `order_status` enum('pending','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deposit_amount` decimal(12,2) DEFAULT NULL,
  `balance_amount` decimal(12,2) DEFAULT NULL,
  `deposit_paid_at` timestamp NULL DEFAULT NULL,
  `balance_paid_at` timestamp NULL DEFAULT NULL,
  `owner_accepted_at` timestamp NULL DEFAULT NULL,
  `passenger_seats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`passenger_seats`)),
  `clickpesa_deposit_ref` varchar(64) DEFAULT NULL,
  `clickpesa_balance_ref` varchar(64) DEFAULT NULL,
  `platform_commission_percent` decimal(5,2) DEFAULT NULL,
  `platform_commission_amount` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `special_hire_orders`
--

INSERT INTO `special_hire_orders` (`id`, `order_code`, `user_id`, `coaster_id`, `customer_name`, `customer_phone`, `customer_email`, `pickup_location`, `pickup_latitude`, `pickup_longitude`, `dropoff_location`, `dropoff_latitude`, `dropoff_longitude`, `hire_date`, `hire_time`, `return_date`, `return_time`, `purpose`, `customer_user_id`, `passengers_count`, `distance_km`, `base_price`, `price_per_km`, `km_amount`, `surcharge_percent`, `surcharge_amount`, `total_amount`, `discount_code`, `discount_amount`, `total_before_discount`, `payment_method`, `payment_status`, `order_status`, `notes`, `created_at`, `updated_at`, `deposit_amount`, `balance_amount`, `deposit_paid_at`, `balance_paid_at`, `owner_accepted_at`, `passenger_seats`, `clickpesa_deposit_ref`, `clickpesa_balance_ref`, `platform_commission_percent`, `platform_commission_amount`) VALUES
(19, 'SH-20260817-001', 25, 2, 'Abraham Manyanga', '+255789473209', 'chizithomas@gmail.com', 'Dar es salaam, Kinondani B', NULL, NULL, 'Bagamoyo, Nia njema', NULL, NULL, '2026-08-19', '08:00:00', '2026-08-20', '13:15:00', 'Sherehe ya arobaini', NULL, 30, 500.00, 0.00, 2500.00, 1250000.00, 0.00, 0.00, 1250000.00, NULL, 0.00, NULL, NULL, 'pending', 'pending', 'Please come on time at pick up point', '2026-08-17 17:17:08', '2026-08-17 17:17:08', 125000.00, 1125000.00, NULL, NULL, NULL, NULL, NULL, NULL, 5.00, NULL),
(20, 'SH-20260901-001', 25, 2, 'Joel Thomas', '+255715020945', 'chizithomas@gmail.com', 'Morogoro', NULL, NULL, 'Dar es salaam', NULL, NULL, '2026-09-01', '08:00:00', NULL, NULL, 'Holiday', NULL, 30, 500.00, 0.00, 2000.00, 1000000.00, 0.00, 0.00, 1000000.00, NULL, 0.00, NULL, NULL, 'pending', 'cancelled', NULL, '2026-09-01 09:27:19', '2026-09-01 09:28:48', 100000.00, 900000.00, NULL, NULL, NULL, NULL, NULL, NULL, 5.00, NULL),
(21, 'SH-20260901-002', 25, 2, 'Thomas Paul Chizi', '0715020945', 'customer-bus@hisgc.co.tz', 'Morogoro, Coastal Zone, Tanzania', -7.08429310, 37.42326860, 'Moshi, Moshi Urban, Kilimanjaro, Northern Zone, 25107, Tanzania', -3.34864560, 37.34352490, '2026-09-01', '06:01:00', NULL, NULL, NULL, 34, 30, 589.96, 0.00, 2000.00, 1179914.00, 0.00, 0.00, 1179914.00, NULL, 0.00, NULL, 'test_mode', 'paid', 'confirmed', NULL, '2026-09-01 10:03:27', '2026-09-01 10:03:27', 1179914.00, 0.00, '2026-09-01 10:03:27', NULL, '2026-09-01 10:03:27', NULL, 'TESTSH13T20260901060327', NULL, 5.00, 58995.70),
(22, 'SH-20260901-003', 25, 2, 'Thomas Paul Chizi', '0715020945', 'customer-bus@hisgc.co.tz', 'Dar es Salaam, Coastal Zone, Tanzania', -6.81608370, 39.28035830, 'Kahama, Kahama Urban, Shinyanga, Lake Zone, Tanzania', -3.82898770, 32.60115580, '2026-09-02', '06:05:00', '2026-09-02', NULL, NULL, 34, 3, 1020.36, 0.00, 2000.00, 2040711.80, 0.00, 0.00, 2040711.80, NULL, 0.00, NULL, 'test_mode', 'paid', 'confirmed', NULL, '2026-09-01 10:17:25', '2026-09-01 10:19:51', 2040711.80, 0.00, '2026-09-01 10:17:25', NULL, '2026-09-01 10:17:25', '[\"Joseph Kalai \\u00b7 0789473209\",\"Mariam Mbwana \\u00b7 0715020945\",\"Kibiriti Maembe \\u00b7 0753020945\"]', 'TESTSH14T20260901061725', NULL, 5.00, 102035.59);

-- --------------------------------------------------------

--
-- Table structure for table `special_hire_payment_intents`
--

CREATE TABLE `special_hire_payment_intents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_user_id` bigint(20) UNSIGNED NOT NULL,
  `coaster_id` bigint(20) UNSIGNED NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `amount` decimal(12,2) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `clickpesa_ref` varchar(64) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `special_hire_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `special_hire_payment_intents`
--

INSERT INTO `special_hire_payment_intents` (`id`, `customer_user_id`, `coaster_id`, `payload`, `amount`, `phone`, `clickpesa_ref`, `status`, `special_hire_order_id`, `expires_at`, `paid_at`, `created_at`, `updated_at`) VALUES
(13, 34, 2, '{\"coaster_id\":2,\"pickup_location\":\"Morogoro, Coastal Zone, Tanzania\",\"pickup_latitude\":-7.0842931,\"pickup_longitude\":37.4232686,\"dropoff_location\":\"Moshi, Moshi Urban, Kilimanjaro, Northern Zone, 25107, Tanzania\",\"dropoff_latitude\":-3.3486456,\"dropoff_longitude\":37.3435249,\"hire_date\":\"2026-09-01\",\"hire_time\":\"06:01\",\"return_date\":null,\"return_time\":null,\"passengers_count\":30,\"purpose\":null,\"notes\":null,\"distance_km\":589.957,\"total_amount\":1179914,\"customer_phone\":\"0715020945\"}', 1179914.00, '0715020945', 'TESTSH13T20260901060327', 'consumed', 21, '2026-09-01 10:48:27', '2026-09-01 10:03:27', '2026-09-01 10:03:27', '2026-09-01 10:03:27'),
(14, 34, 2, '{\"coaster_id\":2,\"pickup_location\":\"Dar es Salaam, Coastal Zone, Tanzania\",\"pickup_latitude\":-6.8160837,\"pickup_longitude\":39.2803583,\"dropoff_location\":\"Kahama, Kahama Urban, Shinyanga, Lake Zone, Tanzania\",\"dropoff_latitude\":-3.8289877,\"dropoff_longitude\":32.6011558,\"hire_date\":\"2026-09-02\",\"hire_time\":\"06:05\",\"return_date\":\"2026-09-02\",\"return_time\":null,\"passengers_count\":3,\"purpose\":null,\"notes\":null,\"distance_km\":1020.3559,\"total_amount\":2040711.8,\"customer_phone\":\"0715020945\"}', 2040711.80, '0715020945', 'TESTSH14T20260901061725', 'consumed', 22, '2026-09-01 11:02:25', '2026-09-01 10:17:25', '2026-09-01 10:17:25', '2026-09-01 10:17:25');

-- --------------------------------------------------------

--
-- Table structure for table `special_hire_pricing`
--

CREATE TABLE `special_hire_pricing` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `coaster_id` bigint(20) UNSIGNED NOT NULL,
  `base_price` decimal(12,2) NOT NULL DEFAULT 100000.00,
  `price_per_km` decimal(10,2) NOT NULL DEFAULT 2500.00,
  `min_km` int(11) NOT NULL DEFAULT 10,
  `weekend_surcharge_percent` decimal(5,2) NOT NULL DEFAULT 15.00,
  `night_surcharge_percent` decimal(5,2) NOT NULL DEFAULT 20.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `special_hire_pricing`
--

INSERT INTO `special_hire_pricing` (`id`, `coaster_id`, `base_price`, `price_per_km`, `min_km`, `weekend_surcharge_percent`, `night_surcharge_percent`, `created_at`, `updated_at`) VALUES
(1, 1, 0.00, 2500.00, 10, 15.00, 20.00, '2026-07-17 02:22:40', '2026-07-17 02:22:40'),
(2, 2, 0.00, 2000.00, 500, 15.00, 20.00, '2026-07-17 19:16:44', '2026-09-01 09:07:07'),
(3, 3, 250000.00, 2500.00, 5, 15.00, 20.00, '2026-07-17 19:31:09', '2026-07-17 19:31:09'),
(4, 4, 0.00, 2500.00, 10, 15.00, 20.00, '2026-07-17 20:16:54', '2026-07-17 20:16:54'),
(5, 5, 0.00, 2500.00, 10, 15.00, 20.00, '2026-07-17 20:39:28', '2026-07-17 20:39:28'),
(6, 6, 25000.00, 25000.00, 10, 15.00, 20.00, '2026-07-19 20:32:46', '2026-07-19 20:32:46'),
(7, 7, 25000.00, 25000.00, 10, 15.00, 20.00, '2026-07-19 20:33:08', '2026-07-19 20:33:08'),
(8, 8, 30000.00, 2500.00, 10, 15.00, 20.00, '2026-07-19 22:32:05', '2026-07-19 22:32:05'),
(9, 9, 3000.00, 2500.00, 10, 15.00, 20.00, '2026-07-20 14:06:52', '2026-07-20 14:06:52');

-- --------------------------------------------------------

--
-- Table structure for table `special_hire_withdrawal_requests`
--

CREATE TABLE `special_hire_withdrawal_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_method` varchar(100) NOT NULL,
  `payment_number` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stend`
--

CREATE TABLE `stend` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `from` varchar(255) DEFAULT NULL,
  `to` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_balance`
--

CREATE TABLE `system_balance` (
  `id` int(11) NOT NULL,
  `campany_id` int(11) DEFAULT NULL,
  `booking_id` varchar(255) DEFAULT NULL,
  `balance` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_balance`
--

INSERT INTO `system_balance` (`id`, `campany_id`, `booking_id`, `balance`, `created_at`, `updated_at`) VALUES
(175, 3, 'SH42945199', 1800, '2026-08-16 22:30:33', '2026-08-16 18:30:33'),
(176, 3, 'AY26333944', 2000, '2026-08-16 22:42:23', '2026-08-16 18:42:23'),
(177, 3, 'RE45390407', 2000, '2026-08-16 23:18:45', '2026-08-16 19:18:45'),
(178, 5, 'EU42334306', 4050, '2026-08-17 00:26:00', '2026-08-16 20:26:00'),
(179, 3, 'CG63508255', 1800, '2026-08-20 12:27:43', '2026-08-20 08:27:43'),
(180, 3, 'GS75925755', 2000, '2026-08-21 21:24:25', '2026-08-21 17:24:25'),
(181, 3, 'QF63950131', 1800, '2026-08-26 01:49:16', '2026-08-25 21:49:16'),
(182, 3, 'CU91995088', 1800, '2026-08-26 09:15:46', '2026-08-26 05:15:46'),
(183, 3, 'DS82734278', 1800, '2026-08-26 09:39:39', '2026-08-26 05:39:39'),
(184, 3, 'LB02341704', 1800, '2026-08-27 00:18:50', '2026-08-26 20:18:50'),
(185, 3, 'NA86148158', 1800, '2026-08-27 16:39:56', '2026-08-27 12:39:56'),
(186, 3, 'MF61171230', 1800, '2026-08-31 02:16:19', '2026-08-30 22:16:19'),
(187, 3, 'UW91157873', 1620, '2026-08-31 03:24:46', '2026-08-30 23:24:46'),
(188, 3, 'UA35686570', 1800, '2026-09-01 13:50:16', '2026-09-01 09:50:16'),
(189, 3, 'XC15355170', 1800, '2026-09-01 13:55:45', '2026-09-01 09:55:45'),
(190, 3, 'OB39756277', 1800, '2026-09-03 12:51:54', '2026-09-03 08:51:54'),
(191, 3, 'DM82681856', 2000, '2026-09-14 13:00:10', '2026-09-14 09:00:10'),
(192, 3, 'PH43046616', 2000, '2026-09-14 16:00:51', '2026-09-14 12:00:51'),
(193, 3, 'LA98894418', 1800, '2026-09-16 16:21:19', '2026-09-16 12:21:19'),
(194, 3, 'MZ76668262', 2000, '2026-09-17 16:39:19', '2026-09-17 12:39:19'),
(195, 3, 'LD41269645', 1800, '2026-09-19 11:47:11', '2026-09-19 07:47:11'),
(196, 3, 'DL04227794', 2000, '2026-09-20 18:02:17', '2026-09-20 14:02:17'),
(197, 3, 'OC55008115', 1800, '2026-09-21 12:02:47', '2026-09-21 08:02:47');

-- --------------------------------------------------------

--
-- Table structure for table `temp_wallets`
--

CREATE TABLE `temp_wallets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `user_key` varchar(255) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `campany_id` int(11) DEFAULT 0,
  `user_id` int(11) DEFAULT 0,
  `payment_method` varchar(255) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `payment_number` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT 'pending',
  `reference_number` varchar(255) DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL,
  `vender_id` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `verification_code` varchar(255) DEFAULT NULL,
  `verification_expires_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `status` enum('accept','cancel','pending') NOT NULL DEFAULT 'pending',
  `campany_id` int(11) DEFAULT NULL,
  `failed_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `special_hire_platform_percent` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `role`, `email_verified_at`, `verification_code`, `verification_expires_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token`, `created_at`, `updated_at`, `contact`, `status`, `campany_id`, `failed_attempts`, `locked_until`, `special_hire_platform_percent`) VALUES
(1, 'System Admin', 'admin@gmail.com', NULL, 'admin', '2026-05-04 00:56:32', NULL, NULL, '$2y$12$XJ.HnLxebdSQJ24be6mHvedGCv5.MuOaIxdWQrY9l/AnXVdGZvJhu', 'eyJpdiI6Inl6MW9GR29XOG9KNXdyY2RvazdTdWc9PSIsInZhbHVlIjoiajhSczlsM25LMEozcCsyU3dvSnl6Z0pJVUU0a01pY2VpS2VkTDh4dGo3RT0iLCJtYWMiOiJlYWQyZWM4ODI0NDRhMzQyY2MxYWIwY2Q4NGFmMWI3YTE0Y2ViNGMyNmIyOTcxN2MwMWExYmVjNTBmOWQwYmVhIiwidGFnIjoiIn0=', 'eyJpdiI6InZyc2NsU1V6cG1SQVlVeWtUdExzcmc9PSIsInZhbHVlIjoiMndSV3RLWllkbktJc0xmVk03eTZRejJidFRCcDZXYjhHZ1ZUV05YK2h6TU1qakU2YjRaaGhHMnB5NlhjQVU2VklMRkJ3TXVldjQranRQZS90OUFKQTE1QzRrUCt1UzIxbytGNHpjUXRTcGZpTUJRQ3dtVXZRM01CaGIyWVZTVkpwdElBR1k0M0k2R2Z5Q3VpZTJ5WWpUSkZpeUFmWktnbTZXOGdTT1lTRWZCSEMvdVVFZGltZjlod2ZVcWZYWnNtbmUvQVJONEpJbCtLWkNJcGxYM1dEQ2pMS0lhL0owc2ZEZXo2eUJ5ZVpzMGZXalhYaHY0SUIvZTRzelJUK0xZQTdZRkpPME9GSVNGVU42cTBuT0RNSHc9PSIsIm1hYyI6Ijg4MGIxN2JhYzk3NGM0NDM3ZDkwZGRjZTIyNTNlYWM5ZTcwNDkxMzIwMzI1NmMzMjY2MTVmNmI1NGNkNDgzNjgiLCJ0YWciOiIifQ==', '2026-07-01 07:30:57', NULL, '2026-05-04 00:56:32', '2026-09-03 03:11:10', '0', 'accept', NULL, 0, NULL, 0.00),
(15, 'Christina Ekarist', 'christina@hisgc.co.tz', NULL, 'admin', NULL, NULL, NULL, '$2y$12$i13NO/Q0SJnLtDx1JkRUoOwjiRgOe8MAxUAw.q3/0H5z1Ly6n3Gi.', NULL, NULL, NULL, 'DVNO6hQ5rcXcwN6HOdH2FNtOIIsfE1zzWSRo44568U5PuaJWQf5PD4bzwAfd', '2026-07-16 21:06:07', '2026-07-16 21:06:07', '717332744', 'accept', NULL, 0, NULL, 0.00),
(16, 'Kenedy Jacob', 'kenedyjacob001@gmail.com', NULL, 'special_hire', NULL, NULL, NULL, '$2y$12$9uOvi4Lx9RfLcKiAIu74xOcZqBjeh/D8N3L1Yw3OXNME9vQhFynp6', NULL, NULL, NULL, NULL, '2026-07-17 01:42:16', '2026-07-23 20:46:35', '762858296', 'accept', NULL, 0, NULL, 5.00),
(17, 'Ibra', 'ibra@gmail.com', '+255628042409', 'customer', NULL, NULL, NULL, '$2y$12$yqn5bNaKB2Mkw7RFyFBKee0rr6gMI/T8Z0.gbKowXCvilk3grxbgy', NULL, NULL, NULL, NULL, '2026-07-17 02:12:19', '2026-07-17 02:12:19', '0', 'accept', NULL, 0, NULL, 0.00),
(18, 'kibosho', 'kibosho@gmail.com', NULL, 'special_hire', NULL, NULL, NULL, '$2y$12$AqAthfJHsPY.jy/fwGISve1lRSCWevcKklixUNxqG/UihEjFDOMXe', NULL, NULL, NULL, NULL, '2026-07-17 02:14:38', '2026-07-17 02:14:38', '628042409', 'accept', NULL, 0, NULL, 0.00),
(19, 'ibrahim', 'driver@gmail.com', NULL, 'driver', NULL, NULL, NULL, '$2y$12$1RclcyE32O9aKEy6qcIXy..wr9YoXhMDB/zVzMdQr4ZxDRuj9kZbe', NULL, NULL, NULL, NULL, '2026-07-17 02:22:40', '2026-07-17 04:01:34', '628042409', 'pending', NULL, 0, NULL, 0.00),
(20, 'Thomas Chizi', 'busowner@hisgc.co.tz', NULL, 'bus_campany', NULL, NULL, NULL, '$2y$12$ov4sb7NDvcBbSbmx4oEx..oPz6SW.crRb5ethc7Vw91fCCmxB0PMK', NULL, NULL, NULL, NULL, '2026-07-17 03:14:33', '2026-08-16 20:12:25', '715020945', 'accept', NULL, 0, NULL, 0.00),
(21, 'Francis oswald assenga', 'assenga418@gmail.com', NULL, 'vender', NULL, NULL, NULL, '$2y$12$VeF9QITjgV4Ttj1fqTMjZuyJUqpedI1tdSaE8S0iv.tC52QZozqSi', NULL, NULL, NULL, NULL, '2026-07-17 10:58:28', '2026-07-17 10:58:28', '749343101', 'accept', NULL, 0, NULL, 0.00),
(22, 'Sumaye Daniel saruni', 'sumayedaniel2025@gmail.com', NULL, 'special_hire', NULL, NULL, NULL, '$2y$12$y0pANyeHSDdi8i3S6Vad8.MBgAovwjDzuHzUagmsttcm3j5x8cJN6', NULL, NULL, NULL, NULL, '2026-07-17 11:12:07', '2026-07-17 11:12:07', '767675461', 'accept', NULL, 0, NULL, 0.00),
(23, 'KELVIN GEORGE MPOGOLE', 'kpslayovi@gmail.com', NULL, 'bus_campany', NULL, NULL, NULL, '$2y$12$LHwWi1DUL8BZNTKTImi91OyLnhclu56Krbo2JZHcKY9433q.1ETQe', NULL, NULL, NULL, NULL, '2026-07-17 15:29:05', '2026-07-22 15:10:51', '786948007', 'accept', NULL, 0, NULL, 0.00),
(24, 'KELVIN GEORGE MPOGOLE', 'mpogolemimikelvin@yahoo.com', NULL, 'special_hire', NULL, NULL, NULL, '$2y$12$fKcVSgZ5BQvAEKEcS.Pmq.6Zk4cMwn1qjU36r2n/XJAJYdgv4M7ka', NULL, NULL, NULL, NULL, '2026-07-17 15:33:21', '2026-07-17 15:33:21', '786948007', 'accept', NULL, 0, NULL, 0.00),
(25, 'Thomas Paul Chizi', 'specialhire-o@hisgc.co.tz', NULL, 'special_hire', NULL, NULL, NULL, '$2y$12$YGhGiUOcNbmjulXfvSUi3.SNRW0uTGRUg1DDZoyQqC3njjit3dXPy', NULL, NULL, NULL, NULL, '2026-07-17 18:36:47', '2026-07-23 19:37:21', '0789473209', 'accept', NULL, 0, NULL, 5.00),
(26, 'Masabu Silayo', 'specialhire-driver@hisgc.co.tz', NULL, 'driver', NULL, NULL, NULL, '$2y$12$VO/RkbW8vAYwEN3gRl2/KuPmg.T/8puCUSQjp26/xzxQSdtQ0BP1a', NULL, NULL, NULL, NULL, '2026-07-17 19:16:44', '2026-07-17 19:16:44', '0628042409', 'pending', NULL, 0, NULL, 0.00),
(27, 'Brian Shayo', 'brianshayo420@gmail.com', NULL, 'vender', NULL, NULL, NULL, '$2y$12$F9xxv2/fVmgxNJijTHPQNunObHcfoUsIk0WHNkAS47zCn6Xgs62rK', NULL, NULL, NULL, NULL, '2026-07-17 19:17:53', '2026-07-17 19:17:53', '0628191514', 'accept', NULL, 0, NULL, 0.00),
(28, 'John Doe', 'john@doe.com', NULL, 'driver', NULL, NULL, NULL, '$2y$12$y7Qecyq4qN0eLxvkZwZijO4yzIxAHMjAS80znmAhdjozeiwVpEos2', NULL, NULL, NULL, NULL, '2026-07-17 19:31:09', '2026-07-17 19:31:09', '0786948007', 'pending', NULL, 0, NULL, 0.00),
(29, 'Alnas Abdul', 'monicamruma20@gmail.com', NULL, 'special_hire', NULL, NULL, NULL, '$2y$12$lJczDf9DZ2o9ZZ4GYpi0d.z6pF2eXOJbnbEH9g2Hg9shiTk5NPhoG', NULL, NULL, NULL, NULL, '2026-07-17 20:14:51', '2026-07-18 03:26:59', '0765553953', 'accept', NULL, 0, NULL, 5.00),
(30, 'Thomas Chizi', 'abdul@gmail.com', NULL, 'driver', NULL, NULL, NULL, '$2y$12$V.2VgVPTrzyByf15341mzOnf6d9vXfOxBzFDNslD2Q0Qr7LjJOrPC', NULL, NULL, NULL, NULL, '2026-07-17 20:16:54', '2026-07-17 20:16:54', '0715553803', 'pending', NULL, 0, NULL, 0.00),
(31, 'Alnas Abdul', 'monicamruma40@gmail.com', '+255765553953', 'driver', NULL, NULL, NULL, '$2y$12$R.Bgnmv4Gy1Dbt81/cCqbe4XKpTiKpKxCB51A6fw1KNmmJnjASlGO', NULL, NULL, NULL, NULL, '2026-07-17 20:39:55', '2026-07-17 20:39:55', '+255765553953', 'accept', NULL, 0, NULL, 0.00),
(32, 'Rukiko Benjamini Chitundu', 'rukikob@gmail.com', NULL, 'special_hire', NULL, NULL, NULL, '$2y$12$HC9p6qyBrZ4XJ3bUNwdoUe2fWCqbTQ7uTuL0qG6L/x/.MFXYbXN7i', NULL, NULL, NULL, 'YAHcySc5DXrVoJkGGzEX92LmU7bc246PXb0EssTiKt6YNRGlAjZmKwDhAEvu', '2026-07-17 22:23:21', '2026-07-17 22:23:21', '0758638868', 'accept', NULL, 0, NULL, 0.00),
(33, 'ZABLON MUTIGITU GIRIMWA', 'zablongirimwa@gmail.com', '0782883904', 'customer', NULL, NULL, NULL, '$2y$12$3OZzyGJpD.HROQ2Q5d7BgOSLMPnhFGE.cRS/Ilv3843u6/KvpM1PK', NULL, NULL, NULL, NULL, '2026-07-18 03:05:23', '2026-07-18 03:05:23', NULL, 'accept', NULL, 0, NULL, 0.00),
(34, 'Thomas Paul Chizi', 'customer-bus@hisgc.co.tz', '0715020945', 'customer', '2026-07-18 07:48:05', NULL, NULL, '$2y$12$kbF4kEmBtJMSzcCpnNg1FOIkCNAFHqDKA4i0z9v32m7lJG9THA.tu', NULL, NULL, NULL, NULL, '2026-07-18 07:48:05', '2026-07-24 01:54:59', '0715020945', 'accept', NULL, 0, NULL, 0.00),
(35, 'Lukiko Kitundu', 'kitundu@hisgc.co.tz', NULL, 'local_bus_owner', NULL, NULL, NULL, '$2y$12$2H2C8y0urykyRAIzuVbLyuPQxmQqUlgaLG84ZeAt0Onc40vpuPEzy', NULL, NULL, NULL, 'peYux4mKC3GVpAvl5c9xMwBmJhqfSPis10gDY68wnelSrLQN6FazQqd4w5wh', '2026-07-18 10:55:21', '2026-07-18 10:55:21', '0758638868', 'accept', 3, 0, NULL, 0.00),
(36, 'Thomas Paul Chizi', 'vendor@hisgc.co.tz', NULL, 'vender', NULL, NULL, NULL, '$2y$12$M5HmpT7GXnsnTkne87klBec3pKHGOt49snZ709vQoLnYmf.rw1JaK', NULL, NULL, NULL, NULL, '2026-07-18 11:37:08', '2026-07-26 09:11:03', '0715020945', 'accept', NULL, 0, NULL, 0.00),
(37, 'Daniel john', 'danielarusha@gmail.com', '0758523652', 'driver', NULL, NULL, NULL, '$2y$12$4MMneC1gtELW464kRhG5Xeoub6Nr/8YIDidD6UL6mrBPdks8uAbs2', NULL, NULL, NULL, NULL, '2026-07-19 22:26:39', '2026-07-19 22:26:39', '0758523652', 'accept', NULL, 0, NULL, 0.00),
(38, 'Fredy kelvin', 'fredyklevin@gmail.com', NULL, 'driver', NULL, NULL, NULL, '$2y$12$9FdaL7Cb24NA4/BvbT2/QuLqIHkh6maDAXDxQUbnAY14rIstcnM0y', NULL, NULL, NULL, NULL, '2026-07-19 22:32:05', '2026-07-19 22:32:05', '0767567899', 'pending', NULL, 0, NULL, 0.00),
(39, 'Juma ally', 'danilyarusha@gmail.com', '0758523652', 'driver', NULL, NULL, NULL, '$2y$12$63E7EP12X.2AeAOowkdYyenip8dCaJj.QuTC0h1UAzONQJFu.BwjK', NULL, NULL, NULL, NULL, '2026-07-19 22:34:43', '2026-07-19 22:34:43', '0758523652', 'accept', NULL, 0, NULL, 0.00),
(40, 'Saidi  david', 'saididavid@gmail.com', NULL, 'driver', NULL, NULL, NULL, '$2y$12$CURkb5Ndu2i.3EqmntEClO0a3fdxMx5GxirvHuQkdfPjRvyhBSXZy', NULL, NULL, NULL, NULL, '2026-07-20 14:06:52', '2026-07-20 14:06:52', '0678899345', 'pending', NULL, 0, NULL, 0.00),
(41, 'Abdul Bunju', 'abduldv@aatanchtrading.com', NULL, 'vender', NULL, NULL, NULL, '$2y$12$5jPXj8fqKG1TSTVDwB2wmuvMI1nhRbMZCh4hdBxjxLzV4c11GtG26', NULL, NULL, NULL, NULL, '2026-07-22 04:39:46', '2026-07-22 04:39:46', '0765553953', 'accept', NULL, 0, NULL, 0.00),
(42, 'Alnas Abdul', 'monicamruma50@gmail.com', NULL, 'special_hire', NULL, NULL, NULL, '$2y$12$eeyhJ/bzxSDVW9d/TPdkJ.fbWxax27W3.98GKF7g5O3L0nfrQZnIa', NULL, NULL, NULL, NULL, '2026-07-22 05:45:41', '2026-07-22 05:45:41', '0765553953', 'accept', NULL, 0, NULL, 0.00),
(43, 'sumaye', 'givendaniel@yahoo.com', '+255767675461', 'customer', NULL, NULL, NULL, '$2y$12$rbgYtyny/R/1VPmSe8/nwuKHmg15i1smSaRLKV4fNJKwN3pxrsL3m', NULL, NULL, NULL, NULL, '2026-07-22 14:34:38', '2026-07-22 15:01:13', '+255767675461', 'accept', NULL, 0, NULL, 0.00),
(44, 'Francis Fredy', 'fredysuma@gmail.com', '0749343101', 'customer', NULL, NULL, NULL, '$2y$12$O/YS3se.dQHWfcyhnMGK4.rU7GxCsBbw7BsfzgmiwrQBH.2NQzPbe', NULL, NULL, NULL, NULL, '2026-07-22 20:25:33', '2026-08-02 21:28:32', '0749343101', 'accept', NULL, 0, NULL, 0.00),
(45, 'Abdul Bunju', 'abjuma0000@gmail.com', NULL, 'customer', '2026-07-29 23:01:51', NULL, NULL, '$2y$12$94nQifMlr2H/w225AiXGleaTws5WkC0Gbl.YwzMFMMBhgsY3v97Ue', NULL, NULL, NULL, NULL, '2026-07-29 23:01:51', '2026-07-29 23:01:51', '0715553803', 'accept', NULL, 0, NULL, 0.00),
(46, 'Bish Telecom', 'admin@bishtelecom.com', NULL, 'bus_campany', NULL, NULL, NULL, '$2y$12$Etrae9I7UdxzbFKMA9GaXuzqFtwX3mmaI7hSEQVjGOCWdznIsjgyG', NULL, NULL, NULL, NULL, '2026-07-31 15:47:05', '2026-07-31 15:47:05', '0765553953', 'accept', NULL, 0, NULL, 0.00),
(47, 'Alnas Abdul', 'monicamruma60@gmail.com', NULL, 'customer', '2026-07-31 19:49:36', NULL, NULL, '$2y$12$JhJsfiB5q07mQULzQtqNVuv.P4bNrKQiT25.HW8PDZ3CBKJDQ/q5e', NULL, NULL, NULL, NULL, '2026-07-31 19:49:36', '2026-07-31 19:49:36', '0765553953', 'accept', NULL, 0, NULL, 0.00),
(48, 'Alnas Abdul', 'alnasabdul@gmail.com', NULL, 'vender', NULL, NULL, NULL, '$2y$12$.wmV0VQor8hLn7o7gVo01.196ZeQFkuBF1laddBRKwZI//8CdytWy', NULL, NULL, NULL, NULL, '2026-07-31 19:58:31', '2026-07-31 19:58:31', '0715553803', 'accept', NULL, 0, NULL, 0.00),
(49, 'abdul Juma', 'bunju@gmail.com', NULL, 'customer', '2026-08-03 23:53:22', NULL, NULL, '$2y$12$ntqPc25gUJZ/RpbCzw/5NuD0L8tu7lsxeBHBbp07.EggJDZ2SxNza', NULL, NULL, NULL, NULL, '2026-08-03 23:53:22', '2026-08-03 23:53:22', '0715553803', 'accept', NULL, 0, NULL, 0.00),
(50, 'Ashfaina Abdul', 'ash@gmail.com', NULL, 'customer', '2026-08-16 22:02:09', NULL, NULL, '$2y$12$yMzmlB6.IZ.6ePyF3LHWKuUHtPBN9pubaM79XPSd50qFnjBJ3x41O', NULL, NULL, NULL, NULL, '2026-08-16 22:02:09', '2026-08-16 22:02:09', '0716600100', 'accept', NULL, 0, NULL, 0.00),
(51, 'ibrahim ashiraf', 'ibrareverbo@gmail.com', NULL, 'vender', NULL, NULL, NULL, '$2y$12$z3CF8bb.xGA5.y.yinFvDOmD60Pkw9jT.U9Olg3430enWPWakXE7W', NULL, NULL, NULL, NULL, '2026-08-16 22:03:41', '2026-08-16 22:03:41', '0628042409', 'accept', NULL, 0, NULL, 0.00),
(52, 'Zablon girimwa', 'zablongirimwa2@gmail.com', NULL, 'vender', NULL, NULL, NULL, '$2y$12$NPuFpBDihr3H4tbHhaRmg.fh7mZa22RPXJizZkyCcu2wnd5wIp1BS', NULL, NULL, NULL, NULL, '2026-09-11 14:56:31', '2026-09-12 13:00:48', '0745769060', 'accept', NULL, 0, NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `vender_account`
--

CREATE TABLE `vender_account` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tin` varchar(255) DEFAULT NULL,
  `house_number` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `town` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `altenative_number` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_number` varchar(255) DEFAULT NULL,
  `percentage` int(11) DEFAULT 0,
  `work` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vender_account`
--

INSERT INTO `vender_account` (`id`, `user_id`, `tin`, `house_number`, `street`, `town`, `city`, `province`, `country`, `altenative_number`, `bank_name`, `bank_number`, `percentage`, `work`, `created_at`, `updated_at`) VALUES
(2, 36, '101102103', '56', 'Shekilango street', 'Ubungo', 'Ubungo', 'Dar es salaam', 'Tanzania', '0715020945', 'CRDB Bank', 'hjggyuiiio', 10, 'Mbezi Magufuli', '2026-07-26 09:11:03', '2026-07-29 02:21:36'),
(3, 52, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2026-09-12 13:00:48', '2026-09-12 09:00:48');

-- --------------------------------------------------------

--
-- Table structure for table `vender_balances`
--

CREATE TABLE `vender_balances` (
  `id` int(11) NOT NULL COMMENT 'Primary Key',
  `user_id` int(11) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT 'Create Time',
  `updated_at` varchar(255) DEFAULT NULL,
  `fees` int(11) DEFAULT 0,
  `payment_number` varchar(255) DEFAULT NULL,
  `sell_cash_amount` decimal(14,2) NOT NULL DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vender_balances`
--

INSERT INTO `vender_balances` (`id`, `user_id`, `amount`, `created_at`, `updated_at`, `fees`, `payment_number`, `sell_cash_amount`) VALUES
(6, 36, 21845, '2026-07-18 07:37:08', '2026-09-21 08:02:47', 0, '0789473209', 0.00),
(5, 27, 0, '2026-07-17 15:17:53', '2026-08-16 18:26:58', 0, NULL, 0.00),
(4, 21, 0, '2026-07-17 06:58:28', '2026-08-16 18:26:58', 0, NULL, 0.00),
(7, 41, 5390, '2026-07-22 00:39:46', '2026-08-16 20:26:00', 0, NULL, 0.00),
(8, 48, 0, '2026-07-31 15:58:31', '2026-08-16 18:26:58', 0, NULL, 0.00),
(9, 51, 0, '2026-08-16 18:03:41', '2026-08-16 18:26:58', 0, NULL, 0.00),
(10, 52, 0, '2026-09-11 10:56:31', '2026-09-11 10:56:31', 0, NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `vender_transactions`
--

CREATE TABLE `vender_transactions` (
  `id` int(11) NOT NULL COMMENT 'Primary Key',
  `vender_balance_id` int(11) NOT NULL COMMENT 'Vender Balance ID',
  `transaction_id` int(11) NOT NULL COMMENT 'Transaction ID',
  `amount` decimal(10,2) NOT NULL COMMENT 'Transaction Amount',
  `created_at` datetime DEFAULT NULL COMMENT 'Create Time',
  `updated_at` datetime DEFAULT NULL COMMENT 'Create Time'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vender_wallet_deposits`
--

CREATE TABLE `vender_wallet_deposits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_method` varchar(32) NOT NULL,
  `reference` varchar(64) NOT NULL,
  `status` varchar(24) NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `via`
--

CREATE TABLE `via` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `via`
--

INSERT INTO `via` (`id`, `bus_id`, `route_id`, `name`, `created_at`, `updated_at`) VALUES
(6, 8, 8, 'Msamvu, Morogoro', '2026-07-17 19:20:07', '2026-07-17 15:20:07'),
(5, 6, 6, 'Bagamoyo', '2026-07-17 13:44:19', '2026-09-14 05:18:10'),
(4, 7, 7, 'Bagamoyo', '2026-07-17 13:27:11', '2026-09-14 04:47:05'),
(7, 11, 11, 'Morogoro', '2026-07-31 16:31:01', '2026-07-31 12:31:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access`
--
ALTER TABLE `access`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_transactions`
--
ALTER TABLE `admin_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_wallet`
--
ALTER TABLE `admin_wallet`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `balances`
--
ALTER TABLE `balances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bima`
--
ALTER TABLE `bima`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_code` (`booking_code`);

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bus_owner_account`
--
ALTER TABLE `bus_owner_account`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `campanies`
--
ALTER TABLE `campanies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cancelled_bookings`
--
ALTER TABLE `cancelled_bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coasters`
--
ALTER TABLE `coasters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coasters_driver_user_id_index` (`driver_user_id`);

--
-- Indexes for table `device_tokens`
--
ALTER TABLE `device_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_tokens_token_unique` (`token`),
  ADD KEY `device_tokens_user_id_app_index` (`user_id`,`app`);

--
-- Indexes for table `discount`
--
ALTER TABLE `discount`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `excess_luggage_escrow`
--
ALTER TABLE `excess_luggage_escrow`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `excess_luggage_escrow_booking_id_unique` (`booking_id`),
  ADD KEY `excess_luggage_escrow_booking_code_index` (`booking_code`),
  ADD KEY `excess_luggage_escrow_status_index` (`status`);

--
-- Indexes for table `excess_luggage_escrow_transactions`
--
ALTER TABLE `excess_luggage_escrow_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `excess_luggage_escrow_transactions_escrow_id_index` (`escrow_id`),
  ADD KEY `excess_luggage_escrow_transactions_booking_id_index` (`booking_id`),
  ADD KEY `excess_luggage_escrow_transactions_type_index` (`type`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `government_levies`
--
ALTER TABLE `government_levies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parcels`
--
ALTER TABLE `parcels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parcel_number` (`parcel_number`),
  ADD KEY `parcels_vender_id_foreign` (`vender_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payment_fees`
--
ALTER TABLE `payment_fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `points`
--
ALTER TABLE `points`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `refund`
--
ALTER TABLE `refund`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `refund_percentages`
--
ALTER TABLE `refund_percentages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roundtrip`
--
ALTER TABLE `roundtrip`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bus_id` (`bus_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sms_logs_created_at_index` (`created_at`),
  ADD KEY `sms_logs_message_id_index` (`message_id`);

--
-- Indexes for table `special_hire_orders`
--
ALTER TABLE `special_hire_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `special_hire_orders_customer_user_id_foreign` (`customer_user_id`);

--
-- Indexes for table `special_hire_payment_intents`
--
ALTER TABLE `special_hire_payment_intents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `special_hire_payment_intents_customer_user_id_foreign` (`customer_user_id`),
  ADD KEY `special_hire_payment_intents_coaster_id_foreign` (`coaster_id`),
  ADD KEY `special_hire_payment_intents_special_hire_order_id_foreign` (`special_hire_order_id`),
  ADD KEY `special_hire_payment_intents_clickpesa_ref_index` (`clickpesa_ref`),
  ADD KEY `special_hire_payment_intents_status_index` (`status`),
  ADD KEY `special_hire_payment_intents_expires_at_index` (`expires_at`);

--
-- Indexes for table `special_hire_pricing`
--
ALTER TABLE `special_hire_pricing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `special_hire_pricing_coaster_id_foreign` (`coaster_id`);

--
-- Indexes for table `special_hire_withdrawal_requests`
--
ALTER TABLE `special_hire_withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `special_hire_withdrawal_requests_user_id_foreign` (`user_id`);

--
-- Indexes for table `stend`
--
ALTER TABLE `stend`
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `system_balance`
--
ALTER TABLE `system_balance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `temp_wallets`
--
ALTER TABLE `temp_wallets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vender_account`
--
ALTER TABLE `vender_account`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vender_balances`
--
ALTER TABLE `vender_balances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vender_transactions`
--
ALTER TABLE `vender_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vender_wallet_deposits`
--
ALTER TABLE `vender_wallet_deposits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vender_wallet_deposits_reference_unique` (`reference`),
  ADD KEY `vender_wallet_deposits_user_id_index` (`user_id`),
  ADD KEY `vender_wallet_deposits_status_index` (`status`);

--
-- Indexes for table `via`
--
ALTER TABLE `via`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access`
--
ALTER TABLE `access`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `admin_transactions`
--
ALTER TABLE `admin_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_wallet`
--
ALTER TABLE `admin_wallet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `balances`
--
ALTER TABLE `balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `bima`
--
ALTER TABLE `bima`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=244;

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `bus_owner_account`
--
ALTER TABLE `bus_owner_account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `campanies`
--
ALTER TABLE `campanies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cancelled_bookings`
--
ALTER TABLE `cancelled_bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `coasters`
--
ALTER TABLE `coasters`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `device_tokens`
--
ALTER TABLE `device_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `discount`
--
ALTER TABLE `discount`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `excess_luggage_escrow`
--
ALTER TABLE `excess_luggage_escrow`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `excess_luggage_escrow_transactions`
--
ALTER TABLE `excess_luggage_escrow_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `government_levies`
--
ALTER TABLE `government_levies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=212;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `parcels`
--
ALTER TABLE `parcels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `payment_fees`
--
ALTER TABLE `payment_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=198;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `points`
--
ALTER TABLE `points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=280;

--
-- AUTO_INCREMENT for table `refund`
--
ALTER TABLE `refund`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `refund_percentages`
--
ALTER TABLE `refund_percentages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `roundtrip`
--
ALTER TABLE `roundtrip`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=493;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `special_hire_orders`
--
ALTER TABLE `special_hire_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `special_hire_payment_intents`
--
ALTER TABLE `special_hire_payment_intents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `special_hire_pricing`
--
ALTER TABLE `special_hire_pricing`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `special_hire_withdrawal_requests`
--
ALTER TABLE `special_hire_withdrawal_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stend`
--
ALTER TABLE `stend`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_balance`
--
ALTER TABLE `system_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=198;

--
-- AUTO_INCREMENT for table `temp_wallets`
--
ALTER TABLE `temp_wallets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `vender_account`
--
ALTER TABLE `vender_account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vender_balances`
--
ALTER TABLE `vender_balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key', AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `vender_transactions`
--
ALTER TABLE `vender_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key';

--
-- AUTO_INCREMENT for table `vender_wallet_deposits`
--
ALTER TABLE `vender_wallet_deposits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `via`
--
ALTER TABLE `via`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `device_tokens`
--
ALTER TABLE `device_tokens`
  ADD CONSTRAINT `device_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parcels`
--
ALTER TABLE `parcels`
  ADD CONSTRAINT `parcels_vender_id_foreign` FOREIGN KEY (`vender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `special_hire_orders`
--
ALTER TABLE `special_hire_orders`
  ADD CONSTRAINT `special_hire_orders_customer_user_id_foreign` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `special_hire_payment_intents`
--
ALTER TABLE `special_hire_payment_intents`
  ADD CONSTRAINT `special_hire_payment_intents_coaster_id_foreign` FOREIGN KEY (`coaster_id`) REFERENCES `coasters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `special_hire_payment_intents_customer_user_id_foreign` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `special_hire_payment_intents_special_hire_order_id_foreign` FOREIGN KEY (`special_hire_order_id`) REFERENCES `special_hire_orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `special_hire_pricing`
--
ALTER TABLE `special_hire_pricing`
  ADD CONSTRAINT `special_hire_pricing_coaster_id_foreign` FOREIGN KEY (`coaster_id`) REFERENCES `coasters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `special_hire_withdrawal_requests`
--
ALTER TABLE `special_hire_withdrawal_requests`
  ADD CONSTRAINT `special_hire_withdrawal_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
