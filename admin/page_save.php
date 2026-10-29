<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/trust_pages.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/pages.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);

if (!db_table_exists('static_pages')) {
    flash_set('error', 'Static page management is not available in this database.');
    redirect('admin/pages.php');
}

$data = [
    'title' => trim((string) post_input('title', '')),
    'slug' => trim((string) post_input('slug', '')),
    'content' => trim((string) post_input('content', '')),
    'faq_json' => trim((string) post_input('faq_json', '')),
    'cta_title' => trim((string) post_input('cta_title', '')),
    'cta_text' => trim((string) post_input('cta_text', '')),
    'meta_title' => trim((string) post_input('meta_title', '')),
    'meta_description' => trim((string) post_input('meta_description', '')),
    'canonical_url' => trim((string) post_input('canonical_url', '')),
    'sort_order' => (int) post_input('sort_order', 0),
    'is_published' => post_input('is_published') ? 1 : 0,
];

if(isset($_POST['layout_style'])){
    if(!is_string($_POST['layout_style']) || !in_array($_POST['layout_style'],['document','travel'],true)){flash_set('error','Choose a supported page layout.');redirect('admin/page_form.php?id='.$id);}
    $data['layout_style']=$_POST['layout_style'];
}
if(isset($_POST['intro'])){if(!is_string($_POST['intro']) || mb_strlen($_POST['intro'])>30000){flash_set('error','Introduction is too long or invalid.');redirect('admin/page_form.php?id='.$id);}$data['intro']=trim($_POST['intro']);}
$data['slug'] = $data['slug'] !== '' ? slugify($data['slug']) : slugify($data['title']);
$data['canonical_url'] = $data['canonical_url'] !== '' ? $data['canonical_url'] : static_page_public_url($data['slug']);

old_input_set($_POST);

$errors = [];
require_once __DIR__ . '/../includes/retired_package_pages.php';
if (package_reference_path_retired($data['slug']) || package_reference_path_retired($data['canonical_url'])) { $errors[] = 'Duration reference pages are permanently retired. Put Tour details in the Tour programme.'; }

if ($data['title'] === '') {
    $errors[] = 'Title is required.';
}

if ($data['slug'] === '') {
    $errors[] = 'Slug could not be generated.';
}

$lengthLimits = [
    'title' => 150,
    'slug' => 180,
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

$originalTrustRecord = $id > 0 ? fetch_one('SELECT * FROM static_pages WHERE id = ?', [$id]) : null;
$trustSlugs = array_merge(array_keys(published_static_page_trust_routes()), ['privacy-policy']);
$isTrustPage = in_array($data['slug'], $trustSlugs, true) || in_array((string)($originalTrustRecord['slug'] ?? ''), $trustSlugs, true);
if ($isTrustPage) {
    $data = trust_page_normalize_optional_fields($data);
    if ($originalTrustRecord && (string)$originalTrustRecord['slug'] !== $data['slug']) { $errors[] = 'Keep the existing trust-page slug; URL changes require a separate migration plan.'; }
    if ($data['is_published'] === 1) {
        $errors = array_merge($errors, trust_page_publication_issues($data));
        if (post_input('trust_owner_approved') !== '1') { $errors[] = 'Confirm owner approval of this exact wording before publication.'; }
    }
}

$existing = fetch_one(
    "SELECT id FROM static_pages WHERE slug = :slug AND id != :id LIMIT 1",
    ['slug' => $data['slug'], 'id' => $id]
);

if ($existing) {
    $errors[] = 'Slug already exists.';
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($id > 0 ? 'admin/page_form.php?id=' . $id : 'admin/page_form.php');
}

$record = [];
if ($id > 0) {
    $record = fetch_one("SELECT * FROM static_pages WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Static page not found.');
        redirect('admin/pages.php');
    }
}

try {
    ensure_static_pages_extended_schema();
    $heroMedia = admin_resolve_media_field('hero_image', 'pages', [
        'path' => $record['hero_image'] ?? null,
        'asset_id' => $record['hero_media_asset_id'] ?? null,
        'media_type' => $record['hero_media_type'] ?? 'image',
    ], true);
    $heroPoster = $record['hero_video_poster'] ?? null;
    if (!empty($_FILES['hero_image_poster']['name'])) {
        $posterMedia = save_media_asset_from_upload($_FILES['hero_image_poster'], 'pages', false);
        $heroPoster = $posterMedia['file_path'];
    }

    $columnValueMap = [
        'title' => $data['title'],
        'slug' => $data['slug'],
        'content' => $data['content'],
        'faq_json' => $data['faq_json'],
        'cta_title' => $data['cta_title'],
        'cta_text' => $data['cta_text'],
        'hero_image' => $heroMedia['path'],
        'hero_media_type' => $heroMedia['media_type'],
        'hero_media_asset_id' => $heroMedia['asset_id'],
        'hero_video_poster' => $heroPoster,
        'meta_title' => $data['meta_title'],
        'meta_description' => $data['meta_description'],
        'canonical_url' => $data['canonical_url'],
        'sort_order' => $data['sort_order'],
        'is_published' => $data['is_published'],
    ];

    foreach(['intro','layout_style'] as $field)if(array_key_exists($field,$data))$columnValueMap[$field]=$data[$field];
    $writeData = [];
    foreach ($columnValueMap as $column => $value) {
        if (db_column_exists('static_pages', $column)) {
            if ($column === 'faq_json' && $value === '') {
                $writeData[$column] = null;
            } else {
                $writeData[$column] = $value;
            }
        }
    }

    if (!isset($writeData['title'], $writeData['slug'])) {
        throw new RuntimeException('Required static page columns are missing in database schema.');
    }

    if ($id > 0) {
        $setParts = [];
        foreach (array_keys($writeData) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }

        execute_query(
            "UPDATE static_pages SET " . implode(', ', $setParts) . " WHERE id = :id",
            array_merge($writeData, ['id' => $id])
        );
    } else {
        $columns = array_keys($writeData);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

        execute_query(
            "INSERT INTO static_pages (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")",
            $writeData
        );

        $id = (int) last_insert_id();
    }

    $oldSlug = trim((string) ($record['slug'] ?? ''));
    $wasPublished = (int) ($record['is_published'] ?? 0) === 1;
    $isPublishedNow = (int) $data['is_published'] === 1;

    $indexNowUrls = [base_url('sitemap.xml')];

    if ($isPublishedNow) {
        $indexNowUrls[] = base_url($data['slug']);
    }

    if ($wasPublished && $oldSlug !== '' && ($oldSlug !== $data['slug'] || !$isPublishedNow)) {
        $indexNowUrls[] = base_url($oldSlug);
    }

    indexnow_submit_urls($indexNowUrls);

    old_input_clear();
    flash_set('success', 'Static page saved successfully.');
    redirect('admin/pages.php');
} catch (Throwable $e) {
    flash_set('error', 'Failed to save static page.');
    redirect($id > 0 ? 'admin/page_form.php?id=' . $id : 'admin/page_form.php');
}
