-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 02, 2025 at 05:23 AM
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
-- Database: `tendergate`
--

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tenders`
--

CREATE TABLE `tenders` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status` enum('OPEN','CLOSED','AWARDED') NOT NULL DEFAULT 'OPEN',
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `fileUrl` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `postedBy` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenders`
--

INSERT INTO `tenders` (`id`, `title`, `description`, `status`, `startDate`, `endDate`, `fileUrl`, `category`, `createdAt`, `updatedAt`, `postedBy`) VALUES
(1, 'مسجد', 'بناء مسجد', 'OPEN', '2025-08-02', '2025-08-19', 'tender_1754103076.pdf', 'الإنشاءات', '2025-08-02 02:51:16', '2025-08-02 02:56:36', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tender_applications`
--

CREATE TABLE `tender_applications` (
  `id` int(11) NOT NULL,
  `tender_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `application_file` varchar(255) DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tender_applications`
--

INSERT INTO `tender_applications` (`id`, `tender_id`, `user_id`, `application_file`, `applied_at`) VALUES
(1, 1, 2, NULL, '2025-08-02 02:54:07'),
(2, 1, 1, NULL, '2025-08-02 02:57:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `isActive`, `createdAt`) VALUES
(1, 'saria', 's.s@s.s', '$2y$10$3iShGEluwP2HM9eDMsTKCOohLElH1ge5TybDdxZEcY51CXrNxqILG', 'admin', 1, '2025-08-02 02:47:52'),
(2, 'asd', 'asd@a.s', '$2y$10$QOt404AeFaaBPYG1E.75kuh1fm/BW97M9Rd1TIkCY84emSBljtSn2', 'user', 1, '2025-08-02 02:53:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tenders`
--
ALTER TABLE `tenders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `postedBy` (`postedBy`);

--
-- Indexes for table `tender_applications`
--
ALTER TABLE `tender_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tender_id` (`tender_id`),
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
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tenders`
--
ALTER TABLE `tenders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tender_applications`
--
ALTER TABLE `tender_applications`
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
-- Constraints for table `tenders`
--
ALTER TABLE `tenders`
  ADD CONSTRAINT `tenders_ibfk_1` FOREIGN KEY (`postedBy`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tender_applications`
--
ALTER TABLE `tender_applications`
  ADD CONSTRAINT `tender_applications_ibfk_1` FOREIGN KEY (`tender_id`) REFERENCES `tenders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tender_applications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
