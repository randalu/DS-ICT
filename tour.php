<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/structured_sections.php';
require_once __DIR__ . '/includes/responsive_image.php';
require_once __DIR__ . '/includes/tour_coverage.php';
require_once __DIR__ . '/includes/tour_program.php';

$slug = trim((string) get_input('slug', ''));
if ($slug === '') {
    require __DIR__ . '/404.php';
    exit;
}

$tour = find_published_tour_by_slug($slug);
if ($tour) {
    record_page_view();

    $tourName = trim((string) ($tour['name'] ?? 'Tour'));
    $tourExcerpt = trim((string) ($tour['excerpt'] ?? ''));
    $pageUrl = !empty($tour['canonical_url'])
        ? (string) $tour['canonical_url']
        : base_url('tours/' . $tour['slug']);
    $heroImage = !empty($tour['hero_image'])
        ? stored_media_url((string) $tour['hero_image'])
        : (!empty($tour['og_image']) ? stored_media_url((string) $tour['og_image']) : DEFAULT_OG_IMAGE);
    if (empty($tour['hero_image']) || is_placeholder_media_url((string)$tour['hero_image'])) {
        $selectedCover = trim((string)($tour['cover_image'] ?? ''));
        if ($selectedCover !== '' && !is_placeholder_media_url($selectedCover)) { $heroImage = stored_media_url($selectedCover); }
    }
    $priceLabel = format_price_range($tour);

    // ── Includes / excludes (mixed format: HTML or newline text) ────────
    $renderItemList = static function (string $raw): string {
        $raw = trim($raw);
        if ($raw === '') return '';
        if ($raw !== strip_tags($raw)) {
            return render_public_content($raw);
        }
        $items = array_values(array_filter(array_map('trim', explode("\n", $raw))));
        if (!$items) return '';
        $html = '<ul class="mb-0">';
        foreach ($items as $item) {
            $html .= '<li>' . e($item) . '</li>';
        }
        return $html . '</ul>';
    };
    $includesHtml = !empty($tour['includes']) ? $renderItemList((string) $tour['includes']) : '';
    $excludesHtml = !empty($tour['excludes']) ? $renderItemList((string) $tour['excludes']) : '';

    // ── Itinerary text (compact day-by-day, distinct from sample_itinerary) ──
    $itineraryText = trim((string) ($tour['itinerary'] ?? ''));

    // ── Best time block ────────────────────────────────────────────────
    $bestTimeBlock = '';
    if (!empty($tour['best_time_json'])) {
        $btData = json_decode((string) $tour['best_time_json'], true);
        if (is_array($btData)) {
            if (array_is_list($btData) && !empty($btData[0]['month'])) {
                // Day-tour format: [{"month":"Jan","rating":"great"}, ...]
                $shortMap = ['jan'=>'january','feb'=>'february','mar'=>'march','apr'=>'april','may'=>'may','jun'=>'june',
                             'jul'=>'july','aug'=>'august','sep'=>'september','oct'=>'october','nov'=>'november','dec'=>'december'];
                $normalized = [];
                foreach ($btData as $item) {
                    $m = strtolower(trim((string) ($item['month'] ?? '')));
                    if (isset($shortMap[$m])) {
                        $normalized[] = $shortMap[$m];
                    } elseif (in_array($m, $shortMap, true)) {
                        $normalized[] = $m;
                    }
                }
                if ($normalized) {
                    $bestTimeBlock = render_best_time_block(['months' => $normalized], ['title' => 'Best Time']);
                }
            } elseif (!empty($btData)) {
                $bestTimeBlock = render_best_time_block($btData, ['title' => 'Best Time']);
            }
        }
    }

    // ── CTA text ───────────────────────────────────────────────────────
    $tourCtaText = trim((string) ($tour['cta_text'] ?? ''));
    $tourCtaTitle = trim((string) ($tour['cta_title'] ?? '')) ?: 'Plan This Tour';

    $relatedDestinations = [];
    if (db_table_exists('destination_tour') && db_table_exists('destinations')) {
        $relatedDestinations = fetch_all(
            "SELECT d.id, d.name, d.slug, d.excerpt, d.cover_image, d.hero_image, d.province, d.destination_type
             FROM destination_tour dt
             INNER JOIN destinations d ON d.id = dt.destination_id
             WHERE dt.tour_id = :tour_id
               AND d.is_published = 1
             ORDER BY d.sort_order ASC, d.name ASC
             LIMIT 6",
            ['tour_id' => (int) $tour['id']]
        );
    }

    $relatedTours = [];
    if (db_table_exists('tours')) {
        $relatedTours = fetch_all(
            "SELECT id, name, slug, excerpt, hero_image, og_image, " . pricing_select_fragment('tours') . "
             FROM tours
             WHERE is_published = 1 AND id != :tour_id
             ORDER BY is_featured DESC, sort_order ASC, name ASC
             LIMIT 3",
            ['tour_id' => (int) $tour['id']]
        );
    }

    $faqItems = decode_faq_json_items($tour['faq_json'] ?? null);

    $seoTitle = trim((string) ($tour['meta_title'] ?? '')) ?: $tourName . ' | ' . APP_NAME;
    $seoDescription = trim((string) ($tour['meta_description'] ?? '')) ?: ($tourExcerpt ?: 'Plan a private Sri Lanka tour with Island Culture Travels.');
    $seoCanonical = $pageUrl;
    $seoImage = $heroImage;
    $seoType = 'article';

    $breadcrumbs = [
        ['name' => 'Home', 'url' => base_url()],
        ['name' => 'Tours', 'url' => base_url('tours')],
        ['name' => $tourName, 'url' => $pageUrl],
    ];

    $schemaGraph = [
        schema_website(),
        schema_travel_agency(),
        schema_breadcrumbs($breadcrumbs),
        schema_trip_from_tour($tour, $pageUrl),
    ];
    $faqSchema = schema_faq_from_items($faqItems);
    if ($faqSchema) {
        $schemaGraph[] = $faqSchema;
    }

    $bodyClass = 'page-tour-detail';
    $extraStylesheets[] = 'assets/css/journey-layouts.css';
    $extraStylesheets[] = 'assets/css/route-map.css';
    require_once __DIR__.'/includes/page_gallery_view.php';
    $pageGallery=page_gallery_rows('tour',(int)$tour['id']);
    $seoImageAlt=(string)($tour['og_alt']??'');
$extraStylesheets[]='assets/css/page-gallery.css';
    if(!empty($tour['og_image']))$seoImage=stored_media_url($tour['og_image']);
    if($pageGallery)$schemaGraph[]=['@type'=>'ImageGallery','name'=>$tourName.' photographs','image'=>page_gallery_schema($pageGallery)];
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/tour_detail_view.php';

    $ctaTitle = $tourCtaTitle ?: 'Customize ' . $tourName;
    $ctaText = $tourCtaText ?: 'Tell us your dates and travel style, and we will shape this tour around your pace, stays, and interests.';
    $ctaButtonText = 'Customise This Tour';
    $ctaButtonUrl = $tourQuoteUrl;
    $ctaWhatsappText = 'Hi, I am interested in the ' . $tourName . ' tour.';
    schema_output($schemaGraph);
    require __DIR__ . '/includes/cta.php';
    require __DIR__ . '/includes/trust_links.php';
require __DIR__ . '/includes/footer.php';
    return;
}

$category = find_itinerary_category_by_slug($slug, true);
if (!$category) {
    $legacyTour = find_published_tour_by_slug($slug);
    if ($legacyTour) {
        redirect('itineraries', 301);
    }

    require __DIR__ . '/404.php';
    exit;
}

$canUseCategory = db_column_exists('itineraries', 'category_id');
$itineraries = $canUseCategory
    ? fetch_all(
        "SELECT id, name, slug, days, excerpt, hero_image, og_image, meta_description, " . pricing_select_fragment('itineraries') . "
         FROM itineraries
         WHERE is_published = 1
           AND category_id = :category_id
         ORDER BY is_featured DESC, sort_order ASC, days ASC, name ASC",
        ['category_id' => $category['id']]
    )
    : [];

$fallbackHero = random_record_hero_image($itineraries, ['hero_image', 'og_image']);
$heroImage = !empty($category['hero_image'])
    ? stored_media_url((string) $category['hero_image'])
    : (!empty($category['og_image']) ? stored_media_url((string) $category['og_image']) : $fallbackHero);

$pageUrl = itinerary_category_public_canonical_url($category);

$seoTitle = (string) ($category['meta_title'] ?: ($category['name'] . ' Experiences in Sri Lanka | ' . APP_NAME));
$seoDescription = (string) ($category['meta_description'] ?: ('Explore ' . $category['name'] . ' experiences in Sri Lanka and related private itinerary ideas.'));
$seoCanonical = $pageUrl;
$seoImage = $heroImage;
$seoType = 'website';
if (!$itineraries && $pageUrl === base_url('tours/' . (string) $category['slug'])) {
    $seoRobots = 'noindex,follow';
}

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Experiences', 'url' => base_url('experiences')],
    ['name' => (string) $category['name'], 'url' => $pageUrl],
];

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
];

$schemaItems = [];
foreach ($itineraries as $item) {
    $schemaItems[] = [
        'url'   => base_url('itineraries/' . $item['slug']),
        'name'  => (string) $item['name'],
        'image' => (package_card_image($item, 'itinerary') ?: DEFAULT_OG_IMAGE),
    ];
}

if ($schemaItems) {
    $schemaGraph[] = schema_item_list((string) $category['name'] . ' Itineraries', $schemaItems);
}

$bodyClass = 'page-tours';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner" style="--hero-image: url('<?= e($heroImage) ?>');">
    <div class="container">
        <div class="hero-content">
            <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
            <span class="section-eyebrow" style="color:var(--accent-light);">Sri Lanka Experience</span>
            <h1 class="hero-title section-title" style="color:#fff;"><?= e($category['name']) ?> <em style="font-style:italic; color:var(--brand-200);">Experiences</em></h1>
            <p class="lead section-lead" style="color:rgba(255,255,255,.86); max-width:48ch;">
                <?= e((string) ($category['description'] ?: 'Explore experience ideas and related itineraries that can be customized for your travel dates.')) ?>
            </p>
            <?php
            $trustAudienceLabel = 'Private and Family Tours';
            $trustExtraLabel = 'Hidden gems and route ideas';
            require __DIR__ . '/includes/trust_signals.php';
            ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="section-head mb-4">
            <div>
                <span class="section-eyebrow"><?= e($category['name']) ?> Collection</span>
                <h2 class="section-title">Itineraries with <em><?= e($category['name']) ?></em></h2>
                <p class="section-lead">Private routes that feature this experience — all chauffeur-guided and customisable.</p>
            </div>
            <a href="<?= e(base_url('itineraries')) ?>" class="section-link">View all itineraries →</a>
        </div>
        <?php if (!$itineraries): ?>
            <p class="text-secondary text-center py-4 mb-0">No itineraries published in this category yet.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($itineraries as $itinerary): ?>
                    <?php
                    $img = (package_card_image($itinerary, 'itinerary') ?: DEFAULT_OG_IMAGE);
                    $priceLabel = format_price_range($itinerary);
                    $itDays = (int)($itinerary['days'] ?? 0);
                    $hasPrice2 = $priceLabel !== '';
                    ?>
                    <div class="col-md-6 col-xl-4 d-flex">
                        <a href="<?= e(base_url('itineraries/' . $itinerary['slug'])) ?>" class="card w-100" style="text-decoration:none; color:inherit;">
                            <div class="card-thumb">
                                <?= responsive_image([
                                    'src' => $img,
                                    'alt' => package_card_alt($itinerary, 'itinerary'),
                                    'widths' => [400, 800],
                                    'sizes' => '(min-width: 992px) 360px, 100vw',
                                    'width' => 800,
                                    'height' => 600,
                                    'loading' => 'lazy',
                                ]) ?>
                                <?php if ($itDays > 0): ?>
                                    <span class="card-badge"><?= $itDays ?> Days</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h2 class="card-title h5"><?= e($itinerary['name']) ?></h2>
                                <p class="card-excerpt">
                                    <?= e(excerpt_words((string) ($itinerary['excerpt'] ?: $itinerary['meta_description'] ?: ''), 20)) ?>
                                </p>
                                <div class="card-footer">
                                    <?php if ($hasPrice2): ?>
                                        <span class="card-price-label">Starting from</span>
                                        <span class="card-price"><?= e($priceLabel) ?></span>
                                    <?php else: ?>
                                        <span class="card-price-label"><?= $itDays > 0 ? $itDays . ' day itinerary' : 'Private tour' ?></span>
                                        <span class="card-price">Custom quote</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
$ctaTitle = 'Need a Custom Plan With ' . (string) $category['name'] . '?';
$ctaText = 'Tell us your dates and priorities and we will weave the right experiences into a private itinerary.';
schema_output($schemaGraph);
require __DIR__ . '/includes/cta.php';
require __DIR__ . '/includes/trust_links.php';
require __DIR__ . '/includes/footer.php';
