-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2026 at 12:22 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mdr`
--

-- --------------------------------------------------------

--
-- Table structure for table `adherence_logs`
--

CREATE TABLE `adherence_logs` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `reminder_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) DEFAULT NULL COMMENT 'Direct link to schedule for reliability',
  `dose_date` date NOT NULL,
  `actual_time_taken` time DEFAULT NULL,
  `verification_method` enum('dot','self_report','digital','caregiver_report') DEFAULT 'self_report',
  `verified_by` int(11) DEFAULT NULL COMMENT 'NULL if self-reported',
  `status` enum('taken','missed','late') DEFAULT 'missed',
  `missed_reason` varchar(255) DEFAULT NULL COMMENT 'side_effects, forgot, stockout, etc.',
  `notes` text DEFAULT NULL,
  `response_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adherence_logs`
--

INSERT INTO `adherence_logs` (`id`, `patient_id`, `reminder_id`, `schedule_id`, `dose_date`, `actual_time_taken`, `verification_method`, `verified_by`, `status`, `missed_reason`, `notes`, `response_time`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, 23, '2026-04-21', NULL, '', NULL, 'missed', 'no_response', NULL, NULL, '2026-04-21 09:08:44', '2026-04-21 09:08:44'),
(2, 3, NULL, 25, '2026-04-21', NULL, '', NULL, 'missed', 'no_response', NULL, NULL, '2026-04-21 09:08:44', '2026-04-21 09:08:44'),
(3, 3, NULL, 27, '2026-04-21', NULL, '', NULL, 'missed', 'no_response', NULL, NULL, '2026-04-21 09:08:44', '2026-04-21 09:08:44'),
(4, 3, NULL, 23, '2026-04-23', NULL, '', NULL, 'missed', 'no_response', NULL, NULL, '2026-04-23 07:51:25', '2026-04-23 07:51:25'),
(5, 3, NULL, 25, '2026-04-23', NULL, '', NULL, 'missed', 'no_response', NULL, NULL, '2026-04-23 07:51:25', '2026-04-23 07:51:25'),
(6, 3, NULL, 27, '2026-04-23', NULL, '', NULL, 'missed', 'no_response', NULL, NULL, '2026-04-23 07:51:25', '2026-04-23 07:51:25'),
(7, 3, NULL, 23, '2026-04-24', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-24 10:42:33', '2026-04-24 10:42:33'),
(8, 3, NULL, 25, '2026-04-24', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-24 10:42:33', '2026-04-24 10:42:33'),
(9, 3, NULL, 27, '2026-04-24', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-24 10:42:33', '2026-04-24 10:42:33'),
(10, 3, NULL, 34, '2026-04-24', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-24 11:15:43', '2026-04-24 11:15:43'),
(11, 3, NULL, 34, '2026-04-25', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-25 08:31:59', '2026-04-25 08:31:59'),
(12, 3, NULL, 31, '2026-04-25', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-25 13:01:37', '2026-04-25 13:01:37'),
(13, 3, NULL, 30, '2026-04-25', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-25 13:21:41', '2026-04-25 13:21:41'),
(14, 3, NULL, 32, '2026-04-25', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-25 15:01:34', '2026-04-25 15:01:34'),
(15, 3, NULL, 33, '2026-04-25', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-25 15:01:34', '2026-04-25 15:01:34'),
(16, 3, NULL, 35, '2026-04-25', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-25 15:01:34', '2026-04-25 15:01:34'),
(17, 3, NULL, 34, '2026-04-26', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-26 12:18:34', '2026-04-26 12:18:34'),
(18, 3, NULL, 31, '2026-04-26', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-26 13:01:34', '2026-04-26 13:01:34'),
(19, 3, NULL, 30, '2026-04-26', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-26 13:21:42', '2026-04-26 13:21:42'),
(20, 3, NULL, 32, '2026-04-26', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-26 15:01:37', '2026-04-26 15:01:37'),
(21, 3, NULL, 33, '2026-04-26', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-26 15:01:37', '2026-04-26 15:01:37'),
(22, 3, NULL, 35, '2026-04-26', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-26 15:01:37', '2026-04-26 15:01:37'),
(23, 3, NULL, 34, '2026-04-27', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-27 11:08:40', '2026-04-27 11:08:40'),
(24, 3, NULL, 31, '2026-04-27', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-27 13:07:12', '2026-04-27 13:07:12'),
(25, 3, NULL, 30, '2026-04-27', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-27 13:21:43', '2026-04-27 13:21:43'),
(26, 3, NULL, 34, '2026-04-28', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-28 07:01:34', '2026-04-28 07:01:34'),
(27, 3, NULL, 34, '2026-04-29', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-29 07:01:35', '2026-04-29 07:01:35'),
(28, 3, NULL, 34, '2026-04-30', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-30 07:01:36', '2026-04-30 07:01:36'),
(29, 3, NULL, 31, '2026-04-30', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-30 13:01:37', '2026-04-30 13:01:37'),
(30, 3, NULL, 30, '2026-04-30', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-04-30 13:21:37', '2026-04-30 13:21:37'),
(31, 3, NULL, 34, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 07:01:35', '2026-05-01 07:01:35'),
(32, 3, NULL, 120, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 07:01:35', '2026-05-01 07:01:35'),
(33, 3, NULL, 125, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 07:01:35', '2026-05-01 07:01:35'),
(34, 3, NULL, 130, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 07:01:35', '2026-05-01 07:01:35'),
(35, 3, NULL, 135, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 07:01:35', '2026-05-01 07:01:35'),
(36, 3, NULL, 140, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 07:01:35', '2026-05-01 07:01:35'),
(37, 3, NULL, 146, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 07:01:35', '2026-05-01 07:01:35'),
(38, 3, NULL, 152, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 07:01:35', '2026-05-01 07:01:35'),
(39, 3, NULL, 158, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 07:01:35', '2026-05-01 07:01:35'),
(40, 3, NULL, 164, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 07:01:35', '2026-05-01 07:01:35'),
(41, 3, NULL, 121, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 11:16:46', '2026-05-01 11:16:46'),
(42, 3, NULL, 126, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 11:16:46', '2026-05-01 11:16:46'),
(43, 3, NULL, 131, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 11:16:46', '2026-05-01 11:16:46'),
(44, 3, NULL, 136, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 11:16:46', '2026-05-01 11:16:46'),
(45, 3, NULL, 141, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 11:16:46', '2026-05-01 11:16:46'),
(46, 3, NULL, 147, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 11:16:46', '2026-05-01 11:16:46'),
(47, 3, NULL, 153, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 11:16:46', '2026-05-01 11:16:46'),
(48, 3, NULL, 159, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 11:16:46', '2026-05-01 11:16:46'),
(49, 3, NULL, 165, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 11:16:46', '2026-05-01 11:16:46'),
(56, 3, NULL, 122, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 13:35:21', '2026-05-01 13:35:21'),
(57, 3, NULL, 127, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 13:35:21', '2026-05-01 13:35:21'),
(58, 3, NULL, 132, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 13:35:21', '2026-05-01 13:35:21'),
(59, 3, NULL, 137, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 13:35:21', '2026-05-01 13:35:21'),
(60, 3, NULL, 142, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 13:35:21', '2026-05-01 13:35:21'),
(61, 3, NULL, 148, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 13:35:21', '2026-05-01 13:35:21'),
(62, 3, NULL, 154, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 13:35:21', '2026-05-01 13:35:21'),
(63, 3, NULL, 160, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 13:35:21', '2026-05-01 13:35:21'),
(64, 3, NULL, 166, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 13:35:21', '2026-05-01 13:35:21'),
(71, 3, NULL, 35, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 15:01:39', '2026-05-01 15:01:39'),
(72, 3, NULL, 123, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 15:01:39', '2026-05-01 15:01:39'),
(73, 3, NULL, 128, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 15:01:39', '2026-05-01 15:01:39'),
(74, 3, NULL, 133, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 15:01:39', '2026-05-01 15:01:39'),
(75, 3, NULL, 138, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 15:01:39', '2026-05-01 15:01:39'),
(76, 3, NULL, 143, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 15:01:39', '2026-05-01 15:01:39'),
(77, 3, NULL, 149, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 15:01:39', '2026-05-01 15:01:39'),
(78, 3, NULL, 155, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 15:01:39', '2026-05-01 15:01:39'),
(79, 3, NULL, 161, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 15:01:39', '2026-05-01 15:01:39'),
(80, 3, NULL, 167, '2026-05-01', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-01 15:01:39', '2026-05-01 15:01:39'),
(86, 3, NULL, 34, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(87, 3, NULL, 120, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(88, 3, NULL, 121, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(89, 3, NULL, 122, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(90, 3, NULL, 125, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(91, 3, NULL, 126, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(92, 3, NULL, 127, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(93, 3, NULL, 130, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(94, 3, NULL, 131, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(95, 3, NULL, 132, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(96, 3, NULL, 135, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(97, 3, NULL, 136, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(98, 3, NULL, 137, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(99, 3, NULL, 140, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(100, 3, NULL, 141, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(101, 3, NULL, 142, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(102, 3, NULL, 146, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(103, 3, NULL, 147, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(104, 3, NULL, 148, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(105, 3, NULL, 152, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(106, 3, NULL, 153, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(107, 3, NULL, 154, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(108, 3, NULL, 158, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(109, 3, NULL, 159, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(110, 3, NULL, 160, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(111, 3, NULL, 164, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(112, 3, NULL, 165, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(113, 3, NULL, 166, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 13:26:23', '2026-05-02 13:26:23'),
(117, 3, NULL, 35, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 15:01:34', '2026-05-02 15:01:34'),
(118, 3, NULL, 123, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 15:01:34', '2026-05-02 15:01:34'),
(119, 3, NULL, 128, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 15:01:34', '2026-05-02 15:01:34'),
(120, 3, NULL, 133, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 15:01:34', '2026-05-02 15:01:34'),
(121, 3, NULL, 138, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 15:01:34', '2026-05-02 15:01:34'),
(122, 3, NULL, 143, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 15:01:34', '2026-05-02 15:01:34'),
(123, 3, NULL, 149, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 15:01:34', '2026-05-02 15:01:34'),
(124, 3, NULL, 155, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 15:01:34', '2026-05-02 15:01:34'),
(125, 3, NULL, 161, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 15:01:34', '2026-05-02 15:01:34'),
(126, 3, NULL, 167, '2026-05-02', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-02 15:01:34', '2026-05-02 15:01:34'),
(132, 3, NULL, 34, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 07:01:35', '2026-05-03 07:01:35'),
(133, 3, NULL, 120, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 07:01:35', '2026-05-03 07:01:35'),
(134, 3, NULL, 125, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 07:01:35', '2026-05-03 07:01:35'),
(135, 3, NULL, 130, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 07:01:35', '2026-05-03 07:01:35'),
(136, 3, NULL, 135, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 07:01:35', '2026-05-03 07:01:35'),
(137, 3, NULL, 140, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 07:01:35', '2026-05-03 07:01:35'),
(138, 3, NULL, 146, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 07:01:35', '2026-05-03 07:01:35'),
(139, 3, NULL, 152, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 07:01:35', '2026-05-03 07:01:35'),
(140, 3, NULL, 158, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 07:01:35', '2026-05-03 07:01:35'),
(141, 3, NULL, 164, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 07:01:35', '2026-05-03 07:01:35'),
(147, 3, NULL, 121, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:11:38', '2026-05-03 11:11:38'),
(148, 3, NULL, 126, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:11:38', '2026-05-03 11:11:38'),
(149, 3, NULL, 131, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:11:38', '2026-05-03 11:11:38'),
(150, 3, NULL, 136, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:11:38', '2026-05-03 11:11:38'),
(151, 3, NULL, 141, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:11:38', '2026-05-03 11:11:38'),
(152, 3, NULL, 147, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:11:38', '2026-05-03 11:11:38'),
(153, 3, NULL, 153, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:11:38', '2026-05-03 11:11:38'),
(154, 3, NULL, 159, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:11:38', '2026-05-03 11:11:38'),
(155, 3, NULL, 165, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:11:38', '2026-05-03 11:11:38'),
(156, 12, NULL, 208, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:11:38', '2026-05-03 11:11:38'),
(157, 12, NULL, 209, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:11:38', '2026-05-03 11:11:38'),
(158, 13, NULL, 203, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:11:38', '2026-05-03 11:11:38'),
(162, 12, NULL, 210, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:51:35', '2026-05-03 11:51:35'),
(163, 12, NULL, 212, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:56:44', '2026-05-03 11:56:44'),
(164, 12, NULL, 213, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:56:44', '2026-05-03 11:56:44'),
(165, 12, NULL, 214, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 11:56:44', '2026-05-03 11:56:44'),
(166, 3, NULL, 122, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 13:02:19', '2026-05-03 13:02:19'),
(167, 3, NULL, 127, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 13:02:19', '2026-05-03 13:02:19'),
(168, 3, NULL, 132, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 13:02:19', '2026-05-03 13:02:19'),
(169, 3, NULL, 137, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 13:02:19', '2026-05-03 13:02:19'),
(170, 3, NULL, 142, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 13:02:19', '2026-05-03 13:02:19'),
(171, 3, NULL, 148, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 13:02:19', '2026-05-03 13:02:19'),
(172, 3, NULL, 154, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 13:02:19', '2026-05-03 13:02:19'),
(173, 3, NULL, 160, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 13:02:19', '2026-05-03 13:02:19'),
(174, 3, NULL, 166, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 13:02:19', '2026-05-03 13:02:19'),
(181, 3, NULL, 35, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:01:44', '2026-05-03 15:01:44'),
(182, 3, NULL, 123, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:01:44', '2026-05-03 15:01:44'),
(183, 3, NULL, 128, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:01:44', '2026-05-03 15:01:44'),
(184, 3, NULL, 133, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:01:44', '2026-05-03 15:01:44'),
(185, 3, NULL, 138, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:01:44', '2026-05-03 15:01:44'),
(186, 3, NULL, 143, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:01:44', '2026-05-03 15:01:44'),
(187, 3, NULL, 149, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:01:44', '2026-05-03 15:01:44'),
(188, 3, NULL, 155, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:01:44', '2026-05-03 15:01:44'),
(189, 3, NULL, 161, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:01:44', '2026-05-03 15:01:44'),
(190, 3, NULL, 167, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:01:44', '2026-05-03 15:01:44'),
(196, 13, NULL, 215, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:06:35', '2026-05-03 15:06:35'),
(197, 13, NULL, 216, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:06:35', '2026-05-03 15:06:35'),
(198, 13, NULL, 217, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:06:35', '2026-05-03 15:06:35'),
(199, 13, NULL, 218, '2026-05-03', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-03 15:06:35', '2026-05-03 15:06:35'),
(203, 3, NULL, 34, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(204, 3, NULL, 120, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(205, 3, NULL, 125, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(206, 3, NULL, 130, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(207, 3, NULL, 135, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(208, 3, NULL, 140, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(209, 3, NULL, 146, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(210, 3, NULL, 152, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(211, 3, NULL, 158, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(212, 3, NULL, 164, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(213, 12, NULL, 212, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(214, 12, NULL, 214, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(215, 13, NULL, 215, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59'),
(216, 13, NULL, 217, '2026-05-04', NULL, '', NULL, 'missed', NULL, NULL, NULL, '2026-05-04 07:43:59', '2026-05-04 07:43:59');

-- --------------------------------------------------------

--
-- Table structure for table `adverse_events`
--

CREATE TABLE `adverse_events` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `suspected_drug_id` int(11) DEFAULT NULL,
  `drug_id` int(11) DEFAULT NULL COMMENT 'NULL if unclear which drug',
  `event_type` varchar(100) NOT NULL COMMENT 'e.g. QTc prolongation, hepatotoxicity, neuropathy, nausea',
  `severity` enum('mild','moderate','severe','life_threatening') NOT NULL,
  `onset_date` date NOT NULL,
  `resolution_date` date DEFAULT NULL,
  `action_taken` enum('continued','dose_reduced','drug_stopped','drug_substituted','regimen_changed','hospitalized') DEFAULT NULL,
  `reported_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `adverse_events`
--

INSERT INTO `adverse_events` (`id`, `patient_id`, `suspected_drug_id`, `drug_id`, `event_type`, `severity`, `onset_date`, `resolution_date`, `action_taken`, `reported_by`, `notes`, `created_at`) VALUES
(1, 3, NULL, 16, 'QTc prolongation', 'mild', '2026-04-26', '2026-04-26', '', 7, 'aa\n[Outcome] ongoing', '2026-04-26 12:36:47'),
(2, 3, NULL, 16, 'QTc prolongation', 'mild', '2026-04-26', '2026-04-26', 'continued', 7, 'aa\n[Outcome] Resolved — no action needed · Regimen flagged for modification', '2026-04-26 12:37:11'),
(3, 3, NULL, 16, 'QTc prolongation', 'mild', '2026-04-26', NULL, NULL, 7, 'aa', '2026-04-26 12:37:27'),
(4, 3, NULL, 16, 'QTc prolongation', 'mild', '2026-04-26', '2026-05-01', 'continued', 7, 'aa\n[Outcome] Resolved — drug stopped', '2026-04-26 12:39:01'),
(5, 3, NULL, 16, 'QTc prolongation', 'mild', '2026-04-26', NULL, NULL, 7, 'aa', '2026-04-26 12:40:52'),
(6, 3, NULL, 16, 'QTc prolongation', 'mild', '2026-04-26', NULL, NULL, 7, 'aa', '2026-04-26 12:42:16'),
(7, 3, NULL, 1, 'Headache', 'mild', '2026-04-26', NULL, NULL, 7, 'llk', '2026-04-26 12:43:29'),
(8, 3, NULL, 16, 'Skin rash', 'mild', '2026-04-26', NULL, NULL, 7, 'jj', '2026-04-26 12:43:55');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `appointment_type` enum('clinical_review','lab_collection','drug_pickup','counseling','follow_up') DEFAULT 'clinical_review',
  `appointment_date` datetime NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','missed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL COMMENT 'INSERT, UPDATE, DELETE, LOGIN, LOGOUT',
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 3, 'INSERT', NULL, NULL, 'patients', 3, NULL, '{\"patient_code\":\"MDR-2026-0001\",\"full_name\":\"EGUNU JEFF\",\"gender\":\"male\",\"date_of_birth\":\"1983-02-21\",\"national_id\":\"\",\"phone\":\"256783874407\",\"address\":\"\",\"facility_id\":\"1\",\"next_of_kin\":\"James\",\"next_of_kin_contact\":\"dave\",\"enrollment_date\":\"2026-04-21\",\"date_of_diagnosis\":\"2026-04-21\",\"tb_case_classification\":\"new\",\"mdr_confirmation\":\"confirmed\",\"hiv_status\":\"positive\",\"on_art\":\"\",\"weight_kg\":\"78\",\"create_user_account\":\"\"}', '127.0.0.1', NULL, '2026-04-21 05:30:54'),
(2, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 2, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":9,\"dose_mg\":500,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":16,\"dose_mg\":1000,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-04-21 05:47:44'),
(3, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 3, NULL, '[{\"drug_id\":10,\"dose_mg\":500,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":13,\"dose_mg\":800,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":18,\"dose_mg\":1000,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-04-21 06:19:12'),
(4, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 4, NULL, '[{\"drug_id\":7,\"dose_mg\":500,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":12,\"dose_mg\":1500,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-04-21 06:20:07'),
(5, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 5, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":3,\"dose_mg\":100,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":5,\"dose_mg\":750,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":16,\"dose_mg\":1000,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-04-21 06:24:34'),
(6, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 6, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":13,\"dose_mg\":800,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":18,\"dose_mg\":1000,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-04-21 06:41:04'),
(7, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 7, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":11,\"dose_mg\":100,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":9,\"dose_mg\":500,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-04-21 06:50:48'),
(8, 4, 'INSERT', NULL, NULL, 'drug_susceptibility', 3, NULL, '0', '127.0.0.1', NULL, '2026-04-22 05:35:20'),
(9, 3, 'INSERT', NULL, NULL, 'patients', 4, NULL, '{\"patient_code\":\"MDR-2026-0002\",\"full_name\":\"AKELLO JANE\",\"gender\":\"female\",\"date_of_birth\":\"1991-02-13\",\"national_id\":\"\",\"phone\":\"256784165935\",\"address\":\"jinja\",\"facility_id\":\"2\",\"next_of_kin\":\"James\",\"next_of_kin_contact\":\"hin\",\"enrollment_date\":\"2026-04-23\",\"date_of_diagnosis\":\"2026-04-01\",\"tb_case_classification\":\"failure\",\"mdr_confirmation\":\"confirmed\",\"hiv_status\":\"positive\",\"on_art\":\"1\",\"weight_kg\":\"89\",\"create_user_account\":\"1\"}', '127.0.0.1', NULL, '2026-04-23 17:17:00'),
(10, 3, 'INSERT', NULL, NULL, 'patients', 5, NULL, '{\"patient_code\":\"MDR-2026-0003\",\"full_name\":\"AKELLO JANE\",\"gender\":\"female\",\"date_of_birth\":\"1991-02-13\",\"national_id\":\"\",\"phone\":\"256784165935\",\"address\":\"jinja\",\"facility_id\":\"2\",\"next_of_kin\":\"James\",\"next_of_kin_contact\":\"hin\",\"enrollment_date\":\"2026-04-23\",\"date_of_diagnosis\":\"2026-04-01\",\"tb_case_classification\":\"failure\",\"mdr_confirmation\":\"confirmed\",\"hiv_status\":\"positive\",\"on_art\":\"1\",\"weight_kg\":\"89\",\"create_user_account\":\"1\"}', '127.0.0.1', NULL, '2026-04-23 17:19:46'),
(11, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 8, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":16,\"dose_mg\":1000,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-04-24 10:50:09'),
(12, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 9, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":2,\"dose_mg\":600,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-04-24 11:02:09'),
(13, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 10, NULL, '[{\"drug_id\":4,\"dose_mg\":400,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-04-24 11:04:43'),
(14, 7, 'INSERT', NULL, NULL, 'adverse_events', 6, NULL, '{\"drug_id\":\"16\",\"event_type\":\"QTc prolongation\",\"severity\":\"mild\",\"onset_date\":\"2026-04-26\",\"description\":\"aa\"}', '127.0.0.1', NULL, '2026-04-26 12:42:17'),
(15, 7, 'INSERT', NULL, NULL, 'adverse_events', 8, NULL, '{\"drug_id\":\"1\",\"event_type\":\"Headache\",\"severity\":\"mild\",\"onset_date\":\"2026-04-26\",\"description\":\"llk\"}', '127.0.0.1', NULL, '2026-04-26 12:43:29'),
(16, 7, 'INSERT', NULL, NULL, 'adverse_events', 10, NULL, '{\"drug_id\":\"16\",\"event_type\":\"Skin rash\",\"severity\":\"mild\",\"onset_date\":\"2026-04-26\",\"description\":\"jj\"}', '127.0.0.1', NULL, '2026-04-26 12:43:56'),
(17, 4, 'INSERT', NULL, NULL, 'lab_results', 4, NULL, '0', '127.0.0.1', NULL, '2026-04-27 06:37:33'),
(18, 4, 'INSERT', NULL, NULL, 'lab_results', 5, NULL, '0', '127.0.0.1', NULL, '2026-04-29 17:02:02'),
(19, 4, 'INSERT', NULL, NULL, 'drug_susceptibility', 3, NULL, '0', '127.0.0.1', NULL, '2026-05-01 03:18:42'),
(20, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 11, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":3,\"dose_mg\":100,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":11,\"dose_mg\":100,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":5,\"dose_mg\":750,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":4,\"dose_mg\":400,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":6,\"dose_mg\":500,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":8,\"dose_mg\":4000,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-01 04:01:58'),
(21, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 12, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":3,\"dose_mg\":100,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":11,\"dose_mg\":100,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":5,\"dose_mg\":750,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":4,\"dose_mg\":400,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":6,\"dose_mg\":500,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":8,\"dose_mg\":4000,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-01 04:18:20'),
(22, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 13, NULL, '[{\"drug_id\":7,\"dose_mg\":500,\"frequency\":5,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":13,\"dose_mg\":800,\"frequency\":5,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":14,\"dose_mg\":1000,\"frequency\":5,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":17,\"dose_mg\":1000,\"frequency\":5,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-01 04:28:27'),
(23, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 14, NULL, '[{\"drug_id\":3,\"dose_mg\":100,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":8,\"dose_mg\":4000,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":10,\"dose_mg\":500,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":7,\"dose_mg\":500,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":18,\"dose_mg\":1000,\"frequency\":6,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-01 04:36:33'),
(24, 3, 'INSERT', NULL, NULL, 'patients', 12, NULL, '{\"patient_code\":\"TB-2026-0001\",\"full_name\":\"AKELLO Jena\",\"gender\":\"female\",\"date_of_birth\":\"2026-05-01\",\"national_id\":\"\",\"phone\":\"\",\"address\":\"\",\"facility_id\":\"3\",\"next_of_kin\":\"\",\"next_of_kin_contact\":\"\",\"enrollment_date\":\"2026-05-01\",\"date_of_diagnosis\":\"2026-05-01\",\"tb_case_classification\":\"return_after_default\",\"mdr_confirmation\":\"confirmed\",\"hiv_status\":\"positive\",\"on_art\":\"\",\"weight_kg\":\"\",\"create_user_account\":\"\"}', '127.0.0.1', NULL, '2026-05-01 14:09:49'),
(32, 3, 'INSERT', NULL, NULL, 'patients', 13, NULL, '{\"patient_code\":\"TB-2026-0002\",\"full_name\":\"dave\",\"gender\":\"male\",\"date_of_birth\":\"1984-12-31\",\"national_id\":\"\",\"phone\":\"\",\"address\":\"\",\"facility_id\":\"1\",\"next_of_kin\":\"\",\"next_of_kin_contact\":\"\",\"enrollment_date\":\"2026-05-03\",\"date_of_diagnosis\":\"2026-05-03\",\"tb_case_classification\":\"new\",\"mdr_confirmation\":\"confirmed\",\"hiv_status\":\"positive\",\"on_art\":\"1\",\"weight_kg\":\"\",\"create_user_account\":\"\"}', '127.0.0.1', NULL, '2026-05-03 09:17:51'),
(38, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 27, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":3,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 10:06:53'),
(39, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 28, NULL, '[{\"drug_id\":3,\"dose_mg\":100,\"frequency\":3,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 10:11:26'),
(40, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 29, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 10:13:42'),
(41, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 30, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 10:23:49'),
(42, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 31, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 10:24:33'),
(43, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 32, NULL, '[{\"drug_id\":13,\"dose_mg\":800,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 10:29:52'),
(44, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 33, NULL, '[{\"drug_id\":2,\"dose_mg\":600,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 10:31:15'),
(45, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 34, NULL, '[{\"drug_id\":13,\"dose_mg\":800,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 10:33:59'),
(46, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 35, NULL, '[{\"drug_id\":5,\"dose_mg\":750,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 10:41:18'),
(47, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 36, NULL, '[{\"drug_id\":14,\"dose_mg\":1000,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":17,\"dose_mg\":1000,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 10:42:37'),
(48, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 37, NULL, '[{\"drug_id\":6,\"dose_mg\":500,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 10:44:51'),
(49, 1, 'approve', NULL, NULL, 'treatment_regimens', 37, NULL, '{\"status\":\"active\"}', '127.0.0.1', NULL, '2026-05-03 11:44:42'),
(50, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 38, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 11:47:16'),
(51, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 39, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 11:53:53'),
(52, 1, 'reject', NULL, NULL, 'treatment_regimens', 39, NULL, '{\"status\":\"rejected\"}', '127.0.0.1', NULL, '2026-05-03 11:54:07'),
(53, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 40, NULL, '[{\"drug_id\":5,\"dose_mg\":750,\"frequency\":1,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 11:54:23'),
(54, 4, 'INSERT', NULL, NULL, 'lab_results', 6, NULL, '0', '127.0.0.1', NULL, '2026-05-03 12:45:13'),
(55, 4, 'INSERT', NULL, NULL, 'lab_results', 7, NULL, '0', '127.0.0.1', NULL, '2026-05-03 12:55:04'),
(56, 3, 'INSERT', NULL, NULL, 'treatment_regimens', 41, NULL, '[{\"drug_id\":1,\"dose_mg\":400,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0},{\"drug_id\":3,\"dose_mg\":100,\"frequency\":2,\"duration_weeks\":null,\"start_week\":0}]', '127.0.0.1', NULL, '2026-05-03 15:04:23'),
(57, 1, 'approve', NULL, NULL, 'treatment_regimens', 40, NULL, '{\"status\":\"active\"}', '127.0.0.1', NULL, '2026-05-03 15:15:36'),
(58, 1, 'approve', NULL, NULL, 'treatment_regimens', 41, NULL, '{\"status\":\"active\"}', '127.0.0.1', NULL, '2026-05-03 15:19:09'),
(59, 4, 'INSERT', NULL, NULL, 'lab_results', 8, NULL, '0', '127.0.0.1', NULL, '2026-05-04 04:11:13'),
(60, 4, 'INSERT', NULL, NULL, 'lab_results', 9, NULL, '0', '127.0.0.1', NULL, '2026-05-04 04:12:35'),
(61, 4, 'INSERT', NULL, NULL, 'drug_susceptibility', 3, NULL, '0', '127.0.0.1', NULL, '2026-05-04 04:46:40');

-- --------------------------------------------------------

--
-- Table structure for table `drugs`
--

CREATE TABLE `drugs` (
  `id` int(11) NOT NULL,
  `drug_name` varchar(100) NOT NULL,
  `drug_code` varchar(20) NOT NULL COMMENT 'Standard abbreviation e.g. BDQ, LZD, CFZ',
  `drug_group` enum('group_a','group_b','group_c','group_d1','group_d2','other') DEFAULT NULL COMMENT 'WHO MDR-TB drug grouping',
  `default_dose_mg` int(11) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL COMMENT 'mg, ml, tablets',
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drugs`
--

INSERT INTO `drugs` (`id`, `drug_name`, `drug_code`, `drug_group`, `default_dose_mg`, `unit`, `notes`, `is_active`, `created_at`) VALUES
(1, 'Bedaquiline', 'BDQ', 'group_a', 400, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(2, 'Linezolid', 'LZD', 'group_a', 600, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(3, 'Clofazimine', 'CFZ', 'group_b', 100, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(4, 'Moxifloxacin', 'MXF', 'group_b', 400, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(5, 'Levofloxacin', 'LVX', 'group_b', 750, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(6, 'Cycloserine', 'CS', 'group_c', 500, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(7, 'Terizidone', 'TRD', 'group_c', 500, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(8, 'Para-aminosalicylic acid', 'PAS', 'group_c', 4000, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(9, 'Ethionamide', 'ETO', 'group_c', 500, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(10, 'Prothionamide', 'PTH', 'group_c', 500, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(11, 'Delamanid', 'DLM', 'group_b', 100, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(12, 'Pyrazinamide', 'PZA', 'group_d1', 1500, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(13, 'Ethambutol', 'EMB', 'group_d1', 800, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(14, 'Amikacin', 'AMK', 'group_d2', 1000, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(15, 'Capreomycin', 'CM', 'group_d2', 1000, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(16, 'Streptomycin', 'SM', 'group_d2', 1000, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(17, 'Imipenem-cilastatin', 'IPM', 'group_d2', 1000, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(18, 'Meropenem', 'MEM', 'group_d2', 1000, 'mg', NULL, 1, '2026-04-20 14:45:34'),
(19, 'sadcv', 'bnma', 'group_a', 0, 'bn', 'vbnm', 1, '2026-05-01 07:23:36'),
(20, 'sadcv', 'bnm', 'group_b', 222, 'mg', '2sads', 1, '2026-05-01 13:23:23');

-- --------------------------------------------------------

--
-- Table structure for table `drug_susceptibility`
--

CREATE TABLE `drug_susceptibility` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `drug_id` int(11) NOT NULL,
  `test_method` enum('gene_xpert','lpa','phenotypic_mgit','phenotypic_lj','molecular_wgs') NOT NULL,
  `result` enum('sensitive','resistant','indeterminate','not_done') NOT NULL,
  `specimen_date` date DEFAULT NULL,
  `result_date` date DEFAULT NULL,
  `lab_facility` varchar(150) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drug_susceptibility`
--

INSERT INTO `drug_susceptibility` (`id`, `patient_id`, `drug_id`, `test_method`, `result`, `specimen_date`, `result_date`, `lab_facility`, `performed_by`, `notes`, `created_at`) VALUES
(1, 3, 1, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:19'),
(2, 3, 2, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(3, 3, 3, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(4, 3, 11, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(5, 3, 5, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(6, 3, 4, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(7, 3, 6, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(8, 3, 9, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(9, 3, 8, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(10, 3, 10, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(11, 3, 7, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(12, 3, 13, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(13, 3, 12, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(14, 3, 14, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(15, 3, 15, 'gene_xpert', 'indeterminate', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(16, 3, 17, 'gene_xpert', 'resistant', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(17, 3, 18, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(18, 3, 16, 'gene_xpert', 'sensitive', '2026-04-22', '2026-04-21', 'MDR Lab', 4, NULL, '2026-04-22 05:35:20'),
(19, 3, 14, 'phenotypic_mgit', 'sensitive', NULL, '2026-04-22', 'MDR Lab', 4, 'aa', '2026-05-01 03:18:42'),
(20, 3, 15, 'phenotypic_mgit', 'resistant', NULL, '2026-04-22', 'MDR Lab', 4, 'aa', '2026-05-01 03:18:42'),
(21, 3, 16, 'phenotypic_mgit', 'sensitive', NULL, '2026-04-22', 'MDR Lab', 4, 'aa', '2026-05-01 03:18:42'),
(22, 3, 1, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:39'),
(23, 3, 2, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(24, 3, 19, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(25, 3, 3, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(26, 3, 11, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(27, 3, 5, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(28, 3, 4, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(29, 3, 20, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(30, 3, 6, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(31, 3, 9, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(32, 3, 8, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(33, 3, 10, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(34, 3, 7, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(35, 3, 13, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(36, 3, 12, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(37, 3, 14, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(38, 3, 15, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(39, 3, 17, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(40, 3, 18, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40'),
(41, 3, 16, 'gene_xpert', 'sensitive', '2026-05-04', '2026-05-04', 'National Tuberculosis Reference Lab', 4, NULL, '2026-05-04 04:46:40');

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `facility_code` varchar(50) DEFAULT NULL,
  `facility_type` enum('national_referral','regional_referral','district','primary','community') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`id`, `name`, `facility_code`, `facility_type`, `address`, `contact_person`, `phone`, `email`, `contact_phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'National Tuberculosis Reference Lab', 'NTRL-001', 'national_referral', '123 Health Plaza, Soroti City', NULL, NULL, NULL, '+256-700-111222', 1, '2026-04-21 05:25:17', '2026-05-01 06:09:46'),
(2, 'Metropolitan Regional Hospital', 'MRH-402', 'regional_referral', '45 Medical Drive, Teso Province', NULL, NULL, NULL, '+256-711-333444', 1, '2026-04-21 05:25:17', NULL),
(3, 'Saint Jude District Clinic', 'SJC-DIST-09', 'district', 'Market Square Road, Kumi District', NULL, NULL, NULL, '+256-722-555666', 1, '2026-04-21 05:25:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lab_results`
--

CREATE TABLE `lab_results` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `test_type` varchar(100) DEFAULT NULL,
  `specimen_type` varchar(50) DEFAULT NULL,
  `result` text DEFAULT NULL,
  `result_date` date DEFAULT NULL,
  `specimen_date` date DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `lab_facility` varchar(150) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `is_final` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_results`
--

INSERT INTO `lab_results` (`id`, `patient_id`, `test_type`, `specimen_type`, `result`, `result_date`, `specimen_date`, `reviewed_at`, `uploaded_by`, `lab_facility`, `reviewed_by`, `is_final`, `created_at`, `updated_at`) VALUES
(1, 3, 'sp', 'pleural_fluid', 'lk', '2026-04-27', '2026-04-25', NULL, 4, 'National Tuberculosis Reference Lab', NULL, 1, '2026-04-27 06:36:38', NULL),
(2, 3, 'sp', 'pleural_fluid', 'lk', '2026-04-27', '2026-04-25', NULL, 4, 'National Tuberculosis Reference Lab', NULL, 1, '2026-04-27 06:36:58', NULL),
(3, 3, 'sp', 'pleural_fluid', 'lk', '2026-04-27', '2026-04-25', NULL, 4, 'National Tuberculosis Reference Lab', NULL, 1, '2026-04-27 06:37:19', NULL),
(4, 3, 'sp', 'pleural_fluid', 'lk', '2026-04-27', '2026-04-25', NULL, 4, 'National Tuberculosis Reference Lab', NULL, 1, '2026-04-27 06:37:31', NULL),
(5, 3, 'sp', 'sputum', 'dd', '2026-04-29', '2026-04-29', NULL, 4, 'National Tuberculosis Reference Lab', NULL, 1, '2026-04-29 17:02:02', NULL),
(6, 4, 'Sputum for GeneXpert MTB/RIF', 'csf', 'ww', '2026-05-03', '2026-05-03', '2026-05-03 15:48:00', 4, 'National Tuberculosis Reference Lab', 1, 1, '2026-05-03 12:45:13', '2026-05-03 12:48:00'),
(7, 4, 'Sputum for AFB Smear', 'pleural_fluid', 'asasas', '2026-05-03', '2026-05-03', NULL, 4, 'National Tuberculosis Reference Lab', NULL, 0, '2026-05-03 12:55:04', NULL),
(8, 13, 'BlD', 'blood', 'negative', '2026-05-04', '2026-05-04', NULL, 4, 'National Tuberculosis Reference Lab', NULL, 1, '2026-05-04 04:11:12', NULL),
(9, 3, 'BlD', 'pleural_fluid', 'hgfhj', '2026-05-04', '2026-05-04', NULL, 4, 'National Tuberculosis Reference Lab', NULL, 0, '2026-05-04 04:12:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `medication_schedule`
--

CREATE TABLE `medication_schedule` (
  `id` int(11) NOT NULL,
  `regimen_id` int(11) NOT NULL,
  `drug_id` int(11) DEFAULT NULL,
  `dose_time` time NOT NULL,
  `frequency` enum('daily','weekly') DEFAULT 'daily',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `day_of_week` tinyint(1) DEFAULT NULL COMMENT '1=Mon...7=Sun. NULL=daily'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medication_schedule`
--

INSERT INTO `medication_schedule` (`id`, `regimen_id`, `drug_id`, `dose_time`, `frequency`, `is_active`, `effective_from`, `effective_to`, `created_at`, `day_of_week`) VALUES
(17, 6, 1, '14:01:13', 'daily', 0, '2026-04-21', NULL, '2026-04-21 06:41:04', NULL),
(23, 7, 1, '14:01:13', 'daily', 0, '2026-04-21', NULL, '2026-04-21 06:50:48', NULL),
(24, 7, 1, '14:01:13', 'daily', 0, '2026-04-21', NULL, '2026-04-21 06:50:48', NULL),
(25, 7, 11, '14:01:13', 'daily', 0, '2026-04-21', NULL, '2026-04-21 06:50:48', NULL),
(26, 7, 11, '14:01:13', 'daily', 0, '2026-04-21', NULL, '2026-04-21 06:50:48', NULL),
(27, 7, 9, '14:01:13', 'daily', 0, '2026-04-21', NULL, '2026-04-21 06:50:48', NULL),
(28, 7, 9, '14:01:13', 'daily', 0, '2026-04-21', NULL, '2026-04-21 06:50:48', NULL),
(30, 8, 1, '14:20:07', 'daily', 0, '2026-04-24', NULL, '2026-04-24 10:50:09', NULL),
(31, 8, 16, '14:01:13', 'daily', 0, '2026-04-24', NULL, '2026-04-24 10:50:09', NULL),
(32, 9, 1, '16:00:00', 'daily', 0, '2026-04-24', NULL, '2026-04-24 11:02:09', NULL),
(33, 9, 2, '16:00:00', 'daily', 0, '2026-04-24', NULL, '2026-04-24 11:02:09', NULL),
(34, 10, 4, '08:00:00', 'daily', 1, '2026-04-24', NULL, '2026-04-24 11:04:43', NULL),
(35, 10, 4, '16:00:00', 'daily', 1, '2026-04-24', NULL, '2026-04-24 11:04:43', NULL),
(120, 13, 7, '08:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(121, 13, 7, '12:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(122, 13, 7, '14:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(123, 13, 7, '16:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(124, 13, 7, '20:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(125, 13, 13, '08:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(126, 13, 13, '12:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(127, 13, 13, '14:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(128, 13, 13, '16:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(129, 13, 13, '20:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(130, 13, 14, '08:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(131, 13, 14, '12:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(132, 13, 14, '14:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(133, 13, 14, '16:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(134, 13, 14, '20:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(135, 13, 17, '08:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(136, 13, 17, '12:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(137, 13, 17, '14:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(138, 13, 17, '16:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(139, 13, 17, '20:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:28:27', NULL),
(140, 14, 3, '08:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(141, 14, 3, '12:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(142, 14, 3, '14:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(143, 14, 3, '16:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(144, 14, 3, '20:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(145, 14, 3, '21:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(146, 14, 8, '08:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(147, 14, 8, '12:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(148, 14, 8, '14:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(149, 14, 8, '16:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(150, 14, 8, '20:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(151, 14, 8, '21:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(152, 14, 10, '08:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(153, 14, 10, '12:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(154, 14, 10, '14:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(155, 14, 10, '16:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(156, 14, 10, '20:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(157, 14, 10, '21:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(158, 14, 7, '08:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(159, 14, 7, '12:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(160, 14, 7, '14:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(161, 14, 7, '16:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(162, 14, 7, '20:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(163, 14, 7, '21:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(164, 14, 18, '08:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(165, 14, 18, '12:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(166, 14, 18, '14:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(167, 14, 18, '16:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(168, 14, 18, '20:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(169, 14, 18, '21:00:00', 'daily', 1, '2026-05-01', NULL, '2026-05-01 04:36:33', NULL),
(191, 27, 1, '08:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:06:53', NULL),
(192, 27, 1, '12:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:06:53', NULL),
(193, 27, 1, '13:12:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:06:53', NULL),
(194, 28, 3, '08:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:11:26', NULL),
(195, 28, 3, '21:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:11:26', NULL),
(196, 28, 3, '13:12:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:11:26', NULL),
(197, 29, 1, '08:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:13:42', NULL),
(198, 30, 1, '08:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:23:49', NULL),
(199, 31, 1, '08:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:24:33', NULL),
(200, 32, 13, '12:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:29:52', NULL),
(201, 32, 13, '13:29:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:29:52', NULL),
(202, 33, 2, '08:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:31:15', NULL),
(203, 34, 13, '08:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:33:59', NULL),
(204, 35, 5, '08:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:41:18', NULL),
(205, 35, 5, '16:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:41:18', NULL),
(206, 36, 14, '08:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:42:37', NULL),
(207, 36, 17, '08:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:42:37', NULL),
(208, 37, 6, '08:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:44:51', NULL),
(209, 37, 6, '12:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 10:44:51', NULL),
(210, 38, 1, '08:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 11:47:16', NULL),
(211, 38, 1, '14:00:00', 'daily', 0, '2026-05-03', NULL, '2026-05-03 11:47:16', NULL),
(212, 39, 1, '08:00:00', 'daily', 1, '2026-05-03', NULL, '2026-05-03 11:53:53', NULL),
(213, 39, 1, '12:00:00', 'daily', 1, '2026-05-03', NULL, '2026-05-03 11:53:53', NULL),
(214, 40, 5, '08:00:00', 'daily', 1, '2026-05-03', NULL, '2026-05-03 11:54:23', NULL),
(215, 41, 1, '08:00:00', 'daily', 1, '2026-05-03', NULL, '2026-05-03 15:04:23', NULL),
(216, 41, 1, '12:00:00', 'daily', 1, '2026-05-03', NULL, '2026-05-03 15:04:23', NULL),
(217, 41, 3, '08:00:00', 'daily', 1, '2026-05-03', NULL, '2026-05-03 15:04:23', NULL),
(218, 41, 3, '12:00:00', 'daily', 1, '2026-05-03', NULL, '2026-05-03 15:04:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Specific user ID (NULL = role-wide)',
  `user_role` varchar(50) DEFAULT NULL COMMENT 'Target role',
  `type` enum('alert','event','log','info') DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `icon_bg` varchar(100) DEFAULT 'bg-info/10 dark:bg-info/15',
  `icon` varchar(100) DEFAULT 'fa-solid fa-bell text-info',
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `user_role`, `type`, `title`, `message`, `icon_bg`, `icon`, `link`, `is_read`, `created_at`) VALUES
(1, NULL, 'doctor', 'alert', 'Adverse Event: QTc prolongation', 'EGUNU JEFF reported QTc prolongation (Severity: mild)', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-exclamation-circle text-warning', 'adverse_events.php', 0, '2026-04-26 15:39:01'),
(2, NULL, 'nurse', 'alert', 'Side Effect Reported', 'EGUNU JEFF — QTc prolongation', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-exclamation-circle text-warning', 'side_effects.php', 0, '2026-04-26 15:39:01'),
(3, NULL, 'doctor', 'alert', 'Adverse Event: QTc prolongation', 'EGUNU JEFF reported QTc prolongation (Severity: mild)', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-exclamation-circle text-warning', 'adverse_events.php', 0, '2026-04-26 15:40:52'),
(4, NULL, 'nurse', 'alert', 'Side Effect Reported', 'EGUNU JEFF — QTc prolongation', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-exclamation-circle text-warning', 'side_effects.php', 0, '2026-04-26 15:40:52'),
(5, NULL, 'doctor', 'alert', 'Adverse Event: QTc prolongation', 'EGUNU JEFF reported QTc prolongation (Severity: mild)', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-exclamation-circle text-warning', 'adverse_events.php', 0, '2026-04-26 15:42:16'),
(6, NULL, 'nurse', 'alert', 'Side Effect Reported', 'EGUNU JEFF — QTc prolongation', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-exclamation-circle text-warning', 'side_effects.php', 0, '2026-04-26 15:42:17'),
(7, NULL, 'doctor', 'alert', 'Adverse Event: Headache', 'EGUNU JEFF reported Headache (Severity: mild)', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-exclamation-circle text-warning', 'adverse_events.php', 0, '2026-04-26 15:43:29'),
(8, NULL, 'nurse', 'alert', 'Side Effect Reported', 'EGUNU JEFF — Headache', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-exclamation-circle text-warning', 'side_effects.php', 0, '2026-04-26 15:43:29'),
(9, NULL, 'doctor', 'alert', 'Adverse Event: Skin rash', 'EGUNU JEFF reported Skin rash (Severity: mild)', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-exclamation-circle text-warning', 'adverse_events.php', 0, '2026-04-26 15:43:55'),
(10, NULL, 'nurse', 'alert', 'Side Effect Reported', 'EGUNU JEFF — Skin rash', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-exclamation-circle text-warning', 'side_effects.php', 0, '2026-04-26 15:43:56'),
(11, NULL, 'patient', 'event', 'Lab Result Available', 'Your sp results are ready.', 'bg-success/10 dark:bg-success/15', 'fa-solid fa-flask text-success', 'results.php', 0, '2026-04-27 09:37:32'),
(12, NULL, 'patient', 'event', 'Lab Result Available', 'Your sp results are ready.', 'bg-success/10 dark:bg-success/15', 'fa-solid fa-flask text-success', 'results.php', 0, '2026-04-29 20:02:02'),
(13, NULL, 'doctor', 'alert', 'Pending Regimen Review', 'AKELLO JANE\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=11', 0, '2026-05-01 07:01:58'),
(14, NULL, 'doctor', 'alert', 'Pending Regimen Review', 'AKELLO JANE\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=12', 0, '2026-05-01 07:18:20'),
(15, NULL, 'doctor', 'alert', 'Pending Regimen Review', 'EGUNU JEFF\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=13', 0, '2026-05-01 07:28:27'),
(16, NULL, 'doctor', 'alert', 'Pending Regimen Review', 'EGUNU JEFF\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=14', 0, '2026-05-01 07:36:33'),
(17, NULL, 'clinician', 'event', 'New Patient Enrolled', 'AKELLO Jena (TB-2026-0001) has been registered.', 'bg-primary/10 dark:bg-accent-light/15', 'fa-solid fa-user-plus text-primary dark:text-accent-light', 'patients.php', 0, '2026-05-01 17:09:49'),
(18, NULL, 'data_officer', 'event', 'New Enrollment', 'AKELLO Jena (TB-2026-0001) enrolled in GxAlert program.', 'bg-info/10 dark:bg-info/15', 'fa-solid fa-user-plus text-info', 'cohort_report.php', 0, '2026-05-01 17:09:49'),
(26, NULL, 'clinician', 'event', 'New Patient Enrolled', 'dave (TB-2026-0002) has been registered.', 'bg-primary/10 dark:bg-accent-light/15', 'fa-solid fa-user-plus text-primary dark:text-accent-light', 'patients.php', 0, '2026-05-03 12:17:51'),
(27, NULL, 'data_officer', 'event', 'New Enrollment', 'dave (TB-2026-0002) enrolled in GxAlert program.', 'bg-info/10 dark:bg-info/15', 'fa-solid fa-user-plus text-info', 'cohort_report.php', 0, '2026-05-03 12:17:51'),
(33, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'dave\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=27', 0, '2026-05-03 13:06:53'),
(34, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'dave\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=28', 0, '2026-05-03 13:11:26'),
(35, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'dave\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=29', 0, '2026-05-03 13:13:42'),
(36, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'dave\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=30', 0, '2026-05-03 13:23:50'),
(37, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'dave\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=31', 0, '2026-05-03 13:24:33'),
(38, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'dave\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=32', 0, '2026-05-03 13:29:52'),
(39, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'dave\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=33', 0, '2026-05-03 13:31:15'),
(40, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'dave\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=34', 0, '2026-05-03 13:33:59'),
(41, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'AKELLO Jena\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=35', 0, '2026-05-03 13:41:19'),
(42, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'AKELLO Jena\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=36', 0, '2026-05-03 13:42:37'),
(43, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'AKELLO Jena\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=37', 0, '2026-05-03 13:44:51'),
(44, 3, 'clinician', 'event', 'Regimen Approved', 'Your regimen for AKELLO Jena has been approved by the doctor.', 'bg-success/10 dark:bg-success/15', 'fa-solid fa-check-circle text-success', 'patients.php', 0, '2026-05-03 14:44:42'),
(45, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'AKELLO Jena\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=38', 0, '2026-05-03 14:47:16'),
(46, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'AKELLO Jena\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=39', 0, '2026-05-03 14:53:53'),
(47, 3, 'clinician', 'alert', 'Regimen Rejected', 'Regimen for AKELLO Jena was rejected: Rejected by doctor — no notes provided', 'bg-error/10 dark:bg-error/15', 'fa-solid fa-times-circle text-error', 'patients.php', 0, '2026-05-03 14:54:07'),
(48, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'AKELLO Jena\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=40', 0, '2026-05-03 14:54:23'),
(49, NULL, 'patient', 'info', 'Preliminary Lab Result', 'Your Sputum for GeneXpert MTB/RIF results are ready.', 'bg-info/10 dark:bg-info/15', 'fa-solid fa-flask text-info', 'results.php', 0, '2026-05-03 15:45:13'),
(50, NULL, 'doctor', 'alert', 'Preliminary Lab Result', 'AKELLO JANE — Sputum for GeneXpert MTB/RIF needs review.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-flask text-warning', 'lab_review.php', 0, '2026-05-03 15:45:13'),
(51, NULL, 'patient', 'info', 'Preliminary Lab Result', 'Your Sputum for AFB Smear results are ready.', 'bg-info/10 dark:bg-info/15', 'fa-solid fa-flask text-info', 'results.php', 0, '2026-05-03 15:55:04'),
(52, NULL, 'doctor', 'alert', 'Preliminary Lab Result', 'AKELLO JANE — Sputum for AFB Smear needs review.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-flask text-warning', 'lab_review.php', 0, '2026-05-03 15:55:04'),
(53, NULL, 'admin', 'log', 'New User Created', 'Alex Extensions (Patient) account has been created.', 'bg-primary/10 dark:bg-accent-light/15', 'fa-solid fa-user-shield text-primary dark:text-accent-light', 'users.php', 0, '2026-05-03 16:15:54'),
(54, NULL, 'clinician', 'alert', 'Pending Regimen Review', 'dave\'s regimen needs your approval.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-clipboard-check text-warning', 'regimen_reviews.php?id=41', 0, '2026-05-03 18:04:23'),
(55, 3, 'clinician', 'event', 'Regimen Approved', 'Your regimen for AKELLO Jena has been approved by the doctor.', 'bg-success/10 dark:bg-success/15', 'fa-solid fa-check-circle text-success', 'patients.php', 0, '2026-05-03 18:15:36'),
(56, 3, 'clinician', 'event', 'Regimen Approved', 'Your regimen for dave has been approved by the doctor.', 'bg-success/10 dark:bg-success/15', 'fa-solid fa-check-circle text-success', 'patients.php', 0, '2026-05-03 18:19:09'),
(57, NULL, 'patient', 'event', 'Lab Result Available', 'Your BlD results are ready.', 'bg-success/10 dark:bg-success/15', 'fa-solid fa-flask text-success', 'results.php', 0, '2026-05-04 07:11:12'),
(58, NULL, 'patient', 'info', 'Preliminary Lab Result', 'Your BlD results are ready.', 'bg-info/10 dark:bg-info/15', 'fa-solid fa-flask text-info', 'results.php', 0, '2026-05-04 07:12:34'),
(59, NULL, 'doctor', 'alert', 'Preliminary Lab Result', 'EGUNU JEFF — BlD needs review.', 'bg-warning/10 dark:bg-warning/15', 'fa-solid fa-flask text-warning', 'lab_review.php', 0, '2026-05-04 07:12:34'),
(60, NULL, 'lab_personnel', 'alert', 'Test Notification', 'Lab notifications are now working', 'bg-info/10 dark:bg-info/15', 'fa-solid fa-flask text-info', 'preliminary_results.php', 0, '2026-05-04 07:47:21'),
(61, NULL, 'lab_personnel', 'alert', 'Test Notification', 'Lab notifications are now working', 'bg-info/10 dark:bg-info/15', 'fa-solid fa-flask text-info', 'preliminary_results.php', 0, '2026-05-04 07:51:52');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `patient_code` varchar(50) NOT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `regimen_id` int(11) DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `date_of_diagnosis` date DEFAULT NULL,
  `tb_case_classification` enum('new','previously_treated','relapse','failure','return_after_default','transfer_in') DEFAULT NULL,
  `mdr_confirmation` enum('confirmed','presumed') DEFAULT NULL,
  `hiv_status` enum('positive','negative','unknown') DEFAULT NULL,
  `on_art` tinyint(1) DEFAULT NULL,
  `weight_kg` decimal(5,1) DEFAULT NULL,
  `treatment_status` enum('enrolled','on_treatment','completed','cured','failed','died','lost_to_followup','transferred_out') DEFAULT 'enrolled',
  `next_of_kin` varchar(150) DEFAULT NULL,
  `next_of_kin_contact` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `patient_code`, `national_id`, `full_name`, `gender`, `date_of_birth`, `phone`, `address`, `facility_id`, `regimen_id`, `enrollment_date`, `date_of_diagnosis`, `tb_case_classification`, `mdr_confirmation`, `hiv_status`, `on_art`, `weight_kg`, `treatment_status`, `next_of_kin`, `next_of_kin_contact`, `created_by`, `created_at`, `updated_at`, `is_active`) VALUES
(3, 7, 'MDR-2026-0001', NULL, 'EGUNU JEFF', 'male', '1983-02-21', '256700765387', NULL, 1, 6, '2026-04-21', '2026-04-21', 'new', 'confirmed', 'negative', 0, '78.0', 'on_treatment', 'James', 'Dave', 3, '2026-04-21 05:30:54', '2026-05-01 04:36:33', 1),
(4, 9, 'MDR-2026-0002', NULL, 'AKELLO JANE', 'female', '1991-02-13', '256784165935', 'jinja', 1, NULL, '2026-04-23', '2026-04-01', 'failure', 'confirmed', 'positive', 1, '89.0', 'enrolled', 'James', 'hin', 3, '2026-04-23 17:17:00', '2026-05-03 10:43:46', 1),
(12, NULL, 'TB-2026-0001', NULL, 'AKELLO Jena', 'female', '2026-05-01', NULL, NULL, 1, 37, '2026-05-01', '2026-05-01', 'return_after_default', 'confirmed', 'positive', 0, NULL, 'on_treatment', NULL, NULL, 3, '2026-05-01 14:09:49', '2026-05-03 11:54:23', 1),
(13, NULL, 'TB-2026-0002', NULL, 'dave', 'male', '1984-12-31', NULL, NULL, 1, NULL, '2026-05-03', '2026-05-03', 'new', 'confirmed', 'positive', 1, NULL, 'on_treatment', NULL, NULL, 3, '2026-05-03 09:17:51', '2026-05-03 15:04:23', 1);

-- --------------------------------------------------------

--
-- Table structure for table `regimen_drugs`
--

CREATE TABLE `regimen_drugs` (
  `id` int(11) NOT NULL,
  `regimen_id` int(11) NOT NULL,
  `drug_id` int(11) NOT NULL,
  `dose_mg` int(11) NOT NULL,
  `frequency_per_day` int(11) NOT NULL DEFAULT 1 COMMENT 'Number of times per day',
  `duration_weeks` int(11) DEFAULT NULL COMMENT 'NULL means full regimen duration',
  `start_week` int(11) NOT NULL DEFAULT 0 COMMENT 'Week 0 for immediate, e.g. BDQ intro phase',
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `regimen_drugs`
--

INSERT INTO `regimen_drugs` (`id`, `regimen_id`, `drug_id`, `dose_mg`, `frequency_per_day`, `duration_weeks`, `start_week`, `notes`, `is_active`, `created_at`) VALUES
(14, 6, 1, 400, 2, NULL, 0, NULL, 1, '2026-04-21 06:41:04'),
(15, 6, 13, 800, 2, NULL, 0, NULL, 1, '2026-04-21 06:41:04'),
(16, 6, 18, 1000, 2, NULL, 0, NULL, 1, '2026-04-21 06:41:04'),
(17, 7, 1, 400, 2, NULL, 0, NULL, 1, '2026-04-21 06:50:48'),
(18, 7, 11, 100, 2, NULL, 0, NULL, 1, '2026-04-21 06:50:48'),
(19, 7, 9, 500, 2, NULL, 0, NULL, 1, '2026-04-21 06:50:48'),
(20, 8, 1, 400, 1, NULL, 0, NULL, 1, '2026-04-24 10:50:09'),
(21, 8, 16, 1000, 1, NULL, 0, NULL, 1, '2026-04-24 10:50:09'),
(22, 9, 1, 400, 1, NULL, 0, NULL, 1, '2026-04-24 11:02:09'),
(23, 9, 2, 600, 1, NULL, 0, NULL, 1, '2026-04-24 11:02:09'),
(24, 10, 4, 400, 2, NULL, 0, NULL, 1, '2026-04-24 11:04:43'),
(25, 7, 1, 400, 1, NULL, 0, NULL, 1, '2026-04-24 11:08:24'),
(40, 13, 7, 500, 5, NULL, 0, NULL, 1, '2026-05-01 04:28:27'),
(41, 13, 13, 800, 5, NULL, 0, NULL, 1, '2026-05-01 04:28:27'),
(42, 13, 14, 1000, 5, NULL, 0, NULL, 1, '2026-05-01 04:28:27'),
(43, 13, 17, 1000, 5, NULL, 0, NULL, 1, '2026-05-01 04:28:27'),
(44, 14, 3, 100, 6, NULL, 0, NULL, 1, '2026-05-01 04:36:33'),
(45, 14, 8, 4000, 6, NULL, 0, NULL, 1, '2026-05-01 04:36:33'),
(46, 14, 10, 500, 6, NULL, 0, NULL, 1, '2026-05-01 04:36:33'),
(47, 14, 7, 500, 6, NULL, 0, NULL, 1, '2026-05-01 04:36:33'),
(48, 14, 18, 1000, 6, NULL, 0, NULL, 1, '2026-05-01 04:36:33'),
(64, 27, 1, 400, 3, NULL, 0, NULL, 1, '2026-05-03 10:06:53'),
(65, 28, 3, 100, 3, NULL, 0, NULL, 1, '2026-05-03 10:11:26'),
(66, 29, 1, 400, 1, NULL, 0, NULL, 1, '2026-05-03 10:13:42'),
(67, 30, 1, 400, 1, NULL, 0, NULL, 1, '2026-05-03 10:23:49'),
(68, 31, 1, 400, 1, NULL, 0, NULL, 1, '2026-05-03 10:24:33'),
(69, 32, 13, 800, 2, NULL, 0, NULL, 1, '2026-05-03 10:29:52'),
(70, 33, 2, 600, 1, NULL, 0, NULL, 1, '2026-05-03 10:31:15'),
(71, 34, 13, 800, 1, NULL, 0, NULL, 1, '2026-05-03 10:33:59'),
(72, 35, 5, 750, 2, NULL, 0, NULL, 1, '2026-05-03 10:41:18'),
(73, 36, 14, 1000, 1, NULL, 0, NULL, 1, '2026-05-03 10:42:37'),
(74, 36, 17, 1000, 1, NULL, 0, NULL, 1, '2026-05-03 10:42:37'),
(75, 37, 6, 500, 2, NULL, 0, NULL, 1, '2026-05-03 10:44:51'),
(76, 38, 1, 400, 2, NULL, 0, NULL, 1, '2026-05-03 11:47:16'),
(77, 39, 1, 400, 2, NULL, 0, NULL, 1, '2026-05-03 11:53:53'),
(78, 40, 5, 750, 1, NULL, 0, NULL, 1, '2026-05-03 11:54:23'),
(79, 41, 1, 400, 2, NULL, 0, NULL, 1, '2026-05-03 15:04:23'),
(80, 41, 3, 100, 2, NULL, 0, NULL, 1, '2026-05-03 15:04:23');

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `channel` enum('sms','ussd','in_app','email') DEFAULT 'sms',
  `reminder_datetime` datetime NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `error_message` varchar(500) DEFAULT NULL,
  `status` enum('pending','sent','delivered','failed','expired','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reminders`
--

INSERT INTO `reminders` (`id`, `patient_id`, `schedule_id`, `channel`, `reminder_datetime`, `sent_at`, `delivered_at`, `retry_count`, `error_message`, `status`, `created_at`) VALUES
(2, 3, 30, 'sms', '2026-04-24 14:15:10', '2026-04-24 14:15:42', NULL, 0, NULL, 'sent', '2026-04-24 11:15:10'),
(3, 3, 140, 'sms', '2026-05-01 08:22:27', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-01 05:22:27'),
(4, 3, 140, 'sms', '2026-05-01 08:31:43', NULL, NULL, 3, 'cURL error: error:0A00010B:SSL routines::wrong version number', 'pending', '2026-05-01 05:31:43'),
(5, 3, 140, 'sms', '2026-05-01 08:37:32', '2026-05-01 08:37:47', NULL, 0, NULL, 'sent', '2026-05-01 05:37:32'),
(6, 3, 140, 'sms', '2026-05-01 08:41:30', '2026-05-01 08:41:56', NULL, 2, 'HTTP 400 — Request is missing required form field \'username\'', 'sent', '2026-05-01 05:41:30'),
(14, 3, 146, 'sms', '2026-05-01 08:46:35', '2026-05-01 08:46:36', NULL, 0, NULL, 'sent', '2026-05-01 05:46:35'),
(15, 3, 152, 'sms', '2026-05-01 08:46:35', '2026-05-01 08:46:37', NULL, 0, NULL, 'sent', '2026-05-01 05:46:35'),
(16, 3, 158, 'sms', '2026-05-01 08:46:35', '2026-05-01 08:46:39', NULL, 0, NULL, 'sent', '2026-05-01 05:46:35'),
(17, 3, 164, 'sms', '2026-05-01 08:46:35', '2026-05-01 08:46:40', NULL, 0, NULL, 'sent', '2026-05-01 05:46:35'),
(24, 3, 140, 'sms', '2026-05-01 09:38:50', '2026-05-01 09:41:44', NULL, 0, NULL, 'sent', '2026-05-01 06:38:50'),
(26, 3, 140, 'sms', '2026-05-01 09:54:37', '2026-05-01 09:56:36', NULL, 0, NULL, 'sent', '2026-05-01 06:54:37'),
(29, 3, 140, 'sms', '2026-05-01 10:11:39', '2026-05-01 10:16:36', NULL, 0, NULL, 'sent', '2026-05-01 07:11:39'),
(30, 3, 140, 'sms', '2026-05-01 10:15:11', '2026-05-01 10:16:38', NULL, 0, NULL, 'sent', '2026-05-01 07:15:11'),
(31, 3, 140, 'sms', '2026-05-01 10:18:30', '2026-05-01 10:18:32', NULL, 0, NULL, 'sent', '2026-05-01 07:18:30'),
(32, 3, 141, 'sms', '2026-05-01 12:56:34', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-01 09:56:34'),
(33, 3, 147, 'sms', '2026-05-01 12:56:34', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-01 09:56:34'),
(34, 3, 153, 'sms', '2026-05-01 12:56:34', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-01 09:56:34'),
(35, 3, 159, 'sms', '2026-05-01 12:56:34', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-01 09:56:34'),
(36, 3, 165, 'sms', '2026-05-01 12:56:34', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-01 09:56:34'),
(37, 3, 140, 'sms', '2026-05-01 14:34:19', '2026-05-01 14:34:21', NULL, 0, NULL, 'failed', '2026-05-01 11:34:19'),
(38, 3, 140, 'sms', '2026-05-01 14:36:26', '2026-05-01 14:36:27', NULL, 0, NULL, 'failed', '2026-05-01 11:36:26'),
(39, 3, 142, 'sms', '2026-05-01 14:56:42', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-01 11:56:42'),
(40, 3, 148, 'sms', '2026-05-01 14:56:42', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-01 11:56:42'),
(41, 3, 154, 'sms', '2026-05-01 14:56:42', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-01 11:56:42'),
(42, 3, 160, 'sms', '2026-05-01 14:56:42', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-01 11:56:42'),
(43, 3, 166, 'sms', '2026-05-01 14:56:42', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-01 11:56:42'),
(46, 3, 143, 'sms', '2026-05-01 16:56:34', '2026-05-01 17:01:40', NULL, 1, 'HTTP 0 — ', 'sent', '2026-05-01 13:56:34'),
(47, 3, 149, 'sms', '2026-05-01 16:56:34', '2026-05-01 17:01:42', NULL, 1, 'HTTP 0 — ', 'sent', '2026-05-01 13:56:34'),
(48, 3, 155, 'sms', '2026-05-01 16:56:34', '2026-05-01 17:06:36', NULL, 2, 'HTTP 0 — ', 'sent', '2026-05-01 13:56:34'),
(49, 3, 161, 'sms', '2026-05-01 16:56:34', '2026-05-01 17:01:45', NULL, 1, 'HTTP 0 — ', 'sent', '2026-05-01 13:56:34'),
(50, 3, 167, 'sms', '2026-05-01 16:56:34', '2026-05-01 17:01:46', NULL, 1, 'HTTP 0 — ', 'sent', '2026-05-01 13:56:34'),
(53, 3, 143, 'sms', '2026-05-02 16:57:31', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-02 13:57:31'),
(54, 3, 149, 'sms', '2026-05-02 16:57:31', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-02 13:57:31'),
(55, 3, 155, 'sms', '2026-05-02 16:57:31', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-02 13:57:31'),
(56, 3, 161, 'sms', '2026-05-02 16:57:31', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-02 13:57:31'),
(57, 3, 167, 'sms', '2026-05-02 16:57:31', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-02 13:57:31'),
(60, 3, 142, 'sms', '2026-05-03 14:56:38', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-03 11:56:38'),
(61, 3, 148, 'sms', '2026-05-03 14:56:38', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-03 11:56:38'),
(62, 3, 154, 'sms', '2026-05-03 14:56:38', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-03 11:56:38'),
(63, 3, 160, 'sms', '2026-05-03 14:56:38', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-03 11:56:38'),
(64, 3, 166, 'sms', '2026-05-03 14:56:38', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-03 11:56:38'),
(67, 3, 140, 'sms', '2026-05-03 16:13:00', '2026-05-03 16:13:01', NULL, 0, NULL, 'failed', '2026-05-03 13:13:00'),
(68, 3, 143, 'sms', '2026-05-03 16:56:34', '2026-05-03 16:56:37', NULL, 0, NULL, 'sent', '2026-05-03 13:56:34'),
(69, 3, 149, 'sms', '2026-05-03 16:56:34', '2026-05-03 16:56:39', NULL, 0, NULL, 'sent', '2026-05-03 13:56:34'),
(70, 3, 155, 'sms', '2026-05-03 16:56:34', '2026-05-03 16:56:40', NULL, 0, NULL, 'sent', '2026-05-03 13:56:34'),
(71, 3, 161, 'sms', '2026-05-03 16:56:34', '2026-05-03 16:56:41', NULL, 0, NULL, 'sent', '2026-05-03 13:56:34'),
(72, 3, 167, 'sms', '2026-05-03 16:56:34', '2026-05-03 16:56:43', NULL, 0, NULL, 'sent', '2026-05-03 13:56:34'),
(75, 3, 140, 'sms', '2026-05-03 17:15:19', '2026-05-03 17:15:21', NULL, 0, NULL, 'sent', '2026-05-03 14:15:19'),
(76, 3, 140, 'sms', '2026-05-03 18:02:25', '2026-05-03 18:02:27', NULL, 0, NULL, 'sent', '2026-05-03 15:02:25'),
(77, 3, 140, 'sms', '2026-05-03 18:30:48', '2026-05-03 18:30:49', NULL, 0, NULL, 'sent', '2026-05-03 15:30:48'),
(78, 3, 141, 'sms', '2026-05-04 12:59:08', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-04 09:59:08'),
(79, 3, 147, 'sms', '2026-05-04 12:59:08', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-04 09:59:08'),
(80, 3, 153, 'sms', '2026-05-04 12:59:08', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-04 09:59:08'),
(81, 3, 159, 'sms', '2026-05-04 12:59:08', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-04 09:59:08'),
(82, 3, 165, 'sms', '2026-05-04 12:59:08', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-04 09:59:08'),
(83, 13, 216, 'sms', '2026-05-04 12:59:08', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-04 09:59:08'),
(84, 13, 218, 'sms', '2026-05-04 12:59:08', NULL, NULL, 3, 'HTTP 0 — ', 'pending', '2026-05-04 09:59:08');

-- --------------------------------------------------------

--
-- Table structure for table `result_notifications`
--

CREATE TABLE `result_notifications` (
  `id` int(11) NOT NULL,
  `result_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `api_response` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `result_notifications`
--

INSERT INTO `result_notifications` (`id`, `result_id`, `patient_id`, `phone`, `message`, `status`, `api_response`, `sent_at`) VALUES
(1, 7, 4, '256784165935', 'Dear AKELLO JANE, your SPUTUM FOR AFB SMEAR result from 03 May 2026 is ready and under review by your doctor at the facility. Ref: MDR-2026-0002. Do not reply to this message.', 'failed', '', '2026-05-03 13:12:02'),
(2, 9, 3, '256700765387', 'Dear EGUNU JEFF, your BLD result from 04 May 2026 is ready and under review by your doctor at the facility. Ref: MDR-2026-0001. Do not reply to this message.', 'failed', '', '2026-05-04 04:12:44');

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `reminder_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL COMMENT 'Snapshot of number at send time',
  `message` text DEFAULT NULL,
  `status` enum('sent','failed') DEFAULT 'sent',
  `message_id` varchar(100) DEFAULT NULL COMMENT 'API provider message ID',
  `delivery_report` enum('delivered','undelivered','expired','rejected','unknown') DEFAULT NULL,
  `cost` decimal(6,4) DEFAULT NULL,
  `api_response` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_logs`
--

INSERT INTO `sms_logs` (`id`, `reminder_id`, `patient_id`, `phone_number`, `message`, `status`, `message_id`, `delivery_report`, `cost`, `api_response`, `sent_at`, `updated_at`) VALUES
(1, NULL, NULL, '+256777777861', 'MDR-TB TEST: SMS integration is working via Twilio. Timestamp: 10:04:47', 'failed', NULL, NULL, NULL, '{\"code\":21608,\"message\":\"The number +25677777XXXX is unverified. Trial accounts cannot send messages to unverified numbers; verify +25677777XXXX at twilio.com/user/account/phone-numbers/verified, or purchase a Twilio number to send messages to unverified numbers\",\"more_info\":\"https://www.twilio.com/docs/errors/21608\",\"status\":400}', '2026-04-23 08:04:53', '2026-04-23 08:04:53'),
(2, NULL, NULL, '+256777777861', 'MDR-TB TEST: SMS integration is working via Twilio. Timestamp: 10:07:46', 'failed', NULL, NULL, NULL, '{\"code\":21608,\"message\":\"The number +25677777XXXX is unverified. Trial accounts cannot send messages to unverified numbers; verify +25677777XXXX at twilio.com/user/account/phone-numbers/verified, or purchase a Twilio number to send messages to unverified numbers\",\"more_info\":\"https://www.twilio.com/docs/errors/21608\",\"status\":400}', '2026-04-23 08:07:48', '2026-04-23 08:07:48'),
(3, NULL, NULL, '+256777777861', 'MDR-TB TEST: SMS integration is working via Twilio. Timestamp: 10:24:50', 'failed', NULL, NULL, NULL, '{\"code\":21608,\"message\":\"The number +25677777XXXX is unverified. Trial accounts cannot send messages to unverified numbers; verify +25677777XXXX at twilio.com/user/account/phone-numbers/verified, or purchase a Twilio number to send messages to unverified numbers\",\"more_info\":\"https://www.twilio.com/docs/errors/21608\",\"status\":400}', '2026-04-23 08:24:52', '2026-04-23 08:24:52'),
(4, NULL, NULL, '+256777777861', 'MDR-TB TEST: SMS integration is working via Africa\'s Talking. Timestamp: 11:49:01', 'failed', NULL, NULL, NULL, 'cURL error 35: error:0A00010B:SSL routines::wrong version number (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://api.sandbox.africastalking.com/version1/messaging', '2026-04-24 09:49:10', '2026-04-24 09:49:10'),
(5, NULL, NULL, '+256777777861', 'MDR-TB TEST: SMS integration is working via Africa\'s Talking. Timestamp: 11:52:39', 'failed', NULL, NULL, NULL, 'cURL error 35: error:0A00010B:SSL routines::wrong version number (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://api.sandbox.africastalking.com/version1/messaging', '2026-04-24 09:52:41', '2026-04-24 09:52:41'),
(6, NULL, NULL, '+256777777861', 'MDR-TB TEST: SMS integration is working via Africa\'s Talking. Timestamp: 12:02:54', 'failed', NULL, NULL, NULL, 'cURL error 35: error:0A00010B:SSL routines::wrong version number (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://api.sandbox.africastalking.com/version1/messaging', '2026-04-24 10:02:56', '2026-04-24 10:02:56'),
(7, NULL, NULL, '+256777777861', 'MDR-TB TEST: SMS integration is working via Africa\'s Talking. Timestamp: 12:03:14', 'failed', NULL, NULL, NULL, 'cURL error 35: error:0A00010B:SSL routines::wrong version number (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://api.sandbox.africastalking.com/version1/messaging', '2026-04-24 10:03:16', '2026-04-24 10:03:16'),
(8, NULL, NULL, '+256777777861', 'MDR-TB TEST: SMS integration is working via Africa\'s Talking. Timestamp: 12:03:56', 'failed', NULL, NULL, NULL, 'cURL error 28: Failed to connect to api.sandbox.africastalking.com port 443 after 21041 ms: Timed out (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://api.sandbox.africastalking.com/version1/messaging', '2026-04-24 10:04:17', '2026-04-24 10:04:17'),
(9, NULL, NULL, '+256777777861', 'MDR-TB TEST: SMS integration is working via Africa\'s Talking. Timestamp: 12:04:22', 'failed', NULL, NULL, NULL, 'cURL error 28: Failed to connect to api.sandbox.africastalking.com port 443 after 21052 ms: Timed out (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://api.sandbox.africastalking.com/version1/messaging', '2026-04-24 10:04:43', '2026-04-24 10:04:43'),
(10, NULL, NULL, '+256777777861', 'MDR-TB TEST: SMS integration is working via Africa\'s Talking. Timestamp: 12:04:54', 'failed', NULL, NULL, NULL, 'cURL error 28: Failed to connect to api.sandbox.africastalking.com port 443 after 21032 ms: Timed out (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://api.sandbox.africastalking.com/version1/messaging', '2026-04-24 10:05:15', '2026-04-24 10:05:15'),
(11, NULL, NULL, '+256777777861', 'MDR-TB TEST: SMS integration is working via Africa\'s Talking. Timestamp: 12:06:37', 'failed', NULL, NULL, NULL, 'cURL error 35: error:0A00010B:SSL routines::wrong version number (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://api.sandbox.africastalking.com/version1/messaging', '2026-04-24 10:06:39', '2026-04-24 10:06:39'),
(12, NULL, NULL, '+256777777861', 'MDR-TB TEST: SMS integration is working via Africa\'s Talking. Timestamp: 12:07:59', 'failed', NULL, NULL, NULL, 'cURL error 35: error:0A00010B:SSL routines::wrong version number (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://api.sandbox.africastalking.com/version1/messaging', '2026-04-24 10:08:04', '2026-04-24 10:08:04'),
(13, NULL, NULL, '+256777777861', 'MDR-TB Test: Integration successful! Timestamp: 12:09:34', 'failed', NULL, NULL, NULL, '', '2026-04-24 10:09:35', '2026-04-24 10:09:35'),
(14, NULL, NULL, '+256777777861', 'MDR-TB Test: Integration successful! Timestamp: 12:11:23', 'failed', NULL, NULL, NULL, '', '2026-04-24 10:11:25', '2026-04-24 10:11:25'),
(15, NULL, NULL, '+256777777861', 'MDR-TB Test: Integration successful! Timestamp: 12:13:52', 'failed', NULL, NULL, NULL, '', '2026-04-24 10:13:53', '2026-04-24 10:13:53'),
(16, NULL, NULL, '+256700765387', 'MDR-TB Test: Integration successful! Timestamp: 12:14:16', 'failed', NULL, NULL, NULL, '', '2026-04-24 10:14:17', '2026-04-24 10:14:17'),
(17, NULL, NULL, '+256700765387', 'MDR-TB Test: Integration successful! Timestamp: 12:16:17', '', NULL, NULL, NULL, 'CURL_ERROR: error:0A00010B:SSL routines::wrong version number', '2026-04-24 10:16:19', '2026-04-24 10:16:19'),
(18, NULL, NULL, '+256700765387', 'MDR-TB Test: Integration successful! Timestamp: 12:18:45', '', NULL, NULL, NULL, 'CURL_ERROR: Failed to connect to api.sandbox.africastalking.com port 443 after 21033 ms: Timed out', '2026-04-24 10:19:06', '2026-04-24 10:19:06'),
(19, NULL, NULL, '+256700765387', 'MDR-TB Test: Integration successful! Timestamp: 12:20:04', '', NULL, NULL, NULL, 'CURL_ERROR: Failed to connect to api.sandbox.africastalking.com port 443 after 21049 ms: Timed out', '2026-04-24 10:20:25', '2026-04-24 10:20:25'),
(20, NULL, NULL, '+256700765387', 'MDR-TB Test: Integration successful! Timestamp: 12:20:58', '', NULL, NULL, NULL, 'CURL_ERROR: Failed to connect to api.sandbox.africastalking.com port 443 after 21043 ms: Timed out', '2026-04-24 10:21:19', '2026-04-24 10:21:19'),
(21, NULL, NULL, '+256700765387', 'MDR-TB Test: Integration successful! Timestamp: 12:21:21', '', NULL, NULL, NULL, 'CURL_ERROR: Failed to connect to api.sandbox.africastalking.com port 443 after 21062 ms: Timed out', '2026-04-24 10:21:42', '2026-04-24 10:21:42'),
(22, NULL, NULL, '+256700765387', 'MDR-TB Test: Integration successful! Timestamp: 12:27:21', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_b5d75aa588f8f9bdb201036a363b9cf6\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-04-24 10:27:22', '2026-04-24 10:27:22'),
(23, NULL, NULL, '+256777777861', 'MDR-TB Test: Integration successful! Timestamp: 12:30:11', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_4b9e99589791fc24afc6c7adf429ccd6\",\"number\":\"+256777777861\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-04-24 10:30:13', '2026-04-24 10:30:13'),
(24, NULL, 3, '256700765387', 'MDR-TB Reminder: Hello EGUNU JEFF, it\'s time to take your Bedaquiline (400mg) at 02:01 PM. Please take your dose now.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_dbc2332a7c3cebea0e18c650bde42462\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-04-24 10:53:35', '2026-04-24 10:53:35'),
(25, NULL, 3, '256700765387', 'MDR-TB Reminder: Hello EGUNU JEFF, it\'s time to take your Bedaquiline (400mg) at 02:20 PM. Please take your dose now.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_50787b63f3af819dad77197454cd0169\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-04-24 11:15:43', '2026-04-24 11:15:43'),
(26, NULL, NULL, '+256777777861', 'MDR-TB Test: Integration successful! Timestamp: 11:09:06', '', NULL, NULL, NULL, 'CURL_ERROR: HTTP Code: 0', '2026-04-25 09:09:06', '2026-04-25 09:09:06'),
(27, NULL, NULL, '+256777777861', 'MDR-TB Test: Integration successful! Timestamp: 11:09:19', '', NULL, NULL, NULL, 'CURL_ERROR: HTTP Code: 0', '2026-04-25 09:09:19', '2026-04-25 09:09:19'),
(28, NULL, NULL, '+256777777861', 'MDR-TB Test: Integration successful! Timestamp: 11:09:55', '', NULL, NULL, NULL, 'CURL_ERROR: HTTP Code: 0', '2026-04-25 09:09:55', '2026-04-25 09:09:55'),
(29, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_01f1aaa4da535c23f8ba037d4f6e55bf\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:37:48', '2026-05-01 05:37:48'),
(30, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_b7eb9077ed60f08f9ff44b02b51f759a\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:41:56', '2026-05-01 05:41:56'),
(31, NULL, NULL, '0777777861', 'GxAlert Test: Integration successful! Timestamp: 07:43:10', 'failed', NULL, NULL, NULL, 'Request is missing required form field \'username\'', '2026-05-01 05:43:11', '2026-05-01 05:43:11'),
(32, NULL, NULL, '0777777861', 'GxAlert Test: Integration successful! Timestamp: 07:45:18', 'failed', NULL, NULL, NULL, 'Request is missing required form field \'username\'', '2026-05-01 05:45:19', '2026-05-01 05:45:19'),
(33, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Para-aminosalicylic acid (4000mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_c5c3f5a12fa14bd11dbf60cd341fdd7d\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:46:36', '2026-05-01 05:46:36'),
(34, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Prothionamide (500mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_1af62a2179ef84638dc8fa46a583ec34\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:46:37', '2026-05-01 05:46:37'),
(35, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Terizidone (500mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_8113b296b77ed7c96958fd7ec4cd48df\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:46:39', '2026-05-01 05:46:39'),
(36, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Meropenem (1000mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_912a201e8a7e8376ace4d64f6e88ad28\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:46:40', '2026-05-01 05:46:40'),
(37, NULL, NULL, '256784165935', 'GxAlert Reminder: It\'s time to take your Bedaquiline (400mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_475f6dad7ca5af880052eac3fdf3632f\",\"number\":\"+256784165935\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:46:41', '2026-05-01 05:46:41'),
(38, NULL, NULL, '256784165935', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_9e8366bdd9bce7e3227506e742341c09\",\"number\":\"+256784165935\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:46:42', '2026-05-01 05:46:42'),
(39, NULL, NULL, '256784165935', 'GxAlert Reminder: It\'s time to take your Delamanid (100mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_f93fb2984706f39d71436e03c3fd92af\",\"number\":\"+256784165935\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:46:43', '2026-05-01 05:46:43'),
(40, NULL, NULL, '256784165935', 'GxAlert Reminder: It\'s time to take your Levofloxacin (750mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_ff59c2d46eb0e40cc44b33c9a1da35be\",\"number\":\"+256784165935\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:46:45', '2026-05-01 05:46:45'),
(41, NULL, NULL, '256784165935', 'GxAlert Reminder: It\'s time to take your Moxifloxacin (400mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_00cc5b867dd9869fda86f99e50b7e992\",\"number\":\"+256784165935\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:46:46', '2026-05-01 05:46:46'),
(42, NULL, NULL, '256784165935', 'GxAlert Reminder: It\'s time to take your Cycloserine (500mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_a5384226b4b5b7a0d972a8187da04ace\",\"number\":\"+256784165935\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:46:47', '2026-05-01 05:46:47'),
(43, NULL, NULL, '256784165935', 'GxAlert Reminder: It\'s time to take your Para-aminosalicylic acid (4000mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_de73a78f8e6fdae4d528eebef2d992be\",\"number\":\"+256784165935\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 05:46:48', '2026-05-01 05:46:48'),
(44, NULL, NULL, '0777777861', 'GxAlert Test: Integration successful! Timestamp: 07:58:41', 'failed', NULL, NULL, NULL, 'Request is missing required form field \'username\'', '2026-05-01 05:58:42', '2026-05-01 05:58:42'),
(45, NULL, NULL, '0777777861', 'GxAlert Test: Integration successful! Timestamp: 07:59:21', 'failed', NULL, NULL, NULL, 'The resource requires authentication, which was not supplied with the request', '2026-05-01 05:59:23', '2026-05-01 05:59:23'),
(46, NULL, NULL, '256777777861', 'GxAlert Test: Integration successful! Timestamp: 08:01:32', 'failed', NULL, NULL, NULL, 'The resource requires authentication, which was not supplied with the request', '2026-05-01 06:01:33', '2026-05-01 06:01:33'),
(47, NULL, NULL, '+256777777861', 'GxAlert Test: Integration successful! Timestamp: 08:03:00', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_cef300c6860234d30fc31feabb705476\",\"number\":\"+256777777861\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 06:03:03', '2026-05-01 06:03:03'),
(48, NULL, NULL, '256784165935', 'GxAlert Reminder: It\'s time to take your Bedaquiline (400mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_e198472ca2fe203d82468ecb6281bbde\",\"number\":\"+256784165935\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 06:36:36', '2026-05-01 06:36:36'),
(49, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_e0f010a75e5aa0622f3598f5f9ce8829\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 06:41:44', '2026-05-01 06:41:44'),
(50, NULL, NULL, '256784165935', 'GxAlert Reminder: It\'s time to take your Bedaquiline (400mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_f147d6d09361c773b3dde382c73fd908\",\"number\":\"+256784165935\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 06:41:47', '2026-05-01 06:41:47'),
(51, NULL, NULL, '256784165935', 'GxAlert Reminder: It\'s time to take your Bedaquiline (400mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_d40f3a1c62f0afefcbdba98ce00ac2fd\",\"number\":\"+256784165935\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 06:41:49', '2026-05-01 06:41:49'),
(52, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_b5f6585fb5d71b187754983f63437d19\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 06:56:36', '2026-05-01 06:56:36'),
(53, NULL, NULL, '+2567000000001', 'GxAlert Test: Integration successful! Timestamp: 09:05:32', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 0/1 Total Cost: 0 Message parts: 1\",\"Recipients\":[{\"cost\":\"0\",\"messageId\":\"None\",\"number\":\"+2567000000001\",\"status\":\"InvalidPhoneNumber\",\"statusCode\":403}]}}', '2026-05-01 07:05:34', '2026-05-01 07:05:34'),
(54, NULL, NULL, '+256700765387', 'GxAlert: Your appointment is due soon. Please contact your facility to confirm. woked here', 'sent', 'ATXid_a2050b842ce10dddf7128f3e62368189', NULL, '35.0000', '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_a2050b842ce10dddf7128f3e62368189\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 07:10:59', '2026-05-01 07:10:59'),
(55, NULL, NULL, '+256700765387', 'GxAlert: Your appointment is due soon. Please contact your facility to confirm. woked here', 'sent', 'ATXid_a2050b842ce10dddf7128f3e62368189', NULL, '35.0000', '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_a2050b842ce10dddf7128f3e62368189\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 07:10:59', '2026-05-01 07:10:59'),
(56, NULL, NULL, '+256774206902', 'GxAlert: Your appointment is due soon. Please contact your facility to confirm. bad', 'sent', 'ATXid_12b6090a268693efaa9c243e519bf76e', NULL, '35.0000', '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_12b6090a268693efaa9c243e519bf76e\",\"number\":\"+256774206902\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 07:12:49', '2026-05-01 07:12:49'),
(57, NULL, NULL, '+256774206902', 'GxAlert: Your appointment is due soon. Please contact your facility to confirm. bad', 'sent', 'ATXid_12b6090a268693efaa9c243e519bf76e', NULL, '35.0000', '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_12b6090a268693efaa9c243e519bf76e\",\"number\":\"+256774206902\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 07:12:49', '2026-05-01 07:12:49'),
(58, NULL, NULL, '+256700000000', 'GxAlert Test: Integration successful! Timestamp: 09:16:18', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_2f65fc364abb8f0d5686bf8302e5a76f\",\"number\":\"+256700000000\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 07:16:19', '2026-05-01 07:16:19'),
(59, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_84982086b18169b065a2ee6c048522c0\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 07:16:36', '2026-05-01 07:16:36'),
(60, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00 AM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_4c9e5bbdf3ffc11c9553d8d8838fa44e\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 07:16:38', '2026-05-01 07:16:38'),
(61, 31, 3, '+256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00. Reply HELP for support.', 'sent', 'ATXid_8a096f798a0b7e456b9109b08ae87f7c', NULL, '35.0000', '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_8a096f798a0b7e456b9109b08ae87f7c\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 07:18:31', '2026-05-01 07:18:31'),
(62, 37, 3, '+256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00. Reply HELP for support.', 'failed', NULL, NULL, NULL, 'CURL_ERROR: error:0A00010B:SSL routines::wrong version number', '2026-05-01 11:34:21', '2026-05-01 11:34:21'),
(63, 38, 3, '+256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00. Reply HELP for support.', 'failed', NULL, NULL, NULL, 'CURL_ERROR: error:0A00010B:SSL routines::wrong version number', '2026-05-01 11:36:27', '2026-05-01 11:36:27'),
(64, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 04:00 PM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_f2c3cb6710ccbb91b57a307fa3562a35\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 14:01:40', '2026-05-01 14:01:40'),
(65, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Para-aminosalicylic acid (4000mg). Please take your dose at 04:00 PM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_1f4db17797955f2d45ec00cc9b0c399a\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 14:01:42', '2026-05-01 14:01:42'),
(66, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Terizidone (500mg). Please take your dose at 04:00 PM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_19b0ecfdf190d3abea721e8b75767d4c\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 14:01:45', '2026-05-01 14:01:45'),
(67, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Meropenem (1000mg). Please take your dose at 04:00 PM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_493ff116fc5b5ba6e572a487c0126193\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 14:01:46', '2026-05-01 14:01:46'),
(68, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Prothionamide (500mg). Please take your dose at 04:00 PM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_4b8dd7d624adb84ed8d84c72cc77a320\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-01 14:06:36', '2026-05-01 14:06:36'),
(69, 67, 3, '+256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00. Reply HELP for support.', 'failed', NULL, NULL, NULL, 'CURL_ERROR: error:0A00010B:SSL routines::wrong version number', '2026-05-03 13:13:01', '2026-05-03 13:13:01'),
(70, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 04:00 PM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_021f0c2247149640b65c1b4a9d878220\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-03 13:56:37', '2026-05-03 13:56:37'),
(71, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Para-aminosalicylic acid (4000mg). Please take your dose at 04:00 PM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_7fc1724d7db1359aa41ebc87bd0af1f5\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-03 13:56:39', '2026-05-03 13:56:39'),
(72, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Prothionamide (500mg). Please take your dose at 04:00 PM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_8ca3cf4f6da8f94f5769a239a0d31796\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-03 13:56:40', '2026-05-03 13:56:40'),
(73, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Terizidone (500mg). Please take your dose at 04:00 PM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_657a510db40b5c709ad73cb1307ff1d8\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-03 13:56:42', '2026-05-03 13:56:42'),
(74, NULL, 3, '256700765387', 'GxAlert Reminder: It\'s time to take your Meropenem (1000mg). Please take your dose at 04:00 PM. Reply HELP for support.', '', NULL, NULL, NULL, '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_5838bc201a8432e10e9d1777057aa745\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-03 13:56:43', '2026-05-03 13:56:43'),
(75, 75, 3, '+256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00. Reply HELP for support.', 'sent', 'ATXid_dd9b560c7aa9b20ed8833ec361edf386', NULL, '35.0000', '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_dd9b560c7aa9b20ed8833ec361edf386\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-03 14:15:21', '2026-05-03 14:15:21'),
(76, 76, 3, '+256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00. Reply HELP for support.', 'sent', 'ATXid_b080607120b0b3401cc86d161f468bb9', NULL, '35.0000', '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_b080607120b0b3401cc86d161f468bb9\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-03 15:02:27', '2026-05-03 15:02:27'),
(77, 77, 3, '+256700765387', 'GxAlert Reminder: It\'s time to take your Clofazimine (100mg). Please take your dose at 08:00. Reply HELP for support.', 'sent', 'ATXid_ba97ed0d303e3d3841413820c7e2d729', NULL, '35.0000', '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_ba97ed0d303e3d3841413820c7e2d729\",\"number\":\"+256700765387\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-03 15:30:49', '2026-05-03 15:30:49'),
(78, NULL, NULL, '+256777777861', 'GxAlert: Your appointment is due soon. Please contact your facility to confirm.', 'sent', 'ATXid_d0f717ef926ab4ac4c772bc36d7c56ee', NULL, '35.0000', '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_d0f717ef926ab4ac4c772bc36d7c56ee\",\"number\":\"+256777777861\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-03 15:31:26', '2026-05-03 15:31:26'),
(79, NULL, NULL, '+256777777861', 'GxAlert: Your appointment is due soon. Please contact your facility to confirm.', 'sent', 'ATXid_d0f717ef926ab4ac4c772bc36d7c56ee', NULL, '35.0000', '{\"SMSMessageData\":{\"Message\":\"Sent to 1/1 Total Cost: EUR 0.0093 Message parts: 1\",\"Recipients\":[{\"cost\":\"UGX 35.0000\",\"messageId\":\"ATXid_d0f717ef926ab4ac4c772bc36d7c56ee\",\"number\":\"+256777777861\",\"status\":\"Success\",\"statusCode\":101}]}}', '2026-05-03 15:31:26', '2026-05-03 15:31:26');

-- --------------------------------------------------------

--
-- Table structure for table `treatment_outcomes`
--

CREATE TABLE `treatment_outcomes` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `regimen_id` int(11) NOT NULL,
  `outcome` enum('cured','treatment_completed','treatment_failed','died','lost_to_followup','not_evaluated','changed_regimen') NOT NULL,
  `outcome_date` date NOT NULL,
  `determined_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `treatment_regimens`
--

CREATE TABLE `treatment_regimens` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `prescribed_by` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `regimen_name` varchar(100) DEFAULT NULL,
  `status` enum('active','completed','discontinued','changed','pending_review','rejected') DEFAULT 'pending_review',
  `reviewed_at` datetime DEFAULT NULL,
  `discontinuation_reason` varchar(255) DEFAULT NULL,
  `discontinued_at` date DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treatment_regimens`
--

INSERT INTO `treatment_regimens` (`id`, `patient_id`, `prescribed_by`, `reviewed_by`, `regimen_name`, `status`, `reviewed_at`, `discontinuation_reason`, `discontinued_at`, `start_date`, `end_date`, `notes`, `review_notes`, `updated_at`, `created_at`) VALUES
(6, 3, 3, NULL, 'start', 'discontinued', NULL, NULL, '2026-04-24', '2026-04-21', '2026-04-22', NULL, NULL, '2026-04-24 11:02:08', '2026-04-21 06:41:03'),
(7, 3, 3, NULL, NULL, 'discontinued', NULL, NULL, '2026-04-24', '2026-04-21', NULL, NULL, NULL, '2026-04-24 11:04:43', '2026-04-21 06:50:48'),
(8, 3, 3, NULL, NULL, 'discontinued', NULL, NULL, '2026-05-01', '2026-04-24', '2026-04-24', '\n[AE #2] Drug modification flagged by doctor', NULL, '2026-05-01 04:28:27', '2026-04-24 10:50:09'),
(9, 3, 3, NULL, 'ghjkl', 'discontinued', NULL, NULL, '2026-05-01', '2026-04-24', '2026-04-24', '\n[AE #2] Drug modification flagged by doctor', NULL, '2026-05-01 04:36:33', '2026-04-24 11:02:08'),
(10, 3, 3, NULL, NULL, 'discontinued', NULL, NULL, '2026-05-01', '2026-04-24', NULL, '\n[AE #2] Drug modification flagged by doctor', NULL, '2026-05-01 04:55:36', '2026-04-24 11:04:43'),
(13, 3, 3, NULL, 'start', 'discontinued', NULL, NULL, '2026-05-01', '2026-05-01', '2026-05-02', 'AA', NULL, '2026-05-01 04:55:36', '2026-05-01 04:28:27'),
(14, 3, 3, NULL, NULL, 'active', NULL, NULL, NULL, '2026-05-01', '2026-05-02', NULL, NULL, '2026-05-01 04:36:33', '2026-05-01 04:36:33'),
(27, 13, 3, NULL, 'start', 'discontinued', NULL, NULL, '2026-05-03', '2026-05-03', NULL, 'aa', NULL, '2026-05-03 10:11:26', '2026-05-03 10:06:53'),
(28, 13, 3, NULL, 'start', 'discontinued', NULL, NULL, '2026-05-03', '2026-05-03', '2026-05-04', 'aaa', NULL, '2026-05-03 10:13:42', '2026-05-03 10:11:26'),
(29, 13, 3, NULL, 'start', 'discontinued', NULL, NULL, '2026-05-03', '2026-05-03', NULL, NULL, NULL, '2026-05-03 10:23:48', '2026-05-03 10:13:42'),
(30, 13, 3, NULL, 'start', 'discontinued', NULL, NULL, '2026-05-03', '2026-05-03', '2026-05-04', NULL, NULL, '2026-05-03 10:24:33', '2026-05-03 10:23:49'),
(31, 13, 3, NULL, 'start', 'discontinued', NULL, NULL, '2026-05-03', '2026-05-03', NULL, 'q', NULL, '2026-05-03 10:29:52', '2026-05-03 10:24:33'),
(32, 13, 3, NULL, NULL, 'discontinued', NULL, NULL, '2026-05-03', '2026-05-03', NULL, NULL, NULL, '2026-05-03 10:31:15', '2026-05-03 10:29:52'),
(33, 13, 3, NULL, NULL, 'discontinued', NULL, NULL, '2026-05-03', '2026-05-03', NULL, NULL, NULL, '2026-05-03 10:33:59', '2026-05-03 10:31:15'),
(34, 13, 3, NULL, NULL, 'discontinued', NULL, NULL, '2026-05-03', '2026-05-03', NULL, NULL, NULL, '2026-05-03 15:04:23', '2026-05-03 10:33:59'),
(35, 12, 3, NULL, NULL, 'discontinued', NULL, NULL, '2026-05-03', '2026-05-03', NULL, 'www', NULL, '2026-05-03 10:42:37', '2026-05-03 10:41:18'),
(36, 12, 3, NULL, 'startaaa', 'discontinued', NULL, NULL, '2026-05-03', '2026-05-03', '2026-05-04', NULL, NULL, '2026-05-03 10:44:51', '2026-05-03 10:42:37'),
(37, 12, 3, 1, 'startaaa', 'discontinued', '2026-05-03 14:44:42', NULL, '2026-05-03', '2026-05-03', NULL, NULL, NULL, '2026-05-03 11:47:16', '2026-05-03 10:44:51'),
(38, 12, 3, NULL, 'New', 'discontinued', NULL, NULL, '2026-05-03', '2026-05-03', NULL, NULL, NULL, '2026-05-03 11:53:53', '2026-05-03 11:47:16'),
(39, 12, 3, 1, 'New1', 'rejected', '2026-05-03 14:54:07', NULL, NULL, '2026-05-03', NULL, NULL, NULL, '2026-05-03 11:54:07', '2026-05-03 11:53:53'),
(40, 12, 3, 1, 'May', 'active', '2026-05-03 18:15:36', NULL, NULL, '2026-05-03', NULL, NULL, NULL, '2026-05-03 15:15:36', '2026-05-03 11:54:23'),
(41, 13, 3, 1, 'startaaa', 'active', '2026-05-03 18:19:09', NULL, NULL, '2026-05-03', NULL, 'kjhgfdghj', NULL, '2026-05-03 15:19:09', '2026-05-03 15:04:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL COMMENT 'NULL for staff, value for patients',
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('doctor','nurse','clinician','lab_personnel','data_officer','admin','patient') NOT NULL,
  `location` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `image_paths` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `patient_id`, `name`, `email`, `password`, `role`, `location`, `phone`, `facility_id`, `image_paths`, `last_login`, `created_at`, `is_active`) VALUES
(1, NULL, 'Dr. Sarah Kato', 'doctor@mdr.com', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', 'doctor', 'National Tuberculosis Reference Lab', NULL, 1, '', '2026-05-03 18:04:48', '2026-04-20 13:14:23', 1),
(2, NULL, 'Nurse James Mwangi', 'nurse@mdr.com', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', 'nurse', 'National Tuberculosis Reference Lab', NULL, NULL, '', '2026-05-01 18:00:48', '2026-04-20 13:14:23', 1),
(3, NULL, 'Clinician Grace Achieng', 'clinician@mdr.com', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', 'clinician', 'National Tuberculosis Reference Lab', NULL, NULL, '', '2026-05-03 18:03:57', '2026-04-20 13:14:23', 1),
(4, NULL, 'Lab Tech Brian Okello', 'lab@mdr.com', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', 'lab_personnel', 'National Tuberculosis Reference Lab', NULL, NULL, '', '2026-05-04 07:08:09', '2026-04-20 13:14:23', 1),
(5, NULL, 'Data Officer Alex', 'data@mdr.com', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', 'data_officer', 'National Tuberculosis Reference Lab', NULL, NULL, '', '2026-05-03 18:34:41', '2026-04-20 13:14:23', 1),
(6, NULL, 'MDR Admin Root', 'admin@mdr.com', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', 'admin', 'National Tuberculosis Reference Lab', NULL, NULL, '', '2026-05-03 18:30:32', '2026-04-20 13:14:23', 1),
(7, 3, 'EGUNU JEFF', 'patient@mdr.com', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', 'patient', 'National Tuberculosis Reference Lab', NULL, NULL, 'default.png', '2026-05-03 10:29:12', '2026-04-20 15:32:55', 1),
(9, NULL, 'AKELLO JANE', 'akello@mdr.com', '$2y$10$RihxIb54bGLvThE1GYy8vuh7758a6Dz1fJx0nTR2tGtS5xPIYfCZ2', 'patient', 'National Tuberculosis Reference Lab', NULL, NULL, '', NULL, '2026-04-23 17:17:00', 1),
(10, NULL, 'AKELLO JANE', 'akello1@mdr.com', '$2y$10$SBZGAfZzEIn3P5BStlG7/.jXjog1Aqu69r/ucklRiths3XKQdeISi', 'patient', 'National Tuberculosis Reference Lab', NULL, NULL, '', NULL, '2026-04-23 17:19:46', 1),
(11, NULL, 'Alex Extensions', 'asas@mdr.com', '0', 'patient', 'Soroti, Ugandaaaaa', '256783874407', 1, '', NULL, '2026-05-03 13:15:54', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adherence_logs`
--
ALTER TABLE `adherence_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reminder_id` (`reminder_id`),
  ADD KEY `adherence_logs_ibfk_3` (`schedule_id`),
  ADD KEY `adherence_logs_ibfk_4` (`verified_by`),
  ADD KEY `idx_patient_dose` (`patient_id`,`schedule_id`,`dose_date`);

--
-- Indexes for table `adverse_events`
--
ALTER TABLE `adverse_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `drug_id` (`drug_id`),
  ADD KEY `adverse_events_ibfk_3` (`reported_by`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `appointments_ibfk_2` (`assigned_to`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `table_record` (`table_name`,`record_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `drugs`
--
ALTER TABLE `drugs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `drug_code` (`drug_code`);

--
-- Indexes for table `drug_susceptibility`
--
ALTER TABLE `drug_susceptibility`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `drug_id` (`drug_id`),
  ADD KEY `drug_susceptibility_ibfk_3` (`performed_by`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `facility_code` (`facility_code`);

--
-- Indexes for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `medication_schedule`
--
ALTER TABLE `medication_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `regimen_id` (`regimen_id`),
  ADD KEY `medication_schedule_ibfk_2` (`drug_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_unread` (`user_id`,`is_read`),
  ADD KEY `idx_role_unread` (`user_role`,`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `patient_code` (`patient_code`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `facility_id` (`facility_id`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `fk_patient_regimen` (`regimen_id`);

--
-- Indexes for table `regimen_drugs`
--
ALTER TABLE `regimen_drugs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `regimen_id` (`regimen_id`),
  ADD KEY `drug_id` (`drug_id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `idx_reminder_time` (`reminder_datetime`),
  ADD KEY `idx_status_time` (`status`,`reminder_datetime`);

--
-- Indexes for table `result_notifications`
--
ALTER TABLE `result_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_result_notified` (`result_id`),
  ADD KEY `idx_patient` (`patient_id`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `sms_logs_ibfk_2` (`reminder_id`);

--
-- Indexes for table `treatment_outcomes`
--
ALTER TABLE `treatment_outcomes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `regimen_id` (`regimen_id`),
  ADD KEY `treatment_outcomes_ibfk_3` (`determined_by`);

--
-- Indexes for table `treatment_regimens`
--
ALTER TABLE `treatment_regimens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `treatment_regimens_ibfk_2` (`prescribed_by`),
  ADD KEY `fk_reviewed_by` (`reviewed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_user_patient_link` (`patient_id`),
  ADD KEY `fk_users_facility` (`facility_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adherence_logs`
--
ALTER TABLE `adherence_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=218;

--
-- AUTO_INCREMENT for table `adverse_events`
--
ALTER TABLE `adverse_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `drugs`
--
ALTER TABLE `drugs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `drug_susceptibility`
--
ALTER TABLE `drug_susceptibility`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lab_results`
--
ALTER TABLE `lab_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `medication_schedule`
--
ALTER TABLE `medication_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=219;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `regimen_drugs`
--
ALTER TABLE `regimen_drugs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `result_notifications`
--
ALTER TABLE `result_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `treatment_outcomes`
--
ALTER TABLE `treatment_outcomes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `treatment_regimens`
--
ALTER TABLE `treatment_regimens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `adherence_logs`
--
ALTER TABLE `adherence_logs`
  ADD CONSTRAINT `adherence_logs_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `adherence_logs_ibfk_2` FOREIGN KEY (`reminder_id`) REFERENCES `reminders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `adherence_logs_ibfk_3` FOREIGN KEY (`schedule_id`) REFERENCES `medication_schedule` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `adherence_logs_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `adverse_events`
--
ALTER TABLE `adverse_events`
  ADD CONSTRAINT `adverse_events_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `adverse_events_ibfk_2` FOREIGN KEY (`drug_id`) REFERENCES `drugs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `adverse_events_ibfk_3` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `drug_susceptibility`
--
ALTER TABLE `drug_susceptibility`
  ADD CONSTRAINT `drug_susceptibility_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `drug_susceptibility_ibfk_2` FOREIGN KEY (`drug_id`) REFERENCES `drugs` (`id`),
  ADD CONSTRAINT `drug_susceptibility_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD CONSTRAINT `lab_results_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_results_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `medication_schedule`
--
ALTER TABLE `medication_schedule`
  ADD CONSTRAINT `medication_schedule_ibfk_1` FOREIGN KEY (`regimen_id`) REFERENCES `treatment_regimens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medication_schedule_ibfk_2` FOREIGN KEY (`drug_id`) REFERENCES `drugs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patient_regimen` FOREIGN KEY (`regimen_id`) REFERENCES `treatment_regimens` (`id`),
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `patients_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `regimen_drugs`
--
ALTER TABLE `regimen_drugs`
  ADD CONSTRAINT `regimen_drugs_ibfk_1` FOREIGN KEY (`regimen_id`) REFERENCES `treatment_regimens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `regimen_drugs_ibfk_2` FOREIGN KEY (`drug_id`) REFERENCES `drugs` (`id`);

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reminders_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `medication_schedule` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `result_notifications`
--
ALTER TABLE `result_notifications`
  ADD CONSTRAINT `fk_rn_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rn_result` FOREIGN KEY (`result_id`) REFERENCES `lab_results` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD CONSTRAINT `sms_logs_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sms_logs_ibfk_2` FOREIGN KEY (`reminder_id`) REFERENCES `reminders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `treatment_outcomes`
--
ALTER TABLE `treatment_outcomes`
  ADD CONSTRAINT `treatment_outcomes_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatment_outcomes_ibfk_2` FOREIGN KEY (`regimen_id`) REFERENCES `treatment_regimens` (`id`),
  ADD CONSTRAINT `treatment_outcomes_ibfk_3` FOREIGN KEY (`determined_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `treatment_regimens`
--
ALTER TABLE `treatment_regimens`
  ADD CONSTRAINT `fk_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `treatment_regimens_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatment_regimens_ibfk_2` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_patient_link` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_users_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
