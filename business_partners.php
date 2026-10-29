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

$categorySlug = trim((string) get_input('category', ''));
$category = $categorySlug !== ''
    ? find_business_partner_category_by_slug($categorySlug, true)
    : null;

if ($categorySlug !== '' && !$category) {
    require __DIR__ . '/404.php';
    exit;
}

$categories = fetch_business_partner_categories(true);
$partners = fetch_published_business_partners($category ? (int) $category['id'] : null, 100);

$pageTitle = $category
    ? ((string) $category['name'] . ' Partners')
    : 'Business Partners';
$pageUrl = $category
    ? business_partner_category_url($category)
    : base_url('business-partners');
$pageDescription = $category
    ? (trim((string) ($category['description'] ?? '')) ?: 'Official accommodation partners working with Island Culture Travels for private Sri Lanka tours.')
    : 'Hotels, accommodations, and tourism businesses that partner with Island Culture Travels after direct business review and mutual agreement.';

$seoTitle = $pageTitle . ' | ' . APP_NAME;
$seoDescription = excerpt_words($pageDescription, 28);
$seoCanonical = $pageUrl;
$seoImage = DEFAULT_OG_IMAGE;
$seoType = 'website';
$bodyClass = 'page-business-partners';
$heroImage = DEFAULT_OG_IMAGE;

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Business Partners', 'url' => base_url('business-partners')],
];
if ($category) {
    $breadcrumbs[] = ['name' => (string) $category['name'], 'url' => $pageUrl];
}

$schemaItems = [];
foreach ($partners as $partner) {
    $schemaItems[] = [
        'url' => business_partner_url($partner),
        'name' => (string) $partner['business_name'],
        'image' => !empty($partner['og_image'])
            ? stored_media_url((string) $partner['og_image'])
            : (!empty($partner['hero_image']) ? stored_media_url((string) $partner['hero_image']) : DEFAULT_OG_IMAGE),
    ];
}

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    [
        '@type' => 'CollectionPage',
        'name' => $pageTitle,
        'url' => $pageUrl,
        'description' => $pageDescription,
        'isPartOf' => ['@id' => base_url() . '#website'],
    ],
];
if ($schemaItems) {
    $schemaGraph[] = schema_item_list($pageTitle, $schemaItems);
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media page-hero-partners">
    <?= responsive_image([
        'src' => $heroImage,
        'alt' => 'Sri Lanka accommodation and tourism business partners',
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
            <span class="section-kicker" style="color:var(--accent-light);">B2B Partnerships</span>
            <h1 class="hero-title"><?= e($pageTitle) ?></h1>
            <div class="hero-summary-box">
                <p class="lead mb-0"><?= e($pageDescription) ?></p>
            </div>
            <div class="hero-meta">
                <span class="badge-soft">Business-submitted applications</span>
                <span class="badge-soft">Private review before publishing</span>
                <span class="badge-soft">Tour-fit accommodation partners</span>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="partner-intro-panel mb-4">
            <div>
                <span class="section-kicker">Partner With Us</span>
                <h2 class="section-heading mb-2">For Sri Lanka tourism businesses interested in working with us</h2>
                <p class="text-secondary mb-0">Businesses submit their own details. We review, contact, agree terms privately, and only then publish official partners.</p>
            </div>
            <a href="<?= e(base_url('business-partners/apply')) ?>" class="btn btn-success">Apply as a Partner</a>
        </div>

        <div class="partner-process-grid mb-5">
            <div class="partner-process-card">
                <span class="partner-process-step">01</span>
                <h3>Business applies</h3>
                <p>The owner or manager submits their own accommodation or tourism business for review.</p>
            </div>
            <div class="partner-process-card">
                <span class="partner-process-step">02</span>
                <h3>Private discussion</h3>
                <p>We contact the business, check tour fit, quality, location, and practical guest experience.</p>
            </div>
            <div class="partner-process-card">
                <span class="partner-process-step">03</span>
                <h3>Published after agreement</h3>
                <p>Only mutually agreed partners appear publicly, and travelers still book through their custom tour plan.</p>
            </div>
        </div>

        <?php if (!$category && $categories): ?>
            <div class="row g-3 mb-5">
                <?php foreach ($categories as $item): ?>
                    <div class="col-md-6 col-lg-4">
                        <a class="partner-category-card" href="<?= e(business_partner_category_url($item)) ?>">
                            <span class="partner-category-title"><?= e($item['name']) ?></span>
                            <span class="partner-category-text"><?= e(excerpt_words((string) ($item['description'] ?? ''), 18)) ?></span>
                            <span class="partner-card-cta">View category</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="section-header-row mb-4">
            <div>
                <span class="section-kicker">Official Partners</span>
                <h2 class="section-heading"><?= $category ? e((string) $category['name']) : 'Published Partner Businesses' ?></h2>
            </div>
        </div>

        <?php if ($partners): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($partners as $partner): ?>
                    <?php
                    $image = !empty($partner['hero_image'])
                        ? stored_media_url((string) $partner['hero_image'])
                        : (!empty($partner['og_image']) ? stored_media_url((string) $partner['og_image']) : DEFAULT_OG_IMAGE);
                    $typeLabel = business_partner_types()[(string) ($partner['business_type'] ?? '')] ?? 'Accommodation';
                    ?>
                    <div class="col">
                        <a class="partner-card" href="<?= e(business_partner_url($partner)) ?>">
                            <span class="partner-card-image"><?= responsive_image([
                                'src' => $image,
                                'alt' => $partner['business_name'],
                                'widths' => [400, 800],
                                'sizes' => '(min-width: 992px) 360px, 100vw',
                                'width' => 800,
                                'height' => 533,
                                'loading' => 'lazy',
                            ]) ?></span>
                            <span class="partner-card-body">
                                <span class="partner-card-meta">
                                    <?= e($typeLabel) ?><?php if (!empty($partner['location'])): ?><span aria-hidden="true"> &middot; </span><?= e((string) $partner['location']) ?><?php endif; ?>
                                </span>
                                <span class="partner-card-title"><?= e($partner['business_name']) ?></span>
                                <span class="partner-card-text"><?= e(excerpt_words((string) ($partner['short_description'] ?? ''), 20)) ?></span>
                                <span class="partner-card-cta">Ask us to include this stay</span>
                            </span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="partner-empty-state">
                <div>
                    <span class="section-kicker">No Public Listings Yet</span>
                    <h3>No official partners published yet</h3>
                    <p>Applications are reviewed privately first. Published partners will appear here only after mutual agreement, so this page stays curated rather than becoming an open directory.</p>
                </div>
                <div class="partner-empty-actions">
                    <a href="<?= e(base_url('business-partners/apply')) ?>" class="btn btn-primary">Apply as a Partner</a>
                    <a href="<?= e(base_url('contact')) ?>" class="btn btn-outline-dark">Contact Us First</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
