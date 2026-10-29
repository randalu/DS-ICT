-- ============================================================
-- Hidden Gems & SEO — Schema Migration
-- 2026-07-09
--
-- Adds columns needed for:
--   1. Peak/shoulder/off-season data
--   2. Traveler demographics and platform ratings
--   3. Difficulty, access, crowd level for travel planners
--   4. Logistical notes and hidden gem category
--   5. Nearby highlights and route pairing (structured)
--   6. Best known for one-liner meta
--   7. Season start/end dates and weather summary
--   8. Hidden gem flag
--
-- Safe to re-run: all ALTERs use IF NOT EXISTS guards via
-- ict_add_column_if_missing (created inline, dropped at end).
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Reusable helper procedure ─────────────────────────────────

DROP PROCEDURE IF EXISTS ict_add_column_if_missing;

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

DELIMITER ;

-- ════════════════════════════════════════════════════════════
-- 1. DESTINATIONS — new SEO and planning columns
-- ════════════════════════════════════════════════════════════

-- Peak/shoulder/off-season as JSON (structured months data)
CALL ict_add_column_if_missing('destinations', 'peak_season_json',
    'LONGTEXT NULL DEFAULT NULL COMMENT "JSON: peak months, best months, shoulder months, off months" AFTER `best_time_json`');

-- Human-friendly season label for cards
CALL ict_add_column_if_missing('destinations', 'best_season_label',
    'VARCHAR(120) NULL DEFAULT NULL COMMENT "e.g. May–Sep" AFTER `peak_season_json`');

CALL ict_add_column_if_missing('destinations', 'shoulder_season_label',
    'VARCHAR(120) NULL DEFAULT NULL COMMENT "e.g. Apr, Oct" AFTER `best_season_label`');

CALL ict_add_column_if_missing('destinations', 'off_season_label',
    'VARCHAR(120) NULL DEFAULT NULL COMMENT "e.g. Nov–Mar" AFTER `shoulder_season_label`');

-- Weather summary for quick reference
CALL ict_add_column_if_missing('destinations', 'weather_summary',
    'VARCHAR(255) NULL DEFAULT NULL COMMENT "One-liner about climate" AFTER `off_season_label`');

-- Season start/end for date-range filtering
CALL ict_add_column_if_missing('destinations', 'season_start',
    'VARCHAR(10) NULL DEFAULT NULL COMMENT "First good month, e.g. Nov" AFTER `weather_summary`');

CALL ict_add_column_if_missing('destinations', 'season_end',
    'VARCHAR(10) NULL DEFAULT NULL COMMENT "Last good month, e.g. Apr" AFTER `season_start`');

-- Hidden gem flag + category
CALL ict_add_column_if_missing('destinations', 'is_hidden_gem',
    'TINYINT(1) NOT NULL DEFAULT 0 COMMENT "1 = hidden gem (not in mainstream lists)" AFTER `is_published`');

CALL ict_add_column_if_missing('destinations', 'hidden_gem_category',
    'VARCHAR(60) NULL DEFAULT NULL COMMENT "Category: Rainforest, Wildlife, Surf, etc." AFTER `is_hidden_gem`');

-- Best-known-for one-liner for meta/hover cards
CALL ict_add_column_if_missing('destinations', 'best_known_for',
    'VARCHAR(255) NULL DEFAULT NULL COMMENT "Single-line hook for meta description / card" AFTER `hidden_gem_category`');

-- Traveler demographics: top nationalities + rated activities
CALL ict_add_column_if_missing('destinations', 'demographic_json',
    'LONGTEXT NULL DEFAULT NULL COMMENT "JSON: {nationalities:[], top_activities:[], traveler_types:[]}" AFTER `best_known_for`');

-- Platform consensus ratings: TripAdvisor, Booking.com, GetYourGuide
CALL ict_add_column_if_missing('destinations', 'platform_ratings_json',
    'LONGTEXT NULL DEFAULT NULL COMMENT "JSON: [{platform, score, reviews, url}]" AFTER `demographic_json`');

-- Difficulty / access / crowd level for travel planners
CALL ict_add_column_if_missing('destinations', 'difficulty_level',
    'VARCHAR(30) NULL DEFAULT NULL COMMENT "Easy / Moderate / Challenging / N/A" AFTER `platform_ratings_json`');

CALL ict_add_column_if_missing('destinations', 'access_type',
    'VARCHAR(60) NULL DEFAULT NULL COMMENT "e.g. Car, Train, 4x4, Ferry, Flight" AFTER `difficulty_level`');

CALL ict_add_column_if_missing('destinations', 'crowd_level',
    'VARCHAR(30) NULL DEFAULT NULL COMMENT "Quiet / Moderate / Busy / Peak Crowded" AFTER `access_type`');

-- Logistical notes — the "catch" for travel planners (strict regulations, seasonality, effort)
CALL ict_add_column_if_missing('destinations', 'logistical_notes',
    'TEXT NULL DEFAULT NULL COMMENT "Planner notes: regulations, effort, seasonality gotchas" AFTER `crowd_level`');

-- Nearby highlights (structured JSON: [{name, slug, type, distance}])
CALL ict_add_column_if_missing('destinations', 'nearby_highlights_json',
    'LONGTEXT NULL DEFAULT NULL COMMENT "JSON array of nearby attractions" AFTER `logistical_notes`');

-- Route pairing (structured JSON: [{name, slug, why_pair}])
CALL ict_add_column_if_missing('destinations', 'route_pairing_json',
    'LONGTEXT NULL DEFAULT NULL COMMENT "JSON: destinations it pairs well with" AFTER `nearby_highlights_json`');

-- Visitor stats (rated activities per traveler type)
CALL ict_add_column_if_missing('destinations', 'visitor_stats_json',
    'LONGTEXT NULL DEFAULT NULL COMMENT "JSON: {traveler_type, activities, rating}" AFTER `route_pairing_json`');

-- ════════════════════════════════════════════════════════════
-- 2. INDEXES — for filtered queries
-- ════════════════════════════════════════════════════════════

SET @idx_exists = NULL;
SELECT COUNT(*) INTO @idx_exists
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'destinations' AND INDEX_NAME = 'idx_is_hidden_gem';

SET @sql = IF(@idx_exists = 0,
    'CREATE INDEX idx_is_hidden_gem ON destinations (is_hidden_gem)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ════════════════════════════════════════════════════════════
-- 3. CLEANUP
-- ════════════════════════════════════════════════════════════

DROP PROCEDURE IF EXISTS ict_add_column_if_missing;

SET FOREIGN_KEY_CHECKS = 1;
