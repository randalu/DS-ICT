<?php
declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

// ── Filter params ──────────────────────────────────────────────────────────────
$seoFilterCanonicalBase = base_url('day-tours');
$seoFilterParams = ['region', 'difficulty', 'price', 'q'];
$hasActiveFilters = false;

$regionSlug   = trim((string)get_input('region', ''));
$difficulty   = trim((string)get_input('difficulty', ''));
$priceRange   = trim((string)get_input('price', '')); // e.g. "0-150", "150-300", "300+"
$searchQuery  = trim((string)get_input('q', ''));

// Build where clause
$whereParts = ['dt.is_published = 1'];
$params = [];

if ($regionSlug !== '') {
    $whereParts[] = 'EXISTS (
        SELECT 1 FROM day_tour_destination dtd
        INNER JOIN destinations d ON d.id = dtd.destination_id
        WHERE dtd.day_tour_id = dt.id AND d.region_slug = :region_slug
    )';
    $params['region_slug'] = $regionSlug;
    $hasActiveFilters = true;
}

if ($difficulty !== '' && in_array($difficulty, ['easy', 'moderate', 'active'], true)) {
    $whereParts[] = 'dt.difficulty = :difficulty';
    $params['difficulty'] = $difficulty;
    $hasActiveFilters = true;
}

if ($priceRange !== '') {
    switch ($priceRange) {
        case '0-150':
            $whereParts[] = 'dt.price_from <= 150';
            break;
        case '150-300':
            $whereParts[] = 'dt.price_from > 150 AND dt.price_from <= 300';
            break;
        case '300+':
            $whereParts[] = 'dt.price_from > 300';
            break;
    }
    $hasActiveFilters = true;
}

if ($searchQuery !== '') {
    $whereParts[] = '(dt.name LIKE :q OR dt.excerpt LIKE :q OR dt.intro LIKE :q)';
    $params['q'] = '%' . $searchQuery . '%';
    $hasActiveFilters = true;
}

$whereSql = implode(' AND ', $whereParts);
$orderSql = 'ORDER BY dt.is_featured DESC, dt.sort_order ASC, dt.name ASC';

// ── Load day tours ─────────────────────────────────────────────────────────────
$dayTours = [];
if (db_table_exists('day_tours')) {
    $dayTours = fetch_all(
        "SELECT dt.id, dt.name, dt.slug, dt.excerpt, dt.hero_image, dt.og_image,
                dt.meta_title, dt.meta_description,
                dt.price_from, dt.price_to, dt.price_currency, dt.price_unit,
                dt.duration_hours, dt.difficulty, dt.best_time,
                dt.is_featured, dt.sort_order
         FROM day_tours dt
         WHERE $whereSql
         $orderSql",
        $params
    );
}

// Hero image for listing page
$coverRecords = array_values(array_filter(
    array_map(static fn(array $row): array => package_card_record($row, 'day_tour'), $dayTours),
    static fn(array $row): bool => trim((string) ($row['cover_image'] ?? '')) !== ''
        && !is_placeholder_media_url((string) $row['cover_image'])
));
$heroImage = random_record_hero_image($coverRecords ?: $dayTours, ['cover_image', 'hero_image', 'og_image']);

// ── SEO ────────────────────────────────────────────────────────────────────────
$seoTitle = 'Sri Lanka Day Tours | Private Chauffeur-Guided Day Trips | Island Culture Travels';
$seoDescription = 'Book private Sri Lanka day tours: Sigiriya, Kandy, Yala Safari, Galle Fort, Nuwara Eliya tea country, and more. Chauffeur-guide, flexible pace, hotel pickup. From $85pp.';
$seoCanonical = base_url('day-tours');
$seoImage = $heroImage;
$seoType = 'website';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Day Tours', 'url' => $seoCanonical],
];

$schemaItems = [];
foreach ($dayTours as $tour) {
    $schemaItems[] = [
        'url'   => base_url('day-tours/' . $tour['slug']),
        'name'  => (string) $tour['name'],
        'image' => (package_card_image($tour, 'day_tour') ?: DEFAULT_OG_IMAGE),
    ];
}

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_item_list('Sri Lanka Day Tours', $schemaItems),
];

$bodyClass = 'page-day-tours';
require __DIR__ . '/includes/header.php';
?>

<!-- ════════════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════════════ -->
<section class="page-hero-banner has-media page-hero-day-tours">
    <?= responsive_image([
        'src' => $heroImage !== '' ? $heroImage : DEFAULT_OG_IMAGE,
        'alt' => 'Sri Lanka Day Tours',
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
            <span class="section-kicker" style="color:var(--accent-light);">Private Chauffeur-Guided Day Trips</span>
            <h1 class="hero-title">Sri Lanka <em>Day Tours</em></h1>
            <div class="hero-summary-box">
                <p class="lead mb-0">
                    Single-day, private vehicle experiences — climb Sigiriya, safari in Yala, explore Galle Fort, sip tea in Nuwara Eliya.
                    Pick your day tour, tell us your dates, and we'll fit it into your trip.
                </p>
            </div>
            <?php
            $trustAudienceLabel = 'Private & Family Day Trips';
            $trustExtraLabel = 'Flexible day tour planning';
            require __DIR__ . '/includes/trust_signals.php';
            ?>
        </div>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     FILTER BAR
════════════════════════════════════════════════════════════ -->
<section class="py-3 border-bottom bg-light-subtle">
    <div class="container">
        <form id="dayTourFilterForm" class="row g-3 align-items-end flex-wrap" method="get" action="<?= e(base_url('day-tours')) ?>">
            <!-- Search -->
            <div class="col-12 col-md-4 form-field">
                <label for="filter-q" class="form-label visually-hidden">Search day tours</label>
                <input type="search" id="filter-q" name="q" class="form-input form-control"
                       placeholder="Search: Sigiriya, Yala, Galle, tea country..."
                       value="<?= e($searchQuery) ?>" style="border-radius:var(--radius-sm);"></div>

            <!-- Region -->
            <div class="col-6 col-md-2 form-field">
                <label for="filter-region" class="form-label visually-hidden">Region</label>
                <select id="filter-region" name="region" class="form-select form-select">
                    <option value="">All Regions</option>
                    <?php
                    $regions = [
                        'cultural' => '🏛 Cultural Triangle',
                        'hill' => '🌿 Hill Country',
                        'wildlife' => '🐘 Wildlife',
                        'south' => '🏖 South Coast',
                        'east' => '🌊 East Coast',
                    ];
                    foreach ($regions as $slug => $label):
                        $sel = ($regionSlug === $slug) ? 'selected' : '';
                    ?>
                    <option value="<?= e($slug) ?>" <?= $sel ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Difficulty -->
            <div class="col-6 col-md-2 form-field">
                <label for="filter-difficulty" class="form-label visually-hidden">Activity Level</label>
                <select id="filter-difficulty" name="difficulty" class="form-select form-select">
                    <option value="">All Levels</option>
                    <option value="easy" <?= $difficulty === 'easy' ? 'selected' : '' ?>>🟢 Easy</option>
                    <option value="moderate" <?= $difficulty === 'moderate' ? 'selected' : '' ?>>🟡 Moderate</option>
                    <option value="active" <?= $difficulty === 'active' ? 'selected' : '' ?>>🔴 Active</option>
                </select>
            </div>

            <!-- Price -->
            <div class="col-6 col-md-2 form-field">
                <label for="filter-price" class="form-label visually-hidden">Price Range</label>
                <select id="filter-price" name="price" class="form-select form-select">
                    <option value="">All Prices</option>
                    <option value="0-150" <?= $priceRange === '0-150' ? 'selected' : '' ?>>Under $150</option>
                    <option value="150-300" <?= $priceRange === '150-300' ? 'selected' : '' ?>>$150–300</option>
                    <option value="300+" <?= $priceRange === '300+' ? 'selected' : '' ?>>$300+</option>
                </select>
            </div>

            <!-- Submit / Clear -->
            <div class="col-12 col-md-2 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="<?= e(base_url('day-tours')) ?>" class="btn btn-outline-dark">Clear</a>
            </div>
        </form>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     DAY TOUR GRID
════════════════════════════════════════════════════════════ -->
<section class="py-5">
    <div class="container">
        <?php if (!$dayTours): ?>
            <div class="text-center py-5">
                <div class="display-1 text-secondary mb-3">🗓</div>
                <h2 class="h4">No Day Tours Match Your Filters</h2>
                <p class="text-secondary mb-4">Try adjusting your filters or <a href="<?= e(base_url('day-tours')) ?>">view all day tours</a>.</p>
                <a href="<?= e(base_url('contact')) ?>" class="btn btn-brand">Request a Custom Day Tour</a>
            </div>
        <?php else: ?>
            <div class="row g-4" id="dayTourGrid">
                <?php foreach ($dayTours as $tour): ?>
                    <?php
                    $img = (package_card_image($tour, 'day_tour') ?: DEFAULT_OG_IMAGE);
                    $priceLabel = '';
                    if (!empty($tour['price_from']) || !empty($tour['price_to'])) {
                        $from = (float)$tour['price_from'];
                        $to   = (float)$tour['price_to'];
                        $curr = $tour['price_currency'] ?? 'USD';
                        $unit = $tour['price_unit'] ?? 'per person';
                        if ($from > 0 && $to > 0 && $from !== $to) {
                            $priceLabel = '$' . number_format($from, 0) . '–' . number_format($to, 0) . ' ' . $unit;
                        } elseif ($from > 0) {
                            $priceLabel = 'From $' . number_format($from, 0) . ' ' . $unit;
                        }
                    }
                    $metaLabel = $priceLabel !== '' ? $priceLabel : 'Private day tour';
                    $difficultyLabel = match ($tour['difficulty']) {
                        'easy' => '🟢 Easy',
                        'moderate' => '🟡 Moderate',
                        'active' => '🔴 Active',
                        default => '',
                    };
                    $durationLabel = $tour['duration_hours'] ? (float)$tour['duration_hours'] . ' hrs' : '';
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <article class="card h-100 day-tour-card" data-difficulty="<?= e($tour['difficulty']) ?>" style="border-radius:var(--radius-lg);overflow:hidden;">
                            <div class="card-thumb">
                                <?= responsive_image([
                                    'src' => $img,
                                    'alt' => package_card_alt($tour, 'day_tour'),
                                    'widths' => [400, 800],
                                    'sizes' => '(min-width: 1200px) 33vw, (min-width: 768px) 50vw, 100vw',
                                    'width' => 800,
                                    'height' => 533,
                                    'class' => 'card-img-top',
                                    'loading' => 'lazy',
                                ]) ?>
                                <?php if ($tour['is_featured']): ?>
                                <span class="card-badge">⭐ Featured</span>
                                <?php else: ?>
                                <span class="card-badge"><?= e($difficultyLabel ?: 'Day Tour') ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h2 class="card-title h5 mb-2">
                                    <a href="<?= e(base_url('day-tours/' . $tour['slug'])) ?>" class="text-decoration-none" style="color:inherit;"><?= e($tour['name']) ?></a>
                                </h2>
                                <p class="card-excerpt small flex-grow-1">
                                    <?= e(excerpt_words((string) ($tour['excerpt'] ?: $tour['meta_description'] ?? ''), 25)) ?>
                                </p>
                            </div>
                            <div class="card-footer d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="card-price-label mb-0" style="margin-bottom:0;"><?= e($durationLabel ?: 'Full day') ?></span>
                                    <span class="card-price" style="font-size:var(--text-sm);"><?= e($priceLabel ?: 'From $85') ?></span>
                                </div>
                                <a href="<?= e(base_url('day-tours/' . $tour['slug'])) ?>" class="btn btn-primary w-100">View Details & Book</a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Results count -->
            <p class="text-secondary text-center mt-4 small">
                Showing <strong><?= count($dayTours) ?></strong> day tour<?= count($dayTours) !== 1 ? 's' : '' ?>
                <?php if ($hasActiveFilters): ?>
                    filtered by your selection
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     CTA
════════════════════════════════════════════════════════════ -->
<?php
$ctaTitle = 'Want a Custom Day Tour or Multi-Day Combination?';
$ctaText = 'Tell us your dates, base hotel, and interests — we\'ll design a private day tour or chain several into a flexible multi-day route with your chauffeur-guide.';
schema_output($schemaGraph);
require __DIR__ . '/includes/cta.php';
require __DIR__ . '/includes/footer.php';
