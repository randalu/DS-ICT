<?php
declare(strict_types=1);

$adminTitle = 'Social Drafts';
$adminPage = 'social_posts';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

ensure_social_marketing_schema();

$page = max(1, (int) get_input('page', 1));
$perPage = 20;
$offset = paginate_offset($page, $perPage);
$statusFilter = trim((string) get_input('status', ''));
$search = trim((string) get_input('q', ''));

$statusOptions = ['draft', 'ready', 'scheduled', 'published', 'archived'];
$whereParts = [];
$params = [];

if ($statusFilter !== '' && in_array($statusFilter, $statusOptions, true)) {
    $whereParts[] = 'p.status = :status_filter';
    $params['status_filter'] = $statusFilter;
}

if ($search !== '') {
    $whereParts[] = '(p.title LIKE :search_title OR p.description LIKE :search_description OR p.hashtags LIKE :search_hashtags OR p.experience_name LIKE :search_experience)';
    $params['search_title'] = '%' . $search . '%';
    $params['search_description'] = '%' . $search . '%';
    $params['search_hashtags'] = '%' . $search . '%';
    $params['search_experience'] = '%' . $search . '%';
}

$where = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$hasDestinations = db_table_exists('destinations') && db_column_exists('destinations', 'id');
$hasItineraries = db_table_exists('itineraries') && db_column_exists('itineraries', 'id');
$hasTours = db_table_exists('tours') && db_column_exists('tours', 'id');

$destinationNameSelect = $hasDestinations && db_column_exists('destinations', 'name') ? 'd.name AS destination_name' : 'NULL AS destination_name';
$itineraryNameSelect = $hasItineraries && db_column_exists('itineraries', 'name') ? 'i.name AS itinerary_name' : 'NULL AS itinerary_name';
$tourNameSelect = $hasTours && db_column_exists('tours', 'name') ? 't.name AS tour_name' : 'NULL AS tour_name';

$destinationJoin = $hasDestinations ? 'LEFT JOIN destinations d ON d.id = p.reference_destination_id' : '';
$itineraryJoin = $hasItineraries ? 'LEFT JOIN itineraries i ON i.id = p.reference_itinerary_id' : '';
$tourJoin = $hasTours ? 'LEFT JOIN tours t ON t.id = p.reference_tour_id' : '';

$total = (int) (fetch_one("SELECT COUNT(*) AS total FROM social_media_posts p {$where}", $params)['total'] ?? 0);

$items = fetch_all(
    "SELECT
        p.id,
        p.title,
        p.description,
        p.experience_name,
        p.platform_targets_json,
        p.status,
        p.scheduled_at,
        p.published_at,
        p.blog_post_id,
        p.created_at,
        {$destinationNameSelect},
        {$itineraryNameSelect},
        {$tourNameSelect},
        (
            SELECT COUNT(*)
            FROM social_media_post_assets spa
            WHERE spa.post_id = p.id
        ) AS media_count,
        (
            SELECT attempted_at
            FROM social_publish_logs spl
            WHERE spl.post_id = p.id
            ORDER BY spl.id DESC
            LIMIT 1
        ) AS last_publish_attempt
     FROM social_media_posts p
     {$destinationJoin}
     {$itineraryJoin}
     {$tourJoin}
     {$where}
     ORDER BY p.id DESC
     LIMIT {$perPage} OFFSET {$offset}",
    $params
);

$pages = max(1, (int) ceil($total / max(1, $perPage)));

$platformOptions = social_media_platform_options();

require __DIR__ . '/_top.php';
?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <form class="row g-2 align-items-center" method="get">
                <div class="col-auto">
                    <input type="text" name="q" class="form-control" placeholder="Search drafts..." value="<?= e($search) ?>">
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        <?php foreach ($statusOptions as $status): ?>
                            <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-dark">Filter</button>
                </div>
            </form>
            <div class="d-flex gap-2">
                <a href="<?= e(base_url('admin/media_library.php')) ?>" class="btn btn-outline-dark">Media Library</a>
                <a href="<?= e(base_url('admin/social_post_form.php')) ?>" class="btn btn-success">New Draft</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-sm">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Reference</th>
                        <th>Targets</th>
                        <th>Status</th>
                        <th>Media</th>
                        <th>Schedule</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items): ?>
                        <?php foreach ($items as $item): ?>
                            <?php
                            $postId = (int) ($item['id'] ?? 0);
                            $platforms = decode_json_array((string) ($item['platform_targets_json'] ?? ''));
                            $targetLabels = [];
                            foreach ($platforms as $platformKey) {
                                $platformKey = strtolower(trim((string) $platformKey));
                                if (isset($platformOptions[$platformKey])) {
                                    $targetLabels[] = $platformOptions[$platformKey];
                                }
                            }

                            $referenceLabel = trim((string) ($item['destination_name'] ?? ''));
                            if ($referenceLabel === '') {
                                $referenceLabel = trim((string) ($item['itinerary_name'] ?? ''));
                            }
                            if ($referenceLabel === '') {
                                $referenceLabel = trim((string) ($item['tour_name'] ?? ''));
                            }
                            if ($referenceLabel === '') {
                                $referenceLabel = trim((string) ($item['experience_name'] ?? ''));
                            }
                            if ($referenceLabel === '') {
                                $referenceLabel = '-';
                            }
                            ?>
                            <tr>
                                <td style="min-width:220px;">
                                    <div class="fw-semibold"><?= e((string) ($item['title'] ?? '')) ?></div>
                                    <div class="small text-secondary"><?= e(excerpt_words((string) ($item['description'] ?? ''), 18)) ?></div>
                                </td>
                                <td><?= e($referenceLabel) ?></td>
                                <td><?= e($targetLabels ? implode(', ', $targetLabels) : 'None') ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= e(ucfirst((string) ($item['status'] ?? 'draft'))) ?></span>
                                    <?php if (!empty($item['blog_post_id'])): ?>
                                        <div class="small text-success">Blog linked #<?= (int) $item['blog_post_id'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format((int) ($item['media_count'] ?? 0)) ?></td>
                                <td>
                                    <div class="small"><?= e((string) ($item['scheduled_at'] ?? '-')) ?></div>
                                    <div class="small text-secondary">Last publish: <?= e((string) ($item['last_publish_attempt'] ?? '-')) ?></div>
                                </td>
                                <td class="text-end" style="min-width:260px;">
                                    <a href="<?= e(base_url('admin/social_post_form.php?id=' . $postId)) ?>" class="btn btn-sm btn-outline-dark">Edit</a>

                                    <form method="post" action="<?= e(base_url('admin/social_post_publish.php')) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $postId ?>">
                                        <select name="platform" class="form-select form-select-sm d-inline-block" style="width:auto;">
                                            <?php foreach ($platformOptions as $platformKey => $platformLabel): ?>
                                                <option value="<?= e($platformKey) ?>"><?= e($platformLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-success">Publish</button>
                                    </form>

                                    <form method="post" action="<?= e(base_url('admin/social_post_delete.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this social draft?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $postId ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-secondary">No social drafts found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination mb-0">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item<?= $i === $page ? ' active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($statusFilter) ?>&q=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
