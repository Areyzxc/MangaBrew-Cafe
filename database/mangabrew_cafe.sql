-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3327
-- Generation Time: May 27, 2025 at 05:50 PM
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
-- Database: `mangabrew_cafe`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Hot Coffee', 'Our signature hot coffee selections', '2025-05-12 17:12:44', '2025-05-12 17:12:44'),
(2, 'Cold Coffee', 'Refreshing iced coffee drinks', '2025-05-12 17:12:44', '2025-05-12 17:12:44'),
(3, 'Tea', 'Traditional and specialty tea options', '2025-05-12 17:12:44', '2025-05-12 17:12:44'),
(4, 'Pastries', 'Freshly baked goods and desserts', '2025-05-12 17:12:44', '2025-05-12 17:12:44'),
(5, 'Manga Specials', 'Special drinks inspired by popular manga series', '2025-05-12 17:12:44', '2025-05-12 17:12:44');

-- --------------------------------------------------------

--
-- Table structure for table `custom_drinks`
--

CREATE TABLE `custom_drinks` (
  `custom_drink_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ingredients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ingredients`)),
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `manga_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manga`
--

CREATE TABLE `manga` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `author` varchar(100) NOT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `available_copies` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `stock` int(11) NOT NULL DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `category_id`, `name`, `description`, `price`, `image`, `stock`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 1, 'Classic Brew', 'Our signature house blend coffee', 120.00, 'classic-brew.jpg', 50, 1, '2025-05-12 17:13:27', '2025-05-12 17:13:27'),
(2, 1, 'Cappuccino', 'Espresso with steamed milk and foam', 140.00, 'cappuccino.jpg', 50, 1, '2025-05-12 17:13:27', '2025-05-12 17:13:27'),
(3, 2, 'Iced Latte', 'Espresso with cold milk over ice', 150.00, 'iced-latte.jpg', 50, 1, '2025-05-12 17:13:27', '2025-05-12 17:13:27'),
(4, 2, 'Cold Brew', 'Slow-steeped coffee served cold', 160.00, 'cold-brew.jpg', 50, 1, '2025-05-12 17:13:27', '2025-05-12 17:13:27'),
(5, 3, 'Green Tea', 'Traditional Japanese green tea', 100.00, 'green-tea.jpg', 50, 1, '2025-05-12 17:13:27', '2025-05-12 17:13:27'),
(6, 3, 'Chai Latte', 'Spiced tea with steamed milk', 130.00, 'chai-latte.jpg', 50, 1, '2025-05-12 17:13:27', '2025-05-12 17:13:27'),
(7, 4, 'Croissant', 'Buttery, flaky pastry', 80.00, 'croissant.jpg', 30, 1, '2025-05-12 17:13:27', '2025-05-12 17:13:27'),
(8, 4, 'Manga Cake', 'Japanese-style sponge cake', 120.00, 'manga-cake.jpg', 20, 1, '2025-05-12 17:13:27', '2025-05-12 17:13:27'),
(9, 5, 'Naruto Ramen Latte', 'Special coffee with ramen-inspired flavors', 180.00, 'naruto-latte.jpg', 30, 1, '2025-05-12 17:13:27', '2025-05-12 17:13:27'),
(10, 5, 'One Piece Straw Hat Frappe', 'Tropical fruit frappe with a pirate twist', 190.00, 'straw-hat-frappe.jpg', 30, 1, '2025-05-12 17:13:27', '2025-05-12 17:13:27');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `pickup_time` datetime NOT NULL,
  `payment_method` enum('cash','card') NOT NULL,
  `status` enum('pending','preparing','ready','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `used` tinyint(1) DEFAULT 0,
  `used_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_details`
--

CREATE TABLE `payment_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('cash','card') NOT NULL,
  `card_last4` varchar(4) DEFAULT NULL,
  `card_expiry` varchar(5) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remember_tokens`
--

INSERT INTO `remember_tokens` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 6, 'c49e7b58963214d14bdf9b36ea6a02fd70b0be76b256ae820be3199f5bacaa44', '2025-06-11 10:47:19', '2025-05-12 16:47:19'),
(2, 6, '764a3804917bac9267310fc1906947a0173a99aae1dd3bbb020d5ddf3a1fa29a', '2025-06-16 06:35:45', '2025-05-17 12:35:45'),
(3, 7, '0fff985502f1138159c3141a49d4e37f5ef7a3965a3b844221198fc10ee3b134', '2025-06-26 08:07:41', '2025-05-27 14:07:41');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `likes_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_categories`
--

CREATE TABLE `review_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_categories`
--

INSERT INTO `review_categories` (`category_id`, `name`, `description`, `created_at`) VALUES
(1, 'Atmosphere', 'Reviews about the cafe environment and ambiance', '2025-05-27 14:54:21'),
(2, 'Service', 'Reviews about staff service and customer experience', '2025-05-27 14:54:21'),
(3, 'Manga Collection', 'Reviews about the manga library and selection', '2025-05-27 14:54:21'),
(4, 'Drinks', 'Reviews about beverages and food items', '2025-05-27 14:54:21'),
(5, 'Staff', 'Reviews specifically about staff interactions', '2025-05-27 14:54:21'),
(6, 'Events', 'Reviews about cafe events and activities', '2025-05-27 14:54:21'),
(7, 'Facilities', 'Reviews about cafe facilities and amenities', '2025-05-27 14:54:21');

-- --------------------------------------------------------

--
-- Table structure for table `review_category_mappings`
--

CREATE TABLE `review_category_mappings` (
  `review_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_photos`
--

CREATE TABLE `review_photos` (
  `photo_id` int(11) NOT NULL,
  `review_id` int(11) DEFAULT NULL,
  `photo_url` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_reactions`
--

CREATE TABLE `review_reactions` (
  `reaction_id` int(11) NOT NULL,
  `review_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `emoji` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_replies`
--

CREATE TABLE `review_replies` (
  `reply_id` int(11) NOT NULL,
  `review_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `parent_reply_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','hidden') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seating_preferences`
--

CREATE TABLE `seating_preferences` (
  `preference_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `preferred_area` enum('window','quiet_corner','group_table','no_preference') DEFAULT 'no_preference',
  `auto_reserve` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `phone`, `address`, `avatar`, `points`, `created_at`, `updated_at`) VALUES
(6, 'arasso210', '$2y$10$hiI.5MjkGjvvpmPo2uS2A.cEXDsi4o/ftkqg/INPHEW3wMKmmDCOi', 'jamesariess76@gmail.com', 'James Aries', '09605876574', NULL, 'avatar_6822304caee5f.jpg', 0, '2025-05-12 16:42:46', '2025-05-12 17:30:52'),
(7, 'arasso', '$2y$10$NNILG1.4SJEETYJju8iDie8R1DZkWJMy8YUyQb9v2qj7TzRfpuwCC', 'arasso@gmail.com', 'JAGS', '09269048201', NULL, 'avatar_6835dcc267e29.jpg', 0, '2025-05-27 14:07:24', '2025-05-27 15:39:46');

-- --------------------------------------------------------

--
-- Table structure for table `user_manga`
--

CREATE TABLE `user_manga` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `manga_id` int(11) NOT NULL,
  `status` enum('reading','completed','on_hold','dropped') DEFAULT 'reading',
  `last_read` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_rewards`
--

CREATE TABLE `user_rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reward_type` enum('coffee','manga_rental') NOT NULL,
  `points_cost` int(11) NOT NULL,
  `status` enum('pending','claimed','expired') DEFAULT 'pending',
  `claimed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `custom_drinks`
--
ALTER TABLE `custom_drinks`
  ADD PRIMARY KEY (`custom_drink_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_email_verifications_user` (`user_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `manga_id` (`manga_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`),
  ADD KEY `idx_login_attempts_ip` (`ip_address`);

--
-- Indexes for table `manga`
--
ALTER TABLE `manga`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_manga_title` (`title`),
  ADD KEY `idx_manga_genre` (`genre`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_user_token` (`user_id`,`token`),
  ADD KEY `idx_password_resets_user` (`user_id`);

--
-- Indexes for table `payment_details`
--
ALTER TABLE `payment_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_remember_tokens_user` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `review_categories`
--
ALTER TABLE `review_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `review_category_mappings`
--
ALTER TABLE `review_category_mappings`
  ADD PRIMARY KEY (`review_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `review_photos`
--
ALTER TABLE `review_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `review_id` (`review_id`);

--
-- Indexes for table `review_reactions`
--
ALTER TABLE `review_reactions`
  ADD PRIMARY KEY (`reaction_id`),
  ADD UNIQUE KEY `unique_reaction` (`review_id`,`user_id`,`emoji`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `review_id` (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_reply_id` (`parent_reply_id`);

--
-- Indexes for table `seating_preferences`
--
ALTER TABLE `seating_preferences`
  ADD PRIMARY KEY (`preference_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_email` (`email`);

--
-- Indexes for table `user_manga`
--
ALTER TABLE `user_manga`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `manga_id` (`manga_id`);

--
-- Indexes for table `user_rewards`
--
ALTER TABLE `user_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `custom_drinks`
--
ALTER TABLE `custom_drinks`
  MODIFY `custom_drink_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `manga`
--
ALTER TABLE `manga`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_details`
--
ALTER TABLE `payment_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_categories`
--
ALTER TABLE `review_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `review_photos`
--
ALTER TABLE `review_photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_reactions`
--
ALTER TABLE `review_reactions`
  MODIFY `reaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_replies`
--
ALTER TABLE `review_replies`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seating_preferences`
--
ALTER TABLE `seating_preferences`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_manga`
--
ALTER TABLE `user_manga`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_rewards`
--
ALTER TABLE `user_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `custom_drinks`
--
ALTER TABLE `custom_drinks`
  ADD CONSTRAINT `custom_drinks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`manga_id`) REFERENCES `manga` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_details`
--
ALTER TABLE `payment_details`
  ADD CONSTRAINT `payment_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `review_category_mappings`
--
ALTER TABLE `review_category_mappings`
  ADD CONSTRAINT `review_category_mappings_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_category_mappings_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `review_categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `review_photos`
--
ALTER TABLE `review_photos`
  ADD CONSTRAINT `review_photos_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE;

--
-- Constraints for table `review_reactions`
--
ALTER TABLE `review_reactions`
  ADD CONSTRAINT `review_reactions_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD CONSTRAINT `review_replies_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `review_replies_ibfk_3` FOREIGN KEY (`parent_reply_id`) REFERENCES `review_replies` (`reply_id`) ON DELETE CASCADE;

--
-- Constraints for table `seating_preferences`
--
ALTER TABLE `seating_preferences`
  ADD CONSTRAINT `seating_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_manga`
--
ALTER TABLE `user_manga`
  ADD CONSTRAINT `user_manga_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_manga_ibfk_2` FOREIGN KEY (`manga_id`) REFERENCES `manga` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_rewards`
--
ALTER TABLE `user_rewards`
  ADD CONSTRAINT `user_rewards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
