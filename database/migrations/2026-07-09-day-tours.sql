-- ============================================================
-- ICT DAY TOURS — Database Migration
-- 2026-07-09
--
-- Day Tours: Single-day, chauffeur-guided experiences
-- that can be booked standalone or added to multi-day itineraries.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ════════════════════════════════════════════════════════════
-- 1. DAY TOURS TABLE
-- ════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `day_tours` (
    `id`                  BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name`                VARCHAR(150)       NOT NULL,
    `slug`                VARCHAR(180)       NOT NULL,
    `excerpt`             VARCHAR(500)       NOT NULL DEFAULT '',
    `intro`               LONGTEXT           NULL,
    `highlights`          LONGTEXT           NULL,
    `itinerary`           LONGTEXT           NULL,
    `includes`            LONGTEXT           NULL,
    `excludes`            LONGTEXT           NULL,
    `meeting_point`       VARCHAR(255)       NULL DEFAULT '' COMMENT 'Default pickup location/hotel area',
    `duration_hours`      DECIMAL(4,1)       NULL DEFAULT 8.0 COMMENT 'Typical duration in hours (e.g. 8.0, 10.5)',
    `start_time`          TIME               NULL DEFAULT '08:00:00' COMMENT 'Typical start time',
    `price_from`          DECIMAL(10,2)      NULL DEFAULT 0.00,
    `price_to`            DECIMAL(10,2)      NULL DEFAULT 0.00,
    `price_currency`      CHAR(3)            NOT NULL DEFAULT 'USD',
    `price_unit`          VARCHAR(80)        NULL DEFAULT 'per person (min 2 pax)' COMMENT 'Display unit label',
    `vehicle_type`        VARCHAR(80)        NULL DEFAULT 'Air-conditioned car/van' COMMENT 'Standard vehicle description',
    `max_group_size`      SMALLINT UNSIGNED  NULL DEFAULT 6 COMMENT 'Max passengers per vehicle',
    `difficulty`          ENUM('easy','moderate','active') NOT NULL DEFAULT 'easy',
    `best_time`           VARCHAR(120)       NULL DEFAULT '' COMMENT 'Human-readable best months e.g. Nov–Apr',
    `best_time_json`      LONGTEXT           NULL COMMENT 'JSON: [{month,rating}x12] for monthly strip UI',
    `faq_json`            LONGTEXT           NULL,
    `hero_image`          VARCHAR(255)       NULL DEFAULT '' COMMENT 'Stored path to hero image',
    `og_image`            VARCHAR(255)       NULL DEFAULT '' COMMENT 'Stored path to OG/social image',
    `meta_title`          VARCHAR(255)       NULL DEFAULT '',
    `meta_description`    VARCHAR(320)       NULL DEFAULT '',
    `canonical_url`       VARCHAR(255)       NULL DEFAULT '',
    `is_featured`         TINYINT(1)         NOT NULL DEFAULT 0,
    `sort_order`          INT                NOT NULL DEFAULT 0,
    `is_published`        TINYINT(1)         NOT NULL DEFAULT 1,
    `created_at`          TIMESTAMP          NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP          NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_day_tours_slug` (`slug`),
    KEY `idx_day_tours_published` (`is_published`, `is_featured`, `sort_order`, `name`),
    KEY `idx_day_tours_difficulty` (`difficulty`),
    KEY `idx_day_tours_price` (`price_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 2. DAY TOUR GALLERY
-- ════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `day_tour_gallery` (
    `id`              BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `day_tour_id`     BIGINT UNSIGNED    NOT NULL,
    `image_path`      VARCHAR(255)       NOT NULL,
    `alt_text`        VARCHAR(255)       NULL DEFAULT '',
    `sort_order`      INT                NOT NULL DEFAULT 0,
    `created_at`      TIMESTAMP          NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_day_tour_gallery_tour` (`day_tour_id`, `sort_order`),
    CONSTRAINT `fk_day_tour_gallery_tour`
        FOREIGN KEY (`day_tour_id`) REFERENCES `day_tours` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 3. DAY TOUR ↔ DESTINATION (Many-to-Many)
-- ════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `day_tour_destination` (
    `day_tour_id`     BIGINT UNSIGNED    NOT NULL,
    `destination_id`  BIGINT UNSIGNED    NOT NULL,
    `sort_order`      INT                NOT NULL DEFAULT 0,
    PRIMARY KEY (`day_tour_id`, `destination_id`),
    KEY `idx_day_tour_dest_dest` (`destination_id`),
    CONSTRAINT `fk_day_tour_dest_tour`
        FOREIGN KEY (`day_tour_id`) REFERENCES `day_tours` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_day_tour_dest_dest`
        FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 4. DAY TOUR ↔ ITINERARY (Cross-sell: which multi-day itineraries include this day tour)
-- ════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `day_tour_itinerary` (
    `day_tour_id`     BIGINT UNSIGNED    NOT NULL,
    `itinerary_id`    BIGINT UNSIGNED    NOT NULL,
    `day_number`      TINYINT UNSIGNED   NULL DEFAULT NULL COMMENT 'Which day of the itinerary this tour typically falls on',
    `sort_order`      INT                NOT NULL DEFAULT 0,
    PRIMARY KEY (`day_tour_id`, `itinerary_id`),
    KEY `idx_day_tour_itin_itin` (`itinerary_id`),
    CONSTRAINT `fk_day_tour_itin_tour`
        FOREIGN KEY (`day_tour_id`) REFERENCES `day_tours` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_day_tour_itin_itin`
        FOREIGN KEY (`itinerary_id`) REFERENCES `itineraries` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 5. DAY TOUR ↔ EXPERIENCE (Cross-sell: related experiences)
-- ════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `day_tour_experience` (
    `day_tour_id`     BIGINT UNSIGNED    NOT NULL,
    `experience_id`   BIGINT UNSIGNED    NOT NULL,
    `sort_order`      INT                NOT NULL DEFAULT 0,
    PRIMARY KEY (`day_tour_id`, `experience_id`),
    KEY `idx_day_tour_exp_exp` (`experience_id`),
    CONSTRAINT `fk_day_tour_exp_tour`
        FOREIGN KEY (`day_tour_id`) REFERENCES `day_tours` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_day_tour_exp_exp`
        FOREIGN KEY (`experience_id`) REFERENCES `experiences` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 6. INDEX HELPERS (idempotent)
-- ════════════════════════════════════════════════════════════

SET @sql = NULL;
SELECT COUNT(*) INTO @sql FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'day_tours' AND INDEX_NAME = 'idx_day_tours_slug_pub';
SET @sql = IF(@sql = 0, 'ALTER TABLE `day_tours` ADD INDEX `idx_day_tours_slug_pub` (`slug`, `is_published`)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = NULL;
SELECT COUNT(*) INTO @sql FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'day_tours' AND INDEX_NAME = 'idx_day_tours_featured';
SET @sql = IF(@sql = 0, 'ALTER TABLE `day_tours` ADD INDEX `idx_day_tours_featured` (`is_featured`, `is_published`, `sort_order`)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ════════════════════════════════════════════════════════════
-- 7. SEED DATA: 10 Most Popular Day Tours
-- ════════════════════════════════════════════════════════════

INSERT INTO `day_tours` (
    `name`, `slug`, `excerpt`, `intro`, `highlights`, `itinerary`, `includes`, `excludes`,
    `meeting_point`, `duration_hours`, `start_time`,
    `price_from`, `price_to`, `price_currency`, `price_unit`,
    `vehicle_type`, `max_group_size`, `difficulty`, `best_time`,
    `best_time_json`, `faq_json`,
    `hero_image`, `og_image`,
    `meta_title`, `meta_description`,
    `is_featured`, `sort_order`, `is_published`
) VALUES

-- 1. Sigiriya & Dambulla Day Tour
(
    'Sigiriya & Dambulla Day Tour',
    'sigiriya-dambulla-day-tour',
    'Climb the iconic Sigiriya Rock Fortress and explore the ancient Dambulla Cave Temple in one unforgettable day with your private chauffeur-guide.',
    '<p>Experience two UNESCO World Heritage Sites in a single day. Your private chauffeur-guide picks you up from your hotel in Kandy, Habarana, Sigiriya, or Dambulla and takes you on a journey through 1,500 years of Sri Lankan history. Climb the 200-meter Sigiriya Lion Rock for breathtaking views and frescoes, then descend to explore the golden Buddha statues and murals of the Dambulla Cave Temple complex.</p>',
    '<ul><li>Private chauffeur-guide with air-conditioned vehicle</li><li>Sigiriya Rock Fortress entrance and climb (UNESCO)</li><li>Dambulla Cave Temple entrance (UNESCO)</li><li>Hotel pickup and drop-off (Kandy/Habarana/Sigiriya/Dambulla)</li><li>Bottled water and refreshments</li><li>Flexible pacing — linger at viewpoints or move faster</li></ul>',
    '<p><strong>07:00</strong> — Pickup from your hotel in Kandy, Habarana, Sigiriya, or Dambulla</p><p><strong>09:00</strong> — Arrive at Sigiriya Rock Fortress. Begin the climb (1.5–2 hours at your pace) with your guide sharing history of King Kashyapa, the frescoes, mirror wall, and lion paws.</p><p><strong>11:30</strong> — Descend and visit the Sigiriya Museum (optional) or enjoy a coconut water at the base.</p><p><strong>12:00</strong> — Drive to Dambulla (20 min). Lunch at a local restaurant overlooking the caves (own expense, we recommend options).</p><p><strong>13:30</strong> — Explore Dambulla Cave Temple: 5 caves, 150+ Buddha statues, 2,100 sq m of murals spanning 2,000 years.</p><p><strong>15:00</strong> — Depart for hotel drop-off.</p><p><strong>16:00–17:00</strong> — Return to your hotel.</p>',
    '<ul><li>Private air-conditioned vehicle with chauffeur-guide</li><li>Hotel pickup and drop-off (Kandy/Habarana/Sigiriya/Dambulla)</li><li>Sigiriya Rock Fortress entrance fee</li><li>Dambulla Cave Temple entrance fee</li><li>Bottled water throughout the day</li><li>All government taxes</li></ul>',
    '<ul><li>Meals and beverages (unless specified)</li><li>Personal expenses and souvenirs</li><li>Tips for guide and driver (appreciated, not expected)</li><li>Optional Sigiriya Museum entry</li></ul>',
    'Your hotel in Kandy, Habarana, Sigiriya, or Dambulla',
    10.0, '07:00:00',
    85.00, 140.00, 'USD', 'per person (min 2 pax)',
    'Air-conditioned sedan/SUV (1–3 pax) or van (4–6 pax)', 6, 'moderate', 'Year-round (best Nov–Apr)',
    '[{"month":"Jan","rating":"great"},{"month":"Feb","rating":"great"},{"month":"Mar","rating":"great"},{"month":"Apr","rating":"great"},{"month":"May","rating":"okay"},{"month":"Jun","rating":"okay"},{"month":"Jul","rating":"okay"},{"month":"Aug","rating":"okay"},{"month":"Sep","rating":"okay"},{"month":"Oct","rating":"okay"},{"month":"Nov","rating":"great"},{"month":"Dec","rating":"great"}]',
    '[{"question":"How difficult is the Sigiriya climb?","answer":"About 1,200 steps to the summit. Moderate fitness needed; take 1.5–2 hours at a relaxed pace. Handrails and rest points exist. Not suitable for severe mobility issues."},{"question":"What should I wear?","answer":"Light, breathable clothing; sturdy walking shoes; hat and sunscreen. Shoulders/knees covered for Dambulla temple."},{"question":"Can you pick up from Colombo?","answer":"Yes, with early start (5:30 AM) and supplement. Contact us for a custom quote."}]',
    'uploads/day-tours/sigiriya-dambulla-hero.jpg', 'uploads/day-tours/sigiriya-dambulla-og.jpg',
    'Sigiriya and Dambulla Day Tour | UNESCO Sites from Kandy/Habarana',
    'Private day tour to Sigiriya Rock Fortress and Dambulla Cave Temple from Kandy, Habarana, Sigiriya or Dambulla. UNESCO sites, chauffeur-guide, flexible pace. From $85pp.',
    1, 1, 1
),

-- 2. Kandy City Day Tour
(
    'Kandy City Highlights Day Tour',
    'kandy-city-day-tour',
    'Discover Sri Lanka\'s cultural capital — Temple of the Tooth, Royal Botanical Gardens, Kandy Lake, and a cultural dance show — all in one seamless private day tour.',
    '<p>Kandy, the last royal capital of Sri Lanka, sits amid misty hills and tea country. This private day tour covers the essential highlights: the sacred Temple of the Tooth Relic, the sprawling Peradeniya Botanical Gardens, a scenic drive around Kandy Lake, the gem museum, and an optional Kandyan cultural dance performance. Perfect as a day trip from Colombo (3 hrs each way) or as a relaxed local experience if you\'re already staying in Kandy.</p>',
    '<ul><li>Temple of the Sacred Tooth Relic (UNESCO)</li><li>Royal Botanical Gardens, Peradeniya</li><li>Kandy Lake drive and viewpoint</li><li>Gem museum and wood carving workshop visit</li><li>Optional: Kandyan cultural dance show (evening)</li><li>Private chauffeur-guide, hotel pickup and drop-off</li></ul>',
    '<p><strong>From Colombo (06:00 pickup):</strong></p><p>06:00 — Depart Colombo via Kandy road (scenic, 3 hrs).</p><p>09:30 — Arrive Kandy. Visit Temple of the Tooth for morning pooja (ceremony).</p><p>11:00 — Peradeniya Botanical Gardens (147 acres, orchids, palms, avenue of royal palms).</p><p>13:00 — Lunch by the Mahaweli River (own expense, we recommend spots).</p><p>14:30 — Kandy Lake viewpoint, gem museum, wood carving center.</p><p>16:30 — Optional cultural dance show (1 hr) or begin return.</p><p>20:00 — Arrive Colombo.</p><p><strong>From Kandy hotel (08:30 start):</strong> Same sites, relaxed pace, return by 17:00.</p>',
    '<ul><li>Private A/C vehicle with chauffeur-guide</li><li>Hotel pickup and drop-off (Colombo, Kandy, or Nuwara Eliya areas)</li><li>Temple of the Tooth entrance fee</li><li>Peradeniya Botanical Gardens entrance fee</li><li>Bottled water</li></ul>',
    '<ul><li>Meals</li><li>Cultural dance show ticket ($10–15 pp)</li><li>Personal expenses and tips</li></ul>',
    'Your hotel in Colombo, Kandy, or Nuwara Eliya area',
    12.0, '06:00:00',
    95.00, 160.00, 'USD', 'per person (min 2 pax)',
    'Air-conditioned sedan/SUV (1–3 pax) or van (4–6 pax)', 6, 'easy', 'Year-round (Jul–Aug Esala Perahera festival period busy)',
    '[{"month":"Jan","rating":"great"},{"month":"Feb","rating":"great"},{"month":"Mar","rating":"great"},{"month":"Apr","rating":"great"},{"month":"May","rating":"okay"},{"month":"Jun","rating":"okay"},{"month":"Jul","rating":"great"},{"month":"Aug","rating":"great"},{"month":"Sep","rating":"okay"},{"month":"Oct","rating":"okay"},{"month":"Nov","rating":"great"},{"month":"Dec","rating":"great"}]',
    '[{"question":"Is the Temple of the Tooth busy?","answer":"Mornings (9–10 AM) are busiest with pilgrims. We time visits for quieter windows. Dress modestly (shoulders/knees covered). Shoes removed at entrance."},{"question":"Can I combine with a tea factory visit?","answer":"Yes — on the return from Colombo we can stop at a tea factory near Ramboda. Add 1 hour. Let us know when booking."}]',
    'uploads/day-tours/kandy-city-hero.jpg', 'uploads/day-tours/kandy-city-og.jpg',
    'Kandy Day Tour | Temple of the Tooth, Botanical Gardens, Cultural Show',
    'Private Kandy day tour from Colombo or Kandy: Temple of the Tooth, Peradeniya Gardens, cultural dance. Chauffeur-guide, flexible pace. From $95pp.',
    1, 2, 1
),

-- 3. Yala National Park Safari Day Tour
(
    'Yala National Park Safari Day Tour',
    'yala-safari-day-tour',
    'Full-day private safari in Yala National Park — Sri Lanka\'s premier leopard destination. Morning or afternoon game drive with expert naturalist tracker.',
    '<p>Yala is world-renowned for its leopard density. This private day tour gets you into the park with a dedicated 4x4 safari jeep and an expert naturalist tracker who reads pugmarks, alarm calls, and terrain to maximize sightings. Choose a full day (morning + afternoon drives with picnic lunch) or a half-day morning/afternoon drive. Pickup from Ella, Tissamaharama, Kataragama, Mirissa, or Weligama.</p>',
    '<ul><li>Private 4x4 safari jeep (max 6 pax) — not shared</li><li>Expert naturalist tracker (certified)</li><li>Yala National Park entrance fees included</li><li>Morning drive (6–10 AM) OR afternoon drive (2–6 PM) OR full day (both)</li><li>Picnic breakfast/lunch on full-day option</li><li>Hotel pickup and drop-off (Ella/Tissa/Mirissa/Weligama)</li></ul>',
    '<p><strong>Full-Day Option (recommended):</strong></p><p>05:00 — Pickup, enter park at 6:00 AM opening.</p><p>06:00–10:00 — Morning game drive (leopards, elephants, sloth bears, crocs, birds).</p><p>10:00 — Picnic breakfast inside park (included).</p><p>10:30–13:30 — Rest at lodge/beach near park (own arrangement) or continue drive.</p><p>14:00–18:00 — Afternoon drive (different zones, golden light for photography).</p><p>18:30 — Exit park, return to hotel.</p><p><strong>Half-Day Morning:</strong> 05:00 pickup, 06:00–10:00 drive, return by 11:00.</p><p><strong>Half-Day Afternoon:</strong> 13:00 pickup, 14:00–18:00 drive, return by 19:00.</p>',
    '<ul><li>Private 4x4 safari jeep (max 6)</li><li>Expert naturalist tracker</li><li>Yala National Park entrance fees</li><li>Hotel pickup and drop-off</li><li>Picnic breakfast (full day) or water/snacks (half day)</li></ul>',
    '<ul><li>Meals beyond picnic breakfast</li><li>Accommodation near park</li><li>Tips for tracker and driver</li></ul>',
    'Your hotel in Ella, Tissamaharama, Kataragama, Mirissa, or Weligama',
    12.0, '05:00:00',
    180.00, 320.00, 'USD', 'per person (min 2 pax)',
    'Private 4x4 safari jeep (max 6 pax)', 6, 'easy', 'Best Feb–Jul (dry season); park closes Sep–Oct',
    '[{"month":"Jan","rating":"okay"},{"month":"Feb","rating":"great"},{"month":"Mar","rating":"great"},{"month":"Apr","rating":"great"},{"month":"May","rating":"great"},{"month":"Jun","rating":"great"},{"month":"Jul","rating":"great"},{"month":"Aug","rating":"okay"},{"month":"Sep","rating":"avoid"},{"month":"Oct","rating":"avoid"},{"month":"Nov","rating":"okay"},{"month":"Dec","rating":"okay"}]',
    '[{"question":"Will I see a leopard?","answer":"Yala has the world\'s highest leopard density, but sightings aren\'t guaranteed. Morning drives have ~60–70% success rate with our trackers. Full day maximizes chances."},{"question":"Is it safe?","answer":"Yes — you remain in the jeep at all times. Trackers are certified and follow park rules. No walking in the park."},{"question":"Can children join?","answer":"Yes, all ages. Under 6 free (on parent\'s lap). Jeeps have high sides; keep arms inside."}]',
    'uploads/day-tours/yala-safari-hero.jpg', 'uploads/day-tours/yala-safari-og.jpg',
    'Yala Safari Day Tour | Private Leopard Safari from Ella/Mirissa/Tissa',
    'Private Yala National Park safari day tour with expert tracker. Morning, afternoon, or full-day 4x4 jeep. Pickup from Ella, Tissa, Mirissa, Weligama. From $180pp.',
    1, 3, 1
),

-- 4. Galle Fort & South Coast Day Tour
(
    'Galle Fort and South Coast Day Tour',
    'galle-fort-south-coast-day-tour',
    'Explore the UNESCO-listed Galle Fort, stilt fishermen, a turtle hatchery, and the scenic south coast road — all in one relaxed private day tour from Colombo or the south coast.',
    '<p>The southern expressway makes Galle an easy day trip from Colombo (1.5 hrs each way). This tour blends colonial history, coastal culture, and wildlife: wander the ramparts of Galle Fort, watch stilt fishermen at Weligama/Koggala, visit a sea turtle conservation hatchery, and enjoy a seafood lunch by the ocean. Perfect for couples, families, or solo travelers wanting a taste of the south without changing hotels.</p>',
    '<ul><li>Galle Fort UNESCO World Heritage Site (ramparts, Dutch church, lighthouse, boutiques)</li><li>Stilt fishermen at Weligama/Koggala (iconic photo stop)</li><li>Sea turtle hatchery visit (conservation-focused)</li><li>Jungle Beach or Unawatuna for a quick dip (optional)</li><li>Seafood lunch by the ocean (recommended spots)</li><li>Expressway return — no traffic stress</li></ul>',
    '<p><strong>From Colombo (07:00 pickup):</strong></p><p>07:00 — Depart Colombo via Southern Expressway.</p><p>08:30 — Arrive Galle Fort. Walking tour: Dutch Reformed Church, Lighthouse, Flag Rock, Maritime Museum, Pedlar Street cafés.</p><p>11:30 — Drive coastal road to Weligama: stilt fishermen (best mid-morning).</p><p>12:30 — Turtle hatchery at Habaraduwa/Koggala.</p><p>13:30 — Seafood lunch at a beachfront restaurant (own expense).</p><p>15:00 — Optional swim at Jungle Beach or Unawatuna.</p><p>16:00 — Return via expressway.</p><p>17:30 — Drop at Colombo hotel.</p><p><strong>From South Coast hotels:</strong> Reverse route, no expressway needed. Pickup 08:30, drop by 17:00.</p>',
    '<ul><li>Private A/C vehicle with chauffeur-guide</li><li>Hotel pickup and drop-off (Colombo or south coast)</li><li>Galle Fort entrance fees</li><li>Turtle hatchery visit</li><li>Bottled water</li></ul>',
    '<ul><li>Meals and seafood lunch</li><li>Personal expenses and tips</li><li>Optional activities (surfing, boat ride)</li></ul>',
    'Your hotel in Colombo, Bentota, Hikkaduwa, Mirissa, or Weligama',
    10.0, '07:00:00',
    95.00, 155.00, 'USD', 'per person (min 2 pax)',
    'Air-conditioned sedan/SUV (1–3 pax) or van (4–6 pax)', 6, 'easy', 'Year-round (Dec–Apr sunniest; May–Oct short showers)',
    '[{"month":"Jan","rating":"great"},{"month":"Feb","rating":"great"},{"month":"Mar","rating":"great"},{"month":"Apr","rating":"great"},{"month":"May","rating":"okay"},{"month":"Jun","rating":"okay"},{"month":"Jul","rating":"okay"},{"month":"Aug","rating":"okay"},{"month":"Sep","rating":"okay"},{"month":"Oct","rating":"okay"},{"month":"Nov","rating":"great"},{"month":"Dec","rating":"great"}]',
    '[{"question":"Is Galle Fort walkable?","answer":"Yes — flat, compact, and walkable in 2–3 hours. Wear comfortable shoes; cobblestones can be uneven."},{"question":"Are the stilt fishermen authentic?","answer":"Most are local fishermen, though some pose for photos. The tradition is real, passed down generations. A small tip (200–500 LKR) is appreciated."}]',
    'uploads/day-tours/galle-fort-hero.jpg', 'uploads/day-tours/galle-fort-og.jpg',
    'Galle Fort Day Tour | South Coast, Stilt Fishermen, Turtle Hatchery',
    'Private Galle Fort and south coast day tour from Colombo or south coast hotels. UNESCO fort, stilt fishermen, turtle hatchery, seafood lunch. From $95pp.',
    1, 4, 1
),

-- 5. Nuwara Eliya Tea Country Day Tour
(
    'Nuwara Eliya Tea Country Day Tour',
    'nuwara-eliya-tea-country-day-tour',
    'Wander Sri Lanka\'s misty hill country: tea plantations, working tea factories, cascading waterfalls, and the colonial charm of Nuwara Eliya — "Little England" in the mountains.',
    '<p>Nuwara Eliya sits at 1,900 meters above sea level, cooled by mountain air and surrounded by emerald tea fields. This day tour takes you from Kandy or Ella through Ramboda\'s famous tunnel pass, past tea estates clinging to hillsides, into a working tea factory where you\'ll see leaf-to-cup processing, and on to cascading waterfalls like Ramboda or Devon. Lunch at a hill-country restaurant, then a walk around Gregory Lake or the town\'s colonial landmarks.</p>',
    '<ul><li>Scenic drive through Ramboda Tunnel and tea country</li><li>Working tea factory tour with tasting</li><li>Tea plantation walk (green tea, orthodox processing)</li><li>Ramboda Falls or Devon Falls viewpoint</li><li>Gregory Lake and colonial Nuwara Eliya town</li><li>Lunch at a hill-country restaurant (recommended)</li></ul>',
    '<p><strong>From Kandy (07:00 pickup):</strong></p><p>07:00 — Depart Kandy via A5 through rubber/teak forests.</p><p>08:30 — Ramboda Tunnel viewpoint and short walk.</p><p>09:30 — Tea factory tour (Mackwoods or similar): withering, rolling, drying, sorting. Free tasting session.</p><p>11:30 — Tea plantation walk; photo stops among tea pluckers (with permission).</p><p>12:30 — Lunch at a local hill-country restaurant.</p><p>14:00 — Ramboda Falls (109m) viewpoint, or Devon Falls if preferred.</p><p>15:30 — Gregory Lake boat ride (optional) or stroll around Nuwara Eliya town: post office, racecourse, English gardens.</p><p>17:00 — Return to Kandy or continue to Ella.</p><p><strong>From Ella:</strong> Reverse route, shorter drive (2.5 hrs).</p>',
    '<ul><li>Private A/C vehicle with chauffeur-guide</li><li>Tea factory tour and tasting</li><li>Hotel pickup and drop-off</li><li>Bottled water</li></ul>',
    '<ul><li>Meals (lunch recommended at 8,000–12,000 LKR pp)</li><li>Tips for factory staff/guide</li><li>Personal expenses</li></ul>',
    'Your hotel in Kandy, Ella, or Nuwara Eliya',
    9.0, '07:00:00',
    110.00, 175.00, 'USD', 'per person (min 2 pax)',
    'Air-conditioned sedan/SUV (1–3 pax) or van (4–6 pax)', 6, 'easy', 'Year-round (Dec–Apr clearest views; May–Oct misty but atmospheric)',
    '[{"month":"Jan","rating":"great"},{"month":"Feb","rating":"great"},{"month":"Mar","rating":"great"},{"month":"Apr","rating":"great"},{"month":"May","rating":"okay"},{"month":"Jun","rating":"okay"},{"month":"Jul","rating":"okay"},{"month":"Aug","rating":"okay"},{"month":"Sep","rating":"okay"},{"month":"Oct","rating":"okay"},{"month":"Nov","rating":"great"},{"month":"Dec","rating":"great"}]',
    '[{"question":"Is tea factory tour interesting?","answer":"Yes — most factories run 45–60 min tours showing orthodox processing. Tasting included. Best at Mackwoods, Damro, or Dilmah."},{"question":"Can we visit a tea plantation with pluckers?","answer":"We arrange photo stops with permission. Respectful distance. Some estates offer short walks with a supervisor."}]',
    'uploads/day-tours/nuwara-eliya-hero.jpg', 'uploads/day-tours/nuwara-eliya-og.jpg',
    'Nuwara Eliya Tea Country Day Tour | Tea Factory, Waterfalls, Hill Country',
    'Private Nuwara Eliya day tour from Kandy or Ella: tea factory tour, plantation walk, waterfalls, Gregory Lake. From $110pp.',
    1, 5, 1
),

-- 6. Ella to Kandy via Little Adam\'s Peak & Nine Arch Bridge Day Tour
(
    'Ella to Kandy via Little Adam\'s Peak and Nine Arch Bridge',
    'ella-kandy-adams-peak-day-tour',
    'Hike Little Adam\'s Peak, walk the iconic Nine Arch Bridge, visit a spice garden, and travel from Ella to Kandy — all in one active, scenic day tour.',
    '<p>Ella is Sri Lanka\'s backpacker-meets-luxury hill-country hub, and this day tour captures its best: a sunrise hike up Little Adam\'s Peak (1,200 steps, panoramic views), the photogenic Nine Arch Bridge, a working spice garden near Kegalle, and a descent into Kandy through the misty Ramboda pass. Best for active travelers who want both nature and culture in a single transfer day.</p>',
    '<ul><li>Little Adam\'s Peak hike (1,200 steps, 1.5 hrs return)</li><li>Nine Arch Bridge walk (iconic railway viaduct)</li><li>Spice garden tour near Kegalle (cinnamon, cardamom, vanilla)</li><li>Ramboda Tunnel viewpoint en route to Kandy</li><li>Scenic train-era railway track walk</li><li>Hotel pickup in Ella, drop-off in Kandy (or reverse)</li></ul>',
    '<p><strong>Ella to Kandy (07:00 pickup in Ella):</strong></p><p>07:00 — Pickup in Ella. Short drive to Little Adam\'s Peak trailhead.</p><p>07:30 — Hike Little Adam\'s Peak (1,200 steps). 45 min up, panoramic views of Ella Gap and sunrise (if early).</p><p>09:00 — Descend, walk to Nine Arch Bridge (20 min through forest). Best light 9–10 AM.</p><p>10:00 — Drive through Demodara loop (world\'s only 360-degree railway loop).</p><p>11:30 — Spice garden near Kegalle: cinnamon, pepper, cardamom, cocoa. 45 min tour.</p><p>13:00 — Lunch at a local restaurant near spice garden.</p><p>14:30 — Ramboda Tunnel viewpoint, tea factory stop (optional).</p><p>16:30 — Arrive Kandy.</p><p><strong>Kandy to Ella (reverse):</strong> Pickup 06:30, arrive Ella by 16:00.</p>',
    '<ul><li>Private A/C vehicle with chauffeur-guide</li><li>Little Adam\'s Peak hike guide</li><li>Spice garden tour</li><li>Hotel pickup and drop-off</li><li>Bottled water</li></ul>',
    '<ul><li>Meals</li><li>Tea factory entry (if not included)</li><li>Tips</li></ul>',
    'Your hotel in Ella or Kandy',
    9.0, '07:00:00',
    115.00, 185.00, 'USD', 'per person (min 2 pax)',
    'Air-conditioned sedan/SUV (1–3 pax) or van (4–6 pax)', 6, 'active', 'Year-round (Dec–Apr best views; May–Oct occasional mist)',
    '[{"month":"Jan","rating":"great"},{"month":"Feb","rating":"great"},{"month":"Mar","rating":"great"},{"month":"Apr","rating":"great"},{"month":"May","rating":"okay"},{"month":"Jun","rating":"okay"},{"month":"Jul","rating":"okay"},{"month":"Aug","rating":"okay"},{"month":"Sep","rating":"okay"},{"month":"Oct","rating":"okay"},{"month":"Nov","rating":"great"},{"month":"Dec","rating":"great"}]',
    '[{"question":"How hard is the Little Adam\'s Peak hike?","answer":"Moderate — 1,200 steps up, well-maintained path, 45 min to summit. Stroller-friendly? No. Good shoes recommended."},{"question":"Is the Nine Arch Bridge easy to reach?","answer":"Yes — 20 min walk from Ella town or 5 min tuk-tuk. Best light is 9–10 AM for photos."}]',
    'uploads/day-tours/ella-kandy-hero.jpg', 'uploads/day-tours/ella-kandy-og.jpg',
    'Ella to Kandy Day Tour | Little Adam\'s Peak, Nine Arch Bridge, Spice Garden',
    'Private day tour from Ella to Kandy or Kandy to Ella: Little Adam\'s Peak hike, Nine Arch Bridge, spice garden. Active day tour. From $115pp.',
    1, 6, 1
),

-- 7. Anuradhapura Ancient City Day Tour
(
    'Anuradhapura Ancient City Day Tour',
    'anuradhapura-ancient-city-day-tour',
    'Walk among 2,000-year-old dagobas, sacred Bodhi trees, and ancient ruins at Anuradhapura — Sri Lanka\'s first capital and a UNESCO World Heritage Site.',
    '<p>Anuradhapura is the beating heart of Sri Lankan Buddhist heritage. Founded in the 4th century BC, it was the island\'s first great capital and remains one of the world\'s most important ancient Buddhist cities. This private day tour covers the Sri Maha Bodhi (the world\'s oldest documented tree), the towering Ruwanweliseya Dagoba, the moonstone-guarded Jetavanaramaya, and the ancient hospital complex. Pickup from Colombo, Negombo, or Polonnaruwa.</p>',
    '<ul><li>Sri Maha Bodhi (world\'s oldest documented tree, 2,300+ years)</li><li>Ruwanweliseya Dagoba (100m white bell-shaped stupa)</li><li>Jetavanaramaya (once the 3rd tallest structure in the world)</li><li>Ancient stone pillars, moonstones, and guardstones</li><li>Isurumuniya rock temple (ancient lovers carving)</li><li>Private chauffeur-guide, hotel pickup and drop-off</li></ul>',
    '<p><strong>From Colombo/Negombo (07:00 pickup):</strong></p><p>07:00 — Depart Colombo via Puttalam or Kurunegala road (3 hrs).</p><p>10:00 — Arrive Anuradhapura. Visit Sri Maha Bodhi (sacred Bodhi tree, 2,300+ years old).</p><p>11:00 — Ruwanweliseya Dagoba and Thuparama (first dagoba in Sri Lanka).</p><p>12:30 — Lunch at a local restaurant (own expense).</p><p>13:30 — Jetavanaramaya and Abhayagiri monastery complex.</p><p>14:30 — Isurumuniya rock temple (famous ancient lovers carving).</p><p>15:30 — Thanthirimale (optional, 30 min detour) or begin return.</p><p>17:00 — Return to Colombo/Negombo.</p><p><strong>From Polonnaruwa:</strong> Pickup 08:00, return by 17:00.</p>',
    '<ul><li>Private A/C vehicle with chauffeur-guide</li><li>Hotel pickup and drop-off</li><li>Anuradhapura ancient city entrance fee</li><li>Bottled water</li></ul>',
    '<ul><li>Meals</li><li>Personal expenses and tips</li></ul>',
    'Your hotel in Colombo, Negombo, or Polonnaruwa',
    10.0, '07:00:00',
    95.00, 155.00, 'USD', 'per person (min 2 pax)',
    'Air-conditioned sedan/SUV (1–3 pax) or van (4–6 pax)', 6, 'easy', 'Year-round (May–Aug hottest; Nov–Apr most pleasant)',
    '[{"month":"Jan","rating":"great"},{"month":"Feb","rating":"great"},{"month":"Mar","rating":"great"},{"month":"Apr","rating":"okay"},{"month":"May","rating":"okay"},{"month":"Jun","rating":"okay"},{"month":"Jul","rating":"okay"},{"month":"Aug","rating":"okay"},{"month":"Sep","rating":"okay"},{"month":"Oct","rating":"okay"},{"month":"Nov","rating":"great"},{"month":"Dec","rating":"great"}]',
    '[{"question":"Is Anuradhapura a religious site?","answer":"Yes — it\'s one of the world\'s oldest continuously inhabited Buddhist cities. Dress modestly (shoulders/knees covered). Remove shoes at temples."},{"question":"Can we combine with Mihintale?","answer":"Yes — Mihintale (birthplace of Buddhism in Sri Lanka) is 30 min away and adds 1.5–2 hours. Ask us to extend the tour."}]',
    'uploads/day-tours/anuradhapura-hero.jpg', 'uploads/day-tours/anuradhapura-og.jpg',
    'Anuradhapura Day Tour | Ancient Sacred City, Sri Maha Bodhi, Dagobas',
    'Private Anuradhapura ancient city day tour from Colombo or Negombo. UNESCO World Heritage Site, sacred Bodhi tree, dagobas, guided tour. From $95pp.',
    1, 7, 1
),

-- 8. Mirissa & Whale Watching Day Tour
(
    'Mirissa and Whale Watching Day Tour',
    'mirissa-whale-watching-day-tour',
    'Set sail from Mirissa Harbor for blue whale and sperm whale watching, then explore the golden beaches, secret beaches, and coconut palm riviera of Sri Lanka\'s south coast.',
    '<p>Mirissa is the whale-watching capital of Sri Lanka, with blue whales and sperm whales visible year-round just 10–15 nautical miles offshore. This private day tour combines a 3–4 hour whale-watching cruise (with guaranteed seat on a clean, well-maintained boat) with beach time at Mirissa\'s famous Coconut Tree Hill, the hidden Paradise Beach, and a fresh seafood lunch by the sand. Pickup from Weligama, Matara, or Tangalle.</p>',
    '<ul><li>Whale-watching cruise (blue whales, sperm whales, dolphins)</li><li>Mirissa Coconut Tree Hill viewpoint (iconic photo)</li><li>Paradise Beach or Secret Beach (quiet coves)</li><li>Fresh seafood lunch by the ocean (recommended)</li><li>Weligama stilt fishermen viewpoint</li><li>Hotel pickup and drop-off</li></ul>',
    '<p><strong>From Weligama/Mirissa (06:30 pickup):</strong></p><p>06:30 — Pickup, drive to Mirissa Harbor (10 min).</p><p>07:00 — Board whale-watching boat. 3–4 hr cruise (departure time varies by season).</p><p>11:00 — Return to harbor. Freshen up.</p><p>11:30 — Drive to Coconut Tree Hill for photos.</p><p>12:00 — Secret Beach or Paradise Beach: relax, swim, or walk.</p><p>13:00 — Seafood lunch at a beachfront shack or café.</p><p>14:30 — Optional: stilt fishermen at Weligama, or visit a sea turtle hatchery.</p><p>15:30 — Return to hotel.</p>',
    '<ul><li>Private A/C vehicle with chauffeur-guide</li><li>Whale-watching boat seat (shared boat, guaranteed spot)</li><li>Hotel pickup and drop-off</li><li>Bottled water and towels on boat</li></ul>',
    '<ul><li>Meals and seafood lunch</li><li>Personal expenses and tips</li><li>Snorkeling gear (available on request)</li></ul>',
    'Your hotel in Mirissa, Weligama, Matara, or Tangalle',
    9.0, '06:30:00',
    165.00, 280.00, 'USD', 'per person (min 2 pax)',
    'Air-conditioned sedan/SUV (1–3 pax) or van (4–6 pax)', 6, 'easy', 'Nov–Apr (whale season); Jun–Oct rough seas, limited departures',
    '[{"month":"Jan","rating":"great"},{"month":"Feb","rating":"great"},{"month":"Mar","rating":"great"},{"month":"Apr","rating":"great"},{"month":"May","rating":"okay"},{"month":"Jun","rating":"avoid"},{"month":"Jul","rating":"avoid"},{"month":"Aug","rating":"avoid"},{"month":"Sep","rating":"avoid"},{"month":"Oct","rating":"okay"},{"month":"Nov","rating":"great"},{"month":"Dec","rating":"great"}]',
    '[{"question":"Do we see whales?","answer":"Blue whales are present year-round, but Nov–Apr has the calmest seas and highest sighting rates (~90% on 2–3 trips). May–Oct is rougher."},{"question":"Is the boat safe?","answer":"Yes — all partner boats are SLTDA-registered, life jackets provided, first aid on board. Smaller boats calmer in morning."}]',
    'uploads/day-tours/mirissa-whale-hero.jpg', 'uploads/day-tours/mirissa-whale-og.jpg',
    'Mirissa Whale Watching Day Tour | Blue Whale Cruise, South Coast Beaches',
    'Private Mirissa whale watching day tour: blue whale and sperm whale cruise, Coconut Tree Hill, Secret Beach, seafood lunch. From $165pp.',
    1, 8, 1
),

-- 9. Udawalawe National Park Safari Day Tour
(
    'Udawalawe National Park Safari Day Tour',
    'udawalawe-safari-day-tour',
    'Spot elephants, water buffalo, crocodiles, and exotic birds in Udawalawe — Sri Lanka\'s best park for guaranteed elephant sightings with a private 4x4 jeep and tracker.',
    '<p>Udawalawe National Park is Sri Lanka\'s elephant capital, with one of the highest densities of Asian elephants in Asia. Unlike Yala, elephant sightings here are near-guaranteed year-round. This private day tour uses a dedicated 4x4 safari jeep and expert tracker who knows the park\'s herds, waterholes, and leopard territories. Pickup from Tissamaharama, Hambantota, or Embilipitiya.</p>',
    '<ul><li>Private 4x4 safari jeep (max 6 pax, not shared)</li><li>Expert naturalist tracker</li><li>Udawalawe National Park entrance fees</li><li>Morning drive (6–10 AM) OR afternoon drive (2–6 PM)</li><li>Elephant Transit Home visit (optional, near park)</li><li>Hotel pickup and drop-off</li></ul>',
    '<p><strong>Morning Safari (recommended):</strong></p><p>05:00 — Pickup, enter park at 6:00 AM opening.</p><p>06:00–10:00 — Morning game drive. Elephant herds at waterholes, birdlife, crocs, buffalos, occasional leopard.</p><p>10:00 — Exit park. Optional Elephant Transit Home visit (baby elephants, rehabilitation center).</p><p>11:00 — Lunch at a local restaurant near the park.</p><p>12:30 — Return to hotel or extend with afternoon drive.</p><p><strong>Afternoon Safari:</strong> 13:00 pickup, 14:00–18:00 drive, return by 19:00.</p><p><strong>Full Day:</strong> Both drives with picnic lunch inside park.</p>',
    '<ul><li>Private 4x4 safari jeep (max 6)</li><li>Expert naturalist tracker</li><li>Udawalawe National Park entrance fees</li><li>Hotel pickup and drop-off</li><li>Bottled water</li></ul>',
    '<ul><li>Meals</li><li>Tips for tracker and driver</li><li>Personal expenses</li></ul>',
    'Your hotel in Tissamaharama, Hambantota, or Embilipitiya',
    8.0, '05:30:00',
    150.00, 260.00, 'USD', 'per person (min 2 pax)',
    'Private 4x4 safari jeep (max 6 pax)', 6, 'easy', 'Year-round (Dec–Mar driest; May–Sep still excellent)',
    '[{"month":"Jan","rating":"great"},{"month":"Feb","rating":"great"},{"month":"Mar","rating":"great"},{"month":"Apr","rating":"great"},{"month":"May","rating":"great"},{"month":"Jun","rating":"great"},{"month":"Jul","rating":"great"},{"month":"Aug","rating":"great"},{"month":"Sep","rating":"great"},{"month":"Oct","rating":"great"},{"month":"Nov","rating":"great"},{"month":"Dec","rating":"great"}]',
    '[{"question":"Are elephants guaranteed?","answer":"Yes — Udawalawe has one of the highest elephant densities in Asia. Morning drives have a 95%+ chance of seeing herds."},{"question":"Can we visit the Elephant Transit Home?","answer":"Yes — it\'s 10 min from the park entrance. Baby elephants are bottle-fed at 9 AM, 12 PM, 3 PM, 6 PM. We time the visit for a feeding."}]',
    'uploads/day-tours/udawalawe-hero.jpg', 'uploads/day-tours/udawalawe-og.jpg',
    'Udawalawe Safari Day Tour | Private Elephant Safari from Tissa/Hambantota',
    'Private Udawalawe National Park safari day tour with guaranteed elephant sightings. 4x4 jeep, expert tracker, Elephant Transit Home. From $150pp.',
    1, 9, 1
),

-- 10. Trincomalee & Nilaveli Day Tour
(
    'Trincomalee and Nilaveli Day Tour',
    'trincomalee-nilaveli-day-tour',
    'Discover Sri Lanka\'s east coast gem: the ancient Koneswaram Temple, pristine Nilaveli beaches, Pigeon Island snorkeling, and Dutch Fort — a relaxed coastal day tour.',
    '<p>Trincomalee (Trinco) is Sri Lanka\'s underrated east coast treasure — a deep-water harbor, colonial forts, Hindu temples perched on cliffs, and some of the island\'s whitest beaches. This private day tour covers the clifftop Koneswaram Temple (the Temple of the Thousand Pillars), the 17th-century Dutch Fort, a snorkeling or glass-bottom boat trip to Pigeon Island (coral reefs, sea turtles), and beach time at Nilaveli or Uppuveli. Pickup from Pasikudah, Batticaloa, or Trinco town.</p>',
    '<ul><li>Koneswaram Temple (clifftop Hindu temple, panoramic harbor views)</li><li>Dutch Fort, Trincomalee (17th-century, coastal ramparts)</li><li>Pigeon Island snorkeling or glass-bottom boat (coral, turtles, reef fish)</li><li>Nilaveli or Uppuveli beach time</li><li>Fredlanda Bay scenic drive</li><li>Hotel pickup and drop-off</li></ul>',
    '<p><strong>From Pasikudah/Batticaloa (08:00 pickup):</strong></p><p>08:00 — Depart Pasikudah via coastal road (1 hr).</p><p>09:00 — Koneswaram Temple: cliff-top Shiva temple, panoramic views of Trinco harbor.</p><p>10:00 — Dutch Fort walk: ramparts, cannons, lighthouse viewpoint.</p><p>11:00 — Drive to Nilaveli Beach.</p><p>11:30 — Snorkeling at Pigeon Island (30 min boat ride). Coral reefs, blacktip reef sharks, sea turtles.</p><p>13:30 — Lunch at a beachfront restaurant in Nilaveli.</p><p>14:30 — Beach time at Nilaveli or Uppuveli.</p><p>16:00 — Optional: hot springs at Kanniya (5 min detour).</p><p>17:00 — Return to hotel.</p><p><strong>From Trinco town:</strong> Pickup 09:00, no travel time needed.</p>',
    '<ul><li>Private A/C vehicle with chauffeur-guide</li><li>Pigeon Island snorkeling gear and boat</li><li>Hotel pickup and drop-off</li><li>Bottled water</li></ul>',
    '<ul><li>Meals</li><li>Tips</li><li>Personal expenses</li></ul>',
    'Your hotel in Trincomalee, Nilaveli, Uppuveli, or Pasikudah',
    9.0, '08:00:00',
    130.00, 210.00, 'USD', 'per person (min 2 pax)',
    'Air-conditioned sedan/SUV (1–3 pax) or van (4–6 pax)', 6, 'easy', 'May–Oct best (calm seas for snorkeling); Nov–Apr choppy, whale watching instead',
    '[{"month":"Jan","rating":"okay"},{"month":"Feb","rating":"okay"},{"month":"Mar","rating":"okay"},{"month":"Apr","rating":"okay"},{"month":"May","rating":"great"},{"month":"Jun","rating":"great"},{"month":"Jul","rating":"great"},{"month":"Aug","rating":"great"},{"month":"Sep","rating":"great"},{"month":"Oct","rating":"great"},{"month":"Nov","rating":"okay"},{"month":"Dec","rating":"okay"}]',
    '[{"question":"Is snorkeling safe?","answer":"Yes — Pigeon Island is a marine national park with shallow reefs. Life jackets provided. No swimming experience required for glass-bottom boat option."},{"question":"Can we combine with Pasikudah?","answer":"Yes — Pasikudah\'s shallow bay is a 40 min drive from Trinco. We can add 2–3 hours beach time. Extend the tour to a full day."}]',
    'uploads/day-tours/trincomalee-hero.jpg', 'uploads/day-tours/trincomalee-og.jpg',
    'Trincomalee Day Tour | Koneswaram Temple, Pigeon Island, Nilaveli Beach',
    'Private Trincomalee and Nilaveli day tour from east coast hotels: Koneswaram Temple, Dutch Fort, Pigeon Island snorkeling. From $130pp.',
    1, 10, 1
);

-- ════════════════════════════════════════════════════════════
-- 8. SAMPLE GALLERY IMAGES (optional enrichments for 2 tours)
-- ════════════════════════════════════════════════════════════

INSERT IGNORE INTO `day_tour_gallery` (`day_tour_id`, `image_path`, `alt_text`, `sort_order`) VALUES
(1, 'uploads/day-tours/sigiriya-frescoes.jpg', 'Sigiriya frescoes', 1),
(1, 'uploads/day-tours/sigiriya-view.jpg', 'Sigiriya summit view', 2),
(1, 'uploads/day-tours/dambulla-caves.jpg', 'Dambulla Cave Temple', 3),
(2, 'uploads/day-tours/kandy-temple-tooth.jpg', 'Temple of the Tooth Relic', 1),
(2, 'uploads/day-tours/kandy-botanical.jpg', 'Peradeniya Botanical Gardens', 2),
(2, 'uploads/day-tours/kandy-lake.jpg', 'Kandy Lake viewpoint', 3);

-- ════════════════════════════════════════════════════════════
-- 9. LINK TO DESTINATIONS (assume slugs exist)
-- ════════════════════════════════════════════════════════════

INSERT IGNORE INTO `day_tour_destination` (`day_tour_id`, `destination_id`, `sort_order`)
SELECT dt.id, d.id, 0
FROM `day_tours` dt
CROSS JOIN `destinations` d
WHERE dt.slug IN ('sigiriya-dambulla-day-tour', 'kandy-city-day-tour', 'yala-safari-day-tour', 'galle-fort-south-coast-day-tour')
  AND d.slug IN ('sigiriya', 'dambulla', 'kandy', 'yala', 'galle', 'ella', 'nuwara-eliya', 'trincomalee', 'anuradhapura', 'mirissa', 'minneriya')
LIMIT 50;

-- ════════════════════════════════════════════════════════════
-- 10. LINK TO ITINERARIES (sample cross-sells)
-- ════════════════════════════════════════════════════════════

INSERT IGNORE INTO `day_tour_itinerary` (`day_tour_id`, `itinerary_id`, `day_number`, `sort_order`)
SELECT dt.id, i.id, 1, 0
FROM `day_tours` dt
CROSS JOIN `itineraries` i
WHERE dt.slug IN ('sigiriya-dambulla-day-tour', 'kandy-city-day-tour', 'yala-safari-day-tour', 'galle-fort-south-coast-day-tour', 'anuradhapura-ancient-city-day-tour')
  AND i.days BETWEEN 3 AND 14
LIMIT 30;

-- ════════════════════════════════════════════════════════════
-- 11. LINK TO EXPERIENCES (sample cross-sells)
-- ════════════════════════════════════════════════════════════

INSERT IGNORE INTO `day_tour_experience` (`day_tour_id`, `experience_id`, `sort_order`)
SELECT dt.id, e.id, 0
FROM `day_tours` dt
CROSS JOIN `experiences` e
WHERE dt.slug IN ('sigiriya-dambulla-day-tour', 'kandy-city-day-tour', 'yala-safari-day-tour', 'galle-fort-south-coast-day-tour', 'mirissa-whale-watching-day-tour')
  AND e.is_published = 1
LIMIT 25;

SET FOREIGN_KEY_CHECKS = 1;
