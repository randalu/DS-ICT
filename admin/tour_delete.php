<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/tours.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);

if ($id <= 0) {
    flash_set('error', 'Invalid tour ID.');
    redirect('admin/tours.php');
}

$record = fetch_one(
    "SELECT slug, is_published FROM tours WHERE id = :id LIMIT 1",
    ['id' => $id]
);

if (!$record) {
    flash_set('error', 'Tour not found.');
    redirect('admin/tours.php');
}

try {
    db()->beginTransaction();

    if (db_table_exists('destination_tour')) {
        execute_query('DELETE FROM destination_tour WHERE tour_id = :id', ['id' => $id]);
    }
    if (db_table_exists('itinerary_tour')) {
        execute_query('DELETE FROM itinerary_tour WHERE tour_id = :id', ['id' => $id]);
    }

    execute_query("DELETE FROM tours WHERE id = :id", ['id' => $id]);
    db()->commit();

    $indexNowUrls = [base_url('sitemap.xml')];

    if ((int) ($record['is_published'] ?? 0) === 1 && !empty($record['slug'])) {
        $indexNowUrls[] = base_url('tours/' . $record['slug']);
    }

    indexnow_submit_urls($indexNowUrls);

    flash_set('success', 'Tour deleted.');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash_set('error', 'Failed to delete tour.');
}
redirect('admin/tours.php');
