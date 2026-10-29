<?php
declare(strict_types=1);

$adminPage = 'blog';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$adminTitle = 'Blog Categories';

if (is_post()) {
    guard_post_size();
    require_csrf();
    $action = (string) post_input('action', 'add');

    if ($action === 'delete') {
        $catId = (int) post_input('id', 0);
        if ($catId > 0) {
            execute_query("UPDATE blog_posts SET category_id = NULL WHERE category_id = :id", ['id' => $catId]);
            execute_query("DELETE FROM blog_categories WHERE id = :id", ['id' => $catId]);
            flash_set('success', 'Category deleted.');
        }
        redirect('admin/blog_categories.php');
    }

    $name = trim((string) post_input('name', ''));
    if ($name !== '') {
        $slug = slugify($name);
        execute_query(
            "INSERT IGNORE INTO blog_categories (name, slug) VALUES (:name, :slug)",
            ['name' => $name, 'slug' => $slug]
        );
        flash_set('success', 'Category added.');
    }
    redirect('admin/blog_categories.php');
}

$categories = fetch_all("SELECT bc.*, COUNT(bp.id) AS post_count FROM blog_categories bc LEFT JOIN blog_posts bp ON bp.category_id = bc.id GROUP BY bc.id ORDER BY bc.sort_order ASC, bc.name ASC");

require __DIR__ . '/_top.php';
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-3">Add Category</h2>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <button class="btn btn-success btn-sm">Add</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-soft">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Name</th><th>Slug</th><th>Posts</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= e($cat['name']) ?></td>
                            <td class="text-secondary small"><?= e($cat['slug']) ?></td>
                            <td><?= (int)$cat['post_count'] ?></td>
                            <td class="text-end">
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
