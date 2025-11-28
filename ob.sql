-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 23, 2025 at 11:01 PM
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
-- Database: `ob`
--

-- --------------------------------------------------------

--
-- Table structure for table `charge_sheets`
--

CREATE TABLE `charge_sheets` (
  `id` int(11) NOT NULL,
  `incident_id` varchar(50) NOT NULL,
  `charge_sheet_number` varchar(100) NOT NULL,
  `date_issued` date NOT NULL,
  `issuing_officer` varchar(255) NOT NULL,
  `suspect_name` varchar(255) NOT NULL,
  `suspect_address` text NOT NULL,
  `suspect_id_number` varchar(100) NOT NULL,
  `suspect_phone` varchar(20) DEFAULT NULL,
  `charges` text NOT NULL,
  `facts_of_case` text NOT NULL,
  `evidence` text DEFAULT NULL,
  `witnesses` text DEFAULT NULL,
  `bail_conditions` text DEFAULT NULL,
  `court_date` date DEFAULT NULL,
  `court_name` varchar(255) DEFAULT NULL,
  `status` enum('pending','filed','hearing','closed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `charge_sheets`
--

INSERT INTO `charge_sheets` (`id`, `incident_id`, `charge_sheet_number`, `date_issued`, `issuing_officer`, `suspect_name`, `suspect_address`, `suspect_id_number`, `suspect_phone`, `charges`, `facts_of_case`, `evidence`, `witnesses`, `bail_conditions`, `court_date`, `court_name`, `status`, `created_at`, `updated_at`) VALUES
(1, '3', '01', '2025-11-24', 'Cpl ommolo', 'Leiyan', '889 nrb', '42165678', '0701673126', 'drunk driving', 'Guilty', 'kjhgytfrertyuiop\r\nuytrtuikol', 'jio\r\nuytgrftyu\r\nihgythj', 'Flight risk', '2025-11-26', 'kibra', 'filed', '2025-11-23 21:25:51', '2025-11-23 21:15:51');

-- --------------------------------------------------------

--
-- Table structure for table `charge_sheet_items`
--

CREATE TABLE `charge_sheet_items` (
  `id` int(11) NOT NULL,
  `charge_sheet_id` int(11) NOT NULL,
  `charge_description` text NOT NULL,
  `law_section` varchar(100) DEFAULT NULL,
  `penalty` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `charge_sheet_witnesses`
--

CREATE TABLE `charge_sheet_witnesses` (
  `id` int(11) NOT NULL,
  `charge_sheet_id` int(11) NOT NULL,
  `witness_name` varchar(100) NOT NULL,
  `witness_address` text DEFAULT NULL,
  `witness_phone` varchar(20) DEFAULT NULL,
  `witness_statement` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(11) NOT NULL,
  `incident_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `incident_date` datetime NOT NULL,
  `reporter_name` varchar(100) NOT NULL,
  `reporter_contact` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('reported','in_progress','resolved') DEFAULT 'reported',
  `image_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incidents`
--

INSERT INTO `incidents` (`id`, `incident_type`, `description`, `location_name`, `latitude`, `longitude`, `incident_date`, `reporter_name`, `reporter_contact`, `user_id`, `status`, `image_path`, `created_at`, `updated_at`) VALUES
(1, 'Theft', 'Stolen laptop from office', 'Main Office Building', 40.71280000, -74.00600000, '2023-10-15 14:30:00', 'John Smith', 'john@company.com', NULL, 'reported', NULL, '2025-11-23 07:39:15', '2025-11-23 07:39:15'),
(2, 'Accident', 'Slip and fall in hallway', 'Building A - 2nd Floor', 40.75890000, -73.98510000, '2023-10-16 09:15:00', 'Sarah Johnson', 'sarah@company.com', NULL, 'in_progress', NULL, '2025-11-23 07:39:15', '2025-11-23 07:39:15'),
(3, 'Accident', 'njm,kl.;', 'oikj', -1.32030680, 36.82840616, '2025-11-23 10:24:00', '[poiuh', '0701673126', NULL, 'reported', 'uploads/6922e18b0eb4a.jpg', '2025-11-23 10:27:23', '2025-11-23 10:27:23');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `police_stations`
--

CREATE TABLE `police_stations` (
  `id` int(11) NOT NULL,
  `station_name` varchar(255) NOT NULL,
  `station_code` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `district` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `officer_in_charge` varchar(255) DEFAULT NULL,
  `jurisdiction_radius` decimal(8,2) DEFAULT 5.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `police_stations`
--

INSERT INTO `police_stations` (`id`, `station_name`, `station_code`, `address`, `city`, `district`, `latitude`, `longitude`, `contact_number`, `email`, `officer_in_charge`, `jurisdiction_radius`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Kasarani police', 'PS9876', '614', 'NAIROBI', 'LKJH', -1.27801000, 36.84051400, '0721565786', 'osingo213@gmail.com', 'JARIUS', 5.00, 1, '2025-11-23 12:14:03', '2025-11-23 12:14:03'),
(2, 'Central Police', 'PS98769', '6145', 'nai', 'central', -1.27951000, 36.81870900, '0883893', 'osingo3213@gmail.com', 'JARIUS M', 5.00, 1, '2025-11-23 12:42:26', '2025-11-23 12:42:26');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('admin','user') DEFAULT 'user',
  `full_name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `user_type`, `full_name`, `department`, `phone`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@occurrencebook.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 'IT', '+1234567890', 1, '2025-11-23 07:56:38', '2025-11-23 07:56:38'),
(2, 'debra', 'osingo213@gmail.com', '$2y$10$nCDnHOykxMeC1ujwj0BNGeCSFCGyOQD1hshu40zvIGdZb96r/VD4y', 'admin', 'Osingo Stephen Ogaga', 'nun', '0721565786', 1, '2025-11-23 08:09:15', '2025-11-23 12:37:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `charge_sheets`
--
ALTER TABLE `charge_sheets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `charge_sheet_number` (`charge_sheet_number`);

--
-- Indexes for table `charge_sheet_items`
--
ALTER TABLE `charge_sheet_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `charge_sheet_id` (`charge_sheet_id`);

--
-- Indexes for table `charge_sheet_witnesses`
--
ALTER TABLE `charge_sheet_witnesses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `charge_sheet_id` (`charge_sheet_id`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `police_stations`
--
ALTER TABLE `police_stations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `station_code` (`station_code`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `charge_sheets`
--
ALTER TABLE `charge_sheets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `charge_sheet_items`
--
ALTER TABLE `charge_sheet_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `charge_sheet_witnesses`
--
ALTER TABLE `charge_sheet_witnesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `police_stations`
--
ALTER TABLE `police_stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `charge_sheet_items`
--
ALTER TABLE `charge_sheet_items`
  ADD CONSTRAINT `charge_sheet_items_ibfk_1` FOREIGN KEY (`charge_sheet_id`) REFERENCES `charge_sheets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `charge_sheet_witnesses`
--
ALTER TABLE `charge_sheet_witnesses`
  ADD CONSTRAINT `charge_sheet_witnesses_ibfk_1` FOREIGN KEY (`charge_sheet_id`) REFERENCES `charge_sheets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `incidents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
