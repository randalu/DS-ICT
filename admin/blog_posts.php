<?php
declare(strict_types=1);

$adminPage = 'blog';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$adminTitle = 'Blog Posts';

$page    = max(1, (int) get_input('page', 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$posts = fetch_all(
    "SELECT bp.*, bc.name AS category_name
     FROM blog_posts bp
     LEFT JOIN blog_categories bc ON bc.id = bp.category_id
     ORDER BY bp.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);

$total = (int) (fetch_one("SELECT COUNT(*) AS c FROM blog_posts")['c'] ?? 0);

require __DIR__ . '/_top.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('admin/ai_search_audit.php')) ?>" class="btn btn-sm btn-outline-primary">AI/Search Audit</a>
        <a href="<?= e(base_url('admin/blog_categories.php')) ?>" class="btn btn-sm btn-outline-secondary">Categories</a>
    </div>
    <a href="<?= e(base_url('admin/blog_post_form.php')) ?>" class="btn btn-sm btn-success">+ New Post</a>
</div>

<div class="card card-soft">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$posts): ?>
                    <tr><td colspan="5" class="text-secondary py-4 text-center">No posts yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($posts as $post): ?>
                <?php
                $publishedAtTs = !empty($post['published_at']) ? strtotime((string) $post['published_at']) : false;
                $isScheduled = (int) ($post['is_published'] ?? 0) === 1 && $publishedAtTs && $publishedAtTs > time();
                $isLive = (int) ($post['is_published'] ?? 0) === 1 && (!$publishedAtTs || $publishedAtTs <= time());
                $publicUrl = base_url('blog/' . $post['slug']);
                $previewUrl = $publicUrl . '?preview=1';
                ?>
                <tr>
                    <td><?= e($post['title']) ?></td>
                    <td><?= e($post['category_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($isScheduled): ?>
                            <span class="badge bg-warning text-dark">Scheduled</span>
                        <?php elseif ($post['is_published']): ?>
                            <span class="badge bg-success">Published</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-secondary small"><?= $post['published_at'] ? date('d M Y', strtotime((string)$post['published_at'])) : '—' ?></td>
                    <td class="text-end">
                        <?php if ($isLive): ?>
                            <a href="<?= e($publicUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">View</a>
                        <?php else: ?>
                            <a href="<?= e($previewUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Preview</a>
                        <?php endif; ?>
                        <a href="<?= e(base_url('admin/blog_post_form.php?id=' . $post['id'])) ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                        <form method="post" action="<?= e(base_url('admin/blog_post_delete.php')) ?>" class="d-inline" onsubmit="return confirm('Delete post?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
