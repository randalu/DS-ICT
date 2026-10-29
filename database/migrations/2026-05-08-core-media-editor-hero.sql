-- Core media picker, hero video support, and homepage pinned hero setting.
-- Safe to run once on VPS.

DROP PROCEDURE IF EXISTS ict_add_column_if_missing;
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
DELIMITER ;

CALL ict_add_column_if_missing('media_assets', 'source_type', 'VARCHAR(40) NULL AFTER `tags`');
CALL ict_add_column_if_missing('media_assets', 'source_url', 'VARCHAR(500) NULL AFTER `source_type`');

CALL ict_add_column_if_missing('destinations', 'hero_media_type', 'VARCHAR(20) NULL AFTER `hero_image`');
CALL ict_add_column_if_missing('destinations', 'hero_media_asset_id', 'BIGINT UNSIGNED NULL AFTER `hero_media_type`');
CALL ict_add_column_if_missing('destinations', 'hero_video_poster', 'VARCHAR(255) NULL AFTER `hero_media_asset_id`');

CALL ict_add_column_if_missing('itineraries', 'hero_media_type', 'VARCHAR(20) NULL AFTER `hero_image`');
CALL ict_add_column_if_missing('itineraries', 'hero_media_asset_id', 'BIGINT UNSIGNED NULL AFTER `hero_media_type`');
CALL ict_add_column_if_missing('itineraries', 'hero_video_poster', 'VARCHAR(255) NULL AFTER `hero_media_asset_id`');

CALL ict_add_column_if_missing('experiences', 'hero_media_type', 'VARCHAR(20) NULL AFTER `hero_image`');
CALL ict_add_column_if_missing('experiences', 'hero_media_asset_id', 'BIGINT UNSIGNED NULL AFTER `hero_media_type`');
CALL ict_add_column_if_missing('experiences', 'hero_video_poster', 'VARCHAR(255) NULL AFTER `hero_media_asset_id`');

CALL ict_add_column_if_missing('blog_posts', 'hero_media_type', 'VARCHAR(20) NULL AFTER `hero_image`');
CALL ict_add_column_if_missing('blog_posts', 'hero_media_asset_id', 'BIGINT UNSIGNED NULL AFTER `hero_media_type`');
CALL ict_add_column_if_missing('blog_posts', 'hero_video_poster', 'VARCHAR(255) NULL AFTER `hero_media_asset_id`');

CALL ict_add_column_if_missing('static_pages', 'hero_media_type', 'VARCHAR(20) NULL AFTER `hero_image`');
CALL ict_add_column_if_missing('static_pages', 'hero_media_asset_id', 'BIGINT UNSIGNED NULL AFTER `hero_media_type`');
CALL ict_add_column_if_missing('static_pages', 'hero_video_poster', 'VARCHAR(255) NULL AFTER `hero_media_asset_id`');

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_pinned_hero_id', ''
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_pinned_hero_id');

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_approach_default_hidden_applied', '0'
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_approach_default_hidden_applied');

DROP PROCEDURE IF EXISTS ict_add_column_if_missing;
