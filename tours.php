<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

// Filter params
$seoFilterCanonicalBase = base_url('tours');
$seoFilterParams = [];

$tours = db_table_exists('tours')
    ? fetch_all(
        "SELECT id, name, slug, excerpt, hero_image, og_image, meta_title, meta_description,
                " . pricing_select_fragment('tours') . "
         FROM tours
         WHERE is_published = 1
         ORDER BY is_featured DESC, sort_order ASC, name ASC"
    )
    : [];
$tourStyleCategories = array_values(array_filter(
    fetch_published_itinerary_categories_with_counts(),
    static function (array $category): bool {
        $slug = trim((string) ($category['slug'] ?? ''));
        if ($slug === '') {
            return false;
        }

        return itinerary_category_public_canonical_url($category) !== base_url('tours/' . $slug)
            || (int) ($category['itinerary_count'] ?? 0) > 0;
    }
));
$coverRecords = array_values(array_filter(
    array_map(static fn(array $row): array => package_card_record($row, 'tour'), $tours),
    static fn(array $row): bool => trim((string) ($row['cover_image'] ?? '')) !== ''
        && !is_placeholder_media_url((string) $row['cover_image'])
));
$heroImage = random_record_hero_image($coverRecords ?: $tours, ['cover_image', 'hero_image', 'og_image']);

$seoTitle = 'Sri Lanka Tours | Private Chauffeur-Guided Trips | Island Culture Travels';
$seoDescription = 'Browse private Sri Lanka tours, wildlife safaris, cultural routes, beach escapes, and flexible trip ideas arranged by Island Culture Travels.';
$seoCanonical = base_url('tours');
$seoImage = $heroImage;
$seoType = 'website';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Tours', 'url' => $seoCanonical],
];

$schemaItems = [];
foreach ($tours as $tour) {
    $schemaItems[] = [
        'url'   => base_url('tours/' . $tour['slug']),
        'name'  => (string) $tour['name'],
        'image' => (package_card_image($tour, 'tour') ?: DEFAULT_OG_IMAGE),
    ];
}

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_item_list('Sri Lanka Tours', $schemaItems),
];

$bodyClass = 'page-tours';
$extraStylesheets[] = 'assets/css/journey-layouts.css';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner" style="--hero-image: url('<?= e($heroImage) ?>');">
    <div class="container">
        <div class="hero-content">
            <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
            <span class="section-eyebrow" style="color:var(--accent-light);">Private Chauffeur-Guided Trips</span>
            <h1 class="hero-title section-title" style="color:#fff;">Browse Sri Lanka <em style="font-style:italic; color:var(--brand-200);">Tours</em></h1>
            <p class="lead section-lead" style="color:rgba(255,255,255,.86); max-width:48ch;">
                Start with a tour style, then shape the route around your dates, pace, hotels, and must-see places.
            </p>
            <?php
            $trustAudienceLabel = 'Private and Family Tours';
            $trustExtraLabel = 'Flexible tour planning';
            require __DIR__ . '/includes/trust_signals.php';
            ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="section-head" style="margin-bottom:2rem;">
            <div>
                <span class="section-eyebrow">Private Tours</span>
                <h2 class="section-title">Explore Sri Lanka Tours</h2>
                <p class="section-lead">Wildlife, culture, beaches and hill country — all with your own private driver and flexible pace.</p>
            </div>
            <a href="<?= e(base_url('contact')) ?>" class="section-link">Plan your trip →</a>
        </div>
        <?php if (!$tours): ?>
            <p class="text-secondary text-center py-4 mb-0">No tours published yet. Send us your dates and interests, and we will recommend a private route.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($tours as $tour): ?>
                    <?php
                    $img = (package_card_image($tour, 'tour') ?: DEFAULT_OG_IMAGE);
                    $priceLabel = format_price_range($tour, false);
                    $hasPrice = $priceLabel !== '';
                    ?>
                    <div class="col-md-6 col-xl-4 d-flex">
                        <a href="<?= e(base_url('tours/' . $tour['slug'])) ?>" class="card w-100" data-lightbox-ignore style="text-decoration:none; color:inherit;">
                            <div class="card-thumb">
                                <?= responsive_image([
                                    'src' => $img,
                                    'alt' => package_card_alt($tour, 'tour'),
                                    'widths' => [400, 800],
                                    'sizes' => '(min-width: 1200px) 33vw, (min-width: 768px) 50vw, 100vw',
                                    'width' => 800,
                                    'height' => 600,
                                    'loading' => 'lazy',
                                ]) ?>
                                <span class="card-badge">Private tour · Customisable</span>
                            </div>
                            <div class="card-body">
                                <h2 class="card-title h5"><?= e($tour['name']) ?></h2>
                                <p class="card-excerpt">
                                    <?= e(excerpt_words((string) ($tour['excerpt'] ?: $tour['meta_description'] ?? ''), 22)) ?>
                                </p>
                                <div class="card-footer">
                                    <?php if ($hasPrice): ?>
                                        <span class="card-price-label"><?= (float) ($tour['price_from'] ?? 0) > 0 ? 'Starting from' : 'Up to' ?></span>
                                        <span class="card-price"><?= e($priceLabel) ?></span>
                                    <?php else: ?>
                                        <span class="card-price-label">Private tour</span>
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

<?php if ($tourStyleCategories): ?>
<section class="py-4 bg-light border-top border-bottom">
    <div class="container">
        <div class="section-head mb-3">
            <div>
                <span class="section-eyebrow">Browse by style</span>
                <h2 class="section-title">Explore tour themes</h2>
            </div>
        </div>
        <div class="row g-3">
            <?php foreach ($tourStyleCategories as $category): ?>
                <?php
                $categoryPath = itinerary_category_public_canonical_url($category);
                $categoryDescription = trim((string) ($category['description'] ?? ''));
                ?>
                <div class="col-sm-6 col-lg-4">
                    <a class="related-link-card d-block h-100" href="<?= e($categoryPath) ?>">
                        <strong class="d-block mb-1"><?= e((string) $category['name']) ?></strong>
                        <?php if ($categoryDescription !== ''): ?>
                            <span class="small text-secondary"><?= e(excerpt_words($categoryDescription, 18)) ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
$ctaTitle = 'Need Help Choosing the Right Sri Lanka Tour?';
$ctaText = 'Tell us your travel dates and interests, and we will recommend the tour style and itinerary flow that fit your trip.';
schema_output($schemaGraph);
require __DIR__ . '/includes/cta.php';
require __DIR__ . '/includes/footer.php';
