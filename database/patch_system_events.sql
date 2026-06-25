-- Channel & system events for admin analytics (USSD, SMS, AI, delivery)
CREATE TABLE IF NOT EXISTS `system_events` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `level` ENUM('info','warning','error') NOT NULL DEFAULT 'info',
    `category` VARCHAR(50) NOT NULL COMMENT 'ussd, sms, ai, delivery, alert',
    `event` VARCHAR(100) NOT NULL COMMENT 'e.g. advisory_answered, inbox, delivery_failed',
    `farmer_id` INT NULL,
    `phone` VARCHAR(20) NULL,
    `channel` VARCHAR(20) NULL COMMENT 'ussd, sms, web',
    `message` VARCHAR(500) NULL,
    `meta` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_se_category` (`category`),
    INDEX `idx_se_event` (`event`),
    INDEX `idx_se_farmer` (`farmer_id`),
    INDEX `idx_se_level` (`level`),
    INDEX `idx_se_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
