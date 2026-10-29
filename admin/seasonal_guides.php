<?php
declare(strict_types=1);

$adminTitle = 'Seasonal Guides';
$adminPage = 'seasonal_guides';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
ensure_seasonal_guides_schema();

$guides = fetch_all_seasonal_guides(false);
$guideByMonth = [];
foreach ($guides as $g) {
    $k = strtolower(trim((string) ($g['month_key'] ?? '')));
    if ($k !== '') {
        $guideByMonth[$k] = $g;
    }
}

$months = seasonal_allowed_months();

require __DIR__ . '/_top.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="small text-secondary">
        Edit the on-page copy for <code>/sri-lanka-in-{month}</code> seasonal pages. Destination/itinerary month matching is controlled from their own admin forms.
    </div>
    <a href="<?= e(base_url('seasonal')) ?>" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener">View Seasonal Hub</a>
</div>

<div class="row g-3">
    <?php foreach ($months as $month): ?>
        <?php
        $record = $guideByMonth[$month] ?? null;
        $title = trim((string) ($record['title'] ?? 'Sri Lanka in ' . ucfirst($month)));
        $isPublished = (int) ($record['is_published'] ?? 1) === 1;
        $updatedAt = trim((string) ($record['updated_at'] ?? ''));
        ?>
        <div class="col-md-6 col-xl-4">
            <div class="card card-soft h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <div class="small text-secondary">Month</div>
                            <div class="h6 mb-0"><?= e(ucfirst($month)) ?></div>
                        </div>
                        <span class="admin-chip" style="<?= $isPublished ? '' : 'opacity:.75;' ?>">
                            <?= $isPublished ? 'Published' : 'Hidden' ?>
                        </span>
                    </div>

                    <div class="fw-semibold mb-2"><?= e($title) ?></div>
                    <div class="small text-secondary mb-3">
                        URL: <a href="<?= e(base_url('sri-lanka-in-' . $month)) ?>" target="_blank" rel="noopener"><?= e('/sri-lanka-in-' . $month) ?></a>
                        <?php if ($updatedAt !== ''): ?>
                            <div>Updated: <?= e($updatedAt) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-auto d-flex gap-2 flex-wrap">
                        <a href="<?= e(base_url('admin/seasonal_guide_form.php?month=' . rawurlencode($month))) ?>" class="btn btn-sm btn-success">Edit</a>
                        <a href="<?= e(base_url('sri-lanka-in-' . $month)) ?>" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener">Preview</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>

