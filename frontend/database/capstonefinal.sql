-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2025 at 09:32 AM
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
-- Database: `capstonefinal`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Electronics', NULL),
(2, 'Clothing', NULL),
(3, 'Food & Beverage', NULL),
(4, 'Books', NULL),
(5, 'Home Goods', NULL),
(6, 'drinks', 'coca cola'),
(8, 'Dondon', 'walaaa');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `barcode` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `barcode`, `name`, `description`, `price`, `cost_price`, `stock_quantity`, `category_id`, `supplier_id`, `brand`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '123456789012', 'Laptop Pro', 'High-performance laptop for professionals', 1200.00, 900.00, 120, 1, NULL, NULL, 1, '2025-05-26 11:48:14', '2025-05-29 14:48:37'),
(2, '987654321098', 'Wireless Mouse', 'Ergonomic wireless mouse', 25.50, 15.00, 100, 1, NULL, NULL, 1, '2025-05-26 11:48:14', '2025-05-29 14:58:59'),
(3, '112233445566', 'T-Shirt (Medium)', 'Comfortable cotton t-shirt', 15.00, 7.50, 98, 2, NULL, NULL, 1, '2025-05-26 11:48:14', '2025-05-29 15:26:29'),
(4, '223344556677', 'Coffee Beans (250g)', 'Freshly roasted arabica beans', 8.75, 4.00, 107, 3, NULL, NULL, 1, '2025-05-26 11:48:14', '2025-05-29 14:31:36'),
(5, '334455667788', 'The Great Novel', 'A compelling fiction novel', 20.00, 10.00, 100, 4, NULL, NULL, 1, '2025-05-26 11:48:14', '2025-05-29 14:41:29'),
(6, '445566778899', 'Desk Lamp', 'Modern LED desk lamp', 35.99, 20.00, 108, 5, NULL, NULL, 1, '2025-05-26 11:48:14', '2025-05-29 14:33:14'),
(7, '556677889900', 'Bluetooth Speaker', 'Portable speaker with clear sound', 75.00, 40.00, 110, 1, NULL, NULL, 1, '2025-05-26 11:48:14', '2025-05-29 13:17:02'),
(8, '667788990011', 'Jeans (Size 32)', 'Slim fit denim jeans', 45.00, 25.00, 120, 2, NULL, NULL, 1, '2025-05-26 11:48:14', '2025-05-29 14:46:57'),
(9, '778899001122', 'Energy Drink (Can)', 'Refreshing energy boost', 2.50, 1.00, 200, 3, NULL, NULL, 1, '2025-05-26 11:48:14', '2025-05-26 11:48:14'),
(10, '889900112233', 'Cookbook: Italian', 'Authentic Italian recipes', 30.00, 15.00, 108, 4, NULL, NULL, 1, '2025-05-26 11:48:14', '2025-05-29 14:37:19'),
(11, '990011223344', 'Yoga Mat', 'Non-slip yoga mat', 22.00, 10.00, 100, 5, NULL, NULL, 1, '2025-05-26 11:48:14', '2025-05-29 15:00:25'),
(14, '12345678', 'ramil', NULL, 50.00, 40.00, 300, 4, NULL, 'wala', 1, '2025-05-29 12:26:29', '2025-05-29 14:36:46'),
(15, '12356', 'ranel', NULL, 50.00, 30.00, 300, 4, NULL, 'wala', 1, '2025-05-29 12:33:37', '2025-05-29 13:28:43');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `sale_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL,
  `cash_received` decimal(10,2) DEFAULT NULL,
  `change_due` decimal(10,2) DEFAULT NULL,
  `cashier_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `sale_date`, `total_amount`, `user_id`, `discount_amount`, `tax_amount`, `payment_method`, `cash_received`, `change_due`, `cashier_id`, `customer_id`, `status`) VALUES
(3, '2025-05-26 15:05:17', 75.00, NULL, 0.00, 0.00, 'Cash', NULL, NULL, 1, NULL, 'completed'),
(4, '2025-05-26 15:06:48', 75.00, NULL, 0.00, 0.00, 'Cash', NULL, NULL, 1, NULL, 'completed'),
(5, '2025-05-26 15:11:45', 8.75, NULL, 0.00, 0.00, 'Cash', NULL, NULL, 1, NULL, 'completed'),
(6, '2025-05-26 15:13:14', 30.00, NULL, 0.00, 0.00, 'Cash', NULL, NULL, 1, NULL, 'completed'),
(7, '2025-05-26 15:18:19', 403.09, NULL, 0.00, 0.00, 'Cash', NULL, NULL, 1, NULL, 'completed'),
(8, '2025-05-26 15:22:35', 252.00, NULL, 0.00, 0.00, 'Cash', NULL, NULL, 1, NULL, 'completed'),
(9, '2025-05-26 15:27:56', 420.00, NULL, 0.00, 0.00, 'Cash', NULL, NULL, 1, NULL, 'completed'),
(10, '2025-05-26 15:38:55', 420.00, NULL, 0.00, 0.00, 'Cash', NULL, NULL, 1, NULL, 'completed'),
(11, '2025-05-26 15:44:22', 336.00, NULL, 0.00, 0.00, 'Cash', NULL, NULL, 1, NULL, 'completed'),
(12, '2025-05-26 15:55:38', 94.08, NULL, 0.00, 10.08, 'Cash', 200.00, 0.00, 1, NULL, 'completed'),
(13, '2025-05-26 15:57:34', 94.08, NULL, 0.00, 10.08, 'Cash', 100.00, 0.00, 1, NULL, 'completed'),
(14, '2025-05-26 16:11:15', 84.00, NULL, 0.00, 0.00, 'Cash', NULL, NULL, 1, NULL, 'completed'),
(15, '2025-05-26 16:31:40', 84.00, NULL, 0.00, 0.00, 'Cash', 200.00, 116.00, 1, NULL, 'completed'),
(16, '2025-05-26 16:32:33', 9.80, NULL, 0.00, 0.00, 'Cash', 100.00, 90.20, 1, NULL, 'completed'),
(17, '2025-05-26 16:33:08', 9.80, NULL, 0.00, 0.00, 'Cash', 100.00, 90.20, 1, NULL, 'completed'),
(18, '2025-05-26 16:45:44', 9.80, NULL, 0.00, 1.05, 'Cash', 10.00, 0.20, 1, NULL, 'completed'),
(19, '2025-05-26 16:46:26', 9.80, NULL, 0.00, 1.05, 'Cash', 10.00, 0.20, 1, NULL, 'completed'),
(20, '2025-05-26 16:50:02', 9.80, NULL, 0.00, 1.05, 'Cash', 10.00, 0.20, 1, NULL, 'completed'),
(21, '2025-05-26 16:50:23', 9.80, NULL, 0.00, 1.05, 'Cash', 10.00, 0.20, 1, NULL, 'completed'),
(22, '2025-05-26 17:00:33', 9.80, NULL, 0.00, 1.05, 'Cash', 20.00, 10.20, 1, NULL, 'completed'),
(23, '2025-05-26 17:01:07', 9.80, NULL, 0.00, 1.05, 'Cash', 20.00, 10.20, 1, NULL, 'completed'),
(24, '2025-05-26 17:11:10', 19.60, NULL, 0.00, 2.10, 'Cash', 20.00, 0.40, 1, NULL, 'completed'),
(25, '2025-05-26 17:17:01', 19.60, NULL, 0.00, 2.10, 'Cash', 100.00, 80.40, 1, NULL, 'completed'),
(26, '2025-05-26 17:22:01', 19.60, NULL, 0.00, 2.10, 'Cash', 100.00, 80.40, 1, NULL, 'completed'),
(27, '2025-05-26 17:23:14', 19.60, NULL, 0.00, 2.10, 'Cash', 20.00, 0.40, 1, NULL, 'completed'),
(28, '2025-05-26 17:24:36', 29.40, NULL, 0.00, 3.15, 'Cash', 200.00, 170.60, 1, NULL, 'completed'),
(29, '2025-05-26 17:30:25', 19.60, NULL, 0.00, 2.10, 'Cash', 100.00, 80.40, 1, NULL, 'completed'),
(30, '2025-05-26 17:41:39', 19.60, NULL, 0.00, 2.10, 'Cash', 50.00, 30.40, 1, NULL, 'completed'),
(31, '2025-05-26 17:45:35', 19.60, NULL, 0.00, 2.10, 'Cash', 50.00, 30.40, 1, NULL, 'completed'),
(32, '2025-05-26 17:49:00', 19.60, NULL, 0.00, 2.10, 'Cash', 100.00, 80.40, 1, NULL, 'completed'),
(33, '2025-05-26 17:54:18', 29.40, NULL, 0.00, 3.15, 'Cash', 200.00, 170.60, 1, NULL, 'completed'),
(34, '2025-05-26 18:23:03', 168.00, NULL, 0.00, 18.00, 'Cash', 200.00, 32.00, 1, NULL, 'completed'),
(35, '2025-05-27 14:11:23', 1344.00, NULL, 0.00, 144.00, 'Cash', 2000.00, 656.00, 1, NULL, 'completed'),
(36, '2025-05-27 14:39:55', 33.60, NULL, 0.00, 3.60, 'Cash', 50.00, 16.40, 1, NULL, 'completed'),
(37, '2025-05-28 11:06:11', 19.60, NULL, 0.00, 2.10, 'Cash', 50.00, 30.40, 1, NULL, 'completed'),
(38, '2025-05-28 11:08:04', 1344.00, NULL, 0.00, 144.00, 'Cash', 2000.00, 656.00, 2, NULL, 'completed'),
(39, '2025-05-28 13:09:03', 84.00, NULL, 0.00, 9.00, 'Cash', 100.00, 16.00, 1, NULL, 'completed'),
(40, '2025-05-29 09:58:21', 80.62, NULL, 0.00, 8.64, 'Cash', 100.00, 19.38, 1, NULL, 'completed'),
(41, '2025-05-29 15:26:29', 33.60, NULL, 0.00, 3.60, 'Cash', 100.00, 66.40, 1, NULL, 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_sale` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `price_at_sale`, `subtotal`) VALUES
(2, 3, 7, 1, 75.00, 75.00),
(3, 4, 7, 1, 75.00, 75.00),
(4, 5, 4, 1, 8.75, 8.75),
(5, 6, 10, 1, 30.00, 30.00),
(6, 7, 6, 10, 35.99, 359.90),
(7, 8, 7, 3, 75.00, 225.00),
(8, 9, 7, 5, 75.00, 375.00),
(9, 10, 7, 5, 75.00, 375.00),
(10, 11, 7, 4, 75.00, 300.00),
(11, 12, 7, 1, 75.00, 75.00),
(12, 13, 7, 1, 75.00, 75.00),
(13, 14, 7, 1, 75.00, 75.00),
(14, 15, 7, 1, 75.00, 75.00),
(15, 16, 4, 1, 8.75, 8.75),
(16, 17, 4, 1, 8.75, 8.75),
(17, 18, 4, 1, 8.75, 8.75),
(18, 19, 4, 1, 8.75, 8.75),
(19, 20, 4, 1, 8.75, 8.75),
(20, 21, 4, 1, 8.75, 8.75),
(21, 22, 4, 1, 8.75, 8.75),
(22, 23, 4, 1, 8.75, 8.75),
(23, 24, 4, 2, 8.75, 17.50),
(24, 25, 4, 2, 8.75, 17.50),
(25, 26, 4, 2, 8.75, 17.50),
(26, 27, 4, 2, 8.75, 17.50),
(27, 28, 4, 3, 8.75, 26.25),
(28, 29, 4, 2, 8.75, 17.50),
(29, 30, 4, 2, 8.75, 17.50),
(30, 31, 4, 2, 8.75, 17.50),
(31, 32, 4, 2, 8.75, 17.50),
(32, 33, 4, 3, 8.75, 26.25),
(33, 34, 7, 2, 75.00, 150.00),
(34, 35, 1, 1, 1200.00, 1200.00),
(35, 36, 10, 1, 30.00, 30.00),
(36, 37, 4, 2, 8.75, 17.50),
(37, 38, 1, 1, 1200.00, 1200.00),
(38, 39, 7, 1, 75.00, 75.00),
(39, 40, 6, 2, 35.99, 71.98),
(40, 41, 3, 2, 15.00, 30.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `current_quantity_after_change` int(11) NOT NULL,
  `change_type` enum('purchase_in','sale_out','adjustment_in','adjustment_out','initial_load') NOT NULL,
  `change_date` datetime DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_history`
--

INSERT INTO `stock_history` (`id`, `product_id`, `quantity_change`, `current_quantity_after_change`, `change_type`, `change_date`, `user_id`, `description`) VALUES
(1, 14, 200, 300, 'purchase_in', '2025-05-29 14:36:46', 1, 'Stock added manually (via Add Stock form) for product ID: 14'),
(2, 10, 100, 108, 'purchase_in', '2025-05-29 14:37:19', 1, 'Stock added manually (via Add Stock form) for product ID: 10'),
(3, 5, 70, 100, 'purchase_in', '2025-05-29 14:41:29', 1, 'Stock added manually (via Add Stock form) for product ID: 5'),
(4, 1, 50, 63, 'purchase_in', '2025-05-29 14:42:36', 1, 'Stock added manually (via Add Stock form) for product ID: 1'),
(5, 1, 50, 113, 'purchase_in', '2025-05-29 14:43:45', 1, 'Stock added manually (via Add Stock form) for product ID: 1'),
(6, 8, 40, 100, 'purchase_in', '2025-05-29 14:45:57', 1, 'Stock added manually (via Add Stock form) for product ID: 8'),
(7, 2, 50, 100, 'purchase_in', '2025-05-29 14:58:59', 1, 'Stock added manually (via Add Stock form) for product ID: 2'),
(8, 11, 35, 70, 'adjustment_in', '2025-05-29 14:59:36', 1, 'Manual stock increase via product edit'),
(9, 11, 30, 100, 'adjustment_in', '2025-05-29 15:00:25', 1, 'Manual stock increase via product edit'),
(10, 3, -2, 98, 'sale_out', '2025-05-29 15:26:29', 1, 'Sale (Order ID: 41)');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`) VALUES
(1, 'Ranel Dahil', 'Ranel Dahil', '', '', 'Taytay, Danao City, Cebu'),
(2, 'Ramil Dondon', 'Ramil Dondon', '09815697578', '', 'Taytay, Danao City, Cebu');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'cashier',
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `created_at`, `last_login`) VALUES
(1, 'admin', NULL, '$2y$10$glqmcJ6BnfuNaE7zYLg0aubFQjMTfeEB4ehjSyRgTbgr/YYmmsEXy', 'admin', '2025-05-26 09:35:33', '2025-05-29 13:06:43'),
(2, 'Pero', 'cruspero@gmail.com', '$2y$10$s3IrZsal1bu6yfYIcCeyVeISQcnEUO9R1xNrCuhLLl6U0XUoq6R.e', 'cashier', '2025-05-27 14:47:36', '2025-05-28 11:06:59');

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
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cashier_id` (`cashier_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `fk_sales_user_id` (`user_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_stock_history_product_id` (`product_id`),
  ADD KEY `idx_stock_history_change_date` (`change_date`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
