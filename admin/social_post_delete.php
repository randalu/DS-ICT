<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/social_posts.php');
}

guard_post_size();
require_csrf();
ensure_social_marketing_schema();

$id = (int) post_input('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid social draft selected.');
    redirect('admin/social_posts.php');
}

$post = fetch_one('SELECT id FROM social_media_posts WHERE id = :id LIMIT 1', ['id' => $id]);
if (!$post) {
    flash_set('error', 'Social draft not found.');
    redirect('admin/social_posts.php');
}

try {
    db()->beginTransaction();
    execute_query('DELETE FROM social_media_post_assets WHERE post_id = :post_id', ['post_id' => $id]);
    execute_query('DELETE FROM social_media_posts WHERE id = :id', ['id' => $id]);
    db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash_set('error', 'Unable to delete social draft. Please try again.');
    redirect('admin/social_posts.php');
}

flash_set('success', 'Social draft deleted.');
redirect('admin/social_posts.php');
