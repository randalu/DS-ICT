<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/activity_pages.php';
require_once __DIR__ . '/includes/activity_gallery_view.php';

$slug = trim((string) get_input('slug', ''));
$activity = activity_page_by_slug($slug);
if (!$activity) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}
$examples = activity_public_destinations(activity_examples((int)$activity['id']));
$galleryImages=array_values(array_filter(activity_category_images((int)$activity['id']),static fn($photo)=>activity_gallery_file_exists((string)($photo['file_path']??''))));
$galleries=[];foreach($galleryImages as $photo){$galleries[(int)$photo['activity_example_id']][]=$photo;}
$pageUrl = activity_page_url($activity);
$seoCanonical = $pageUrl;
$seoTitle = (string) ($activity['meta_title'] ?: ($activity['name'] . ' in Sri Lanka | ' . APP_NAME));
$seoDescription = (string) ($activity['meta_description'] ?: $activity['excerpt']);
$seoImage = activity_page_image($activity, 'og');
$seoType = 'article';
$bodyClass = 'page-activity ict-detail-page';
$extraStylesheets[] = 'assets/css/activities.css';
$breadcrumbs = [['name'=>'Home','url'=>base_url()],['name'=>'Activities','url'=>base_url('activities')],['name'=>$activity['name'],'url'=>$pageUrl]];
$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    ['@type'=>'WebPage','@id'=>$pageUrl.'#webpage','url'=>$pageUrl,'name'=>$seoTitle,'description'=>$seoDescription,'isPartOf'=>['@id'=>base_url().'#website'],
        'image'=>array_values(array_map(static fn($photo)=>['@type'=>'ImageObject','contentUrl'=>stored_media_url((string)$photo['file_path']),'description'=>$photo['alt_text']],array_filter($galleryImages,static fn($photo)=>!empty($photo['file_path']))))],
];
require_once __DIR__.'/includes/page_gallery_view.php';
$pageGallery=page_gallery_rows('activity',(int)$activity['id']);
$seoImageAlt=(string)($activity['og_alt']??'');
$extraStylesheets[]='assets/css/page-gallery.css';
if(!empty($activity['og_image']))$seoImage=stored_media_url($activity['og_image']);
if($pageGallery)$schemaGraph[]=['@type'=>'ImageGallery','name'=>$activity['name'].' photographs','image'=>page_gallery_schema($pageGallery)];
require __DIR__ . '/includes/header.php';
?>
<div class="activities-page">
    <section class="activities-hero activities-hero-detail">
        <?php if (!empty($activity['hero_image'])): ?><img class="activities-hero-image" src="<?= e(stored_media_url($activity['hero_image'])) ?>" alt="" aria-hidden="true"><?php endif; ?>
        <div class="activities-hero-shade"></div>
        <div class="activities-shell activities-hero-content">
            <nav class="activities-breadcrumb" aria-label="Breadcrumb"><a href="<?= e(base_url()) ?>">Home</a><span aria-hidden="true">/</span><a href="<?= e(base_url('activities')) ?>">Activities</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($activity['name']) ?></span></nav>
            <p class="activities-eyebrow">Sri Lanka Activity Guide</p>
            <h1><?= e($activity['name']) ?></h1>
            <p class="activities-lead"><?= e($activity['excerpt']) ?></p>
        </div>
    </section>
<section class="activities-shell activities-section activity-examples" aria-labelledby="activity-examples-title">
        <div class="activities-section-heading"><div><p class="activities-eyebrow activities-eyebrow-dark">Where to find it</p><h2 id="activity-examples-title"><?= e($activity['name']) ?> destinations</h2></div><a class="activities-back-link" href="<?= e(base_url('activities')) ?>">Browse all activities</a></div>
        <?php if ($examples): ?><ol class="activity-example-list">
            <?php foreach ($examples as $example): ?>
                <?php $photos=$galleries[(int)$example['id']]??[]; ?>
                <li id="activity-entry-<?= (int)$example['id'] ?>" class="activity-example<?= $photos?' activity-example-with-gallery':'' ?>">
                    <div class="activity-example-copy">
                    <span class="activity-example-number" aria-hidden="true"><?= e(str_pad((string)($example['sort_order'] + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                    <div><h3><?= e($example['title']) ?></h3><p class="activity-example-destinations">
                        <?php foreach (($example['destination_items'] ?? []) as $index => $destination): ?><?php if ($index): ?><span aria-hidden="true"> · </span><?php endif; ?><?php if ($destination['url'] !== ''): ?><a href="<?= e($destination['url']) ?>"><?= e($destination['name']) ?></a><?php else: ?><?= e($destination['name']) ?><?php endif; ?><?php endforeach; ?>
                    </p></div></div>
                    <?php activity_gallery_public($photos,(string)$example['title']); ?>
                </li>
            <?php endforeach; ?>
        </ol><?php else: ?><p>Destination information is being reviewed. Please check back soon.</p><?php endif; ?>
        <p class="activity-source-note">Availability, access, weather and seasonal operation can change. Confirm current arrangements before visiting.</p>
    </section>
</div>
<?php page_gallery_assets();page_gallery_public($pageGallery,$activity['name']); ?>
<script src="<?= e(base_url('assets/js/activity-gallery.js').'?v='.filemtime(__DIR__.'/assets/js/activity-gallery.js')) ?>" defer></script>
<script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@graph'=>$schemaGraph], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
