<?php
declare(strict_types=1);

$adminTitle = 'Country Pages';
$adminPage = 'countries';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$hasCountryPages = db_table_exists('country_pages');
$page = max(1, (int) get_input('page', 1));
$perPage = 15;
$offset = paginate_offset($page, $perPage);
$search = trim((string) get_input('q', ''));

$where = '';
$params = [];

if ($hasCountryPages && $search !== '') {
    $where = "WHERE country_name LIKE :search_country OR slug LIKE :search_slug";
    $params['search_country'] = '%' . $search . '%';
    $params['search_slug'] = '%' . $search . '%';
}

$total = $hasCountryPages ? (fetch_one("SELECT COUNT(*) AS total FROM country_pages $where", $params)['total'] ?? 0) : 0;

$items = $hasCountryPages
    ? fetch_all("
        SELECT id, country_name, slug, is_published, sort_order, updated_at
        FROM country_pages
        $where
        ORDER BY sort_order ASC, country_name ASC
        LIMIT $perPage OFFSET $offset
    ", $params)
    : [];

$pages = max(1, (int) ceil(((int) $total) / $perPage));

require __DIR__ . '/_top.php';
?>

<div class="card card-soft">
    <div class="card-body">
        <?php if (!$hasCountryPages): ?>
            <div class="alert alert-warning mb-0">Country page management is not available because the <code>country_pages</code> table is missing.</div>
        <?php else: ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <form class="d-flex gap-2" method="get">
                <input type="text" name="q" class="form-control" placeholder="Search country pages..." value="<?= e($search) ?>">
                <button class="btn btn-outline-dark">Search</button>
            </form>

            <a href="<?= e(base_url('admin/country_form.php')) ?>" class="btn btn-success">Add Country Page</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Country</th>
                    <th>Slug</th>
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
                            <td><?= e($item['country_name']) ?></td>
                            <td><code><?= e($item['slug']) ?></code></td>
                            <td><?= (int) $item['is_published'] ? 'Yes' : 'No' ?></td>
                            <td><?= (int) $item['sort_order'] ?></td>
                            <td><?= e((string) $item['updated_at']) ?></td>
                            <td class="text-end">
                                <a href="<?= e(base_url('admin/country_form.php?id=' . (int) $item['id'])) ?>" class="btn btn-sm btn-outline-dark">Edit</a>
                                <form method="post" action="<?= e(base_url('admin/country_delete.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this country page?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-secondary">No country pages found.</td></tr>
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
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
