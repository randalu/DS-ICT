<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

$countries = db_table_exists('country_pages')
    ? fetch_all("
        SELECT id, country_name, slug, excerpt, hero_image, og_image, meta_description
        FROM country_pages
        WHERE is_published = 1
        ORDER BY sort_order ASC, country_name ASC
    ")
    : [];
$heroImage = DEFAULT_OG_IMAGE;
if ($countries) {
    $heroImage = stored_media_url(
        (string) ($countries[0]['og_image'] ?: $countries[0]['hero_image']),
        DEFAULT_OG_IMAGE
    );
}

$seoTitle = 'Travelling to Sri Lanka from Your Country | Island Culture Travels';
$seoDescription = 'Country-specific planning pages for Sri Lanka private tours, including route ideas, travel notes, and recommended itineraries.';
$seoCanonical = base_url('from');
$seoImage = $heroImage;
$seoImageWidth = 1200;
$seoImageHeight = 675;
$seoType = 'website';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'From Country', 'url' => $seoCanonical],
];

$schemaItems = [];
foreach ($countries as $c) {
    $schemaItems[] = [
        'url'   => base_url('from/' . $c['slug']),
        'name'  => (string) $c['country_name'],
        'image' => !empty($c['og_image']) ? upload_url($c['og_image']) : (!empty($c['hero_image']) ? upload_url($c['hero_image']) : DEFAULT_OG_IMAGE),
    ];
}

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_item_list('Sri Lanka Tours From Country', $schemaItems),
];

$bodyClass = 'page-from-countries';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner" style="--hero-image: url('<?= e($heroImage) ?>');">
    <div class="container">
        <div class="hero-content">
            <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
            <span class="section-kicker" style="color:var(--accent-light);">Travel Planning</span>
            <h1 class="hero-title">Sri Lanka Tours <em>From Your Country</em></h1>
            <p class="lead mb-0">
                Country-specific travel pages with practical guidance for planning your private Sri Lanka journey.
            </p>
            <?php
            $trustAudienceLabel = 'Private and Family Tours';
            $trustExtraLabel = 'From-country route guidance';
            require __DIR__ . '/includes/trust_signals.php';
            ?>

            <?php
            $featuredImageUrl = $heroImage;
            $featuredImageAlt = 'Sri Lanka tours from your country with private driver and vehicle';
            require __DIR__ . '/includes/featured_image.php';
            ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if (!$countries): ?>
            <p class="text-secondary text-center py-4 mb-0">No published country pages available yet.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($countries as $country): ?>
                    <?php
                    $img = !empty($country['hero_image'])
                        ? stored_media_url($country['hero_image'])
                        : (!empty($country['og_image']) ? stored_media_url($country['og_image']) : DEFAULT_OG_IMAGE);
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card visual-card h-100">
                            <?= responsive_image([
                                'src' => $img,
                                'alt' => $country['country_name'],
                                'widths' => [400, 800],
                                'sizes' => '(min-width: 992px) 360px, 100vw',
                                'width' => 800,
                                'height' => 533,
                                'class' => 'card-img-top',
                                'loading' => 'lazy',
                            ]) ?>
                            <div class="card-body">
                                <div class="compact-meta">Travelling from</div>
                                <h2 class="h5 card-title">
                                    <a href="<?= e(base_url('from/' . $country['slug'])) ?>"><?= e($country['country_name']) ?></a>
                                </h2>
                                <p class="card-text text-secondary">
                                    <?= e(excerpt_words((string) ($country['excerpt'] ?: $country['meta_description'] ?: ''), 20)) ?>
                                </p>
                            </div>
                            <div class="card-footer">
                                <a href="<?= e(base_url('from/' . $country['slug'])) ?>" class="btn btn-outline-dark btn-sm">Open Guide</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
$ctaTitle = 'Plan Your Sri Lanka Tour';
$ctaText = 'Tell us where you are travelling from, your dates, and your preferred route style. We will help you plan the right private journey.';
schema_output($schemaGraph);
require __DIR__ . '/includes/cta.php';
require __DIR__ . '/includes/footer.php';
