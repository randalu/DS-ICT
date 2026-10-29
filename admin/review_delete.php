<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/reviews.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);

if ($id <= 0) {
    flash_set('error', 'Invalid review ID.');
    redirect('admin/reviews.php');
}

try {
    $record = fetch_one('SELECT id FROM reviews WHERE id = :id LIMIT 1', ['id' => $id]);
    if (!$record) {
        flash_set('error', 'Review not found.');
        redirect('admin/reviews.php');
    }

    $photos = fetch_review_photos($id);
    $photoPathsToDelete = array_values(array_filter(array_map(
        static fn(array $photo): string => (string) ($photo['image_path'] ?? ''),
        $photos
    )));

    db()->beginTransaction();
    if (db_table_exists('review_photos')) {
        execute_query("DELETE FROM review_photos WHERE review_id = :id", ['id' => $id]);
    }
    execute_query("DELETE FROM reviews WHERE id = :id", ['id' => $id]);
    db()->commit();

    foreach (array_unique($photoPathsToDelete) as $photoPath) {
        delete_uploaded_media($photoPath);
    }

    flash_set('success', 'Review deleted.');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash_set('error', 'Failed to delete review.');
}

redirect('admin/reviews.php');
