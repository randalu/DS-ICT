<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';

$slug = trim((string) get_input('slug', ''));
require_once __DIR__ . '/includes/retired_package_pages.php';
if (package_reference_path_retired($slug)) { package_reference_gone(); }

if ($slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
    require __DIR__ . '/404.php';
    exit;
}

$page = find_published_static_page_by_slug($slug);

if (!$page) {
    require __DIR__ . '/404.php';
    exit;
}

$pageTitle = trim((string) ($page['title'] ?? ''));
if ($pageTitle === '') {
    $pageTitle = ucwords(str_replace('-', ' ', $slug));
}

$seoTitle = trim((string) ($page['meta_title'] ?? ''));
if ($seoTitle === '') {
    $seoTitle = $pageTitle . ' | ' . APP_NAME;
}

$seoDescription = trim((string) ($page['meta_description'] ?? ''));
if ($seoDescription === '') {
    $seoDescription = $pageTitle . ' | ' . APP_NAME;
}

$seoCanonical = trim((string) ($page['canonical_url'] ?? ''));
$seoCanonical = static_page_public_url($slug, $seoCanonical);

$pageHeroMedia = content_hero_media($page);
$heroImageUrl = $pageHeroMedia['type'] === 'video'
    ? ($pageHeroMedia['poster_url'] ?: DEFAULT_OG_IMAGE)
    : ($pageHeroMedia['url'] ?: DEFAULT_OG_IMAGE);
$seoImage = $heroImageUrl !== '' ? $heroImageUrl : DEFAULT_OG_IMAGE;
$seoType = 'website';
$faqItems = clean_public_faq_items(decode_faq_json_items((string) ($page['faq_json'] ?? '')));
$faqSchema = schema_faq_from_items($faqItems);
$commercialPageConfig = match ($slug) {
    'private-driver-sri-lanka' => [
        'type' => 'service',
        'name' => 'Private Driver in Sri Lanka',
        'description' => $seoDescription,
        'service_type' => 'Private driver and chauffeur-guided Sri Lanka tours',
        'area' => 'Sri Lanka',
        'related_links' => [
            ['path' => 'private-driver-sri-lanka/cost', 'label' => 'Review private driver cost factors'],
            ['path' => 'itineraries', 'label' => 'Browse private Sri Lanka itineraries'],
            ['path' => 'contact', 'label' => 'Ask for a route and vehicle recommendation'],
        ],
    ],
    'private-driver-sri-lanka-cost' => [
        'type' => 'service',
        'name' => 'Sri Lanka Private Driver Cost',
        'description' => $seoDescription,
        'service_type' => 'Private driver and vehicle planning for Sri Lanka',
        'area' => 'Sri Lanka',
    ],
    'sri-lanka-private-tours' => [
        'type' => 'service',
        'name' => 'Sri Lanka Private Tours',
        'description' => $seoDescription,
        'service_type' => 'Private Sri Lanka tour planning',
        'area' => 'Sri Lanka',
        'related_links' => [
            ['path' => 'sri-lanka-tour-cost', 'label' => 'Understand private tour cost factors'],
            ['path' => 'tours', 'label' => 'Browse private tours'],
            ['path' => 'itineraries', 'label' => 'Compare itinerary ideas'],
            ['path' => 'contact', 'label' => 'Request a route-based estimate'],
        ],
    ],
    'sri-lanka-tour-cost' => [
        'type' => 'service',
        'name' => 'Sri Lanka Tour Cost',
        'description' => $seoDescription,
        'service_type' => 'Private Sri Lanka tour cost planning',
        'area' => 'Sri Lanka',
    ],
    'sri-lanka-airport-transfers' => [
        'type' => 'service',
        'name' => 'Sri Lanka Airport Transfers',
        'description' => $seoDescription,
        'service_type' => 'Private airport transfer and onward route planning',
        'area' => 'Sri Lanka',
        'related_links' => [
            ['path' => 'colombo-airport-to-sigiriya', 'label' => 'Colombo Airport to Sigiriya transfer'],
            ['path' => 'galle-to-colombo-airport-private-transfer', 'label' => 'Galle to Colombo Airport transfer'],
            ['path' => 'sigiriya-to-kandy-private-transfer', 'label' => 'Sigiriya to Kandy private transfer'],
            ['path' => 'yala-to-mirissa-private-transfer', 'label' => 'Yala to Mirissa private transfer'],
            ['path' => 'private-driver-sri-lanka', 'label' => 'Private driver planning'],
        ],
    ],
    'colombo-airport-to-sigiriya' => [
        'type' => 'service',
        'name' => 'Colombo Airport to Sigiriya Private Transfer',
        'description' => $seoDescription,
        'service_type' => 'Private Colombo Airport to Sigiriya transfer',
        'area' => 'Sri Lanka',
        'related_links' => [
            ['path' => 'sri-lanka-airport-transfers', 'label' => 'Sri Lanka airport transfers'],
            ['path' => 'destinations/sigiriya', 'label' => 'Plan time in Sigiriya'],
            ['path' => 'private-driver-sri-lanka', 'label' => 'Private driver options'],
        ],
    ],
    'sigiriya-to-kandy-private-transfer' => [
        'type' => 'service',
        'name' => 'Sigiriya to Kandy Private Transfer',
        'description' => $seoDescription,
        'service_type' => 'Private Sigiriya to Kandy route transfer',
        'area' => 'Sri Lanka',
        'related_links' => [
            ['path' => 'sri-lanka-airport-transfers', 'label' => 'Explore private transfer routes'],
            ['path' => 'sri-lanka-private-tours', 'label' => 'Build a private Sri Lanka tour'],
            ['path' => 'destinations/kandy', 'label' => 'Plan time in Kandy'],
            ['path' => 'kandy-to-ella-private-transfer', 'label' => 'Continue from Kandy to Ella'],
        ],
    ],
    'kandy-to-ella-private-transfer' => [
        'type' => 'service',
        'name' => 'Kandy to Ella Private Transfer',
        'description' => $seoDescription,
        'service_type' => 'Private Kandy to Ella road or train-support planning',
        'area' => 'Sri Lanka',
        'related_links' => [
            ['path' => 'experiences/kandy-to-ella-train', 'label' => 'Review the Kandy to Ella train experience'],
            ['path' => 'destinations/ella', 'label' => 'Plan time in Ella'],
            ['path' => 'private-driver-sri-lanka', 'label' => 'Private driver options'],
        ],
    ],
    'ella-to-yala-private-transfer' => [
        'type' => 'service',
        'name' => 'Ella to Yala Private Transfer',
        'description' => $seoDescription,
        'service_type' => 'Private Ella to Yala safari-base transfer',
        'area' => 'Sri Lanka',
        'related_links' => [
            ['path' => 'experiences/yala-leopard-safari', 'label' => 'Plan a Yala safari experience'],
            ['path' => 'ella-to-udawalawe-private-transfer', 'label' => 'Compare Ella to Udawalawe'],
            ['path' => 'sri-lanka-private-tours', 'label' => 'Build a private Sri Lanka tour'],
        ],
    ],
    'ella-to-udawalawe-private-transfer' => [
        'type' => 'service',
        'name' => 'Ella to Udawalawe Private Transfer',
        'description' => $seoDescription,
        'service_type' => 'Private Ella to Udawalawe safari-base transfer',
        'area' => 'Sri Lanka',
        'related_links' => [
            ['path' => 'experiences/udawalawe-elephant-safari', 'label' => 'Plan an Udawalawe safari experience'],
            ['path' => 'ella-to-yala-private-transfer', 'label' => 'Compare Ella to Yala'],
            ['path' => 'private-driver-sri-lanka', 'label' => 'Private driver options'],
        ],
    ],
    'yala-to-mirissa-private-transfer' => [
        'type' => 'service',
        'name' => 'Yala to Mirissa Private Transfer',
        'description' => $seoDescription,
        'service_type' => 'Private Yala to Mirissa south-coast transfer',
        'area' => 'Sri Lanka',
        'related_links' => [
            ['path' => 'destinations/mirissa', 'label' => 'Plan time in Mirissa'],
            ['path' => 'experiences/yala-leopard-safari', 'label' => 'Plan the safari before departure'],
            ['path' => 'sri-lanka-airport-transfers', 'label' => 'Review airport transfer options'],
        ],
    ],
    'galle-to-colombo-airport-private-transfer' => [
        'type' => 'service',
        'name' => 'Galle to Colombo Airport Private Transfer',
        'description' => $seoDescription,
        'service_type' => 'Private Galle to Colombo Airport departure transfer',
        'area' => 'Sri Lanka',
        'related_links' => [
            ['path' => 'sri-lanka-airport-transfers', 'label' => 'Sri Lanka airport transfers'],
            ['path' => 'destinations/galle', 'label' => 'Plan your Galle stay'],
            ['path' => 'private-driver-sri-lanka', 'label' => 'Private driver options'],
        ],
    ],
    default => null,
};

$commercialRelatedLinks = $commercialPageConfig['related_links'] ?? [
    ['path' => 'itineraries', 'label' => 'Browse private Sri Lanka itineraries'],
    ['path' => 'destinations', 'label' => 'Explore Sri Lanka destinations'],
    ['path' => 'experiences', 'label' => 'Find experiences for your route'],
    ['path' => 'contact', 'label' => 'Ask for a route and vehicle recommendation'],
];

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => $pageTitle, 'url' => $seoCanonical],
];

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    [
        '@type' => 'WebPage',
        'name' => $pageTitle,
        'url' => $seoCanonical,
        'isPartOf' => ['@id' => base_url() . '#website'],
    ],
];
if ($commercialPageConfig) {
    $schemaGraph[] = schema_service([
        'name' => $commercialPageConfig['name'],
        'meta_description' => $commercialPageConfig['description'],
        'excerpt' => $commercialPageConfig['description'],
        'og_image' => (string) ($page['hero_image'] ?? ''),
    ], $seoCanonical);
}
if ($faqSchema) {
    $schemaGraph[] = $faqSchema;
}

require_once __DIR__.'/includes/page_gallery_view.php';
$pageGallery=page_gallery_rows('page',(int)$page['id']);
$pageLayout=page_layout($page);
$seoImageAlt=(string)($page['og_alt']??'');
$extraStylesheets[]='assets/css/page-gallery.css';
if(!empty($page['og_image']))$seoImage=stored_media_url($page['og_image']);
if($pageGallery)$schemaGraph[]=['@type'=>'ImageGallery','name'=>$pageTitle.' photographs','image'=>page_gallery_schema($pageGallery)];
$bodyClass = 'page-static page-static-'.$pageLayout;
require __DIR__ . '/includes/header.php';
?>

<section class="py-5 bg-light border-bottom">
    <div class="container">
        <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
        <h1 class="display-6 fw-bold mb-3"><?= e($pageTitle) ?></h1>
        <?php if(!empty($page['intro'])): ?><div class="page-static-intro"><?= render_public_content((string)$page['intro']) ?></div><?php endif; ?>
        <?php if ($heroImageUrl !== ''): ?>
            <?php if ($pageHeroMedia['type'] === 'video' && $pageHeroMedia['url'] !== ''): ?>
                <video class="img-fluid rounded-4 shadow-sm mt-2" src="<?= e($pageHeroMedia['url']) ?>" poster="<?= e($pageHeroMedia['poster_url'] ?: $heroImageUrl) ?>" controls playsinline preload="metadata"></video>
            <?php else: ?>
            <img
                src="<?= e($heroImageUrl) ?>"
                alt="<?= e((string)($page['hero_alt']??'') ?: $pageTitle) ?>"
                class="img-fluid rounded-4 shadow-sm mt-2"
                loading="eager"
            >
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<section class="py-5">
    <div class="container page-static-body">
        <?php if (!empty($page['content'])): ?>
            <div class="text-secondary"><?= render_public_content((string) $page['content']) ?></div>
        <?php else: ?>
            <div class="detail-block-soft">
                <h2 class="h4 mb-2"><?= e($pageTitle) ?> Details</h2>
                <p class="text-secondary mb-3">Contact our team for current details about <?= e($pageTitle) ?> and how it fits into a private Sri Lanka tour.</p>
                <a href="<?= e(base_url('contact')) ?>" class="btn btn-success">Ask Us for Details</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php page_gallery_assets();page_gallery_public($pageGallery,$pageTitle); ?>

<?php if ($commercialPageConfig): ?>
    <section class="py-4 border-top">
        <div class="container" style="max-width: 900px;">
            <h2 class="h4 mb-3">Continue planning</h2>
            <div class="row g-3">
                <?php foreach ($commercialRelatedLinks as $relatedLink): ?>
                    <div class="col-md-6"><a class="related-link-card d-block h-100" href="<?= e(base_url((string) ($relatedLink['path'] ?? 'contact'))) ?>"><?= e((string) ($relatedLink['label'] ?? 'Continue planning')) ?></a></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
if ($faqItems) {
    $faqHeading = 'Frequently Asked Questions';
    $faqAccordionId = 'staticPageFaqAccordion';
    $faqPrefix = 'static-page-faq';
    $faqCtaTitle = trim((string) ($page['cta_title'] ?? '')) ?: 'Need Help Planning Your Tour?';
    $faqCtaText = trim((string) ($page['cta_text'] ?? '')) ?: 'Tell us your travel dates and interests. We will suggest a practical private route and vehicle setup.';
    $faqCtaHref = base_url('contact');
    require __DIR__ . '/includes/faq_section.php';
}

$ctaTitle = trim((string) ($page['cta_title'] ?? '')) ?: 'Plan Your Private Sri Lanka Tour';
$ctaText = trim((string) ($page['cta_text'] ?? '')) ?: 'Share your dates, group size, and interests. We will create a custom private itinerary with the right route and vehicle.';
require __DIR__ . '/includes/cta.php';

schema_output($schemaGraph);
require __DIR__ . '/includes/trust_links.php';
require __DIR__ . '/includes/footer.php';
