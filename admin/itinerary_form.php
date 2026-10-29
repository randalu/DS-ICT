<?php
declare(strict_types=1);

$adminPage = 'itineraries';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$id = (int) get_input('id', 0);
$record = [];

if ($id > 0) {
    $record = fetch_one("SELECT * FROM itineraries WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Itinerary not found.');
        redirect('admin/itineraries.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Itinerary' : 'Add Itinerary';

$hasDestinations = db_table_exists('destinations');
$hasItineraryDestination = db_table_exists('itinerary_destination')
    && db_column_exists('itinerary_destination', 'itinerary_id')
    && db_column_exists('itinerary_destination', 'destination_id');

$allDestinations = $hasDestinations ? fetch_all("SELECT id, name FROM destinations ORDER BY name ASC") : [];
$allCategories = fetch_itinerary_categories(false);
$selectedDestinations = [];
$selectedCategoryId = (int) ($record['category_id'] ?? 0);

if ($id > 0 && $hasItineraryDestination) {
    $selectedDestinations = array_map('intval', array_column(fetch_all(
        "SELECT destination_id FROM itinerary_destination WHERE itinerary_id = :id",
        ['id' => $id]
    ), 'destination_id'));
}

$oldDestinations = old_input('destinations', null);
if (is_array($oldDestinations)) {
    $selectedDestinations = array_map('intval', $oldDestinations);
}

$oldCategoryId = (int) old_input('category_id', 0);
if ($oldCategoryId > 0) {
    $selectedCategoryId = $oldCategoryId;
}

require __DIR__ . '/_top.php';
require_once __DIR__ . '/_media_picker.php';
?>

<?php if (!$allCategories): ?>
    <div class="alert alert-warning">
        No itinerary categories found. Create at least one category in
        <a href="<?= e(base_url('admin/itinerary_categories.php')) ?>">Itinerary Categories</a>
        before publishing itineraries.
    </div>
<?php endif; ?>

<form method="post" action="<?= e(base_url('admin/itinerary_save.php')) ?>" enctype="multipart/form-data" data-ai-context="itinerary" data-ai-title="<?= e($adminTitle) ?>">
    <?= csrf_field() ?><input type="hidden" name="destinations_present" value="1"><input type="hidden" name="package_revision" value="<?= e(package_edit_revision($record)) ?>">
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <?php if (db_column_exists('itineraries','traveller_types') && db_column_exists('itineraries','start_point') && db_column_exists('itineraries','end_point')) { require __DIR__ . '/_gem_itinerary_fields.php'; } ?>
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Content</h2>

                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= e((string) admin_form_value($record, 'name')) ?>" required data-ai-field="name" data-ai-label="Itinerary Name">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label">Slug</label>
                            <input
                                type="text"
                                name="slug"
                                class="form-control"
                                value="<?= e((string) admin_form_value($record, 'slug')) ?>"
                                data-live-slug="1"
                                data-slug-source="name"
                                data-canonical-input="canonical_url"
                                data-canonical-prefix="itineraries"
                            >
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Days *</label>
                            <input type="number" name="days" min="1" class="form-control" value="<?= e((string) admin_form_value($record, 'days', 1)) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" <?= $allCategories ? 'required' : '' ?>>
                                <option value="">Select category</option>
                                <?php foreach ($allCategories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>" <?= $selectedCategoryId === (int) $category['id'] ? 'selected' : '' ?>>
                                        <?= e($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Excerpt</label>
                        <textarea name="excerpt" rows="3" class="form-control" data-ai-field="excerpt"><?= e((string) admin_form_value($record, 'excerpt')) ?></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Price From</label>
                            <input type="text" name="price_from" class="form-control" placeholder="650" value="<?= e((string) admin_form_value($record, 'price_from')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Price To</label>
                            <input type="text" name="price_to" class="form-control" placeholder="1200" value="<?= e((string) admin_form_value($record, 'price_to')) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Currency</label>
                            <?php $itineraryPriceCurrency = strtoupper((string) admin_form_value($record, 'price_currency', 'USD')); ?>
                            <select name="price_currency" class="form-select">
                                <?php foreach (['USD', 'LKR', 'EUR', 'GBP'] as $currency): ?>
                                    <option value="<?= e($currency) ?>" <?= $itineraryPriceCurrency === $currency ? 'selected' : '' ?>><?= e($currency) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price Unit</label>
                            <input type="text" name="price_unit" class="form-control" placeholder="per couple" value="<?= e((string) admin_form_value($record, 'price_unit', 'per couple')) ?>">
                        </div>
                    </div>

                    <?php
                    $textareas = [
                        'intro' => 'Introduction',
                        'highlights' => 'Tour Highlights',
                        'route_summary' => 'Route Summary',
                        'best_for' => 'Best For',
                        'travel_notes' => 'Travel Notes',
                        'nearby_travel_times' => 'Nearby Travel Times',
                        'works_well_with' => 'Works Well With',
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

                    <?php $packageType='itinerary'; require __DIR__ . '/_package_season_editor.php'; require __DIR__ . '/_faq_editor.php'; ?>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-1">Best Months for This Route</h2>
                    <p class="text-secondary small">Tick the months when this itinerary travels best. Surfaced as a 12-month grid above the day plan.</p>
                    <?php
                    $bestMonthsRaw = (string) admin_form_value($record, 'best_months_json');
                    $bestMonthsDecoded = $bestMonthsRaw !== '' ? (json_decode($bestMonthsRaw, true) ?: []) : [];
                    $bestMonthsActive = isset($bestMonthsDecoded['months']) && is_array($bestMonthsDecoded['months'])
                        ? array_map('strtolower', array_filter($bestMonthsDecoded['months'], 'is_string'))
                        : [];
                    $bestMonthsNote = (string) ($bestMonthsDecoded['note'] ?? '');
                    $monthLabels = [
                        'january'=>'Jan','february'=>'Feb','march'=>'Mar','april'=>'Apr',
                        'may'=>'May','june'=>'Jun','july'=>'Jul','august'=>'Aug',
                        'september'=>'Sep','october'=>'Oct','november'=>'Nov','december'=>'Dec',
                    ];
                    ?>
                    <div class="d-flex flex-wrap gap-2 mb-3" id="bestMonthsGrid">
                        <?php foreach ($monthLabels as $key => $label): ?>
                            <label class="btn btn-sm <?= in_array($key, $bestMonthsActive, true) ? 'btn-success' : 'btn-outline-secondary' ?>" data-month="<?= e($key) ?>">
                                <input type="checkbox" name="best_months[]" value="<?= e($key) ?>" class="d-none" <?= in_array($key, $bestMonthsActive, true) ? 'checked' : '' ?>>
                                <?= e($label) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Best Months Note (optional)</label>
                        <textarea name="best_months_note" rows="2" class="form-control" placeholder="e.g. Avoid the south-west monsoon (May–Jun)." data-ai-field="best_months_note"><?= e($bestMonthsNote) ?></textarea>
                    </div>
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

                    <button type="submit" class="btn btn-success w-100 mb-2"><?= $id > 0 ? 'Update Itinerary' : 'Create Itinerary' ?></button>

                    <?php if ($id > 0): ?>
                        <a href="<?= e(base_url('admin/itinerary_days.php?itinerary_id=' . $id)) ?>" class="btn btn-outline-dark w-100">Manage Day Plan</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <?php $packageType = 'itinerary'; require __DIR__ . '/_package_cover_editor.php'; ?>
                    <h2 class="h5 mb-3">Images</h2>

                    <div class="mb-3">
                        <?php admin_media_picker('hero_image', 'Hero Image / Video', $record, ['allow_video' => true]); ?>
                    </div>

                    <div class="mb-0">
                        <?php admin_media_picker('og_image', 'OG Image', $record); ?>
                    </div>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Included Destinations</h2>
                    <div style="max-height: 320px; overflow:auto;">
                        <?php if (!$hasItineraryDestination || !$hasDestinations): ?>
                            <p class="text-secondary small mb-0">Destination relationship options are not available in this database.</p>
                        <?php elseif (!$allDestinations): ?>
                            <p class="text-secondary small mb-0">No destinations are available yet.</p>
                        <?php else: ?>
                            <?php foreach ($allDestinations as $item): ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="destinations[]" value="<?= (int) $item['id'] ?>" id="itinerary_destination_<?= (int) $item['id'] ?>" <?= in_array((int) $item['id'], $selectedDestinations, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="itinerary_destination_<?= (int) $item['id'] ?>"><?= e($item['name']) ?></label>
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

<?php if ($id > 0) { ?><p><a href="<?= e(base_url('admin/package_fields.php?type=' . $packageType . '&id=' . $id)) ?>">Review all package fields and editors</a> · <a href="<?= e(base_url('admin/map_manager.php?type=itinerary&id=' . $id)) ?>">Manage journey map</a></p><?php }
require_once __DIR__.'/../includes/page_media_admin.php';page_media_admin_assets();page_media_manager('itinerary',(int)$id);
require __DIR__ . '/_bottom.php'; ?>
