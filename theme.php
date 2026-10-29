<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/related_grid.php';

$themeDefaults = [
    'honeymoon-tours' => 'Honeymoon',
    'family-tours' => 'Family',
    'adventure-tours' => 'Adventure',
    'wellness-tours' => 'Wellness',
    'luxury-tours' => 'Luxury',
    'wildlife-tours' => 'Wildlife',
];
$slug = trim((string) get_input('slug', ''));
if (!array_key_exists($slug, $themeDefaults)) {
    require __DIR__ . '/404.php';
    exit;
}

$category = null;
if (db_table_exists('itinerary_categories') && db_column_exists('itinerary_categories', 'theme_slug')) {
    $where = ['theme_slug = :slug'];
    if (db_column_exists('itinerary_categories', 'is_theme')) {
        $where[] = 'COALESCE(is_theme, 0) = 1';
    }

    $category = fetch_one(
        'SELECT * FROM itinerary_categories WHERE ' . implode(' AND ', $where) . ' LIMIT 1',
        ['slug' => $slug]
    );
}

if (!$category) {
    $category = [
        'id' => 0,
        'name' => $themeDefaults[$slug],
        'hero_image' => '',
        'meta_description' => '',
        'theme_intro_html' => '',
    ];
}

$editorial = null;
if (db_table_exists('theme_page_editorial')) {
    $editorial = fetch_one(
        'SELECT * FROM theme_page_editorial WHERE theme_slug = :slug AND is_published = 1 LIMIT 1',
        ['slug' => $slug]
    );
}

record_page_view();

$itineraries = [];
if ((int) ($category['id'] ?? 0) > 0 && db_table_exists('itineraries') && db_column_exists('itineraries', 'category_id')) {
    $itineraries = fetch_all(
        "SELECT i.id, i.name, i.slug, i.days, i.excerpt, i.hero_image, i.og_image, i.meta_description,
            " . pricing_select_fragment('itineraries') . "
         FROM itineraries i
         WHERE i.is_published = 1 AND i.category_id = :cat
         ORDER BY i.is_featured DESC, i.sort_order ASC, i.days ASC, i.name ASC",
        ['cat' => (int) $category['id']]
    );
}

$themeTours = [];
if (db_table_exists('theme_tour_links') && db_table_exists('tours')) {
    $themeTours = fetch_all(
        "SELECT t.id, t.name, t.slug, t.excerpt, t.hero_image, t.og_image, t.meta_description,
                " . pricing_select_fragment('tours') . "
         FROM theme_tour_links l
         INNER JOIN tours t ON t.slug = l.tour_slug
         WHERE l.theme_slug = :slug AND l.is_active = 1 AND t.is_published = 1
         ORDER BY l.sort_order ASC, t.name ASC",
        ['slug' => $slug]
    );
}

$themeName = trim((string) ($category['name'] ?? $themeDefaults[$slug]));
$pageTitle = trim((string) ($editorial['page_title'] ?? '')) ?: ($themeName . ' Sri Lanka Tours');
$canonical = base_url($slug);
$editorialHero = trim((string) ($editorial['hero_image'] ?? ''));
$heroImage = $editorialHero !== ''
    ? stored_media_url($editorialHero)
    : (!empty($category['hero_image'])
        ? upload_url($category['hero_image'])
        : random_record_hero_image($itineraries, ['hero_image', 'og_image']));
if ($heroImage === '') {
    $heroImage = DEFAULT_OG_IMAGE;
}

$seoTitle = trim((string) ($editorial['meta_title'] ?? '')) ?: ($pageTitle . ' | ' . APP_NAME);
$seoDescription = trim((string) ($editorial['meta_description'] ?? ''))
    ?: (trim((string) ($category['meta_description'] ?? ''))
        ?: sprintf('Curated %s tours of Sri Lanka. Private transport and tailor-made pacing.', strtolower($themeName)));
$introHtml = render_public_content((string) ($editorial['intro_html'] ?? $category['theme_intro_html'] ?? ''));
$routeHtml = render_public_content((string) ($editorial['route_html'] ?? ''));
$planningHtml = render_public_content((string) ($editorial['planning_html'] ?? ''));
$seasonalHtml = render_public_content((string) ($editorial['seasonal_html'] ?? ''));
$reassuranceHtml = render_public_content((string) ($editorial['reassurance_html'] ?? ''));
$faqItems = decode_faq_json_items($editorial['faq_json'] ?? null);

$seoCanonical = $canonical;
$seoImage = $heroImage;
$seoType = 'website';
$bodyClass = 'page-theme';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Itineraries', 'url' => base_url('itineraries')],
    ['name' => $pageTitle, 'url' => $canonical],
];

$schemaItems = [];
foreach ($itineraries as $i) {
    $schemaItems[] = [
        'url' => base_url('itineraries/' . $i['slug']),
        'name' => (string) $i['name'],
        'image' => package_card_image($i, 'itinerary'),
    ];
}
foreach ($themeTours as $tour) {
    $schemaItems[] = [
        'url' => base_url('tours/' . $tour['slug']),
        'name' => (string) $tour['name'],
        'image' => package_card_image($tour, 'tour'),
    ];
}

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_item_list($pageTitle, $schemaItems),
    schema_service([
        'name' => $pageTitle,
        'meta_description' => $seoDescription,
        'excerpt' => $seoDescription,
        'og_image' => $editorialHero !== '' ? $editorialHero : (string) ($category['hero_image'] ?? ''),
    ], $canonical),
];
$faqSchema = schema_faq_from_items($faqItems);
if ($faqSchema) {
    $schemaGraph[] = $faqSchema;
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner" style="--hero-image: url('<?= e($heroImage) ?>');">
    <div class="container">
        <div class="hero-content">
            <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
            <span class="section-kicker" style="color:var(--accent-light);"><?= e($themeName) ?></span>
            <h1 class="hero-title"><?= e($pageTitle) ?></h1>
            <p class="lead mb-0"><?= e($seoDescription) ?></p>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if ($introHtml !== ''): ?>
            <div class="row justify-content-center mb-5">
                <div class="col-lg-9">
                    <div class="content-prose"><?= $introHtml ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($routeHtml !== '' || $planningHtml !== '' || $seasonalHtml !== '' || $reassuranceHtml !== ''): ?>
            <div class="row g-4 mb-5">
                <?php foreach ([
                    ['title' => 'Route ideas', 'html' => $routeHtml],
                    ['title' => 'Plan the details', 'html' => $planningHtml],
                    ['title' => 'Seasonal choices', 'html' => $seasonalHtml],
                    ['title' => 'Book with clear expectations', 'html' => $reassuranceHtml],
                ] as $section): ?>
                    <?php if ($section['html'] !== ''): ?>
                        <div class="col-md-6">
                            <article class="card card-soft h-100">
                                <div class="card-body">
                                    <h2 class="h5 mb-3"><?= e($section['title']) ?></h2>
                                    <div class="content-prose small"><?= $section['html'] ?></div>
                                </div>
                            </article>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($themeTours): ?>
            <?= render_related_grid('tours', $themeTours, [
                'heading' => 'Private tour options',
                'cols' => 3,
                'card_class' => 'visual-card',
                'image_widths' => [400, 800],
                'image_sizes' => '(min-width:992px) 360px, 100vw',
            ]) ?>
        <?php endif; ?>

        <?php if (!$itineraries && !$themeTours): ?>
            <div class="text-center py-4">
                <p class="text-secondary mb-3">No curated <?= e(strtolower($themeName)) ?> routes are currently published.</p>
                <a href="<?= e(base_url('itineraries')) ?>" class="btn btn-outline-dark me-2 mb-2">Browse All Itineraries</a>
                <a href="<?= e(base_url('contact')) ?>" class="btn btn-success mb-2">Plan a Custom Trip</a>
            </div>
        <?php elseif ($itineraries): ?>
            <?= render_related_grid('itineraries', $itineraries, [
                'heading' => 'Itinerary ideas',
                'cols' => 3,
                'card_class' => 'visual-card',
                'image_widths' => [400, 800],
                'image_sizes' => '(min-width:992px) 360px, 100vw',
            ]) ?>
        <?php endif; ?>
    </div>
</section>

<?php
if ($faqItems) {
    $faqHeading = 'Questions about ' . strtolower($themeName) . ' tours';
    $faqAccordionId = 'themeFaqAccordion';
    $faqPrefix = 'theme-faq';
    $faqCtaTitle = trim((string) ($editorial['cta_title'] ?? '')) ?: 'Plan a route that fits your group';
    $faqCtaText = trim((string) ($editorial['cta_text'] ?? '')) ?: 'Share your dates and priorities. We will compare the practical private options.';
    $faqCtaHref = base_url('contact');
    require __DIR__ . '/includes/faq_section.php';
}

$ctaTitle = trim((string) ($editorial['cta_title'] ?? '')) ?: ('Plan Your ' . $themeName . ' Sri Lanka Trip');
$ctaText = trim((string) ($editorial['cta_text'] ?? '')) ?: ('Tell us your dates and travel style. We will tailor a private ' . strtolower($themeName) . ' itinerary.');
schema_output($schemaGraph);
require __DIR__ . '/includes/cta.php';
require __DIR__ . '/includes/footer.php';
