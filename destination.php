<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/structured_sections.php';
require_once __DIR__ . '/includes/detail_redesign_helpers.php';
require_once __DIR__ . '/includes/responsive_image.php';

$slug = trim((string) get_input('slug', ''));
if ($slug === '') {
    require __DIR__ . '/404.php';
    exit;
}

$destination = find_published_destination_by_slug($slug);
if (!$destination) {
    require __DIR__ . '/404.php';
    exit;
}

$destinationId = (int) $destination['id'];
$destinationPhotos = [];
$galleryAvailable = db_table_exists('destination_photos');
foreach (['destination_id', 'image_path', 'caption', 'sort_order', 'id'] as $galleryColumn) {
    if ($galleryAvailable && !db_column_exists('destination_photos', $galleryColumn)) { $galleryAvailable = false; }
}
if ($galleryAvailable) {
    $destinationPhotos = fetch_all('SELECT image_path, caption FROM destination_photos WHERE destination_id = :id ORDER BY sort_order, id', ['id' => $destinationId]);
    // Legacy assignments may outlive their files; keep them editable in admin without broken public tiles.
    $destinationPhotos = array_values(array_filter($destinationPhotos, static function (array $photo): bool {
        $relative = image_normalize_storage_path((string) $photo['image_path']);
        if ($relative === null) { return true; }
        if ($relative === '' || str_contains($relative, '..')) { return false; }
        $file = str_starts_with($relative, 'assets/') ? BASE_PATH . '/' . $relative : UPLOAD_BASE_PATH . '/' . $relative;
        return is_file($file) && is_readable($file);
    }));
}

$relatedItineraries = fetch_all(
    "SELECT i.id, i.name, i.slug, i.days, i.excerpt, i.hero_image, i.og_image, " . pricing_select_fragment('itineraries') . "
     FROM destination_itinerary di
     INNER JOIN itineraries i ON i.id = di.itinerary_id
     WHERE di.destination_id = :id
       AND i.is_published = 1
     ORDER BY i.sort_order ASC, i.days ASC, i.name ASC
     LIMIT 3",
    ['id' => $destinationId]
);

$relatedDestinations = [];
if (db_table_exists('destination_related') && db_table_exists('destinations')) {
    $relatedDestinations = fetch_all(
        "SELECT d.id, d.name, d.slug, d.excerpt, d.cover_image, d.hero_image, d.province, d.destination_type, " . content_image_select_fragment('destinations','d') . "
         FROM destination_related dr
         INNER JOIN destinations d ON d.id = dr.related_destination_id
         WHERE dr.destination_id = :id
           AND d.is_published = 1
         ORDER BY d.sort_order ASC, d.name ASC
         LIMIT 6",
        ['id' => $destinationId]
    );
}

$relatedTours = [];
if (db_table_exists('destination_tour') && db_table_exists('tours')) {
    $relatedTours = fetch_all(
        "SELECT t.id, t.name, t.slug, t.excerpt, t.hero_image, t.og_image, " . pricing_select_fragment('tours') . "
         FROM destination_tour dt
         INNER JOIN tours t ON t.id = dt.tour_id
         WHERE dt.destination_id = :id
           AND t.is_published = 1
         ORDER BY t.is_featured DESC, t.sort_order ASC, t.name ASC
         LIMIT 3",
        ['id' => $destinationId]
    );
}

$relatedExperiences = [];
if (db_table_exists('experience_destination') && db_table_exists('experiences')) {
    $relatedExperiences = fetch_all(
        "SELECT e.id, e.title, e.slug, e.excerpt, e.hero_image, e.og_image, " . content_image_select_fragment('experiences','e') . "
         FROM experience_destination ed
         INNER JOIN experiences e ON e.id = ed.experience_id
         WHERE ed.destination_id = :id
           AND e.is_published = 1
         ORDER BY e.sort_order ASC, e.title ASC
         LIMIT 4",
        ['id' => $destinationId]
    );
}

$pageUrl = !empty($destination['canonical_url'])
    ? (string) $destination['canonical_url']
    : base_url('destinations/' . $destination['slug']);
$pageImage = detail_image_from_record($destination, ['hero_image', 'cover_image', 'og_image']);
$heroImage = detail_image_from_record($destination, ['hero_image', 'cover_image', 'og_image']);
$destinationName = trim((string) ($destination['name'] ?? 'Destination'));
$destinationExcerpt = trim((string) ($destination['excerpt'] ?? ''));
$destinationProvince = trim((string) ($destination['province'] ?? ''));
$destinationType = trim((string) ($destination['destination_type'] ?? ''));
$destinationStay = trim((string) ($destination['recommended_stay'] ?? ($destination['stay_duration'] ?? '')));
$destinationContext = strtolower($destinationName . ' ' . $destinationType . ' ' . $destinationProvince);
$relatedDestinationNames = array_values(array_filter(array_map(
    static fn(array $item): string => trim((string) ($item['name'] ?? '')),
    $relatedDestinations
)));
$relatedItineraryNames = array_values(array_filter(array_map(
    static fn(array $item): string => trim((string) ($item['name'] ?? '')),
    $relatedItineraries
)));

$destinationTheme = 'Sri Lanka private touring';
if (preg_match('/wildlife|safari|yala|udawalawe|minneriya|wilpattu|sinharaja|elephant|leopard|bird/', $destinationContext)) {
    $destinationTheme = 'wildlife, safari timing, and nature-focused routing';
} elseif (preg_match('/beach|coast|galle|mirissa|bentota|trincomalee|arugam|negombo|surf|whale/', $destinationContext)) {
    $destinationTheme = 'coastal stays, beach time, and relaxed southern or eastern routing';
} elseif (preg_match('/ella|nuwara|tea|hill|waterfall|kandy|knuckles|mountain/', $destinationContext)) {
    $destinationTheme = 'tea country, viewpoints, rail scenery, and cooler hill-country pacing';
} elseif (preg_match('/sigiriya|dambulla|anuradhapura|polonnaruwa|temple|heritage|culture|unesco|buddhist/', $destinationContext)) {
    $destinationTheme = 'heritage sites, temples, UNESCO context, and early-start sightseeing';
} elseif (preg_match('/colombo|city|capital/', $destinationContext)) {
    $destinationTheme = 'arrival logistics, city food stops, galleries, and first-night or final-night planning';
}

$seoTitle = trim((string) ($destination['meta_title'] ?? '')) ?: $destinationName . ' Travel Guide | ' . APP_NAME;
$seoDescription = trim((string) ($destination['meta_description'] ?? '')) ?: ($destinationExcerpt ?: 'Discover ' . $destinationName . ' with private Sri Lanka tour planning.');
$seoCanonical = $pageUrl;
$seoImage = $pageImage;
$seoType = 'article';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Destinations', 'url' => base_url('destinations')],
    ['name' => $destinationName, 'url' => $pageUrl],
];
$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_destination($destination, $pageUrl),
];

$bestTimeData = decode_best_time_json($destination['best_time_json'] ?? null);
$goodMonths = array_map('strtolower', array_map('strval', $bestTimeData['months'] ?? []));
$bestTimeNote = trim((string) ($bestTimeData['note'] ?? ''));
$routeNotesHtml = render_route_notes_block($destination['route_notes_json'] ?? null, [
    'title' => $destinationName . ' Route & Driving Notes',
]);
$faqItems = decode_faq_json_items($destination['faq_json'] ?? null);
if ($faqItems) {
    $faqSchema = schema_faq_from_items($faqItems);
    if ($faqSchema) {
        $schemaGraph[] = $faqSchema;
    }
}

$fallbackBlocks = [
    'Overview' => $destinationName . ' is a useful stop for ' . $destinationTheme . '. A private chauffeur-guided route lets you time the main sights around heat, crowds, wildlife movement, or coastal conditions instead of following a fixed bus schedule.' . ($destinationStay !== '' ? ' Most guests plan around ' . $destinationStay . ' here, then connect it with nearby regions for a smoother Sri Lanka journey.' : ''),
    'Why Visit ' . $destinationName => 'Visit ' . $destinationName . ' when you want the route to feel planned but not rushed. With a private driver, you can start early, pause for local viewpoints or food stops, and adjust the day if weather, traffic, or site conditions change.',
    'Things to Do' => 'Typical private-route planning includes the headline sights, locally timed photo stops, a relaxed meal break, and enough buffer for road conditions. Your driver can help sequence the day so the most important stop happens at the best time rather than at the busiest hour.',
    'How to Get There' => $destinationName . ' works best by private vehicle because Sri Lanka travel times change with weather, city traffic, mountain roads, and festival periods. Plan the transfer from the previous overnight stop with an early start where possible, and keep enough time for rest stops or scenic detours.',
    'Suggested Private Route' => $relatedItineraryNames
        ? 'This destination pairs well with routes such as ' . implode(', ', array_slice($relatedItineraryNames, 0, 3)) . '. Use those itineraries as starting points, then adjust nights, hotel style, and daily pace around your arrival time and travel priorities.'
        : 'Use ' . $destinationName . ' as part of a clockwise or south-to-hill-country private route depending on your arrival airport, beach preference, and total trip length. The best sequence depends on whether you prioritise wildlife, culture, beaches, or a slower boutique-stay pace.',
    'Works Well With' => $relatedDestinationNames
        ? $destinationName . ' combines naturally with ' . implode(', ', array_slice($relatedDestinationNames, 0, 4)) . '. Linking these stops by private vehicle keeps the route practical and reduces backtracking.'
        : $destinationName . ' combines well with one or two contrasting regions so the trip does not feel repetitive. Pair it with a beach, hill-country, cultural, or wildlife stop based on the season and your flight times.',
];

$overviewBlocks = [
    'Overview' => $destination['intro'] ?? '',
    'Why Visit ' . $destinationName => $destination['why_visit'] ?? '',
    'Things to Do' => $destination['things_to_do'] ?? '',
    'Best Time' => $destination['best_time'] ?? '',
    'How to Get There' => $destination['how_to_get_there'] ?? '',
    'Suggested Private Route' => $destination['suggested_itinerary'] ?? '',
    'Works Well With' => $destination['works_well_with'] ?? '',
    'Where to Stay' => $destination['where_to_stay'] ?? '',
];
$destinationCtaTitle = trim((string) ($destination['cta_title'] ?? '')) ?: 'Add ' . $destinationName . ' to your private tour.';
$destinationCtaText = trim((string) ($destination['cta_text'] ?? ''));

$bodyClass = 'page-destination ict-detail-page';
$extraStylesheets = ['assets/css/detail-redesign.css'];
require_once __DIR__.'/includes/page_gallery_view.php';
$pageGallery=page_gallery_rows('destination',(int)$destination['id']);
$seoImageAlt=(string)($destination['og_alt']??'');
$extraStylesheets[]='assets/css/page-gallery.css';
if(!empty($destination['og_image']))$seoImage=stored_media_url($destination['og_image']);
if($pageGallery)$schemaGraph[]=['@type'=>'ImageGallery','name'=>$destinationName.' photographs','image'=>page_gallery_schema($pageGallery)];
require __DIR__ . '/includes/header.php';
?>

<header class="rd-hero">
    <?php if (!is_placeholder_media_url($heroImage)): ?>
        <?= responsive_image([
            'src' => $heroImage,
            'alt' => $destinationName,
            'widths' => [800, 1200, 1600],
            'sizes' => '100vw',
            'width' => 1600,
            'height' => 900,
            'class' => 'rd-hero-img',
            'loading' => 'eager',
            'fetchpriority' => 'high',
        ]) ?>
    <?php endif; ?>
    <div class="rd-hero-overlay"></div>
    <div class="rd-container rd-hero-content">
        <div class="rd-hero-copy">
            <nav class="rd-breadcrumb" aria-label="Breadcrumb">
                <a href="<?= e(base_url('destinations')) ?>">Destinations</a>
                <?= detail_icon('chevron-right', 'rd-icon-xs') ?>
                <span><?= e($destinationName) ?></span>
            </nav>
            <h1 class="rd-title"><?= e($destinationName) ?></h1>
            <?php if ($destinationExcerpt !== ''): ?>
                <p class="rd-lead"><?= e($destinationExcerpt) ?></p>
            <?php endif; ?>
        </div>
    </div>
</header>
<div class="rd-info-bar">
    <div class="rd-container rd-info-inner">
        <div class="rd-info-items">
            <?php if ($destinationProvince !== ''): ?>
                <div class="rd-info-item"><?= detail_icon('map-pin', 'rd-icon') ?><span>Province: <strong><?= e($destinationProvince) ?></strong></span></div>
            <?php endif; ?>
            <?php if ($destinationType !== ''): ?>
                <div class="rd-info-item"><?= detail_icon('compass', 'rd-icon') ?><span>Type: <strong><?= e($destinationType) ?></strong></span></div>
            <?php endif; ?>
            <?php
            $difficulty = trim((string) ($destination['difficulty_level'] ?? ''));
            if ($difficulty !== ''):
            ?>
                <div class="rd-info-item"><?= detail_icon('trending-up', 'rd-icon') ?><span>Difficulty: <strong><?= e($difficulty) ?></strong></span></div>
            <?php endif; ?>
            <?php
            $crowd = trim((string) ($destination['crowd_level'] ?? ''));
            if ($crowd !== ''):
            ?>
                <div class="rd-info-item rd-crowd-item"><?= detail_icon('users', 'rd-icon') ?><span>Crowds: <strong><?= e($crowd) ?></strong></span></div>
            <?php endif; ?>
            <?php if (!empty($destination['is_hidden_gem'])): ?>
                <div class="rd-info-item rd-hidden-badge"><?= detail_icon('star', 'rd-icon') ?><span><strong>Hidden Gem</strong></span></div>
            <?php endif; ?>
        </div>
        <a href="<?= e(base_url('contact?destination=' . rawurlencode($destinationName))) ?>" class="btn btn-primary">Include in My Route</a>
    </div>
</div>

<section class="rd-section">
    <div class="rd-container rd-two-col">
        <div class="rd-main">
            <?php foreach ($overviewBlocks as $heading => $content): ?>
                <?php
                $content = trim((string) $content);
                if ($content === '' && isset($fallbackBlocks[$heading])) {
                    $content = trim((string) $fallbackBlocks[$heading]);
                }
                if ($content === '') continue;
                ?>
                <div class="rd-content-block<?= $heading === 'Overview' ? ' mt-0' : '' ?>">
                    <?php if ($heading !== 'Overview'): ?><h2><?= e($heading) ?></h2><?php endif; ?>
                    <div class="rd-prose"><?= render_public_content($content) ?></div>
                </div>
            <?php endforeach; ?>

            <?php if (!empty($destination['travel_tips'])): ?>
                <div class="rd-tips">
                    <h3><?= detail_icon('info', 'rd-icon-lg') ?> ICT Travel Tips</h3>
                    <div class="rd-prose"><?= render_public_content((string) $destination['travel_tips']) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($goodMonths): ?>
                <div class="rd-weather">
                    <span class="rd-kicker">Weather Check</span>
                    <h2>When to Visit</h2>
                    <div class="rd-month-grid">
                        <?php foreach (detail_month_labels() as $month): ?>
                            <?php $isGood = in_array($month, $goodMonths, true); ?>
                            <div class="rd-month<?= $isGood ? ' is-good' : '' ?>">
                                <span><?= e(substr($month, 0, 3)) ?></span>
                                <?php if ($isGood): ?><?= detail_icon('check', 'rd-icon-xs') ?><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($bestTimeNote !== ''): ?><p class="rd-note"><?= e($bestTimeNote) ?></p><?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($routeNotesHtml !== ''): ?>
                <div class="rd-content-block rd-structured-block">
                    <?= $routeNotesHtml ?>
                </div>
            <?php endif; ?>

            <?php if ($faqItems): ?>
                <div class="rd-content-block">
                    <span class="rd-kicker">Travel Answers</span>
                    <h2>Frequently Asked Questions About <?= e($destinationName) ?></h2>
                    <div class="rd-faq-list">
                        <?php foreach ($faqItems as $index => $faq): ?>
                            <details class="rd-faq-item" <?= $index === 0 ? 'open' : '' ?>>
                                <summary><?= e($faq['question']) ?></summary>
                                <div class="rd-prose"><?= render_public_content($faq['answer']) ?></div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <aside class="rd-sidebar">
            <?php if (!empty($destination['nearby_travel_times'])): ?>
                <div class="rd-card">
                    <h4><?= detail_icon('clock', 'rd-icon') ?> Drive Times</h4>
                    <div class="rd-prose"><?= render_public_content((string) $destination['nearby_travel_times']) ?></div>
                </div>
            <?php endif; ?>
            <?php
            $ratingsJson = $destination['platform_ratings_json'] ?? '';
            $ratings = $ratingsJson !== '' ? json_decode($ratingsJson, true) : null;
            if ($ratings && is_array($ratings)):
            ?>
                <div class="rd-card rd-ratings-card">
                    <h4><?= detail_icon('star', 'rd-icon') ?> Platform Ratings</h4>
                    <div class="rd-ratings-list">
                        <?php foreach ($ratings as $label => $r): if (!is_array($r)) continue; ?>
                            <div class="rd-rating-item">
                                <span class="rd-rating-source"><?= e($label) ?></span>
                                <span class="rd-rating-score">
                                    <strong><?= e((string)($r['score'] ?? '')) ?></strong>
                                    <?php if (!empty($r['max'])): ?><small>/ <?= e((string)$r['max']) ?></small><?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php
            $bestSeason = trim((string) ($destination['best_season_label'] ?? ''));
            $weather    = trim((string) ($destination['weather_summary'] ?? ''));
            if ($bestSeason !== '' || $weather !== ''):
            ?>
                <div class="rd-card rd-weather-card">
                    <h4><?= detail_icon('moon', 'rd-icon') ?> Best Time</h4>
                    <?php if ($bestSeason !== ''): ?>
                        <p class="rd-weather-season"><?= e($bestSeason) ?></p>
                    <?php endif; ?>
                    <?php if ($weather !== ''): ?>
                        <p class="rd-weather-note"><?= e($weather) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="rd-cta-card">
                <h3><?= e($destinationCtaTitle) ?></h3>
                <?php if ($destinationCtaText !== ''): ?>
                    <p><?= e($destinationCtaText) ?></p>
                <?php endif; ?>
                <a href="<?= e(base_url('contact?destination=' . rawurlencode($destinationName))) ?>" class="btn btn-primary">Start Route Plan</a>
                <a href="<?= e(site_whatsapp_url('Hi, I am interested in visiting ' . $destinationName . ' in Sri Lanka.')) ?>" target="_blank" rel="noopener" class="btn btn-whatsapp" style="margin-top:.5rem;">Chat on WhatsApp</a>
            </div>
        </aside>
    </div>
</section>



<?php page_gallery_assets();page_gallery_public($pageGallery,$destinationName); ?>

<?php if ($relatedItineraries): ?>
<section class="rd-section rd-section-soft">
    <div class="rd-container">
        <div class="rd-section-head section-head">
            <div>
                <span class="section-eyebrow rd-kicker">Route Ideas</span>
                <h2 class="section-title rd-section-title">Tours Featuring <?= e($destinationName) ?></h2>
                <p class="section-lead" style="margin-top:.5rem;">Handpicked private routes featuring this destination.</p>
            </div>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px,1fr)); gap:1.25rem;">
            <?php foreach ($relatedItineraries as $itinerary): ?>
                <?php
                    $itineraryImage = package_card_image($itinerary, 'itinerary');
                    $itDays = (int)($itinerary['days'] ?? 0);
                    $itPrice = format_price_range($itinerary, true) ?: 'On request';
                ?>
                <a href="<?= e(base_url('itineraries/' . $itinerary['slug'])) ?>" class="card" style="text-decoration:none; color:inherit;">
                    <div class="card-thumb">
                        <?php if (!is_placeholder_media_url($itineraryImage)): ?>
                            <?= responsive_image([
                                'src' => $itineraryImage,
                                'alt' => $itinerary['name'],
                                'widths' => [400, 800],
                                'sizes' => '(min-width: 992px) 360px, 100vw',
                                'width' => 800,
                                'height' => 533,
                                'loading' => 'lazy',
                            ]) ?>
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:linear-gradient(135deg, var(--brand), var(--surface-mid));"></div>
                        <?php endif; ?>
                        <?php if ($itDays > 0): ?>
                            <span class="card-badge"><?= $itDays ?> Days</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title" style="font-size:1.05rem;"><?= e($itinerary['name']) ?></h3>
                        <?php if (!empty($itinerary['excerpt'])): ?>
                            <p class="card-excerpt"><?= e(excerpt_words((string)$itinerary['excerpt'], 22)) ?></p>
                        <?php endif; ?>
                        <div class="card-footer">
                            <span class="card-price-label">Starting from</span>
                            <span class="card-price"><?= e($itPrice) ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($relatedDestinations): ?>
<section class="rd-section">
    <div class="rd-container">
        <div class="rd-section-head section-head">
            <div>
                <span class="section-eyebrow rd-kicker">Nearby Pairings</span>
                <h2 class="section-title rd-section-title">Destinations to Combine with <?= e($destinationName) ?></h2>
                <p class="section-lead" style="margin-top:.5rem;">Combine these nearby places for a smoother private route.</p>
            </div>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px,1fr)); gap:1rem;">
            <?php foreach ($relatedDestinations as $relatedDestination): ?>
                <?php $relatedImage = detail_image_from_record($relatedDestination, ['cover_image', 'hero_image']); ?>
                <a href="<?= e(base_url('destinations/' . $relatedDestination['slug'])) ?>" class="card-dest">
                    <?php if (!is_placeholder_media_url($relatedImage)): ?>
                        <?= responsive_image([
                            'src' => $relatedImage,
                            'alt' => $relatedDestination['name'],
                            'widths' => [400, 800],
                            'sizes' => '(min-width: 992px) 220px, 100vw',
                            'width' => 640,
                            'height' => 640,
                            'loading' => 'lazy',
                        ]) ?>
                    <?php else: ?>
                        <div style="position:absolute; inset:0; background:linear-gradient(135deg, var(--brand), var(--surface-mid));"></div>
                    <?php endif; ?>
                    <span class="card-dest-overlay"></span>
                    <span class="card-dest-name"><?= e($relatedDestination['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($relatedTours): ?>
<section class="rd-section rd-section-soft">
    <div class="rd-container">
        <div class="rd-section-head section-head">
            <div>
                <span class="section-eyebrow rd-kicker">Tour Packages</span>
                <h2 class="section-title rd-section-title">Private Tours Featuring <?= e($destinationName) ?></h2>
                <p class="section-lead" style="margin-top:.5rem;">Private chauffeur-guided tours that include this stop.</p>
            </div>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px,1fr)); gap:1.25rem;">
            <?php foreach ($relatedTours as $tour): ?>
                <?php
                    $tourImage = package_card_image($tour, 'tour');
                    $tourPrice = format_price_range($tour, true) ?: 'On request';
                ?>
                <a href="<?= e(base_url('tours/' . $tour['slug'])) ?>" class="card" style="text-decoration:none; color:inherit;">
                    <div class="card-thumb">
                        <?php if (!is_placeholder_media_url($tourImage)): ?>
                            <?= responsive_image([
                                'src' => $tourImage,
                                'alt' => $tour['name'],
                                'widths' => [400, 800],
                                'sizes' => '(min-width: 992px) 360px, 100vw',
                                'width' => 800,
                                'height' => 533,
                                'loading' => 'lazy',
                            ]) ?>
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:linear-gradient(135deg, var(--brand), var(--surface-mid));"></div>
                        <?php endif; ?>
                        <span class="card-badge">Private Tour</span>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title" style="font-size:1.05rem;"><?= e($tour['name']) ?></h3>
                        <?php if (!empty($tour['excerpt'])): ?>
                            <p class="card-excerpt"><?= e(excerpt_words((string)$tour['excerpt'], 22)) ?></p>
                        <?php endif; ?>
                        <div class="card-footer">
                            <span class="card-price-label">Private tour</span>
                            <span class="card-price"><?= e($tourPrice) ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($relatedExperiences): ?>
<section class="rd-section">
    <div class="rd-container">
        <div class="rd-section-head section-head">
            <div>
                <span class="section-eyebrow rd-kicker">Experience Ideas</span>
                <h2 class="section-title rd-section-title">Experiences to Try at <?= e($destinationName) ?></h2>
                <p class="section-lead" style="margin-top:.5rem;">Add these handpicked experiences to your stay.</p>
            </div>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px,1fr)); gap:1rem;">
            <?php foreach ($relatedExperiences as $exp):
                $expImage = detail_image_from_record($exp, ['hero_image', 'og_image']);
            ?>
                <a href="<?= e(base_url('experiences/' . $exp['slug'])) ?>" class="card-exp">
                    <?php if (!is_placeholder_media_url($expImage)): ?>
                        <?= responsive_image([
                            'src' => $expImage,
                            'alt' => $exp['title'],
                            'widths' => [400, 800],
                            'sizes' => '(min-width: 992px) 360px, 100vw',
                            'width' => 800,
                            'height' => 533,
                            'loading' => 'lazy',
                        ]) ?>
                    <?php else: ?>
                        <div style="position:absolute; inset:0; background:linear-gradient(135deg, var(--brand), var(--surface-mid));"></div>
                    <?php endif; ?>
                    <span class="card-exp-overlay"></span>
                    <span class="card-exp-body">
                        <strong class="card-exp-title"><?= e($exp['title']) ?></strong>
                        <?php if (!empty($exp['excerpt'])): ?>
                            <span class="card-exp-excerpt"><?= e(excerpt_words((string)$exp['excerpt'], 22)) ?></span>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
$ctaTitle = 'Add ' . $destinationName . ' to Your Private Route';
$ctaText = 'Tell us your travel dates and group size, and we will weave ' . $destinationName . ' into a smooth private itinerary with the right driver, stops, and pacing.';
$ctaButtonUrl = base_url('contact?destination=' . rawurlencode($destinationName));
$ctaWhatsappText = 'Hi, I am interested in visiting ' . $destinationName . ' in Sri Lanka.';
schema_output($schemaGraph);
require __DIR__ . '/includes/cta.php';
require __DIR__ . '/includes/footer.php';
