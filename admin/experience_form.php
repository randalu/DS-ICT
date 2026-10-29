<?php
declare(strict_types=1);

$adminPage = 'experiences';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

ensure_experiences_schema();

$id = (int) get_input('id', 0);
$record = [];

if ($id > 0) {
    $record = fetch_one("SELECT * FROM experiences WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Experience not found.');
        redirect('admin/experiences.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Experience' : 'Add Experience';
$publicUrl = $record ? experience_url($record) : '';

$hasDestinations = db_table_exists('destinations');
$hasItineraries = db_table_exists('itineraries');
$destinationPublishedClause = $hasDestinations && db_column_exists('destinations', 'is_published') ? 'WHERE is_published = 1' : '';
$itineraryPublishedClause = $hasItineraries && db_column_exists('itineraries', 'is_published') ? 'WHERE is_published = 1' : '';
$itineraryDaysSelect = $hasItineraries && db_column_exists('itineraries', 'days') ? 'days' : 'NULL AS days';
$itineraryDaysOrder = $hasItineraries && db_column_exists('itineraries', 'days') ? 'days ASC, ' : '';
$allDestinations = $hasDestinations
    ? fetch_all("SELECT id, name FROM destinations {$destinationPublishedClause} ORDER BY name ASC")
    : [];
$allItineraries = $hasItineraries
    ? fetch_all("SELECT id, name, {$itineraryDaysSelect} FROM itineraries {$itineraryPublishedClause} ORDER BY {$itineraryDaysOrder}name ASC")
    : [];
$allExperiences = fetch_all("SELECT id, title FROM experiences ORDER BY sort_order ASC, title ASC");
$experienceCategories = db_table_exists('experience_categories')
    ? fetch_all("SELECT id, name, slug, emoji FROM experience_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")
    : [];
$selectedCategoryId = (int) admin_form_value($record, 'category_id', 0);
$oldCategoryId = old_input('category_id', null);
if ($oldCategoryId !== null) {
    $selectedCategoryId = (int) $oldCategoryId;
}

$selectedDestinations = [];
$selectedItineraries = [];
$selectedRelatedExperiences = [];
$galleryPhotos = [];

if ($id > 0) {
    $galleryPhotos = fetch_experience_gallery($id);

    $selectedDestinations = array_map('intval', array_column(fetch_all(
        "SELECT destination_id FROM experience_destination WHERE experience_id = :id",
        ['id' => $id]
    ), 'destination_id'));

    $selectedItineraries = array_map('intval', array_column(fetch_all(
        "SELECT itinerary_id FROM experience_itinerary WHERE experience_id = :id",
        ['id' => $id]
    ), 'itinerary_id'));

    $selectedRelatedExperiences = array_map('intval', array_column(fetch_all(
        "SELECT related_experience_id FROM experience_related WHERE experience_id = :id",
        ['id' => $id]
    ), 'related_experience_id'));
}

foreach ([
    'destinations' => &$selectedDestinations,
    'itineraries' => &$selectedItineraries,
    'related_experiences' => &$selectedRelatedExperiences,
] as $key => &$selected) {
    $old = old_input($key, null);
    if (is_array($old)) {
        $selected = array_map('intval', $old);
    }
}
unset($selected);

require __DIR__ . '/_top.php';
require_once __DIR__ . '/_media_picker.php';
?>

<?php if ($publicUrl !== ''): ?>
    <div class="d-flex justify-content-end mb-3">
        <a href="<?= e($publicUrl) ?>" class="btn btn-outline-dark btn-sm" target="_blank" rel="noopener">View Public Page</a>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(base_url('admin/experience_save.php')) ?>" enctype="multipart/form-data" data-ai-context="experience" data-ai-title="<?= e($adminTitle) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Experience Content</h2>

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" value="<?= e((string) admin_form_value($record, 'title')) ?>" required data-ai-field="title" data-ai-label="Experience Title">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input
                            type="text"
                            name="slug"
                            class="form-control"
                            value="<?= e((string) admin_form_value($record, 'slug')) ?>"
                            data-live-slug="1"
                            data-slug-source="title"
                            data-canonical-input="canonical_url"
                            data-canonical-prefix="experiences"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mood / Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Choose category</option>
                            <?php foreach ($experienceCategories as $category): ?>
                                <?php $categoryId = (int) $category['id']; ?>
                                <option value="<?= $categoryId ?>" <?= $selectedCategoryId === $categoryId ? 'selected' : '' ?>>
                                    <?= e(trim((string) (($category['emoji'] ?? '') . ' ' . ($category['name'] ?? '')))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Controls the public Experiences mood tiles and filtered category pages.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Excerpt</label>
                        <textarea name="excerpt" rows="3" class="form-control" data-ai-field="excerpt"><?= e((string) admin_form_value($record, 'excerpt')) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Intro</label>
                        <textarea name="intro" rows="6" class="form-control js-richtext"><?= e((string) admin_form_value($record, 'intro')) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Where to Experience</label>
                        <textarea name="where_to_experience" rows="6" class="form-control js-richtext"><?= e((string) admin_form_value($record, 'where_to_experience')) ?></textarea>
                        <div class="form-text">Use this for regions, route ideas, and how this experience fits into a trip.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Best Time / Travel Notes</label>
                        <textarea name="best_time" rows="3" class="form-control" data-ai-field="best_time"><?= e((string) admin_form_value($record, 'best_time')) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tips JSON</label>
                        <textarea name="tips_json" rows="5" class="form-control" placeholder='["Start early","Carry light clothing"]'><?= e((string) admin_form_value($record, 'tips_json')) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Day Arc (JSON)</label>
                        <textarea name="day_arc_json" rows="6" class="form-control font-monospace" placeholder='[{"time_of_day":"Morning","icon":"🌅","heading":"Early Start","body":"Description","image_path":""}]'><?= e((string) admin_form_value($record, 'day_arc_json')) ?></textarea>
                        <div class="form-text">JSON array of day segments: time_of_day, icon, heading, body, image_path. Max 4 entries (Morning, Midday, Afternoon, Evening).</div>
                    </div>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Related Destinations</h2>
                    <div style="max-height: 240px; overflow:auto;">
                        <?php if ($allDestinations): ?>
                            <?php foreach ($allDestinations as $item): ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="destinations[]" value="<?= (int) $item['id'] ?>" id="exp_dest_<?= (int) $item['id'] ?>" <?= in_array((int) $item['id'], $selectedDestinations, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="exp_dest_<?= (int) $item['id'] ?>"><?= e($item['name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-secondary small mb-0">Destination options are not available in this database.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Related Itineraries</h2>
                    <div style="max-height: 240px; overflow:auto;">
                        <?php if ($allItineraries): ?>
                            <?php foreach ($allItineraries as $item): ?>
                                <?php $days = (int) ($item['days'] ?? 0); ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="itineraries[]" value="<?= (int) $item['id'] ?>" id="exp_itin_<?= (int) $item['id'] ?>" <?= in_array((int) $item['id'], $selectedItineraries, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="exp_itin_<?= (int) $item['id'] ?>"><?= e($item['name']) ?><?= $days > 0 ? ' (' . $days . ' days)' : '' ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-secondary small mb-0">Itinerary options are not available in this database.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Related Experiences</h2>
                    <div style="max-height: 220px; overflow:auto;">
                        <?php foreach ($allExperiences as $item): ?>
                            <?php if ((int) $item['id'] === $id) continue; ?>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="related_experiences[]" value="<?= (int) $item['id'] ?>" id="exp_rel_<?= (int) $item['id'] ?>" <?= in_array((int) $item['id'], $selectedRelatedExperiences, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="exp_rel_<?= (int) $item['id'] ?>"><?= e($item['title']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Publishing</h2>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_published" id="is_published" <?= (int) admin_form_value($record, 'is_published', 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_published">Published</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?= (int) admin_form_value($record, 'is_featured', 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured">Featured</label>
                    </div>
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= e((string) admin_form_value($record, 'sort_order', 0)) ?>">
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Images</h2>
                    <div class="mb-3">
                        <?php admin_media_picker('hero_image', 'Hero Image / Video', $record, ['allow_video' => true]); ?>
                    </div>
                    <div class="mb-3">
                        <?php admin_media_picker('og_image', 'OG Image', $record); ?>
                    </div>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Experience Gallery</h2>
                    <p class="text-secondary small">Add supporting photos for richer experience pages. These appear below the intro as a visual gallery.</p>
                    <?php if ($galleryPhotos): ?>
                        <div class="row g-2 mb-3">
                            <?php foreach ($galleryPhotos as $photo): ?>
                                <?php $photoId = (int) $photo['id']; ?>
                                <div class="col-6">
                                    <div class="border rounded-3 p-2">
                                        <img src="<?= e(stored_media_url((string) $photo['image_path'])) ?>" alt="" class="img-fluid rounded mb-2" style="height: 110px; width: 100%; object-fit: cover;">
                                        <input type="text" name="gallery_alt[<?= $photoId ?>]" class="form-control form-control-sm mb-2" value="<?= e((string) ($photo['alt_text'] ?? '')) ?>" placeholder="Alt text" maxlength="255" required>
                                        <input type="number" name="gallery_sort[<?= $photoId ?>]" class="form-control form-control-sm mb-2" value="<?= (int) ($photo['sort_order'] ?? 0) ?>" placeholder="Sort">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="delete_gallery_ids[]" value="<?= $photoId ?>" id="delete_gallery_<?= $photoId ?>">
                                            <label class="form-check-label text-danger" for="delete_gallery_<?= $photoId ?>">Delete</label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple>
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
                    <div class="mb-3">
                        <label class="form-label">Canonical URL</label>
                        <input type="text" name="canonical_url" class="form-control" value="<?= e((string) admin_form_value($record, 'canonical_url')) ?>">
                    </div>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">CTA</h2>
                    <div class="mb-3">
                        <label class="form-label">CTA Title</label>
                        <input type="text" name="cta_title" class="form-control" value="<?= e((string) admin_form_value($record, 'cta_title')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CTA Text</label>
                        <textarea name="cta_text" rows="3" class="form-control" data-ai-field="cta_text"><?= e((string) admin_form_value($record, 'cta_text')) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Save Experience</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__.'/../includes/page_media_admin.php';page_media_admin_assets();page_media_manager('experience',(int)$id); ?>
<?php require __DIR__ . '/_bottom.php'; ?>
