<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

$page = find_published_static_page_by_slug('why-choose-us');

$seoTitle = $page['meta_title'] ?? ('Why Choose Island Culture Travels for Private Sri Lanka Tours | ' . APP_NAME);
$seoDescription = $page['meta_description'] ?? 'Discover why travelers choose Island Culture Travels — private vehicles, professional chauffeurs, flexible itineraries, and reliable service across Sri Lanka.';
$seoCanonical = $page['canonical_url'] ?? base_url('why-choose-us');
$seoImage = !empty($page['hero_image']) ? upload_url($page['hero_image']) : DEFAULT_OG_IMAGE;
$seoType = 'website';
$faqItems = clean_public_faq_items(decode_faq_json_items((string) ($page['faq_json'] ?? '')));
$faqSchema = schema_faq_from_items($faqItems);
$heroImageUrl = !empty($page['hero_image']) ? upload_url((string) $page['hero_image']) : '';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Why Choose Us', 'url' => $seoCanonical],
];

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
];
if ($faqSchema) {
    $schemaGraph[] = $faqSchema;
}

$bodyClass = 'page-why';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media page-hero-why">
    <?= responsive_image([
        'src' => $heroImageUrl !== '' ? $heroImageUrl : DEFAULT_OG_IMAGE,
        'alt' => 'Why Choose Island Culture Travels',
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
            <span class="section-kicker" style="color:var(--accent-light);">Why Choose Us</span>
            <h1 class="hero-title">Why Choose Island Culture Travels —<br><em>Private. Professional. Reliable.</em></h1>
            <div class="hero-summary-box">
                <p class="lead mb-0">Travel Sri Lanka with a private chauffeur, comfortable vehicle, and flexible itinerary designed around your travel dates, group size, and interests.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if (!empty($page['content'])): ?>
            <div class="content-prose text-secondary mb-5" style="line-height:1.82;font-size:1.02rem;"><?= render_public_content($page['content']) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4"><div class="card h-100" style="border-radius:var(--radius-lg);padding:1.5rem;"><div class="trust-icon" style="width:3rem;height:3rem;font-size:1.2rem;margin-bottom:1rem;"><span aria-hidden="true">◆</span></div><h2 class="h5 card-title">Private tours only</h2><p class="card-excerpt mb-0">No crowded buses, no fixed group schedules, no mystery detours.</p></div></div>
            <div class="col-md-6 col-lg-4"><div class="card h-100" style="border-radius:var(--radius-lg);padding:1.5rem;"><div class="trust-icon" style="width:3rem;height:3rem;font-size:1.2rem;margin-bottom:1rem;"><span aria-hidden="true">◎</span></div><h2 class="h5 card-title">Flexible itineraries</h2><p class="card-excerpt mb-0">Routes built around your dates, interests, and pace.</p></div></div>
            <div class="col-md-6 col-lg-4"><div class="card h-100" style="border-radius:var(--radius-lg);padding:1.5rem;"><div class="trust-icon" style="width:3rem;height:3rem;font-size:1.2rem;margin-bottom:1rem;"><span aria-hidden="true">⬢</span></div><h2 class="h5 card-title">Comfortable vehicles</h2><p class="card-excerpt mb-0">Travel across Sri Lanka without turning the road into an endurance sport.</p></div></div>
            <div class="col-md-6 col-lg-4"><div class="card h-100" style="border-radius:var(--radius-lg);padding:1.5rem;"><div class="trust-icon" style="width:3rem;height:3rem;font-size:1.2rem;margin-bottom:1rem;"><span aria-hidden="true">◈</span></div><h2 class="h5 card-title">Driver-guide support</h2><p class="card-excerpt mb-0">Local insight, practical road planning, and less confusion at every stop.</p></div></div>
            <div class="col-md-6 col-lg-4"><div class="card h-100" style="border-radius:var(--radius-lg);padding:1.5rem;"><div class="trust-icon" style="width:3rem;height:3rem;font-size:1.2rem;margin-bottom:1rem;"><span aria-hidden="true">❤</span></div><h2 class="h5 card-title">Family-friendly pacing</h2><p class="card-excerpt mb-0">Routes that make sense for couples, children, and multigenerational groups.</p></div></div>
            <div class="col-md-6 col-lg-4"><div class="card h-100" style="border-radius:var(--radius-lg);padding:1.5rem;"><div class="trust-icon" style="width:3rem;height:3rem;font-size:1.2rem;margin-bottom:1rem;"><span aria-hidden="true">✦</span></div><h2 class="h5 card-title">Direct communication</h2><p class="card-excerpt mb-0">Straight answers, practical planning, and less travel-agency theatre.</p></div></div>
        </div>
    </div>
</section>

<?php
if ($faqItems) {
    $faqHeading = 'Frequently Asked Questions';
    $faqAccordionId = 'whyFaqAccordion';
    $faqPrefix = 'why-faq';
    $faqCtaTitle = trim((string) ($page['cta_title'] ?? '')) ?: 'Need Help Planning Your Trip?';
    $faqCtaText = trim((string) ($page['cta_text'] ?? '')) ?: 'Tell us your dates and priorities. We will suggest the most practical private route.';
    $faqCtaHref = base_url('contact');
    require __DIR__ . '/includes/faq_section.php';
}

$ctaTitle = trim((string) ($page['cta_title'] ?? '')) ?: 'Plan Your Private Sri Lanka Journey Today';
$ctaText = trim((string) ($page['cta_text'] ?? '')) ?: 'Tell us your travel dates, group size, and destinations, and we will prepare a personalized travel plan with the right vehicle and professional chauffeur for your trip.';
require __DIR__ . '/includes/cta.php';

schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
