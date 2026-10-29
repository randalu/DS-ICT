<?php
declare(strict_types=1);

$adminPage = 'business_partners';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

ensure_business_partners_schema();

$id = (int) get_input('id', 0);
$record = [];
if ($id > 0) {
    $record = fetch_one('SELECT * FROM business_partners WHERE id = :id LIMIT 1', ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Business partner not found.');
        redirect('admin/business_partners.php');
    }
}

$adminTitle = $id > 0 ? 'Edit Business Partner' : 'Add Business Partner';
$statuses = business_partner_statuses();
$statusHelp = [
    'applied' => 'New submission. Not contacted yet.',
    'contacted' => 'You have reached out and are waiting for details or reply.',
    'negotiating' => 'Terms, quality checks, or mutual understanding are being discussed.',
    'agreed' => 'Partnership accepted internally, but not visible publicly yet.',
    'published' => 'Visible on public partner pages and related sections.',
    'rejected' => 'Not moving forward. Hidden publicly.',
];
$businessTypes = business_partner_types();
$categories = fetch_business_partner_categories(false);
$hasDestinations = db_table_exists('destinations');
$hasItineraries = db_table_exists('itineraries');
$hasPartnerDestinations = db_table_exists('business_partner_destination');
$hasPartnerItineraries = db_table_exists('business_partner_itinerary');
$destinationPublishedClause = $hasDestinations && db_column_exists('destinations', 'is_published') ? 'WHERE is_published = 1' : '';
$itineraryPublishedClause = $hasItineraries && db_column_exists('itineraries', 'is_published') ? 'WHERE is_published = 1' : '';
$itineraryDaysSelect = $hasItineraries && db_column_exists('itineraries', 'days') ? 'days' : 'NULL AS days';
$itineraryDaysOrder = $hasItineraries && db_column_exists('itineraries', 'days') ? 'days ASC, ' : '';
$destinations = $hasDestinations
    ? fetch_all("SELECT id, name FROM destinations {$destinationPublishedClause} ORDER BY name ASC")
    : [];
$itineraries = $hasItineraries
    ? fetch_all("SELECT id, name, {$itineraryDaysSelect} FROM itineraries {$itineraryPublishedClause} ORDER BY {$itineraryDaysOrder}name ASC")
    : [];
$photos = $id > 0 ? fetch_business_partner_photos($id) : [];
$selectedDestinations = [];
$selectedItineraries = [];

if ($id > 0) {
    if ($hasPartnerDestinations) {
        $selectedDestinations = array_map('intval', array_column(fetch_all(
            'SELECT destination_id FROM business_partner_destination WHERE partner_id = :id',
            ['id' => $id]
        ), 'destination_id'));
    }
    if ($hasPartnerItineraries) {
        $selectedItineraries = array_map('intval', array_column(fetch_all(
            'SELECT itinerary_id FROM business_partner_itinerary WHERE partner_id = :id',
            ['id' => $id]
        ), 'itinerary_id'));
    }
}

foreach (['destinations' => &$selectedDestinations, 'itineraries' => &$selectedItineraries] as $key => &$selected) {
    $old = old_input($key, null);
    if (is_array($old)) {
        $selected = array_map('intval', $old);
    }
}
unset($selected);

$publicUrl = $record && ($record['status'] ?? '') === 'published' ? business_partner_url($record) : '';

require __DIR__ . '/_top.php';
?>

<?php if ($publicUrl !== ''): ?>
    <div class="d-flex justify-content-end mb-3">
        <a href="<?= e($publicUrl) ?>" class="btn btn-outline-dark btn-sm" target="_blank" rel="noopener">View Public Page</a>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(base_url('admin/business_partner_save.php')) ?>" enctype="multipart/form-data" data-ai-context="business_partner" data-ai-title="<?= e($adminTitle) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Business Details</h2>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Business Name *</label>
                            <input type="text" name="business_name" class="form-control" value="<?= e((string) admin_form_value($record, 'business_name')) ?>" required data-ai-field="business_name" data-ai-label="Business Name" data-ai-context="The registered or trading name of this Sri Lanka tourism business partner (hotel, surf school, restaurant, activity provider etc). Write as a proper business name.">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Business Type</label>
                            <select name="business_type" class="form-select">
                                <?php foreach ($businessTypes as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= admin_form_value($record, 'business_type', 'hotel') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" value="<?= e((string) admin_form_value($record, 'slug')) ?>" data-live-slug="1" data-slug-source="business_name" data-canonical-input="canonical_url" data-canonical-prefix="business-partners/partner">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int) $category['id'] ?>" <?= (int) admin_form_value($record, 'category_id', 1) === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="<?= e((string) admin_form_value($record, 'location')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Primary Destination</label>
                            <?php if ($destinations): ?>
                                <select name="destination_id" class="form-select">
                                    <option value="0">None</option>
                                    <?php foreach ($destinations as $destination): ?>
                                        <option value="<?= (int) $destination['id'] ?>" <?= (int) admin_form_value($record, 'destination_id', 0) === (int) $destination['id'] ? 'selected' : '' ?>><?= e($destination['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="hidden" name="destination_id" value="<?= (int) admin_form_value($record, 'destination_id', 0) ?>">
                                <div class="form-control-plaintext text-secondary">Destination options are not available in this database.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Public Description</label>
                            <textarea name="short_description" rows="5" class="form-control quill-editor" data-ai-field="short_description" data-ai-label="Public Description" data-ai-context="Public-facing description of this business partner shown on the srilankaprivatetravels.com website. 2-4 sentences. Describe what they offer, their location, and why they are a good match for private tour guests. Professional, inviting tone."><?= e((string) admin_form_value($record, 'short_description')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Partner Reason / Application Notes</label>
                            <textarea name="partner_reason" rows="4" class="form-control" data-ai-field="partner_reason" data-ai-label="Partnership Notes" data-ai-context="Internal notes about why this business is a suitable partner for Island Culture Travels. Not shown publicly. Mention what type of guests they suit, any exclusive arrangements, and quality notes."><?= e((string) admin_form_value($record, 'partner_reason')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Accommodation Details</label>
                            <textarea name="accommodation_details" rows="4" class="form-control quill-editor" data-ai-field="accommodation_details" data-ai-label="Accommodation Details" data-ai-context="Detailed description of the accommodation offered by this partner (for hotel/villa partners). Include room types, facilities, atmosphere, and suitability for different traveller types."><?= e((string) admin_form_value($record, 'accommodation_details')) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Price Range</label>
                            <input type="text" name="price_range" class="form-control" value="<?= e((string) admin_form_value($record, 'price_range')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Contact Details</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" value="<?= e((string) admin_form_value($record, 'contact_person')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role / Title</label>
                            <input type="text" name="contact_role" class="form-control" value="<?= e((string) admin_form_value($record, 'contact_role')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e((string) admin_form_value($record, 'email')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= e((string) admin_form_value($record, 'phone')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control" value="<?= e((string) admin_form_value($record, 'whatsapp')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Website URL</label>
                            <input type="url" name="website_url" class="form-control" value="<?= e((string) admin_form_value($record, 'website_url')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Google Maps URL</label>
                            <input type="url" name="google_maps_url" class="form-control" value="<?= e((string) admin_form_value($record, 'google_maps_url')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Related Destinations</h2>
                    <div style="max-height: 220px; overflow:auto;">
                        <?php if ($destinations && $hasPartnerDestinations): ?>
                            <?php foreach ($destinations as $destination): ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="destinations[]" value="<?= (int) $destination['id'] ?>" id="partner_dest_<?= (int) $destination['id'] ?>" <?= in_array((int) $destination['id'], $selectedDestinations, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="partner_dest_<?= (int) $destination['id'] ?>"><?= e($destination['name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-secondary small mb-0">Related destination options are not available in this database.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">Related Itineraries</h2>
                    <div style="max-height: 220px; overflow:auto;">
                        <?php if ($itineraries && $hasPartnerItineraries): ?>
                            <?php foreach ($itineraries as $itinerary): ?>
                                <?php $days = (int) ($itinerary['days'] ?? 0); ?>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" name="itineraries[]" value="<?= (int) $itinerary['id'] ?>" id="partner_itin_<?= (int) $itinerary['id'] ?>" <?= in_array((int) $itinerary['id'], $selectedItineraries, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="partner_itin_<?= (int) $itinerary['id'] ?>"><?= e($itinerary['name']) ?><?= $days > 0 ? ' (' . $days . ' days)' : '' ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-secondary small mb-0">Related itinerary options are not available in this database.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Workflow</h2>
                    <div class="alert alert-info small">
                        Use <strong>Agreed</strong> when the partnership is accepted internally. Use <strong>Published</strong> only when it should appear publicly.
                    </div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select mb-3">
                        <?php foreach ($statuses as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= admin_form_value($record, 'status', 'applied') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="list-group small mb-3">
                        <?php foreach ($statuses as $value => $label): ?>
                            <div class="list-group-item py-2">
                                <strong><?= e($label) ?>:</strong> <?= e($statusHelp[$value] ?? '') ?>
                            </div>
                        <?php endforeach; ?>
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
                    <h2 class="h5 mb-3">Private Admin Notes</h2>
                    <p class="text-secondary small mb-2">
                        Private only. Use this for contact history, agreed terms, referral/commission notes, quality checks, and any concerns.
                    </p>
                    <textarea name="admin_notes" rows="10" class="form-control" placeholder="Suggested format:
- Contacted on:
- Contact person:
- Mutual understanding:
- Commission/referral terms:
- Quality notes:
- Next follow-up:"><?= e((string) admin_form_value($record, 'admin_notes')) ?></textarea>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">Images</h2>
                    <?php if (!empty($record['hero_image'])): ?>
                        <img src="<?= e(stored_media_url((string) $record['hero_image'])) ?>" alt="" class="img-fluid rounded border mb-2" style="max-height: 160px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Hero Image</label>
                        <input type="file" name="hero_image" class="form-control" accept="image/*">
                    </div>
                    <?php if (!empty($record['og_image'])): ?>
                        <img src="<?= e(stored_media_url((string) $record['og_image'])) ?>" alt="" class="img-fluid rounded border mb-2" style="max-height: 160px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">OG Image</label>
                        <input type="file" name="og_image" class="form-control" accept="image/*">
                    </div>

                    <h3 class="h6 mt-4">Photo Gallery</h3>
                    <?php if ($photos): ?>
                        <div class="row g-2 mb-3">
                            <?php foreach ($photos as $photo): ?>
                                <?php $photoId = (int) $photo['id']; ?>
                                <div class="col-6">
                                    <div class="border rounded-3 p-2">
                                        <img src="<?= e(stored_media_url((string) $photo['image_path'])) ?>" alt="" class="img-fluid rounded mb-2" style="height: 100px; width: 100%; object-fit: cover;">
                                        <input type="text" name="photo_alt[<?= $photoId ?>]" class="form-control form-control-sm mb-2" value="<?= e((string) ($photo['alt_text'] ?? '')) ?>" placeholder="Alt text">
                                        <input type="number" name="photo_sort[<?= $photoId ?>]" class="form-control form-control-sm mb-2" value="<?= (int) ($photo['sort_order'] ?? 0) ?>" placeholder="Sort">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="delete_photo_ids[]" value="<?= $photoId ?>" id="delete_partner_photo_<?= $photoId ?>">
                                            <label class="form-check-label text-danger" for="delete_partner_photo_<?= $photoId ?>">Delete</label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="partner_photos[]" class="form-control" accept="image/*" multiple>
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-body">
                    <h2 class="h5 mb-3">SEO</h2>
                    <div class="mb-3">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" value="<?= e((string) admin_form_value($record, 'meta_title')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" rows="3" class="form-control"><?= e((string) admin_form_value($record, 'meta_description')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Canonical URL</label>
                        <input type="text" name="canonical_url" class="form-control" value="<?= e((string) admin_form_value($record, 'canonical_url')) ?>">
                    </div>
                    <button type="submit" class="btn btn-success w-100">Save Business Partner</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/_bottom.php'; ?>
