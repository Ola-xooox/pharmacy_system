-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2025 at 06:24 PM
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
(8, 'ANTACID'),
(9, 'ANTI ANGINA'),
(10, 'ANTI ASTHMA'),
(12, 'ANTI-DIABETIC'),
(13, 'ANTI-EMETIC'),
(14, 'ANTI-FUNGAL'),
(15, 'ANTI-GOUT'),
(16, 'ANTI-HISTAMINE'),
(17, 'ANTI-HYPERTENSIVE'),
(18, 'ANTI-PLATELET'),
(19, 'ANTI-SPASMODIC'),
(20, 'ANTI-THROMBOTIC'),
(34, 'ANTI-VERTIGO'),
(11, 'ANTIBACTERIAL'),
(5, 'antibiotic'),
(37, 'ANTIEMETIC'),
(39, 'ANTIFUNGAL'),
(6, 'Antihistamine'),
(40, 'ANTISEPTIC'),
(2, 'cold'),
(21, 'CORTICOSTEROID'),
(4, 'cough'),
(22, 'COUGH AND COLDS'),
(43, 'CREAMS'),
(48, 'DIAPER'),
(23, 'DIURETIC'),
(24, 'ELECTROLYTES'),
(25, 'ERECTILE DYSFUNCTION'),
(7, 'EURIVIT-M TABLET'),
(1, 'Flu'),
(38, 'FLU AND COLDS'),
(26, 'GASTRIC MUCOSAL PROTECTANT'),
(44, 'GEL'),
(3, 'headache'),
(27, 'HEMOSTAT'),
(46, 'MEDICAL SUPPLIES'),
(47, 'MILK'),
(35, 'NOOTROPIC'),
(28, 'NSAIDs'),
(45, 'OINTMENT'),
(41, 'OPHTHALMIC'),
(29, 'OTHERS'),
(42, 'OTIC'),
(33, 'PROBIOTICS'),
(30, 'PROTON PUMP INHIBITOR'),
(32, 'STATINS'),
(36, 'SYRUPS/SUSPENSIONS'),
(31, 'VITAMINS');

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
-- Table structure for table `login_approvals`
--

CREATE TABLE `login_approvals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` enum('pos','cms','inventory') NOT NULL,
  `status` enum('pending','approved','declined','no_response') DEFAULT 'pending',
  `requested_at` datetime DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `login_approvals`
--

INSERT INTO `login_approvals` (`id`, `user_id`, `email`, `name`, `role`, `status`, `requested_at`, `reviewed_at`, `reviewed_by`, `ip_address`, `user_agent`) VALUES
(1, 70, 'edmalyncabales3@gmail.com', 'Edmalyn P Cabales', 'pos', 'approved', '2025-10-29 01:21:22', '2025-10-29 01:21:31', 15, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0');

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
(154, 'edmalyncabales3@gmail.com', '867350', '2025-10-28 17:20:31', '2025-10-28 17:25:31', 0, 0),
(155, 'edmalyncabales3@gmail.com', '942940', '2025-10-28 17:21:05', '2025-10-28 17:26:05', 1, 0);

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
(1635, 'TAB ZILGAM', 'Z-47013', 8, 2.00, 0.43, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1636, 'Kremil-S TAB', 'UO86447', 8, 10.00, 8.07, 100, '2025-10-16 00:00:00', '2027-01-01', 'RB', NULL, NULL, NULL, NULL),
(1637, 'Kremil-S Advance TAB', 'UO98136', 8, 23.00, 18.01, 100, '2025-10-16 00:00:00', '2027-01-01', 'RB', NULL, NULL, NULL, NULL),
(1638, 'Ranitidine 150mg TAB RANITEIN', 'R-96003', 8, 2.00, 0.65, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1639, 'Ranitidine 300mg TAB ZENTEK', '23GT232', 8, 5.00, 1.20, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1640, 'TMZ 35mg TAB GOTAZIDINE', 'FFM2F406', 9, 7.00, 1.15, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1641, 'Salbutamol 2mg TAB VENTOMAX', '4B151', 10, 2.00, 0.22, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1642, 'Cefalexin 500mg CAP CEFAST', 'CFX-1111', 11, 2.20, 0.58, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1643, 'Amoxicillin 500mg CAP SUMOX', 'PA042', 11, 1.25, 0.38, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1644, 'Cotrimoxazole 800/160 TAB BACTIZOLE', 'TCL2314', 11, 1.20, 0.40, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1645, 'Doxycycline 100mg CAP DOXIN', 'H312211', 11, 1.10, 0.45, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1646, 'Ciprofloxacin 500mg TAB', 'CP-82017', 11, 2.15, 0.42, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1647, 'Azithromycin 500mg TAB AZYTH', 'AZT-23004', 11, 7.50, 2.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1648, 'Clindamycin 300mg CAP CLINDEX', 'CD-36011', 11, 2.80, 1.20, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1649, 'Metformin 500mg TAB GLUMET', 'B230303', 12, 0.50, 0.19, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1650, 'Metformin 850mg TAB METFOR', 'P010218', 12, 1.30, 0.28, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1651, 'Gliclazide 30mg TAB GLIZID', '210515', 12, 1.70, 0.88, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1652, 'Glibenclamide 5mg TAB', 'D521010', 12, 0.50, 0.16, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1653, 'Glimepiride 2mg TAB', '221101', 12, 1.00, 0.19, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1654, 'Dimenhydrinate 50mg TAB', '230101', 13, 0.70, 0.20, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1655, 'Meclizine 25mg TAB DIZINIL', 'CL-03-24', 13, 0.80, 0.21, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1656, 'Fluconazole 150mg CAP', 'B22F013', 14, 5.00, 1.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1657, 'Allopurinol 100mg TAB PURINOL', 'ALP-39001', 15, 0.70, 0.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1658, 'Allopurinol 300mg TAB PURINOL', 'ALP-40003', 15, 1.80, 0.53, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1659, 'Febuxostat 40mg TAB', '21FB004', 15, 7.50, 3.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1660, 'Colchicine 500mcg TAB', '230113', 15, 3.00, 2.05, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1661, 'Cetirizine 10mg TAB CETIZIN', 'B221379', 16, 0.50, 0.17, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1662, 'Loratadine 10mg TAB LERZIN', 'B221330', 16, 0.80, 0.21, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1663, 'Diphenhydramine 25mg CAP', '171007', 16, 1.50, 0.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1664, 'Hydroxyzine 10mg TAB', 'E23019', 16, 2.00, 0.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1665, 'Losartan 50mg TAB', '230623', 17, 0.80, 0.17, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1666, 'Losartan 100mg TAB', '230616', 17, 1.20, 0.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1667, 'Amlodipine 5mg TAB', '230303', 17, 0.70, 0.15, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1668, 'Amlodipine 10mg TAB', '230302', 17, 1.00, 0.19, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1669, 'Metoprolol 50mg TAB', '221102', 17, 1.00, 0.24, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1670, 'Metoprolol 100mg TAB', '220901', 17, 1.50, 0.40, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1671, 'Captopril 25mg TAB', '2211425', 17, 0.50, 0.15, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1672, 'Clonidine 75mcg TAB', 'B230383', 17, 0.70, 0.23, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1673, 'Carvedilol 25mg TAB', '220901', 17, 3.00, 0.75, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1674, 'Telmisartan 40mg TAB', 'B221081', 17, 2.00, 0.45, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1675, 'Telmisartan 80mg TAB', 'B221066', 17, 3.00, 0.85, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1676, 'Losartan+HCTZ 50/12.5 TAB', '220501', 17, 1.50, 0.38, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1677, 'Amlodipine+Losartan 5/50 TAB', 'L23004', 17, 3.00, 0.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1678, 'Aspilet 80mg TAB', '2403756', 18, 2.00, 1.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1679, 'Clopidogrel 75mg TAB PLATEL', 'PL-68001', 18, 1.80, 0.48, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1680, 'Hyoscine 10mg TAB', 'N096A', 19, 1.00, 0.35, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1681, 'Cilostazol 100mg TAB', '230402', 20, 10.00, 2.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1682, 'Prednisone 5mg TAB', '2305011', 21, 0.50, 0.18, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1683, 'Prednisone 20mg TAB', '2211003', 21, 1.00, 0.28, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1684, 'Dexamethasone 500mcg TAB', 'B221303', 21, 0.50, 0.16, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1685, 'Methylprednisolone 4mg TAB', '220902', 21, 3.00, 0.45, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1686, 'Ambroxol 30mg TAB', 'B230064', 22, 0.80, 0.23, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1687, 'Carbocisteine 500mg CAP', 'B220811', 22, 1.20, 0.38, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1688, 'Lagundi 300mg TAB', '230101', 22, 1.50, 0.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1689, 'PPA+BROM+PARA (Sinutab)', 'E212', 22, 5.00, 3.20, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1690, 'Phenylephrine 10mg TAB (Neozep)', 'E1238', 22, 5.00, 3.10, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1691, 'PPA+CHLOR+PARA (Bioflu)', '4F040', 22, 7.00, 5.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1692, 'Cetirizine+Phenylephrine (Alnix Plus)', '4J018', 22, 9.00, 6.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1693, 'Dextro 10mg TAB (Robitussin)', 'R23594', 22, 5.00, 3.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1694, 'Butamirate 50mg TAB', 'L00962A', 22, 7.00, 3.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1695, 'Furosemide 40mg TAB', '230401', 23, 0.80, 0.23, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1696, 'HCTZ 50mg TAB', '220901', 23, 0.80, 0.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1697, 'Hydrite TAB', '24C2401', 24, 10.00, 6.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1698, 'Sildenafil 100mg TAB', 'CE1421008', 25, 20.00, 4.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1699, 'Rebamipide 100mg TAB', 'B221081', 26, 8.00, 2.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1700, 'Tranexamic 500mg CAP', '230113', 27, 5.00, 1.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1701, 'Paracetamol 500mg TAB', 'B230302', 28, 0.50, 0.18, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1702, 'Mefenamic Acid 500mg TAB', 'B230005', 28, 0.60, 0.21, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1703, 'Ibuprofen 200mg TAB', 'B23A031', 28, 0.70, 0.23, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1704, 'Ibuprofen 400mg TAB', 'B23A005', 28, 1.20, 0.40, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1705, 'Celecoxib 200mg CAP', '230401', 28, 2.00, 0.45, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1706, 'Naproxen 550mg TAB', '44422', 28, 2.50, 0.85, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1707, 'Etoricoxib 60mg TAB', '230101', 28, 8.00, 3.20, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1708, 'Etoricoxib 120mg TAB', '221101', 28, 10.00, 5.20, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1709, 'PARA+ORPHENADRINE (Norgesic)', '23B002', 28, 6.00, 3.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1710, 'PARA+TRAMADOL (Algesia)', 'L1213', 28, 5.00, 1.30, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1711, 'Loperamide 2mg TAB LOMID', 'LMD23005', 29, 0.50, 0.20, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1712, 'Senna 8.6mg TAB', 'C005/23', 29, 1.50, 0.55, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1713, 'Bisacodyl 5mg TAB', 'B221102', 29, 0.80, 0.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1714, 'Domperidone 10mg TAB', 'B221295', 29, 1.20, 0.40, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1715, 'Metoclopramide 10mg TAB', '230101', 29, 1.00, 0.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1716, 'Tamsulosin 400mcg CAP', 'B22H008', 29, 5.00, 1.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1717, 'Sambong 500mg TAB', '230302', 29, 2.00, 0.85, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1718, 'Gingko Biloba TAB', '230301', 29, 3.00, 0.90, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1719, 'Rosuvastatin+Fenofibrate CAP', 'RFN23001', 29, 15.00, 6.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1720, 'Silymarin CAP', 'T22L001', 29, 3.00, 1.10, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1721, 'CoQ10 CAP', '220902', 29, 10.00, 3.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1722, 'Melatonin 3mg TAB', '230501', 29, 5.00, 1.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1723, 'Omeprazole 20mg CAP', 'B22K006', 30, 1.00, 0.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1724, 'Esomeprazole 20mg CAP', 'B23B002', 30, 4.00, 0.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1725, 'Esomeprazole 40mg CAP', 'B23B001', 30, 5.00, 1.10, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1726, 'Ascorbic Acid 500mg TAB', '230401', 31, 1.00, 0.35, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1727, 'Vitamin B-Complex TAB', 'T23D002', 31, 1.00, 0.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1728, 'Vitamin E 400IU CAP', '230101', 31, 3.00, 1.20, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1729, 'MVT+Iron (Iberet)', '220042', 31, 8.00, 5.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1730, 'Ferrous Sulfate TAB', '230101', 31, 1.00, 0.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1731, 'Ferrous+Folic TAB', 'B221345', 31, 1.00, 0.35, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1732, 'MVT (Enervon)', '2304207', 31, 7.00, 5.05, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1733, 'MVT+Zinc (Berocca)', 'BAY66E2', 31, 15.00, 11.20, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1734, 'MVT (Centrum Advance)', 'R23594', 31, 9.00, 6.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1735, 'MVT (Conzace)', '4G008', 31, 13.00, 9.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1736, 'MVT (Stresstabs)', 'E1238', 31, 8.00, 5.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1737, 'D-Forte CAP D-Rise', '3J007', 31, 15.00, 11.20, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1738, 'Caltrate Advance TAB', 'R22849', 31, 10.00, 7.20, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1739, 'Calciumade TAB', '4C011', 31, 8.00, 5.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1740, 'MVT (Revicon Max)', '4C011', 31, 8.00, 5.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1741, 'MVT (Clusivol)', 'E1238', 31, 9.00, 6.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1742, 'Mecobalamin 500mcg CAP', 'B22K004', 31, 4.00, 1.30, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1743, 'Calcuim Carb+Vit D3 TAB', '230113', 31, 2.00, 0.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1744, 'Atorvastatin 10mg TAB ATORVA', 'AT-37001', 32, 2.00, 0.38, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1745, 'Atorvastatin 20mg TAB ATORVA', 'AT-38002', 32, 3.00, 0.48, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1746, 'Atorvastatin 40mg TAB ATORVA', 'AT-39001', 32, 4.00, 0.85, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1747, 'Atorvastatin 80mg TAB ATORVA', 'AT-40003', 32, 5.00, 1.60, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1748, 'Rosuvastatin 10mg TAB ROSUVA', 'RO-17002', 32, 3.00, 0.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1749, 'Rosuvastatin 20mg TAB ROSUVA', 'RO-18001', 32, 4.00, 1.10, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1750, 'Rosuvastatin 40mg TAB ROSUVA', 'RO-19003', 32, 7.00, 2.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1751, 'Fenofibrate 160mg TAB', '220901', 32, 7.00, 2.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1752, 'Simvastatin 10mg TAB ZIMVAST', 'Z-02033', 32, 5.00, 1.60, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1753, 'Simvastatin 20mg TAB DIASTIN', '143109', 32, 5.00, 0.85, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1754, 'Erceflora Gut Defense 5ml BOT', '51065', 33, 47.00, 40.89, 100, '2025-10-16 00:00:00', '2027-01-01', 'CP', NULL, NULL, NULL, NULL),
(1755, 'Bacillus Clausii 10mL BOT ENCAZYM', '30923', 33, 20.00, 10.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1756, 'Betahistine 8mg TAB VERTISAPH', 'NBE-01', 34, 6.00, 1.60, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1757, 'Betahistine 24mg TAB VERTISAPH', 'NBT05', 34, 17.00, 8.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1758, 'Cinnarizine 25mg TAB CINZITAB', 'M906', 34, 2.00, 0.63, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1759, 'Citicoline 500mg TAB CITCOLE', 'CL-020-22', 35, 17.00, 8.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1760, 'Gaviscon DA Sachet 10ml', 'AGR282', 8, 42.00, 31.45, 100, '2025-10-16 00:00:00', '2027-01-01', 'JR', NULL, NULL, NULL, NULL),
(1761, 'Domperidone 1mg/ml SUSP 60ml ACCEDOME', 'AGR282', 37, 42.00, 31.45, 100, '2025-10-16 00:00:00', '2027-01-01', 'JR', NULL, NULL, NULL, NULL),
(1762, 'Prednisone 10mg SUSP 60ml LEFESONE', 'ADO57A', 21, 80.00, 39.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1763, 'Disudrin SYR 60ML', 'L025491', 38, 140.00, 130.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'CP', NULL, NULL, NULL, NULL),
(1764, 'Symdex D DROPS 15ml', '35224021', 38, 60.00, 28.90, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1765, 'Symdex D SYR 60ML', '35124074', 38, 60.00, 29.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1766, 'Ibuprofen 100mg SUSP 60ml DOLAN', '4K053', 28, 80.00, 60.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1767, 'Paracetamol 120mg/5ml SUSP 60ml', 'B23M003', 28, 30.00, 12.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1768, 'Paracetamol 250mg/5ml SUSP 60ml', 'B23M005', 28, 40.00, 15.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1769, 'Biogesic 120mg SUSP 60ml', '4F040', 28, 70.00, 52.60, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1770, 'Biogesic 250mg SUSP 60ml', '4K053', 28, 90.00, 69.85, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1771, 'Biogesic DROP 15ml (Babies)', '4A002', 28, 70.00, 50.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1772, 'Calpol 120mg SUSP 60ml', 'N121', 28, 70.00, 52.60, 100, '2025-10-16 00:00:00', '2027-01-01', 'CP', NULL, NULL, NULL, NULL),
(1773, 'Ceelin DROPS 15ml', 'A054', 31, 70.00, 50.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1774, 'Ceelin SYR 60ml', 'N010', 31, 80.00, 59.50, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1775, 'Ceelin SYR 120ml', 'M004', 31, 130.00, 99.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1776, 'Ceelin Plus DROPS 15ml', 'A073', 31, 90.00, 68.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1777, 'Ceelin Plus SYR 60ml', 'N016', 31, 100.00, 75.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1778, 'Ceelin Plus SYR 120ml', 'N014', 31, 160.00, 126.90, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1779, 'Tiki-Tiki DROPS 15ml', '4E002', 31, 70.00, 50.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1780, 'Tiki-Tiki SYR 60ml', '4E002', 31, 100.00, 75.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1781, 'Tiki-Tiki SYR 120ml', '4G008', 31, 160.00, 126.90, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1782, 'Nutrilin DROPS 15ml', 'M003', 31, 80.00, 59.50, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1783, 'Nutrilin SYR 60ml', 'N011', 31, 110.00, 84.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1784, 'Nutrilin SYR 120ml', 'N009', 31, 180.00, 144.50, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1785, 'Cherifer DROPS 15ml', '3L031', 31, 100.00, 75.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1786, 'Cherifer SYR 60ml', '4E002', 31, 140.00, 108.60, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1787, 'Cherifer SYR 120ml', '4J018', 31, 220.00, 175.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1788, 'Cherifer Forte SYR 60ml', '4C011', 31, 120.00, 92.50, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1789, 'Cherifer Forte SYR 120ml', '4B007', 31, 190.00, 153.40, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1790, 'Growee SYR 60ml', 'E1238', 31, 100.00, 75.80, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1791, 'Growee SYR 120ml', 'E212', 31, 160.00, 126.90, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1792, 'Propan TLC SYR 60ml', '220042', 31, 110.00, 84.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1793, 'Propan TLC SYR 120ml', 'R23594', 31, 180.00, 144.50, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1794, 'Propan Fit SYR 60ml', '0923055', 31, 130.00, 99.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1795, 'Propan Fit SYR 120ml', '0923055', 31, 210.00, 166.20, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1796, 'Scott\'s Emulsion 100ml', '038318', 31, 130.00, 99.70, 100, '2025-10-16 00:00:00', '2027-01-01', 'CP', NULL, NULL, NULL, NULL),
(1797, 'Caltrate Advance BOT 60\'s', 'R22849', 31, 500.00, 400.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1798, 'Cefalexin 100mg/ml SUSP 60ml CEFAST', 'CFS-7105', 11, 90.00, 48.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1799, 'Amoxicillin 250mg/5ml SUSP 60ml SUMOX', 'PA042', 11, 50.00, 21.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1800, 'Amoxicillin 100mg/ml DROPS 10ml', '230101', 11, 40.00, 15.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1801, 'Co-Amoxiclav 250mg/5ml SUSP 60ml', 'B221387', 11, 100.00, 39.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1802, 'Co-Amoxiclav 400mg/5ml SUSP 70ml', 'B230232', 11, 150.00, 65.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1803, 'Cotrimoxazole 200/40 SUSP 60ml', 'TCL2314', 11, 40.00, 15.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1804, 'Azithromycin 200mg/5ml SUSP 15ml', 'AZT-23004', 11, 100.00, 39.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1805, 'Cefixime 100mg/5ml SUSP 30ml', '221101', 11, 100.00, 39.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1806, 'Cefixime 100mg/5ml SUSP 60ml', '221101', 11, 150.00, 65.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1807, 'Clindamycin 75mg/5ml SUSP 60ml', 'CD-36011', 11, 100.00, 39.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1808, 'Nystatin 100,000U/ml SUSP 30ml', '230101', 39, 100.00, 39.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1809, 'Povidone-Iodine 10% SOL\'N 15ml', 'B22K006', 40, 30.00, 12.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1810, 'Povidone-Iodine 10% SOL\'N 30ml', 'B22K006', 40, 40.00, 15.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1811, 'Povidone-Iodine 10% SOL\'N 60ml', 'B22K006', 40, 60.00, 28.90, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1812, 'Povidone-Iodine 10% SOL\'N 120ml', 'B22K006', 40, 100.00, 48.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1813, 'Betadine Sol\'n 10% 15ml', '2403756', 40, 40.00, 31.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1814, 'Betadine Sol\'n 10% 30ml', '23C0702', 40, 60.00, 47.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1815, 'Betadine Sol\'n 10% 60ml', '23A0201', 40, 100.00, 78.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1816, 'Betadine Sol\'n 10% 120ml', '23A0201', 40, 160.00, 129.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1817, 'Hydrogen Peroxide 3% 60ml', '230401', 40, 20.00, 8.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1818, 'Hydrogen Peroxide 3% 120ml', '230401', 40, 30.00, 12.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1819, 'Tobramycin E/E DROPS 5ml', 'B221303', 41, 80.00, 24.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1820, 'Polymyxin+Neomycin+Fludrocortisone OTIC SOLN', '230101', 42, 100.00, 39.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1821, 'Clotrimazole 10mg/g CREAM 15g', 'B23B002', 43, 50.00, 16.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1822, 'Betamethasone 500mcg/g CREAM 5g', 'B221345', 43, 40.00, 15.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1823, 'Clobetasol 500mcg/g CREAM 5g', 'B221102', 43, 40.00, 15.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1824, 'Mupirocin 20mg/g CREAM 5g', '230101', 43, 80.00, 24.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1825, 'KETOCONAZOLE 2MG/G 15G FUNGINIL-K', 'EG0063A', 43, 80.00, 34.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1826, 'Momentasone 1mg/g CREAM 15g DIAMETASONE', 'E23259', 43, 270.00, 185.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1827, 'SILVER SULFADIAZINE 10MG/G 20G MAZINE', '23J084', 43, 105.00, 52.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1828, 'BL CREAM 20s', '240626', 43, 40.00, 31.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1829, 'KETO+CLOBE CREAM 7G', 'N02', 43, 33.00, 24.75, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL),
(1830, 'CANESTEN CREAM 5G', '23CN79A', 43, 220.00, 193.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'CP', NULL, NULL, NULL, NULL),
(1831, 'DAKTARIN ORAL GEL 3.5G', '23DKG-39', 44, 280.00, 275.00, 100, '2025-10-16 00:00:00', '2027-01-01', 'RB', NULL, NULL, NULL, NULL),
(1832, 'Bioderm Tin Oint 5g', '23J04', 45, 20.00, 18.25, 100, '2025-10-16 00:00:00', '2027-01-01', 'RG', NULL, NULL, NULL, NULL);

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
(70, 'Cabales', 'Edmalyn', 'P', 'Elyn', 'edmalyncabales3@gmail.com', '$2y$10$YK/qomLkxt.kjmMdu0ViZerixfr9IcbutRJD7/P9A0txN2pesnH/.', 'pos', NULL);

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
(96, 20, 'Inventory System: User logged in successfully', '2025-10-26 21:29:40'),
(97, 15, 'Admin System: User logged in successfully', '2025-10-29 01:16:02'),
(98, 15, 'Admin approved login request for Elyn (pos)', '2025-10-29 01:21:31'),
(99, 70, 'Pos System: User logged in successfully after approval', '2025-10-29 01:21:34');

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
-- Indexes for table `login_approvals`
--
ALTER TABLE `login_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_requested_at` (`requested_at`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=336;

--
-- AUTO_INCREMENT for table `customer_history`
--
ALTER TABLE `customer_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `login_approvals`
--
ALTER TABLE `login_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `otp_verification`
--
ALTER TABLE `otp_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1833;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `login_approvals`
--
ALTER TABLE `login_approvals`
  ADD CONSTRAINT `login_approvals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `login_approvals_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

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
