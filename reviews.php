<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';
require_once __DIR__ . '/includes/review_widget_snippets.php';

ensure_reviews_schema();

$page = max(1, (int) get_input('page', 1));
$perPage = 4;
$offset = paginate_offset($page, $perPage);

$publicReviewWhere = "
    is_published = 1
    AND customer_name NOT LIKE '%test%'
    AND customer_name NOT LIKE '%Test%'
    AND customer_name NOT LIKE '%remove%'
    AND customer_name NOT LIKE '%Remove%'
";

$totalReviews = (int) (fetch_one("SELECT COUNT(*) AS total FROM reviews WHERE {$publicReviewWhere}")['total'] ?? 0);
$pages = max(1, (int) ceil($totalReviews / $perPage));

$reviews = fetch_all("
    SELECT id, customer_name, country, country_code, rating, review_title, review_text, trip_type,
           tour_done_date, tour_done_month, review_source, review_source_url, created_at
    FROM reviews
    WHERE {$publicReviewWhere}
    ORDER BY COALESCE(tour_done_date, STR_TO_DATE(CONCAT(tour_done_month, '-01'), '%Y-%m-%d'), created_at) DESC, id DESC
    LIMIT {$perPage} OFFSET {$offset}
");

$reviewPhotos = fetch_review_photos_for_reviews(array_column($reviews, 'id'));

$heroPool = fetch_all("
    SELECT rp.image_path
    FROM review_photos rp
    INNER JOIN reviews r ON r.id = rp.review_id
    WHERE {$publicReviewWhere}
    ORDER BY RAND()
    LIMIT 12
");
$heroImage = random_record_hero_image($heroPool, ['image_path'], DEFAULT_OG_IMAGE);

$reviewSummary = fetch_public_review_summary();
$reviewAverage = (float) ($reviewSummary['average'] ?? 0);
$reviewCount = (int) ($reviewSummary['count'] ?? 0);
$tripadvisorReviewsWidget = tripadvisor_reviews_panel_widget();

$seoTitle = 'Traveler Reviews | Island Culture Travels';
$seoDescription = 'Read real traveler reviews for private Sri Lanka tours with Island Culture Travels, including customer photos, review sources, and trip dates.';
$seoCanonical = base_url('reviews' . ($page > 1 ? '?page=' . $page : ''));
$seoImage = $heroImage ?: DEFAULT_OG_IMAGE;
$seoImageWidth = 1200;
$seoImageHeight = 630;
$seoType = 'website';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Reviews', 'url' => base_url('reviews')],
];

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
];

if ($reviewCount > 0 && $reviewAverage > 0) {
    $schemaGraph[] = [
        '@type' => 'AggregateRating',
        'itemReviewed' => [
            '@type' => 'TravelAgency',
            '@id' => base_url() . '#business',
            'name' => APP_NAME,
            'url' => base_url(),
        ],
        'ratingValue' => number_format($reviewAverage, 1, '.', ''),
        'reviewCount' => $reviewCount,
        'bestRating' => 5,
        'worstRating' => 1,
    ];
}

foreach ($reviews as $r) {
    $schemaGraph[] = schema_review([
        'author' => $r['customer_name'],
        'rating' => (float) $r['rating'],
        'text' => (string) ($r['review_text'] ?? ''),
        'date_iso' => (string) ($r['tour_done_date'] ?: $r['created_at']),
    ]);
}

$formatReviewDate = static function (array $review): string {
    $date = trim((string) ($review['tour_done_date'] ?? ''));
    if ($date !== '') {
        $ts = strtotime($date);
        return $ts ? date('F Y', $ts) : $date;
    }
    $month = trim((string) ($review['tour_done_month'] ?? ''));
    if ($month !== '') {
        $dt = DateTime::createFromFormat('Y-m', $month);
        return $dt ? $dt->format('F Y') : $month;
    }
    return '';
};

$bodyClass = 'page-reviews';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media page-hero-reviews">
    <?= responsive_image([
        'src' => $heroImage,
        'alt' => 'Traveler review photos from Sri Lanka tours',
        'widths' => [800, 1200, 1600],
        'sizes' => '100vw',
        'width' => 1600,
        'height' => 900,
        'loading' => 'eager',
        'fetchpriority' => 'high',
        'class' => 'page-hero-bg-img',
        'picture_class' => 'page-hero-media',
    ]) ?>
    <div class="container">
        <div class="hero-content">
            <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
            <span class="section-kicker" style="color:var(--accent-light);">Traveler Reviews</span>
            <h1 class="hero-title">Guest Stories From <em>Sri Lanka</em></h1>
            <div class="hero-summary-box">
                <p class="lead mb-0">Real traveler feedback from private chauffeur-guided tours, with original review titles, sources, trip dates, and customer-uploaded tour photos where available.</p>
            </div>
            <?php if ($reviewCount > 0): ?>
                <div class="hero-meta mt-3">
                    <span class="badge-soft">Rating <?= e(number_format($reviewAverage, 1, '.', '')) ?></span>
                    <span class="badge-soft"><?= e((string) $reviewCount) ?> published reviews</span>
                    <span class="badge-soft">Private Sri Lanka tours</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if ($tripadvisorReviewsWidget !== ''): ?>
            <div class="tripadvisor-review-widget-panel">
                <div>
                    <span class="section-kicker">TripAdvisor Reviews</span>
                    <h2>Traveler Feedback on TripAdvisor</h2>
                    <p>Independent TripAdvisor profile summary for Island Culture Travels.</p>
                </div>
                <div class="tripadvisor-review-widget">
                    <?= $tripadvisorReviewsWidget ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($reviews): ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <?php
                    $reviewId = (int) $review['id'];
                    $photos = $reviewPhotos[$reviewId] ?? [];
                    $reviewTitle = trim((string) ($review['review_title'] ?? '')) ?: 'Traveler review';
                    $flag = country_flag_emoji($review['country_code'] ?? '');
                    $dateLabel = $formatReviewDate($review);
                    $source = review_source_label($review['review_source'] ?? '');
                    $sourceIcon = review_source_icon($review['review_source'] ?? '');
                    ?>
                    <article class="review-card traveler-review-card">
                        <header class="traveler-review-head review-author">
                            <div class="review-avatar traveler-review-avatar"><?= e(strtoupper(substr((string) $review['customer_name'], 0, 1))) ?></div>
                            <div>
                                <div class="review-name traveler-review-author">
                                    <?= e($review['customer_name']) ?>
                                    <?php if ($flag !== ''): ?><span class="review-flag" title="<?= e((string) ($review['country'] ?? '')) ?>"><?= e($flag) ?></span><?php endif; ?>
                                </div>
                                <div class="review-type traveler-review-meta">
                                    <?= e(trim(($review['country'] ?: '') . ($review['trip_type'] ? ' | ' . $review['trip_type'] : ''))) ?>
                                </div>
                            </div>
                            <span class="review-source-chip review-source-chip--labeled" title="<?= e($source) ?>">
                                <span><?= e($sourceIcon) ?></span>
                                <span><?= e($source) ?></span>
                            </span>
                        </header>

                        <?php if ($photos): ?>
                            <div class="traveler-review-gallery">
                                <?php $visiblePhotos = array_slice($photos, 0, 5); ?>
                                <?php $primaryPhoto = $visiblePhotos[0]; ?>
                                <div class="traveler-review-photo-main">
                                    <?= responsive_image([
                                        'src' => stored_media_url((string) $primaryPhoto['image_path']),
                                        'alt' => (string) ($primaryPhoto['alt_text'] ?: $reviewTitle),
                                        'widths' => [400, 800],
                                        'sizes' => '(min-width: 992px) 360px, 100vw',
                                        'width' => 800,
                                        'height' => 533,
                                        'loading' => 'lazy',
                                    ]) ?>
                                    <?php if (count($visiblePhotos) > 1): ?>
                                        <span class="review-photo-count">1/<?= e((string) count($visiblePhotos)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (count($visiblePhotos) > 1): ?>
                                    <div class="traveler-review-thumbs" aria-label="Additional customer tour photos">
                                        <?php foreach (array_slice($visiblePhotos, 1) as $photo): ?>
                                            <?= responsive_image([
                                                'src' => stored_media_url((string) $photo['image_path']),
                                                'alt' => (string) ($photo['alt_text'] ?: $reviewTitle),
                                                'widths' => [400, 800],
                                                'sizes' => '(min-width: 992px) 360px, 100vw',
                                                'width' => 800,
                                                'height' => 533,
                                                'loading' => 'lazy',
                                            ]) ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="traveler-review-body">
                            <div class="traveler-review-score">
                                <div class="review-stars review-dot-rating" aria-label="<?= (int) $review['rating'] ?> out of 5">
                                    <?= str_repeat('<span></span>', max(1, (int) $review['rating'])) ?>
                                </div>
                                <span><?= (int) $review['rating'] ?>/5</span>
                            </div>
                            <h2 class="traveler-review-title"><?= e($reviewTitle) ?></h2>
                            <p class="review-text traveler-review-text">"<?= e($review['review_text']) ?>"</p>
                            <?php if ($dateLabel !== ''): ?>
                                <p class="review-date mb-0">Date of experience: <?= e($dateLabel) ?></p>
                            <?php endif; ?>
                            <div class="review-original-row">
                                <span class="review-source-logo"><?= e($sourceIcon) ?></span>
                                <span><?= e($source) ?></span>
                                <?php if (!empty($review['review_source_url'])): ?>
                                    <a href="<?= e((string) $review['review_source_url']) ?>" target="_blank" rel="noopener nofollow">View original review</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($pages > 1): ?>
                <nav class="mt-4" aria-label="Review pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item<?= $i === $page ? ' active' : '' ?>">
                                <a class="page-link" href="<?= e(base_url('reviews' . ($i > 1 ? '?page=' . $i : ''))) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-light border">No published reviews yet.</div>
        <?php endif; ?>
    </div>
</section>

<?php
$ctaTitle = 'Plan Your Private Sri Lanka Journey Today';
$ctaText = 'Tell us your travel dates, group size, and destinations, and we will prepare a personalized travel plan with the right vehicle and professional chauffeur for your trip.';
require __DIR__ . '/includes/cta.php';

schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
