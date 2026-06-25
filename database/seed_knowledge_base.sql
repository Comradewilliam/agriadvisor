-- ============================================================
-- Agri-Advisory — Knowledge Base Seed
-- Source: kakonko_crop_kb_tz_official.md (TARI/MoA official guidance)
-- District: Kakonko (id=1) | Language: English
-- Run AFTER setup.sql and seed.sql
-- ============================================================
USE `agridb`;

-- ============================================================
-- ADVISORY TOPICS (sub-categories per growth stage)
-- ============================================================

-- Maize (crop_id=1) topics
INSERT IGNORE INTO `advisory_topics` (`stage_id`, `name_sw`, `name_en`, `sort_order`) VALUES
(1,  'Maandalizi ya Udongo',        'Soil Preparation',              1),
(1,  'Mifereji ya Maji',            'Drainage',                      2),
(2,  'Uteuzi wa Mbegu',             'Seed Selection',                1),
(2,  'Mbegu za Chotara vs OPV',     'Hybrid vs OPV',                 2),
(3,  'Wakati wa Kupanda',           'Planting Timing',               1),
(3,  'Nafasi ya Kupanda',           'Spacing',                       2),
(5,  'Mbolea ya Msingi',            'Basal Fertilizer',              1),
(5,  'Mbolea ya Juu',               'Top-dressing',                  2),
(6,  'Palizi',                      'Weeding',                       1),
(6,  'Magonjwa ya Majani',          'Leaf Diseases',                 2),
(7,  'Ugonjwa wa Milia (Streak)',   'Maize Streak Virus',            1),
(8,  'Magonjwa ya Punje',           'Grain/Ear Diseases',            1),
(9,  'Wakati wa Kuvuna',            'Harvest Timing',                1),
(10, 'Wadudu wa Ghala',             'Storage Pests',                 1),
(10, 'Usafi wa Ghala',              'Store Hygiene',                 2);

-- Common Beans (crop_id=3) topics
INSERT IGNORE INTO `advisory_topics` (`stage_id`, `name_sw`, `name_en`, `sort_order`) VALUES
(11, 'Udongo na Drenage',           'Soil & Drainage',               1),
(12, 'Mbegu Bora',                  'Improved Seed',                 1),
(13, 'Mbolea ya Kupanda',           'Basal Nutrition',               1),
(13, 'Unyevu wa Udongo',            'Soil Moisture at Planting',     2),
(14, 'Palizi',                      'Weeding',                       1),
(14, 'Wadudu wa Mapema',            'Early Pests',                   2),
(15, 'Magonjwa ya Jani',            'Leaf Diseases',                 1),
(15, 'Mzunguko wa Mazao',           'Crop Rotation',                 2),
(16, 'Msongo wa Maua',              'Stress at Flowering',           1),
(18, 'Wakati wa Kuvuna',            'Harvest Timing',                1),
(19, 'Kuhifadhi Maharage',          'Bean Storage',                  1);

-- Groundnuts (crop_id=4) topics
INSERT IGNORE INTO `advisory_topics` (`stage_id`, `name_sw`, `name_en`, `sort_order`) VALUES
(20, 'Ardhi na Drenage',            'Land & Drainage',               1),
(21, 'Aina za Mbegu',               'Seed Varieties',                1),
(22, 'Kupanda Mapema',              'Early Planting',                1),
(24, 'Palizi',                      'Weeding',                       1),
(25, 'Ukame',                       'Drought Management',            1),
(25, 'Magonjwa – Rosette, Kutu',   'Diseases – Rosette, Rust',      2),
(26, 'Aflatoxin',                   'Aflatoxin Risk',                1),
(27, 'Kuvuna kwa Wakati',           'Timely Harvest',                1),
(28, 'Kukausha na Kuhifadhi',       'Drying & Storage',              1);

-- Oil Palm (crop_id=5) topics
INSERT IGNORE INTO `advisory_topics` (`stage_id`, `name_sw`, `name_en`, `sort_order`) VALUES
(29, 'Miche Bora',                  'Quality Planting Material',     1),
(30, 'Kuoteshwa Mbegu',             'Seed Germination',              1),
(31, 'Kitalu',                      'Nursery Management',            1),
(32, 'Kupandikiza',                 'Transplanting',                 1),
(33, 'Palizi Baada ya Kupandikiza', 'Early Field Weeding',           1),
(35, 'Uzalishaji wa Makundi',       'Bunch Production',              1),
(36, 'Kuvuna Matunda Yaliyoiva',    'Harvesting Ripe Bunches',       1),
(37, 'Usindikaji wa Haraka',        'Quick Processing',              1);



-- ============================================================
-- MAIZE (crop_id=1) Knowledge Base Entries
-- ============================================================

-- Stage 1: Land Preparation (growth_stage id=1)
INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 1, 'Maize: Land Preparation',
'Soil not ready before the first rains; hard soil after heavy rain; poor drainage or waterlogging.',
'Prepare the field early before the first rains. Plough about 15–20 cm deep. Use ridges or mounds in places that hold water. A non-selective herbicide can also be used instead of ploughing where appropriate.',
'en', 'system', 'published');

-- Stage 2: Variety/Seed Selection (growth_stage id=2)
INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 2, 'Maize: Variety and Seed Selection',
'Wrong variety for the season, altitude, rainfall, or farming objective.',
'Choose the variety based on the goal of production, season (masika or vuli), altitude, and rainfall. Hybrid seed is used for one season; OPV seed can be reused for 2–3 seasons.',
'en', 'system', 'published');

-- Stage 3: Planting (growth_stage id=3)
INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 3, 'Maize: Planting Timing',
'Late planting increases risk, especially for streak or milia-prone varieties.',
'Plant early. For varieties sensitive to maize streak virus, delayed planting increases damage. Use the spacing recommended for the specific variety and zone.',
'en', 'system', 'published');

-- Stage 4: Plant Population (growth_stage id=4)
INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 4, 'Maize: Plant Population and Spacing',
'Too few or too many plants, poor stand establishment.',
'Target the recommended stand: about 40,000–50,000 plants/ha in general, and 60,000–70,000 plants/ha for short, early-maturing varieties like Kito and Katumani. Thin excess plants 2–3 weeks after emergence. For long-duration varieties use about 75×30 cm or 90×25 cm with one plant per hole. In dry areas, wider spacing such as 100×50 cm with two plants per hole is recommended.',
'en', 'system', 'published');

-- Stage 5: Nutrient Management — manure (growth_stage id=5)
INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 5, 'Maize: Organic Manure and Soil Fertility',
'Low soil fertility, weak early growth, low yields.',
'Apply farmyard manure or compost before planting: about 10–15 tonnes/ha of manure (4–6 tonnes/acre). Fertilizer works best when placed close to the seed but not touching it.',
'en', 'system', 'published');

-- Stage 5: Nutrient Management — split fertilizer
INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 5, 'Maize: Fertilizer Split Application',
'Nitrogen and phosphorus shortages, especially in low-rainfall and coastal areas.',
'Split fertilizer: apply about 30–50% at planting and the rest when maize reaches about 1 m height or earlier depending on crop condition. Phosphorus should be applied fully at planting.',
'en', 'system', 'published');

-- Stage 5: Intercropping
INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 5, 'Maize: Intercropping for Soil Health',
'Weed pressure, erosion, and pest damage on maize fields.',
'Intercrop maize with legumes such as beans, cowpea, groundnut, or pigeonpea. This improves soil fertility, reduces weeds and erosion, and can reduce pest pressure.',
'en', 'system', 'published');

-- Stage 6: Early Vegetative (growth_stage id=6)
INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 6, 'Maize: Early Weed Control',
'Weeds compete for water, nutrients, and light in early maize growth.',
'Keep the field weed-free during the first 40 days after emergence. Two hand weedings are usually enough in low, middle, and highland zones, with timing adjusted by region.',
'en', 'system', 'published');

-- Stage 7: Vegetative to Reproductive — leaf diseases (growth_stage id=7)
INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 7, 'Maize: Leaf Diseases (Rusts, Blights, Streak)',
'Five major leaf diseases reduce yield: two rusts, two leaf blights, and viral streak disease.',
'These diseases are not profitably controlled with chemicals. Use tolerant or resistant varieties as the main control measure. Plant early and use resistant/tolerant varieties for maize streak virus.',
'en', 'system', 'published');

-- Stage 8: Grain/Cob Stage (growth_stage id=8)
INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 8, 'Maize: Ear and Kernel Diseases',
'Ear and kernel diseases such as Gibberella, Fusarium, and Diplodia attack the cob and seed, especially in wet areas.',
'Remove and destroy or bury infected plants and cobs. Keep the crop healthy, avoid mechanical damage, and reduce field moisture stress where possible.',
'en', 'system', 'published');

-- Stage 9: Harvest Maturity (growth_stage id=9)
INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 9, 'Maize: Harvest Timing',
'Harvesting too early reduces grain fill and yield; too late increases losses.',
'Harvest about 7–8 weeks after flowering when physiological maturity is reached. Signs include yellowing leaves, husks turning pale yellow, and the black layer on kernels.',
'en', 'system', 'published');

-- Stage 10: Post-Harvest Storage (growth_stage id=10)
INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 10, 'Maize: Post-Harvest Drying and Storage Pests',
'Weevils, larger grain borer, and other store pests damage grain after harvest.',
'Dry grain well before storage. Use clean stores, airtight containers or bins where possible, or approved grain protectants. Actellic and Malathion options can be used for stored grain protection.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(1, 10, 'Maize: Store Hygiene Before New Season',
'Old grain residues and dirty stores raise infestation risk.',
'Clean the store before the new crop is put inside. Keep the store dry and sanitary at least once a year before new grain is stored.',
'en', 'system', 'published');

-- ============================================================
-- COMMON BEANS (crop_id=3) Knowledge Base Entries
-- ============================================================

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(3, 11, 'Beans: Land Preparation',
'Poorly prepared field, low aeration, and poor root development for beans.',
'Prepare a fine, well-aerated seedbed before the rains. Use a well-drained field and avoid waterlogging.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(3, 11, 'Beans: Site Selection',
'Beans are sensitive to poor drainage and low soil fertility.',
'Choose a field with good drainage and fertile soil, ideally around pH 5–7. Avoid places where water stands after rainfall.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(3, 12, 'Beans: Seed Selection',
'Local bean seed is often low-yielding and may not resist disease well.',
'Use improved, certified seed from recognized sources. Prefer disease-resistant varieties and seed that is clean and true-to-type.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(3, 13, 'Beans: Planting and Basal Fertilizer',
'Low soil fertility limits growth and pod formation in beans.',
'Use locally recommended basal fertilizer options at planting such as TSP with CAN, or Urea, and also DAP or Minjingu Mazao. Use the locally recommended package after soil advice. Plant when there is enough moisture in the soil.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(3, 14, 'Beans: Early Pest Damage and Weed Control',
'Young bean plants can be attacked by leaf-feeding pests and stem pests; weeds reduce light, water, and nutrients.',
'Monitor early and protect the stand quickly when damage appears. Watch for Ootheca (bean leaf beetles) and other bean pests. Weed early and keep the field clean during the critical early growth period.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(3, 15, 'Beans: Major Diseases (Anthracnose, ALS, BCMV, Blight, Rust)',
'Anthracnose, angular leaf spot, bean common mosaic virus (BCMV), common bacterial blight, and leaf rust reduce bean yield.',
'Use resistant varieties where available, plant clean seed, and remove heavily infected plants. Keep the field clean and avoid moving disease with infected seed or crop residues. Rotate crops and do not keep beans in the same field season after season.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(3, 16, 'Beans: Stress at Flowering',
'Stress at bean flowering reduces pod numbers and pod filling.',
'Avoid moisture stress, heavy weed pressure, and disease outbreaks at flowering. Maintain timely field scouting and prompt control measures.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(3, 17, 'Beans: Pod Filling Protection',
'Pod and seed filling can be reduced by pests, disease, and drought stress.',
'Protect the crop from stress until pods mature. Keep the crop healthy and manage water where irrigation is possible.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(3, 18, 'Beans: Harvesting to Avoid Pod Shattering',
'Delayed bean harvesting leads to pod shattering and field losses.',
'Harvest promptly when most pods are mature and dry. Avoid leaving mature pods too long in the field.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(3, 19, 'Beans: Post-Harvest Drying and Storage',
'Grain stored too wet spoils and loses quality; insects and fungal spoilage reduce grain quality.',
'Dry beans thoroughly before storage. Store only clean, dry grain in clean containers or bags. Keep the store dry and inspect regularly.',
'en', 'system', 'published');

-- ============================================================
-- GROUNDNUTS (crop_id=4) Knowledge Base Entries
-- ============================================================

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(4, 20, 'Groundnuts: Land Preparation and Site',
'Waterlogging, compacted soil, or poor root development for groundnuts.',
'Use well-drained land. Groundnuts perform best on suitable soils below 1500 m altitude and in areas with appropriate rainfall and soil conditions.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(4, 21, 'Groundnuts: Improved Varieties',
'Improved groundnut seed is not always available; old varieties can be low-yielding.',
'Use improved varieties and quality seed. Key improved varieties in Tanzania include Pendo 1998, Johari 1985, Naliendele 2009, Mnanje 2009, Mangaka 2009, and Nachi 2015.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(4, 22, 'Groundnuts: Planting',
'Late planting or poor seed quality lowers stand establishment for groundnuts.',
'Plant at the onset of rains using clean, viable seed. Use local agronomist guidance on spacing and seed rate for the variety. Use quality seed and a fine seedbed. Keep the field free from standing water and crusting.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(4, 24, 'Groundnuts: Early Weed Control',
'Weeds compete strongly in the early groundnut crop period.',
'Weed early and keep the crop clean. Groundnut performance drops sharply when weeds are not controlled in time.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(4, 25, 'Groundnuts: Diseases (Rosette, Rust, Leaf Spots)',
'Groundnut rosette disease, rust, and early and late leaf spot reduce yield across Tanzania.',
'Use resistant or tolerant varieties, scout early, and apply integrated disease management. Use moisture-conserving practices, plant at the right time, and choose drought-tolerant improved varieties.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(4, 26, 'Groundnuts: Aflatoxin Risk During Pod Filling',
'Aflatoxin risk rises when groundnut pods are stressed, damaged, or exposed to moisture and poor drying.',
'Harvest on time, minimize pod damage, and dry pods quickly and properly. Keep moisture low to limit fungal contamination. Do not leave mature pods too long in the soil.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(4, 28, 'Groundnuts: Drying and Aflatoxin-Safe Storage',
'High moisture in pods or grain causes mold and aflatoxin risk in stored groundnuts.',
'Dry pods and grain thoroughly before storage. Use clean drying surfaces and protect from rain. Store only dry, clean groundnuts in a dry, ventilated place. Avoid damaged pods and keep the store clean.',
'en', 'system', 'published');

-- ============================================================
-- OIL PALM / MAWESE (crop_id=5) Knowledge Base Entries
-- ============================================================

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(5, 29, 'Oil Palm: Site and Planting Material',
'Poor-quality oil palm planting material lowers yield and oil quality.',
'Use verified, high-quality planting material. TARI Kihinga highlights good planting material for higher yield, better disease/pest tolerance, and improved palm oil quality.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(5, 30, 'Oil Palm: Seed Preparation and Germination',
'Oil palm seed has a hard shell that prevents rapid water uptake and delays germination.',
'Follow the official TARI seed preparation and germination guide for michikichi. The shell must be managed so the seed can absorb water and sprout.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(5, 31, 'Oil Palm: Nursery Stage',
'Weak or uneven oil palm seedlings reduce field performance.',
'Raise healthy seedlings in the nursery and select only vigorous seedlings for transplanting.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(5, 32, 'Oil Palm: Transplanting',
'Poor transplanting reduces oil palm survival and future bunch yield.',
'Transplant only strong, healthy nursery seedlings and establish them in a suitable field following the local TARI or extension protocol.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(5, 33, 'Oil Palm: Early Establishment Weed Control',
'Weed pressure and competition slow early oil palm growth.',
'Weed regularly and keep young palms well maintained. Maintain the stand well, keep it weed-free, and follow local soil-fertility advice for the site.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(5, 35, 'Oil Palm: Bearing Stage Management',
'Low-quality oil palm seedlings and poor management reduce oil yield and quality.',
'Use improved, verified hybrid seedlings from TARI Kihinga. Monitor the plantation and follow TARI or extension recommendations for plant protection. Use illegitimacy-screened hybrid seedlings to reduce contamination in crossing programs.',
'en', 'system', 'published');

INSERT INTO `knowledge_base` (`crop_id`, `stage_id`, `title`, `situation`, `solution`, `language`, `source`, `status`) VALUES
(5, 36, 'Oil Palm: Harvesting Ripe Bunches',
'Harvesting unripe oil palm bunches reduces oil quality and oil extraction efficiency.',
'Harvest ripe bunches and process them promptly to protect quality. Keep harvested fruit or bunches moving quickly into clean processing and value-addition steps.',
'en', 'system', 'published');
