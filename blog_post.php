<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/toc.php';
require_once __DIR__ . '/includes/blog_widgets.php';

$slug = trim((string) get_input('slug', ''));
$isPreview = (string) get_input('preview', '') === '1';
if ($slug === '') {
    require __DIR__ . '/404.php';
    exit;
}

if (!db_table_exists('blog_posts')) {
    require __DIR__ . '/404.php';
    exit;
}

$publicationWhere = "AND bp.is_published = 1
       AND (bp.published_at IS NULL OR bp.published_at <= NOW())";
if ($isPreview) {
    require_once __DIR__ . '/includes/auth.php';
    admin_require_auth();
    $publicationWhere = '';
}

$hasBlogCategories = db_table_exists('blog_categories') && db_column_exists('blog_posts', 'category_id');
$categorySelect = $hasBlogCategories ? 'bc.name AS category_name, bc.slug AS category_slug' : 'NULL AS category_name, NULL AS category_slug';
$categoryJoin = $hasBlogCategories ? 'LEFT JOIN blog_categories bc ON bc.id = bp.category_id' : '';
$post = fetch_one(
    "SELECT bp.*, {$categorySelect}
     FROM blog_posts bp
     {$categoryJoin}
     WHERE bp.slug = :slug
       {$publicationWhere}
     LIMIT 1",
    ['slug' => $slug]
);

if (!$post) {
    require __DIR__ . '/404.php';
    exit;
}

$widgetsByPosition = widget_resolve_for_post($post);
$widgetContext = ['post' => $post];

$postUrl = !empty($post['canonical_url']) ? $post['canonical_url'] : base_url('blog/' . $post['slug']);
$postHeroMedia = content_hero_media($post);
$postHeroImage = $postHeroMedia['type'] === 'video'
    ? ($postHeroMedia['poster_url'] ?: DEFAULT_OG_IMAGE)
    : ($postHeroMedia['url'] ?: DEFAULT_OG_IMAGE);
$postImage = !empty($post['og_image']) ? upload_url($post['og_image']) : $postHeroImage;

$seoTitle = $post['meta_title'] ?: ($post['title'] . ' | ' . APP_NAME);
$seoDescription = $post['meta_description'] ?: ($post['excerpt'] ?: '');
$seoCanonical = $postUrl;
$seoImage = $postImage;
$seoType = 'article';
$seoRobots = $isPreview ? 'noindex,follow,noarchive,max-image-preview:large' : null;
$bodyClass = 'page-blog-post';
$recordPageView = !$isPreview;

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Blog', 'url' => base_url('blog')],
    ['name' => $post['title'], 'url' => $postUrl],
];

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_article([
        'title' => $post['title'] ?? '',
        'excerpt' => $post['excerpt'] ?? '',
        'meta_description' => $seoDescription,
        'image' => $postImage,
        'author' => $post['author'] ?? APP_NAME,
        'published_at' => $post['published_at'] ?? null,
        'created_at' => $post['created_at'] ?? null,
        'updated_at' => $post['updated_at'] ?? null,
        'about' => [
            'Sri Lanka private tours',
            'Sri Lanka travel planning',
            (string) ($post['category_name'] ?? 'Sri Lanka travel guide'),
        ],
    ], $postUrl, 'BlogPosting'),
];
$ctaWhatsappText = DEFAULT_WHATSAPP_TEXT;

require_once __DIR__.'/includes/page_gallery_view.php';
$pageGallery=page_gallery_rows('blog',(int)$post['id']);
$seoImageAlt=(string)($post['og_alt']??'');
$extraStylesheets[]='assets/css/page-gallery.css';
if(!empty($post['og_image']))$seoImage=stored_media_url($post['og_image']);
if($pageGallery)$schemaGraph[]=['@type'=>'ImageGallery','name'=>$post['title'].' photographs','image'=>page_gallery_schema($pageGallery)];
require __DIR__ . '/includes/header.php';
?>

<article>

<?php if ($isPreview): ?>
<div class="container pt-4">
    <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2 mb-0">
        <span><strong>Admin preview.</strong> This post ignores the public publish-date filter and is marked noindex.</span>
        <a class="btn btn-sm btn-outline-dark" href="<?= e(base_url('admin/blog_post_form.php?id=' . (int) $post['id'])) ?>">Edit post</a>
    </div>
</div>
<?php endif; ?>


<section class="page-hero-banner" style="--hero-image: url('<?= e($postHeroImage) ?>');">
    <?php if ($postHeroMedia['type'] === 'video' && $postHeroMedia['url'] !== ''): ?>
        <video class="page-hero-video" src="<?= e($postHeroMedia['url']) ?>" poster="<?= e($postHeroMedia['poster_url'] ?: $postHeroImage) ?>" autoplay muted loop playsinline preload="metadata"></video>
    <?php endif; ?>
    <div class="container">
        <div class="hero-content">
            <?php if (!empty($post['category_name'])): ?>
            <div class="section-kicker mb-2" style="color:var(--accent-light);"><?= e($post['category_name']) ?></div>
            <?php endif; ?>
            <?php if($pageGallery): require __DIR__.'/includes/breadcrumbs.php';endif; ?>
            <h1 class="display-6 fw-bold hero-title mb-2"><?= e($post['title']) ?></h1>
            <p class="small mb-0" style="color:rgba(255,255,255,0.70);">
                <?= e($post['author'] ?? APP_NAME) ?> | <?= e(date('d M Y', strtotime((string) ($post['published_at'] ?: $post['created_at'])))) ?>
            </p>
        </div>
    </div>
</section>
<section class="py-5">
    <div class="container">
        <div class="row g-5 justify-content-center">
            <?php
            $sidebarWidgets = $widgetsByPosition['sidebar'] ?? [];
            $inlineWidgets = $widgetsByPosition['inline_after_p'] ?? [];
            $belowWidgets = $widgetsByPosition['below_post'] ?? [];

            $blogContent = toc_inject_ids((string) $post['content']);
            $blogToc = render_toc($blogContent, ['min_headings' => 3]);
            $blogTocMobile = render_toc_mobile($blogContent, ['min_headings' => 3]);
            if ($inlineWidgets) {
                $blogContent = widget_splice_inline($blogContent, $inlineWidgets, $widgetContext);
            }
            $hasSidebar = ($blogToc !== '' || $sidebarWidgets);
            ?>
            <div class="col-lg-<?= $hasSidebar ? 8 : 10 ?> order-2 order-lg-1">
                <?php if (!empty($post['excerpt'])): ?>
                <p class="lead text-secondary border-start ps-3 mb-4" style="border-left:4px solid var(--brand) !important;"><?= e($post['excerpt']) ?></p>
                <?php endif; ?>

                <?= $blogTocMobile ?>
                <div class="content-prose" style="line-height:1.82;font-size:1.02rem;color:var(--text-body);">
                    <?= $blogContent /* Quill HTML sanitized on entry */ ?>
                </div>
            </div>

            <?php if ($hasSidebar): ?>
            <div class="col-lg-4 order-1 order-lg-2">
                <?= $blogToc ?>
                <?php foreach ($sidebarWidgets as $w): ?>
                    <?= widget_render($w, $widgetContext) ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($belowWidgets): ?>
        <div class="row mt-4">
            <div class="col-12">
                <?php foreach ($belowWidgets as $w): ?>
                    <?= widget_render($w, $widgetContext) ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php page_gallery_assets();page_gallery_public($pageGallery,$post['title']); ?>

</article>

<?php
schema_output($schemaGraph);
require __DIR__ . '/includes/cta.php';
require __DIR__ . '/includes/footer.php';
