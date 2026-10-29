<?php
declare(strict_types=1);

$adminTitle = 'Destinations';
$adminPage = 'destinations';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$page = max(1, (int) get_input('page', 1));
$perPage = 15;
$offset = paginate_offset($page, $perPage);
$search = trim((string) get_input('q', ''));

$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE name LIKE :search_name OR slug LIKE :search_slug";
    $params['search_name'] = '%' . $search . '%';
    $params['search_slug'] = '%' . $search . '%';
}

$total = fetch_one("SELECT COUNT(*) AS total FROM destinations $where", $params)['total'] ?? 0;

$paramsWithLimit = $params;
$paramsWithLimit['limit'] = $perPage;
$paramsWithLimit['offset'] = $offset;

$items = fetch_all("
    SELECT id, name, slug, is_featured, is_published, sort_order, updated_at
    FROM destinations
    $where
    ORDER BY sort_order ASC, id DESC
    LIMIT $perPage OFFSET $offset
", $params);

$pages = max(1, (int) ceil(((int) $total) / $perPage));

require __DIR__ . '/_top.php';
?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <form class="d-flex gap-2" method="get">
                <input type="text" name="q" class="form-control" placeholder="Search destinations..." value="<?= e($search) ?>">
                <button class="btn btn-outline-dark">Search</button>
            </form>

            <a href="<?= e(base_url('admin/destination_form.php')) ?>" class="btn btn-success">Add Destination</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Featured</th>
                    <th>Published</th>
                    <th>Sort</th>
                    <th>Updated</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($items): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['name']) ?></td>
                            <td><code><?= e($item['slug']) ?></code></td>
                            <td><?= (int) $item['is_featured'] ? 'Yes' : 'No' ?></td>
                            <td><?= (int) $item['is_published'] ? 'Yes' : 'No' ?></td>
                            <td><?= (int) $item['sort_order'] ?></td>
                            <td><?= e((string) $item['updated_at']) ?></td>
                            <td class="text-end">
                                <a href="<?= e(base_url('admin/destination_form.php?id=' . (int) $item['id'])) ?>" class="btn btn-sm btn-outline-dark">Edit</a>
                                <form method="post" action="<?= e(base_url('admin/destination_delete.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this destination?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center text-secondary">No destinations found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination mb-0">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item<?= $i === $page ? ' active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
