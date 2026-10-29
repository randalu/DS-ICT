<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/itineraries.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);
$categoryId = (int) post_input('category_id', 0);

$data = [
    'name' => trim((string) post_input('name', '')),
    'slug' => trim((string) post_input('slug', '')),
    'days' => max(1, (int) post_input('days', 1)),
    'category_id' => $categoryId > 0 ? $categoryId : null,
    'excerpt' => trim((string) post_input('excerpt', '')),
    'intro' => trim((string) post_input('intro', '')),
    'highlights' => trim((string) post_input('highlights', '')),
    'route_summary' => trim((string) post_input('route_summary', '')),
    'best_for' => trim((string) post_input('best_for', '')),
    'travel_notes' => trim((string) post_input('travel_notes', '')),
    'nearby_travel_times' => trim((string) post_input('nearby_travel_times', '')),
    'works_well_with' => trim((string) post_input('works_well_with', '')),
    'traveller_types' => trim((string) post_input('traveller_types', '')),
    'start_point' => trim((string) post_input('start_point', '')),
    'end_point' => trim((string) post_input('end_point', '')),
    'pricing_notes' => trim((string) post_input('pricing_notes', '')),
    'vehicle_options' => trim((string) post_input('vehicle_options', '')),
    'price_from' => normalize_price_input(post_input('price_from')),
    'price_to' => normalize_price_input(post_input('price_to')),
    'price_currency' => strtoupper(trim((string) post_input('price_currency', 'USD'))),
    'price_unit' => trim((string) post_input('price_unit', '')),
    'faq_json' => trim((string) post_input('faq_json', '')),
    'cta_title' => trim((string) post_input('cta_title', '')),
    'cta_text' => trim((string) post_input('cta_text', '')),
    'best_months_json' => '',
    'meta_title' => trim((string) post_input('meta_title', '')),
    'meta_description' => trim((string) post_input('meta_description', '')),
    'canonical_url' => trim((string) post_input('canonical_url', '')),
    'sort_order' => (int) post_input('sort_order', 0),
    'is_featured' => post_input('is_featured') ? 1 : 0,
    'is_published' => post_input('is_published') ? 1 : 0,
];

$data['slug'] = $data['slug'] !== '' ? slugify($data['slug']) : slugify($data['name']);
$data['canonical_url'] = $data['canonical_url'] !== '' ? $data['canonical_url'] : base_url('itineraries/' . $data['slug']);

$bestMonths = array_values(array_filter(array_map(
    static fn($m) => strtolower(trim((string) $m)),
    (array) post_input('best_months', [])
)));
$bestMonthsNote = trim((string) post_input('best_months_note', ''));
if ($bestMonths || $bestMonthsNote !== '') {
    $data['best_months_json'] = json_encode([
        'months' => $bestMonths,
        'note'   => $bestMonthsNote,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

old_input_set($_POST);

$destinationIds = array_values(array_filter(array_unique(array_map(
    'intval',
    (array) post_input('destinations', [])
)), static fn(int $value): bool => $value > 0));

$errors = [];
try {
    $travellerTypes = package_traveller_types($_POST);
    if ($travellerTypes !== null) {
        $data['traveller_types'] = $travellerTypes;
        $pendingInput = $_POST; $pendingInput['traveller_types'] = $travellerTypes; old_input_set($pendingInput);
    }
} catch (InvalidArgumentException $e) { $errors[] = $e->getMessage(); }

if ($data['name'] === '') {
    $errors[] = 'Name is required.';
}

if ($data['slug'] === '') {
    $errors[] = 'Slug could not be generated.';
}

$lengthLimits = [
    'name' => 150,
    'slug' => 180,
    'excerpt' => 500,
    'traveller_types' => 120, 'start_point' => 255, 'end_point' => 255,
    'price_currency' => 3,
    'price_unit' => 80,
    'cta_title' => 255,
    'meta_title' => 255,
    'meta_description' => 320,
    'canonical_url' => 255,
];
foreach ($lengthLimits as $field => $limit) {
    if (mb_strlen((string) $data[$field]) > $limit) {
        $label = ucwords(str_replace('_', ' ', $field));
        $errors[] = "{$label} must be {$limit} characters or fewer.";
    }
}

$canonicalScheme = strtolower((string) parse_url($data['canonical_url'], PHP_URL_SCHEME));
if (!filter_var($data['canonical_url'], FILTER_VALIDATE_URL) || !in_array($canonicalScheme, ['http', 'https'], true)) {
    $errors[] = 'Canonical URL must be a valid http(s) URL.';
}

if (db_table_exists('itinerary_categories') && db_column_exists('itineraries', 'category_id')) {
    if ($data['category_id'] === null) {
        $errors[] = 'Category is required.';
    } else {
        $category = fetch_one(
            "SELECT id FROM itinerary_categories WHERE id = :id LIMIT 1",
            ['id' => $data['category_id']]
        );

        if (!$category) {
            $errors[] = 'Selected category is invalid.';
        }
    }
}

if (!preg_match('/^[A-Z]{3}$/', $data['price_currency'])) {
    $data['price_currency'] = 'USD';
}

if ($data['price_from'] !== null && $data['price_to'] !== null && $data['price_to'] < $data['price_from']) {
    $errors[] = 'Price To must be greater than or equal to Price From.';
}

if ($data['faq_json'] !== '') {
    $decoded = json_decode($data['faq_json'], true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = 'FAQ JSON is invalid.';
    }
}

$fetchExistingIds = static function (string $table, string $column, array $ids): array {
    if (!$ids || !db_table_exists($table) || !db_column_exists($table, $column)) {
        return [];
    }

    $placeholders = [];
    $params = [];
    foreach (array_values($ids) as $index => $candidateId) {
        $key = 'id' . $index;
        $placeholders[] = ':' . $key;
        $params[$key] = (int) $candidateId;
    }

    $rows = fetch_all(
        'SELECT ' . $column . ' FROM ' . $table . ' WHERE ' . $column . ' IN (' . implode(', ', $placeholders) . ')',
        $params
    );

    return array_map(static fn(array $row): int => (int) ($row[$column] ?? 0), $rows);
};

if ($destinationIds) {
    $existingDestinationIds = $fetchExistingIds('destinations', 'id', $destinationIds);
    if (array_diff($destinationIds, $existingDestinationIds)) {
        $errors[] = 'Selected destinations are invalid.';
    }
}

$existing = fetch_one(
    "SELECT id FROM itineraries WHERE slug = :slug AND id != :id LIMIT 1",
    ['slug' => $data['slug'], 'id' => $id]
);

if ($existing) {
    $errors[] = 'Slug already exists.';
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($id > 0 ? 'admin/itinerary_form.php?id=' . $id : 'admin/itinerary_form.php');
}

$record = [];
if ($id > 0) {
    $record = fetch_one("SELECT * FROM itineraries WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Itinerary not found.');
        redirect('admin/itineraries.php');
    }
}

$coverCreatedPath = null;
$legacyCreatedPaths = [];
// Legacy uploads/imports prepare their schema before the package transaction starts.
$createsHero = !empty($_FILES['hero_image']['name']) || trim((string) post_input('hero_image_url', '')) !== '';
$createsOg = !empty($_FILES['og_image']['name']) || trim((string) post_input('og_image_url', '')) !== '';
try {
    if ($createsHero || $createsOg || !empty($_FILES['hero_image_poster']['name'])) { ensure_social_marketing_schema(); }
    db()->beginTransaction();
    if ($id > 0) {
        $record = fetch_one('SELECT * FROM itineraries WHERE id=:id FOR UPDATE', ['id' => $id]) ?? throw new InvalidArgumentException('Package not found.');
        if (isset($_POST['package_revision']) && (!is_string($_POST['package_revision']) || !hash_equals(package_edit_revision($record), $_POST['package_revision']))) { throw new DomainException('This package changed in another editor. Reload and compare before saving.'); }
    }
    $data['best_months_json'] = package_season_json((string) ($record['best_months_json'] ?? ''), $_POST, 'best_months_json', 'best_months', 'best_months_note');

    $coverUpdates = package_cover_updates('itinerary', $record, $_POST, $_FILES, $coverCreatedPath);
    $heroMedia = admin_resolve_media_field('hero_image', 'itineraries', [
        'path' => $record['hero_image'] ?? null,
        'asset_id' => $record['hero_media_asset_id'] ?? null,
        'media_type' => $record['hero_media_type'] ?? 'image',
    ], true, $legacyCreatedPaths);
    $ogMedia = admin_resolve_media_field('og_image', 'itineraries', ['path' => $record['og_image'] ?? null], false, $legacyCreatedPaths);
    $heroPoster = $record['hero_video_poster'] ?? null;
    if (!empty($_FILES['hero_image_poster']['name'])) {
        $posterMedia = save_media_asset_from_upload($_FILES['hero_image_poster'], 'itineraries', false);
        $heroPoster = $posterMedia['file_path'];
        $legacyCreatedPaths[] = $heroPoster;
    }

    $columnValueMap = [
        'name' => $data['name'],
        'slug' => $data['slug'],
        'days' => $data['days'],
        'category_id' => $data['category_id'],
        'excerpt' => $data['excerpt'],
        'intro' => $data['intro'],
        'highlights' => $data['highlights'],
        'route_summary' => $data['route_summary'],
        'best_for' => $data['best_for'],
        'travel_notes' => $data['travel_notes'],
        'nearby_travel_times' => $data['nearby_travel_times'],
        'works_well_with' => $data['works_well_with'],
        'traveller_types' => $data['traveller_types'],
        'start_point' => $data['start_point'],
        'end_point' => $data['end_point'],
        'pricing_notes' => $data['pricing_notes'],
        'vehicle_options' => $data['vehicle_options'],
        'price_from' => $data['price_from'],
        'price_to' => $data['price_to'],
        'price_currency' => $data['price_currency'],
        'price_unit' => $data['price_unit'],
        'faq_json' => $data['faq_json'],
        'cta_title' => $data['cta_title'],
        'cta_text' => $data['cta_text'],
        'best_months_json' => $data['best_months_json'],
        'hero_image' => $heroMedia['path'],
        'hero_media_type' => $heroMedia['media_type'],
        'hero_media_asset_id' => $heroMedia['asset_id'],
        'hero_video_poster' => $heroPoster,
        'og_image' => $ogMedia['path'],
        'meta_title' => $data['meta_title'],
        'meta_description' => $data['meta_description'],
        'canonical_url' => $data['canonical_url'],
        'is_featured' => $data['is_featured'],
        'sort_order' => $data['sort_order'],
        'is_published' => $data['is_published'],
    ];

    $columnValueMap = array_merge($columnValueMap, $coverUpdates);
    foreach (['nearby_travel_times','works_well_with','traveller_types','start_point','end_point'] as $optional) { if (!array_key_exists($optional, $_POST) && !($optional === 'traveller_types' && ($_POST['traveller_types_present'] ?? '') === '1')) { unset($columnValueMap[$optional]); } }
    $writeData = [];
    foreach ($columnValueMap as $column => $value) {
        if (db_column_exists('itineraries', $column)) {
            if (in_array($column, ['best_months_json', 'faq_json'], true) && $value === '') {
                $writeData[$column] = null;
            } else {
                $writeData[$column] = $value;
            }
        }
    }

    if (!isset($writeData['name'], $writeData['slug'], $writeData['days'])) {
        throw new RuntimeException('Required itinerary columns are missing in database schema.');
    }

    if ($id > 0) {
        $setParts = [];
        foreach (array_keys($writeData) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }

        execute_query(
            'UPDATE itineraries SET ' . implode(', ', $setParts) . ' WHERE id = :id',
            array_merge($writeData, ['id' => $id])
        );
    } else {
        $columns = array_keys($writeData);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

        execute_query(
            'INSERT INTO itineraries (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')',
            $writeData
        );

        $id = (int) last_insert_id();
    }

    if (($_POST['destinations_present'] ?? '') === '1' || array_key_exists('destinations', $_POST)) {
        package_sync_links('itinerary_destination', 'itinerary_id', 'destination_id', $id, $destinationIds);
        package_sync_links('destination_itinerary', 'itinerary_id', 'destination_id', $id, $destinationIds);
    }

    db()->commit();

    $oldSlug = trim((string) ($record['slug'] ?? ''));
    $wasPublished = (int) ($record['is_published'] ?? 0) === 1;
    $isPublishedNow = (int) $data['is_published'] === 1;

    $indexNowUrls = [
        base_url('itineraries'),
        base_url('sitemap.xml'),
    ];

    if ($isPublishedNow) {
        $indexNowUrls[] = base_url('itineraries/' . $data['slug']);
    }

    if ($id > 0 && $wasPublished && $oldSlug !== '' && ($oldSlug !== $data['slug'] || !$isPublishedNow)) {
        $indexNowUrls[] = base_url('itineraries/' . $oldSlug);
    }

    indexnow_submit_urls($indexNowUrls);

    old_input_clear();
    flash_set('success', 'Itinerary saved successfully.');
    redirect('admin/itineraries.php');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }

    foreach (array_unique($legacyCreatedPaths) as $createdPath) { delete_uploaded_media($createdPath); }
    if ($coverCreatedPath !== null) { delete_uploaded_media($coverCreatedPath); }
    flash_set('error', ($e instanceof InvalidArgumentException || $e instanceof DomainException) ? $e->getMessage() : 'Failed to save itinerary.');
    redirect($id > 0 ? 'admin/itinerary_form.php?id=' . $id : 'admin/itinerary_form.php');
}
