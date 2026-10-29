-- Experiences, review enhancements, and gallery support.
-- MySQL 8.x / MariaDB compatible. Safe to run after the PHP schema guards.

DROP PROCEDURE IF EXISTS ict_add_column_if_missing;
DROP PROCEDURE IF EXISTS ict_add_index_if_missing;

DELIMITER //

CREATE PROCEDURE ict_add_column_if_missing(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

CREATE PROCEDURE ict_add_index_if_missing(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

DELIMITER ;

CREATE TABLE IF NOT EXISTS experiences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT NULL,
    intro LONGTEXT NULL,
    where_to_experience LONGTEXT NULL,
    tips_json LONGTEXT NULL,
    best_time TEXT NULL,
    hero_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    cta_title VARCHAR(255) NULL,
    cta_text TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_experiences_published_sort (is_published, sort_order),
    KEY idx_experiences_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL ict_add_column_if_missing('experiences', 'excerpt', 'TEXT NULL AFTER `slug`');
CALL ict_add_column_if_missing('experiences', 'intro', 'LONGTEXT NULL AFTER `excerpt`');
CALL ict_add_column_if_missing('experiences', 'where_to_experience', 'LONGTEXT NULL AFTER `intro`');
CALL ict_add_column_if_missing('experiences', 'tips_json', 'LONGTEXT NULL AFTER `where_to_experience`');
CALL ict_add_column_if_missing('experiences', 'best_time', 'TEXT NULL AFTER `tips_json`');
CALL ict_add_column_if_missing('experiences', 'hero_image', 'VARCHAR(255) NULL AFTER `best_time`');
CALL ict_add_column_if_missing('experiences', 'og_image', 'VARCHAR(255) NULL AFTER `hero_image`');
CALL ict_add_column_if_missing('experiences', 'meta_title', 'VARCHAR(255) NULL AFTER `og_image`');
CALL ict_add_column_if_missing('experiences', 'meta_description', 'VARCHAR(320) NULL AFTER `meta_title`');
CALL ict_add_column_if_missing('experiences', 'canonical_url', 'VARCHAR(255) NULL AFTER `meta_description`');
CALL ict_add_column_if_missing('experiences', 'cta_title', 'VARCHAR(255) NULL AFTER `canonical_url`');
CALL ict_add_column_if_missing('experiences', 'cta_text', 'TEXT NULL AFTER `cta_title`');
CALL ict_add_column_if_missing('experiences', 'sort_order', 'INT NOT NULL DEFAULT 0 AFTER `cta_text`');
CALL ict_add_column_if_missing('experiences', 'is_featured', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `sort_order`');
CALL ict_add_column_if_missing('experiences', 'is_published', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_featured`');
CALL ict_add_column_if_missing('experiences', 'created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `is_published`');
CALL ict_add_column_if_missing('experiences', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');
CALL ict_add_index_if_missing('experiences', 'idx_experiences_published_sort', '(`is_published`, `sort_order`)');
CALL ict_add_index_if_missing('experiences', 'idx_experiences_featured', '(`is_featured`)');

CREATE TABLE IF NOT EXISTS experience_destination (
    experience_id BIGINT UNSIGNED NOT NULL,
    destination_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (experience_id, destination_id),
    KEY idx_experience_destination_destination (destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS experience_itinerary (
    experience_id BIGINT UNSIGNED NOT NULL,
    itinerary_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (experience_id, itinerary_id),
    KEY idx_experience_itinerary_itinerary (itinerary_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS experience_related (
    experience_id BIGINT UNSIGNED NOT NULL,
    related_experience_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (experience_id, related_experience_id),
    KEY idx_experience_related_related (related_experience_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS experience_gallery (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    experience_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_experience_gallery_experience (experience_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL ict_add_column_if_missing('reviews', 'country_code', 'CHAR(2) NULL AFTER `country`');
CALL ict_add_column_if_missing('reviews', 'review_title', 'VARCHAR(255) NULL AFTER `rating`');
CALL ict_add_column_if_missing('reviews', 'tour_done_date', 'DATE NULL AFTER `trip_type`');
CALL ict_add_column_if_missing('reviews', 'tour_done_month', 'CHAR(7) NULL AFTER `tour_done_date`');
CALL ict_add_column_if_missing('reviews', 'review_source', 'VARCHAR(120) NULL AFTER `tour_done_month`');
CALL ict_add_column_if_missing('reviews', 'review_source_url', 'VARCHAR(255) NULL AFTER `review_source`');
CALL ict_add_column_if_missing('reviews', 'is_featured', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `review_source_url`');
CALL ict_add_column_if_missing('reviews', 'is_published', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_featured`');
CALL ict_add_column_if_missing('reviews', 'sort_order', 'INT NOT NULL DEFAULT 0 AFTER `is_published`');
CALL ict_add_column_if_missing('reviews', 'created_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `sort_order`');
CALL ict_add_column_if_missing('reviews', 'updated_at', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');
CALL ict_add_index_if_missing('reviews', 'idx_reviews_public_date', '(`is_published`, `tour_done_date`)');
CALL ict_add_index_if_missing('reviews', 'idx_reviews_featured_sort', '(`is_featured`, `sort_order`)');

CREATE TABLE IF NOT EXISTS review_photos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    review_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_review_photos_review (review_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS ict_add_column_if_missing;
DROP PROCEDURE IF EXISTS ict_add_index_if_missing;
