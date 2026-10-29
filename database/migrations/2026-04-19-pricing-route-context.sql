-- Add pricing range fields and route context fields for tours/itineraries/destinations.
-- Safe to run multiple times.

SET @db = DATABASE();

-- destinations.nearby_travel_times
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'destinations' AND COLUMN_NAME = 'nearby_travel_times'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE destinations ADD COLUMN nearby_travel_times LONGTEXT NULL AFTER travel_tips', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- destinations.works_well_with
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'destinations' AND COLUMN_NAME = 'works_well_with'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE destinations ADD COLUMN works_well_with LONGTEXT NULL AFTER nearby_travel_times', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- tours.price_from
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tours' AND COLUMN_NAME = 'price_from'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE tours ADD COLUMN price_from DECIMAL(10,2) NULL AFTER pricing_notes', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- tours.price_to
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tours' AND COLUMN_NAME = 'price_to'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE tours ADD COLUMN price_to DECIMAL(10,2) NULL AFTER price_from', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- tours.price_currency
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tours' AND COLUMN_NAME = 'price_currency'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE tours ADD COLUMN price_currency CHAR(3) NOT NULL DEFAULT ''USD'' AFTER price_to', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- tours.price_unit
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tours' AND COLUMN_NAME = 'price_unit'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE tours ADD COLUMN price_unit VARCHAR(80) NULL AFTER price_currency', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- itineraries.nearby_travel_times
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'itineraries' AND COLUMN_NAME = 'nearby_travel_times'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE itineraries ADD COLUMN nearby_travel_times LONGTEXT NULL AFTER route_summary', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- itineraries.works_well_with
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'itineraries' AND COLUMN_NAME = 'works_well_with'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE itineraries ADD COLUMN works_well_with LONGTEXT NULL AFTER nearby_travel_times', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- itineraries.price_from
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'itineraries' AND COLUMN_NAME = 'price_from'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE itineraries ADD COLUMN price_from DECIMAL(10,2) NULL AFTER travel_notes', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- itineraries.price_to
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'itineraries' AND COLUMN_NAME = 'price_to'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE itineraries ADD COLUMN price_to DECIMAL(10,2) NULL AFTER price_from', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- itineraries.price_currency
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'itineraries' AND COLUMN_NAME = 'price_currency'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE itineraries ADD COLUMN price_currency CHAR(3) NOT NULL DEFAULT ''USD'' AFTER price_to', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- itineraries.price_unit
SET @exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'itineraries' AND COLUMN_NAME = 'price_unit'
);
SET @sql = IF(@exists = 0, 'ALTER TABLE itineraries ADD COLUMN price_unit VARCHAR(80) NULL AFTER price_currency', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
