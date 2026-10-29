<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/related_fetch.php';
require_once __DIR__ . '/includes/related_grid.php';
require_once __DIR__ . '/includes/responsive_image.php';

$month = strtolower(trim((string) get_input('month', '')));
$allowed = [
    'january','february','march','april','may','june',
    'july','august','september','october','november','december',
];

if ($month === '' || !in_array($month, $allowed, true)) {
    require __DIR__ . '/404.php';
    exit;
}

ensure_seasonal_guides_schema();
$guide = fetch_seasonal_guide($month, true);

// Back-compat: if DB row is missing, fall back to file data (seed source).
/** @var array<string, array{title?:string,intro?:string,highlights?:array,best_regions?:array,avoid_regions?:array}> $monthData */
$monthData = is_file(__DIR__ . '/data/month_weather.php') ? (require __DIR__ . '/data/month_weather.php') : [];
$fallbackInfo = $monthData[$month] ?? [];

$destinations = fetch_destinations_for_month($month, 12);
$itineraries = fetch_itineraries_for_month($month, 12);

// Hero image: pick from seasonal results; fall back to default OG.
$heroOverride = trim((string) ($guide['hero_image'] ?? ''));
$heroUrl = $heroOverride !== ''
    ? stored_media_url($heroOverride, DEFAULT_OG_IMAGE)
    : random_record_hero_image(
        array_merge($destinations, $itineraries),
        ['cover_image', 'hero_image', 'og_image'],
        DEFAULT_OG_IMAGE
    );

$title = trim((string) (($guide['title'] ?? '') !== '' ? $guide['title'] : ($fallbackInfo['title'] ?? ('Sri Lanka in ' . ucfirst($month)))));
$intro = trim((string) (($guide['intro'] ?? '') !== '' ? $guide['intro'] : ($fallbackInfo['intro'] ?? 'Plan the best routes and regions for this month in Sri Lanka.')));
$highlights = $guide ? decode_string_list_json($guide['highlights_json'] ?? null) : (isset($fallbackInfo['highlights']) && is_array($fallbackInfo['highlights']) ? $fallbackInfo['highlights'] : []);
$bestRegions = $guide ? decode_string_list_json($guide['best_regions_json'] ?? null) : (isset($fallbackInfo['best_regions']) && is_array($fallbackInfo['best_regions']) ? $fallbackInfo['best_regions'] : []);
$avoidRegions = $guide ? decode_string_list_json($guide['avoid_regions_json'] ?? null) : (isset($fallbackInfo['avoid_regions']) && is_array($fallbackInfo['avoid_regions']) ? $fallbackInfo['avoid_regions'] : []);

$slug = 'sri-lanka-in-' . $month;
$metaTitle = trim((string) ($guide['meta_title'] ?? ''));
$metaDescription = trim((string) ($guide['meta_description'] ?? ''));
$seoTitle = ($metaTitle !== '' ? $metaTitle : ($title . ' | ' . APP_NAME));
$seoDescription = ($metaDescription !== '' ? $metaDescription : $intro);
$seoCanonical = base_url($slug);
$seoImage = $heroUrl ?: DEFAULT_OG_IMAGE;
$seoType = 'website';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => $title, 'url' => $seoCanonical],
];

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    [
        '@type' => 'WebPage',
        'name' => $title,
        'url' => $seoCanonical,
        'isPartOf' => ['@id' => base_url() . '#website'],
    ],
];

if ($destinations) {
    $schemaGraph[] = schema_item_list($title . ' Destinations', array_map(static function (array $d): array {
        return [
            'name' => (string) ($d['name'] ?? ''),
            'url' => base_url('destinations/' . (string) ($d['slug'] ?? '')),
        ];
    }, $destinations));
}

if ($itineraries) {
    $schemaGraph[] = schema_item_list($title . ' Itineraries', array_map(static function (array $i): array {
        $days = isset($i['days']) ? (int) $i['days'] : 0;
        $name = (string) ($i['name'] ?? '');
        if ($days > 0) {
            $name .= ' (' . $days . ' days)';
        }
        return [
            'name' => $name,
            'url' => base_url('itineraries/' . (string) ($i['slug'] ?? '')),
        ];
    }, $itineraries));
}

$bodyClass = 'page-seasonal';
$ctaWhatsappText = 'Hello Island Culture Travels, I am planning a Sri Lanka trip in ' . ucfirst($month) . '.';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media">
    <?= responsive_image([
        'src'           => $heroUrl,
        'alt'           => $title,
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
            <div class="section-kicker mb-2" style="color:var(--accent-light);">Seasonal Guide</div>
            <h1 class="display-6 fw-bold hero-title mb-2"><?= e($title) ?></h1>
            <p class="lead mb-0" style="color:rgba(255,255,255,0.82);"><?= e($intro) ?></p>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <?php if ($highlights): ?>
                    <div class="card mb-4" style="border-radius:var(--radius-lg);">
                        <div class="card-body">
                            <h2 class="h5 mb-3" style="font-family:var(--font-display);">Highlights in <?= e(ucfirst($month)) ?></h2>
                            <ul class="mb-0 text-secondary">
                                <?php foreach ($highlights as $h): ?>
                                    <?php $h = trim((string) $h); if ($h === '') continue; ?>
                                    <li><?= e($h) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($destinations): ?>
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <h2 class="h4 mb-0">Best Destinations</h2>
                        <a class="small text-decoration-none" href="<?= e(base_url('destinations')) ?>">All destinations</a>
                    </div>
                    <?= render_related_grid('destinations', $destinations, ['columns' => 3]) ?>
                <?php else: ?>
                    <p class="text-secondary">No destinations have been tagged for this month yet. Add month selections in the destination admin form.</p>
                <?php endif; ?>

                <?php if ($itineraries): ?>
                    <div class="d-flex justify-content-between align-items-baseline mt-5 mb-2">
                        <h2 class="h4 mb-0">Recommended Itineraries</h2>
                        <a class="small text-decoration-none" href="<?= e(base_url('itineraries')) ?>">All itineraries</a>
                    </div>
                    <?= render_related_grid('itineraries', $itineraries, ['columns' => 3]) ?>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="floating-card mb-4">
                    <h2 class="h6 mb-3">Plan Tools</h2>
                    <div class="d-grid gap-2">
                        <a class="btn btn-success btn-sm" href="<?= e(base_url('plan')) ?>">Use Trip Planner</a>
                        <a class="btn btn-outline-dark btn-sm" href="<?= e(base_url('seasonal')) ?>">All Months</a>
                        <a class="btn btn-outline-dark btn-sm" href="<?= e(base_url('contact')) ?>">Request a Custom Plan</a>
                    </div>
                </div>
                <div class="floating-card mb-4">
                    <h2 class="h6 mb-3">Best Regions</h2>
                    <?php if ($bestRegions): ?>
                        <ul class="mb-0 text-secondary small">
                            <?php foreach ($bestRegions as $r): ?>
                                <?php $r = trim((string) $r); if ($r === '') continue; ?>
                                <li><?= e(ucwords(str_replace('-', ' ', $r))) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-secondary small mb-0">This month is flexible across many regions.</p>
                    <?php endif; ?>
                </div>

                <div class="floating-card">
                    <h2 class="h6 mb-3">Regions to Avoid</h2>
                    <?php if ($avoidRegions): ?>
                        <ul class="mb-0 text-secondary small">
                            <?php foreach ($avoidRegions as $r): ?>
                                <?php $r = trim((string) $r); if ($r === '') continue; ?>
                                <li><?= e(ucwords(str_replace('-', ' ', $r))) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-secondary small mb-0">No major regional weather issues expected.</p>
                    <?php endif; ?>
                </div>

                <div class="mt-4 d-flex gap-2 flex-wrap">
                    <?php
                    $monthIndex = array_search($month, $allowed, true);
                    $prev = $monthIndex !== false ? $allowed[($monthIndex + 11) % 12] : null;
                    $next = $monthIndex !== false ? $allowed[($monthIndex + 1) % 12] : null;
                    ?>
                    <?php if ($prev): ?>
                        <a class="btn btn-outline-dark btn-sm" href="<?= e(base_url('sri-lanka-in-' . $prev)) ?>">&larr; <?= e(ucfirst($prev)) ?></a>
                    <?php endif; ?>
                    <?php if ($next): ?>
                        <a class="btn btn-outline-dark btn-sm" href="<?= e(base_url('sri-lanka-in-' . $next)) ?>"><?= e(ucfirst($next)) ?> &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
schema_output($schemaGraph);
require __DIR__ . '/includes/cta.php';
require __DIR__ . '/includes/footer.php';
