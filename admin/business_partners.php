<?php
declare(strict_types=1);

$adminTitle = 'Business Partners';
$adminPage = 'business_partners';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

ensure_business_partners_schema();

$publicPartnersEnabled = business_partners_public_enabled();
$page = max(1, (int) get_input('page', 1));
$perPage = 20;
$offset = paginate_offset($page, $perPage);
$search = trim((string) get_input('q', ''));
$status = trim((string) get_input('status', ''));
$statuses = business_partner_statuses();
$statusHelp = [
    'applied' => 'New submission. Not contacted yet.',
    'contacted' => 'You have reached out and are waiting for details or reply.',
    'negotiating' => 'Terms, quality checks, or mutual understanding are being discussed.',
    'agreed' => 'Partnership accepted internally, but not visible publicly yet.',
    'published' => 'Visible on public partner pages and related sections.',
    'rejected' => 'Not moving forward. Hidden publicly.',
];
$statusBadgeClasses = [
    'applied' => 'secondary',
    'contacted' => 'info',
    'negotiating' => 'warning',
    'agreed' => 'primary',
    'published' => 'success',
    'rejected' => 'danger',
];

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(bp.business_name LIKE :search_business OR bp.location LIKE :search_location OR bp.email LIKE :search_email)";
    $params['search_business'] = '%' . $search . '%';
    $params['search_location'] = '%' . $search . '%';
    $params['search_email'] = '%' . $search . '%';
}
if ($status !== '' && isset($statuses[$status])) {
    $where[] = "bp.status = :status";
    $params['status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$statusCountsRaw = fetch_all("
    SELECT status, COUNT(*) AS total
    FROM business_partners
    GROUP BY status
");
$statusCounts = array_fill_keys(array_keys($statuses), 0);
foreach ($statusCountsRaw as $row) {
    $key = (string) ($row['status'] ?? '');
    if (isset($statusCounts[$key])) {
        $statusCounts[$key] = (int) ($row['total'] ?? 0);
    }
}
$allPartnerCount = array_sum($statusCounts);
$pipelineCount = $statusCounts['applied'] + $statusCounts['contacted'] + $statusCounts['negotiating'];

$total = fetch_one("
    SELECT COUNT(*) AS total
    FROM business_partners bp
    {$whereSql}
", $params)['total'] ?? 0;

$items = fetch_all("
    SELECT bp.id, bp.business_name, bp.slug, bp.business_type, bp.location, bp.email, bp.status,
           bp.is_featured, bp.sort_order, bp.updated_at, c.name AS category_name
    FROM business_partners bp
    LEFT JOIN business_partner_categories c ON c.id = bp.category_id
    {$whereSql}
    ORDER BY FIELD(bp.status, 'applied','contacted','negotiating','agreed','published','rejected'),
             bp.updated_at DESC,
             bp.business_name ASC
    LIMIT {$perPage} OFFSET {$offset}
", $params);

$pages = max(1, (int) ceil(((int) $total) / $perPage));

require __DIR__ . '/_top.php';
?>

<div class="card card-soft mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="h5 mb-1">Public Partners Area</h2>
                <p class="text-secondary small mb-0">
                    Disable this to hide public partner pages, partner application form, nav/footer links, related partner cards, and sitemap partner URLs.
                    Existing applications and partner records remain unchanged.
                </p>
            </div>
            <form method="post" action="<?= e(base_url('admin/business_partner_settings_save.php')) ?>" class="d-flex align-items-center gap-3">
                <?= csrf_field() ?>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="business_partners_public_enabled" id="business_partners_public_enabled" <?= $publicPartnersEnabled ? 'checked' : '' ?>>
                    <label class="form-check-label" for="business_partners_public_enabled"><?= $publicPartnersEnabled ? 'Enabled' : 'Disabled' ?></label>
                </div>
                <button type="submit" class="btn btn-outline-dark btn-sm">Save</button>
            </form>
        </div>
        <?php if (!$publicPartnersEnabled): ?>
            <div class="alert alert-warning small mb-0 mt-3">
                Public partner pages are currently hidden. Applications, uploaded photos, admin notes, and partner statuses are still saved and editable here.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4 col-xl-2">
        <a href="<?= e(base_url('admin/business_partners.php')) ?>" class="card card-soft text-decoration-none h-100">
            <div class="card-body py-3">
                <div class="small text-secondary">All Partners</div>
                <div class="h4 mb-0 text-dark"><?= (int) $allPartnerCount ?></div>
            </div>
        </a>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card card-soft h-100">
            <div class="card-body py-3">
                <div class="small text-secondary">Needs Attention</div>
                <div class="h4 mb-0"><?= (int) $pipelineCount ?></div>
            </div>
        </div>
    </div>
    <?php foreach ($statuses as $value => $label): ?>
        <div class="col-md-4 col-xl-2">
            <a href="<?= e(base_url('admin/business_partners.php?status=' . urlencode($value))) ?>" class="card card-soft text-decoration-none h-100 <?= $status === $value ? 'border-primary' : '' ?>">
                <div class="card-body py-3">
                    <div class="small text-secondary"><?= e($label) ?></div>
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="h4 mb-0 text-dark"><?= (int) ($statusCounts[$value] ?? 0) ?></div>
                        <span class="badge text-bg-<?= e($statusBadgeClasses[$value] ?? 'secondary') ?>">&nbsp;</span>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <form class="d-flex flex-wrap gap-2" method="get">
                <input type="text" name="q" class="form-control" placeholder="Search partners..." value="<?= e($search) ?>" style="max-width:260px;">
                <select name="status" class="form-select" style="max-width:180px;">
                    <option value="">All statuses</option>
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-dark">Filter</button>
                <?php if ($search !== '' || $status !== ''): ?>
                    <a href="<?= e(base_url('admin/business_partners.php')) ?>" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </form>

            <a href="<?= e(base_url('admin/business_partner_form.php')) ?>" class="btn btn-success">Add Partner</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Business</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Featured</th>
                    <th>Updated</th>
                    <th>Quick Stage</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($items): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?= e($item['business_name']) ?></strong>
                                <div class="small text-secondary"><?= e(business_partner_types()[(string) $item['business_type']] ?? (string) $item['business_type']) ?> · <?= e($item['email']) ?></div>
                            </td>
                            <td><?= e((string) ($item['category_name'] ?? '')) ?></td>
                            <td><?= e((string) ($item['location'] ?? '')) ?></td>
                            <td>
                                <span class="badge text-bg-<?= e($statusBadgeClasses[(string) $item['status']] ?? 'secondary') ?>"><?= e($statuses[(string) $item['status']] ?? (string) $item['status']) ?></span>
                                <div class="small text-secondary mt-1"><?= e($statusHelp[(string) $item['status']] ?? '') ?></div>
                            </td>
                            <td><?= (int) $item['is_featured'] ? 'Yes' : 'No' ?></td>
                            <td><?= e((string) $item['updated_at']) ?></td>
                            <td>
                                <form method="post" action="<?= e(base_url('admin/business_partner_status_save.php')) ?>" class="d-flex flex-wrap gap-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="return_to" value="<?= e((string) ($_SERVER['REQUEST_URI'] ?? 'admin/business_partners.php')) ?>">
                                    <?php foreach (['contacted', 'negotiating', 'agreed', 'published', 'rejected'] as $quickStatus): ?>
                                        <?php if ((string) $item['status'] === $quickStatus) continue; ?>
                                        <?php
                                        $confirm = '';
                                        if ($quickStatus === 'published') {
                                            $confirm = "return confirm('Publish this partner publicly? It will become visible on the website.');";
                                        } elseif ($quickStatus === 'rejected') {
                                            $confirm = "return confirm('Mark this partner as rejected? It will remain hidden publicly.');";
                                        }
                                        ?>
                                        <button type="submit" name="status" value="<?= e($quickStatus) ?>" class="btn btn-sm btn-outline-<?= e($statusBadgeClasses[$quickStatus] ?? 'secondary') ?>" <?= $confirm !== '' ? 'onclick="' . e($confirm) . '"' : '' ?>>
                                            <?= e($statuses[$quickStatus]) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </form>
                            </td>
                            <td class="text-end">
                                <?php if ($item['status'] === 'published'): ?>
                                    <a href="<?= e(base_url('business-partners/partner/' . $item['slug'])) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">View</a>
                                <?php endif; ?>
                                <a href="<?= e(base_url('admin/business_partner_form.php?id=' . (int) $item['id'])) ?>" class="btn btn-sm btn-outline-dark">Edit</a>
                                <form method="post" action="<?= e(base_url('admin/business_partner_delete.php')) ?>" class="d-inline" onsubmit="return confirm('Delete this partner application?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center text-secondary">No partner applications found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination mb-0">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item<?= $i === $page ? ' active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
