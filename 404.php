<?php
declare(strict_types=1);

http_response_code(404);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

$seoTitle = 'Page Not Found | ' . APP_NAME;
$seoDescription = 'The page you are looking for could not be found. Explore destinations, tours, and itineraries with Island Culture Travels.';
$seoCanonical = base_url(ltrim(request_path(), '/'));
$seoImage = DEFAULT_OG_IMAGE;
$seoType = 'website';
$seoRobots = 'noindex,follow';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => '404', 'url' => ''],
];

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    [
        '@type' => 'WebPage',
        'name' => '404 - Page Not Found',
        'url' => $seoCanonical,
        'isPartOf' => ['@id' => base_url() . '#website'],
    ],
];

$bodyClass = 'page-404';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media page-hero-404" style="min-height:520px;">
    <?= responsive_image([
        'src' => DEFAULT_OG_IMAGE,
        'alt' => 'Page not found',
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
        <div class="hero-content" style="text-align:center;max-width:720px;margin:0 auto;">
            <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
            <div style="display:inline-flex;align-items:center;justify-content:center;width:5rem;height:5rem;border-radius:50%;background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.18);color:#fff;font-size:2rem;font-weight:800;margin-bottom:1rem;">404</div>
            <h1 class="hero-title">Page Not <em>Found</em></h1>
            <div class="hero-summary-box" style="margin-left:auto;margin-right:auto;">
                <p class="lead mb-0">
                    The page you requested does not exist, may have moved, or the URL might be incorrect.
                </p>
            </div>
            <div class="hero-actions mt-4 d-flex gap-2 justify-content-center flex-wrap">
                <a class="btn btn-primary" href="<?= e(base_url()) ?>">Back to Home</a>
                <a class="btn btn-ghost" href="<?= e(base_url('contact')) ?>">Contact Us</a>
            </div>
        </div>
    </div>
</section>

<section class="py-5" style="background:var(--surface-alt);">
    <div class="container" style="max-width:900px;">
        <div class="card" style="border-radius:var(--radius-lg);padding:2rem;">
            <span class="section-kicker">Explore</span>
            <h2 class="section-title h4 mb-3">Try one of these pages instead</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <a class="btn btn-outline-dark w-100" href="<?= e(base_url('destinations')) ?>">All Destinations</a>
                </div>
                <div class="col-md-6">
                    <a class="btn btn-outline-dark w-100" href="<?= e(base_url('experiences')) ?>">Experiences</a>
                </div>
                <div class="col-md-6">
                    <a class="btn btn-outline-dark w-100" href="<?= e(base_url('itineraries')) ?>">All Itineraries</a>
                </div>
                <div class="col-md-6">
                    <a class="btn btn-primary w-100" href="<?= e(base_url('contact')) ?>">Plan My Tour</a>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-4 justify-content-center">
                <a class="small section-link" href="<?= e(base_url('seasonal')) ?>">When to Visit</a>
                <span class="text-secondary">·</span>
                <a class="small section-link" href="<?= e(base_url('plan')) ?>">Trip Planner</a>
                <span class="text-secondary">·</span>
                <a class="small section-link" href="<?= e(base_url('faq')) ?>">FAQ</a>
                <span class="text-secondary">·</span>
                <a class="small section-link" href="<?= e(base_url('reviews')) ?>">Reviews</a>
            </div>
        </div>
    </div>
</section>

<?php
$ctaTitle = 'Need Help Finding the Right Page?';
$ctaText = 'Message us with your travel dates and interests. We will guide you to the best destinations, tours, and itinerary options.';
require __DIR__ . '/includes/cta.php';

schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
