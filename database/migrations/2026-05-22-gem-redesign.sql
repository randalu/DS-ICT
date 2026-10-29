-- ============================================================
-- ICT GEM REDESIGN — Database Migration
-- 2026-05-22
--
-- Covers all schema changes needed for:
--   1. Destinations  — map pins, region, emoji, best_time_json
--   2. Experiences   — mood/category system, day-arc JSON,
--                      itinerary_count denorm
--   3. Itineraries   — traveller_type, start/end region,
--                      best_months_json (already exists check)
--   4. Itinerary categories — theme_slug for mood filter
--   5. Experience categories (mood board)
--   6. Route wishlist sessions (server-side persistence)
--
-- Safe to re-run: all ALTERs use IF NOT EXISTS guards via
-- the ict_add_column_if_missing / ict_add_index_if_missing
-- stored procedures (created inline, dropped at end).
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Reusable helper procedures ────────────────────────────────────────────────

DROP PROCEDURE IF EXISTS ict_add_column_if_missing;
DROP PROCEDURE IF EXISTS ict_add_index_if_missing;

DELIMITER //

CREATE PROCEDURE ict_add_column_if_missing(
    IN p_table     VARCHAR(64),
    IN p_column    VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = p_table
          AND COLUMN_NAME  = p_column
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

CREATE PROCEDURE ict_add_index_if_missing(
    IN p_table      VARCHAR(64),
    IN p_index      VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = p_table
          AND INDEX_NAME   = p_index
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` ', p_definition
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

DELIMITER ;


-- ════════════════════════════════════════════════════════════
-- 1. DESTINATIONS
--    New columns needed for the interactive map component
--    and the card redesign.
-- ════════════════════════════════════════════════════════════

-- Map pin coordinates (SVG viewBox 0 0 220 345)
-- Stored as DECIMAL so the admin can set them precisely.
CALL ict_add_column_if_missing('destinations', 'map_x',
    'DECIMAL(6,2) NULL DEFAULT NULL COMMENT "SVG map pin X coordinate" AFTER `sort_order`');

CALL ict_add_column_if_missing('destinations', 'map_y',
    'DECIMAL(6,2) NULL DEFAULT NULL COMMENT "SVG map pin Y coordinate" AFTER `map_x`');

CALL ict_add_column_if_missing('destinations', 'map_r',
    'DECIMAL(4,1) NOT NULL DEFAULT 7.0 COMMENT "SVG pin radius (6-9 range)" AFTER `map_y`');

CALL ict_add_column_if_missing('destinations', 'map_initial',
    'VARCHAR(4) NULL DEFAULT NULL COMMENT "1-2 letter initial shown inside the SVG pin" AFTER `map_r`');

-- Region / area grouping for map filter pills and list filtering.
-- Enum keeps values controlled; admin UI offers a dropdown.
CALL ict_add_column_if_missing('destinations', 'region_slug',
    'VARCHAR(60) NULL DEFAULT NULL
     COMMENT "One of: cultural, hill, wildlife, south, east — drives map region filter"
     AFTER `map_initial`');

CALL ict_add_column_if_missing('destinations', 'region_label',
    'VARCHAR(120) NULL DEFAULT NULL
     COMMENT "Human-readable region name shown on map hover card e.g. Cultural Triangle"
     AFTER `region_slug`');

-- Emoji for the list panel thumbnail fallback (when no image set)
CALL ict_add_column_if_missing('destinations', 'emoji',
    'VARCHAR(8) NULL DEFAULT NULL
     COMMENT "Single emoji used as list item thumbnail fallback e.g. 🏔"
     AFTER `region_label`');

-- Card-level display fields
CALL ict_add_column_if_missing('destinations', 'stay_duration',
    'VARCHAR(80) NULL DEFAULT NULL
     COMMENT "Short label for card meta e.g. Half day, 1-2 nights"
     AFTER `recommended_stay`');

-- Structured best-time data for the 12-month colour strip on detail page.
-- JSON: { "months": [{"month":"Jan","rating":"great"},{"month":"Feb","rating":"okay"}, ...] }
-- Ratings: great | okay | avoid
CALL ict_add_column_if_missing('destinations', 'best_time_json',
    'LONGTEXT NULL DEFAULT NULL
     COMMENT "JSON: [{month,rating}x12] for the monthly colour-strip UI"
     AFTER `best_time`');

-- Route/driving notes structured data for the detail page.
-- JSON: [ { "from": "Colombo", "to": "Sigiriya", "duration": "3.5 hrs",
--           "road": "A1 then A6", "notes": "Comfortable on dual carriageway" } ]
CALL ict_add_column_if_missing('destinations', 'route_notes_json',
    'LONGTEXT NULL DEFAULT NULL
     COMMENT "JSON array of driving route notes for destination detail page"
     AFTER `best_time_json`');

-- Denormalised itinerary count — updated by a trigger (added below).
-- Used on cards and the map hover card without a JOIN.
CALL ict_add_column_if_missing('destinations', 'itinerary_count',
    'SMALLINT UNSIGNED NOT NULL DEFAULT 0
     COMMENT "Cached count of published itineraries linked to this destination"
     AFTER `is_published`');

-- Indexes
CALL ict_add_index_if_missing('destinations', 'idx_destinations_region',
    '(`region_slug`)');

CALL ict_add_index_if_missing('destinations', 'idx_destinations_map',
    '(`map_x`, `map_y`)');


-- ── Seed map coordinates for the 11 core destinations ────────────────────────
-- Only updates rows that already exist and have no map_x set yet.
-- Match is by slug — safe if slugs differ on your live DB; just skip.

UPDATE destinations SET
    map_x = 118, map_y = 108, map_r = 8.0, map_initial = 'S',
    region_slug = 'cultural', region_label = 'Cultural Triangle',
    emoji = '🏔', stay_duration = 'Half day'
WHERE slug = 'sigiriya' AND (map_x IS NULL OR map_x = 0);

UPDATE destinations SET
    map_x = 104, map_y = 122, map_r = 6.5, map_initial = 'D',
    region_slug = 'cultural', region_label = 'Cultural Triangle',
    emoji = '🪨', stay_duration = '2–3 hrs'
WHERE slug = 'dambulla' AND (map_x IS NULL OR map_x = 0);

UPDATE destinations SET
    map_x = 94, map_y = 76, map_r = 6.5, map_initial = 'A',
    region_slug = 'cultural', region_label = 'Cultural Triangle',
    emoji = '🏯', stay_duration = 'Full day'
WHERE slug = 'anuradhapura' AND (map_x IS NULL OR map_x = 0);

UPDATE destinations SET
    map_x = 100, map_y = 160, map_r = 7.0, map_initial = 'K',
    region_slug = 'hill', region_label = 'Hill Country',
    emoji = '🌿', stay_duration = '1 night'
WHERE slug = 'kandy' AND (map_x IS NULL OR map_x = 0);

UPDATE destinations SET
    map_x = 116, map_y = 202, map_r = 6.5, map_initial = 'E',
    region_slug = 'hill', region_label = 'Hill Country',
    emoji = '⛰', stay_duration = '1–2 nights'
WHERE slug = 'ella' AND (map_x IS NULL OR map_x = 0);

UPDATE destinations SET
    map_x = 100, map_y = 190, map_r = 6.0, map_initial = 'N',
    region_slug = 'hill', region_label = 'Hill Country',
    emoji = '🍃', stay_duration = '1 night'
WHERE slug IN ('nuwara-eliya','nuwara_eliya') AND (map_x IS NULL OR map_x = 0);

UPDATE destinations SET
    map_x = 130, map_y = 248, map_r = 6.5, map_initial = 'Y',
    region_slug = 'wildlife', region_label = 'Wildlife',
    emoji = '🐆', stay_duration = 'Full day safari'
WHERE slug = 'yala' AND (map_x IS NULL OR map_x = 0);

UPDATE destinations SET
    map_x = 118, map_y = 134, map_r = 6.0, map_initial = 'M',
    region_slug = 'wildlife', region_label = 'Wildlife',
    emoji = '🐘', stay_duration = 'Half day'
WHERE slug = 'minneriya' AND (map_x IS NULL OR map_x = 0);

UPDATE destinations SET
    map_x = 70, map_y = 272, map_r = 6.5, map_initial = 'G',
    region_slug = 'south', region_label = 'South Coast',
    emoji = '🏛', stay_duration = 'Half–1 day'
WHERE slug IN ('galle','galle-fort') AND (map_x IS NULL OR map_x = 0);

UPDATE destinations SET
    map_x = 90, map_y = 285, map_r = 5.5, map_initial = 'Mi',
    region_slug = 'south', region_label = 'South Coast',
    emoji = '🐋', stay_duration = '1–2 nights'
WHERE slug = 'mirissa' AND (map_x IS NULL OR map_x = 0);

UPDATE destinations SET
    map_x = 150, map_y = 128, map_r = 6.5, map_initial = 'T',
    region_slug = 'east', region_label = 'East Coast',
    emoji = '🌊', stay_duration = '1–2 nights'
WHERE slug = 'trincomalee' AND (map_x IS NULL OR map_x = 0);


-- ── Trigger: keep itinerary_count in sync ─────────────────────────────────────
-- Fires on destination_itinerary INSERT/DELETE.
-- Falls back gracefully if trigger already exists.

DROP TRIGGER IF EXISTS trg_dest_itin_count_insert;
DROP TRIGGER IF EXISTS trg_dest_itin_count_delete;

CREATE TRIGGER trg_dest_itin_count_insert
AFTER INSERT ON destination_itinerary
FOR EACH ROW
    UPDATE destinations d
    INNER JOIN itineraries i ON i.id = NEW.itinerary_id AND i.is_published = 1
    SET d.itinerary_count = (
        SELECT COUNT(*)
        FROM destination_itinerary di
        INNER JOIN itineraries ii ON ii.id = di.itinerary_id AND ii.is_published = 1
        WHERE di.destination_id = NEW.destination_id
    )
    WHERE d.id = NEW.destination_id;

CREATE TRIGGER trg_dest_itin_count_delete
AFTER DELETE ON destination_itinerary
FOR EACH ROW
    UPDATE destinations
    SET itinerary_count = (
        SELECT COUNT(*)
        FROM destination_itinerary di
        INNER JOIN itineraries ii ON ii.id = di.itinerary_id AND ii.is_published = 1
        WHERE di.destination_id = OLD.destination_id
    )
    WHERE id = OLD.destination_id;

-- Backfill itinerary_count for existing rows
UPDATE destinations d
SET d.itinerary_count = (
    SELECT COUNT(*)
    FROM destination_itinerary di
    INNER JOIN itineraries i ON i.id = di.itinerary_id AND i.is_published = 1
    WHERE di.destination_id = d.id
);


-- ════════════════════════════════════════════════════════════
-- 2. EXPERIENCES
--    Add mood/category system, day-arc content,
--    and denormalised counts for the listing page.
-- ════════════════════════════════════════════════════════════

-- ── 2a. Experience categories (mood board tiles) ──────────────────────────────
-- One experience can belong to one primary category (mood).
-- A separate pivot allows multiple moods per experience if needed later.

CREATE TABLE IF NOT EXISTS experience_categories (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120)    NOT NULL              COMMENT 'Display name e.g. Wildlife',
    slug        VARCHAR(140)    NOT NULL              COMMENT 'URL/filter slug e.g. wildlife',
    emoji       VARCHAR(8)      NOT NULL DEFAULT ''   COMMENT 'Mood emoji e.g. 🐘',
    description TEXT            NULL,
    sort_order  INT             NOT NULL DEFAULT 0,
    is_active   TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_experience_categories_slug (slug),
    KEY idx_experience_categories_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the 7 mood tiles shown in the mockup
INSERT IGNORE INTO experience_categories
    (name, slug, emoji, sort_order, is_active)
VALUES
    ('Wildlife',     'wildlife', '🐘', 1, 1),
    ('Culture',      'culture',  '🏛', 2, 1),
    ('Beach & Coast','beach',    '🏖', 3, 1),
    ('Food & Village','food',    '🍛', 4, 1),
    ('Hill Country', 'hill',     '⛰', 5, 1),
    ('Off-Route Gems','off-route','🧭',6, 1),
    ('Slow Travel',  'slow',     '🧘', 7, 1);

-- ── 2b. Add category FK + new content columns to experiences ──────────────────

CALL ict_add_column_if_missing('experiences', 'category_id',
    'INT UNSIGNED NULL DEFAULT NULL
     COMMENT "Primary mood/category (FK to experience_categories)"
     AFTER `slug`');

-- Day-in-the-life arc stored as JSON.
-- Structure:
-- [
--   { "time_of_day": "Morning", "icon": "🌅",
--     "heading": "Early Drive from Sigiriya",
--     "body": "Leave by 6am...", "image_path": "uploads/..." },
--   { ... }, { ... }, { ... }
-- ]
-- 4 segments max (Morning / Midday / Afternoon / Evening).
CALL ict_add_column_if_missing('experiences', 'day_arc_json',
    'LONGTEXT NULL DEFAULT NULL
     COMMENT "JSON: [{time_of_day,icon,heading,body,image_path}] — day-in-the-life arc"
     AFTER `intro`');

-- Denormalised counts — cached from pivot tables
CALL ict_add_column_if_missing('experiences', 'itinerary_count',
    'SMALLINT UNSIGNED NOT NULL DEFAULT 0
     COMMENT "Cached count of published itineraries linked to this experience"
     AFTER `is_published`');

CALL ict_add_column_if_missing('experiences', 'destination_count',
    'SMALLINT UNSIGNED NOT NULL DEFAULT 0
     COMMENT "Cached count of destinations linked to this experience"
     AFTER `itinerary_count`');

-- Index on category for the mood filter
CALL ict_add_index_if_missing('experiences', 'idx_experiences_category',
    '(`category_id`, `is_published`, `sort_order`)');

-- ── 2c. Pivot: experience ↔ category (many-to-many for multiple moods) ────────
-- The category_id on experiences is the PRIMARY mood.
-- This pivot is for future secondary mood tags.
CREATE TABLE IF NOT EXISTS experience_category_pivot (
    experience_id INT UNSIGNED NOT NULL,
    category_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (experience_id, category_id),
    KEY idx_exp_cat_pivot_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2d. Triggers: keep experience itinerary_count in sync ────────────────────
DROP TRIGGER IF EXISTS trg_exp_itin_count_insert;
DROP TRIGGER IF EXISTS trg_exp_itin_count_delete;

CREATE TRIGGER trg_exp_itin_count_insert
AFTER INSERT ON experience_itinerary
FOR EACH ROW
    UPDATE experiences
    SET itinerary_count = (
        SELECT COUNT(*)
        FROM experience_itinerary ei
        INNER JOIN itineraries i ON i.id = ei.itinerary_id AND i.is_published = 1
        WHERE ei.experience_id = NEW.experience_id
    )
    WHERE id = NEW.experience_id;

CREATE TRIGGER trg_exp_itin_count_delete
AFTER DELETE ON experience_itinerary
FOR EACH ROW
    UPDATE experiences
    SET itinerary_count = (
        SELECT COUNT(*)
        FROM experience_itinerary ei
        INNER JOIN itineraries i ON i.id = ei.itinerary_id AND i.is_published = 1
        WHERE ei.experience_id = OLD.experience_id
    )
    WHERE id = OLD.experience_id;

-- Backfill
UPDATE experiences e
SET e.itinerary_count = (
    SELECT COUNT(*)
    FROM experience_itinerary ei
    INNER JOIN itineraries i ON i.id = ei.itinerary_id AND i.is_published = 1
    WHERE ei.experience_id = e.id
),
e.destination_count = (
    SELECT COUNT(*)
    FROM experience_destination ed
    WHERE ed.experience_id = e.id
);


-- ════════════════════════════════════════════════════════════
-- 3. ITINERARIES
--    New filter fields for the listing page:
--    traveller type, start/end point, best months.
-- ════════════════════════════════════════════════════════════

-- Traveller type filter chip on listing page.
-- Comma-separated flags: couple, family, honeymoon, solo, group
CALL ict_add_column_if_missing('itineraries', 'traveller_types',
    'VARCHAR(120) NULL DEFAULT NULL
     COMMENT "Comma-separated filter tags: couple,family,honeymoon,solo,group"
     AFTER `category_id`');

-- Start / end point for the "Starts: Colombo" filter chip
CALL ict_add_column_if_missing('itineraries', 'start_point',
    'VARCHAR(120) NULL DEFAULT NULL
     COMMENT "Starting location for filter e.g. Colombo Airport, Colombo, Negombo"
     AFTER `traveller_types`');

CALL ict_add_column_if_missing('itineraries', 'end_point',
    'VARCHAR(120) NULL DEFAULT NULL
     COMMENT "Ending location for filter e.g. Colombo Airport, Galle"
     AFTER `start_point`');

-- Best months JSON for the visual calendar strip on the detail page.
-- Structure: { "months": [{"month":"Jan","rating":"great"}, ...] }
CALL ict_add_column_if_missing('itineraries', 'best_months_json',
    'LONGTEXT NULL DEFAULT NULL
     COMMENT "JSON: [{month,rating}x12] for best-time strip on detail page"
     AFTER `end_point`');

-- Denormalised stop count (destinations linked)
CALL ict_add_column_if_missing('itineraries', 'destination_count',
    'SMALLINT UNSIGNED NOT NULL DEFAULT 0
     COMMENT "Cached count of destinations linked to this itinerary"
     AFTER `is_published`');

-- Indexes for the filter bar queries
CALL ict_add_index_if_missing('itineraries', 'idx_itineraries_traveller',
    '(`traveller_types`(40))');

CALL ict_add_index_if_missing('itineraries', 'idx_itineraries_start',
    '(`start_point`(40))');

CALL ict_add_index_if_missing('itineraries', 'idx_itineraries_days_pub',
    '(`days`, `is_published`, `sort_order`)');

-- Backfill destination_count
UPDATE itineraries i
SET i.destination_count = (
    SELECT COUNT(*)
    FROM itinerary_destination id2
    WHERE id2.itinerary_id = i.id
);


-- ════════════════════════════════════════════════════════════
-- 4. ITINERARY CATEGORIES
--    Add theme_slug for the mood/theme filter chip on the
--    itineraries listing page.
-- ════════════════════════════════════════════════════════════

CALL ict_add_column_if_missing('itinerary_categories', 'theme_slug',
    'VARCHAR(80) NULL DEFAULT NULL
     COMMENT "Filter chip slug e.g. culture, wildlife, beach, honeymoon, family"
     AFTER `slug`');

CALL ict_add_column_if_missing('itinerary_categories', 'emoji',
    'VARCHAR(8) NULL DEFAULT NULL
     COMMENT "Emoji shown on listing filter chip e.g. 🏛"
     AFTER `theme_slug`');

-- Seed common theme slugs if categories already exist without them
UPDATE itinerary_categories SET theme_slug = 'culture',    emoji = '🏛' WHERE slug LIKE '%cultur%'   AND theme_slug IS NULL;
UPDATE itinerary_categories SET theme_slug = 'wildlife',   emoji = '🐘' WHERE slug LIKE '%wildlife%' AND theme_slug IS NULL;
UPDATE itinerary_categories SET theme_slug = 'beach',      emoji = '🏖' WHERE slug LIKE '%beach%'    AND theme_slug IS NULL;
UPDATE itinerary_categories SET theme_slug = 'honeymoon',  emoji = '💑' WHERE slug LIKE '%honey%'    AND theme_slug IS NULL;
UPDATE itinerary_categories SET theme_slug = 'family',     emoji = '👨‍👩‍👧' WHERE slug LIKE '%famil%'    AND theme_slug IS NULL;


-- ════════════════════════════════════════════════════════════
-- 5. ITINERARY DAYS
--    highlights_json: structured bullet points per day
--    for the timeline expand panel.
-- ════════════════════════════════════════════════════════════

-- highlights_json: ["🏛 Dambulla Caves", "🌿 Spice Garden", "🚗 ~4 hrs drive"]
CALL ict_add_column_if_missing('itinerary_days', 'highlights_json',
    'LONGTEXT NULL DEFAULT NULL
     COMMENT "JSON string array of highlight chips shown in the day timeline panel"
     AFTER `image_path`');


-- ════════════════════════════════════════════════════════════
-- 6. ROUTE WISHLIST SESSIONS
--    Server-side persistence for the "Add to Route" and
--    "Add to Wishlist" feature. Keyed by a browser session
--    token (stored in a cookie). Complements sessionStorage
--    so the route survives across tabs and devices.
-- ════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS route_sessions (
    id           BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    token        VARCHAR(64)      NOT NULL                   COMMENT 'Random hex token stored in cookie ict_route_token',
    -- Destinations route (CSV of destination slugs)
    dest_slugs   TEXT             NULL                       COMMENT 'CSV of destination slugs e.g. sigiriya,ella,galle',
    -- Experience wishlist (CSV of experience slugs)
    exp_slugs    TEXT             NULL                       COMMENT 'CSV of experience slugs',
    -- Optional: customer info if they filled the quick form
    customer_name    VARCHAR(150) NULL,
    customer_email   VARCHAR(190) NULL,
    customer_dates   VARCHAR(120) NULL,
    -- Metadata
    ip_address   VARCHAR(45)      NOT NULL DEFAULT '',
    user_agent   VARCHAR(300)     NOT NULL DEFAULT '',
    converted    TINYINT(1)       NOT NULL DEFAULT 0         COMMENT '1 = became a contact_inquiry',
    inquiry_id   BIGINT UNSIGNED  NULL                       COMMENT 'FK to contact_inquiries if converted',
    created_at   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at   DATETIME         NOT NULL                   COMMENT 'TTL — typically NOW() + 30 days',
    UNIQUE KEY uq_route_sessions_token (token),
    KEY idx_route_sessions_expires (expires_at),
    KEY idx_route_sessions_email   (customer_email),
    KEY idx_route_sessions_converted (converted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ════════════════════════════════════════════════════════════
-- 7. ADMIN NOTES
--    Tracks what columns need admin UI additions.
--    Not a real table — kept as comments for reference.
-- ════════════════════════════════════════════════════════════

-- destinations admin form additions needed:
--   • Map Pin tab: map_x (number), map_y (number), map_r (select 5-9),
--     map_initial (text, max 2 chars), region_slug (select), region_label (text), emoji (text)
--   • best_time_json: 12-month grid selector (great/okay/avoid per month)
--   • route_notes_json: repeatable from/to/duration/notes rows
--   • stay_duration: short text field

-- experiences admin form additions needed:
--   • category_id: dropdown from experience_categories
--   • day_arc_json: 4-row repeater (time_of_day, icon, heading, body, image)

-- itineraries admin form additions needed:
--   • traveller_types: multi-checkbox (couple/family/honeymoon/solo/group)
--   • start_point / end_point: text or select (Colombo Airport / Colombo / Negombo / Galle)
--   • best_months_json: 12-month grid selector

-- itinerary_days admin form additions needed:
--   • highlights_json: tag-input (each tag becomes an array item)


-- ── Cleanup helpers ──────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS ict_add_column_if_missing;
DROP PROCEDURE IF EXISTS ict_add_index_if_missing;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Done ─────────────────────────────────────────────────────────────────────
-- To apply on the VPS:
--   mysql -u sril_site -p sril_site < database/migrations/2026-05-22-gem-redesign.sql
