<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

$page = find_published_static_page_by_slug('faq');
ensure_faq_entries_table();

$fallbackFaqItems = [
    [
        'question' => 'Can we customize the itinerary?',
        'answer' => 'Yes. Every private tour can be adjusted based on your dates, interests, travel pace, and group size.',
    ],
    [
        'question' => 'Do you provide airport pickup?',
        'answer' => 'Yes. Airport pickup and onward private travel support can be arranged.',
    ],
    [
        'question' => 'Are your tours suitable for families?',
        'answer' => 'Yes. We plan routes with sensible pacing for families, couples, and small groups.',
    ],
    [
        'question' => 'Can we book a vehicle with driver only?',
        'answer' => 'Yes. Depending on your plan, we can arrange private transport with driver support.',
    ],
];

/** @var array<int, array{question:string,answer:string}> $faqItems */
$faqItems = array_map(
    static fn(array $item): array => [
        'question' => trim((string) ($item['question'] ?? '')),
        'answer' => trim((string) ($item['answer'] ?? '')),
    ],
    fetch_published_faq_entries()
);
$faqItems = clean_public_faq_items($faqItems);

if (!$faqItems) {
    $faqItems = decode_faq_json_items((string) ($page['faq_json'] ?? ''));
}

if (!$faqItems) {
    $faqItems = $fallbackFaqItems;
}

$seoTitle = $page['meta_title'] ?? ('FAQ — Private Sri Lanka Tours with Chauffeur and Vehicle | ' . APP_NAME);
$seoDescription = $page['meta_description'] ?? 'Frequently asked questions about private Sri Lanka tours, chauffeur services, transport options, and itinerary planning with Island Culture Travels.';
$seoCanonical = $page['canonical_url'] ?? base_url('faq');
$seoImage = !empty($page['hero_image']) ? upload_url($page['hero_image']) : DEFAULT_OG_IMAGE;
$seoType = 'website';
$heroImageUrl = !empty($page['hero_image']) ? upload_url((string) $page['hero_image']) : '';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'FAQ', 'url' => $seoCanonical],
];

$faqSchema = null;
if ($faqItems) {
    $faqSchema = [
        '@type' => 'FAQPage',
        'mainEntity' => array_map(static function (array $item): array {
            return [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ];
        }, $faqItems),
    ];
}

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
];
if ($faqSchema) {
    $schemaGraph[] = $faqSchema;
}

$bodyClass = 'page-faq';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media page-hero-faq">
    <?= responsive_image([
        'src' => $heroImageUrl !== '' ? $heroImageUrl : DEFAULT_OG_IMAGE,
        'alt' => 'Frequently Asked Questions',
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
            <span class="section-kicker" style="color:var(--accent-light);">FAQ</span>
            <h1 class="hero-title">Frequently Asked Questions —<br><em>Private Sri Lanka Tours</em></h1>
            <div class="hero-summary-box">
                <p class="lead mb-0">Straight answers about private chauffeur-guided travel, vehicle options, and itinerary planning before you book.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container" style="max-width: 900px;">
        <?php if (!empty($page['content'])): ?>
            <div class="text-secondary mb-4"><?= render_public_content($page['content']) ?></div>
        <?php endif; ?>

        <?php
        $faqHeading = 'Frequently Asked Questions';
        $faqAccordionId = 'faqPageAccordion';
        $faqPrefix = 'faq-page';
        $faqCtaTitle = trim((string) ($page['cta_title'] ?? '')) ?: 'Need a Personalized Answer?';
        $faqCtaText = trim((string) ($page['cta_text'] ?? '')) ?: 'Send your travel dates and priorities. We will recommend the best private route and vehicle setup.';
        $faqCtaHref = base_url('contact');
        $faqRelatedLinks = [
            ['label' => 'Experiences & Hidden Gems', 'url' => base_url('experiences')],
            ['label' => 'Itineraries', 'url' => base_url('itineraries')],
            ['label' => 'Destinations', 'url' => base_url('destinations')],
            ['label' => 'Reviews', 'url' => base_url('reviews')],
        ];
        require __DIR__ . '/includes/faq_section.php';
        ?>
    </div>
</section>

<?php
$ctaTitle = trim((string) ($page['cta_title'] ?? '')) ?: 'Plan Your Private Sri Lanka Journey Today';
$ctaText = trim((string) ($page['cta_text'] ?? '')) ?: 'Tell us your travel dates, group size, and destinations, and we will prepare a personalized travel plan with the right vehicle and professional chauffeur for your trip.';
require __DIR__ . '/includes/cta.php';

schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
