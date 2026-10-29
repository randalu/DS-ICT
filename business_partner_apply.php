<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

ensure_business_partners_schema();

if (!business_partners_public_enabled()) {
    require __DIR__ . '/404.php';
    exit;
}

$errors = [];
$success = false;
$businessTypes = business_partner_types();
$destinations = db_table_exists('destinations')
    ? fetch_all("SELECT id, name FROM destinations WHERE is_published = 1 ORDER BY name ASC")
    : [];
$destinationMap = [];
foreach ($destinations as $destination) {
    $destinationId = (int) ($destination['id'] ?? 0);
    if ($destinationId > 0) {
        $destinationMap[$destinationId] = $destination;
    }
}
$category = find_business_partner_category_by_slug('hotels-accommodations', true)
    ?: ['id' => 1, 'name' => 'Hotels & Accommodations', 'slug' => 'hotels-accommodations'];

if (!isset($_SESSION['partner_application_rendered_at'])) {
    $_SESSION['partner_application_rendered_at'] = time();
}

if (is_post()) {
    guard_post_size();
    require_csrf();

    $businessName = trim((string) post_input('business_name', ''));
    $businessType = trim((string) post_input('business_type', 'hotel'));
    $location = trim((string) post_input('location', ''));
    $destinationId = (int) post_input('destination_id', 0);
    $contactPerson = trim((string) post_input('contact_person', ''));
    $contactRole = trim((string) post_input('contact_role', ''));
    $email = trim((string) post_input('email', ''));
    $phone = trim((string) post_input('phone', ''));
    $whatsapp = trim((string) post_input('whatsapp', ''));
    $websiteUrl = trim((string) post_input('website_url', ''));
    $googleMapsUrl = trim((string) post_input('google_maps_url', ''));
    $shortDescription = trim((string) post_input('short_description', ''));
    $partnerReason = trim((string) post_input('partner_reason', ''));
    $accommodationDetails = trim((string) post_input('accommodation_details', ''));
    $priceRange = trim((string) post_input('price_range', ''));
    $consent = post_input('representative_consent') ? 1 : 0;
    $honeypot = trim((string) post_input('company_website_confirm', ''));
    $formRenderedAt = (int) post_input('form_rendered_at', 0);
    $sessionRenderedAt = (int) ($_SESSION['partner_application_rendered_at'] ?? 0);
    if ($formRenderedAt <= 0) {
        $formRenderedAt = $sessionRenderedAt;
    }

    old_input_set($_POST);

    if ($businessName === '') {
        $errors[] = 'Business name is required.';
    }
    if (!isset($businessTypes[$businessType])) {
        $errors[] = 'Please select a valid business type.';
    }
    if ($location === '') {
        $errors[] = 'Location is required.';
    }
    if ($contactPerson === '') {
        $errors[] = 'Contact person is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid business email is required.';
    }
    if ($phone === '' && $whatsapp === '') {
        $errors[] = 'Please provide a phone or WhatsApp number.';
    }
    foreach (['website_url' => [$websiteUrl, 'Website URL'], 'google_maps_url' => [$googleMapsUrl, 'Google Maps URL']] as [$urlValue, $urlLabel]) {
        if ($urlValue === '') {
            continue;
        }
        $scheme = strtolower((string) parse_url($urlValue, PHP_URL_SCHEME));
        if (!filter_var($urlValue, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
            $errors[] = $urlLabel . ' must be a valid http(s) URL.';
        }
    }
    if ($destinationId > 0 && !isset($destinationMap[$destinationId])) {
        $errors[] = 'Please choose a valid destination.';
    }
    if ($shortDescription === '') {
        $errors[] = 'Business description is required.';
    }
    if ($partnerReason === '') {
        $errors[] = 'Please tell us why you want to partner with us.';
    }
    if (!$consent) {
        $errors[] = 'Please confirm that you represent this business.';
    }
    if (slugify($businessName) === '') {
        $errors[] = 'Business name must contain letters or numbers.';
    }

    $lengthLimits = [
        [$businessName, 255, 'Business name'],
        [$businessType, 80, 'Business type'],
        [$location, 180, 'Location'],
        [$contactPerson, 180, 'Contact person'],
        [$contactRole, 180, 'Role'],
        [$email, 190, 'Email'],
        [$phone, 80, 'Phone'],
        [$whatsapp, 80, 'WhatsApp'],
        [$websiteUrl, 255, 'Website URL'],
        [$googleMapsUrl, 255, 'Google Maps URL'],
        [$priceRange, 80, 'Price range'],
    ];
    foreach ($lengthLimits as [$value, $limit, $label]) {
        if ($value !== '' && mb_strlen($value) > $limit) {
            $errors[] = $label . ' must be ' . $limit . ' characters or fewer.';
        }
    }

    $requestIp = request_ip_address();
    $spamReason = '';
    if ($honeypot !== '') {
        $spamReason = 'honeypot';
    }
    if ($spamReason === '' && $formRenderedAt > 0 && (time() - $formRenderedAt) < 3) {
        $spamReason = 'too_fast';
    }
    if ($spamReason === '' && form_submission_limit_exceeded('business_partner_application', $requestIp, 900, 3)) {
        $spamReason = 'rate_limit';
    }
    if ($spamReason !== '') {
        $errors[] = 'We could not accept this submission right now. Please try again later.';
    }

    if (!$errors) {
        try {
            form_submission_log_hit('business_partner_application', $requestIp);
            db()->beginTransaction();

            $baseSlug = slugify($businessName);
            $slug = $baseSlug;
            if (fetch_one('SELECT id FROM business_partners WHERE slug = :slug LIMIT 1', ['slug' => $slug])) {
                $slug = $baseSlug . '-' . time();
            }

            $data = [
                'category_id' => (int) ($category['id'] ?? 1),
                'business_name' => $businessName,
                'slug' => $slug,
                'business_type' => $businessType,
                'location' => $location,
                'destination_id' => $destinationId > 0 ? $destinationId : null,
                'contact_person' => $contactPerson,
                'contact_role' => $contactRole !== '' ? $contactRole : null,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'whatsapp' => $whatsapp !== '' ? $whatsapp : null,
                'website_url' => $websiteUrl !== '' ? $websiteUrl : null,
                'google_maps_url' => $googleMapsUrl !== '' ? $googleMapsUrl : null,
                'short_description' => $shortDescription,
                'partner_reason' => $partnerReason,
                'accommodation_details' => $accommodationDetails !== '' ? $accommodationDetails : null,
                'price_range' => $priceRange !== '' ? $priceRange : null,
                'status' => 'applied',
                'is_featured' => 0,
                'sort_order' => 0,
                'canonical_url' => base_url('business-partners/partner/' . $slug),
            ];

            $columns = array_keys($data);
            $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
            execute_query(
                'INSERT INTO business_partners (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')',
                $data
            );
            $partnerId = (int) last_insert_id();

            if ($destinationId > 0) {
                if (db_table_exists('business_partner_destination')) {
                    execute_query(
                        'INSERT IGNORE INTO business_partner_destination (partner_id, destination_id) VALUES (:partner_id, :destination_id)',
                        ['partner_id' => $partnerId, 'destination_id' => $destinationId]
                    );
                }
            }

            if (db_table_exists('business_partner_photos') && !empty($_FILES['partner_photos']['name']) && is_array($_FILES['partner_photos']['name'])) {
                $uploaded = 0;
                foreach ($_FILES['partner_photos']['name'] as $index => $name) {
                    if ($uploaded >= 5) {
                        break;
                    }
                    if (trim((string) $name) === '') {
                        continue;
                    }
                    $file = [
                        'name' => $_FILES['partner_photos']['name'][$index] ?? '',
                        'type' => $_FILES['partner_photos']['type'][$index] ?? '',
                        'tmp_name' => $_FILES['partner_photos']['tmp_name'][$index] ?? '',
                        'error' => $_FILES['partner_photos']['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $_FILES['partner_photos']['size'][$index] ?? 0,
                    ];
                    $path = upload_image($file, 'business-partners');
                    if ($path === null) {
                        continue;
                    }
                    execute_query(
                        'INSERT INTO business_partner_photos (partner_id, image_path, alt_text, sort_order)
                         VALUES (:partner_id, :image_path, :alt_text, :sort_order)',
                        [
                            'partner_id' => $partnerId,
                            'image_path' => $path,
                            'alt_text' => $businessName . ' accommodation partner',
                            'sort_order' => ($uploaded + 1) * 10,
                        ]
                    );
                    if ($uploaded === 0) {
                        execute_query(
                            'UPDATE business_partners SET hero_image = :image, og_image = :image WHERE id = :id',
                            ['image' => $path, 'id' => $partnerId]
                        );
                    }
                    $uploaded++;
                }
            }

            db()->commit();
            old_input_clear();
            unset($_SESSION['partner_application_rendered_at']);
            $success = true;
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $errors[] = 'Could not submit partner application.';
        }
    }
}

$seoTitle = 'Partner With Island Culture Travels | ' . APP_NAME;
$seoDescription = 'Hotels and accommodation businesses in Sri Lanka can apply to partner with Island Culture Travels after business review and mutual agreement.';
$seoCanonical = base_url('business-partners/apply');
$seoImage = DEFAULT_OG_IMAGE;
$seoType = 'website';
$bodyClass = 'page-business-partners';
$heroImage = DEFAULT_OG_IMAGE;
$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Business Partners', 'url' => base_url('business-partners')],
    ['name' => 'Apply', 'url' => base_url('business-partners/apply')],
];
$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    [
        '@type' => 'WebPage',
        'name' => 'Partner With Island Culture Travels',
        'url' => $seoCanonical,
        'description' => $seoDescription,
        'isPartOf' => ['@id' => base_url() . '#website'],
    ],
];

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media page-hero-partners">
    <?= responsive_image([
        'src' => $heroImage,
        'alt' => 'Apply for a Sri Lanka tourism business partnership',
        'widths' => [800, 1200, 1600],
        'sizes' => '100vw',
        'width' => 1600,
        'height' => 900,
        'loading' => 'eager',
        'fetchpriority' => 'high',
        'class' => 'page-hero-bg-img',
        'picture_class' => 'page-hero-media',
    ]) ?>
    <div class="container">
        <div class="hero-content">
            <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
            <span class="section-kicker" style="color:var(--accent-light);">B2B Application</span>
            <h1 class="hero-title">Partner With Island Culture Travels</h1>
            <div class="hero-summary-box">
                <p class="lead mb-0">For Sri Lanka hotels and accommodation businesses interested in working with our private tour planning team.</p>
            </div>
            <div class="hero-meta">
                <span class="badge-soft">Business owners and managers only</span>
                <span class="badge-soft">Private application review</span>
                <span class="badge-soft">Nothing public without approval</span>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <?php if ($success): ?>
                    <div class="detail-block-soft">
                        <span class="section-kicker">Application Received</span>
                        <h2 class="h4">Thank you for applying to partner with us.</h2>
                        <p class="text-secondary mb-3">Your business details are private for now. We will review the application and contact you before anything appears publicly.</p>
                        <a href="<?= e(base_url('business-partners')) ?>" class="btn btn-success">Back to Business Partners</a>
                    </div>
                <?php else: ?>
                    <div class="partner-application-lead mb-4">
                        <div>
                            <span class="section-kicker">Before You Apply</span>
                            <h2>Applications are private until mutual agreement</h2>
                            <p>Submitting this form does not publish your business. We review the details, contact you directly, and only publish partners after both sides agree.</p>
                        </div>
                        <ul>
                            <li>Use a business email if possible.</li>
                            <li>Add a Google Maps URL if the location is hard to find.</li>
                            <li>Upload clear property photos only if you have rights to use them.</li>
                        </ul>
                    </div>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger"><?= e(implode(' ', $errors)) ?></div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" class="partner-application-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_rendered_at" value="<?= (int) ($_SESSION['partner_application_rendered_at'] ?? time()) ?>">
                        <input type="text" name="company_website_confirm" value="" tabindex="-1" autocomplete="off" class="d-none" aria-hidden="true">

                        <div class="detail-block-soft mb-4">
                            <h2 class="h5 mb-3">Business Details</h2>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="bp-name" class="form-label">Business Name *</label>
                                    <input type="text" id="bp-name" name="business_name" class="form-control" value="<?= e((string) old_input('business_name', '')) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="bp-type" class="form-label">Business Type *</label>
                                    <select id="bp-type" name="business_type" class="form-select" required>
                                        <?php foreach ($businessTypes as $value => $label): ?>
                                            <option value="<?= e($value) ?>" <?= old_input('business_type', 'hotel') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="bp-location" class="form-label">Location *</label>
                                    <input type="text" id="bp-location" name="location" class="form-control" value="<?= e((string) old_input('location', '')) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="bp-destination" class="form-label">Nearest Destination</label>
                                    <select id="bp-destination" name="destination_id" class="form-select">
                                        <option value="0">Select destination</option>
                                        <?php foreach ($destinations as $destination): ?>
                                            <option value="<?= (int) $destination['id'] ?>" <?= (int) old_input('destination_id', 0) === (int) $destination['id'] ? 'selected' : '' ?>><?= e($destination['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="bp-description" class="form-label">Short Business Description *</label>
                                    <textarea id="bp-description" name="short_description" rows="4" class="form-control" required><?= e((string) old_input('short_description', '')) ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="bp-details" class="form-label">Rooms / Property Details</label>
                                    <textarea id="bp-details" name="accommodation_details" rows="3" class="form-control"><?= e((string) old_input('accommodation_details', '')) ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="bp-price" class="form-label">Typical Price Range</label>
                                    <input type="text" id="bp-price" name="price_range" class="form-control" value="<?= e((string) old_input('price_range', '')) ?>" placeholder="Example: USD 80-160 per night">
                                </div>
                            </div>
                        </div>

                        <div class="detail-block-soft mb-4">
                            <h2 class="h5 mb-3">Contact and Partnership</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="bp-contact-person" class="form-label">Contact Person *</label>
                                    <input type="text" id="bp-contact-person" name="contact_person" class="form-control" value="<?= e((string) old_input('contact_person', '')) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="bp-role" class="form-label">Role / Title</label>
                                    <input type="text" id="bp-role" name="contact_role" class="form-control" value="<?= e((string) old_input('contact_role', '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="bp-email" class="form-label">Email *</label>
                                    <input type="email" id="bp-email" name="email" class="form-control" value="<?= e((string) old_input('email', '')) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="bp-phone" class="form-label">Phone</label>
                                    <input type="text" id="bp-phone" name="phone" class="form-control" value="<?= e((string) old_input('phone', '')) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="bp-whatsapp" class="form-label">WhatsApp</label>
                                    <input type="text" id="bp-whatsapp" name="whatsapp" class="form-control" value="<?= e((string) old_input('whatsapp', '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="bp-website" class="form-label">Website URL</label>
                                    <input type="url" id="bp-website" name="website_url" class="form-control" value="<?= e((string) old_input('website_url', '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="bp-maps" class="form-label">Google Maps URL</label>
                                    <input type="url" id="bp-maps" name="google_maps_url" class="form-control" value="<?= e((string) old_input('google_maps_url', '')) ?>">
                                </div>
                                <div class="col-12">
                                    <label for="bp-reason" class="form-label">Why do you want to partner with us? *</label>
                                    <textarea id="bp-reason" name="partner_reason" rows="4" class="form-control" required><?= e((string) old_input('partner_reason', '')) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label for="bp-photos" class="form-label">Optional Images</label>
                                    <input type="file" id="bp-photos" name="partner_photos[]" class="form-control" accept="image/*" multiple>
                                    <div class="form-text">Upload up to 5 property/business images. They stay private until admin approval.</div>
                                </div>
                            </div>
                        </div>

                        <div class="detail-block-soft">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="representative_consent" id="representative_consent" value="1" <?= old_input('representative_consent') ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="representative_consent">I confirm that I represent this business and want to discuss a partnership with Island Culture Travels.</label>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg">Submit Partner Application</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
