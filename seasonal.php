<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

ensure_seasonal_guides_schema();

$guides = fetch_all_seasonal_guides(true);
$heroImage = DEFAULT_OG_IMAGE;
foreach ($guides as $g) {
    $img = trim((string) ($g['hero_image'] ?? ''));
    if ($img !== '') {
        $heroImage = stored_media_url($img, DEFAULT_OG_IMAGE);
        break;
    }
}

$seoTitle = 'When to Visit Sri Lanka (Month-by-Month Guide) | ' . APP_NAME;
$seoDescription = 'Month-by-month Sri Lanka travel guide: weather notes, best regions, and recommended itineraries and destinations for every season.';
$seoCanonical = base_url('seasonal');
$seoImage = $heroImage;
$seoType = 'website';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'When to Visit', 'url' => $seoCanonical],
];

$schemaItems = [];
foreach ($guides as $g) {
    $m = strtolower(trim((string) ($g['month_key'] ?? '')));
    if ($m === '') {
        continue;
    }
    $schemaItems[] = [
        'name' => trim((string) ($g['title'] ?? 'Sri Lanka in ' . ucfirst($m))),
        'url' => base_url('sri-lanka-in-' . $m),
    ];
}

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_item_list('Sri Lanka Monthly Guides', $schemaItems),
];

$bodyClass = 'page-seasonal-hub';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media">
    <?= responsive_image([
        'src'           => $heroImage,
        'alt'           => 'When to visit Sri Lanka',
        'widths'        => [800, 1200, 1600, 2000],
        'sizes'         => '100vw',
        'width'         => 2000,
        'height'        => 1125,
        'loading'       => 'eager',
        'fetchpriority' => 'high',
        'class'         => 'page-hero-bg-img',
        'picture_class' => 'page-hero-media',
    ]) ?>
    <div class="container">
        <div class="hero-content">
            <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
            <span class="section-kicker" style="color:var(--accent-light);">Seasonal Planning</span>
            <h1 class="hero-title">When to Visit <em>Sri Lanka</em></h1>
            <p class="lead mb-0">
                Month-by-month guides that explain what to expect, where to go, and which routes work best.
            </p>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-3">
            <?php foreach ($guides as $g): ?>
                <?php
                $month = strtolower(trim((string) ($g['month_key'] ?? '')));
                if ($month === '') continue;
                $title = trim((string) ($g['title'] ?? 'Sri Lanka in ' . ucfirst($month)));
                $intro = trim((string) ($g['intro'] ?? ''));
                $intro = excerpt_words($intro, 22);
                $img = trim((string) ($g['hero_image'] ?? ''));
                $imgUrl = $img !== '' ? stored_media_url($img) : '';
                ?>
                <div class="col-md-6 col-xl-4">
                    <a href="<?= e(base_url('sri-lanka-in-' . $month)) ?>" class="card h-100 text-decoration-none" style="border-radius:var(--radius-lg);overflow:hidden;">
                        <?php if ($imgUrl !== ''): ?>
                            <div class="card-thumb">
                                <?= responsive_image([
                                    'src'     => $imgUrl,
                                    'alt'     => $title,
                                    'widths'  => [400, 800],
                                    'sizes'   => '(min-width: 1200px) 380px, (min-width: 768px) 50vw, 100vw',
                                    'width'   => 800,
                                    'height'  => 500,
                                    'class'   => 'card-img-top',
                                    'loading' => 'lazy',
                                ]) ?>
                                <span class="card-badge"><?= e(ucfirst($month)) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="card-title h5 mb-2" style="color:var(--text);"><?= e($title) ?></div>
                            <?php if ($intro !== ''): ?>
                                <div class="card-excerpt"><?= e($intro) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0">
                            <span class="btn btn-outline-dark btn-sm">View Guide</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
$ctaTitle = 'Plan Your Trip by Season';
$ctaText = 'Tell us your travel month and priorities. We will recommend the best regions, route flow, and private transport plan.';
require __DIR__ . '/includes/cta.php';

schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
