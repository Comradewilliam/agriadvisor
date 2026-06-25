-- Farmer portal: crop stages, harvests, CMS library articles
-- Run: php scripts/apply-patch.php database/patch_farmer_portal.sql

-- Crop growth stage tracking per farmer
ALTER TABLE `farmer_crops`
    ADD COLUMN `growth_stage_id` INT NULL DEFAULT NULL AFTER `planted_at`;

-- FK may fail if column already exists without FK; apply script handles gracefully
-- ALTER TABLE farmer_crops ADD CONSTRAINT fk_fc_stage FOREIGN KEY (growth_stage_id) REFERENCES growth_stages(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS `farmer_harvests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `crop_id` INT NOT NULL,
    `harvest_year` SMALLINT NOT NULL,
    `yield_amount` DECIMAL(10, 2) DEFAULT NULL,
    `yield_unit` VARCHAR(50) DEFAULT 'gunia',
    `yield_per_acre` VARCHAR(120) DEFAULT NULL COMMENT 'e.g. Gunia 45 kwa ekari',
    `quality_status` ENUM('BORA', 'KAWAIDA', 'DHAIFU') DEFAULT 'KAWAIDA',
    `notes` TEXT DEFAULT NULL,
    `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`crop_id`) REFERENCES `crops`(`id`) ON DELETE CASCADE,
    INDEX `idx_farmer_harvests_farmer` (`farmer_id`, `harvest_year` DESC)
);

CREATE TABLE IF NOT EXISTS `cms_content` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `key_name` VARCHAR(100) NOT NULL,
    `section` VARCHAR(50) NOT NULL DEFAULT 'general',
    `content_type` ENUM('text', 'image', 'html') NOT NULL DEFAULT 'text',
    `content_value` TEXT,
    `sort_order` INT DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_cms_key` (`key_name`)
);

-- Farmer library articles (admin-editable via CMS)
INSERT INTO `cms_content` (`key_name`, `section`, `content_type`, `content_value`, `sort_order`) VALUES
('farmer_lib_1_tag',   'farmer_library', 'text',  'MWONGOZO', 1),
('farmer_lib_1_title', 'farmer_library', 'text',  'Jinsi ya kutambua ukungu kwenye majani ya mahindi.', 2),
('farmer_lib_1_body',  'farmer_library', 'html',  '<p>Ukungu (maize rust) huonekana kama madoa ya kahawia au machungwa kwenye majani. Chunguza shamba kila wiki, epuka kupanda mahindi mfululizo kwenye shamba moja, na tumia mbegu zilizothibitishwa.</p><p>Endapo ugonjwa unaenea haraka, wasiliana na afisa wako wa kilimo au uliza BwanaShamba AI kwa ushauri wa dawa sahihi.</p>', 3),
('farmer_lib_1_image', 'farmer_library', 'image', '/assets/images/maize_disease.png', 4),
('farmer_lib_2_tag',   'farmer_library', 'text',  'VIDOKEZO', 5),
('farmer_lib_2_title', 'farmer_library', 'text',  'Mbinu bora za umwagiliaji wakati wa kiangazi.', 6),
('farmer_lib_2_body',  'farmer_library', 'html',  '<p>Wakati wa joto kali, umwagiliaji wa asubuhi au jioni hupunguza uvujaji wa maji. Tumia mifereji au umwagiliaji wa matone (drip) ili kufikisha maji moja kwa moja kwenye mizizi.</p><p>Funika udongo kwa malisho (mulching) ili kuhifadhi unyevu na kupunguza magugu.</p>', 7),
('farmer_lib_2_image', 'farmer_library', 'image', '/assets/images/irrigation_drip.png', 8),
('farmer_lib_3_tag',   'farmer_library', 'text',  'MBOLEA', 9),
('farmer_lib_3_title', 'farmer_library', 'text',  'Aina 3 za mbolea kwa ukuaji wa kasi.', 10),
('farmer_lib_3_body',  'farmer_library', 'html',  '<p><strong>1. Mbolea ya msingi (DAP/MAP):</strong> wakati wa kupanda — fosforasi kwa mizizi imara.</p><p><strong>2. Urea:</strong> hatua ya ukuaji — nitrojeni kwa majani na ukuaji wa kasi.</p><p><strong>3. CAN au NPK:</strong> kabla ya maua — lishe kamili kwa mavuno bora.</p><p>Rekodi tarehe na kiasi ulichoweka ili kuepuka kupitisha au kupunguza.</p>', 11),
('farmer_lib_3_image', 'farmer_library', 'image', '/assets/images/fertilizer.png', 12),
('farmer_lib_4_tag',   'farmer_library', 'text',  'MAVUNO', 13),
('farmer_lib_4_title', 'farmer_library', 'text',  'Tayarisha ghala lako mwezi mmoja kabla.', 14),
('farmer_lib_4_body',  'farmer_library', 'html',  '<p>Safisha ghala, fungia nyufa, na weka dawa ya wadudu (phosphine) kwa usalama wa chakula. Kausha mahindi hadi unyevu chini ya 13.5% kabla ya kuhifadhi.</p><p>Panga nafasi ya hewa na epuka kuhifadhi mazao yaliyochafuka pamoja na mazuri.</p>', 15),
('farmer_lib_4_image', 'farmer_library', 'image', '/assets/images/harvest_storage.png', 16)
ON DUPLICATE KEY UPDATE `content_value` = VALUES(`content_value`);
