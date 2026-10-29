<?php
declare(strict_types=1);

/**
 * Migration: Add SEO enrichment columns to destinations table.
 * Run: php cli\migrate_destination_seo_columns.php
 */

require_once __DIR__ . '/../includes/functions.php';

$added = 0;
$skipped = 0;
$errors = 0;

$columns = [
    ['top_source_markets',       'VARCHAR(255) NULL COMMENT \'e.g. UK, AUS, USA\''],
    ['top_activities',           'VARCHAR(500) NULL COMMENT \'Top 3 activities comma-separated\''],
    ['tripadvisor_rating',       'DECIMAL(2,1) NULL'],
    ['tripadvisor_review_count', 'INT NULL DEFAULT 0'],
    ['booking_rating',           'DECIMAL(2,1) NULL'],
    ['getyourguide_rating',      'DECIMAL(2,1) NULL'],
    ['getyourguide_review_count','INT NULL DEFAULT 0'],
    ['strict_regulations_notes', 'TEXT NULL'],
    ['seasonality_notes',        'TEXT NULL'],
    ['logistical_effort_notes',  'TEXT NULL'],
    ['peak_months',              'VARCHAR(100) NULL'],
    ['shoulder_months',          'VARCHAR(100) NULL'],
    ['off_months',               'VARCHAR(100) NULL'],
];

foreach ($columns as [$col, $def]) {
    if (db_column_exists('destinations', $col)) {
        echo "SKIP  {$col} — already exists\n";
        ++$skipped;
        continue;
    }

    try {
        execute_query("ALTER TABLE destinations ADD COLUMN {$col} {$def}");
        echo "OK    {$col}\n";
        ++$added;
    } catch (\Throwable $e) {
        echo "FAIL  {$col} — {$e->getMessage()}\n";
        ++$errors;
    }
}

echo "\nDone. Added: {$added}, Skipped: {$skipped}, Errors: {$errors}\n";
exit($errors > 0 ? 1 : 0);
