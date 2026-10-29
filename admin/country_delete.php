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

if ($id <= 0) {
    flash_set('error', 'Invalid country page ID.');
    redirect('admin/countries.php');
}

$record = fetch_one(
    "SELECT slug, is_published FROM country_pages WHERE id = :id LIMIT 1",
    ['id' => $id]
);

if (!$record) {
    flash_set('error', 'Country page not found.');
    redirect('admin/countries.php');
}

try {
    execute_query("DELETE FROM country_pages WHERE id = :id", ['id' => $id]);

    $indexNowUrls = [
        base_url('from'),
        base_url('sitemap.xml'),
    ];

    if ((int) ($record['is_published'] ?? 0) === 1 && !empty($record['slug'])) {
        $indexNowUrls[] = base_url('from/' . $record['slug']);
    }

    indexnow_submit_urls($indexNowUrls);
    flash_set('success', 'Country page deleted.');
} catch (Throwable $e) {
    flash_set('error', 'Failed to delete country page.');
}
redirect('admin/countries.php');
