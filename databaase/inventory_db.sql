-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 29, 2025 at 08:40 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventory_db`
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
(2, 'Cold & Flu'),
(7, 'dada'),
(6, 'ewan'),
(1, 'Pain Relief'),
(4, 'Personal Care'),
(5, 'sdsd'),
(3, 'Vitamins');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `item_total` decimal(10,2) DEFAULT 0.00,
  `date_added` datetime NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `lot_number`, `category_id`, `price`, `cost`, `stock`, `item_total`, `date_added`, `expiration_date`, `supplier`, `batch_number`, `image_path`) VALUES
(1, 'sdsd', 'sdsd', 5, 232.00, 2323.00, 0, 0.00, '2025-08-29 13:41:10', '2025-08-29', 'dsd', 'dsds', 'uploads/68b191d6179f1_mjpharmacy.logo.jpg'),
(2, 'dad', 'sds', 5, 32.00, 23.00, 0, 0.00, '2025-08-29 13:46:32', '2025-08-29', 'sds', 'dssd', 'uploads/68b193184098e_mjpharmacy.logo.jpg'),
(3, 'Paracetamol', '12345', 2, 15.00, 16.00, 0, 0.00, '2025-08-29 14:45:57', '2025-08-29', 'Mark', '1', NULL),
(4, 'cvc', 'adada', 7, 2323.00, 313.00, 1, 1.00, '2025-08-29 17:44:03', '2025-08-30', 'czc', '1', NULL),
(9, 'Paracetamol', '123', 6, 15.00, 15.00, 10, 10.00, '2025-08-29 20:17:51', '2025-09-30', 'Mark', '1', NULL),
(10, 'bc', '1', 2, 1.00, 1.00, 5, 1.00, '2025-08-29 20:25:13', '2025-08-31', '1', '1', NULL),
(11, 'sdfasdf', '1', 2, 1.00, 1.00, 1, 1.00, '2025-08-29 20:30:37', '2025-08-31', '1', '1', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_history`
--

CREATE TABLE `product_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `item_total` decimal(10,2) DEFAULT 0.00,
  `date_added` datetime NOT NULL,
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
(1, 5, 'ad', 'q', 2, 1.00, 1.00, 21, 21.00, '2025-08-29 18:43:07', '2025-08-31', '1', '1', NULL, '2025-08-29 17:57:20'),
(2, 7, 'bc', '2', 2, 2.00, 2.00, 2, 2.00, '2025-08-29 19:59:03', '2025-08-31', '2', '2', NULL, '2025-08-29 18:04:12'),
(3, 6, 'bc', '1', 2, 1.00, 1.00, 7, 12.00, '2025-08-29 19:42:30', '2025-08-31', '1', '1', NULL, '2025-08-29 18:16:06'),
(4, 8, 'bc', '2', 2, 1.00, 1.00, 2, 2.00, '2025-08-29 20:15:54', '2025-08-31', '1', '2', NULL, '2025-08-29 18:16:29');

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `product_history`
--
ALTER TABLE `product_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
