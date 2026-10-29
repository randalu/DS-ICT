<?php
declare(strict_types=1);

$adminTitle = 'Itineraries';
$adminPage = 'itineraries';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$page = max(1, (int) get_input('page', 1));
$perPage = 15;
$offset = paginate_offset($page, $perPage);
$search = trim((string) get_input('q', ''));

$where = '';
$params = [];
$canUseCategories = db_table_exists('itinerary_categories') && db_column_exists('itineraries', 'category_id');

if ($search !== '') {
    $where = 'WHERE i.name LIKE :search_name OR i.slug LIKE :search_slug';
    $params['search_name'] = '%' . $search . '%';
    $params['search_slug'] = '%' . $search . '%';
}

$totalWhere = str_replace('i.', '', $where);
$total = fetch_one("SELECT COUNT(*) AS total FROM itineraries {$totalWhere}", $params)['total'] ?? 0;

$categoryJoin = $canUseCategories ? 'LEFT JOIN itinerary_categories ic ON ic.id = i.category_id' : '';
$categorySelect = $canUseCategories ? 'ic.name AS category_name,' : 'NULL AS category_name,';

$items = fetch_all(
    "SELECT
        i.id,
        i.name,
        i.slug,
        i.days,
        {$categorySelect}
        i.is_featured,
        i.is_published,
        i.sort_order,
        i.updated_at
     FROM itineraries i
     {$categoryJoin}
     {$where}
     ORDER BY i.sort_order ASC, i.days ASC, i.id DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

$pages = max(1, (int) ceil(((int) $total) / $perPage));

require __DIR__ . '/_top.php';
?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <form class="d-flex gap-2" method="get">
                <input type="text" name="q" class="form-control" placeholder="Search itineraries..." value="<?= e($search) ?>">
                <button class="btn btn-outline-dark">Search</button>
            </form>

            <a href="<?= e(base_url('admin/itinerary_form.php')) ?>" class="btn btn-success">Add Itinerary</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Days</th>
                    <th>Category</th>
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
                            <td><?= (int) $item['days'] ?></td>
                            <td><?= e((string) ($item['category_name'] ?? '-')) ?></td>
                            <td><code><?= e($item['slug']) ?></code></td>
                            <td><?= (int) $item['is_featured'] ? 'Yes' : 'No' ?></td>
                            <td><?= (int) $item['is_published'] ? 'Yes' : 'No' ?></td>
                            <td><?= (int) $item['sort_order'] ?></td>
                            <td><?= e((string) $item['updated_at']) ?></td>
                            <td class="text-end">
                                <a href="<?= e(base_url('admin/itinerary_days.php?itinerary_id=' . (int) $item['id'])) ?>" class="btn btn-sm btn-outline-secondary">Days</a>
                                <a href="<?= e(base_url('admin/itinerary_form.php?id=' . (int) $item['id'])) ?>" class="btn btn-sm btn-outline-dark">Edit</a>
                                <form method="post" action="<?= e(base_url('admin/itinerary_delete.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this itinerary?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center text-secondary">No itineraries found.</td></tr>
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
