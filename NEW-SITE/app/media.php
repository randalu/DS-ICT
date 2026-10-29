<?php
declare(strict_types=1);

namespace NewSite;

function media_allowlist(): array
{
    static $allowed;
    if ($allowed !== null) {
        return $allowed;
    }
    $allowed = [
        'assets/images/island_culture_travels_logo.png',
        'assets/images/galle-lighthouse.w1200.webp',
        'assets/images/kandy-temple-lk.w1200.webp',
        'assets/images/mirissa-beach-lk.w1200.webp',
        'assets/images/yala-national-park-leopard-safari-large.w800.webp',
        'assets/images/country-market/nine-arch-bridge.jpg',
        'assets/images/country-market/nuwara-eliya-tea.jpg',
        'assets/images/home-hero/home-3.w1200.webp',
    ];
    // Only existing variants of these explicit public images can cross the boundary.
    foreach ($allowed as $original) {
        $stem = preg_replace('/(?:\.w\d+)?\.(?:jpe?g|png|webp|avif)$/i', '', $original);
        foreach ([400, 800, 1200] as $width) {
            foreach (['avif', 'webp'] as $format) {
                $variant = $stem . '.w' . $width . '.' . $format;
                if (is_file(dirname(__DIR__, 2) . '/' . $variant)) {
                    $allowed[] = $variant;
                }
            }
        }
    }
    return $allowed = array_values(array_unique($allowed));
}

function source_image(string $relative): ?array
{
    if (!in_array($relative, media_allowlist(), true)) {
        return null;
    }
    $images = dirname(__DIR__, 2) . '/assets/images';
    $file = contained_file($images, substr($relative, strlen('assets/images/')));
    if ($file === null || !is_readable($file)) {
        return null;
    }
    $types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'avif' => 'image/avif'];
    $expected = $types[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? null;
    $actual = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
    $size = @getimagesize($file);
    if ($expected === null || $actual !== $expected || $size === false || ($size['mime'] ?? '') !== $expected) {
        return null;
    }
    return ['file' => $file, 'type' => $expected, 'width' => $size[0], 'height' => $size[1]];
}
