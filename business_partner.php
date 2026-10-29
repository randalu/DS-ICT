<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

if (!business_partners_public_enabled()) {
    require __DIR__ . '/404.php';
    exit;
}

$slug = trim((string) get_input('slug', ''));
if ($slug === '') {
    require __DIR__ . '/404.php';
    exit;
}

$partner = find_published_business_partner_by_slug($slug);
if (!$partner) {
    require __DIR__ . '/404.php';
    exit;
}

$photos = fetch_business_partner_photos((int) $partner['id']);
$typeLabel = business_partner_types()[(string) ($partner['business_type'] ?? '')] ?? 'Accommodation';
$pageUrl = !empty($partner['canonical_url'])
    ? (string) $partner['canonical_url']
    : business_partner_url($partner);
$pageImage = !empty($partner['og_image'])
    ? stored_media_url((string) $partner['og_image'])
    : (!empty($partner['hero_image'])
        ? stored_media_url((string) $partner['hero_image'])
        : (!empty($photos[0]['image_path']) ? stored_media_url((string) $photos[0]['image_path']) : DEFAULT_OG_IMAGE));

$seoTitle = trim((string) ($partner['meta_title'] ?? '')) ?: ((string) $partner['business_name'] . ' | ' . APP_NAME);
$seoDescription = trim((string) ($partner['meta_description'] ?? '')) ?: excerpt_words((string) ($partner['short_description'] ?? ''), 28);
$seoCanonical = $pageUrl;
$seoImage = $pageImage;
$seoType = 'article';
$bodyClass = 'page-business-partner';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Business Partners', 'url' => base_url('business-partners')],
];
if (!empty($partner['category_name']) && !empty($partner['category_slug'])) {
    $breadcrumbs[] = ['name' => (string) $partner['category_name'], 'url' => base_url('business-partners/' . $partner['category_slug'])];
}
$breadcrumbs[] = ['name' => (string) $partner['business_name'], 'url' => $pageUrl];

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_business_partner($partner, $pageUrl, $photos),
];

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media page-hero-partners">
    <?= responsive_image([
        'src' => $pageImage,
        'alt' => (string) $partner['business_name'],
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
            <span class="section-kicker" style="color:var(--accent-light);">Official Accommodation Partner</span>
            <h1 class="hero-title"><?= e($partner['business_name']) ?></h1>
            <div class="hero-summary-box">
                <p class="lead mb-0"><?= e((string) ($partner['short_description'] ?? '')) ?></p>
            </div>
            <div class="hero-meta">
                <span class="badge-soft"><?= e($typeLabel) ?></span>
                <?php if (!empty($partner['location'])): ?>
                    <span class="badge-soft"><?= e($partner['location']) ?></span>
                <?php endif; ?>
                <?php if (!empty($partner['price_range'])): ?>
                    <span class="badge-soft"><?= e($partner['price_range']) ?></span>
                <?php endif; ?>
            </div>
            <div class="hero-actions mt-3 d-flex gap-2 flex-wrap">
                <a href="<?= e(base_url('contact?message=' . rawurlencode('I am interested in including ' . (string) $partner['business_name'] . ' in my Sri Lanka tour.'))) ?>" class="btn btn-primary">Ask Us to Include This Stay</a>
                <a href="<?= e(base_url('business-partners')) ?>" class="btn btn-ghost">View All Partners</a>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="detail-block-soft mb-4">
                    <span class="section-kicker">Partner Profile</span>
                    <h2 class="h4">About <?= e($partner['business_name']) ?></h2>
                    <div class="content-prose"><?= render_public_content((string) ($partner['short_description'] ?? '')) ?></div>
                    <?php if (!empty($partner['accommodation_details'])): ?>
                        <hr>
                        <h3 class="h5">Accommodation Notes</h3>
                        <div class="content-prose"><?= render_public_content((string) $partner['accommodation_details']) ?></div>
                    <?php endif; ?>
                </div>

                <?php if ($photos): ?>
                    <div class="partner-gallery mb-4">
                        <?php foreach ($photos as $index => $photo): ?>
                            <figure class="<?= $index === 0 ? 'partner-gallery-main' : '' ?>">
                                <?= responsive_image([
                                    'src' => stored_media_url((string) $photo['image_path']),
                                    'alt' => (string) ($photo['alt_text'] ?: $partner['business_name']),
                                    'widths' => [400, 800],
                                    'sizes' => '(min-width: 992px) 360px, 100vw',
                                    'width' => 800,
                                    'height' => 533,
                                    'loading' => 'lazy',
                                ]) ?>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <aside class="partner-sidebar">
                    <div class="detail-block-soft mb-3">
                        <h2 class="h5">Plan This Stay Into Your Tour</h2>
                        <p class="text-secondary">Tell us your route and travel dates. We can discuss whether this partner stay fits your private Sri Lanka itinerary.</p>
                        <a href="<?= e(base_url('contact?message=' . rawurlencode('I am interested in including ' . (string) $partner['business_name'] . ' in my Sri Lanka tour.'))) ?>" class="btn btn-primary w-100">Ask Us to Include This Stay</a>
                    </div>

                    <div class="partner-fit-card mb-3">
                        <span class="section-kicker">How We Use Partners</span>
                        <h2>Not a fixed package hotel</h2>
                        <p>Partner stays are suggested only when they fit your route, budget, dates, and travel style.</p>
                    </div>

                    <div class="detail-block-soft">
                        <h2 class="h6">Business Details</h2>
                        <dl class="partner-facts mb-0">
                            <dt>Type</dt>
                            <dd><?= e($typeLabel) ?></dd>
                            <?php if (!empty($partner['location'])): ?>
                                <dt>Location</dt>
                                <dd><?= e($partner['location']) ?></dd>
                            <?php endif; ?>
                            <?php if (!empty($partner['destination_name']) && !empty($partner['destination_slug'])): ?>
                                <dt>Nearest Destination</dt>
                                <dd><a href="<?= e(base_url('destinations/' . $partner['destination_slug'])) ?>"><?= e($partner['destination_name']) ?></a></dd>
                            <?php endif; ?>
                            <?php if (!empty($partner['google_maps_url'])): ?>
                                <dt>Map</dt>
                                <dd><a href="<?= e($partner['google_maps_url']) ?>" target="_blank" rel="noopener">Open Google Maps</a></dd>
                            <?php endif; ?>
                            <?php if (!empty($partner['website_url'])): ?>
                                <dt>Website</dt>
                                <dd><a href="<?= e($partner['website_url']) ?>" target="_blank" rel="noopener nofollow">Visit Website</a></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>

<?php
schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
