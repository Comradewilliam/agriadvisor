-- Officer portal: visits extensions, automated alerts, visit_requests columns
USE `agridb`;

-- visits: extended scheduling & feedback
ALTER TABLE `visits` ADD COLUMN IF NOT EXISTS `visit_type` VARCHAR(50) DEFAULT 'officer_planned' AFTER `reason`;
ALTER TABLE `visits` ADD COLUMN IF NOT EXISTS `crop_id` INT NULL AFTER `visit_type`;
ALTER TABLE `visits` ADD COLUMN IF NOT EXISTS `farm_size_acres` DECIMAL(6,2) NULL AFTER `crop_id`;
ALTER TABLE `visits` ADD COLUMN IF NOT EXISTS `visit_request_id` INT NULL AFTER `farm_size_acres`;
ALTER TABLE `visits` ADD COLUMN IF NOT EXISTS `visit_batch_id` VARCHAR(64) NULL AFTER `visit_request_id`;
ALTER TABLE `visits` ADD COLUMN IF NOT EXISTS `outcome` VARCHAR(100) NULL AFTER `notes`;
ALTER TABLE `visits` ADD COLUMN IF NOT EXISTS `not_done_reason` TEXT NULL AFTER `outcome`;
ALTER TABLE `visits` ADD COLUMN IF NOT EXISTS `followup` TEXT NULL AFTER `not_done_reason`;
ALTER TABLE `visits` ADD COLUMN IF NOT EXISTS `followup_at` DATETIME NULL AFTER `followup`;
ALTER TABLE `visits` ADD COLUMN IF NOT EXISTS `rescheduled_at` DATETIME NULL AFTER `followup_at`;

ALTER TABLE `visits` MODIFY COLUMN `status` ENUM('pending','scheduled','completed','cancelled','postponed') DEFAULT 'scheduled';

-- visit_requests: scheduling linkage
ALTER TABLE `visit_requests` ADD COLUMN IF NOT EXISTS `crop_id` INT NULL AFTER `request_reason`;
ALTER TABLE `visit_requests` ADD COLUMN IF NOT EXISTS `farm_size_acres` DECIMAL(6,2) NULL AFTER `crop_id`;
ALTER TABLE `visit_requests` ADD COLUMN IF NOT EXISTS `scheduled_at` DATETIME NULL AFTER `farm_size_acres`;
ALTER TABLE `visit_requests` ADD COLUMN IF NOT EXISTS `visit_id` INT NULL AFTER `scheduled_at`;
ALTER TABLE `visit_requests` ADD COLUMN IF NOT EXISTS `rescheduled_at` DATETIME NULL AFTER `visit_id`;

-- automated alerts (DAO-managed SMS/event rules)
CREATE TABLE IF NOT EXISTS `automated_alerts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `district_id` INT NOT NULL,
    `ward_id` INT NULL COMMENT 'NULL = whole district',
    `alert_type` ENUM('weather','welcome','visit','crop_advisory','custom') NOT NULL DEFAULT 'custom',
    `title` VARCHAR(255) NOT NULL,
    `message_template` TEXT NOT NULL,
    `channel` ENUM('sms','both') NOT NULL DEFAULT 'sms',
    `trigger_event` ENUM('on_register','on_visit_scheduled','on_visit_reminder','weather_daily','manual') NOT NULL DEFAULT 'manual',
    `trigger_offset_hours` INT NOT NULL DEFAULT 24,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`district_id`) REFERENCES `districts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`ward_id`) REFERENCES `wards`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_auto_alerts_district` (`district_id`, `is_active`)
);
