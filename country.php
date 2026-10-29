<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/toc.php';

$slug = trim((string) get_input('slug', ''));
if ($slug === '') {
    require __DIR__ . '/404.php';
    exit;
}

$countryPage = find_published_country_page_by_slug($slug);
if (!$countryPage) {
    require __DIR__ . '/404.php';
    exit;
}

$countryName = trim((string) ($countryPage['public_country_name'] ?? $countryPage['country_name'] ?? ''));

$topDestinations = fetch_all("
    SELECT id, name, slug
    FROM destinations
    WHERE is_published = 1
    ORDER BY is_featured DESC, sort_order ASC, name ASC
    LIMIT 4
");

$recommendedItinerary = fetch_one("
    SELECT id, name, slug, days
    FROM itineraries
    WHERE is_published = 1
    ORDER BY is_featured DESC, sort_order ASC, days ASC
    LIMIT 1
");

$countryItineraries = [];
if (db_table_exists('country_market_itinerary_links')) {
    $countryItineraries = fetch_all("
        SELECT i.id, i.name, i.slug, i.days, i.excerpt, i.hero_image, i.og_image
        FROM country_market_itinerary_links AS link
        INNER JOIN itineraries AS i ON i.slug = link.itinerary_slug
        WHERE link.country_slug = :country_slug
          AND i.is_published = 1
        ORDER BY link.sort_order ASC, i.name ASC
        LIMIT 6
    ", ['country_slug' => $slug]);
}

$privateTour = null;
$familyTour = null;

$faqItems = [];
if (!empty($countryPage['faq_json'])) {
    $decodedFaq = json_decode($countryPage['faq_json'], true);
    if (is_array($decodedFaq)) {
        $faqItems = clean_public_faq_items($decodedFaq);
    }
}

$pageUrl = !empty($countryPage['canonical_url'])
    ? $countryPage['canonical_url']
    : base_url('from/' . $countryPage['slug']);

$pageImage = !empty($countryPage['og_image'])
    ? upload_url($countryPage['og_image'])
    : (!empty($countryPage['hero_image']) ? upload_url($countryPage['hero_image']) : DEFAULT_OG_IMAGE);

$seoTitle = $countryPage['meta_title'] ?: ('Private Sri Lanka Tours for Travelers from ' . $countryName . ' | ' . APP_NAME);
$seoDescription = $countryPage['meta_description'] ?: ($countryPage['excerpt'] ?: ('Plan your Sri Lanka holiday from ' . $countryName . ' with a trusted chauffeur service, comfortable vehicle options, and personalized travel itineraries.'));
$seoCanonical = $pageUrl;
$seoImage = $pageImage;
$seoImageWidth = 1200;
$seoImageHeight = 675;
$seoType = 'article';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'From ' . $countryName, 'url' => $pageUrl],
];

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_article([
        'title' => 'Private Sri Lanka Tours for Travelers from ' . $countryName,
        'excerpt' => $countryPage['excerpt'] ?? '',
        'meta_description' => $seoDescription,
        'image' => $pageImage,
        'author' => APP_NAME,
        'created_at' => $countryPage['created_at'] ?? null,
        'updated_at' => $countryPage['updated_at'] ?? null,
        'about' => [
            'Sri Lanka private tours from ' . $countryName,
            'Sri Lanka itinerary planning',
            'Sri Lanka chauffeur-guided tours',
        ],
        'mentions' => [
            'Sri Lanka',
            $countryName,
            'Bandaranaike International Airport',
        ],
    ], $pageUrl, 'Article'),
];

if ($countryItineraries) {
    $schemaGraph[] = schema_item_list(
        'Sri Lanka itineraries from ' . $countryName,
        array_map(static function (array $itinerary): array {
            return [
                'name' => (string) ($itinerary['name'] ?? ''),
                'url' => base_url('itineraries/' . (string) ($itinerary['slug'] ?? '')),
            ];
        }, $countryItineraries)
    );
}

$faqSchema = schema_faq_from_items($faqItems);
if ($faqSchema) {
    $schemaGraph[] = $faqSchema;
}

$bodyClass = 'page-country';

$countryIntro = clean_public_text($countryPage['intro'] ?? '');
$countryWhySriLanka = clean_public_text($countryPage['why_sri_lanka'] ?? '');
$countryRecItinerary = clean_public_text($countryPage['recommended_itinerary'] ?? '');
$countryTravelInfo = clean_public_text($countryPage['travel_info'] ?? '');
$countryBestTime = clean_public_text($countryPage['best_time'] ?? '');

$heroTitle = "Private Sri Lanka Tours for Travelers from {$countryName}";
$heroSubtitle = clean_public_text(
    $countryPage['excerpt'] ?? '',
    "Plan your Sri Lanka holiday from {$countryName} with a trusted chauffeur service, comfortable vehicle options, and personalized travel itineraries."
);

if (strtolower($countryName) === 'australia') {
    $heroTitle = 'Private Sri Lanka Travel Services for Visitors from Australia';
    $heroSubtitle = clean_public_text(
        $countryPage['excerpt'] ?? '',
        'Arrange your Sri Lanka holiday in advance with a professional chauffeur, comfortable transport, and carefully planned travel routes across the island.'
    );
}

$tocItems = [];
if ($countryIntro !== '')                                        $tocItems[] = ['id' => 'planning',   'text' => 'Planning Your Journey'];
if ($countryWhySriLanka !== '')                                  $tocItems[] = ['id' => 'why',        'text' => 'Why Sri Lanka'];
if ($countryRecItinerary !== '' || $countryTravelInfo !== '')   $tocItems[] = ['id' => 'recommended','text' => 'Recommended Itinerary'];
if ($countryBestTime !== '')                                     $tocItems[] = ['id' => 'best-time',  'text' => 'Best Time to Visit'];
if ($countryItineraries || $recommendedItinerary || $topDestinations) {
    $tocItems[] = ['id' => 'helpful', 'text' => 'Helpful Pages'];
}
if (!empty($faqItems))                                           $tocItems[] = ['id' => 'faq',        'text' => 'FAQs'];

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner" style="--hero-image: url('<?= e($pageImage) ?>');">
    <div class="container">
        <div class="hero-content">
            <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>

            <div class="hero-meta">
                <span class="badge-soft">Private Travel from <?= e($countryName) ?></span>
            </div>

            <h1 class="display-6 fw-bold mb-3 hero-title"><?= e($heroTitle) ?></h1>

            <div class="hero-summary-box">
                <p class="lead mb-0"><?= e($heroSubtitle) ?></p>
            </div>

            <?php
            $trustAudienceLabel = 'Private and Family Tours';
            $trustExtraLabel = 'From-country planning support';
            require __DIR__ . '/includes/trust_signals.php';
            ?>

        </div>
    </div>
</section>

<section class="detail-content-wrap pb-5">
    <div class="container">
        <div class="detail-content-panel">
            <?= render_toc_from_items($tocItems) ?>
            <div class="detail-main">
                <?= render_toc_mobile_from_items($tocItems) ?>
                <?php if ($countryIntro !== ''): ?>
                    <section id="planning">
                        <div class="detail-meta-row">
                            <span class="detail-meta-pill">Travel from <?= e($countryName) ?></span>
                            <span class="detail-meta-pill">Private chauffeur-guided planning</span>
                        </div>
                        <h2>Planning Your Sri Lanka Journey from <?= e($countryName) ?></h2>
                        <div class="content-prose"><?= render_public_content($countryIntro) ?></div>
                    </section>
                <?php endif; ?>

                <section>
                    <div class="image-break" style="--break-image: url('<?= e($pageImage) ?>');"></div>
                </section>

                <?php if ($countryWhySriLanka !== ''): ?>
                    <section id="why">
                        <div class="detail-block-soft">
                            <h2>Why Sri Lanka Suits Travelers from <?= e($countryName) ?></h2>
                            <div class="content-prose"><?= render_public_content($countryWhySriLanka) ?></div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($countryRecItinerary !== '' || $countryTravelInfo !== ''): ?>
                    <section id="recommended">
                        <div class="split-highlight">
                            <?php if ($countryRecItinerary !== ''): ?>
                                <div class="split-copy">
                                    <h2>Recommended Itinerary</h2>
                                    <div class="content-prose"><?= render_public_content($countryRecItinerary) ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if ($countryTravelInfo !== ''): ?>
                                <div class="split-note">
                                    <h2>Travel Planning Notes</h2>
                                    <div class="content-prose"><?= render_public_content($countryTravelInfo) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($countryBestTime !== ''): ?>
                    <section id="best-time">
                        <h2>Best Time to Visit Sri Lanka</h2>
                        <div class="content-prose"><?= render_public_content($countryBestTime) ?></div>
                    </section>
                <?php endif; ?>

                <?php if ($countryItineraries || $recommendedItinerary || $topDestinations): ?>
                    <section id="helpful">
                        <div class="accordion related-accordion related-accordion-grid" id="countryRelatedAccordion">
                            <?php if ($countryItineraries || $recommendedItinerary): ?>
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="country-helpful-heading">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#country-helpful-panel" aria-expanded="true" aria-controls="country-helpful-panel">
                                            Country Itineraries
                                        </button>
                                    </h3>
                                    <div id="country-helpful-panel" class="accordion-collapse collapse show" aria-labelledby="country-helpful-heading">
                                        <div class="accordion-body">
                                            <ul class="related-link-list">
                                                <?php if ($countryItineraries): ?>
                                                    <?php foreach ($countryItineraries as $itinerary): ?>
                                                        <li>
                                                            <a href="<?= e(base_url('itineraries/' . $itinerary['slug'])) ?>">
                                                                <?= e($itinerary['name']) ?><?= !empty($itinerary['days']) ? ' (' . (int) $itinerary['days'] . ' days)' : '' ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                <?php elseif ($recommendedItinerary): ?>
                                                    <li>
                                                        <a href="<?= e(base_url('itineraries/' . $recommendedItinerary['slug'])) ?>">
                                                            <?= e($recommendedItinerary['name']) ?><?= !empty($recommendedItinerary['days']) ? ' (' . (int) $recommendedItinerary['days'] . ' days)' : '' ?>
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($topDestinations): ?>
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="country-topdest-heading">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#country-topdest-panel" aria-expanded="true" aria-controls="country-topdest-panel">
                                            Top Destinations
                                        </button>
                                    </h3>
                                    <div id="country-topdest-panel" class="accordion-collapse collapse show" aria-labelledby="country-topdest-heading">
                                        <div class="accordion-body">
                                            <ul class="related-link-list">
                                                <?php foreach ($topDestinations as $item): ?>
                                                    <li><a href="<?= e(base_url('destinations/' . $item['slug'])) ?>"><?= e($item['name']) ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($faqItems): ?>
                    <div id="faq" style="scroll-margin-top:96px;"></div>
                    <?php
                    $faqHeading = 'Frequently Asked Questions';
                    $faqAccordionId = 'countryFaqAccordion';
                    $faqPrefix = 'country-faq';
                    $faqCtaTitle = 'Need Help Planning from ' . $countryName . '?';
                    $faqCtaText = 'Share your travel month and trip length. We will map a practical private route from arrival to departure.';
                    $faqCtaHref = base_url('contact');
                    $faqRelatedLinks = [];

                    foreach (array_slice($topDestinations, 0, 4) as $item) {
                        $faqRelatedLinks[] = ['label' => $item['name'], 'url' => base_url('destinations/' . $item['slug'])];
                    }
                    foreach (array_slice($countryItineraries, 0, 3) as $itinerary) {
                        $faqRelatedLinks[] = ['label' => $itinerary['name'], 'url' => base_url('itineraries/' . $itinerary['slug'])];
                    }
                    if (!$countryItineraries && $recommendedItinerary) {
                        $faqRelatedLinks[] = ['label' => $recommendedItinerary['name'], 'url' => base_url('itineraries/' . $recommendedItinerary['slug'])];
                    }
                    require __DIR__ . '/includes/faq_section.php';
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
$ctaTitle = ($countryPage['cta_title'] ?? '') ?: ('Plan Your Sri Lanka Tour from ' . ($countryName ?: 'your country'));
$ctaText = ($countryPage['cta_text'] ?? '') ?: 'We help couples, families, and small groups plan smooth private Sri Lanka holidays with the right route and travel pace.';
$ctaWhatsappText = 'Hello Island Culture Travels, I am planning a Sri Lanka trip from ' . ($countryName ?: 'my country') . '.';
require __DIR__ . '/includes/cta.php';

schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
