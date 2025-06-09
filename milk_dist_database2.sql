-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2025 at 01:50 PM
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
-- Database: `milk_dist_database2`
--

-- --------------------------------------------------------

--
-- Table structure for table `seller_month_revenue`
--

CREATE TABLE `seller_month_revenue` (
  `Smr_id` int(11) NOT NULL,
  `Seller_id` int(11) DEFAULT NULL,
  `Month` varchar(20) DEFAULT NULL,
  `Total_liter` decimal(10,2) DEFAULT NULL,
  `Total_price` decimal(10,2) DEFAULT NULL,
  `Payment_status` varchar(50) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_payment`
--

CREATE TABLE `seller_payment` (
  `S_payment_id` int(11) NOT NULL,
  `Seller_id` int(11) NOT NULL,
  `Customer_id` int(11) DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `Amount_collected` decimal(10,2) DEFAULT NULL,
  `Payment_status` varchar(50) DEFAULT NULL,
  `Payment_date` datetime DEFAULT NULL,
  `Method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_payment`
--

INSERT INTO `seller_payment` (`S_payment_id`, `Seller_id`, `Customer_id`, `Date`, `Amount_collected`, `Payment_status`, `Payment_date`, `Method`) VALUES
(1, 7, 9, '2025-06-08 00:00:00', 1208.00, 'Paid', '2025-06-08 00:00:00', 'Cash'),
(2, 7, 11, '2025-06-07 00:00:00', 250.00, 'Paid', '2025-06-07 00:00:00', 'Cash'),
(3, 7, 8, '2025-05-15 00:00:00', 3000.00, 'Paid', '2025-05-15 00:00:00', 'Cash'),
(4, 0, 9, '2025-07-09 00:00:00', 3000.00, 'Paid', '2025-05-15 00:00:00', 'UPI'),
(5, 0, 10, '2025-06-07 00:00:00', 1200.00, 'Pending', '2025-06-07 18:04:14', 'UPI');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_address`
--

CREATE TABLE `tbl_address` (
  `Address_id` int(11) NOT NULL,
  `Address` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_address`
--

INSERT INTO `tbl_address` (`Address_id`, `Address`) VALUES
(1, 'kadi'),
(2, 'Kadi2'),
(3, 'Dadu'),
(4, 'Kalol'),
(5, 'Babajiup'),
(6, 'Dadu'),
(7, 'Kdsksk');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_admin`
--

CREATE TABLE `tbl_admin` (
  `Admin_id` int(11) NOT NULL,
  `Contact` int(10) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_admin`
--

INSERT INTO `tbl_admin` (`Admin_id`, `Contact`, `Password`) VALUES
(1, 1234567890, '$2y$10$b7Q/IRhL8z3RJUIpnyfVC.l9puapShguza0bf0SBnTXlbzILKNo6W');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer`
--

CREATE TABLE `tbl_customer` (
  `Customer_id` int(11) NOT NULL,
  `Name` varchar(40) DEFAULT NULL,
  `Contact` bigint(11) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Address_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_customer`
--

INSERT INTO `tbl_customer` (`Customer_id`, `Name`, `Contact`, `Password`, `Price`, `Date`, `Address_id`) VALUES
(8, 'Sanjay', 6356753673, '$2y$10$JJaPLET1rtPIwtwwWqkUr.JX3UfkZP8A1MoEGesqaOWTGjCXz4IlO', 66.00, '2025-05-04', 1),
(9, 'Sanjay', 1234567890, '$2y$10$HzUFL35KyySd3t8Q0Zp0LuueR/wscsB5vbNy8EnOL19Z4324E/1cO', 10.00, '2025-05-09', 2),
(10, 'India', 1122334455, '$2y$10$/Kqnl0dRDj0VaIBhG11bGedv9U4KjYDEgB.1BCM7Jxheou/QBa7ge', 33.00, '2025-05-16', 1),
(11, 'Dadubhai', 1231231231, '$2y$10$kXJkJCAtfE5a7YOBGeRZA.1RjDQREO0WGbZd5c4Vcb4dxaKvuzfWS', 66.00, '2025-05-18', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_milk_assignment`
--

CREATE TABLE `tbl_milk_assignment` (
  `Assignment_id` int(11) NOT NULL,
  `Seller_id` int(11) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Assigned_quantity` decimal(10,2) DEFAULT NULL,
  `Remaining_quantity` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_milk_assignment`
--

INSERT INTO `tbl_milk_assignment` (`Assignment_id`, `Seller_id`, `Date`, `Assigned_quantity`, `Remaining_quantity`) VALUES
(11, 7, '2025-05-16', 100.00, 100.00),
(12, 7, '2025-05-18', 100.00, 85.00),
(13, 8, '2025-05-04', 100.00, 100.00),
(14, 8, '2025-06-07', 100.00, 100.00),
(15, 7, '2025-06-07', 200.00, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_milk_delivery`
--

CREATE TABLE `tbl_milk_delivery` (
  `Delivery_id` int(11) NOT NULL,
  `Seller_id` int(11) DEFAULT NULL,
  `Customer_id` int(11) DEFAULT NULL,
  `DateTime` datetime DEFAULT NULL,
  `Quantity` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_milk_delivery`
--

INSERT INTO `tbl_milk_delivery` (`Delivery_id`, `Seller_id`, `Customer_id`, `DateTime`, `Quantity`) VALUES
(15, 7, 8, '2025-05-18 12:17:52', 10.00),
(16, 7, 10, '2025-05-18 18:01:20', 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_seller`
--

CREATE TABLE `tbl_seller` (
  `Seller_id` int(11) NOT NULL,
  `Name` varchar(30) DEFAULT NULL,
  `Contact` bigint(10) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `Vehicle_no` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_seller`
--

INSERT INTO `tbl_seller` (`Seller_id`, `Name`, `Contact`, `Password`, `Vehicle_no`) VALUES
(7, 'Sanjay', 1234567890, '$2y$10$hLfORxbK4wnpURo8wVyeJeMZsBckbthjSJSUHUqU.KdKEhx.JCyZ6', 'Gj01'),
(8, 'Khilil', 7990584749, '$2y$10$jcBwacbTAetPUtBnWJBYD.CnJgRidIh8nDnfWwYwx2rnT4oBahgb6', 'Gj02xl6705'),
(9, 'Riahjdjs', 1234564888, '$2y$10$Lj67I1AMYlENrH/Pir4XY.idRS.7.06dIGUTIDaAvq6O2d0K6Q7QG', 'Gj-18');

-- --------------------------------------------------------

--
-- Table structure for table `user_month_report`
--

CREATE TABLE `user_month_report` (
  `User_report_id` int(11) NOT NULL,
  `Customer_id` int(11) DEFAULT NULL,
  `Month` varchar(20) DEFAULT NULL,
  `Total_liter` decimal(10,2) DEFAULT NULL,
  `Total_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `seller_month_revenue`
--
ALTER TABLE `seller_month_revenue`
  ADD PRIMARY KEY (`Smr_id`),
  ADD KEY `Seller_id` (`Seller_id`);

--
-- Indexes for table `seller_payment`
--
ALTER TABLE `seller_payment`
  ADD PRIMARY KEY (`S_payment_id`),
  ADD KEY `seller_payment_ibfk_1` (`Customer_id`);

--
-- Indexes for table `tbl_address`
--
ALTER TABLE `tbl_address`
  ADD PRIMARY KEY (`Address_id`);

--
-- Indexes for table `tbl_admin`
--
ALTER TABLE `tbl_admin`
  ADD PRIMARY KEY (`Admin_id`);

--
-- Indexes for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  ADD PRIMARY KEY (`Customer_id`),
  ADD KEY `Address_id` (`Address_id`);

--
-- Indexes for table `tbl_milk_assignment`
--
ALTER TABLE `tbl_milk_assignment`
  ADD PRIMARY KEY (`Assignment_id`),
  ADD KEY `Seller_id` (`Seller_id`);

--
-- Indexes for table `tbl_milk_delivery`
--
ALTER TABLE `tbl_milk_delivery`
  ADD PRIMARY KEY (`Delivery_id`),
  ADD KEY `Seller_id` (`Seller_id`),
  ADD KEY `Customer_id` (`Customer_id`);

--
-- Indexes for table `tbl_seller`
--
ALTER TABLE `tbl_seller`
  ADD PRIMARY KEY (`Seller_id`);

--
-- Indexes for table `user_month_report`
--
ALTER TABLE `user_month_report`
  ADD PRIMARY KEY (`User_report_id`),
  ADD KEY `Customer_id` (`Customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `seller_month_revenue`
--
ALTER TABLE `seller_month_revenue`
  MODIFY `Smr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_payment`
--
ALTER TABLE `seller_payment`
  MODIFY `S_payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_address`
--
ALTER TABLE `tbl_address`
  MODIFY `Address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_admin`
--
ALTER TABLE `tbl_admin`
  MODIFY `Admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  MODIFY `Customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_milk_assignment`
--
ALTER TABLE `tbl_milk_assignment`
  MODIFY `Assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tbl_milk_delivery`
--
ALTER TABLE `tbl_milk_delivery`
  MODIFY `Delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tbl_seller`
--
ALTER TABLE `tbl_seller`
  MODIFY `Seller_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_month_report`
--
ALTER TABLE `user_month_report`
  MODIFY `User_report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `seller_month_revenue`
--
ALTER TABLE `seller_month_revenue`
  ADD CONSTRAINT `seller_month_revenue_ibfk_1` FOREIGN KEY (`Seller_id`) REFERENCES `tbl_seller` (`Seller_id`);

--
-- Constraints for table `seller_payment`
--
ALTER TABLE `seller_payment`
  ADD CONSTRAINT `seller_payment_ibfk_1` FOREIGN KEY (`Customer_id`) REFERENCES `tbl_customer` (`Customer_id`);

--
-- Constraints for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  ADD CONSTRAINT `tbl_customer_ibfk_1` FOREIGN KEY (`Address_id`) REFERENCES `tbl_address` (`Address_id`) ON DELETE SET NULL;

--
-- Constraints for table `tbl_milk_assignment`
--
ALTER TABLE `tbl_milk_assignment`
  ADD CONSTRAINT `tbl_milk_assignment_ibfk_1` FOREIGN KEY (`Seller_id`) REFERENCES `tbl_seller` (`Seller_id`);

--
-- Constraints for table `tbl_milk_delivery`
--
ALTER TABLE `tbl_milk_delivery`
  ADD CONSTRAINT `tbl_milk_delivery_ibfk_1` FOREIGN KEY (`Seller_id`) REFERENCES `tbl_seller` (`Seller_id`),
  ADD CONSTRAINT `tbl_milk_delivery_ibfk_2` FOREIGN KEY (`Customer_id`) REFERENCES `tbl_customer` (`Customer_id`);

--
-- Constraints for table `user_month_report`
--
ALTER TABLE `user_month_report`
  ADD CONSTRAINT `user_month_report_ibfk_1` FOREIGN KEY (`Customer_id`) REFERENCES `tbl_customer` (`Customer_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;