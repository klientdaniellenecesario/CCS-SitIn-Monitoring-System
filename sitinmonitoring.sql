-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 05:40 PM
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
-- Database: `sitinmonitoring`
--
CREATE DATABASE IF NOT EXISTS `sitinmonitoring` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sitinmonitoring`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', 'admin123', 'admin@ccs.edu.ph', 'System Administrator', 'admin', '2026-03-26 12:41:51');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `author` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `author`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to CCS Sit-in System', 'The new sit-in monitoring system is now live! Please register your account to start using the system.', 'CCS Admin', NULL, '2026-03-26 12:43:32', '2026-03-26 12:43:32'),
(2, 'Library Hours Extended', 'The computer lab will be open until 8 PM during exam week.', 'CCS Admin', NULL, '2026-03-26 12:43:32', '2026-03-26 12:43:32'),
(3, 'no class tomorrow', 'bagyo', 'System Administrator', NULL, '2026-03-26 15:14:49', '2026-03-26 15:14:49');

-- --------------------------------------------------------

--
-- Table structure for table `sit_in_requests`
--

DROP TABLE IF EXISTS `sit_in_requests`;
CREATE TABLE `sit_in_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `session_time` time NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `sit_lab` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sit_in_sessions`
--

DROP TABLE IF EXISTS `sit_in_sessions`;
CREATE TABLE `sit_in_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `session_time` time NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `sit_lab` varchar(50) DEFAULT '524',
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sit_in_sessions`
--

INSERT INTO `sit_in_sessions` (`id`, `user_id`, `session_date`, `session_time`, `purpose`, `sit_lab`, `status`, `created_at`) VALUES
(1, 3, '2026-03-26', '16:06:29', 'Java Programming', '527', 'active', '2026-03-26 15:06:29'),
(2, 3, '2026-03-26', '16:08:09', 'Research', '525', 'active', '2026-03-26 15:08:09'),
(3, 3, '2026-03-26', '16:14:20', 'Java Programming', '525', 'active', '2026-03-26 15:14:20'),
(4, 5, '2026-03-26', '16:21:08', 'Group Study', '527', 'active', '2026-03-26 15:21:08'),
(5, 6, '2026-03-26', '17:05:24', 'Web Development', '526', 'active', '2026-03-26 16:05:24'),
(6, 6, '2026-03-26', '17:19:49', 'C Programming', '525', 'active', '2026-03-26 16:19:49'),
(7, 5, '2026-03-26', '17:22:02', 'Java Programming', '525', 'active', '2026-03-26 16:22:02');

-- --------------------------------------------------------

--
-- Table structure for table `sit_labs`
--

DROP TABLE IF EXISTS `sit_labs`;
CREATE TABLE `sit_labs` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT 30,
  `status` varchar(20) DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sit_labs`
--

INSERT INTO `sit_labs` (`id`, `lab_name`, `capacity`, `status`, `created_at`) VALUES
(1, 'Lab A', 30, 'available', '2026-03-26 12:42:51'),
(2, 'Lab B', 30, 'available', '2026-03-26 12:42:51'),
(3, 'Lab C', 25, 'available', '2026-03-26 12:42:51'),
(4, 'Lab D', 25, 'available', '2026-03-26 12:42:51');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `course_level` int(11) NOT NULL,
  `course` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `session_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_picture` varchar(255) DEFAULT 'default-avatar.png',
  `role` varchar(20) DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `last_name`, `first_name`, `middle_name`, `course_level`, `course`, `password`, `email`, `address`, `session_count`, `created_at`, `updated_at`, `profile_picture`, `role`) VALUES
(3, '1001', 'necesario', 'klient', '', 3, 'BSIT', '$2y$10$jGasQ5Lh6Ng0IeVNLsS.luSqGl4y6cTbKAQGXf.EK2wQ58c1dk77q', 'jamal@gmail.com', 'cebu city', 0, '2026-03-25 16:03:14', '2026-03-26 15:16:36', '3_1774538196.png', 'student'),
(4, 'ADMIN001', 'Admin', 'System', '', 0, 'ADMIN', '$2y$10$YourHashHere', 'admin@ccs.edu.ph', 'Admin Office', 0, '2026-03-26 12:19:34', '2026-03-26 12:19:34', 'default-avatar.png', 'admin'),
(5, '1003', 'jupeta', 'mami', '', 4, 'BSCS', '$2y$10$DX0q8rIboS5Yg6E/hse2a.zbOKe/LFlyhHcPWiwpttwTEbm8oHp5m', 'mami@gmail.com', 'mars', 3, '2026-03-26 15:18:17', '2026-03-26 16:22:02', '5_1774538364.jpg', 'student'),
(6, '1004', 'james', 'lebron', '', 4, 'BSCS', '$2y$10$b2FHvgDiof3a3qZB.laK0OwxktXYl6pRFmw5YJG/en3hPgOykntEO', 'lebron@gmail.com', 'lebwrong', 0, '2026-03-26 16:01:17', '2026-03-26 16:19:49', '6_1774540919.jpeg', 'student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sit_in_requests`
--
ALTER TABLE `sit_in_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sit_in_sessions`
--
ALTER TABLE `sit_in_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sit_labs`
--
ALTER TABLE `sit_labs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sit_in_requests`
--
ALTER TABLE `sit_in_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sit_in_sessions`
--
ALTER TABLE `sit_in_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sit_labs`
--
ALTER TABLE `sit_labs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sit_in_requests`
--
ALTER TABLE `sit_in_requests`
  ADD CONSTRAINT `sit_in_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sit_in_sessions`
--
ALTER TABLE `sit_in_sessions`
  ADD CONSTRAINT `sit_in_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
