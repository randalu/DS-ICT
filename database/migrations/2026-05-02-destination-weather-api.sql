-- Destination Google Weather API cache + monthly climate notes.
-- Safe to run more than once.

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

CREATE TABLE IF NOT EXISTS weather_api_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value LONGTEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO weather_api_settings (setting_key, setting_value)
SELECT 'weather_api_enabled', '0'
WHERE NOT EXISTS (SELECT 1 FROM weather_api_settings WHERE setting_key = 'weather_api_enabled');

INSERT INTO weather_api_settings (setting_key, setting_value)
SELECT 'weather_api_key', ''
WHERE NOT EXISTS (SELECT 1 FROM weather_api_settings WHERE setting_key = 'weather_api_key');

INSERT INTO weather_api_settings (setting_key, setting_value)
SELECT 'weather_units_system', 'METRIC'
WHERE NOT EXISTS (SELECT 1 FROM weather_api_settings WHERE setting_key = 'weather_units_system');

INSERT INTO weather_api_settings (setting_key, setting_value)
SELECT 'weather_language_code', 'en'
WHERE NOT EXISTS (SELECT 1 FROM weather_api_settings WHERE setting_key = 'weather_language_code');

INSERT INTO weather_api_settings (setting_key, setting_value)
SELECT 'weather_current_cache_minutes', '60'
WHERE NOT EXISTS (SELECT 1 FROM weather_api_settings WHERE setting_key = 'weather_current_cache_minutes');

INSERT INTO weather_api_settings (setting_key, setting_value)
SELECT 'weather_forecast_cache_minutes', '360'
WHERE NOT EXISTS (SELECT 1 FROM weather_api_settings WHERE setting_key = 'weather_forecast_cache_minutes');

INSERT INTO weather_api_settings (setting_key, setting_value)
SELECT 'weather_timeout_seconds', '8'
WHERE NOT EXISTS (SELECT 1 FROM weather_api_settings WHERE setting_key = 'weather_timeout_seconds');

CALL ict_add_column_if_missing('destinations', 'weather_enabled', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_published`');
CALL ict_add_column_if_missing('destinations', 'weather_latitude', 'DECIMAL(10,7) NULL AFTER `weather_enabled`');
CALL ict_add_column_if_missing('destinations', 'weather_longitude', 'DECIMAL(10,7) NULL AFTER `weather_latitude`');
CALL ict_add_column_if_missing('destinations', 'weather_location_label', 'VARCHAR(180) NULL AFTER `weather_longitude`');
CALL ict_add_column_if_missing('destinations', 'weather_admin_note', 'TEXT NULL AFTER `weather_location_label`');
CALL ict_add_index_if_missing('destinations', 'idx_destinations_weather_enabled', '(`weather_enabled`)');

CREATE TABLE IF NOT EXISTS destination_weather_cache (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id BIGINT UNSIGNED NOT NULL,
    cache_type VARCHAR(40) NOT NULL,
    request_hash VARCHAR(64) NOT NULL,
    payload_json LONGTEXT NULL,
    http_status INT NULL,
    error_message VARCHAR(500) NULL,
    fetched_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_destination_weather_cache (destination_id, cache_type, request_hash),
    KEY idx_destination_weather_cache_expiry (destination_id, cache_type, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destination_weather_monthly_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id BIGINT UNSIGNED NOT NULL,
    month_key VARCHAR(20) NOT NULL,
    climate_summary TEXT NULL,
    temperature_note VARCHAR(255) NULL,
    rain_note VARCHAR(255) NULL,
    sea_note VARCHAR(255) NULL,
    best_for VARCHAR(255) NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_destination_weather_month (destination_id, month_key),
    KEY idx_destination_weather_month_destination (destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS ict_add_column_if_missing;
DROP PROCEDURE IF EXISTS ict_add_index_if_missing;
