<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/schema.php';

if (!function_exists('og_image_url')) {
    /**
     * Resolve an Open Graph image URL for a content record.
     * Priority: og_image → hero_image → cover_image → generated assets/images/og/{slug}.jpg (only when file exists) → DEFAULT_OG_IMAGE.
     */
    function og_image_url(array $record, string $slugKey = 'slug'): string
    {
        foreach (['og_image', 'hero_image', 'cover_image'] as $key) {
            $raw = trim((string) ($record[$key] ?? ''));
            if ($raw === '') {
                continue;
            }
            $url = upload_url($raw);
            // Skip placeholder/default images so generated OG can be used.
            if (function_exists('is_placeholder_media_url') && is_placeholder_media_url($url)) {
                continue;
            }
            if ($url === DEFAULT_OG_IMAGE) {
                continue;
            }
            return $url;
        }

        $slug = trim((string) ($record[$slugKey] ?? ''));
        if ($slug !== '') {
            $generatedPath = PUBLIC_PATH . '/assets/images/og/' . $slug . '.jpg';
            if (is_file($generatedPath)) {
                return base_url('assets/images/og/' . $slug . '.jpg');
            }
        }

        return DEFAULT_OG_IMAGE;
    }
}
