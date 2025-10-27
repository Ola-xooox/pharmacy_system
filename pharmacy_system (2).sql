-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2025 at 02:32 PM
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
(6, 'Antihistamine'),
(2, 'cold'),
(4, 'cough'),
(7, 'EURIVIT-M TABLET'),
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
(17, 'Mr', '12345', 1, 40.00, '2025-10-24 09:09:32'),
(18, 'Mr', '1', 1, 40.00, '2025-10-24 09:35:10'),
(19, 'Walk-in', '', 4, 1040.00, '2025-10-24 18:24:54');

-- --------------------------------------------------------

--
-- Table structure for table `otp_verification`
--

CREATE TABLE `otp_verification` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `attempts` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `otp_verification`
--

INSERT INTO `otp_verification` (`id`, `email`, `otp_code`, `created_at`, `expires_at`, `is_used`, `attempts`) VALUES
(152, 'markjamesp11770@gmail.com', '529742', '2025-10-26 13:29:23', '2025-10-26 13:34:23', 1, 0);

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

INSERT INTO `products` (`id`, `name`, `lot_number`, `category_id`, `price`, `cost`, `stock`, `date_added`, `expiration_date`, `supplier`, `batch_number`, `image_path`, `image_data`, `image_type`) VALUES
(42, 'Lagundi', '1', 5, 10.00, 100.00, 0, '2025-10-24 16:58:31', '2025-11-24', 'Mark', '1', NULL, NULL, NULL),
(44, 'Solmux', '2', 5, 10.00, 100.00, 5, '2025-10-24 17:30:57', '2025-11-24', 'Mark', '2', NULL, NULL, NULL),
(45, 'Paracetamol', '3', 5, 10.00, 100.00, 10, '2025-10-24 17:41:54', '2025-10-24', 'Mark', '1', NULL, NULL, NULL),
(46, 'Lagundi', '3', 5, 100.00, 10.00, 0, '2025-10-24 17:44:46', '2025-11-24', 'Mark', '1', NULL, NULL, NULL),
(47, 'Paracetamol', '5', 5, 10.00, 2.00, 7, '2025-10-25 00:49:18', '2025-11-25', 'Mark', '1', NULL, NULL, NULL),
(48, 'Saridol', '4', 5, 5.00, 1.00, 29, '2025-10-25 00:54:05', '2025-11-25', 'Mark', NULL, NULL, NULL, NULL),
(49, 'Vitamin C', '6', 5, 5.00, 2.00, 9, '2025-10-25 01:03:41', '2025-11-25', 'Mark', NULL, NULL, NULL, NULL),
(50, 'Lagundi', '7', 5, 5.00, 2.00, 10, '2025-10-25 02:25:47', '2025-11-25', 'Mark', NULL, NULL, NULL, NULL);

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
  `date_added` datetime DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(66, 28, 'Lagundi', 5, 50.00, '2025-10-24 09:09:32'),
(67, 29, 'Solmux', 5, 50.00, '2025-10-24 09:35:10'),
(68, 30, 'Paracetamol', 1, 10.00, '2025-10-24 16:49:35'),
(69, 31, 'Paracetamol', 2, 20.00, '2025-10-24 16:54:52'),
(70, 31, 'Saridol', 1, 5.00, '2025-10-24 16:54:52'),
(71, 32, 'Lagundi', 10, 1000.00, '2025-10-24 16:55:23'),
(72, 33, 'Vitamin C', 1, 5.00, '2025-10-24 18:24:54');

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
(28, 17, 40.00, '2025-10-24 09:09:32'),
(29, 18, 40.00, '2025-10-24 09:35:10'),
(30, 19, 10.00, '2025-10-24 16:49:35'),
(31, 19, 25.00, '2025-10-24 16:54:52'),
(32, 19, 1000.00, '2025-10-24 16:55:23'),
(33, 19, 5.00, '2025-10-24 18:24:54');

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
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `last_name`, `first_name`, `middle_name`, `username`, `email`, `password`, `role`, `profile_image`) VALUES
(15, 'Admin', 'System', NULL, 'admin', 'lhandelpamisa0@gmail.com', '$2y$10$iMKWGXUioV/Q/dR8S6r/XetaO5I6lTZGtz.KN8VaOJ97D9HBWo876', 'admin', NULL),
(17, 'Pamisa', 'Lhandel', 'V.', 'inventory', 'warrioscalvary@gmail.com', '$2y$10$oh2KU9pCzYmNYpW48PyhYuTfOeE8T4BSULmg10NsEEcLCCGPEGX.G', 'inventory', NULL),
(18, 'Pamisa', 'Lhandel', 'V.', 'POS', 'parkjihyoda@gmail.com', '$2y$10$H5pv2cJ96Tb10FJNpuDclObjPykgcc7/gt7yJo1//p0xawIWqzSm6', 'pos', NULL),
(19, 'Pamisa', 'Lhandel', 'V.', 'Customer Mngmnt', 'unstoppabegaming@gmail.com', '$2y$10$pgrIQlYs7tCm.JvwPQhZ3.xBHFy0FIMiaOdFh5Be0uEb/maE5AUHe', 'cms', NULL),
(20, 'Pisngot', 'Mark James', 'G.', 'Mark', 'markjamesp11770@gmail.com', '$2y$10$R6WhwqvJEqYUa1kKpfOR1eYAfWgp8EqkeQUGvgI0/M0YjcBn5oVrC', 'inventory', 'uploads/profiles/68f2094625a15_wallhaven-gwp2w3.png'),
(53, 'Cabales ', 'Edmalyn', 'P', 'Elyn', 'edmalyncabales3@gmail.com', '$2y$10$JjraedeWbCyDIkZkvtDUUOUDCCkq6PdL0lpGjX8kjNl0nL.XbhxJu', 'pos', NULL),
(54, 'Indoso', 'Jairo', '', 'Jairo', 'indosojairo@gmail.com', '$2y$10$XP3G2NKmJ.OvlfUTH6S84ebsCp4rBjQCVaYHus14tIukcD7zI2N9q', 'inventory', NULL),
(56, 'Elacio', 'Kim', '', 'Kim', 'kkvelacion@gmail.com', '$2y$10$MgoY5gEXgI6xKUnQUAd85.9r5FjaVPkrfYsMo3mqwqX9dqE6V777W', 'pos', NULL),
(57, 'Amata', 'Michael Angelo', '', 'Michael Angelo', 'michaelangelo.amata@gmail.com', '$2y$10$Xo7C6HehG46kbYHrGJB5oOqezgWfg/CVClfEIeqlj6dq2qlLtTpXm', 'inventory', NULL),
(58, 'Juego', 'Ana', '', 'Ana', 'sansayjuego@gmail.com', '$2y$10$7c8yCQXHz5sramxnbjik6ecGv8XPJIEiePyFgpE7gRWXvARYoj7Lm', 'cms', NULL),
(59, 'Mercadal', 'Micah Mhae', '', 'Micah', 'mercadal.micahmhae.alpis@gmail.com', '$2y$10$J2k02znlKZ5MjPuAfjtebO31Ke8ukm8pH5HXVUx1ZWJc2FFVdjQom', 'inventory', NULL),
(60, 'Feudo', 'Karl Vincent ', '', 'Karl', 'karl19feudo@gmail.com', '$2y$10$E/En8CwX4XgW./aqzpeeXusq.GeMdl7KROMdOQ02d3agwE0CyKeTy', 'cms', NULL),
(61, 'Baa', 'Bret', '', 'Bret', 'bretbaa12@gmail.com', '$2y$10$2V7UfvXtAKyl/xVQUVAgPOIcfaOMqHGY5eKbuGJwkJ7zWHbxGzfZ6', 'inventory', NULL),
(63, 'Balcos', 'Maria Criselda', NULL, 'mariacriseldabalcos', 'contact.mariacriseldabalcos@gmail.com', '$2y$10$fNFmhg2JUEJsKidBn76mn.IyN9uW1u2clKHLKURStbDP0.5xuBoFK', 'admin', NULL),
(67, 'Inventory', 'Manager', NULL, 'inventory_manager', 'contact.mariacriseldabalcos@gmail.com', '$2y$10$oOlXo6ltdwmy7dUX9YSkTuXqekDl92jrAd44YJHO7OgaZI26TbZLq', 'inventory', NULL),
(68, 'POS', 'Operator', NULL, 'pos_operator', 'contact.mariacriseldabalcos@gmail.com', '$2y$10$D48JlQ.MWltxrSj8oncznuuNAZnVdSwLZvcw5TK.bOmfYaFSFAqN6', 'pos', NULL),
(69, 'CMS', 'Manager', NULL, 'cms_manager', 'contact.mariacriseldabalcos@gmail.com', '$2y$10$2vkdkAAPMifCqG7UZ5PjU.n8QUeSp9kCq7ijk3qMBBP8FOhOeS5bS', 'cms', NULL);

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
(67, 20, 'Inventory System: Added new product: \'Lagundi\' (Lot: 1).', '2025-10-24 16:58:31'),
(68, 20, 'Inventory System: Added new product: \'Solmux\' (Lot: 1).', '2025-10-24 17:11:21'),
(69, 20, 'Inventory System: Added new product: \'Solmux\' (Lot: 2).', '2025-10-24 17:30:57'),
(70, 20, 'Inventory System: Added new product: \'Paracetamol\' (Lot: 3).', '2025-10-24 17:41:54'),
(71, 20, 'Inventory System: Added new product: \'Lagundi\' (Lot: 3).', '2025-10-24 17:44:46'),
(72, 20, 'Inventory System: Added new product: \'Paracetamol\' (Lot: 5).', '2025-10-25 00:49:18'),
(73, 20, 'Inventory System: Added new product: \'Saridol\' (Lot: 4).', '2025-10-25 00:54:05'),
(74, 20, 'Inventory System: Added new product: \'Vitamin C\' (Lot: 6).', '2025-10-25 01:03:41'),
(75, 18, 'Pos System: Processed a sale of 1 item(s) for customer \'Walk-in\': Vitamin C (x1). Total: ₱5.00', '2025-10-25 02:24:54'),
(76, 20, 'Inventory System: Added new product: \'Lagundi\' (Lot: 7).', '2025-10-25 02:25:47'),
(77, 15, 'Admin System: User logged in successfully', '2025-10-25 19:39:06'),
(83, 17, 'Inventory System: User logged out successfully', '2025-10-25 19:55:41'),
(88, 19, 'Cms System: User logged out successfully', '2025-10-25 19:58:02'),
(91, 19, 'Cms System: User logged out successfully', '2025-10-25 20:01:50'),
(95, 19, 'Cms System: User logged out successfully', '2025-10-25 20:03:49'),
(96, 20, 'Inventory System: User logged in successfully', '2025-10-26 21:29:40');

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
-- Indexes for table `otp_verification`
--
ALTER TABLE `otp_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `otp_code` (`otp_code`),
  ADD KEY `expires_at` (`expires_at`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customer_history`
--
ALTER TABLE `customer_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `otp_verification`
--
ALTER TABLE `otp_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `product_history`
--
ALTER TABLE `product_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `purchase_history`
--
ALTER TABLE `purchase_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

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
