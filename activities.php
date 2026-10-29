<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/activity_pages.php';

$hub = activity_hub();
if (!$hub) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}
$categories = activity_published_categories();
$pageUrl = base_url('activities');
$seoCanonical = $pageUrl;
$seoTitle = (string) ($hub['meta_title'] ?: 'Activities in Sri Lanka | ' . APP_NAME);
$seoDescription = (string) ($hub['meta_description'] ?: $hub['excerpt']);
$seoImage = activity_page_image($hub, 'og');
$seoType = 'website';
$bodyClass = 'page-activities ict-detail-page';
$extraStylesheets[] = 'assets/css/activities.css';

$itemList = [];
foreach ($categories as $position => $category) {
    $itemList[] = ['@type' => 'ListItem', 'position' => $position + 1, 'name' => $category['name'], 'url' => activity_page_url($category)];
}
$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs([['name'=>'Home','url'=>base_url()],['name'=>'Activities','url'=>$pageUrl]]),
    ['@type'=>'CollectionPage','@id'=>$pageUrl.'#activities','url'=>$pageUrl,'name'=>$seoTitle,'description'=>$seoDescription,
        'mainEntity'=>['@type'=>'ItemList','itemListElement'=>$itemList]],
];
require __DIR__ . '/includes/header.php';
?>
<div class="activities-page">
    <section class="activities-hero">
        <?php if (!empty($hub['hero_image'])): ?><img class="activities-hero-image" src="<?= e(stored_media_url($hub['hero_image'])) ?>" alt="" aria-hidden="true"><?php endif; ?>
        <div class="activities-hero-shade"></div>
        <div class="activities-shell activities-hero-content">
            <nav class="activities-breadcrumb" aria-label="Breadcrumb"><a href="<?= e(base_url()) ?>">Home</a><span aria-hidden="true">/</span><span aria-current="page">Activities</span></nav>
            <p class="activities-eyebrow">Explore Sri Lanka</p>
            <h1><?= e($hub['name']) ?></h1>
            <p class="activities-lead"><?= e($hub['excerpt']) ?></p>
        </div>
    </section>
    <section class="activities-shell activities-section" aria-labelledby="activities-categories-title">
        <div class="activities-section-heading"><div><p class="activities-eyebrow activities-eyebrow-dark">Find your interest</p><h2 id="activities-categories-title">Activities across the island</h2></div><p>Choose a category to see a short introduction and the destinations where it is available.</p></div>
        <div class="activities-grid">
            <?php foreach ($categories as $category): ?>
                <?php $cover = trim((string)($category['cover_image'] ?? '')); ?>
                <a class="activity-card" href="<?= e(activity_page_url($category)) ?>">
                    <span class="activity-card-media">
                        <?php if ($cover !== ''): ?><img src="<?= e(stored_media_url($cover)) ?>" alt="<?= e((string)($category['cover_alt'] ?? $category['name'])) ?>" loading="lazy" decoding="async"><?php else: ?><span class="activity-card-placeholder" aria-hidden="true"></span><?php endif; ?>
                        <span class="activity-card-arrow" aria-hidden="true">↗</span>
                    </span>
                    <span class="activity-card-copy"><span class="activity-card-title"><?= e($category['name']) ?></span><span class="activity-card-summary"><?= e($category['excerpt']) ?></span><span class="activity-card-link">Explore destinations <span aria-hidden="true">→</span></span></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@graph'=>$schemaGraph], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
