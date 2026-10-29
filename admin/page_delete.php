<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
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

if ($id <= 0) {
    flash_set('error', 'Invalid page ID.');
    redirect('admin/pages.php');
}

$record = fetch_one(
    "SELECT slug, is_published FROM static_pages WHERE id = :id LIMIT 1",
    ['id' => $id]
);

if (!$record) {
    flash_set('error', 'Static page not found.');
    redirect('admin/pages.php');
}

try {
    execute_query("DELETE FROM static_pages WHERE id = :id", ['id' => $id]);
} catch (Throwable $e) {
    flash_set('error', 'Failed to delete static page.');
    redirect('admin/pages.php');
}

$indexNowUrls = [base_url('sitemap.xml')];

if ($record && (int) ($record['is_published'] ?? 0) === 1 && !empty($record['slug'])) {
    $indexNowUrls[] = base_url($record['slug']);
}

indexnow_submit_urls($indexNowUrls);

flash_set('success', 'Static page deleted.');
redirect('admin/pages.php');
