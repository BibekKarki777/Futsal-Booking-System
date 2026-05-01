-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 28, 2026 at 06:50 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `25123788`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `court_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `booking_status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `court_id`, `booking_date`, `start_time`, `end_time`, `total_price`, `booking_status`, `created_at`, `updated_at`) VALUES
(1, 2, 1, '2026-01-24', '10:00:00', '11:00:00', 2500.00, 'confirmed', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(2, 2, 2, '2026-01-25', '14:00:00', '16:00:00', 4000.00, 'pending', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(3, 3, 4, '2026-01-24', '18:00:00', '20:00:00', 6000.00, 'confirmed', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(4, 4, 4, '2026-01-30', '19:00:00', '20:00:00', 3000.00, 'confirmed', '2026-01-24 15:48:38', '2026-01-24 15:48:38'),
(5, 4, 2, '2026-02-03', '06:00:00', '07:00:00', 2000.00, 'confirmed', '2026-01-24 15:49:30', '2026-01-24 15:49:30'),
(6, 5, 6, '2026-02-01', '17:00:00', '18:00:00', 3500.00, 'confirmed', '2026-01-24 15:52:39', '2026-01-28 03:52:32'),
(7, 5, 3, '2026-02-07', '10:00:00', '11:00:00', 1500.00, 'confirmed', '2026-01-24 15:53:58', '2026-01-24 15:53:58'),
(8, 5, 4, '2026-02-04', '14:00:00', '15:00:00', 3000.00, 'confirmed', '2026-01-24 15:55:08', '2026-01-24 15:55:08'),
(9, 4, 6, '2026-02-08', '14:00:00', '15:00:00', 3500.00, 'pending', '2026-01-28 04:39:50', '2026-01-28 04:39:50');

-- --------------------------------------------------------

--
-- Table structure for table `courts`
--

CREATE TABLE `courts` (
  `court_id` int(11) NOT NULL,
  `futsal_id` int(11) NOT NULL,
  `court_name` varchar(100) NOT NULL,
  `surface_type` varchar(50) NOT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `status` enum('active','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courts`
--

INSERT INTO `courts` (`court_id`, `futsal_id`, `court_name`, `surface_type`, `price_per_hour`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Court A - Premium', 'Artificial Grass', 2500.00, 'active', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(2, 1, 'Court B - Standard', 'Artificial Grass', 2000.00, 'active', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(3, 1, 'Court C - Mini', 'Rubber Mat', 1500.00, 'active', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(4, 2, 'Main Arena', 'Artificial Grass', 3000.00, 'active', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(5, 2, 'Training Ground', 'Artificial Grass', 2000.00, 'active', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(6, 3, 'Elite Court', 'Premium Turf', 3500.00, 'active', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(7, 3, 'Practice Court', 'Standard Turf', 2500.00, 'maintenance', '2026-01-24 15:33:04', '2026-01-24 15:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `futsals`
--

CREATE TABLE `futsals` (
  `futsal_id` int(11) NOT NULL,
  `futsal_name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `open_time` time NOT NULL,
  `close_time` time NOT NULL,
  `status` enum('active','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `futsals`
--

INSERT INTO `futsals` (`futsal_id`, `futsal_name`, `address`, `city`, `contact_number`, `open_time`, `close_time`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Green Field Futsal', 'Thamel, Street 5', 'Kathmandu', '9841234567', '06:00:00', '22:00:00', 'active', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(2, 'Champions Arena', 'Lakeside Road', 'Pokhara', '9851234567', '07:00:00', '21:00:00', 'active', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(3, 'Victory Sports Hub', 'Durbar Marg', 'Kathmandu', '9861234567', '05:00:00', '23:00:00', 'active', '2026-01-24 15:33:04', '2026-01-24 15:33:04');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('cash','esewa','khalti','bank') NOT NULL,
  `payment_status` enum('unpaid','paid','refunded') DEFAULT 'unpaid',
  `paid_at` datetime DEFAULT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `amount`, `method`, `payment_status`, `paid_at`, `transaction_ref`, `created_at`, `updated_at`) VALUES
(1, 1, 2500.00, 'esewa', 'paid', '2026-01-24 21:18:04', 'ESW123456789', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(2, 2, 4000.00, 'cash', 'unpaid', NULL, NULL, '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(3, 3, 6000.00, 'khalti', 'paid', '2026-01-24 21:18:04', 'KHL987654321', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(4, 4, 3000.00, 'esewa', 'paid', '2026-01-24 21:33:38', 'ESW762904561', '2026-01-24 15:48:38', '2026-01-24 15:48:38'),
(5, 5, 2000.00, 'khalti', 'paid', '2026-01-24 21:34:30', 'KHL518345607', '2026-01-24 15:49:30', '2026-01-24 15:49:30'),
(6, 6, 3500.00, 'cash', 'paid', '2026-01-28 09:37:32', 'MLT378273278', '2026-01-24 15:52:39', '2026-01-28 03:52:32'),
(7, 7, 1500.00, 'bank', 'paid', '2026-01-24 21:38:58', 'CSH542901445', '2026-01-24 15:53:58', '2026-01-24 15:53:58'),
(8, 8, 3000.00, 'khalti', 'paid', '2026-01-24 21:40:08', 'KHL711905237', '2026-01-24 15:55:08', '2026-01-24 15:55:08'),
(9, 9, 3500.00, 'cash', 'unpaid', NULL, NULL, '2026-01-28 04:39:50', '2026-01-28 04:39:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `role` enum('admin','player') DEFAULT 'player',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `contact_number`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'User', 'admin@futsal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9800000000', 'admin', '2026-01-24 15:33:04', '2026-01-24 15:33:04'),
(2, 'Ram', 'Sharma', 'ram@example.com', '$2y$10$zZ31rvO.vUh.mewD8u2qJeSavtTKJC/RKek8xNFIF3DfXQWe7Y7O2', '9812345678', 'player', '2026-01-24 15:33:04', '2026-01-24 15:44:31'),
(3, 'Sita', 'Thapa', 'sita@example.com', '$2y$10$EHmuVaWyjDGcPA3DxaW5y.C8Ht4sOuNwKpXpj/jUvauvKL5ZApc/.', '9823456789', 'player', '2026-01-24 15:33:04', '2026-01-24 15:42:47'),
(4, 'Utsav', 'Rai', 'utsavrai@gmail.com', '$2y$10$etNorfk5vVQ6IqFEZU9xF.3vMAQsRlK9L3xYbUHwkCSD0yVzZon2K', '9845231785', 'player', '2026-01-24 15:47:06', '2026-01-24 15:50:56'),
(5, 'Sushant', 'Shrestha', 'sushantshrestha@gmail.com', '$2y$10$s9pxbPnZx7GMX5C6/c2RmeNv/0eTgraYfWw8/HXta7Yk8cJg.MvNK', '7851045238', 'player', '2026-01-24 15:52:04', '2026-01-24 15:52:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_bookings_user` (`user_id`),
  ADD KEY `idx_bookings_court` (`court_id`),
  ADD KEY `idx_bookings_date` (`booking_date`);

--
-- Indexes for table `courts`
--
ALTER TABLE `courts`
  ADD PRIMARY KEY (`court_id`),
  ADD KEY `idx_courts_futsal` (`futsal_id`);

--
-- Indexes for table `futsals`
--
ALTER TABLE `futsals`
  ADD PRIMARY KEY (`futsal_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD KEY `idx_payments_booking` (`booking_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `courts`
--
ALTER TABLE `courts`
  MODIFY `court_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `futsals`
--
ALTER TABLE `futsals`
  MODIFY `futsal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`court_id`) REFERENCES `courts` (`court_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `courts`
--
ALTER TABLE `courts`
  ADD CONSTRAINT `courts_ibfk_1` FOREIGN KEY (`futsal_id`) REFERENCES `futsals` (`futsal_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
