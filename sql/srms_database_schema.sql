-- St. Raphaela Mary School Website Database Schema
-- MySQL/MariaDB Script

-- Drop database if it exists (comment this out in production)
DROP DATABASE IF EXISTS srms_database;

-- Create database with proper character set
CREATE DATABASE srms_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE srms_database;

-- ------------------------------------
-- Core System Tables
-- ------------------------------------

-- Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL, -- Stores hashed passwords
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `role` ENUM('admin', 'editor', 'content_manager') NOT NULL DEFAULT 'editor',
  `active` BOOLEAN DEFAULT TRUE,
  `last_login` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- School Information Table
CREATE TABLE `school_information` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `logo` VARCHAR(255), -- Path to logo file
  `mission` TEXT,
  `vision` TEXT,
  `philosophy` TEXT,
  `email` VARCHAR(100),
  `phone` VARCHAR(50),
  `address` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pages Table
CREATE TABLE `pages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `content` LONGTEXT,
  `meta_title` VARCHAR(100),
  `meta_description` VARCHAR(255),
  `status` ENUM('published', 'draft') DEFAULT 'draft',
  `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------
-- Navigation Structure
-- ------------------------------------

-- Navigation Menu Table
CREATE TABLE `navigation` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `parent_id` INT DEFAULT NULL,
  `display_order` INT DEFAULT 0,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`parent_id`) REFERENCES `navigation`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------
-- Faculty and Staff
-- ------------------------------------

-- Faculty Categories Table
CREATE TABLE `faculty_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Faculty Table
CREATE TABLE `faculty` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `category_id` INT NOT NULL,
  `photo` VARCHAR(255),
  `qualifications` VARCHAR(255),
  `bio` TEXT,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `faculty_categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------
-- News and Updates
-- ------------------------------------

-- News Articles Table
CREATE TABLE `news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `content` LONGTEXT NOT NULL,
  `summary` TEXT,
  `image` VARCHAR(255),
  `published_date` DATETIME NOT NULL,
  `author_id` INT,
  `status` ENUM('published', 'draft') DEFAULT 'draft',
  `featured` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------
-- Facilities and Campus
-- ------------------------------------

-- Facilities Table
CREATE TABLE `facilities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------
-- Academic Programs
-- ------------------------------------

-- Academic Levels Table
CREATE TABLE `academic_levels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academic Programs Table
CREATE TABLE `academic_programs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `level_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`level_id`) REFERENCES `academic_levels`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academic Tracks Table (for Senior High)
CREATE TABLE `academic_tracks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `program_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(20),
  `description` TEXT,
  `display_order` INT DEFAULT 0,
  FOREIGN KEY (`program_id`) REFERENCES `academic_programs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------
-- Admissions
-- ------------------------------------

-- Admission Policies Table
CREATE TABLE `admission_policies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(100) NOT NULL,
  `content` TEXT NOT NULL,
  `display_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student Types Table
CREATE TABLE `student_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `requirements` TEXT NOT NULL,
  `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Age Requirements Table
CREATE TABLE `age_requirements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `grade_level` VARCHAR(50) NOT NULL,
  `requirements` TEXT NOT NULL,
  `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enrollment Procedures Table
CREATE TABLE `enrollment_procedures` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_type` VARCHAR(50) NOT NULL,
  `steps` TEXT NOT NULL,
  `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grounds for Non-Readmission Table
CREATE TABLE `non_readmission_grounds` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `description` TEXT NOT NULL,
  `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------
-- Media and Content
-- ------------------------------------

-- Media Library Table
CREATE TABLE `media` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `filename` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` ENUM('image', 'document', 'video') NOT NULL,
  `alt_text` VARCHAR(255),
  `file_size` INT,
  `dimensions` VARCHAR(20), -- For images (width x height)
  `uploaded_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Slideshow Table
CREATE TABLE `slideshow` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `image` VARCHAR(255) NOT NULL,
  `caption` TEXT,
  `link` VARCHAR(255),
  `display_order` INT DEFAULT 0,
  `is_active` BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- School Goals Table
CREATE TABLE `school_goals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `display_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------
-- Contact and Interaction
-- ------------------------------------

-- Contact Information Table
CREATE TABLE `contact_information` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `address` TEXT NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `map_embed_code` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact Form Submissions Table
CREATE TABLE `contact_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(50),
  `subject` VARCHAR(255),
  `message` TEXT NOT NULL,
  `submission_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
  `ip_address` VARCHAR(45)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Social Media Table
CREATE TABLE `social_media` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `platform` VARCHAR(50) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `icon` VARCHAR(50),
  `display_order` INT DEFAULT 0,
  `is_active` BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create database indexes for performance optimization
-- Index for frequently queried columns
CREATE INDEX idx_news_published_date ON news(published_date);
CREATE INDEX idx_faculty_category ON faculty(category_id);
CREATE INDEX idx_academic_programs_level ON academic_programs(level_id);
CREATE INDEX idx_navigation_parent ON navigation(parent_id);