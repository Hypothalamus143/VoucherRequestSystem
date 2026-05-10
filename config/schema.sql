-- Voucher Request System Database Schema
-- Run this file to initialize the database

CREATE DATABASE IF NOT EXISTS voucher_request_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE voucher_request_db;

-- 1. User (base entity)
CREATE TABLE IF NOT EXISTS User (
    userID     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fname      VARCHAR(100) NOT NULL,
    mname      VARCHAR(100)          DEFAULT NULL,
    lname      VARCHAR(100) NOT NULL,
    password   VARCHAR(255) NOT NULL,          -- store bcrypt hash
    userType   CHAR(1)      NOT NULL CHECK (userType IN ('S', 'T')),
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Student (subtype of User)
CREATE TABLE IF NOT EXISTS Student (
    studID    INT UNSIGNED NOT NULL,           -- official CIT-U student ID
    userID    INT UNSIGNED NOT NULL UNIQUE,
    yearLevel TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (studID),
    FOREIGN KEY (userID) REFERENCES User(userID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. TSG (subtype of User)
CREATE TABLE IF NOT EXISTS TSG (
    empID  INT UNSIGNED NOT NULL,             -- official CIT-U employee ID
    userID INT UNSIGNED NOT NULL UNIQUE,
    PRIMARY KEY (empID),
    FOREIGN KEY (userID) REFERENCES User(userID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Request (submitted by a Student)
CREATE TABLE IF NOT EXISTS Request (
    requestID      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    studID         INT UNSIGNED NOT NULL,
    datetime       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    message        TEXT                  DEFAULT NULL,
    isAccomplished TINYINT(1)   NOT NULL DEFAULT 0,
    FOREIGN KEY (studID) REFERENCES Student(studID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Reply (thread-based; parent can be a Request OR another Reply)
CREATE TABLE IF NOT EXISTS Reply (
    replyID        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parentID       INT UNSIGNED NOT NULL,       -- ID of Request or Reply
    userID         INT UNSIGNED NOT NULL,       -- who wrote this reply
    datetime       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    message        TEXT                  DEFAULT NULL,
    isFromRequest  TINYINT(1)   NOT NULL DEFAULT 0,  -- 1 = parent is a Request, 0 = parent is a Reply
    FOREIGN KEY (userID) REFERENCES User(userID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Indexes for common queries
CREATE INDEX idx_request_studID        ON Request(studID);
CREATE INDEX idx_request_accomplished  ON Request(isAccomplished);
CREATE INDEX idx_reply_parentID        ON Reply(parentID);
CREATE INDEX idx_reply_userID          ON Reply(userID);
