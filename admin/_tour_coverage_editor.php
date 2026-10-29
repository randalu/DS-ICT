<?php
declare(strict_types=1);

$coverageAvailable = db_column_exists('tours', 'coverage_json');
$coverageDestinations = db_table_exists('destinations') ? fetch_all('SELECT id, name FROM destinations ORDER BY name') : [];
$coverageExperiences = db_table_exists('experiences') ? fetch_all('SELECT id, title AS name FROM experiences ORDER BY title') : [];
$coverageHasOld = old_input('coverage_present', null) !== null && (int) old_input('id', 0) === $id;
$coverageSavedRows = tour_coverage_decode($record['coverage_json'] ?? null);
$coverageRows = $coverageHasOld ? old_input('coverage_items', []) : $coverageSavedRows;
$coverageRows = is_array($coverageRows) ? $coverageRows : [];
$coverageCurrentRevision = tour_coverage_revision($record['coverage_json'] ?? null);
$coverageOldRevision = old_input('coverage_revision', '');
$coverageRevision = $coverageHasOld ? (is_string($coverageOldRevision) ? $coverageOldRevision : '') : $coverageCurrentRevision;
?>
<section class="card card-soft mb-4" id="tour-coverage-editor" data-current-revision="<?= e($coverageCurrentRevision) ?>" data-library-url="<?= e(base_url('admin/image_manager_api.php?action=library')) ?>">
    <div class="card-body">
        <h2 class="h5">Places &amp; Experiences Covered</h2>
        <p class="text-secondary">Describe the exact places and experiences in this tour. These cards explain the proposed plan; use Includes and Excludes to explain what the price covers.</p>
        <?php if (!$coverageAvailable): ?>
            <p class="alert alert-info mb-0">Coverage editing becomes available after the tour coverage migration is applied. Existing tour content can still be edited.</p>
        <?php else: ?>
            <input type="hidden" name="coverage_present" value="1">
            <input type="hidden" name="coverage_revision" value="<?= e($coverageRevision) ?>">
            <?php if ($coverageRevision !== $coverageCurrentRevision): ?>
                <div class="alert alert-warning" data-coverage-stale-warning>Your entered cards are retained below, but saved coverage has changed. Copy any notes you need, then load the saved coverage and review it before saving.
                    <button type="button" class="btn btn-outline-dark mt-2" data-coverage-reload>Load saved coverage</button>
                </div>
            <?php endif; ?>
            <div data-coverage-items></div>
            <button type="button" class="btn btn-outline-dark mt-3" data-coverage-add hidden>Add place or experience</button>
            <p class="small text-secondary mt-2 mb-0" data-coverage-status role="status" aria-live="polite"></p>
            <script type="application/json" data-coverage-initial><?= json_encode(array_values($coverageRows), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
            <script type="application/json" data-coverage-saved><?= json_encode($coverageSavedRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
            <noscript><p class="alert alert-warning">Enable JavaScript to edit coverage cards. Existing coverage is preserved when you save other fields.</p></noscript>
            <template data-coverage-template>
                <fieldset class="coverage-edit-card">
                    <legend>Coverage card <span data-coverage-number></span></legend>
                    <div class="coverage-edit-actions">
                        <button type="button" class="btn btn-outline-dark btn-sm" data-coverage-up>Move up</button>
                        <button type="button" class="btn btn-outline-dark btn-sm" data-coverage-down>Move down</button>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-coverage-remove>Remove card</button>
                    </div>
                    <label class="form-label">Experience title<input class="form-control" data-field="title" maxlength="180" required></label>
                    <label class="form-label">Exact location<input class="form-control" data-field="location" maxlength="255" required placeholder="Park, landmark, village or venue"></label>
                    <label class="form-label">What travellers experience<textarea class="form-control" data-field="description" rows="4" maxlength="6000" required></textarea></label>
                    <label class="form-label">Plan status<select class="form-select" data-field="status"><option value="proposed">Part of proposed tour</option><option value="optional">Optional addition</option></select></label>
                    <label class="form-label">Link destination (optional)<select class="form-select" data-field="destination_id"><option value="">No destination link</option><?php foreach ($coverageDestinations as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e($item['name']) ?></option><?php endforeach; ?></select></label>
                    <label class="form-label">Link experience (optional)<select class="form-select" data-field="experience_id"><option value="">No experience link</option><?php foreach ($coverageExperiences as $item): ?><option value="<?= (int) $item['id'] ?>"><?= e($item['name']) ?></option><?php endforeach; ?></select></label>
                    <input type="hidden" data-field="image_asset_id">
                    <div class="coverage-image-controls"><button type="button" class="btn btn-outline-dark" data-coverage-image>Choose library image</button><button type="button" class="btn btn-outline-secondary" data-coverage-clear>Clear image</button><span data-coverage-image-label class="small"></span></div>
                    <label class="form-label">Image alt text<input class="form-control" data-field="image_alt" maxlength="255"></label>
                </fieldset>
            </template>
        <?php endif; ?>
    </div>
</section>
<?php if ($coverageAvailable): ?>
<dialog class="coverage-library" data-coverage-library aria-labelledby="coverage-library-title">
    <div class="coverage-library-head"><h2 id="coverage-library-title" class="h5">Choose a coverage image</h2><button type="button" class="btn btn-outline-dark" data-library-close>Close</button></div>
    <label class="form-label w-100">Search Media Library<input type="search" class="form-control" data-library-search></label>
    <p class="small" data-library-status role="status" aria-live="polite"></p>
    <div class="coverage-library-grid" data-library-grid></div>
    <div class="coverage-library-pages"><button type="button" class="btn btn-outline-dark" data-library-prev>Previous</button><button type="button" class="btn btn-outline-dark" data-library-next>Next</button></div>
</dialog>
<link rel="stylesheet" href="<?= e(base_url('assets/css/tour-coverage-editor.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/tour-coverage-editor.css')) ?>">
<script src="<?= e(base_url('assets/js/tour-coverage-editor.js') . '?v=' . filemtime(__DIR__ . '/../assets/js/tour-coverage-editor.js')) ?>" defer></script>
<?php endif; ?>
