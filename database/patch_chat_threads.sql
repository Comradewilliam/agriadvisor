-- Persistent chat conversations (threads)
CREATE TABLE IF NOT EXISTS `chat_threads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `channel` ENUM('web', 'sms') NOT NULL DEFAULT 'web',
    `title` VARCHAR(255) DEFAULT NULL COMMENT 'Preview from first farmer message',
    `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`id`) ON DELETE CASCADE,
    INDEX `idx_chat_threads_farmer` (`farmer_id`, `updated_at`)
);

ALTER TABLE `ai_messages` ADD COLUMN `thread_id` INT NULL DEFAULT NULL AFTER `farmer_id`;

ALTER TABLE `ai_messages` ADD CONSTRAINT `fk_ai_thread` FOREIGN KEY (`thread_id`) REFERENCES `chat_threads`(`id`) ON DELETE SET NULL;
