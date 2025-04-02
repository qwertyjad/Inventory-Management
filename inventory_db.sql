-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 02, 2025 at 01:13 PM
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
-- Database: `inventory_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `delivery_tracking`
--

CREATE TABLE `delivery_tracking` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `delivered_quantity` int(11) NOT NULL DEFAULT 0,
  `delivery_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_tracking`
--

INSERT INTO `delivery_tracking` (`id`, `po_number`, `item_name`, `delivered_quantity`, `delivery_date`) VALUES
(1, 'PO-206282', 'Thor Luna', 500, '2025-04-02 02:49:46');

-- --------------------------------------------------------

--
-- Table structure for table `guest_users`
--

CREATE TABLE `guest_users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guest_users`
--

INSERT INTO `guest_users` (`id`, `name`, `role`, `created_at`) VALUES
(1, 'hadz', 'user', '2025-04-01 14:15:02'),
(2, 'okay', 'user', '2025-04-01 14:19:56');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) NOT NULL DEFAULT 0,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `location` varchar(255) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `name`, `unit`, `quantity`, `min_stock_level`, `cost`, `total_cost`, `location`, `supplier`, `description`, `created_at`, `last_updated`) VALUES
(32, 'Hasad Keith', 'boxes', 0, 5, 16.00, 8000.00, 'Ea non assumenda ani', 'Yetta Peterson', 'Ut similique deserun', '2025-04-01 19:03:55', '2025-04-02 11:10:56');

-- --------------------------------------------------------

--
-- Table structure for table `item_requests`
--

CREATE TABLE `item_requests` (
  `id` int(11) NOT NULL,
  `guest_user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `request_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_requests`
--

INSERT INTO `item_requests` (`id`, `guest_user_id`, `item_id`, `item_name`, `quantity`, `request_date`, `status`) VALUES
(2, 1, 25, 'Joshua Barber', 706, '2025-04-02 00:29:38', 'canceled'),
(3, 1, 25, 'Joshua Barber', 428, '2025-04-02 00:45:23', 'canceled'),
(8, 1, 25, 'Joshua Barber', 788, '2025-04-02 01:31:02', 'rejected'),
(10, 1, 25, 'Joshua Barber', 290, '2025-04-02 02:00:47', 'approved'),
(11, 1, 25, 'Joshua Barber', 279, '2025-04-02 02:09:43', 'approved'),
(12, 1, 28, 'Thor Luna', 300, '2025-04-02 02:15:47', 'approved'),
(13, 1, 31, 'Thor Luna', 12, '2025-04-02 02:45:02', 'rejected'),
(14, 1, 32, 'Hasad Keith', 600, '2025-04-02 03:06:32', 'approved'),
(15, 1, 32, 'Hasad Keith', 1, '2025-04-02 03:09:03', 'canceled'),
(16, 1, 34, 'Hasad Keith', 600, '2025-04-02 03:24:23', 'approved'),
(17, 1, 34, 'Rashad Chavez', 28, '2025-04-02 03:37:22', 'approved'),
(18, 1, 32, 'Hasad Keith', 28, '2025-04-02 03:38:38', 'approved'),
(19, 1, 32, 'Hasad Keith', 1000, '2025-04-02 11:16:42', 'approved'),
(20, 1, 37, 'Genevieve Hess', 990, '2025-04-02 11:33:57', 'approved'),
(21, 1, 32, 'Hasad Keith', 1, '2025-04-02 11:39:36', 'canceled'),
(22, 1, 32, 'Hasad Keith', 314, '2025-04-02 11:40:26', 'canceled'),
(23, 1, 32, 'Hasad Keith', 447, '2025-04-02 11:57:04', 'canceled'),
(24, 1, 32, 'Hasad Keith', 166, '2025-04-02 12:09:34', 'canceled'),
(25, 1, 32, 'Hasad Keith', 307, '2025-04-02 12:10:50', 'canceled'),
(26, 1, 32, 'Hasad Keith', 90, '2025-04-02 12:13:14', 'rejected'),
(27, 1, 32, 'Hasad Keith', 1, '2025-04-02 12:19:40', 'rejected'),
(28, 1, 32, 'Hasad Keith', 461, '2025-04-02 12:36:28', 'approved'),
(29, 1, 32, 'Hasad Keith', 39, '2025-04-02 19:10:45', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(10) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` datetime NOT NULL,
  `status` enum('ordered','shipped','delivered','cancelled') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remaining_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_number`, `item_name`, `quantity`, `unit_cost`, `total_cost`, `supplier_id`, `order_date`, `status`, `created_at`, `remaining_quantity`) VALUES
(13, 'PO-0A6D02', 'Allegra Glass', 7, 97.00, 679.00, 4, '2025-03-31 13:52:31', 'delivered', '2025-03-31 11:52:31', 7),
(14, 'PO-0FAD25', 'Clare Mcmillan', 406, 83.00, 33698.00, 2, '2025-03-31 13:52:59', 'cancelled', '2025-03-31 11:52:59', 406),
(15, 'PO-379594', 'Joshua Barber', 39, 84.00, 3276.00, 4, '2025-03-31 14:14:23', 'cancelled', '2025-03-31 12:14:23', 30),
(16, 'PO-F207D1', 'Fuller Witt', 38, 50.00, 1900.00, 2, '2025-03-31 14:56:53', 'cancelled', '2025-03-31 12:56:53', 38),
(17, 'PO-C926CB', 'Kellie Knowles', 935, 82.00, 76670.00, 4, '2025-03-31 14:59:52', 'cancelled', '2025-03-31 12:59:52', 935),
(18, 'PO-95F575', 'Allegra Glass', 10, 97.00, 970.00, 4, '2025-03-31 15:14:44', 'cancelled', '2025-03-31 13:14:44', 10),
(19, 'PO-BACE02', 'Stella Ferguson', 684, 57.00, 38988.00, 4, '2025-04-01 08:08:50', 'delivered', '2025-04-01 06:08:50', 684),
(20, 'PO-E37FC0', 'Joshua Barber', 800, 84.00, 67200.00, 4, '2025-04-01 08:11:35', 'cancelled', '2025-04-01 06:11:35', 791),
(21, 'PO-206282', 'Thor Luna', 500, 54.00, 27000.00, 4, '2025-04-01 20:14:29', 'cancelled', '2025-04-01 18:14:29', 0),
(22, 'PO-9FF270', 'Genevieve Hess', 993, 91.00, 90363.00, 4, '2025-04-01 20:52:04', 'delivered', '2025-04-01 18:52:04', 0),
(23, 'PO-05DF9C', 'Hermione Collier', 279, 18.00, 5022.00, 2, '2025-04-01 20:59:45', 'cancelled', '2025-04-01 18:59:45', 0),
(24, 'PO-F85552', 'Hasad Keith', 628, 16.00, 10048.00, 4, '2025-04-01 21:02:24', 'delivered', '2025-04-01 19:02:24', 0),
(25, 'PO-E33244', 'Hasad Keith', 1000, 16.00, 16000.00, 4, '2025-04-01 21:39:25', 'delivered', '2025-04-01 19:39:25', 0),
(26, 'PO-944414', 'Hasad Keith', 500, 16.00, 8000.00, 4, '2025-04-02 05:21:29', 'delivered', '2025-04-02 03:21:29', 0),
(27, 'PO-8A5CD0', 'Idola Weeks', 204, 92.00, 18768.00, 2, '2025-04-02 07:06:41', 'ordered', '2025-04-02 05:06:41', 0),
(28, 'PO-47A7A5', 'Violet Gill', 358, 48.00, 17184.00, 4, '2025-04-02 07:06:56', 'delivered', '2025-04-02 05:06:56', 0),
(29, 'PO-5ABCF2', 'Akeem Phillips', 17, 63.00, 1071.00, 4, '2025-04-02 07:38:38', 'delivered', '2025-04-02 05:38:38', 0),
(30, 'PO-AFE51A', 'Caleb Maynard', 972, 22.00, 21384.00, 4, '2025-04-02 07:43:32', 'cancelled', '2025-04-02 05:43:32', 0);

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','staff') NOT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `otp`, `otp_expires_at`, `created_at`) VALUES
(1, 'Admin User', 'admin@example.com', '$2y$10$5z5z5z5z5z5z5z5z5z5z5u5z5z5z5z5z5z5z5z5z5z5z5z5z5z5z5z5', 'admin', NULL, NULL, '2025-03-31 10:44:19'),
(2, 'Staff User', 'staff@gmail.com', '$2y$10$5z5z5z5z5z5z5z5z5z5z5u5z5z5z5z5z5z5z5z5z5z5z5z5z5z5z5z5', 'staff', NULL, NULL, '2025-03-31 10:44:19'),
(3, 'hadz', 'admin@gmail.com', '$2y$10$r3qrZAMJPQ/rOH9atrHrTuxUm67lc2YQuD2WbuIWG7i/qtkJPKV5m', 'admin', NULL, NULL, '2025-03-31 10:45:15'),
(4, 'Yetta Peterson', 'sample2@gmail.com', '$2y$10$kZ/ugmxAl5vVjGngKF.9yu9LCUC20PuaoL243OeHk3ihtxr0Z2c7S', 'staff', NULL, NULL, '2025-03-31 10:56:11'),
(5, 'hadz', '', NULL, '', NULL, NULL, '2025-04-01 13:07:49'),
(8, 'hadz', NULL, NULL, '', NULL, NULL, '2025-04-01 13:13:32'),
(9, 'hadz', NULL, NULL, '', NULL, NULL, '2025-04-01 13:13:38'),
(10, 'hadz', NULL, NULL, '', NULL, NULL, '2025-04-01 14:05:26'),
(11, 'hadz', NULL, NULL, '', NULL, NULL, '2025-04-01 14:05:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `delivery_tracking`
--
ALTER TABLE `delivery_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_number` (`po_number`);

--
-- Indexes for table `guest_users`
--
ALTER TABLE `guest_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_name` (`name`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `item_requests`
--
ALTER TABLE `item_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `delivery_tracking`
--
ALTER TABLE `delivery_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `guest_users`
--
ALTER TABLE `guest_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `item_requests`
--
ALTER TABLE `item_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `delivery_tracking`
--
ALTER TABLE `delivery_tracking`
  ADD CONSTRAINT `delivery_tracking_ibfk_1` FOREIGN KEY (`po_number`) REFERENCES `purchase_orders` (`po_number`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
