-- Additive and rerunnable. Apply explicitly after reviewing the release; never on a page request.
SET @ict_tour_coverage_sql = (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE tours ADD COLUMN coverage_json LONGTEXT NULL',
        'DO 0')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tours' AND COLUMN_NAME = 'coverage_json'
);
PREPARE ict_tour_coverage_statement FROM @ict_tour_coverage_sql;
EXECUTE ict_tour_coverage_statement;
DEALLOCATE PREPARE ict_tour_coverage_statement;
