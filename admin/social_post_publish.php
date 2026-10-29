<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/social_posts.php');
}

guard_post_size();
require_csrf();
ensure_social_marketing_schema();

$id = (int) post_input('id', 0);
$platform = strtolower(trim((string) post_input('platform', '')));
$platformOptions = social_media_platform_options();

if ($id <= 0 || !isset($platformOptions[$platform])) {
    flash_set('error', 'Invalid publish request.');
    redirect('admin/social_posts.php');
}

$post = fetch_one('SELECT * FROM social_media_posts WHERE id = :id LIMIT 1', ['id' => $id]);
if (!$post) {
    flash_set('error', 'Social draft not found.');
    redirect('admin/social_posts.php');
}

$assets = fetch_all(
    'SELECT m.id, m.file_path, m.mime_type, m.alt_text
     FROM social_media_post_assets spa
     INNER JOIN media_assets m ON m.id = spa.asset_id
     WHERE spa.post_id = :post_id
     ORDER BY spa.sort_order ASC, spa.id ASC',
    ['post_id' => $id]
);

$title = trim((string) ($post['title'] ?? ''));
$description = trim((string) ($post['description'] ?? ''));
$hashtags = trim((string) ($post['hashtags'] ?? ''));

$lines = array_filter([$title, $description, $hashtags], static fn(string $line): bool => $line !== '');
$postText = implode("\n\n", $lines);

$mediaUrls = [];
$imageAssetPath = null;
foreach ($assets as $asset) {
    $path = trim((string) ($asset['file_path'] ?? ''));
    if ($path === '') {
        continue;
    }
    $mediaUrls[] = stored_media_url($path);
    $mimeType = strtolower((string) ($asset['mime_type'] ?? ''));
    if ($imageAssetPath === null && str_starts_with($mimeType, 'image/')) {
        $imageAssetPath = $path;
    }
}

$adminUser = admin_user();
$adminUserId = (int) ($adminUser['id'] ?? 0);

if ($platform === 'blog') {
    if (!db_table_exists('blog_posts')) {
        flash_set('error', 'Blog posts table is not available in this database.');
        redirect('admin/social_posts.php');
    }

    $existingBlogPostId = (int) ($post['blog_post_id'] ?? 0);
    $contentBlocks = [];

    if ($description !== '') {
        $contentBlocks[] = '<p>' . nl2br(e($description)) . '</p>';
    }

    foreach ($assets as $asset) {
        $path = trim((string) ($asset['file_path'] ?? ''));
        if ($path === '') {
            continue;
        }
        $assetUrl = stored_media_url($path);
        $mimeType = strtolower((string) ($asset['mime_type'] ?? ''));
        $alt = trim((string) ($asset['alt_text'] ?? $title));

        if (str_starts_with($mimeType, 'image/')) {
            $contentBlocks[] = '<p><img src="' . e($assetUrl) . '" alt="' . e($alt) . '" style="max-width:100%;height:auto;"></p>';
        } elseif (str_starts_with($mimeType, 'video/')) {
            $contentBlocks[] = '<p><a href="' . e($assetUrl) . '" target="_blank" rel="noopener">Watch related video</a></p>';
        }
    }

    if ($hashtags !== '') {
        $contentBlocks[] = '<p>' . e($hashtags) . '</p>';
    }

    $content = implode("\n", $contentBlocks);
    $excerpt = mb_substr(trim(strip_tags($description !== '' ? $description : $title)), 0, 480);

    $slugBase = slugify($title !== '' ? $title : ('social-post-' . $id));
    $slug = $slugBase . '-social-' . $id;

    if ($existingBlogPostId > 0) {
        execute_query(
            "UPDATE blog_posts
             SET title = :title,
                 excerpt = :excerpt,
                 content = :content,
                 hero_image = :hero_image,
                 meta_title = :meta_title,
                 meta_description = :meta_description,
                 updated_at = NOW()
             WHERE id = :id",
            [
                'title' => $title,
                'excerpt' => $excerpt !== '' ? $excerpt : null,
                'content' => $content,
                'hero_image' => $imageAssetPath,
                'meta_title' => $title . ' | ' . APP_NAME,
                'meta_description' => mb_substr($excerpt, 0, 300),
                'id' => $existingBlogPostId,
            ]
        );
        $blogPostId = $existingBlogPostId;
    } else {
        $checkSlug = fetch_one('SELECT id FROM blog_posts WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
        if ($checkSlug) {
            $slug .= '-' . time();
        }

        execute_query(
            "INSERT INTO blog_posts
            (category_id, title, slug, excerpt, content, hero_image, og_image, author, meta_title, meta_description, canonical_url, is_featured, is_published, published_at)
            VALUES
            (NULL, :title, :slug, :excerpt, :content, :hero_image, :og_image, :author, :meta_title, :meta_description, NULL, 0, 0, NULL)",
            [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $excerpt !== '' ? $excerpt : null,
                'content' => $content,
                'hero_image' => $imageAssetPath,
                'og_image' => $imageAssetPath,
                'author' => APP_NAME,
                'meta_title' => $title . ' | ' . APP_NAME,
                'meta_description' => mb_substr($excerpt, 0, 300),
            ]
        );
        $blogPostId = (int) last_insert_id();
    }

    execute_query(
        'UPDATE social_media_posts SET blog_post_id = :blog_post_id, status = :status, published_at = NOW() WHERE id = :id',
        [
            'blog_post_id' => $blogPostId,
            'status' => 'published',
            'id' => $id,
        ]
    );

    execute_query(
        "INSERT INTO social_publish_logs
        (post_id, platform, publish_status, response_json, error_message, retry_count, attempted_by)
        VALUES
        (:post_id, :platform, :publish_status, :response_json, NULL, 0, :attempted_by)",
        [
            'post_id' => $id,
            'platform' => $platform,
            'publish_status' => 'success',
            'response_json' => json_encode([
                'blog_post_id' => $blogPostId,
                'blog_edit_url' => base_url('admin/blog_post_form.php?id=' . $blogPostId),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'attempted_by' => $adminUserId > 0 ? $adminUserId : null,
        ]
    );

    flash_set('success', 'Published to Blog as a draft post (ID #' . $blogPostId . '). You can review/edit in Blog admin.');
    redirect('admin/social_posts.php');
}

$connection = fetch_one(
    'SELECT id, auth_mode, is_active FROM social_platform_connections WHERE platform = :platform LIMIT 1',
    ['platform' => $platform]
);

$shareUrls = [
    'facebook' => 'https://www.facebook.com/',
    'instagram' => 'https://www.instagram.com/',
    'youtube' => 'https://studio.youtube.com/',
    'pinterest' => 'https://www.pinterest.com/',
];

$landingUrl = $mediaUrls[0] ?? base_url();
if ($platform === 'facebook') {
    $landingUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($landingUrl);
} elseif ($platform === 'pinterest') {
    $landingUrl = 'https://www.pinterest.com/pin/create/button/?url=' . rawurlencode(base_url()) . '&description=' . rawurlencode($postText);
} else {
    $landingUrl = $shareUrls[$platform] ?? base_url();
}

$publishStatus = ((int) ($connection['is_active'] ?? 0) === 1 && strtolower((string) ($connection['auth_mode'] ?? 'manual')) === 'api')
    ? 'queued_for_api'
    : 'manual_required';

$payload = [
    'text' => $postText,
    'media_urls' => $mediaUrls,
    'target_platform' => $platform,
    'manual_share_url' => $landingUrl,
];

execute_query(
    "INSERT INTO social_publish_logs
    (post_id, platform, publish_status, response_json, error_message, retry_count, attempted_by)
    VALUES
    (:post_id, :platform, :publish_status, :response_json, NULL, 0, :attempted_by)",
    [
        'post_id' => $id,
        'platform' => $platform,
        'publish_status' => $publishStatus,
        'response_json' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'attempted_by' => $adminUserId > 0 ? $adminUserId : null,
    ]
);

if ($publishStatus === 'manual_required') {
    flash_set('success', 'Publish package prepared for ' . ucfirst($platform) . '. Open Social Channels for API setup, or post manually using the generated content.');
} else {
    flash_set('success', ucfirst($platform) . ' publish request queued for API delivery.');
}

redirect('admin/social_posts.php');
