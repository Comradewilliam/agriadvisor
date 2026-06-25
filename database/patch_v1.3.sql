-- Patch v1.3: schema alignment, weather approvals, OTP unique phone
-- Run: mysql -u root agridb < database/patch_v1.3.sql

USE `agridb`;

-- knowledge_base: align with RAG (title, situation, crop_id)
ALTER TABLE `knowledge_base`
    ADD COLUMN IF NOT EXISTS `crop_id` INT DEFAULT NULL AFTER `id`,
    ADD COLUMN IF NOT EXISTS `title` VARCHAR(255) NULL AFTER `district_id`,
    ADD COLUMN IF NOT EXISTS `situation` TEXT NULL AFTER `title`,
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Migrate legacy columns if present
UPDATE `knowledge_base`
SET `title` = COALESCE(`title`, `disease_pest_name`, CONCAT('Entry #', id)),
    `situation` = COALESCE(`situation`, `symptoms`, '')
WHERE `title` IS NULL OR `title` = '';

-- weather_alerts: approval workflow
ALTER TABLE `weather_alerts`
    ADD COLUMN IF NOT EXISTS `alert_type` VARCHAR(80) DEFAULT 'general',
    ADD COLUMN IF NOT EXISTS `approval_status` ENUM('pending','approved','rejected','expired') DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS `expires_at` TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS `approved_by_ward` INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `approved_by_dao` INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `approved_by_admin` INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ward_approved_at` TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS `dao_approved_at` TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS `admin_approved_at` TIMESTAMP NULL;

UPDATE `weather_alerts`
SET `approval_status` = IF(`active` = 1, 'approved', 'pending'),
    `expires_at` = COALESCE(`expires_at`, DATE_ADD(`created_at`, INTERVAL 48 HOUR))
WHERE `approval_status` IS NULL OR `approval_status` = '';

UPDATE `weather_alerts` SET `active` = 0 WHERE `approval_status` = 'pending';

-- OTP: one row per phone (replace on new request)
ALTER TABLE `otp_tokens` ADD UNIQUE KEY `unique_phone` (`phone`);
