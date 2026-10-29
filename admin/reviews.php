<?php
declare(strict_types=1);

$adminTitle = 'Reviews';
$adminPage = 'reviews';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

ensure_reviews_schema();

$page = max(1, (int) get_input('page', 1));
$perPage = 15;
$offset = paginate_offset($page, $perPage);
$search = trim((string) get_input('q', ''));

$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE customer_name LIKE :search_customer OR country LIKE :search_country OR review_title LIKE :search_title OR review_text LIKE :search_text OR review_source LIKE :search_source";
    $params['search_customer'] = '%' . $search . '%';
    $params['search_country'] = '%' . $search . '%';
    $params['search_title'] = '%' . $search . '%';
    $params['search_text'] = '%' . $search . '%';
    $params['search_source'] = '%' . $search . '%';
}

$total = fetch_one("SELECT COUNT(*) AS total FROM reviews $where", $params)['total'] ?? 0;

$items = fetch_all("
    SELECT id, customer_name, country, country_code, rating, review_title, trip_type, tour_done_date, tour_done_month, review_source, is_featured, is_published, sort_order, updated_at
    FROM reviews
    $where
    ORDER BY COALESCE(tour_done_date, STR_TO_DATE(CONCAT(tour_done_month, '-01'), '%Y-%m-%d'), created_at) DESC, id DESC
    LIMIT $perPage OFFSET $offset
", $params);

$pages = max(1, (int) ceil(((int) $total) / $perPage));

require __DIR__ . '/_top.php';
?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <form class="d-flex gap-2" method="get">
                <input type="text" name="q" class="form-control" placeholder="Search reviews..." value="<?= e($search) ?>">
                <button class="btn btn-outline-dark">Search</button>
            </form>

            <a href="<?= e(base_url('admin/review_form.php')) ?>" class="btn btn-success">Add Review</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Customer</th>
                    <th>Title</th>
                    <th>Country</th>
                    <th>Rating</th>
                    <th>Trip Type</th>
                    <th>Tour Done</th>
                    <th>Source</th>
                    <th>Featured</th>
                    <th>Published</th>
                    <th>Sort</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($items): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['customer_name']) ?></td>
                            <td><?= e((string) ($item['review_title'] ?? '')) ?></td>
                            <td><?= e(trim(country_flag_emoji($item['country_code'] ?? '') . ' ' . (string) ($item['country'] ?? ''))) ?></td>
                            <td><?= (int) $item['rating'] ?></td>
                            <td><?= e((string) ($item['trip_type'] ?? '')) ?></td>
                            <td><?= e((string) ($item['tour_done_date'] ?: $item['tour_done_month'] ?: '')) ?></td>
                            <td><?= e((string) ($item['review_source'] ?? '')) ?></td>
                            <td><?= (int) $item['is_featured'] ? 'Yes' : 'No' ?></td>
                            <td><?= (int) $item['is_published'] ? 'Yes' : 'No' ?></td>
                            <td><?= (int) $item['sort_order'] ?></td>
                            <td class="text-end">
                                <a href="<?= e(base_url('admin/review_form.php?id=' . (int) $item['id'])) ?>" class="btn btn-sm btn-outline-dark">Edit</a>
                                <form method="post" action="<?= e(base_url('admin/review_delete.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this review?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="text-center text-secondary">No reviews found.</td></tr>
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
