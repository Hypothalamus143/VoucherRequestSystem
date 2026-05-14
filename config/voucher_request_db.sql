-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 14, 2026 at 05:51 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `voucher_request_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `Reply`
--

CREATE TABLE `Reply` (
  `replyID` int(10) UNSIGNED NOT NULL,
  `parentID` int(10) UNSIGNED NOT NULL,
  `userID` int(10) UNSIGNED NOT NULL,
  `datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `message` text DEFAULT NULL,
  `isFromRequest` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Reply`
--

INSERT INTO `Reply` (`replyID`, `parentID`, `userID`, `datetime`, `message`, `isFromRequest`) VALUES
(2, 1, 1, '2026-05-10 12:09:13', '0', 0),
(3, 2, 1, '2026-05-10 12:09:51', '0', 0),
(6, 5, 1, '2026-05-10 12:10:30', '0', 0),
(7, 6, 1, '2026-05-10 12:10:38', '0', 0),
(9, 8, 1, '2026-05-10 12:17:56', '0', 0),
(10, 9, 1, '2026-05-10 12:18:01', '0', 0),
(11, 1, 1, '2026-05-10 12:19:08', '0', 1),
(12, 2, 2, '2026-05-11 12:48:33', '0', 1),
(13, 12, 2, '2026-05-11 12:48:42', '0', 0),
(14, 3, 3, '2026-05-11 13:01:32', '0', 1),
(15, 14, 3, '2026-05-11 13:01:44', '0', 0),
(16, 4, 3, '2026-05-14 11:43:21', '0', 1),
(17, 16, 3, '2026-05-14 11:43:32', '0', 0),
(18, 13, 2, '2026-05-14 11:43:57', '0', 0);

-- --------------------------------------------------------

--
-- Table structure for table `Request`
--

CREATE TABLE `Request` (
  `requestID` int(10) UNSIGNED NOT NULL,
  `studID` int(10) UNSIGNED NOT NULL,
  `datetime` datetime NOT NULL DEFAULT current_timestamp(),
  `message` text DEFAULT NULL,
  `isAccomplished` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Request`
--

INSERT INTO `Request` (`requestID`, `studID`, `datetime`, `message`, `isAccomplished`) VALUES
(1, 24, '2026-05-10 12:05:06', 'please help', 0),
(2, 241, '2026-05-11 12:47:45', 'give me voucher', 0),
(3, 244, '2026-05-11 13:00:49', 'give me voucher', 0),
(4, 244, '2026-05-14 11:43:14', 'reply to me', 0);

-- --------------------------------------------------------

--
-- Table structure for table `Student`
--

CREATE TABLE `Student` (
  `studID` int(10) UNSIGNED NOT NULL,
  `userID` int(10) UNSIGNED NOT NULL,
  `yearLevel` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Student`
--

INSERT INTO `Student` (`studID`, `userID`, `yearLevel`) VALUES
(24, 1, 1),
(241, 2, 1),
(244, 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `TSG`
--

CREATE TABLE `TSG` (
  `empID` int(10) UNSIGNED NOT NULL,
  `userID` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `User`
--

CREATE TABLE `User` (
  `userID` int(10) UNSIGNED NOT NULL,
  `fname` varchar(100) NOT NULL,
  `mname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `userType` char(1) NOT NULL CHECK (`userType` in ('S','T')),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `User`
--

INSERT INTO `User` (`userID`, `fname`, `mname`, `lname`, `password`, `userType`, `created_at`) VALUES
(1, 'john', 'john', 'john', '$2y$12$D.Ur2IZhFXWMLHhBiEwtOu6DGbvtx/nSuYtL94.T8WN7u9ANWQ3p.', 'S', '2026-05-10 12:02:31'),
(2, 'john', 'john', 'john', '$2y$12$UkJ823Gq9P0jdPMUP9DvceOY6vA00ckz7yrLOOvhl/zuVEDlLLvc2', 'S', '2026-05-11 12:47:29'),
(3, 'john', 'john', 'john', '$2y$12$qV5vRnOnAPDJU9KVdxGQLOJZ/1fE9Gr7.4mn01e6xqmJ2tEK4J5a6', 'S', '2026-05-11 12:59:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Reply`
--
ALTER TABLE `Reply`
  ADD PRIMARY KEY (`replyID`),
  ADD KEY `idx_reply_parentID` (`parentID`),
  ADD KEY `idx_reply_userID` (`userID`);

--
-- Indexes for table `Request`
--
ALTER TABLE `Request`
  ADD PRIMARY KEY (`requestID`),
  ADD KEY `idx_request_studID` (`studID`),
  ADD KEY `idx_request_accomplished` (`isAccomplished`);

--
-- Indexes for table `Student`
--
ALTER TABLE `Student`
  ADD PRIMARY KEY (`studID`),
  ADD UNIQUE KEY `userID` (`userID`);

--
-- Indexes for table `TSG`
--
ALTER TABLE `TSG`
  ADD PRIMARY KEY (`empID`),
  ADD UNIQUE KEY `userID` (`userID`);

--
-- Indexes for table `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Reply`
--
ALTER TABLE `Reply`
  MODIFY `replyID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `Request`
--
ALTER TABLE `Request`
  MODIFY `requestID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `User`
--
ALTER TABLE `User`
  MODIFY `userID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Reply`
--
ALTER TABLE `Reply`
  ADD CONSTRAINT `Reply_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `User` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `Request`
--
ALTER TABLE `Request`
  ADD CONSTRAINT `Request_ibfk_1` FOREIGN KEY (`studID`) REFERENCES `Student` (`studID`) ON DELETE CASCADE;

--
-- Constraints for table `Student`
--
ALTER TABLE `Student`
  ADD CONSTRAINT `Student_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `User` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `TSG`
--
ALTER TABLE `TSG`
  ADD CONSTRAINT `TSG_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `User` (`userID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
