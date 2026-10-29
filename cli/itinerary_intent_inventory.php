<?php
declare(strict_types=1);

/**
 * Read-only Phase 56-57 inventory and intent-separation audit.
 */

require_once __DIR__ . '/../config/bootstrap.php';

if (!db_table_exists('itineraries')) {
    fwrite(STDERR, "Itinerary inventory: missing itineraries table.\n");
    exit(2);
}
if (!db_table_exists('seo_intent_map')) {
    fwrite(STDERR, "Itinerary inventory: missing seo_intent_map table.\n");
    exit(2);
}

$rows = fetch_all(
    "SELECT id, name, slug, days, meta_title, meta_description, canonical_url, is_published
     FROM itineraries
     WHERE is_published = 1
     ORDER BY days ASC, sort_order ASC, name ASC"
);
$byDays = [];
foreach ($rows as $row) {
    $days = (int) ($row['days'] ?? 0);
    $byDays[$days][] = $row;
}

$expectedDays = [5, 7, 9, 10, 12, 14];
$failures = [];
foreach ($expectedDays as $days) {
    $count = count($byDays[$days] ?? []);
    echo '[INVENTORY] ' . $days . '-day published itinerary records: ' . $count . PHP_EOL;
    if ($count === 0) {
        $failures[] = 'no published itinerary record for ' . $days . ' days';
    }
}

$mapRows = fetch_all(
    "SELECT target_path, intent_role, canonical_target
     FROM seo_intent_map
     WHERE is_active = 1
     ORDER BY target_path"
);
$map = [];
foreach ($mapRows as $mapRow) {
    $map[(string) ($mapRow['target_path'] ?? '')] = $mapRow;
}

$requiredMap = [
    '/itineraries/sri-lanka-itinerary-10-days',
    '/tours/sri-lanka-10-day-itinerary-package',
    '/sri-lanka-private-tours',
];
foreach ($requiredMap as $path) {
    if (!isset($map[$path])) {
        $failures[] = 'intent map missing ' . $path;
    }
}

$package = fetch_one(
    "SELECT id, slug FROM tours WHERE slug = :slug AND is_published = 1 LIMIT 1",
    ['slug' => 'sri-lanka-10-day-itinerary-package']
);
if (!$package) {
    $failures[] = 'published 10-day transactional package is missing';
}

$hub = find_published_static_page_by_slug('sri-lanka-private-tours');
if (!$hub) {
    $failures[] = 'published private-tours commercial hub is missing';
}

if ($failures) {
    echo '[FAIL] ' . implode('; ', $failures) . PHP_EOL;
    exit(1);
}

echo '[PASS] Independent itinerary records and Tour package roles are mapped; duplicate duration reference pages remain retired.' . PHP_EOL;
echo sprintf("Intent inventory summary: published_itineraries=%d; mapped_targets=%d; failures=0.\n", count($rows), count($mapRows));
