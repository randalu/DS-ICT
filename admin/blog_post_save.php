<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';
require_once __DIR__ . '/../includes/blog_widgets.php';

admin_require_auth();
ensure_blog_posts_sidebar_schema();

if (!is_post()) {
    redirect('admin/blog_posts.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);

$data = [
    'category_id'      => post_input('category_id') ? (int) post_input('category_id') : null,
    'title'            => trim((string) post_input('title', '')),
    'slug'             => trim((string) post_input('slug', '')),
    'excerpt'          => trim((string) post_input('excerpt', '')),
    'content'          => trim((string) post_input('content', '')),
    'author'           => trim((string) post_input('author', 'Island Culture Travels')),
    'meta_title'       => trim((string) post_input('meta_title', '')),
    'meta_description' => trim((string) post_input('meta_description', '')),
    'canonical_url'    => trim((string) post_input('canonical_url', '')),
    'published_at'     => trim((string) post_input('published_at', '')),
    'is_featured'      => post_input('is_featured') ? 1 : 0,
    'is_published'     => post_input('is_published') ? 1 : 0,
];

$data['slug'] = $data['slug'] !== '' ? slugify($data['slug']) : slugify($data['title']);

old_input_set($_POST);

$errors = [];

$rawWidgetsJson = trim((string) post_input('widgets_v2_json', ''));
$widgetInput = ['version' => 2, 'widgets' => []];
if ($rawWidgetsJson !== '') {
    $decodedWidgets = json_decode($rawWidgetsJson, true);
    if (!is_array($decodedWidgets) || json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = 'Widget configuration JSON is invalid.';
    } else {
        $widgetInput = $decodedWidgets;
    }
}
$widgetsV2 = validate_widget_config($widgetInput);
$widgetsV2Json = json_encode($widgetsV2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$data['sidebar_widgets_json'] = $widgetsV2Json !== false ? $widgetsV2Json : '{"version":2,"widgets":[]}';
$data['canonical_url'] = $data['canonical_url'] !== '' ? $data['canonical_url'] : base_url('blog/' . $data['slug']);

if ($data['published_at'] !== '') {
    $publishedAtTimestamp = strtotime($data['published_at']);
    if ($publishedAtTimestamp === false) {
        $errors[] = 'Publish date is invalid.';
    } else {
        $data['published_at'] = date('Y-m-d H:i:s', $publishedAtTimestamp);
    }
} else {
    $data['published_at'] = date('Y-m-d H:i:s');
}

if ($data['title'] === '') {
    $errors[] = 'Title is required.';
}

$lengthLimits = [
    'title' => 255,
    'slug' => 180,
    'excerpt' => 500,
    'author' => 150,
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

if ($data['category_id'] !== null) {
    if (!db_table_exists('blog_categories') || !db_column_exists('blog_categories', 'id')) {
        $errors[] = 'Blog categories are not available in this database.';
    } else {
        $category = fetch_one('SELECT id FROM blog_categories WHERE id = :id LIMIT 1', ['id' => $data['category_id']]);
        if (!$category) {
            $errors[] = 'Selected blog category is invalid.';
        }
    }
}

$existing = fetch_one("SELECT id FROM blog_posts WHERE slug = :slug AND id != :id LIMIT 1", ['slug' => $data['slug'], 'id' => $id]);
if ($existing) {
    $errors[] = 'Slug already exists.';
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($id > 0 ? 'admin/blog_post_form.php?id=' . $id : 'admin/blog_post_form.php');
}

$record = $id > 0 ? (fetch_one("SELECT * FROM blog_posts WHERE id = :id", ['id' => $id]) ?: []) : [];

try {
    $heroMedia = admin_resolve_media_field('hero_image', 'blog', [
        'path' => $record['hero_image'] ?? null,
        'asset_id' => $record['hero_media_asset_id'] ?? null,
        'media_type' => $record['hero_media_type'] ?? 'image',
    ], true);
    $ogMedia = admin_resolve_media_field('og_image', 'blog', ['path' => $record['og_image'] ?? null]);
    $heroPoster = $record['hero_video_poster'] ?? null;
    if (!empty($_FILES['hero_image_poster']['name'])) {
        $posterMedia = save_media_asset_from_upload($_FILES['hero_image_poster'], 'blog', false);
        $heroPoster = $posterMedia['file_path'];
    }

    $mediaData = [
        'hero_image' => $heroMedia['path'],
        'hero_media_type' => $heroMedia['media_type'],
        'hero_media_asset_id' => $heroMedia['asset_id'],
        'hero_video_poster' => $heroPoster,
        'og_image' => $ogMedia['path'],
    ];

    if ($id > 0) {
        $setParts = [
            'category_id=:category_id', 'title=:title', 'slug=:slug', 'excerpt=:excerpt',
            'content=:content', 'hero_image=:hero_image', 'og_image=:og_image', 'author=:author',
            'meta_title=:meta_title', 'meta_description=:meta_description', 'canonical_url=:canonical_url',
            'sidebar_widgets_json=:sidebar_widgets_json', 'published_at=:published_at',
            'is_featured=:is_featured', 'is_published=:is_published',
        ];
        foreach (['hero_media_type', 'hero_media_asset_id', 'hero_video_poster'] as $column) {
            if (db_column_exists('blog_posts', $column)) {
                $setParts[] = "{$column}=:{$column}";
            }
        }
        execute_query(
            "UPDATE blog_posts SET " . implode(', ', $setParts) . " WHERE id=:id",
            array_merge($data, array_intersect_key($mediaData, array_flip(array_merge(['hero_image', 'og_image'], array_filter(['hero_media_type', 'hero_media_asset_id', 'hero_video_poster'], static fn($c) => db_column_exists('blog_posts', $c))))), ['id' => $id])
        );
    } else {
        $columns = ['category_id', 'title', 'slug', 'excerpt', 'content', 'hero_image', 'og_image', 'author', 'meta_title', 'meta_description', 'canonical_url', 'sidebar_widgets_json', 'published_at', 'is_featured', 'is_published'];
        foreach (['hero_media_type', 'hero_media_asset_id', 'hero_video_poster'] as $column) {
            if (db_column_exists('blog_posts', $column)) {
                $columns[] = $column;
            }
        }
        $writeData = array_merge($data, array_intersect_key($mediaData, array_flip($columns)));
        execute_query(
            "INSERT INTO blog_posts (" . implode(', ', $columns) . ")
             VALUES (:" . implode(', :', $columns) . ")",
            array_intersect_key($writeData, array_flip($columns))
        );
        $id = (int) last_insert_id();
    }

    $oldSlug = trim((string) ($record['slug'] ?? ''));
    $wasPublished = (int) ($record['is_published'] ?? 0) === 1;
    $isPublishedNow = (int) $data['is_published'] === 1;
    $isPublicNow = $isPublishedNow && strtotime((string) $data['published_at']) <= time();

    $indexNowUrls = [
        base_url('blog'),
        base_url('sitemap.xml'),
    ];

    if ($isPublicNow) {
        $indexNowUrls[] = base_url('blog/' . $data['slug']);
    }

    if ($id > 0 && $wasPublished && $oldSlug !== '' && ($oldSlug !== $data['slug'] || !$isPublicNow)) {
        $indexNowUrls[] = base_url('blog/' . $oldSlug);
    }

    indexnow_submit_urls($indexNowUrls);

    old_input_clear();
    flash_set('success', 'Post saved.');
    redirect('admin/blog_posts.php');
} catch (Throwable $e) {
    flash_set('error', 'Failed to save post.');
    redirect($id > 0 ? 'admin/blog_post_form.php?id=' . $id : 'admin/blog_post_form.php');
}
