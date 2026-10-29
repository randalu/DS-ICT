<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

require_once __DIR__.'/includes/page_gallery_view.php';
$landing=blog_landing_record();
$pageGallery=page_gallery_rows('blog_landing',1);
$seoImageAlt=(string)($landing['og_alt']??'');
$extraStylesheets[]='assets/css/page-gallery.css';
record_page_view();

$page    = max(1, (int) get_input('page', 1));
$perPage = 9;
$offset  = ($page - 1) * $perPage;
$total   = count_published_blog_posts();
$posts   = fetch_published_blog_posts($perPage, $offset);

$seoTitle       = 'Travel Blog | Sri Lanka Travel Tips & Guides | ' . APP_NAME;
$seoDescription = 'Sri Lanka travel tips, destination guides, itinerary ideas, and cultural insights from the Island Culture Travels team.';
$seoCanonical   = base_url($page > 1 && $posts !== [] ? 'blog?page=' . $page : 'blog');
$seoTitle=trim((string)($landing['meta_title']??''))?:$seoTitle;
$seoDescription=trim((string)($landing['meta_description']??''))?:$seoDescription;
$seoImage=stored_media_url(content_image_path($landing,['og_image','hero_image','cover_image']));
$seoType        = 'website';
$bodyClass      = 'page-blog';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Blog', 'url' => base_url('blog')],
];

$schemaItems = [];
foreach ($posts as $p) {
    $schemaItems[] = [
        'url'   => base_url('blog/' . $p['slug']),
        'name'  => (string) $p['title'],
        'image' => !empty($p['hero_image']) ? upload_url($p['hero_image']) : DEFAULT_OG_IMAGE,
    ];
}

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_item_list('Travel Blog Posts', $schemaItems),
];

if($pageGallery)$schemaGraph[]=['@type'=>'ImageGallery','name'=>$landing['title'].' photographs','image'=>page_gallery_schema($pageGallery)];
$ctaTitle=trim((string)$landing['cta_title'])?:'Plan Your Private Sri Lanka Tour';
$ctaText=trim((string)$landing['cta_text'])?:'Share your dates and interests with our team.';
$ctaWhatsappText = DEFAULT_WHATSAPP_TEXT;

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media page-hero-blog">
    <?= responsive_image([
        'src' => !empty($landing['hero_image'])?stored_media_url($landing['hero_image']):DEFAULT_OG_IMAGE,
        'alt' => (string)($landing['hero_alt']??'')?:$landing['title'],
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
            <div class="section-kicker mb-2" style="color:var(--accent-light);">Travel Journal</div>
            <h1 class="display-6 fw-bold hero-title mb-2"><?= e($landing['title']) ?></h1>
            <p class="lead mb-0" style="color:rgba(255,255,255,0.82);"><?= e(strip_tags((string)$landing['intro'])) ?></p>
        </div>
    </div>
</section>
<section class="py-5">
    <div class="container">
        <?php if (!$posts): ?>
            <p class="text-secondary text-center py-5">No posts yet. Check back soon!</p>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($posts as $post): ?>
            <?php
            $postImg = stored_media_url(content_image_path($post,['cover_image','hero_image','og_image'])) ?: DEFAULT_OG_IMAGE;
            ?>
            <div class="col-md-6 col-lg-4">
                <a href="<?= e(base_url('blog/' . $post['slug'])) ?>" class="text-decoration-none">
                    <div class="card h-100" style="border-radius:var(--radius-lg);overflow:hidden;">
                        <div class="card-thumb">
                            <?= responsive_image([
                                'src' => $postImg,
                                'alt' => trim((string)($post['cover_alt']??''))?:$post['title'],
                                'widths' => [400, 800],
                                'sizes' => '(min-width: 1200px) 33vw, (min-width: 768px) 50vw, 100vw',
                                'width' => 800,
                                'height' => 533,
                                'class' => 'card-img-top',
                                'loading' => 'lazy',
                            ]) ?>
                        </div>
                        <div class="card-body">
                            <div class="small text-secondary mb-1" style="color:var(--muted);"><?= e(date('d M Y', strtotime((string)($post['published_at'] ?: $post['created_at'])))) ?></div>
                            <h2 class="card-title h5 mb-2" style="color:var(--text);"><?= e($post['title']) ?></h2>
                            <?php if (!empty($post['excerpt'])): ?>
                            <p class="card-excerpt small mb-0"><?= e($post['excerpt']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
                            <span class="small section-link" style="color:var(--brand);">Read more →</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total > $perPage): ?>
        <nav class="mt-5 d-flex justify-content-center">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= e(base_url('blog?page=' . ($page - 1))) ?>">← Prev</a></li>
                <?php endif; ?>
                <?php for ($p = 1; $p <= ceil($total / $perPage); $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e(base_url('blog?page=' . $p)) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
                <?php if ($page < ceil($total / $perPage)): ?>
                <li class="page-item"><a class="page-link" href="<?= e(base_url('blog?page=' . ($page + 1))) ?>">Next →</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php
page_gallery_assets();page_gallery_public($pageGallery,(string)$landing['title']);
schema_output($schemaGraph);
require __DIR__ . '/includes/cta.php';
require __DIR__ . '/includes/footer.php';
