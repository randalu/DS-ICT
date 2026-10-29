<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

$dayOptions = trip_planner_days_options();
$themeOptions = trip_planner_theme_options();

$days = (int) get_input('d', 0);
$days = $days > 0 ? max(1, min(30, $days)) : 0;

$theme = strtolower(trim((string) get_input('theme', '')));
$theme = preg_replace('/[^a-z0-9-]+/', '', $theme) ?: '';

$where = ['i.is_published = 1'];
$params = [];
$join = '';

if ($days > 0) {
    $where[] = 'i.days = :days';
    $params['days'] = $days;
}

if ($theme !== '' && db_table_exists('itinerary_categories') && db_column_exists('itineraries', 'category_id')) {
    $join = 'LEFT JOIN itinerary_categories c ON c.id = i.category_id';
    $where[] = '(c.theme_slug = :theme OR c.slug = :theme)';
    $params['theme'] = $theme;
}

$itineraries = fetch_all(
    "SELECT i.id, i.name, i.slug, i.days, i.excerpt, i.hero_image, i.og_image, i.meta_description,
            " . pricing_select_fragment('itineraries') . "
     FROM itineraries i
     {$join}
     WHERE " . implode(' AND ', $where) . "
     ORDER BY i.is_featured DESC, i.sort_order ASC, i.days ASC, i.name ASC
     LIMIT 24",
    $params
);

$seoTitle = trip_planner_meta_title();
$seoDescription = trip_planner_meta_description();
$seoCanonical = base_url('plan');
$seoImage = DEFAULT_OG_IMAGE;
$seoType = 'website';
$seoRobots = 'noindex,follow,max-image-preview:large';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Trip Planner', 'url' => $seoCanonical],
];

$schemaItems = [];
foreach ($itineraries as $i) {
    $schemaItems[] = [
        'url' => base_url('itineraries/' . (string) ($i['slug'] ?? '')),
        'name' => (string) ($i['name'] ?? ''),
        'image' => !empty($i['og_image']) ? upload_url((string) $i['og_image']) : (!empty($i['hero_image']) ? upload_url((string) $i['hero_image']) : DEFAULT_OG_IMAGE),
    ];
}

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
];
if ($schemaItems) {
    $schemaGraph[] = schema_item_list('Planner Results', $schemaItems);
}

$bodyClass = 'page-plan';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media page-hero-plan">
    <?= responsive_image([
        'src' => DEFAULT_OG_IMAGE,
        'alt' => 'Trip Planner',
        'widths' => [800, 1280, 1920],
        'sizes' => '100vw',
        'width' => 1920,
        'height' => 1080,
        'loading' => 'eager',
        'fetchpriority' => 'high',
        'class' => 'page-hero-bg-img',
        'picture_class' => 'page-hero-media',
    ]) ?>
    <div class="container">
        <div class="hero-content">
            <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
            <span class="section-kicker" style="color:var(--accent-light);">Trip Planner</span>
            <h1 class="hero-title">Trip <em>Planner</em></h1>
            <div class="hero-summary-box">
                <p class="lead mb-0"><?= e(trip_planner_intro_text()) ?></p>
            </div>
        </div>
    </div>
</section>

<section class="py-4 border-bottom" style="background:var(--surface);">
    <div class="container">
        <form class="row g-3 align-items-end" data-planner-form>
            <div class="col-6 col-md-3 form-field">
                <label for="planner-days" class="form-label">Days</label>
                <select id="planner-days" name="d" class="form-select form-select">
                    <option value="">Any</option>
                    <?php foreach ($dayOptions as $opt): ?>
                        <option value="<?= (int) $opt ?>" <?= $days === (int) $opt ? 'selected' : '' ?>><?= (int) $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-4 form-field">
                <label for="planner-theme" class="form-label">Theme</label>
                <select id="planner-theme" name="theme" class="form-select form-select">
                    <option value="">Any</option>
                    <?php foreach ($themeOptions as $opt): ?>
                        <?php
                        $optSlug = (string) ($opt['slug'] ?? '');
                        $optLabel = (string) ($opt['label'] ?? '');
                        if ($optSlug === '' || $optLabel === '') continue;
                        ?>
                        <option value="<?= e($optSlug) ?>" <?= $theme === $optSlug ? 'selected' : '' ?>><?= e($optLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-5 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Update Results</button>
                <a class="btn btn-outline-dark" href="<?= e(base_url('plan')) ?>">Reset</a>
            </div>
        </form>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a class="btn btn-outline-dark btn-sm" href="<?= e(base_url('seasonal')) ?>">When to Visit (Monthly Guides)</a>
            <a class="btn btn-outline-dark btn-sm" href="<?= e(base_url('itineraries')) ?>">Browse Itineraries</a>
        </div>
        <?php if (!$itineraries): ?>
            <div class="alert alert-light border">
                No itineraries match these filters yet. Try resetting, or send an inquiry and we will tailor a route for your dates.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($itineraries as $itinerary): ?>
                    <?php
                    $img = !empty($itinerary['hero_image'])
                        ? stored_media_url((string) $itinerary['hero_image'])
                        : (!empty($itinerary['og_image']) ? stored_media_url((string) $itinerary['og_image']) : DEFAULT_OG_IMAGE);
                    $price = format_price_range($itinerary, true);
                    ?>
                    <div class="col-md-6 col-xl-3">
                        <div class="card h-100" style="border-radius:var(--radius-lg);overflow:hidden;">
                            <div class="card-thumb">
                                <?= responsive_image([
                                    'src'     => $img,
                                    'alt'     => (string) $itinerary['name'],
                                    'widths'  => [400, 800],
                                    'sizes'   => '(min-width: 1200px) 300px, (min-width: 768px) 50vw, 100vw',
                                    'width'   => 800,
                                    'height'  => 500,
                                    'class'   => 'card-img-top',
                                    'loading' => 'lazy',
                                ]) ?>
                                <span class="card-badge"><?= (int) $itinerary['days'] ?> Days</span>
                            </div>
                            <div class="card-body">
                                <h2 class="card-title h5">
                                    <a href="<?= e(base_url('itineraries/' . $itinerary['slug'])) ?>" class="text-decoration-none" style="color:inherit;"><?= e($itinerary['name']) ?></a>
                                </h2>
                                <p class="card-excerpt">
                                    <?= e(excerpt_words((string) ($itinerary['excerpt'] ?: $itinerary['meta_description'] ?: ''), 18)) ?>
                                </p>
                            </div>
                            <div class="card-footer d-flex gap-2 flex-wrap">
                                <span class="card-price-label" style="width:100%;"><?= $price !== '' ? e($price) : 'Request quote' ?></span>
                                <a href="<?= e(base_url('itineraries/' . $itinerary['slug'])) ?>" class="btn btn-outline-dark btn-sm">View</a>
                                <a href="<?= e(base_url('contact?itinerary_id=' . (int) $itinerary['id'] . '&from=plan&d=' . (int) $days . '&theme=' . rawurlencode($theme))) ?>" class="btn btn-primary btn-sm">Inquire</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script src="<?= e(base_url('assets/js/trip-planner.js')) ?>" defer></script>

<?php
schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
