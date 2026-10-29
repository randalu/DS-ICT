-- Additive and rerunnable. Existing aggregate/legacy records are not backfilled.
SET @ict_stats_ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='page_views' AND COLUMN_NAME='visitor_key')=0,
 'ALTER TABLE page_views ADD COLUMN visitor_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT ''''', 'DO 1');
PREPARE ict_stats_stmt FROM @ict_stats_ddl;
EXECUTE ict_stats_stmt;
DEALLOCATE PREPARE ict_stats_stmt;
SET @ict_stats_ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='page_views' AND COLUMN_NAME='visit_key')=0,
 'ALTER TABLE page_views ADD COLUMN visit_key CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT ''''', 'DO 1');
PREPARE ict_stats_stmt FROM @ict_stats_ddl;
EXECUTE ict_stats_stmt;
DEALLOCATE PREPARE ict_stats_stmt;
