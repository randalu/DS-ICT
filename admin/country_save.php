<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/countries.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);

if (!db_table_exists('country_pages')) {
    flash_set('error', 'Country page management is not available in this database.');
    redirect('admin/countries.php');
}

$data = [
    'country_name' => trim((string) post_input('country_name', '')),
    'slug' => trim((string) post_input('slug', '')),
    'excerpt' => trim((string) post_input('excerpt', '')),
    'intro' => trim((string) post_input('intro', '')),
    'why_sri_lanka' => trim((string) post_input('why_sri_lanka', '')),
    'recommended_itinerary' => trim((string) post_input('recommended_itinerary', '')),
    'travel_info' => trim((string) post_input('travel_info', '')),
    'best_time' => trim((string) post_input('best_time', '')),
    'faq_json' => trim((string) post_input('faq_json', '')),
    'cta_title' => trim((string) post_input('cta_title', '')),
    'cta_text' => trim((string) post_input('cta_text', '')),
    'meta_title' => trim((string) post_input('meta_title', '')),
    'meta_description' => trim((string) post_input('meta_description', '')),
    'canonical_url' => trim((string) post_input('canonical_url', '')),
    'sort_order' => (int) post_input('sort_order', 0),
    'is_published' => post_input('is_published') ? 1 : 0,
];

$data['slug'] = $data['slug'] !== '' ? slugify($data['slug']) : slugify($data['country_name']);
$data['canonical_url'] = $data['canonical_url'] !== '' ? $data['canonical_url'] : base_url('from/' . $data['slug']);

old_input_set($_POST);

$errors = [];

if ($data['country_name'] === '') {
    $errors[] = 'Country name is required.';
}

if ($data['slug'] === '') {
    $errors[] = 'Slug could not be generated.';
}

$lengthLimits = [
    'country_name' => 120,
    'slug' => 180,
    'excerpt' => 500,
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

if ($data['faq_json'] !== '') {
    $decoded = json_decode($data['faq_json'], true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = 'FAQ JSON is invalid.';
    }
}

$existing = fetch_one(
    "SELECT id FROM country_pages WHERE slug = :slug AND id != :id LIMIT 1",
    ['slug' => $data['slug'], 'id' => $id]
);

if ($existing) {
    $errors[] = 'Slug already exists.';
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($id > 0 ? 'admin/country_form.php?id=' . $id : 'admin/country_form.php');
}

$record = [];
if ($id > 0) {
    $record = fetch_one("SELECT * FROM country_pages WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Country page not found.');
        redirect('admin/countries.php');
    }
}

try {
    $heroImage = $record['hero_image'] ?? null;
    $ogImage = $record['og_image'] ?? null;

    if (!empty($_FILES['hero_image']['name'])) {
        $heroImage = upload_image($_FILES['hero_image'], 'countries');
    }
    if (!empty($_FILES['og_image']['name'])) {
        $ogImage = upload_image($_FILES['og_image'], 'countries');
    }

    $columnValueMap = [
        'country_name' => $data['country_name'],
        'slug' => $data['slug'],
        'excerpt' => $data['excerpt'],
        'intro' => $data['intro'],
        'why_sri_lanka' => $data['why_sri_lanka'],
        'recommended_itinerary' => $data['recommended_itinerary'],
        'travel_info' => $data['travel_info'],
        'best_time' => $data['best_time'],
        'faq_json' => $data['faq_json'],
        'cta_title' => $data['cta_title'],
        'cta_text' => $data['cta_text'],
        'hero_image' => $heroImage,
        'og_image' => $ogImage,
        'meta_title' => $data['meta_title'],
        'meta_description' => $data['meta_description'],
        'canonical_url' => $data['canonical_url'],
        'sort_order' => $data['sort_order'],
        'is_published' => $data['is_published'],
    ];

    $writeData = [];
    foreach ($columnValueMap as $column => $value) {
        if (db_column_exists('country_pages', $column)) {
            $writeData[$column] = $value;
        }
    }

    if (!isset($writeData['country_name'], $writeData['slug'])) {
        throw new RuntimeException('Required country page columns are missing in database schema.');
    }

    if ($id > 0) {
        $setParts = [];
        foreach (array_keys($writeData) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }

        execute_query(
            "UPDATE country_pages SET " . implode(', ', $setParts) . " WHERE id = :id",
            array_merge($writeData, ['id' => $id])
        );
    } else {
        $columns = array_keys($writeData);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

        execute_query(
            "INSERT INTO country_pages (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")",
            $writeData
        );

        $id = (int) last_insert_id();
    }

    old_input_clear();

    $oldSlug = trim((string) ($record['slug'] ?? ''));
    $wasPublished = (int) ($record['is_published'] ?? 0) === 1;
    $isPublishedNow = (int) $data['is_published'] === 1;

    $indexNowUrls = [
        base_url('from'),
        base_url('sitemap.xml'),
    ];

    if ($isPublishedNow) {
        $indexNowUrls[] = base_url('from/' . $data['slug']);
    }

    if ($id > 0 && $wasPublished && $oldSlug !== '' && ($oldSlug !== $data['slug'] || !$isPublishedNow)) {
        $indexNowUrls[] = base_url('from/' . $oldSlug);
    }

    indexnow_submit_urls($indexNowUrls);

    flash_set('success', 'Country page saved successfully.');
    redirect('admin/countries.php');
} catch (Throwable $e) {
    flash_set('error', 'Failed to save country page.');
    redirect($id > 0 ? 'admin/country_form.php?id=' . $id : 'admin/country_form.php');
}
