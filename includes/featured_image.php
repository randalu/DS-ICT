<?php
declare(strict_types=1);

/**
 * Featured image partial — backwards-compat wrapper around responsive_image().
 *
 * Reads the same $featuredImage* outer-scope variables it always did, so existing
 * callers (about-us, country, destination, from, etc.) keep working unchanged.
 */

require_once __DIR__ . '/responsive_image.php';

if (empty($featuredImageUrl)) {
    return;
}

$featuredImageAlt = trim((string) ($featuredImageAlt ?? 'Sri Lanka private travel featured image'));
$featuredImageWidth = isset($featuredImageWidth) ? (int) $featuredImageWidth : 1200;
$featuredImageHeight = isset($featuredImageHeight) ? (int) $featuredImageHeight : 675;
$featuredImageClass = trim((string) ($featuredImageClass ?? 'img-fluid rounded-4 mt-4 hero-featured-image'));
$featuredImageLoading = trim((string) ($featuredImageLoading ?? 'eager'));
$featuredImagePriority = trim((string) ($featuredImagePriority ?? 'high'));
$featuredImageSizes = trim((string) ($featuredImageSizes ?? '(min-width: 992px) 1100px, 100vw'));

echo '<figure class="featured-image-wrap mb-0">';
echo responsive_image([
    'src'           => $featuredImageUrl,
    'alt'           => $featuredImageAlt,
    'width'         => $featuredImageWidth,
    'height'        => $featuredImageHeight,
    'sizes'         => $featuredImageSizes,
    'class'         => $featuredImageClass,
    'loading'       => $featuredImageLoading,
    'fetchpriority' => $featuredImagePriority,
]);
echo '</figure>';
