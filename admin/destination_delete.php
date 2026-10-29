<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/destinations.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);

if ($id <= 0) {
    flash_set('error', 'Invalid destination ID.');
    redirect('admin/destinations.php');
}

$record = fetch_one(
    "SELECT slug, is_published FROM destinations WHERE id = :id LIMIT 1",
    ['id' => $id]
);

if (!$record) {
    flash_set('error', 'Destination not found.');
    redirect('admin/destinations.php');
}

try {
    db()->beginTransaction();

    if (db_table_exists('destination_related')) {
        execute_query(
            'DELETE FROM destination_related WHERE destination_id = :id OR related_destination_id = :related_id',
            ['id' => $id, 'related_id' => $id]
        );
    }
    if (db_table_exists('destination_tour')) {
        execute_query('DELETE FROM destination_tour WHERE destination_id = :id', ['id' => $id]);
    }
    if (db_table_exists('destination_itinerary')) {
        execute_query('DELETE FROM destination_itinerary WHERE destination_id = :id', ['id' => $id]);
    }
    if (db_table_exists('itinerary_destination')) {
        execute_query('DELETE FROM itinerary_destination WHERE destination_id = :id', ['id' => $id]);
    }

    execute_query("DELETE FROM destinations WHERE id = :id", ['id' => $id]);
    db()->commit();

    $indexNowUrls = [
        base_url('destinations'),
        base_url('sitemap.xml'),
    ];

    if ((int) ($record['is_published'] ?? 0) === 1 && !empty($record['slug'])) {
        $indexNowUrls[] = base_url('destinations/' . $record['slug']);
    }

    indexnow_submit_urls($indexNowUrls);

    flash_set('success', 'Destination deleted.');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash_set('error', 'Failed to delete destination.');
}
redirect('admin/destinations.php');
