-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 20, 2026 at 06:14 PM
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
-- Database: `hardware_erp`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `created_at`) VALUES
(1, 'Roofing', 'Deals with roofing materials', '2026-03-20 14:39:14'),
(2, 'Foundation', 'For foundation materials', '2026-03-20 14:46:00'),
(3, 'Plumbing', '', '2026-03-20 14:46:28');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(30) NOT NULL DEFAULT 'piece',
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `restock_level` int(11) NOT NULL DEFAULT 20,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_id`, `category_id`, `name`, `description`, `unit`, `price`, `stock_quantity`, `restock_level`, `created_at`, `updated_at`) VALUES
(1, 3, 'Pipe', '', 'piece', 0.00, 43, 20, '2026-03-20 14:51:18', '2026-03-20 16:05:10'),
(2, 1, 'Iron Sheets', '', 'piece', 0.00, 29, 20, '2026-03-20 15:03:12', '2026-03-20 16:13:05');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `log_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`log_id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 1, 'category_create', 'Category \'Roofing\' (#1) created.', '2026-03-20 14:39:14'),
(2, 1, 'category_create', 'Category \'Foundation\' (#2) created.', '2026-03-20 14:46:00'),
(3, 1, 'category_create', 'Category \'Plumbing\' (#3) created.', '2026-03-20 14:46:28'),
(4, 1, 'item_create', 'Item \'Pipe\' (#1) added.', '2026-03-20 14:51:18'),
(5, 1, 'item_create', 'Item \'Iron Sheets\' (#2) added.', '2026-03-20 15:03:12'),
(6, 1, 'sale_create', 'Sale #1 — 1 item(s) — UGX 30000', '2026-03-20 15:21:28'),
(7, 1, 'sale_create', 'Sale #2 — 1 item(s) — UGX 400000', '2026-03-20 15:39:28'),
(8, 1, 'restock_create', 'Restock #1 — 2 item(s) from \'\'.', '2026-03-20 16:05:10'),
(9, 1, 'restock_create', 'Restock #2 — 1 item(s) from \'Quick restock\'.', '2026-03-20 16:13:05');

-- --------------------------------------------------------

--
-- Table structure for table `restocks`
--

CREATE TABLE `restocks` (
  `restock_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `supplier` varchar(150) DEFAULT NULL,
  `po_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `restock_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restocks`
--

INSERT INTO `restocks` (`restock_id`, `user_id`, `supplier`, `po_number`, `notes`, `restock_date`, `created_at`) VALUES
(1, 1, NULL, NULL, NULL, '2026-03-20', '2026-03-20 16:05:10'),
(2, 1, 'Quick restock', NULL, 'Quick restock from Low Stock Alerts page', '2026-03-20', '2026-03-20 16:13:05');

-- --------------------------------------------------------

--
-- Table structure for table `restock_items`
--

CREATE TABLE `restock_items` (
  `restock_item_id` int(10) UNSIGNED NOT NULL,
  `restock_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `new_price` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restock_items`
--

INSERT INTO `restock_items` (`restock_item_id`, `restock_id`, `item_id`, `item_name`, `category`, `quantity`, `new_price`) VALUES
(1, 1, 1, 'Pipe', NULL, 20, 0.00),
(2, 1, 2, 'Iron Sheets', NULL, 6, 0.00),
(3, 2, 2, 'Iron Sheets', NULL, 10, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `payment_method` enum('cash','mobile_money','bank','credit') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `user_id`, `customer_name`, `payment_method`, `notes`, `total_amount`, `sale_date`) VALUES
(1, 1, 'Walk-in', 'mobile_money', NULL, 30000.00, '2026-03-20 15:21:28'),
(2, 1, NULL, NULL, NULL, 400000.00, '2026-03-20 15:39:28');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `sale_item_id` int(10) UNSIGNED NOT NULL,
  `sale_id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`sale_item_id`, `sale_id`, `item_id`, `item_name`, `category`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 1, 'Pipe', 'Plumbing', 2, 15000.00, 30000.00),
(2, 2, 2, 'Iron Sheets', 'Roofing', 10, 40000.00, 400000.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `phone`, `role`, `created_at`) VALUES
(1, 'Denis', '$2y$12$07lzefSYAel5k0/lpQKeWu.CRmnEQtFoJWtLTXHsGPnBGgG/QcvPy', 'Denis Jr', '0762924109', 'user', '2026-03-20 14:35:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_items_category` (`category_id`),
  ADD KEY `idx_items_stock` (`stock_quantity`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_logs_user` (`user_id`);

--
-- Indexes for table `restocks`
--
ALTER TABLE `restocks`
  ADD PRIMARY KEY (`restock_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_restock_date` (`restock_date`);

--
-- Indexes for table `restock_items`
--
ALTER TABLE `restock_items`
  ADD PRIMARY KEY (`restock_item_id`),
  ADD KEY `restock_id` (`restock_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_sales_date` (`sale_date`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`sale_item_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `idx_sale_items_sale` (`sale_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `restocks`
--
ALTER TABLE `restocks`
  MODIFY `restock_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `restock_items`
--
ALTER TABLE `restock_items`
  MODIFY `restock_item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `sale_item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `restocks`
--
ALTER TABLE `restocks`
  ADD CONSTRAINT `restocks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `restock_items`
--
ALTER TABLE `restock_items`
  ADD CONSTRAINT `restock_items_ibfk_1` FOREIGN KEY (`restock_id`) REFERENCES `restocks` (`restock_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `restock_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`item_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
