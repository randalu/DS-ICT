<?php
declare(strict_types=1);

if (!function_exists('detail_icon')) {
    function detail_icon(string $name, string $class = ''): string
    {
        $classAttr = $class !== '' ? ' class="' . e($class) . '"' : '';
        $icons = [
            'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
            'map-pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
            'compass' => '<circle cx="12" cy="12" r="10"/><path d="m16 8-2.2 5.8L8 16l2.2-5.8L16 8Z"/>',
            'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
            'check' => '<path d="m20 6-11 11-5-5"/>',
            'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
            'car' => '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.4 10l-1.7-3.4A3 3 0 0 0 14 5H7a3 3 0 0 0-2.7 1.6L2.6 10 .5 11.1C-.3 11.5-1 12.1-1 13v3c0 .6.4 1 1 1h2" transform="translate(1 0)"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
            'moon' => '<path d="M12 3a6 6 0 0 0 9 7.4A9 9 0 1 1 12 3Z"/>',
            'camera' => '<path d="M14.5 4 16 7h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h3l1.5-3h5Z"/><circle cx="12" cy="13" r="3"/>',
            'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m22 4-10 10.01-3-3"/>',
            'star' => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
            'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'trending-up' => '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
        ];
        $paths = $icons[$name] ?? $icons['check'];
        return '<svg' . $classAttr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
    }
}

if (!function_exists('detail_month_labels')) {
    function detail_month_labels(): array
    {
        return ['january','february','march','april','may','june','july','august','september','october','november','december'];
    }
}

if (!function_exists('detail_image_from_record')) {
    function detail_image_from_record(array $record, array $keys = ['hero_image', 'og_image', 'cover_image']): string
    {
        $path = content_image_path($record, $keys);
        return $path !== '' ? stored_media_url($path) : DEFAULT_OG_IMAGE;
    }
}
