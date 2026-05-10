-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 08, 2026 at 09:41 AM
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
-- Database: `tournoidia`
--

-- --------------------------------------------------------

--
-- Table structure for table `areas`
--

CREATE TABLE `areas` (
  `ID` bigint(20) NOT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `Banner` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `ID` int(11) NOT NULL,
  `Page` varchar(50) NOT NULL COMMENT 'Mã trang (vd: home, about, contact, tours)',
  `Image` varchar(255) NOT NULL COMMENT 'Đường dẫn file ảnh',
  `Title` varchar(255) DEFAULT NULL COMMENT 'Dòng chữ chính trên banner (nếu có)',
  `Subtitle` varchar(255) DEFAULT NULL COMMENT 'Dòng chữ phụ trên banner (nếu có)',
  `Link` varchar(255) DEFAULT NULL COMMENT 'Link khi khách click vào banner',
  `DisplayOrder` int(11) DEFAULT 0 COMMENT 'Thứ tự hiển thị (nếu làm slider có nhiều ảnh)',
  `Status` tinyint(1) DEFAULT 1 COMMENT '1: Đang hiển thị, 0: Đang ẩn',
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `ID` bigint(20) NOT NULL,
  `BookingCode` varchar(20) DEFAULT NULL,
  `TourID` bigint(20) DEFAULT NULL,
  `InsuranceID` int(11) DEFAULT NULL,
  `UserID` bigint(20) DEFAULT NULL,
  `CustomerName` varchar(100) DEFAULT NULL,
  `CustomerEmail` varchar(100) DEFAULT NULL,
  `CustomerPhone` varchar(20) DEFAULT NULL,
  `Slot` int(11) DEFAULT NULL,
  `SlotKid` int(11) DEFAULT NULL,
  `TotalPrice` int(11) DEFAULT 0,
  `InsuranceFee` int(11) DEFAULT 0,
  `VoucherCode` varchar(50) DEFAULT NULL,
  `DiscountAmount` int(11) DEFAULT 0,
  `FinalPrice` int(11) DEFAULT 0,
  `PaymentMethod` enum('cod','vnpay') DEFAULT 'cod',
  `PaymentStatus` enum('pending','paid','failed') DEFAULT 'pending',
  `TransactionNo` varchar(100) DEFAULT NULL,
  `Status` varchar(255) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `VehicleID` int(11) DEFAULT NULL,
  `ShuttleService` varchar(255) DEFAULT NULL,
  `TimeStartStore` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `ID` bigint(20) NOT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `AreaID` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `ID` bigint(20) NOT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Phone` varchar(255) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `Content` varchar(255) DEFAULT NULL,
  `Subject` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `ID` bigint(20) NOT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `CityID` bigint(20) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Stars` text DEFAULT NULL,
  `Price` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_packages`
--

CREATE TABLE `insurance_packages` (
  `ID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `PricePerPerson` int(11) NOT NULL,
  `Status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) NOT NULL,
  `sender_id` varchar(50) DEFAULT NULL,
  `receiver_id` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0 COMMENT '0: chưa đọc, 1: đã đọc',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `ID` bigint(20) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Slug` varchar(255) NOT NULL,
  `Thumbnail` varchar(255) DEFAULT NULL,
  `Content` longtext NOT NULL,
  `AuthorID` bigint(20) DEFAULT NULL,
  `Views` int(11) DEFAULT 0,
  `Status` tinyint(1) DEFAULT 1,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tours`
--

CREATE TABLE `tours` (
  `ID` bigint(20) NOT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `CityID` bigint(20) DEFAULT NULL,
  `Content` longtext DEFAULT NULL,
  `Price` int(11) DEFAULT NULL,
  `Options` longtext DEFAULT NULL,
  `Shuttle` tinyint(1) DEFAULT NULL,
  `TimeStart` longtext DEFAULT NULL,
  `Status` tinyint(1) DEFAULT NULL,
  `TotalSeats` int(11) NOT NULL DEFAULT 40,
  `BookedSeats` int(11) NOT NULL DEFAULT 0,
  `Slot` int(11) DEFAULT NULL,
  `Banner` varchar(255) DEFAULT NULL,
  `PriceKid` int(11) DEFAULT NULL,
  `Description` longtext DEFAULT NULL,
  `Duration` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tour_hotel`
--

CREATE TABLE `tour_hotel` (
  `TourID` bigint(20) NOT NULL,
  `HotelID` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `ID` bigint(20) NOT NULL,
  `Username` varchar(255) DEFAULT NULL,
  `Role` varchar(255) DEFAULT NULL,
  `FirstName` varchar(255) DEFAULT NULL,
  `LastName` varchar(255) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Phone` varchar(255) DEFAULT NULL,
  `DOB` date DEFAULT NULL,
  `Status` varchar(255) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `TotalSpent` bigint(20) NOT NULL DEFAULT 0,
  `CustomerTier` enum('Bronze','Silver','Gold','Platinum') NOT NULL DEFAULT 'Bronze'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `ID` bigint(20) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Seats` int(11) NOT NULL,
  `CityID` bigint(20) DEFAULT NULL,
  `PricePerDay` int(11) NOT NULL,
  `HasDriver` tinyint(1) DEFAULT 0,
  `Image` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `ID` int(11) NOT NULL,
  `Code` varchar(50) NOT NULL,
  `DiscountType` enum('percent','amount') NOT NULL,
  `DiscountValue` int(11) NOT NULL,
  `MinOrderValue` int(11) DEFAULT 0,
  `Quantity` int(11) NOT NULL DEFAULT 100,
  `Used` int(11) NOT NULL DEFAULT 0,
  `ExpiryDate` datetime NOT NULL,
  `Status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `Page` (`Page`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `TourID` (`TourID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `AreaID` (`AreaID`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `CityID` (`CityID`);

--
-- Indexes for table `insurance_packages`
--
ALTER TABLE `insurance_packages`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Slug` (`Slug`),
  ADD KEY `fk_post_author` (`AuthorID`);

--
-- Indexes for table `tours`
--
ALTER TABLE `tours`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `CityID` (`CityID`);
ALTER TABLE `tours` ADD FULLTEXT KEY `Description` (`Description`);

--
-- Indexes for table `tour_hotel`
--
ALTER TABLE `tour_hotel`
  ADD PRIMARY KEY (`TourID`,`HotelID`),
  ADD KEY `HotelID` (`HotelID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Phone` (`Phone`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_vehicle_city` (`CityID`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Code` (`Code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `insurance_packages`
--
ALTER TABLE `insurance_packages`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tours`
--
ALTER TABLE `tours`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`TourID`) REFERENCES `tours` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`AreaID`) REFERENCES `areas` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `hotels`
--
ALTER TABLE `hotels`
  ADD CONSTRAINT `hotels_ibfk_1` FOREIGN KEY (`CityID`) REFERENCES `cities` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_post_author` FOREIGN KEY (`AuthorID`) REFERENCES `users` (`ID`) ON DELETE SET NULL;

--
-- Constraints for table `tours`
--
ALTER TABLE `tours`
  ADD CONSTRAINT `tours_ibfk_1` FOREIGN KEY (`CityID`) REFERENCES `cities` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `tour_hotel`
--
ALTER TABLE `tour_hotel`
  ADD CONSTRAINT `tour_hotel_ibfk_1` FOREIGN KEY (`TourID`) REFERENCES `tours` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `tour_hotel_ibfk_2` FOREIGN KEY (`HotelID`) REFERENCES `hotels` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `fk_vehicle_city` FOREIGN KEY (`CityID`) REFERENCES `cities` (`ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
