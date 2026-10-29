<?php
declare(strict_types=1);

$adminPage = 'tours';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/tour_coverage.php';

admin_require_auth();

$id = (int) get_input('id', 0);
$record = [];

if ($id > 0) {
    $record = fetch_one("SELECT * FROM tours WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Tour not found.');
        redirect('admin/tours.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Tour Package' : 'Add Tour Package';

$hasDestinations = db_table_exists('destinations');
$hasDestinationTour = db_table_exists('destination_tour') && db_column_exists('destination_tour','destination_id') && db_column_exists('destination_tour','tour_id');
$allDestinations = $hasDestinations ? fetch_all('SELECT id,name FROM destinations ORDER BY name') : [];
$selectedDestinations = [];

if ($id > 0 && $hasDestinationTour) {
    $selectedDestinations = array_map('intval', array_column(fetch_all(
        "SELECT destination_id FROM destination_tour WHERE tour_id = :id",
        ['id' => $id]
    ), 'destination_id'));
}


$oldDestinations = old_input('destinations', null);
if (is_array($oldDestinations)) {
    $selectedDestinations = array_map('intval', $oldDestinations);
}


require __DIR__ . '/_top.php';
?>

<?php if ($id > 0): ?><div class="mb-3"><a class="btn btn-primary me-2" href="<?= e(base_url('admin/tour_program.php?id=' . $id)) ?>">Edit route, days and hotels</a><a class="btn btn-outline-dark" href="<?= e(base_url('admin/image_manager.php?type=tour&id=' . $id)) ?>">Edit images in Image Manager</a></div><?php endif; ?>
<form method="post" action="<?= e(base_url('admin/tour_save.php')) ?>" enctype="multipart/form-data" data-ai-context="tour" data-ai-title="<?= e($adminTitle) ?>">
    <?= csrf_field() ?><input type="hidden" name="destinations_present" value="1"><input type="hidden" name="package_revision" value="<?= e(package_edit_revision($record)) ?>">
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Content</h2>

                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= e((string) admin_form_value($record, 'name')) ?>" required data-ai-field="name" data-ai-label="Tour Name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input
                            type="text"
                            name="slug"
                            class="form-control"
                            value="<?= e((string) admin_form_value($record, 'slug')) ?>"
                            data-live-slug="1"
                            data-slug-source="name"
                            data-canonical-input="canonical_url"
                            data-canonical-prefix="tours"
                        >
                        <div class="form-text">Auto-generates live from name. Edit manually to override.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Excerpt</label>
                        <textarea name="excerpt" rows="3" class="form-control" data-ai-field="excerpt"><?= e((string) admin_form_value($record, 'excerpt')) ?></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Price From</label>
                            <input type="text" name="price_from" class="form-control" placeholder="450" value="<?= e((string) admin_form_value($record, 'price_from')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Price To</label>
                            <input type="text" name="price_to" class="form-control" placeholder="900" value="<?= e((string) admin_form_value($record, 'price_to')) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Currency</label>
                            <?php $tourPriceCurrency = strtoupper((string) admin_form_value($record, 'price_currency', 'USD')); ?>
                            <select name="price_currency" class="form-select">
                                <?php foreach (['USD', 'LKR', 'EUR', 'GBP'] as $currency): ?>
                                    <option value="<?= e($currency) ?>" <?= $tourPriceCurrency === $currency ? 'selected' : '' ?>><?= e($currency) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price Unit</label>
                            <input type="text" name="price_unit" class="form-control" placeholder="per couple" value="<?= e((string) admin_form_value($record, 'price_unit', 'per couple')) ?>">
                        </div>
                    </div>

                    <?php require __DIR__ . '/_tour_coverage_editor.php'; ?>
                    <?php
                    $textareas = [
                        'intro' => 'Introduction',
                        'highlights' => 'Highlights',
                        'includes' => 'Includes',
                        'excludes' => 'Excludes',
                        'pricing_notes' => 'Pricing Notes',
                        'vehicle_options' => 'Vehicle Options',
                        'cta_text' => 'CTA Text',
                    ];
                    foreach ($textareas as $field => $label):
                    ?>
                        <div class="mb-3">
                            <label class="form-label"><?= e($label) ?></label>
                            <textarea name="<?= e($field) ?>" rows="5" class="form-control quill-editor"><?= e((string) admin_form_value($record, $field)) ?></textarea>
                        </div>
                    <?php endforeach; ?>

                    <div class="mb-3">
                        <label class="form-label">CTA Title</label>
                        <input type="text" name="cta_title" class="form-control" value="<?= e((string) admin_form_value($record, 'cta_title')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meeting Point</label>
                        <input type="text" name="meeting_point" class="form-control" placeholder="Hotel lobby, airport arrival hall, etc." value="<?= e((string) admin_form_value($record, 'meeting_point')) ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Duration (hours)</label>
                            <input type="number" step="0.5" min="1" name="duration_hours" class="form-control" value="<?= e((string) admin_form_value($record, 'duration_hours', 8)) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control" value="<?= e((string) admin_form_value($record, 'start_time', '08:00')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Group Size</label>
                            <input type="number" min="1" name="max_group_size" class="form-control" value="<?= e((string) admin_form_value($record, 'max_group_size', 6)) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Difficulty</label>
                            <?php $tourDifficulty = (string) admin_form_value($record, 'difficulty', 'easy'); ?>
                            <select name="difficulty" class="form-select">
                                <?php foreach (['easy' => 'Easy', 'moderate' => 'Moderate', 'active' => 'Active'] as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= $tourDifficulty === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Vehicle Type</label>
                        <input type="text" name="vehicle_type" class="form-control" placeholder="Air-conditioned car/van" value="<?= e((string) admin_form_value($record, 'vehicle_type', 'Air-conditioned car/van')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Best Time</label>
                        <input type="text" name="best_time" class="form-control" placeholder="e.g. December to April" value="<?= e((string) admin_form_value($record, 'best_time')) ?>">
                    </div>

                    <div class="card card-soft mb-3">
                        <div class="card-body">
                            <h2 class="h5 mb-1">Best Months at a Glance</h2>
                            <p class="text-secondary small">Tick the best months for this tour. Surfaced as a 12-month colour strip on the tour page.</p>
                            <?php
                            $bestTimeRaw = (string) admin_form_value($record, 'best_time_json');
                            $bestTimeDecoded = $bestTimeRaw !== '' ? (json_decode($bestTimeRaw, true) ?: []) : [];
                            $bestTimeMonths = isset($bestTimeDecoded['months']) && is_array($bestTimeDecoded['months'])
                                ? array_map('strtolower', array_filter($bestTimeDecoded['months'], 'is_string'))
                                : [];
                            $bestTimeNote = (string) ($bestTimeDecoded['note'] ?? '');
                            $monthLabels = [
                                'january'=>'Jan','february'=>'Feb','march'=>'Mar','april'=>'Apr',
                                'may'=>'May','june'=>'Jun','july'=>'Jul','august'=>'Aug',
                                'september'=>'Sep','october'=>'Oct','november'=>'Nov','december'=>'Dec',
                            ];
                            ?>
                            <div class="d-flex flex-wrap gap-2 mb-3" id="bestMonthsGrid">
                                <?php foreach ($monthLabels as $key => $label): ?>
                                    <label class="btn btn-sm <?= in_array($key, $bestTimeMonths, true) ? 'btn-success' : 'btn-outline-secondary' ?>" data-month="<?= e($key) ?>">
                                        <input type="checkbox" name="best_time_months[]" value="<?= e($key) ?>" class="d-none" <?= in_array($key, $bestTimeMonths, true) ? 'checked' : '' ?>>
                                        <?= e($label) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Best Time Note (optional)</label>
                                <textarea name="best_time_note" rows="2" class="form-control" placeholder="e.g. Avoid Apr–Jun (south-west monsoon brings heavy rain)." data-ai-field="best_time_note"><?= e($bestTimeNote) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <?php $packageType='tour'; require __DIR__ . '/_package_season_editor.php'; require __DIR__ . '/_faq_editor.php'; ?>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">SEO</h2>

                    <div class="mb-3">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" value="<?= e((string) admin_form_value($record, 'meta_title')) ?>" data-ai-field="meta_title">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" rows="3" class="form-control" data-ai-field="meta_description"><?= e((string) admin_form_value($record, 'meta_description')) ?></textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Canonical URL</label>
                        <input type="text" name="canonical_url" class="form-control" value="<?= e((string) admin_form_value($record, 'canonical_url')) ?>">
                        <div class="form-text">Auto-builds from slug. Clear this field to re-enable auto mode.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Publish</h2>

                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?= e((string) admin_form_value($record, 'sort_order', 0)) ?>">
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured" <?= admin_checkbox_value($record, 'is_featured') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured">Featured</label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" <?= admin_checkbox_value($record, 'is_published') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_published">Published</label>
                    </div>

                    <button type="submit" class="btn btn-success w-100"><?= $id > 0 ? 'Update Tour' : 'Create Tour' ?></button>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <?php $packageType = 'tour'; require __DIR__ . '/_package_cover_editor.php'; ?>
                    <h2 class="h5 mb-3">Images</h2>

                    <div class="mb-3">
                        <label class="form-label">Hero Image</label>
                        <input type="file" name="hero_image" class="form-control" accept="image/*">
                        <?php if (!empty($record['hero_image'])): ?>
                            <div class="small mt-2 text-secondary"><?= e((string) $record['hero_image']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">OG Image</label>
                        <input type="file" name="og_image" class="form-control" accept="image/*">
                        <?php if (!empty($record['og_image'])): ?>
                            <div class="small mt-2 text-secondary"><?= e((string) $record['og_image']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Related Destinations</h2>
                    <div style="max-height: 320px; overflow:auto;">
                        <?php if (!$hasDestinationTour || !$hasDestinations): ?>
                            <p class="text-secondary small mb-0">Destination relationship options are not available in this database.</p>
                        <?php elseif (!$allDestinations): ?>
                            <p class="text-secondary small mb-0">No destinations are available yet.</p>
                        <?php else: ?>
                            <?php foreach ($allDestinations as $item): ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="destinations[]" value="<?= (int) $item['id'] ?>" id="destination_<?= (int) $item['id'] ?>" <?= in_array((int) $item['id'], $selectedDestinations, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="destination_<?= (int) $item['id'] ?>"><?= e($item['name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


        </div>
    </div>
</form>

<script>
document.querySelectorAll('#bestMonthsGrid label').forEach(label => {
    label.addEventListener('click', (ev) => {
        ev.preventDefault();
        const cb = label.querySelector('input[type=checkbox]');
        cb.checked = !cb.checked;
        label.classList.toggle('btn-success', cb.checked);
        label.classList.toggle('btn-outline-secondary', !cb.checked);
    });
});
</script>

<?php if ($id > 0) { ?><p><a href="<?= e(base_url('admin/package_fields.php?type=' . $packageType . '&id=' . $id)) ?>">Review all package fields and editors</a> · <a href="<?= e(base_url('admin/tour_program.php?id=' . $id)) ?>">Edit route, daily plan and stays</a> · <a href="<?= e(base_url('admin/map_manager.php?type=tour&id=' . $id)) ?>">Manage journey map</a></p><?php }
require_once __DIR__.'/../includes/page_media_admin.php';page_media_admin_assets();page_media_manager('tour',(int)$id);
require __DIR__ . '/_bottom.php'; ?>
