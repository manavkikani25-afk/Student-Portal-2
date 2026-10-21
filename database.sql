-- ==========================================================
-- Student Portal - Complete Database Schema & Seed Data
-- Subject: Web Development Frameworks (ITUE203)
-- Database: campussync_db
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `campussync_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campussync_db`;

-- Drop tables in order of foreign key dependency
DROP TABLE IF EXISTS `remember_tokens`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `users`;

-- 1. USERS TABLE (Prac 9, 10, 14)
CREATE TABLE `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `mobile` VARCHAR(15) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `course` VARCHAR(50) NOT NULL,
    `year` TINYINT NOT NULL DEFAULT 1,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. USER ROLES TABLE (Prac 10, 14 - RBAC)
CREATE TABLE `user_roles` (
    `role_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. REMEMBER ME TOKENS (Prac 10)
CREATE TABLE `remember_tokens` (
    `token_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `selector` CHAR(24) NOT NULL UNIQUE,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. STUDENTS DIRECTORY TABLE (Prac 11 - Minimum 20 records required)
CREATE TABLE `students` (
    `student_id` INT AUTO_INCREMENT PRIMARY KEY,
    `enrollment_no` VARCHAR(20) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `mobile` VARCHAR(15) NOT NULL,
    `course` VARCHAR(50) NOT NULL,
    `semester` TINYINT NOT NULL DEFAULT 1,
    `status` ENUM('Active', 'Graduated', 'Suspended') NOT NULL DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. EVENTS TABLE (Prac 12, 13)
CREATE TABLE `events` (
    `event_id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `event_date` DATE NOT NULL,
    `event_time` TIME NOT NULL,
    `venue` VARCHAR(100) NOT NULL,
    `event_description` TEXT NOT NULL,
    `poster` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('Upcoming', 'Ongoing', 'Completed') NOT NULL DEFAULT 'Upcoming',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. EVENT REGISTRATIONS (Prac 14)
CREATE TABLE `registrations` (
    `registration_id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. AUDIT LOGS (Prac 14)
CREATE TABLE `audit_logs` (
    `log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `user_name` VARCHAR(100) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- SEED DATA INSERTION
-- ==========================================================

-- Insert Default Admin User (Password: Admin@12345)
INSERT INTO `users` (`user_id`, `full_name`, `email`, `mobile`, `password`, `course`, `year`, `gender`) VALUES
(1, 'System Administrator', 'admin@campus.edu', '9876543210', '$2y$10$XdH0iaeRGiYWFk6j5KiKcuel5yXRg5zgUufbgixRztU9r.1gPQW1y', 'B.Tech IT', 4, 'Male');

-- Insert Default Student User (Password: Student@123)
INSERT INTO `users` (`user_id`, `full_name`, `email`, `mobile`, `password`, `course`, `year`, `gender`) VALUES
(2, 'Aryan Sharma', 'student@campus.edu', '9898012345', '$2y$10$dHPtEyqC1CjzhV.d75bWsuS9EYhITyZ9BZ7.Ma7YpmfCKqNkzAf0S', 'B.Tech CSE', 3, 'Male'),
(3, 'Priya Patel', 'priya.patel@campus.edu', '9712345678', '$2y$10$dHPtEyqC1CjzhV.d75bWsuS9EYhITyZ9BZ7.Ma7YpmfCKqNkzAf0S', 'B.Tech CE', 2, 'Female');

-- Assign Roles
INSERT INTO `user_roles` (`user_id`, `role`) VALUES
(1, 'admin'),
(2, 'student'),
(3, 'student');

-- Seed Students Table (22 seeded records for Practical 11 requirement)
INSERT INTO `students` (`enrollment_no`, `full_name`, `email`, `mobile`, `course`, `semester`, `status`) VALUES
('25DCS001', 'Aarav Mehta', 'aarav.mehta@campus.edu', '9825011223', 'B.Tech CSE', 3, 'Active'),
('25DCS002', 'Ananya Shah', 'ananya.shah@campus.edu', '9825022334', 'B.Tech CSE', 3, 'Active'),
('25DIT003', 'Bhavya Joshi', 'bhavya.joshi@campus.edu', '9825033445', 'B.Tech IT', 3, 'Active'),
('25DCE004', 'Chirag Dave', 'chirag.dave@campus.edu', '9825044556', 'B.Tech CE', 5, 'Active'),
('25DCS005', 'Diya Trivedi', 'diya.trivedi@campus.edu', '9825055667', 'B.Tech CSE', 3, 'Active'),
('25DIT006', 'Eshan Vora', 'eshan.vora@campus.edu', '9825066778', 'B.Tech IT', 1, 'Active'),
('25DAI007', 'Falguni Barot', 'falguni.barot@campus.edu', '9825077889', 'B.Tech AIML', 3, 'Active'),
('25DCE008', 'Gaurav Parmar', 'gaurav.parmar@campus.edu', '9825088990', 'B.Tech CE', 7, 'Graduated'),
('25DCS009', 'Heli Pandya', 'heli.pandya@campus.edu', '9825099001', 'B.Tech CSE', 3, 'Active'),
('25DIT010', 'Ishaan Solanki', 'ishaan.solanki@campus.edu', '9825100112', 'B.Tech IT', 5, 'Active'),
('25DAI011', 'Jhanvi Prajapati', 'jhanvi.prajapati@campus.edu', '9825111223', 'B.Tech AIML', 1, 'Active'),
('25DCS012', 'Kavya Zala', 'kavya.zala@campus.edu', '9825122334', 'B.Tech CSE', 5, 'Active'),
('25DCE013', 'Manan Desai', 'manan.desai@campus.edu', '9825133445', 'B.Tech CE', 3, 'Active'),
('25DIT014', 'Nidhi Vyas', 'nidhi.vyas@campus.edu', '9825144556', 'B.Tech IT', 3, 'Active'),
('25DCS015', 'Omkar Bhatt', 'omkar.bhatt@campus.edu', '9825155667', 'B.Tech CSE', 1, 'Active'),
('25DAI016', 'Pooja Shukla', 'pooja.shukla@campus.edu', '9825166778', 'B.Tech AIML', 5, 'Active'),
('25DIT017', 'Rohan Soni', 'rohan.soni@campus.edu', '9825177889', 'B.Tech IT', 7, 'Graduated'),
('25DCE018', 'Sneha Mistry', 'sneha.mistry@campus.edu', '9825188990', 'B.Tech CE', 3, 'Active'),
('25DCS019', 'Tanmay Rathod', 'tanmay.rathod@campus.edu', '9825199001', 'B.Tech CSE', 5, 'Suspended'),
('25DAI020', 'Urvi Panchal', 'urvi.panchal@campus.edu', '9825200112', 'B.Tech AIML', 3, 'Active'),
('25DIT021', 'Vedant Parekh', 'vedant.parekh@campus.edu', '9825211223', 'B.Tech IT', 3, 'Active'),
('25DCS022', 'Yashvi Kothari', 'yashvi.kothari@campus.edu', '9825222334', 'B.Tech CSE', 1, 'Active');

-- Seed Events Table (Prac 12, 13)
INSERT INTO `events` (`title`, `category`, `event_date`, `event_time`, `venue`, `event_description`, `poster`, `status`) VALUES
('TechNova 2026 Hackathon', 'Technical', '2026-11-15', '09:00:00', 'Central Computing Lab, Block-B', 'A 24-hour national level hackathon focusing on AI, Web3, and IoT smart campus solutions with cash prizes up to Rs. 50,000.', NULL, 'Upcoming'),
('Spandan Annual Cultural Fest', 'Cultural', '2026-12-02', '17:30:00', 'University Open Air Auditorium', 'The flagship cultural carnival featuring singing, dance, drama, fashion pageant, and celebrity music night.', NULL, 'Upcoming'),
('Inter-College Cricket Tournament', 'Sports', '2026-11-20', '08:00:00', 'University Sports Ground', 'Annual inter-departmental leather ball cricket tournament with 16 competing teams.', NULL, 'Upcoming'),
('Modern Full-Stack Web Development Workshop', 'Workshop', '2026-11-08', '10:30:00', 'Seminar Hall 3', 'Hands-on session covering PHP 8, REST APIs, responsive CSS3 architectures, and MySQL optimization.', NULL, 'Upcoming'),
('AI & Machine Learning Symposium', 'Seminar', '2026-11-28', '11:00:00', 'Auditorium A', 'Expert keynote addresses from industry leaders on Generative AI, Large Language Models, and deep tech trends.', NULL, 'Upcoming');

-- Seed Event Registrations
INSERT INTO `registrations` (`event_id`, `user_id`) VALUES
(1, 2),
(2, 2),
(4, 3);

-- Seed Initial Audit Logs (Prac 14)
INSERT INTO `audit_logs` (`user_id`, `user_name`, `action`, `details`, `ip_address`) VALUES
(1, 'System Administrator', 'DATABASE_INIT', 'Database schema initialized and seed records inserted successfully.', '127.0.0.1'),
(1, 'System Administrator', 'ADMIN_LOGIN', 'Admin authenticated into system.', '127.0.0.1'),
(2, 'Aryan Sharma', 'STUDENT_LOGIN', 'Student logged into portal successfully.', '127.0.0.1'),
(2, 'Aryan Sharma', 'EVENT_REGISTRATION', 'Registered for TechNova 2026 Hackathon.', '127.0.0.1');
