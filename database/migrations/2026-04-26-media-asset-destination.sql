-- Add destination_id to media_assets so library items can be tagged to a destination.
-- Safe to run multiple times.

SET @db = DATABASE();

SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'media_assets' AND COLUMN_NAME = 'destination_id'
);
SET @sql = IF(@exists = 0,
  'ALTER TABLE media_assets ADD COLUMN destination_id BIGINT UNSIGNED NULL AFTER tags, ADD INDEX idx_media_assets_destination (destination_id)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
