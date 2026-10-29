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

if ($id <= 0) {
    flash_set('error', 'Invalid itinerary ID.');
    redirect('admin/itineraries.php');
}

$record = fetch_one(
    "SELECT slug, is_published FROM itineraries WHERE id = :id LIMIT 1",
    ['id' => $id]
);

if (!$record) {
    flash_set('error', 'Itinerary not found.');
    redirect('admin/itineraries.php');
}

try {
    db()->beginTransaction();

    if (db_table_exists('itinerary_destination')) {
        execute_query('DELETE FROM itinerary_destination WHERE itinerary_id = :id', ['id' => $id]);
    }
    if (db_table_exists('destination_itinerary')) {
        execute_query('DELETE FROM destination_itinerary WHERE itinerary_id = :id', ['id' => $id]);
    }
    if (db_table_exists('itinerary_tour')) {
        execute_query('DELETE FROM itinerary_tour WHERE itinerary_id = :id', ['id' => $id]);
    }
    if (db_table_exists('itinerary_days')) {
        execute_query('DELETE FROM itinerary_days WHERE itinerary_id = :id', ['id' => $id]);
    }

    execute_query("DELETE FROM itineraries WHERE id = :id", ['id' => $id]);
    db()->commit();

    $indexNowUrls = [
        base_url('itineraries'),
        base_url('sitemap.xml'),
    ];

    if ((int) ($record['is_published'] ?? 0) === 1 && !empty($record['slug'])) {
        $indexNowUrls[] = base_url('itineraries/' . $record['slug']);
    }

    indexnow_submit_urls($indexNowUrls);

    flash_set('success', 'Itinerary deleted.');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash_set('error', 'Failed to delete itinerary.');
}
redirect('admin/itineraries.php');
