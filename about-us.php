<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

$page = find_published_static_page_by_slug('about-us');

$seoTitle = $page['meta_title'] ?? ('About Island Culture Travels — Private Chauffeur-Guided Tours | ' . APP_NAME);
$seoDescription = $page['meta_description'] ?? 'Learn more about Island Culture Travels — a professional private chauffeur-guided travel service offering safe vehicles, local expertise, and flexible itineraries across Sri Lanka.';
$seoCanonical = $page['canonical_url'] ?? base_url('about-us');
$seoImage = !empty($page['hero_image']) ? upload_url($page['hero_image']) : DEFAULT_OG_IMAGE;
$seoType = 'website';
$faqItems = clean_public_faq_items(decode_faq_json_items((string) ($page['faq_json'] ?? '')));
$faqSchema = schema_faq_from_items($faqItems);
$heroImageUrl = !empty($page['hero_image']) ? upload_url((string) $page['hero_image']) : '';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'About Us', 'url' => $seoCanonical],
];

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
];
if ($faqSchema) {
    $schemaGraph[] = $faqSchema;
}

$bodyClass = 'page-about';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media page-hero-about">
    <?= responsive_image([
        'src' => $heroImageUrl !== '' ? $heroImageUrl : DEFAULT_OG_IMAGE,
        'alt' => 'About Island Culture Travels',
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
            <span class="section-kicker" style="color:var(--accent-light);">About Us</span>
            <h1 class="hero-title">About Island Culture Travels —<br><em>Private Chauffeur-Guided Travel</em></h1>
            <div class="hero-summary-box">
                <p class="lead mb-0">A professional private chauffeur service offering safe vehicles, expert local knowledge, and flexible travel planning across Sri Lanka.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container" style="max-width: 900px;">
        <?php if (!empty($page['content'])): ?>
            <div class="content-prose text-secondary" style="line-height:1.82;font-size:1.02rem;"><?= render_public_content($page['content']) ?></div>
        <?php else: ?>
            <div class="content-prose text-secondary" style="line-height:1.82;font-size:1.02rem;">
                <p>Island Culture Travels focuses on private Sri Lanka tours for couples, families, and small groups who want flexibility over cookie-cutter bus tourism.</p>
                <p>We build itineraries around your dates, interests, and pace, whether that means culture, wildlife, beaches, hill country, or a sensible mix of all four.</p>
                <p>Our approach is simple: comfortable travel, experienced driver-guides, direct communication, and itineraries that make practical sense on the road.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
if ($faqItems) {
    $faqHeading = 'Frequently Asked Questions';
    $faqAccordionId = 'aboutFaqAccordion';
    $faqPrefix = 'about-faq';
    $faqCtaTitle = trim((string) ($page['cta_title'] ?? '')) ?: 'Need Help Planning Your Trip?';
    $faqCtaText = trim((string) ($page['cta_text'] ?? '')) ?: 'Share your travel dates and goals. We will recommend the right private route and pacing.';
    $faqCtaHref = base_url('contact');
    require __DIR__ . '/includes/faq_section.php';
}

$ctaTitle = trim((string) ($page['cta_title'] ?? '')) ?: 'Plan Your Private Sri Lanka Journey Today';
$ctaText = trim((string) ($page['cta_text'] ?? '')) ?: 'Tell us your travel dates, group size, and destinations, and we will prepare a personalized travel plan with the right vehicle and professional chauffeur for your trip.';
require __DIR__ . '/includes/cta.php';

schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
