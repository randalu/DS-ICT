<?php
declare(strict_types=1);

$adminPage = 'destinations';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

$id = (int) get_input('id', 0);
$record = [];

if ($id > 0) {
    $record = fetch_one("SELECT * FROM destinations WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Destination not found.');
        redirect('admin/destinations.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Destination' : 'Add Destination';

$hasTours = db_table_exists('tours');
$hasItineraries = db_table_exists('itineraries');
$hasDestinationRelated = db_table_exists('destination_related')
    && db_column_exists('destination_related', 'destination_id')
    && db_column_exists('destination_related', 'related_destination_id');
$hasDestinationTour = db_table_exists('destination_tour')
    && db_column_exists('destination_tour', 'destination_id')
    && db_column_exists('destination_tour', 'tour_id');
$hasDestinationItinerary = db_table_exists('destination_itinerary')
    && db_column_exists('destination_itinerary', 'destination_id')
    && db_column_exists('destination_itinerary', 'itinerary_id');
$hasSeasonalNotes = db_table_exists('seasonal_notes')
    && db_column_exists('seasonal_notes', 'entity_type')
    && db_column_exists('seasonal_notes', 'entity_id')
    && db_column_exists('seasonal_notes', 'season_label')
    && db_column_exists('seasonal_notes', 'season_note');

$allDestinations = fetch_all("SELECT id, name FROM destinations ORDER BY name ASC");
$allTours = $hasTours ? fetch_all("SELECT id, name FROM tours ORDER BY name ASC") : [];
$allItineraries = [];
if ($hasItineraries) {
    $itineraryHasDays = db_column_exists('itineraries', 'days');
    $itinerarySelect = $itineraryHasDays ? 'id, name, days' : 'id, name';
    $itineraryOrder = $itineraryHasDays ? 'days ASC, name ASC' : 'name ASC';
    $allItineraries = fetch_all("SELECT {$itinerarySelect} FROM itineraries ORDER BY {$itineraryOrder}");
}

$selectedRelatedDestinations = [];
$selectedTours = [];
$selectedItineraries = [];

if ($id > 0 && $hasDestinationRelated) {
    $selectedRelatedDestinations = array_map('intval', array_column(fetch_all(
        "SELECT related_destination_id FROM destination_related WHERE destination_id = :id",
        ['id' => $id]
    ), 'related_destination_id'));
}

if ($id > 0 && $hasDestinationTour) {
    $selectedTours = array_map('intval', array_column(fetch_all(
        "SELECT tour_id FROM destination_tour WHERE destination_id = :id",
        ['id' => $id]
    ), 'tour_id'));
}

if ($id > 0 && $hasDestinationItinerary) {
    $selectedItineraries = array_map('intval', array_column(fetch_all(
        "SELECT itinerary_id FROM destination_itinerary WHERE destination_id = :id",
        ['id' => $id]
    ), 'itinerary_id'));
}

$oldRelatedDestinations = old_input('related_destinations', null);
$oldTours = old_input('tours', null);
$oldItineraries = old_input('itineraries', null);

if (is_array($oldRelatedDestinations)) {
    $selectedRelatedDestinations = array_map('intval', $oldRelatedDestinations);
}
if (is_array($oldTours)) {
    $selectedTours = array_map('intval', $oldTours);
}
if (is_array($oldItineraries)) {
    $selectedItineraries = array_map('intval', $oldItineraries);
}

require __DIR__ . '/_top.php';
require_once __DIR__ . '/_media_picker.php';
?>

<form method="post" action="<?= e(base_url('admin/destination_save.php')) ?>" enctype="multipart/form-data" data-ai-context="destination" data-ai-title="<?= e($adminTitle) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Content</h2>

                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= e((string) admin_form_value($record, 'name')) ?>" required data-ai-field="name" data-ai-label="Destination Name">
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
                            data-canonical-prefix="destinations"
                        >
                        <div class="form-text">Auto-generates live from name. Edit manually to override.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Excerpt</label>
                        <textarea name="excerpt" rows="3" class="form-control" data-ai-field="excerpt"><?= e((string) admin_form_value($record, 'excerpt')) ?></textarea>
                    </div>

                    <?php
                    $textareas = [
                        'intro' => 'Introduction',
                        'why_visit' => 'Why Visit',
                        'things_to_do' => 'Things to Do',
                        'best_time' => 'Best Time',
                        'how_to_get_there' => 'How to Get There',
                        'nearby_travel_times' => 'Nearby Travel Times',
                        'works_well_with' => 'Works Well With',
                        'suggested_itinerary' => 'Suggested Itinerary',
                        'where_to_stay' => 'Where to Stay',
                        'travel_tips' => 'Travel Tips',
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

                    <?php require __DIR__ . '/_faq_editor.php'; ?>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-1">Best Months at a Glance</h2>
                    <p class="text-secondary small">Tick the best months for visiting. Surfaced as a 12-month grid above the FAQ.</p>
                    <?php
                    $bestTimeRaw = (string) admin_form_value($record, 'best_time_json');
                    $bestTimeDecoded = $bestTimeRaw !== '' ? (json_decode($bestTimeRaw, true) ?: []) : [];
                    $bestTimeMonths = isset($bestTimeDecoded['months']) && is_array($bestTimeDecoded['months'])
                        ? array_map('strtolower', $bestTimeDecoded['months'])
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

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-1">Route &amp; Driving Notes</h2>
                    <p class="text-secondary small">JSON array of stops on the route. Each item: <code>{"stop":"…","distance":"…","note":"…"}</code></p>
                    <textarea name="route_notes_json" rows="6" class="form-control font-monospace" placeholder='[
  {"stop":"Negombo","distance":"40 min from CMB","note":"Optional first night"},
  {"stop":"Sigiriya","distance":"3.5 hr drive","note":"Climb early morning"}
]'><?= e((string) admin_form_value($record, 'route_notes_json')) ?></textarea>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-1">Activity Categories &amp; Source Markets</h2>
                    <p class="text-secondary small">JSON arrays of activity category slugs and source market data.</p>
                    <div class="mb-3">
                        <label class="form-label">Activity Categories (JSON)</label>
                        <textarea name="activity_categories_json" rows="4" class="form-control font-monospace" placeholder='["culture","nature","wildlife","beach"]'><?= e((string) admin_form_value($record, 'activity_categories_json')) ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Source Markets (JSON)</label>
                        <textarea name="source_markets_json" rows="4" class="form-control font-monospace" placeholder='[{"market":"UK","pct":30},{"market":"GER","pct":20}]'><?= e((string) admin_form_value($record, 'source_markets_json')) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card card-soft mb-4">
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

                    <button type="submit" class="btn btn-success w-100"><?= $id > 0 ? 'Update Destination' : 'Create Destination' ?></button>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Classification &amp; Ratings</h2>
                    <div class="mb-3">
                        <label class="form-label">Region Slug</label>
                        <select name="region_slug" class="form-select">
                            <option value="">— None —</option>
                            <?php $currentRegion = (string) admin_form_value($record, 'region_slug'); ?>
                            <?php foreach ([
                                'north' => 'North',
                                'south' => 'South',
                                'east' => 'East',
                                'west' => 'West',
                                'central' => 'Central',
                                'hill' => 'Hill Country',
                                'wildlife' => 'Wildlife',
                                'beach' => 'Beach',
                                'cultural' => 'Cultural',
                            ] as $val => $label): ?>
                            <option value="<?= e($val) ?>" <?= $currentRegion === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-0">
                        <div class="col-6">
                            <label class="form-label">Avg Rating</label>
                            <input type="number" step="0.1" min="0" max="5" name="avg_rating" class="form-control" value="<?= e((string) admin_form_value($record, 'avg_rating', '')) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Rating Count</label>
                            <input type="number" min="0" name="avg_rating_count" class="form-control" value="<?= e((string) admin_form_value($record, 'avg_rating_count', '')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Seasonal Availability</h2>
                    <?php
                    $seasonNote = null;
                    if ($id > 0 && $hasSeasonalNotes) {
                        $seasonNote = fetch_one("SELECT * FROM seasonal_notes WHERE entity_type='destination' AND entity_id=:id LIMIT 1", ['id' => $id]);
                    }
                    ?>
                    <?php if (!$hasSeasonalNotes): ?>
                        <p class="text-secondary small mb-0">Seasonal availability is not available in this database.</p>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Season Label</label>
                        <select name="season_label" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach (['High Season','Low Season','Peak Season','Shoulder Season','Monsoon Season'] as $sl): ?>
                            <option value="<?= e($sl) ?>" <?= ($seasonNote['season_label'] ?? '') === $sl ? 'selected' : '' ?>><?= e($sl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Season Note</label>
                        <textarea name="season_note" rows="3" class="form-control" placeholder="e.g. Best visited Oct–Apr. Avoid May–Sep due to SW monsoon."><?= e((string)($seasonNote['season_note'] ?? '')) ?></textarea>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Images</h2>

                    <div class="mb-3">
                        <?php admin_media_picker('hero_image', 'Hero Image / Video', $record, ['allow_video' => true]); ?>
                    </div>

                    <div class="mb-3">
                        <?php admin_media_picker('cover_image', 'Cover Image', $record); ?>
                    </div>

                    <div class="mb-0">
                        <?php admin_media_picker('og_image', 'OG Image', $record); ?>
                    </div>
                </div>
            </div>

            <?php if ($id > 0): ?>
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-2">Photo Gallery</h2>
                    <p class="text-secondary small">Upload a gallery of destination photos shown in a carousel on the public page.</p>
                    <a href="<?= e(base_url('admin/destination_photos.php?destination_id=' . $id)) ?>" class="btn btn-outline-secondary btn-sm">Manage Photos</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Related Destinations</h2>
                    <div style="max-height: 240px; overflow:auto;">
                        <?php if (!$hasDestinationRelated): ?>
                            <p class="text-secondary small mb-0">Related destination links are not available in this database.</p>
                        <?php else: ?>
                            <?php $renderedDestinationOptions = 0; ?>
                            <?php foreach ($allDestinations as $item): ?>
                                <?php if ((int) $item['id'] === $id) continue; ?>
                                <?php $renderedDestinationOptions++; ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="related_destinations[]" value="<?= (int) $item['id'] ?>" id="rd_<?= (int) $item['id'] ?>" <?= in_array((int) $item['id'], $selectedRelatedDestinations, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="rd_<?= (int) $item['id'] ?>"><?= e($item['name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($renderedDestinationOptions === 0): ?>
                                <p class="text-secondary small mb-0">No other destinations are available yet.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Related Tours</h2>
                    <div style="max-height: 220px; overflow:auto;">
                        <?php if (!$hasDestinationTour || !$hasTours): ?>
                            <p class="text-secondary small mb-0">Tour relationship options are not available in this database.</p>
                        <?php elseif (!$allTours): ?>
                            <p class="text-secondary small mb-0">No tours are available yet.</p>
                        <?php else: ?>
                            <?php foreach ($allTours as $item): ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="tours[]" value="<?= (int) $item['id'] ?>" id="tour_<?= (int) $item['id'] ?>" <?= in_array((int) $item['id'], $selectedTours, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tour_<?= (int) $item['id'] ?>"><?= e($item['name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Related Itineraries</h2>
                    <div style="max-height: 220px; overflow:auto;">
                        <?php if (!$hasDestinationItinerary || !$hasItineraries): ?>
                            <p class="text-secondary small mb-0">Itinerary relationship options are not available in this database.</p>
                        <?php elseif (!$allItineraries): ?>
                            <p class="text-secondary small mb-0">No itineraries are available yet.</p>
                        <?php else: ?>
                            <?php foreach ($allItineraries as $item): ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="itineraries[]" value="<?= (int) $item['id'] ?>" id="itinerary_<?= (int) $item['id'] ?>" <?= in_array((int) $item['id'], $selectedItineraries, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="itinerary_<?= (int) $item['id'] ?>"><?= e($item['name']) ?></label>
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

<?php require_once __DIR__.'/../includes/page_media_admin.php';page_media_admin_assets();page_media_manager('destination',(int)$id); ?>
<?php require __DIR__ . '/_bottom.php'; ?>
