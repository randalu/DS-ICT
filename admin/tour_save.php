<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';
require_once __DIR__ . '/../includes/tour_coverage.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/tours.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);

$data = [
    'name' => trim((string) post_input('name', '')),
    'slug' => trim((string) post_input('slug', '')),
    'excerpt' => trim((string) post_input('excerpt', '')),
    'intro' => trim((string) post_input('intro', '')),
    'highlights' => trim((string) post_input('highlights', '')),
    'itinerary' => trim((string) post_input('itinerary', '')),
    'includes' => trim((string) post_input('includes', '')),
    'excludes' => trim((string) post_input('excludes', '')),
    'sample_itinerary' => trim((string) post_input('sample_itinerary', '')),
    'pricing_notes' => trim((string) post_input('pricing_notes', '')),
    'price_from' => normalize_price_input(post_input('price_from')),
    'price_to' => normalize_price_input(post_input('price_to')),
    'price_currency' => strtoupper(trim((string) post_input('price_currency', 'USD'))),
    'price_unit' => trim((string) post_input('price_unit', '')),
    'vehicle_options' => trim((string) post_input('vehicle_options', '')),
    'vehicle_type' => trim((string) post_input('vehicle_type', 'Air-conditioned car/van')),
    'faq_json' => trim((string) post_input('faq_json', '')),
    'cta_title' => trim((string) post_input('cta_title', '')),
    'cta_text' => trim((string) post_input('cta_text', '')),
    'meta_title' => trim((string) post_input('meta_title', '')),
    'meta_description' => trim((string) post_input('meta_description', '')),
    'canonical_url' => trim((string) post_input('canonical_url', '')),
    'meeting_point' => trim((string) post_input('meeting_point', '')),
    'duration_hours' => max(0.5, (float) post_input('duration_hours', 8)),
    'start_time' => preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', (string) post_input('start_time', '')) ? (string) post_input('start_time') : '08:00:00',
    'max_group_size' => max(1, (int) post_input('max_group_size', 6)),
    'difficulty' => in_array((string) post_input('difficulty', 'easy'), ['easy', 'moderate', 'active'], true) ? (string) post_input('difficulty') : 'easy',
    'best_time' => trim((string) post_input('best_time', '')),
    'best_time_json' => '',
    'sort_order' => (int) post_input('sort_order', 0),
    'is_featured' => post_input('is_featured') ? 1 : 0,
    'is_published' => post_input('is_published') ? 1 : 0,
];

$data['slug'] = $data['slug'] !== '' ? slugify($data['slug']) : slugify($data['name']);
$data['canonical_url'] = $data['canonical_url'] !== '' ? $data['canonical_url'] : base_url('tours/' . $data['slug']);

// Compose best_time_json from month checkboxes + free-text note
$bestTimeMonths = array_values(array_filter(array_map(
    static fn($m) => strtolower(trim((string) $m)),
    (array) post_input('best_time_months', [])
)));
$bestTimeNote = trim((string) post_input('best_time_note', ''));
if ($bestTimeMonths || $bestTimeNote !== '') {
    $data['best_time_json'] = json_encode([
        'months' => $bestTimeMonths,
        'note'   => $bestTimeNote,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

old_input_set($_POST);

$destinationIds = array_values(array_filter(array_unique(array_map(
    'intval',
    (array) post_input('destinations', [])
)), static fn(int $value): bool => $value > 0));

$errors = [];
$coverageSubmitted = ($_POST['coverage_present'] ?? null) === '1' && ($_POST['coverage_ready'] ?? null) === '1';
$coverageJson = null;
if ($coverageSubmitted) {
    try {
        if (!db_column_exists('tours', 'coverage_json')) {
            throw new InvalidArgumentException('Coverage editing requires the tour coverage migration.');
        }
        $coverageJson = json_encode(tour_coverage_normalize($_POST['coverage_items'] ?? [], 'tour_coverage_resolve'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    } catch (InvalidArgumentException | JsonException $e) {
        $errors[] = $e->getMessage();
    }
}

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
    "SELECT id FROM tours WHERE slug = :slug AND id != :id LIMIT 1",
    ['slug' => $data['slug'], 'id' => $id]
);

if ($existing) {
    $errors[] = 'Slug already exists.';
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($id > 0 ? 'admin/tour_form.php?id=' . $id : 'admin/tour_form.php');
}

$record = [];
if ($id > 0) {
    $record = fetch_one("SELECT * FROM tours WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Tour not found.');
        redirect('admin/tours.php');
    }
}

$coverCreatedPath = null;
$legacyCreatedPaths = [];
try {
    db()->beginTransaction();
    if ($id > 0) {
        $record = fetch_one('SELECT * FROM tours WHERE id=:id FOR UPDATE', ['id' => $id]) ?? throw new InvalidArgumentException('Package not found.');
        if (isset($_POST['package_revision']) && (!is_string($_POST['package_revision']) || !hash_equals(package_edit_revision($record), $_POST['package_revision']))) { throw new DomainException('This package changed in another editor. Reload and compare before saving.'); }
    }
    foreach (['itinerary','sample_itinerary'] as $legacyField) { if (!array_key_exists($legacyField,$_POST)) { $data[$legacyField]=$record[$legacyField] ?? null; } }
    $data['best_time_json'] = package_season_json((string) ($record['best_time_json'] ?? ''), $_POST, 'best_time_json', 'best_time_months', 'best_time_note');

    if ($coverageSubmitted && $id > 0) {
        $lockedCoverage = fetch_one('SELECT coverage_json FROM tours WHERE id = :id FOR UPDATE', ['id' => $id]);
        $submittedRevision = $_POST['coverage_revision'] ?? '';
        if (!is_string($submittedRevision) || !hash_equals(tour_coverage_revision($lockedCoverage['coverage_json'] ?? null), $submittedRevision)) {
            throw new DomainException('Coverage was changed in another editor. Load the saved coverage and review it before saving.');
        }
    }

    $coverUpdates = package_cover_updates('tour', $record, $_POST, $_FILES, $coverCreatedPath);
    $heroImage = $record['hero_image'] ?? null;
    $ogImage = $record['og_image'] ?? null;

    if (!empty($_FILES['hero_image']['name'])) {
        $heroImage = upload_image($_FILES['hero_image'], 'tours');
        if ($heroImage !== null) { $legacyCreatedPaths[] = $heroImage; }
    }
    if (!empty($_FILES['og_image']['name'])) {
        $ogImage = upload_image($_FILES['og_image'], 'tours');
        if ($ogImage !== null) { $legacyCreatedPaths[] = $ogImage; }
    }

    $columnValueMap = [
        'name' => $data['name'],
        'slug' => $data['slug'],
        'excerpt' => $data['excerpt'],
        'intro' => $data['intro'],
        'highlights' => $data['highlights'],
        'itinerary' => $data['itinerary'],
        'includes' => $data['includes'],
        'excludes' => $data['excludes'],
        'sample_itinerary' => $data['sample_itinerary'],
        'pricing_notes' => $data['pricing_notes'],
        'price_from' => $data['price_from'],
        'price_to' => $data['price_to'],
        'price_currency' => $data['price_currency'],
        'price_unit' => $data['price_unit'],
        'vehicle_options' => $data['vehicle_options'],
        'vehicle_type' => $data['vehicle_type'],
        'faq_json' => $data['faq_json'],
        'cta_title' => $data['cta_title'],
        'cta_text' => $data['cta_text'],
        'hero_image' => $heroImage,
        'og_image' => $ogImage,
        'meta_title' => $data['meta_title'],
        'meta_description' => $data['meta_description'],
        'canonical_url' => $data['canonical_url'],
        'meeting_point' => $data['meeting_point'],
        'duration_hours' => $data['duration_hours'],
        'start_time' => $data['start_time'],
        'max_group_size' => $data['max_group_size'],
        'difficulty' => $data['difficulty'],
        'best_time' => $data['best_time'],
        'best_time_json' => $data['best_time_json'],
        'is_featured' => $data['is_featured'],
        'sort_order' => $data['sort_order'],
        'is_published' => $data['is_published'],
    ];

    if ($coverageSubmitted) {
        $columnValueMap['coverage_json'] = $coverageJson;
    }
    $columnValueMap = array_merge($columnValueMap, $coverUpdates);
    $writeData = [];
    foreach ($columnValueMap as $column => $value) {
        if (db_column_exists('tours', $column)) {
            $writeData[$column] = $value;
        }
    }

    if (!isset($writeData['name'], $writeData['slug'])) {
        throw new RuntimeException('Required tour columns are missing in database schema.');
    }

    if ($id > 0) {
        $setParts = [];
        foreach (array_keys($writeData) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }

        execute_query(
            "UPDATE tours SET " . implode(', ', $setParts) . " WHERE id = :id",
            array_merge($writeData, ['id' => $id])
        );
    } else {
        $columns = array_keys($writeData);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

        execute_query(
            "INSERT INTO tours (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")",
            $writeData
        );

        $id = (int) last_insert_id();
    }

    if (($_POST['destinations_present'] ?? '') === '1' || array_key_exists('destinations', $_POST)) {
        package_sync_links('destination_tour', 'tour_id', 'destination_id', $id, $destinationIds);
    }

    db()->commit();

    $oldSlug = trim((string) ($record['slug'] ?? ''));
    $wasPublished = (int) ($record['is_published'] ?? 0) === 1;
    $isPublishedNow = (int) $data['is_published'] === 1;

    $indexNowUrls = [base_url('sitemap.xml')];

    if ($isPublishedNow) {
        $indexNowUrls[] = base_url('tours/' . $data['slug']);
    }

    if ($id > 0 && $wasPublished && $oldSlug !== '' && ($oldSlug !== $data['slug'] || !$isPublishedNow)) {
        $indexNowUrls[] = base_url('tours/' . $oldSlug);
    }

    indexnow_submit_urls($indexNowUrls);

    old_input_clear();
    flash_set('success', 'Tour saved successfully.');
    redirect('admin/tours.php');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }

    foreach (array_unique($legacyCreatedPaths) as $createdPath) { delete_uploaded_media($createdPath); }
    if ($coverCreatedPath !== null) { delete_uploaded_media($coverCreatedPath); }
    flash_set('error', $e instanceof DomainException || $e instanceof InvalidArgumentException ? $e->getMessage() : 'Failed to save tour.');
    redirect($id > 0 ? 'admin/tour_form.php?id=' . $id : 'admin/tour_form.php');
}
