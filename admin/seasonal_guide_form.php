<?php
declare(strict_types=1);

$adminPage = 'seasonal_guides';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
ensure_seasonal_guides_schema();

$month = strtolower(trim((string) get_input('month', '')));
if ($month === '' || !in_array($month, seasonal_allowed_months(), true)) {
    flash_set('error', 'Invalid month.');
    redirect('admin/seasonal_guides.php');
}

$record = fetch_seasonal_guide($month, false) ?? [];

$adminTitle = 'Edit Seasonal Guide - ' . ucfirst($month);

$title = trim((string) ($record['title'] ?? ''));
$intro = trim((string) ($record['intro'] ?? ''));
$highlights = decode_string_list_json($record['highlights_json'] ?? null);
$bestRegions = decode_string_list_json($record['best_regions_json'] ?? null);
$avoidRegions = decode_string_list_json($record['avoid_regions_json'] ?? null);
$metaTitle = trim((string) ($record['meta_title'] ?? ''));
$metaDescription = trim((string) ($record['meta_description'] ?? ''));
$heroImage = trim((string) ($record['hero_image'] ?? ''));
$isPublished = (int) ($record['is_published'] ?? 1) === 1;

// Old input override
$oldTitle = old_input('title', '__ICT_NO_OLD__');
if ($oldTitle !== '__ICT_NO_OLD__') {
    $title = trim((string) $oldTitle);
    $intro = trim((string) old_input('intro', ''));
    $metaTitle = trim((string) old_input('meta_title', ''));
    $metaDescription = trim((string) old_input('meta_description', ''));
    $highlights = textarea_lines_to_list((string) old_input('highlights', ''), 30);
    $bestRegions = textarea_lines_to_list((string) old_input('best_regions', ''), 30);
    $avoidRegions = textarea_lines_to_list((string) old_input('avoid_regions', ''), 30);
    $isPublished = (bool) old_input('is_published', $isPublished ? '1' : '');
}

require __DIR__ . '/_top.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="small text-secondary">
        Public URL: <a href="<?= e(base_url('sri-lanka-in-' . $month)) ?>" target="_blank" rel="noopener"><?= e('/sri-lanka-in-' . $month) ?></a>
    </div>
    <a href="<?= e(base_url('admin/seasonal_guides.php')) ?>" class="btn btn-sm btn-outline-dark">Back</a>
</div>

<form method="post" action="<?= e(base_url('admin/seasonal_guide_save.php')) ?>" enctype="multipart/form-data" data-ai-context="seasonal_guide" data-ai-title="<?= e($adminTitle) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="month" value="<?= e($month) ?>">

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card card-soft mb-3">
                <div class="card-body">
                    <h2 class="h5 mb-2">Page Content</h2>
                    <p class="small text-secondary mb-3">This edits the text shown on the monthly guide. The destination/itinerary lists are driven by the month selections on those records.</p>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="<?= e($title) ?>" placeholder="Sri Lanka in April" data-ai-field="title" data-ai-label="Guide Title">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Intro</label>
                        <textarea name="intro" rows="3" class="form-control" data-ai-field="intro"><?= e($intro) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Highlights (one per line)</label>
                        <textarea name="highlights" rows="6" class="form-control" data-ai-field="highlights"><?= e(implode("\n", $highlights)) ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Best Regions (one per line)</label>
                            <textarea name="best_regions" rows="5" class="form-control" placeholder="south-coast&#10;cultural-triangle" data-ai-field="best_regions"><?= e(implode("\n", $bestRegions)) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Regions to Avoid (one per line)</label>
                            <textarea name="avoid_regions" rows="5" class="form-control" placeholder="east-coast" data-ai-field="avoid_regions"><?= e(implode("\n", $avoidRegions)) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-2">SEO</h2>
                    <div class="mb-3">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" value="<?= e($metaTitle) ?>" data-ai-field="meta_title">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" rows="3" class="form-control" data-ai-field="meta_description"><?= e($metaDescription) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-soft mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-2">Publish</h2>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" <?= $isPublished ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_published">Published</label>
                    </div>

                    <button type="submit" class="btn btn-success w-100">Save Seasonal Guide</button>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h6 mb-2">Hero Image (optional)</h2>
                    <input type="file" name="hero_image" class="form-control" accept="image/*">
                    <?php if ($heroImage !== ''): ?>
                        <div class="small mt-2 text-secondary"><?= e($heroImage) ?></div>
                        <img src="<?= e(stored_media_url($heroImage)) ?>" alt="Hero image" class="img-fluid rounded-3 mt-2 border">
                    <?php else: ?>
                        <div class="small mt-2 text-secondary">If empty, the page will auto-pick a hero from matching destinations/itineraries.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/_bottom.php'; ?>

