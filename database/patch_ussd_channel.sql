-- Allow USSD advisory messages in ai_messages
ALTER TABLE `ai_messages`
    MODIFY COLUMN `channel` ENUM('sms', 'web', 'ussd') NOT NULL DEFAULT 'web';
