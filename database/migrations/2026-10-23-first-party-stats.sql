-- Additive, rerunnable; old aggregate/legacy records are not backfilled.
SET @ict_stats_ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='page_views' AND COLUMN_NAME='device_type')=0,
 'ALTER TABLE page_views ADD COLUMN device_type VARCHAR(16) NOT NULL DEFAULT ''''', 'DO 1');
PREPARE ict_stats_stmt FROM @ict_stats_ddl;
EXECUTE ict_stats_stmt;
DEALLOCATE PREPARE ict_stats_stmt;
SET @ict_stats_ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='page_views' AND COLUMN_NAME='browser_name')=0,
 'ALTER TABLE page_views ADD COLUMN browser_name VARCHAR(24) NOT NULL DEFAULT ''''', 'DO 1');
PREPARE ict_stats_stmt FROM @ict_stats_ddl;
EXECUTE ict_stats_stmt;
DEALLOCATE PREPARE ict_stats_stmt;
SET @ict_stats_ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='page_views' AND COLUMN_NAME='os_name')=0,
 'ALTER TABLE page_views ADD COLUMN os_name VARCHAR(24) NOT NULL DEFAULT ''''', 'DO 1');
PREPARE ict_stats_stmt FROM @ict_stats_ddl;
EXECUTE ict_stats_stmt;
DEALLOCATE PREPARE ict_stats_stmt;
