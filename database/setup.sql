-- ============================================================
-- Agri-Advisory System — Database Setup (v2)
-- Multi-district scalable schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS `agridb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `agridb`;

-- ============================================================
-- GEOGRAPHICAL HIERARCHY
-- ============================================================

CREATE TABLE `districts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `region` VARCHAR(255) NOT NULL DEFAULT 'Kigoma',
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `wards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `district_id` INT NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`district_id`) REFERENCES `districts`(`id`) ON DELETE CASCADE
);

CREATE TABLE `villages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `ward_id` INT NOT NULL,
    `lat` DECIMAL(10, 8) DEFAULT NULL,
    `lng` DECIMAL(11, 8) DEFAULT NULL,
    `network_quality` ENUM('good', 'average', 'poor') DEFAULT 'average',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ward_id`) REFERENCES `wards`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- USERS (Staff: Admin, DAO, WAO)
-- ============================================================

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'dao', 'ward_officer') NOT NULL,
    `phone` VARCHAR(20) UNIQUE DEFAULT NULL,
    `working_office` VARCHAR(255) DEFAULT NULL,
    `district_id` INT DEFAULT NULL COMMENT 'Primary district for DAOs',
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_by` INT DEFAULT NULL COMMENT 'Which admin/dao created this account',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`district_id`) REFERENCES `districts`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- Map officers (ward_officer) to their wards (a WAO can cover multiple wards)
CREATE TABLE `officer_wards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `officer_id` INT NOT NULL,
    `ward_id` INT NOT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_officer_ward` (`officer_id`, `ward_id`),
    FOREIGN KEY (`officer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`ward_id`) REFERENCES `wards`(`id`) ON DELETE CASCADE
);

-- Map DAOs to districts
CREATE TABLE `officer_districts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `officer_id` INT NOT NULL,
    `district_id` INT NOT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_officer_district` (`officer_id`, `district_id`),
    FOREIGN KEY (`officer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`district_id`) REFERENCES `districts`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- FARMERS
-- ============================================================

CREATE TABLE `farmers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) UNIQUE NOT NULL,
    `ward_id` INT DEFAULT NULL,
    `village_id` INT DEFAULT NULL,
    `farm_size_acres` DECIMAL(6, 2) DEFAULT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `registered_via` ENUM('ussd', 'web', 'officer') DEFAULT 'web',
    `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`ward_id`) REFERENCES `wards`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`village_id`) REFERENCES `villages`(`id`) ON DELETE SET NULL
);

-- ============================================================
-- CROPS & KNOWLEDGE STRUCTURE
-- ============================================================

CREATE TABLE `crops` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name_sw` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255) NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `farmer_crops` (
    `farmer_id` INT NOT NULL,
    `crop_id` INT NOT NULL,
    `type` ENUM('primary', 'secondary') DEFAULT 'primary',
    `planted_at` DATE DEFAULT NULL,
    `acres` DECIMAL(6, 2) DEFAULT NULL,
    PRIMARY KEY (`farmer_id`, `crop_id`),
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`crop_id`) REFERENCES `crops`(`id`) ON DELETE CASCADE
);

CREATE TABLE `growth_stages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `crop_id` INT NOT NULL,
    `name_sw` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255) NOT NULL,
    `start_day` INT DEFAULT 0 COMMENT 'Days from planting',
    `end_day` INT DEFAULT 0 COMMENT 'Days from planting',
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`crop_id`) REFERENCES `crops`(`id`) ON DELETE CASCADE
);

CREATE TABLE `advisory_topics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `stage_id` INT NOT NULL,
    `name_sw` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255) NOT NULL,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`stage_id`) REFERENCES `growth_stages`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- KNOWLEDGE BASE (RAG)
-- ============================================================

CREATE TABLE `knowledge_base` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `crop_id` INT DEFAULT NULL,
    `stage_id` INT NOT NULL,
    `topic_id` INT DEFAULT NULL,
    `district_id` INT DEFAULT NULL COMMENT 'NULL means applies to all districts',
    `title` VARCHAR(255) NOT NULL,
    `situation` TEXT DEFAULT NULL,
    `solution` TEXT NOT NULL,
    `language` ENUM('sw', 'en') DEFAULT 'sw',
    `source` VARCHAR(255) DEFAULT 'system' COMMENT 'MoA, TARI, user_id, etc',
    `status` ENUM('draft', 'published', 'archived') DEFAULT 'published',
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`crop_id`) REFERENCES `crops`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`stage_id`) REFERENCES `growth_stages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`topic_id`) REFERENCES `advisory_topics`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`district_id`) REFERENCES `districts`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- ============================================================
-- MESSAGES (Unified: SMS + Web)
-- ============================================================

CREATE TABLE `ai_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `direction` ENUM('in', 'out') NOT NULL COMMENT 'in=from farmer, out=to farmer',
    `channel` ENUM('sms', 'web', 'ussd') NOT NULL DEFAULT 'web',
    `content` TEXT NOT NULL,
    `provider_message_id` VARCHAR(100) DEFAULT NULL COMMENT 'Africa''s Talking message ID',
    `ai_confidence` ENUM('high', 'medium', 'low') DEFAULT NULL,
    `escalated` BOOLEAN DEFAULT FALSE,
    `escalated_to` INT DEFAULT NULL COMMENT 'officer user id escalated to',
    `delivery_status` VARCHAR(50) DEFAULT 'delivered',
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`escalated_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE TABLE `officer_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `direction` ENUM('in', 'out') NOT NULL COMMENT 'in=from farmer, out=to farmer',
    `channel` ENUM('sms', 'web') NOT NULL DEFAULT 'web',
    `content` TEXT NOT NULL,
    `provider_message_id` VARCHAR(100) DEFAULT NULL COMMENT 'Africa''s Talking message ID',
    `officer_id` INT DEFAULT NULL COMMENT 'NULL means system generated',
    `is_system_alert` BOOLEAN DEFAULT FALSE,
    `delivery_status` VARCHAR(50) DEFAULT 'delivered',
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`officer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- ============================================================
-- ESCALATIONS
-- ============================================================

CREATE TABLE `escalations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ai_message_id` INT NOT NULL,
    `assigned_officer_id` INT DEFAULT NULL,
    `status` ENUM('pending', 'responded') DEFAULT 'pending',
    `priority` ENUM('urgent', 'normal') DEFAULT 'normal',
    `officer_reply` TEXT DEFAULT NULL,
    `escalated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `responded_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`ai_message_id`) REFERENCES `ai_messages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_officer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- ============================================================
-- FARMER RELOCATION REQUESTS
-- ============================================================

CREATE TABLE `farmer_relocation_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `from_ward_id` INT NOT NULL,
    `to_ward_id` INT NOT NULL,
    `requested_by` INT NOT NULL COMMENT 'WAO user id',
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `notes` TEXT DEFAULT NULL,
    `reviewed_by` INT DEFAULT NULL COMMENT 'WAO of destination ward',
    `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_ward_id`) REFERENCES `wards`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`to_ward_id`) REFERENCES `wards`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- ============================================================
-- VISIT REQUESTS
-- ============================================================

CREATE TABLE `visit_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `request_reason` TEXT NOT NULL,
    `preferred_date` DATE NULL,
    `preferred_time` VARCHAR(50) NULL,
    `status` ENUM('pending', 'scheduled', 'completed', 'cancelled') DEFAULT 'pending',
    `handled_by` INT NULL,
    `handled_at` TIMESTAMP NULL,
    `notes` TEXT NULL,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`handled_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- ============================================================
-- VISITS
-- ============================================================

CREATE TABLE `visits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `officer_id` INT NOT NULL,
    `scheduled_at` DATETIME NOT NULL,
    `reason` VARCHAR(500) NOT NULL,
    `status` ENUM('pending', 'scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`officer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- WEATHER ALERTS
-- ============================================================

CREATE TABLE `weather_alerts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `district_id` INT DEFAULT NULL,
    `ward_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `alert_type` VARCHAR(80) DEFAULT 'general',
    `severity` ENUM('low', 'medium', 'high') DEFAULT 'medium',
    `approval_status` ENUM('pending', 'approved', 'rejected', 'expired') DEFAULT 'pending',
    `active` BOOLEAN DEFAULT FALSE COMMENT 'TRUE only after full approval chain',
    `expires_at` TIMESTAMP NULL COMMENT 'Unapproved alerts expire automatically',
    `approved_by_ward` INT DEFAULT NULL,
    `approved_by_dao` INT DEFAULT NULL,
    `approved_by_admin` INT DEFAULT NULL,
    `ward_approved_at` TIMESTAMP NULL DEFAULT NULL,
    `dao_approved_at` TIMESTAMP NULL DEFAULT NULL,
    `admin_approved_at` TIMESTAMP NULL DEFAULT NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`district_id`) REFERENCES `districts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`ward_id`) REFERENCES `wards`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by_ward`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by_dao`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by_admin`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- ============================================================
-- OTP TOKENS
-- ============================================================

CREATE TABLE `otp_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL,
    `token_hash` VARCHAR(10) NOT NULL COMMENT 'Plain 6-digit OTP code',
    `expires_at` TIMESTAMP NOT NULL,
    `used_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `unique_phone` (`phone`)
);

-- ============================================================
-- USSD SESSIONS
-- ============================================================

CREATE TABLE `ussd_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT DEFAULT NULL,
    `session_id` VARCHAR(255) NOT NULL,
    `menu_path` JSON DEFAULT NULL,
    `start_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `end_time` TIMESTAMP NULL DEFAULT NULL,
    `completed` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`id`) ON DELETE SET NULL
);

-- ============================================================
-- AUDIT LOG
-- ============================================================

CREATE TABLE `audit_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `actor_id` INT DEFAULT NULL COMMENT 'User who performed the action',
    `actor_role` VARCHAR(50) DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL COMMENT 'e.g. create_officer, delete_ward, relocate_farmer',
    `entity_type` VARCHAR(100) DEFAULT NULL COMMENT 'e.g. user, ward, farmer, knowledge_base',
    `entity_id` INT DEFAULT NULL,
    `meta` JSON DEFAULT NULL COMMENT 'Additional context (before/after values)',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`actor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
