-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2024 at 01:49 AM
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
-- Database: `moneytracker1`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `created_at`) VALUES
(1, 'Hazel Joyce Cruiz', 'hjoycecruiz@gmail.com', '$2y$10$.qkQ.4Uhx3SYxlhMotzcQe6LlIt.c3oRhp1YW3mZjb.OJh5Y3t5g6', '2024-12-16 16:04:19'),
(4, 'Charlene Catalino', 'charlenecatalino5@gmail.com', '$2y$10$eIt4vUzr32xriV7Hb7wEs.gWl4yAafdiDrbTQHdhT6nXYhJHom7P2', '2024-12-17 21:39:38');

-- --------------------------------------------------------

--
-- Table structure for table `user_dashboard_data`
--

CREATE TABLE `user_dashboard_data` (
  `data_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_dashboard_summary`
--

CREATE TABLE `user_dashboard_summary` (
  `summary_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `today_income` decimal(10,2) DEFAULT 0.00,
  `today_spending` decimal(10,2) DEFAULT 0.00,
  `today_savings` decimal(10,2) DEFAULT 0.00,
  `weekly_income` decimal(10,2) DEFAULT 0.00,
  `weekly_spending` decimal(10,2) DEFAULT 0.00,
  `weekly_savings` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_expenses`
--

CREATE TABLE `user_expenses` (
  `expense_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL
) ;

--
-- Dumping data for table `user_expenses`
--

INSERT INTO `user_expenses` (`expense_id`, `user_id`, `category`, `type`, `amount`, `date`) VALUES
(25, 1, 'Food', 'Chicken', 50.00, '2024-12-19'),
(26, 4, 'Education', 'College Fee', 500.00, '2024-12-18'),
(30, 4, 'Food', 'Water', 50.00, '2024-12-20'),
(31, 4, 'Health', 'dsg', 9000.00, '2024-12-26'),
(33, 4, 'Food', 'asdadas', 5000.00, '2024-12-02'),
(34, 4, 'Health', 'sahd', 1000.00, '2024-12-17');

-- --------------------------------------------------------

--
-- Table structure for table `user_expense_categories`
--

CREATE TABLE `user_expense_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_incomes`
--

CREATE TABLE `user_incomes` (
  `income_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('income','allowance') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_incomes`
--

INSERT INTO `user_incomes` (`income_id`, `user_id`, `type`, `amount`, `date`) VALUES
(19, 1, 'income', 500.00, '2024-12-20'),
(20, 4, 'allowance', 10000.00, '2024-12-19'),
(21, 1, 'allowance', 500.00, '2024-12-19'),
(30, 4, 'income', 50.00, '2024-12-20');

-- --------------------------------------------------------

--
-- Table structure for table `user_summary`
--

CREATE TABLE `user_summary` (
  `summary_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `total_income` decimal(10,2) DEFAULT 0.00,
  `total_spending` decimal(10,2) DEFAULT 0.00,
  `total_savings` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_transactions`
--

CREATE TABLE `user_transactions` (
  `transaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('add','edit','delete') NOT NULL,
  `table_name` varchar(255) NOT NULL,
  `record_id` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_dashboard_data`
--
ALTER TABLE `user_dashboard_data`
  ADD PRIMARY KEY (`data_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_dashboard_summary`
--
ALTER TABLE `user_dashboard_summary`
  ADD PRIMARY KEY (`summary_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_expenses`
--
ALTER TABLE `user_expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_expense_categories`
--
ALTER TABLE `user_expense_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `user_incomes`
--
ALTER TABLE `user_incomes`
  ADD PRIMARY KEY (`income_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_summary`
--
ALTER TABLE `user_summary`
  ADD PRIMARY KEY (`summary_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_transactions`
--
ALTER TABLE `user_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_dashboard_data`
--
ALTER TABLE `user_dashboard_data`
  MODIFY `data_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_dashboard_summary`
--
ALTER TABLE `user_dashboard_summary`
  MODIFY `summary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_expenses`
--
ALTER TABLE `user_expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_expense_categories`
--
ALTER TABLE `user_expense_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_incomes`
--
ALTER TABLE `user_incomes`
  MODIFY `income_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `user_summary`
--
ALTER TABLE `user_summary`
  MODIFY `summary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_transactions`
--
ALTER TABLE `user_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_dashboard_data`
--
ALTER TABLE `user_dashboard_data`
  ADD CONSTRAINT `user_dashboard_data_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_dashboard_summary`
--
ALTER TABLE `user_dashboard_summary`
  ADD CONSTRAINT `user_dashboard_summary_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_expenses`
--
ALTER TABLE `user_expenses`
  ADD CONSTRAINT `user_expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_incomes`
--
ALTER TABLE `user_incomes`
  ADD CONSTRAINT `user_incomes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_summary`
--
ALTER TABLE `user_summary`
  ADD CONSTRAINT `user_summary_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_transactions`
--
ALTER TABLE `user_transactions`
  ADD CONSTRAINT `user_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
