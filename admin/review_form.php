<?php
declare(strict_types=1);

$adminPage = 'reviews';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

ensure_reviews_schema();

$id = (int) get_input('id', 0);
$record = [];
$photos = [];

if ($id > 0) {
    $record = fetch_one("SELECT * FROM reviews WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Review not found.');
        redirect('admin/reviews.php');
    }
    $photos = fetch_review_photos($id);
}

$adminTitle = $id > 0 ? 'Edit Review' : 'Add Review';
$photoCount = count($photos);
$remainingPhotoSlots = max(0, 5 - $photoCount);
$sourceOptions = ['TripAdvisor', 'Google', 'Facebook', 'Airbnb', 'Email', 'WhatsApp'];

require __DIR__ . '/_top.php';
?>

<form method="post" action="<?= e(base_url('admin/review_save.php')) ?>" enctype="multipart/form-data" data-ai-context="review" data-ai-title="<?= e($adminTitle) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Review Details</h2>

                    <div class="mb-3">
                        <label class="form-label">Customer Name *</label>
                        <input type="text" name="customer_name" class="form-control" value="<?= e((string) admin_form_value($record, 'customer_name')) ?>" required data-ai-field="customer_name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Original Review Title</label>
                        <input type="text" name="review_title" class="form-control" value="<?= e((string) admin_form_value($record, 'review_title')) ?>" placeholder="e.g. Excellent driver and guide!">
                        <div class="form-text">Use the exact title from the original review when available. This appears on homepage review tiles and /reviews.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="<?= e((string) admin_form_value($record, 'country')) ?>" placeholder="Australia">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Flag Code</label>
                            <input type="text" name="country_code" maxlength="2" class="form-control text-uppercase" value="<?= e((string) admin_form_value($record, 'country_code')) ?>" placeholder="AU" list="countryCodeSuggestions">
                            <datalist id="countryCodeSuggestions">
                                <option value="AU">
                                <option value="GB">
                                <option value="US">
                                <option value="DE">
                                <option value="FR">
                                <option value="IN">
                            </datalist>
                            <div class="form-text">2-letter ISO code. Example: AU shows the Australia flag.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Trip Type</label>
                            <input type="text" name="trip_type" class="form-control" value="<?= e((string) admin_form_value($record, 'trip_type')) ?>" placeholder="e.g. Family, Honeymoon">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tour Done Date</label>
                        <input type="date" name="tour_done_date" class="form-control" value="<?= e((string) admin_form_value($record, 'tour_done_date')) ?>">
                        <div class="form-text">Used to sort reviews newest first on the public reviews page.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Review Source Site</label>
                            <input type="text" name="review_source" class="form-control" value="<?= e((string) admin_form_value($record, 'review_source')) ?>" placeholder="e.g. TripAdvisor" list="reviewSourceSuggestions">
                            <datalist id="reviewSourceSuggestions">
                                <?php foreach ($sourceOptions as $sourceOption): ?>
                                    <option value="<?= e($sourceOption) ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <div class="form-text">Known sources show cleaner badges: TripAdvisor, Google, Facebook, Airbnb.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Original Review Link</label>
                            <input type="url" name="review_source_url" class="form-control" value="<?= e((string) admin_form_value($record, 'review_source_url')) ?>" placeholder="https://...">
                            <div class="form-text">Optional, but useful for trust. Opens as "View original review" on /reviews.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rating *</label>
                        <select name="rating" class="form-select" required>
                            <?php $currentRating = (int) admin_form_value($record, 'rating', 5); ?>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= $currentRating === $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Review Text *</label>
                        <textarea name="review_text" rows="8" class="form-control" required data-ai-field="review_text"><?= e((string) admin_form_value($record, 'review_text')) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card card-soft mt-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <h2 class="h5 mb-1">Customer Photos</h2>
                            <p class="text-secondary small mb-0">Upload original customer tour photos only. These can appear on /reviews and in the reviews hero image pool.</p>
                        </div>
                        <span class="badge text-bg-light border"><?= (int) $photoCount ?>/5 used</span>
                    </div>
                    <div class="alert alert-info small mb-3">
                        You can keep up to 5 photos per review. Remaining slots now: <strong><?= (int) $remainingPhotoSlots ?></strong>.
                        If you tick photos for deletion and upload replacements in the same save, the deleted slots become available.
                    </div>

                    <?php if ($photos): ?>
                        <div class="row g-3 mb-3">
                            <?php foreach ($photos as $photo): ?>
                                <?php $photoId = (int) $photo['id']; ?>
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-2 h-100 bg-white">
                                        <img src="<?= e(stored_media_url((string) $photo['image_path'])) ?>" alt="" class="img-fluid rounded mb-2" style="height: 140px; width: 100%; object-fit: cover;">
                                        <label class="form-label small fw-semibold">Alt Text</label>
                                        <input type="text" name="photo_alt[<?= $photoId ?>]" class="form-control form-control-sm mb-2" value="<?= e((string) ($photo['alt_text'] ?? '')) ?>" maxlength="255" required>
                                        <div class="form-text mb-2">Describe what is in the photo, not just the customer name.</div>
                                        <label class="form-label small fw-semibold">Sort Order</label>
                                        <input type="number" name="photo_sort[<?= $photoId ?>]" class="form-control form-control-sm mb-2" value="<?= (int) ($photo['sort_order'] ?? 0) ?>">
                                        <div class="form-check border-top pt-2">
                                            <input type="checkbox" class="form-check-input" name="delete_photo_ids[]" value="<?= $photoId ?>" id="delete_photo_<?= $photoId ?>">
                                            <label for="delete_photo_<?= $photoId ?>" class="form-check-label text-danger fw-semibold">Delete this photo on save</label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <label class="form-label">Add Photos</label>
                    <input type="file" name="review_photos[]" class="form-control" accept="image/*" multiple>
                    <div class="form-text">Upload JPG, PNG, WebP, AVIF, or GIF. The server will ignore photos beyond the 5-photo limit.</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-soft">
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
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="is_published" <?= admin_checkbox_value($record, 'is_published', 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_published">Published</label>
                    </div>

                    <button type="submit" class="btn btn-success w-100"><?= $id > 0 ? 'Update Review' : 'Create Review' ?></button>
                </div>
            </div>

            <div class="card card-soft mt-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Before Publishing</h2>
                    <ul class="small text-secondary mb-0 ps-3">
                        <li>Use the customer's original review title when possible.</li>
                        <li>Set Tour Done Date so newest real experiences appear first.</li>
                        <li>Add the source and original link when available.</li>
                        <li>Add a country code only when you know the country.</li>
                        <li>Use customer-uploaded photos only, not stock images.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/_bottom.php'; ?>
