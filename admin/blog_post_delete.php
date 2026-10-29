<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/blog_posts.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);

if ($id <= 0) {
    flash_set('error', 'Invalid blog post ID.');
    redirect('admin/blog_posts.php');
}

$record = fetch_one(
    "SELECT slug, is_published FROM blog_posts WHERE id = :id LIMIT 1",
    ['id' => $id]
);

if (!$record) {
    flash_set('error', 'Blog post not found.');
    redirect('admin/blog_posts.php');
}

try {
    execute_query("DELETE FROM blog_posts WHERE id = :id", ['id' => $id]);

    $indexNowUrls = [
        base_url('blog'),
        base_url('sitemap.xml'),
    ];

    if ($record && (int) ($record['is_published'] ?? 0) === 1 && !empty($record['slug'])) {
        $indexNowUrls[] = base_url('blog/' . $record['slug']);
    }

    indexnow_submit_urls($indexNowUrls);

    flash_set('success', 'Post deleted.');
} catch (Throwable $e) {
    flash_set('error', 'Failed to delete blog post.');
}

redirect('admin/blog_posts.php');
