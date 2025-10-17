-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 17, 2025 at 06:48 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pharmacy_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(5, 'antibiotic'),
(2, 'cold'),
(4, 'cough'),
(1, 'Flu'),
(3, 'headache');

-- --------------------------------------------------------

--
-- Table structure for table `customer_history`
--

CREATE TABLE `customer_history` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_id_no` varchar(255) DEFAULT NULL,
  `total_visits` int(11) NOT NULL DEFAULT 1,
  `total_spent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_visit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_history`
--

INSERT INTO `customer_history` (`id`, `customer_name`, `customer_id_no`, `total_visits`, `total_spent`, `last_visit`) VALUES
(1, 'Mr.logan', '123', 1, 8.00, '2025-08-31 03:03:19'),
(2, 'Mr. Wayne', '1245', 1, 16.00, '2025-08-31 03:23:29'),
(3, 'Walk-in', '', 7, 105.00, '2025-09-19 02:49:07'),
(4, 'Mr. Banner', '1235', 1, 8.00, '2025-08-31 04:03:19'),
(5, 'Mr. Pogi', '11111', 1, 8.00, '2025-08-31 16:38:40'),
(6, 'Balong', '', 1, 100.00, '2025-08-31 17:06:50'),
(7, 'Ms. Elyn', '123', 1, 16.00, '2025-08-31 17:18:14'),
(8, 'Ms.elyn', '123', 1, 8.00, '2025-08-31 17:19:22'),
(9, 'shhheesshh', '1234', 1, 24.00, '2025-09-15 04:42:41'),
(10, 'pamisa', '12345', 1, 24.00, '2025-09-15 04:44:21'),
(11, 'papa', '1234', 1, 24.00, '2025-09-15 04:45:07'),
(12, 'pamisa', '1234', 1, 20.00, '2025-09-19 01:38:21'),
(13, 'Lhandel', '', 1, 50.00, '2025-09-19 01:44:02'),
(14, 'Lhandel', '1234', 1, 92.00, '2025-09-19 01:44:40'),
(15, 'dada', '1234', 1, 20.00, '2025-09-22 06:34:29');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `item_total` int(11) DEFAULT NULL,
  `items_per_stock` int(11) DEFAULT 0,
  `date_added` datetime DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `image_data` longblob DEFAULT NULL,
  `image_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `lot_number`, `category_id`, `price`, `cost`, `stock`, `item_total`, `items_per_stock`, `date_added`, `expiration_date`, `supplier`, `batch_number`, `image_path`, `image_data`, `image_type`) VALUES
(9, 'Paracetamol', '1', 1, 10.00, 10.00, 0, 0, 10, '2025-08-30 12:22:19', '2025-09-30', 'Mark', '1', NULL, NULL, NULL),
(10, 'Paracetamol', '2', 1, 10.00, 10.00, 0, 0, 10, '2025-08-30 12:26:18', '2025-09-30', 'Mark', '2', NULL, NULL, NULL),
(11, 'Paracetamol', '3', 1, 10.00, 10.00, 0, 0, 10, '2025-08-30 12:32:11', '2025-09-30', 'Mark', '3', NULL, NULL, NULL),
(12, 'Biogesic', '2', 2, 10.00, 10.00, 1, 1, 10, '2025-08-30 12:47:22', '2025-09-30', 'Mark', '1', NULL, NULL, NULL),
(13, 'Diatabs', '2', 1, 10.00, 10.00, 1, 3, 5, '2025-08-30 12:49:11', '2025-09-10', 'Mark', '1', NULL, NULL, NULL),
(14, 'saridon', '1', 3, 10.00, 10.00, 1, 5, 5, '2025-08-30 12:57:35', '2025-08-30', 'Mark', '1', NULL, NULL, NULL),
(17, 'amoxicillin', '1', 5, 10.00, 10.00, 3, 22, 10, '2025-08-30 21:52:23', '2025-10-01', 'Mark', '1', NULL, NULL, NULL),
(20, 'Bioflu', '1', 1, 10.00, 15.00, 4, 17, 5, '2025-08-31 20:31:58', '2025-09-29', 'Mark', '1', NULL, NULL, NULL),
(34, 'sheesh', '1', 5, 15.00, 10.00, 1, 1, 1, '2025-09-19 03:43:04', '2025-10-19', 'Pamisa', '3', 'uploads/products/68ccb528a236d_1758246184.png', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_history`
--

CREATE TABLE `product_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `item_total` int(11) DEFAULT NULL,
  `date_added` datetime DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_history`
--

INSERT INTO `product_history` (`id`, `product_id`, `name`, `lot_number`, `category_id`, `price`, `cost`, `stock`, `item_total`, `date_added`, `expiration_date`, `supplier`, `batch_number`, `image_path`, `deleted_at`) VALUES
(2, 19, 'Ibuprofen', '123', 1, 10.00, 10.00, 5, 49, '2025-08-31 06:02:04', '2025-10-01', 'Mark', '1', NULL, '2025-08-31 04:07:46'),
(3, 26, 'sheesh', '3', 5, 10.00, 10.00, 10, 3, '2025-09-15 03:06:41', '2025-10-15', 'Pamisa', '3', 'uploads/products/68c766a1d5b60_1757898401.png', '2025-09-15 01:33:21'),
(4, 28, 'sheesh', '3', 5, 15.00, 10.00, 10, 10, '2025-09-15 03:44:57', '2025-10-15', 'Pamisa', '3', NULL, '2025-09-15 01:45:09'),
(5, 27, 'meow', '3', 5, 10.00, 10.00, 10, 10, '2025-09-15 03:15:35', '2025-10-15', 'Pamisa', '3', NULL, '2025-09-15 01:45:21'),
(6, 29, 'sheesh', '3', 5, 15.00, 10.00, 10, 10, '2025-09-15 03:45:48', '2025-10-15', 'Pamisa', '3', 'uploads/products/68c76fccad26a_1757900748.png', '2025-09-15 01:47:38'),
(7, 15, 'Plemex', '1', 4, 10.00, 10.00, 10, 99, '2025-08-30 20:52:16', '2025-10-01', 'Mark', '1', NULL, '2025-09-15 01:49:01'),
(8, 18, 'Neozep', '1', 1, 10.00, 10.00, 5, 49, '2025-08-31 05:21:53', '2025-10-01', 'Mark', '1', NULL, '2025-09-15 01:49:15'),
(9, 16, 'lagundi', '1', 4, 10.00, 8.00, 20, 58, '2025-08-30 21:20:55', '2025-10-01', 'Mark', '1', NULL, '2025-09-15 01:49:33'),
(10, 30, 'sheesh', '3', 5, 15.00, 10.00, 10, 15, '2025-09-15 06:27:53', '2025-10-15', 'Pamisa', '3', NULL, '2025-09-15 04:34:41'),
(11, 31, 'sheesh', '3', 5, 15.00, 10.00, 10, 10, '2025-09-15 06:35:09', '2025-10-15', 'Pamisa', '3', 'uploads/products/68c7977db614b_1757910909.png', '2025-09-15 04:40:31'),
(12, 32, 'sheesh', '1', 5, 15.00, 10.00, 10, 10, '2025-09-19 03:34:53', '2025-10-19', 'Pamisa', '3', NULL, '2025-09-19 01:35:07'),
(13, 33, 'sheesh', '3', 5, 15.00, 10.00, 9, 9, '2025-09-19 03:35:47', '2025-10-19', 'Pamisa', '3', 'uploads/products/68ccb373a1b64_1758245747.png', '2025-09-19 01:39:04');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_history`
--

CREATE TABLE `purchase_history` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_history`
--

INSERT INTO `purchase_history` (`id`, `transaction_id`, `product_name`, `quantity`, `total_price`, `transaction_date`) VALUES
(1, NULL, 'amoxicillin', 10, 100.00, '2025-08-31 11:06:50'),
(2, 7, 'amoxicillin', 10, 100.00, '2025-08-31 17:06:50'),
(3, NULL, 'Biogesic', 1, 10.00, '2025-08-31 11:18:14'),
(4, NULL, 'Diatabs', 1, 10.00, '2025-08-31 11:18:14'),
(5, 8, 'Biogesic', 1, 10.00, '2025-08-31 17:18:14'),
(6, 8, 'Diatabs', 1, 10.00, '2025-08-31 17:18:14'),
(7, NULL, 'amoxicillin', 1, 10.00, '2025-08-31 11:19:22'),
(8, 9, 'amoxicillin', 1, 10.00, '2025-08-31 17:19:22'),
(9, NULL, 'lagundi', 2, 20.00, '2025-08-31 12:10:15'),
(10, 10, 'lagundi', 2, 20.00, '2025-08-31 18:10:15'),
(11, NULL, 'amoxicillin', 1, 10.00, '2025-08-31 13:26:57'),
(12, 11, 'amoxicillin', 1, 10.00, '2025-08-31 19:26:57'),
(13, NULL, 'amoxicillin', 1, 10.00, '2025-09-14 22:41:23'),
(14, 12, 'amoxicillin', 1, 10.00, '2025-09-15 04:41:23'),
(15, NULL, 'amoxicillin', 1, 10.00, '2025-09-14 22:42:41'),
(16, NULL, 'Bioflu', 1, 10.00, '2025-09-14 22:42:41'),
(17, NULL, 'Biogesic', 1, 10.00, '2025-09-14 22:42:41'),
(18, 13, 'amoxicillin', 1, 10.00, '2025-09-15 04:42:41'),
(19, 13, 'Bioflu', 1, 10.00, '2025-09-15 04:42:41'),
(20, 13, 'Biogesic', 1, 10.00, '2025-09-15 04:42:41'),
(21, NULL, 'amoxicillin', 1, 10.00, '2025-09-14 22:44:21'),
(22, NULL, 'Bioflu', 1, 10.00, '2025-09-14 22:44:21'),
(23, NULL, 'Biogesic', 1, 10.00, '2025-09-14 22:44:21'),
(24, 14, 'amoxicillin', 1, 10.00, '2025-09-15 04:44:21'),
(25, 14, 'Bioflu', 1, 10.00, '2025-09-15 04:44:21'),
(26, 14, 'Biogesic', 1, 10.00, '2025-09-15 04:44:21'),
(27, NULL, 'amoxicillin', 1, 10.00, '2025-09-14 22:45:07'),
(28, NULL, 'Bioflu', 1, 10.00, '2025-09-14 22:45:07'),
(29, NULL, 'Biogesic', 1, 10.00, '2025-09-14 22:45:07'),
(30, 15, 'amoxicillin', 1, 10.00, '2025-09-15 04:45:07'),
(31, 15, 'Bioflu', 1, 10.00, '2025-09-15 04:45:07'),
(32, 15, 'Biogesic', 1, 10.00, '2025-09-15 04:45:07'),
(33, NULL, 'amoxicillin', 1, 10.00, '2025-09-14 22:45:18'),
(34, 16, 'amoxicillin', 1, 10.00, '2025-09-15 04:45:18'),
(35, NULL, 'sheesh', 1, 15.00, '2025-09-18 19:38:21'),
(36, NULL, 'Biogesic', 1, 10.00, '2025-09-18 19:38:21'),
(37, 17, 'sheesh', 1, 15.00, '2025-09-19 01:38:21'),
(38, 17, 'Biogesic', 1, 10.00, '2025-09-19 01:38:21'),
(39, NULL, 'sheesh', 2, 30.00, '2025-09-18 19:44:02'),
(40, NULL, 'Biogesic', 2, 20.00, '2025-09-18 19:44:02'),
(41, 18, 'sheesh', 2, 30.00, '2025-09-19 01:44:02'),
(42, 18, 'Biogesic', 2, 20.00, '2025-09-19 01:44:02'),
(43, NULL, 'sheesh', 5, 75.00, '2025-09-18 19:44:40'),
(44, NULL, 'Bioflu', 4, 40.00, '2025-09-18 19:44:40'),
(45, 19, 'sheesh', 5, 75.00, '2025-09-19 01:44:40'),
(46, 19, 'Bioflu', 4, 40.00, '2025-09-19 01:44:40'),
(47, NULL, 'sheesh', 1, 15.00, '2025-09-18 20:49:07'),
(48, NULL, 'Biogesic', 1, 10.00, '2025-09-18 20:49:07'),
(49, NULL, 'Bioflu', 1, 10.00, '2025-09-18 20:49:07'),
(50, 20, 'sheesh', 1, 15.00, '2025-09-19 02:49:07'),
(51, 20, 'Biogesic', 1, 10.00, '2025-09-19 02:49:07'),
(52, 20, 'Bioflu', 1, 10.00, '2025-09-19 02:49:07'),
(53, NULL, 'sheesh', 1, 15.00, '2025-09-22 00:34:29'),
(54, NULL, 'Biogesic', 1, 10.00, '2025-09-22 00:34:29'),
(55, 21, 'sheesh', 1, 15.00, '2025-09-22 06:34:29'),
(56, 21, 'Biogesic', 1, 10.00, '2025-09-22 06:34:29');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `customer_history_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `customer_history_id`, `total_amount`, `transaction_date`) VALUES
(1, 1, 8.00, '2025-08-31 03:03:19'),
(2, 2, 16.00, '2025-08-31 03:23:29'),
(3, 3, 10.00, '2025-08-31 03:32:21'),
(4, 4, 8.00, '2025-08-31 04:03:19'),
(5, 3, 10.00, '2025-08-31 16:19:42'),
(6, 5, 8.00, '2025-08-31 16:38:40'),
(7, 6, 100.00, '2025-08-31 17:06:50'),
(8, 7, 16.00, '2025-08-31 17:18:14'),
(9, 8, 8.00, '2025-08-31 17:19:22'),
(10, 3, 20.00, '2025-08-31 18:10:15'),
(11, 3, 10.00, '2025-08-31 19:26:57'),
(12, 3, 10.00, '2025-09-15 04:41:23'),
(13, 9, 24.00, '2025-09-15 04:42:41'),
(14, 10, 24.00, '2025-09-15 04:44:21'),
(15, 11, 24.00, '2025-09-15 04:45:07'),
(16, 3, 10.00, '2025-09-15 04:45:18'),
(17, 12, 20.00, '2025-09-19 01:38:21'),
(18, 13, 50.00, '2025-09-19 01:44:02'),
(19, 14, 92.00, '2025-09-19 01:44:40'),
(20, 3, 35.00, '2025-09-19 02:49:07'),
(21, 15, 20.00, '2025-09-22 06:34:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `last_name`, `first_name`, `middle_name`, `username`, `password`, `role`, `profile_image`) VALUES
(1, '', '', NULL, 'admin', 'admin123', 'admin', NULL),
(3, '', '', NULL, 'inventory_user', 'inventory123', 'inventory', NULL),
(4, '', '', NULL, 'cms_user', 'cms123', 'cms', NULL),
(5, '', '', NULL, 'mark@mark', '1123', 'pos', NULL),
(14, 'Pamisa', 'Lhandel', 'V.', 'pamisa', '$2y$10$f2XqzO.NjcPxH8BjIgHFiOg36wco/DWbCY9JYfX1JOCKVCUTz3qBm', 'inventory', 'uploads/profiles/68d0ed373d3b8_image_2025-09-12_022859998-removebg-preview.png');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_description` text NOT NULL,
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `action_description`, `timestamp`) VALUES
(1, 3, 'Inventory System: Updated stock for \'Lagundi\' (Lot: 1). Added 6 stock.', '2025-09-01 03:11:01'),
(2, 5, 'Pos System: Processed a sale of 1 item(s): amoxicillin (x1).', '2025-09-01 03:26:57'),
(3, 3, 'Inventory System: Added new product: \'sheesh\' (Lot: 3).', '2025-09-15 08:47:52'),
(4, 3, 'Inventory System: Added new product: \'sheesh\' (Lot: 1).', '2025-09-15 08:49:29'),
(5, 3, 'Inventory System: Added new product: \'sheesh\' (Lot: 3).', '2025-09-15 08:50:14'),
(6, 3, 'Inventory System: Added new product: \'sheesh\' (Lot: 3).', '2025-09-15 09:01:25'),
(7, 3, 'Inventory System: Added new product: \'sheesh\' (Lot: 3).', '2025-09-15 09:02:25'),
(8, 3, 'Inventory System: Added new product: \'sheesh\' (Lot: 3).', '2025-09-15 09:06:41'),
(9, 3, 'Inventory System: Added new product: \'meow\' (Lot: 3).', '2025-09-15 09:15:35'),
(10, 3, 'Inventory System: Deleted product: \'sheesh\' (Lot: 3) and moved to history.', '2025-09-15 09:33:21'),
(11, 3, 'Inventory System: Added new product: \'sheesh\' (Lot: 3).', '2025-09-15 09:44:57'),
(12, 3, 'Inventory System: Deleted product: \'sheesh\' (Lot: 3) and moved to history.', '2025-09-15 09:45:09'),
(13, 3, 'Inventory System: Deleted product: \'meow\' (Lot: 3) and moved to history.', '2025-09-15 09:45:21'),
(14, 3, 'Inventory System: Added new product: \'sheesh\' (Lot: 3).', '2025-09-15 09:45:48'),
(15, 3, 'Inventory System: Deleted product: \'sheesh\' (Lot: 3) and moved to history.', '2025-09-15 09:47:38'),
(16, 3, 'Inventory System: Deleted product: \'Plemex\' (Lot: 1) and moved to history.', '2025-09-15 09:49:01'),
(17, 3, 'Inventory System: Deleted product: \'Neozep\' (Lot: 1) and moved to history.', '2025-09-15 09:49:15'),
(18, 3, 'Inventory System: Deleted product: \'lagundi\' (Lot: 1) and moved to history.', '2025-09-15 09:49:33'),
(19, 1, 'Deleted user account: pamisa', '2025-09-15 11:58:30'),
(20, 1, 'Deleted user account: mark@james', '2025-09-15 12:03:36'),
(21, 1, 'Deleted user account: pamisa', '2025-09-15 12:04:14'),
(22, 1, 'Deleted user account: pamisa', '2025-09-15 12:06:13'),
(23, 3, 'Inventory System: Added new product: \'sheesh\' (Lot: 3).', '2025-09-15 12:27:53'),
(24, 3, 'Inventory System: Deleted product: \'sheesh\' (Lot: 3) and moved to history.', '2025-09-15 12:34:41'),
(25, 3, 'Inventory System: Added new product: \'sheesh\' (Lot: 3).', '2025-09-15 12:35:09'),
(26, 3, 'Inventory System: Deleted product: \'sheesh\' (Lot: 3) and moved to history.', '2025-09-15 12:40:31'),
(27, 5, 'Pos System: Processed a sale of 1 item(s): amoxicillin (x1).', '2025-09-15 12:41:23'),
(28, 5, 'Pos System: Processed a sale of 3 item(s): amoxicillin (x1), Bioflu (x1), Biogesic (x1).', '2025-09-15 12:42:41'),
(29, 5, 'Pos System: Processed a sale of 3 item(s): amoxicillin (x1), Bioflu (x1), Biogesic (x1).', '2025-09-15 12:44:21'),
(30, 5, 'Pos System: Processed a sale of 3 item(s): amoxicillin (x1), Bioflu (x1), Biogesic (x1).', '2025-09-15 12:45:07'),
(31, 5, 'Pos System: Processed a sale of 1 item(s): amoxicillin (x1).', '2025-09-15 12:45:18'),
(32, 10, 'Inventory System: Added new product: \'sheesh\' (Lot: 1).', '2025-09-19 09:34:53'),
(33, 10, 'Inventory System: Deleted product: \'sheesh\' (Lot: 1) and moved to history.', '2025-09-19 09:35:07'),
(34, 10, 'Inventory System: Added new product: \'sheesh\' (Lot: 3).', '2025-09-19 09:35:47'),
(35, 5, 'Pos System: Processed a sale of 2 item(s): sheesh (x1), Biogesic (x1).', '2025-09-19 09:38:21'),
(36, 10, 'Inventory System: Deleted product: \'sheesh\' (Lot: 3) and moved to history.', '2025-09-19 09:39:04'),
(37, 1, 'Deleted user account: pamisa', '2025-09-19 09:39:59'),
(38, 11, 'Inventory System: Added new product: \'sheesh\' (Lot: 1).', '2025-09-19 09:43:04'),
(39, 5, 'Pos System: Processed a sale of 4 item(s): sheesh (x2), Biogesic (x2).', '2025-09-19 09:44:02'),
(40, 5, 'Pos System: Processed a sale of 9 item(s): sheesh (x5), Bioflu (x4).', '2025-09-19 09:44:40'),
(41, 1, 'Deleted user account: pamisa', '2025-09-19 09:45:49'),
(42, 1, 'Deleted user account: pamisa', '2025-09-19 09:57:26'),
(43, 1, 'Deleted user account: markjames@pisngot', '2025-09-19 09:59:23'),
(44, 1, 'Deleted user account: pamisa', '2025-09-19 10:02:23'),
(45, 5, 'Pos System: Processed a sale of 3 item(s): sheesh (x1), Biogesic (x1), Bioflu (x1).', '2025-09-19 10:49:07'),
(46, 5, 'Pos System: Processed a sale of 2 item(s): sheesh (x1), Biogesic (x1).', '2025-09-22 14:34:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `customer_history`
--
ALTER TABLE `customer_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_history`
--
ALTER TABLE `product_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_history`
--
ALTER TABLE `purchase_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transaction_id` (`transaction_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_history_id` (`customer_history_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customer_history`
--
ALTER TABLE `customer_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `product_history`
--
ALTER TABLE `product_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `purchase_history`
--
ALTER TABLE `purchase_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `purchase_history`
--
ALTER TABLE `purchase_history`
  ADD CONSTRAINT `fk_transaction_id` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`customer_history_id`) REFERENCES `customer_history` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
