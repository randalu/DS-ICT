<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/seasonal_guides.php');
}

guard_post_size();
require_csrf();
ensure_seasonal_guides_schema();

$month = strtolower(trim((string) post_input('month', '')));
if ($month === '' || !in_array($month, seasonal_allowed_months(), true)) {
    flash_set('error', 'Invalid month.');
    redirect('admin/seasonal_guides.php');
}

$title = trim((string) post_input('title', ''));
$intro = trim((string) post_input('intro', ''));
$metaTitle = trim((string) post_input('meta_title', ''));
$metaDescription = trim((string) post_input('meta_description', ''));
$highlightsText = (string) post_input('highlights', '');
$bestRegionsText = (string) post_input('best_regions', '');
$avoidRegionsText = (string) post_input('avoid_regions', '');
$isPublished = post_input('is_published') ? 1 : 0;

old_input_set($_POST);

if ($title === '') {
    $title = 'Sri Lanka in ' . ucfirst($month);
}

$highlights = textarea_lines_to_list($highlightsText, 30);
$bestRegions = textarea_lines_to_list($bestRegionsText, 30);
$avoidRegions = textarea_lines_to_list($avoidRegionsText, 30);

$existing = fetch_seasonal_guide($month, false) ?? [];
$heroImage = trim((string) ($existing['hero_image'] ?? ''));

try {
    if (!empty($_FILES['hero_image']['name'])) {
        $heroImage = upload_image($_FILES['hero_image'], 'seasonal');
    }

    $payload = [
        'month_key' => $month,
        'title' => $title,
        'intro' => $intro,
        'highlights_json' => $highlights ? json_encode($highlights, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
        'best_regions_json' => $bestRegions ? json_encode($bestRegions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
        'avoid_regions_json' => $avoidRegions ? json_encode($avoidRegions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
        'meta_title' => $metaTitle !== '' ? $metaTitle : null,
        'meta_description' => $metaDescription !== '' ? $metaDescription : null,
        'hero_image' => $heroImage !== '' ? $heroImage : null,
        'is_published' => $isPublished,
    ];

    execute_query(
        "INSERT INTO seasonal_guides
        (month_key, title, intro, highlights_json, best_regions_json, avoid_regions_json, meta_title, meta_description, hero_image, is_published)
        VALUES
        (:month_key, :title, :intro, :highlights_json, :best_regions_json, :avoid_regions_json, :meta_title, :meta_description, :hero_image, :is_published)
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            intro = VALUES(intro),
            highlights_json = VALUES(highlights_json),
            best_regions_json = VALUES(best_regions_json),
            avoid_regions_json = VALUES(avoid_regions_json),
            meta_title = VALUES(meta_title),
            meta_description = VALUES(meta_description),
            hero_image = VALUES(hero_image),
            is_published = VALUES(is_published)",
        $payload
    );

    indexnow_submit_urls([
        base_url('sri-lanka-in-' . $month),
        base_url('seasonal'),
        base_url('sitemap.xml'),
    ]);

    old_input_clear();
    flash_set('success', 'Seasonal guide saved.');
    redirect('admin/seasonal_guides.php');
} catch (Throwable $e) {
    flash_set('error', 'Failed to save seasonal guide.');
    redirect('admin/seasonal_guide_form.php?month=' . rawurlencode($month));
}
