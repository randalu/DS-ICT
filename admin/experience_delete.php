<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();
ensure_experiences_schema();

if (!is_post()) {
    redirect('admin/experiences.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid experience ID.');
    redirect('admin/experiences.php');
}

$record = fetch_one("SELECT slug, is_published FROM experiences WHERE id = :id LIMIT 1", ['id' => $id]);
if (!$record) {
    flash_set('error', 'Experience not found.');
    redirect('admin/experiences.php');
}

try {
    $galleryPhotos = fetch_experience_gallery($id);
    $galleryPathsToDelete = array_values(array_filter(array_map(
        static fn(array $photo): string => (string) ($photo['image_path'] ?? ''),
        $galleryPhotos
    )));

    db()->beginTransaction();
    if (db_table_exists('experience_destination')) {
        execute_query('DELETE FROM experience_destination WHERE experience_id = :id', ['id' => $id]);
    }
    if (db_table_exists('experience_itinerary')) {
        execute_query('DELETE FROM experience_itinerary WHERE experience_id = :id', ['id' => $id]);
    }
    if (db_table_exists('experience_related')) {
        execute_query(
            'DELETE FROM experience_related WHERE experience_id = :id OR related_experience_id = :related_id',
            ['id' => $id, 'related_id' => $id]
        );
    }
    if (db_table_exists('experience_gallery')) {
        execute_query('DELETE FROM experience_gallery WHERE experience_id = :id', ['id' => $id]);
    }
    execute_query('DELETE FROM experiences WHERE id = :id', ['id' => $id]);
    db()->commit();

    foreach (array_unique($galleryPathsToDelete) as $galleryPath) {
        delete_uploaded_media($galleryPath);
    }

    $indexNowUrls = [base_url('experiences'), base_url('sitemap.xml')];
    if ((int) ($record['is_published'] ?? 0) === 1 && !empty($record['slug'])) {
        $indexNowUrls[] = base_url('experiences/' . $record['slug']);
    }
    indexnow_submit_urls($indexNowUrls);

    flash_set('success', 'Experience deleted.');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash_set('error', 'Failed to delete experience.');
}
redirect('admin/experiences.php');
