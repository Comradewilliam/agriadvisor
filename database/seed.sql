-- ============================================================
-- Agri-Advisory System — Seed Data (v2)
-- ============================================================
USE `agridb`;

-- ============================================================
-- DISTRICTS
-- ============================================================
INSERT INTO `districts` (`id`, `name`, `region`, `is_active`) VALUES
(1, 'Kakonko', 'Kigoma', 1);

-- ============================================================
-- USERS — Admin Account
-- ============================================================
-- Young Sadiki | sadiki@agriadvisory.co.tz | QAZzaq123
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `is_active`) VALUES
('Young Sadiki', 'sadiki@agriadvisory.co.tz', '$2y$10$Hnrl9VCEiNPRz/k1pFq3Teu03/2lBdG2Ev0qV2.Bth.VcQ6HZQE5y', 'super_admin', 1);
-- Note: hash above is bcrypt of 'QAZzaq123'. Regenerate with: password_hash('QAZzaq123', PASSWORD_BCRYPT)

-- ============================================================
-- WARDS (Kakonko District — district_id = 1)
-- ============================================================
INSERT INTO `wards` (`id`, `name`, `district_id`) VALUES
(1,  'Kakonko',    1),
(2,  'Kanyonza',   1),
(3,  'Kasanda',    1),
(4,  'Kasuga',     1),
(5,  'Katanga',    1),
(6,  'Kiziguzigu', 1),
(7,  'Mugunzu',    1),
(8,  'Muhange',    1),
(9,  'Gwarama',    1),
(10, 'Gwanumpu',   1),
(11, 'Nyabibuye',  1),
(12, 'Nyamtukuza', 1),
(13, 'Rugenge',    1);

-- ============================================================
-- VILLAGES
-- ============================================================
INSERT INTO `villages` (`name`, `ward_id`) VALUES
('Kakonko', 1), ('Mbizi', 1), ('Muganza', 1), ('Itumbiko', 1),
('Kanyonza', 2),
('Kasanda', 3), ('Chilambo', 3), ('Kazilamihunda', 3),
('Kasuga', 4), ('Kinonko', 4), ('Nyakayenzi', 4),
('Katanga', 5), ('Ilabiro', 5),
('Kiziguzigu', 6), ('Kiyobera', 6), ('Ruyenzi', 6), ('Kabingo', 6),
('Mugunzu', 7), ('Kiduduye', 7), ('Nyagwijima', 7),
('Muhange', 8), ('Rubale', 8),
('Gwarama', 9),
('Gwanumpu', 10), ('Bukirilo', 10),
('Nyabibuye', 11), ('Rumashi', 11),
('Nyamtukuza', 12), ('Kinyinya', 12), ('Chulazo', 12),
('Rugenge', 13), ('Kiga', 13), ('Kasongati', 13);

-- ============================================================
-- CROPS
-- ============================================================
INSERT INTO `crops` (`id`, `name_sw`, `name_en`) VALUES
(1, 'Mahindi',            'Maize'),
(2, 'Mhogo',              'Cassava'),
(3, 'Maharage',           'Common Beans'),
(4, 'Karanga',            'Groundnuts'),
(5, 'Mawese (Michikichi)', 'Oil Palm');

-- ============================================================
-- GROWTH STAGES — Maize (crop_id=1)
-- ============================================================
INSERT INTO `growth_stages` (`id`, `crop_id`, `name_sw`, `name_en`, `sort_order`) VALUES
(1,  1, 'Maandalizi ya Shamba',        'Land Preparation',     1),
(2,  1, 'Uteuzi wa Mbegu/Aina',        'Variety / Seed Selection', 2),
(3,  1, 'Kupanda',                     'Planting',             3),
(4,  1, 'Idadi ya Mimea',              'Plant Population',     4),
(5,  1, 'Lishe ya Udongo (Mbolea)',    'Nutrient Management',  5),
(6,  1, 'Ukuaji wa Mapema (Palizi)',   'Early Vegetative',     6),
(7,  1, 'Ukuaji hadi Kuchanua',        'Vegetative to Reproductive', 7),
(8,  1, 'Hatua ya Punje / Bua',        'Grain/Cob Stage',      8),
(9,  1, 'Ukomavu na Mavuno',           'Harvest Maturity',     9),
(10, 1, 'Kukausha na Kuhifadhi',       'Post-Harvest Storage', 10);

-- Growth Stages — Common Beans (crop_id=3)
INSERT INTO `growth_stages` (`id`, `crop_id`, `name_sw`, `name_en`, `sort_order`) VALUES
(11, 3, 'Maandalizi ya Shamba',        'Land Preparation',     1),
(12, 3, 'Uteuzi wa Mbegu',             'Seed Selection',       2),
(13, 3, 'Kupanda / Lishe ya Msingi',   'Planting / Basal Nutrition', 3),
(14, 3, 'Ukuaji wa Mapema',            'Early Vegetative',     4),
(15, 3, 'Ukuaji hadi Maua',            'Vegetative to Flowering', 5),
(16, 3, 'Maua / Kuweka Mikoba',        'Flowering / Pod Set',  6),
(17, 3, 'Ujazaji wa Mikoba',           'Pod Filling',          7),
(18, 3, 'Ukomavu / Mavuno',            'Maturity / Harvest',   8),
(19, 3, 'Kukausha na Kuhifadhi',       'Post-Harvest',         9);

-- Growth Stages — Groundnuts (crop_id=4)
INSERT INTO `growth_stages` (`id`, `crop_id`, `name_sw`, `name_en`, `sort_order`) VALUES
(20, 4, 'Maandalizi ya Shamba',        'Land Preparation',     1),
(21, 4, 'Uteuzi wa Mbegu',             'Seed Selection',       2),
(22, 4, 'Kupanda',                     'Planting',             3),
(23, 4, 'Ukuaji wa Mapema',            'Early Establishment',  4),
(24, 4, 'Ukuaji wa Mimea',             'Vegetative Stage',     5),
(25, 4, 'Maua na Kupiga Vikonyo',      'Flowering / Pegging',  6),
(26, 4, 'Ujazaji wa Mikoba',           'Pod Filling',          7),
(27, 4, 'Ukomavu / Mavuno',            'Maturity / Harvest',   8),
(28, 4, 'Kukausha na Kuhifadhi',       'Post-Harvest Storage', 9);

-- Growth Stages — Oil Palm (crop_id=5)
INSERT INTO `growth_stages` (`id`, `crop_id`, `name_sw`, `name_en`, `sort_order`) VALUES
(29, 5, 'Uteuzi wa Eneo na Miche',     'Site & Planting Material', 1),
(30, 5, 'Maandalizi ya Mbegu',         'Seed Preparation',     2),
(31, 5, 'Hatua ya Kitalu',             'Nursery Stage',        3),
(32, 5, 'Kupanda Shambani',            'Transplanting',        4),
(33, 5, 'Ukuaji wa Mapema Shambani',   'Early Field Establishment', 5),
(34, 5, 'Ukuaji wa Mimea',             'Vegetative Growth',    6),
(35, 5, 'Hatua ya Kuzaa',              'Bearing Stage',        7),
(36, 5, 'Kuvuna',                      'Harvesting',           8),
(37, 5, 'Baada ya Mavuno / Mnyororo wa Thamani', 'Post-Harvest / Value Chain', 9);
